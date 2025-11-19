<?php

namespace App\Http\Controllers;

use App\Models\Assets;
use App\Models\Disposal;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class DisposalController extends Controller
{
    protected function canReadDisposal(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAction('DISPOSAL', 'R');
    }

    public function index()
    {
        // KEEP: using type = 'Transfer' (your original behavior)
        $workflows = MasterStatus::query()
            ->where('type', 'Transfer')
            ->orderBy('kode')
            ->get(['kode', 'name']);
        return view('disposal.disposal', compact('workflows'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'C'), 403);
        $data = $request->validate([
            'asset_uuid'    => ['required', 'uuid', 'exists:assets,uuid'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'target_status' => ['nullable', 'string'],
            'file'          => [
                'nullable',
                'file',
                'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt',
                'max:20480',
            ],
        ]);
        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $asset = Assets::select('uuid', 'asset_code', 'kode_status')
            ->findOrFail($data['asset_uuid']);

        $target = $data['target_status'] ?? 'DIS';

        $ok = MasterStatus::where('kode', $target)
            ->where(function ($q) {
                $q->where('type', 'Asset')->orWhereNull('type');
            })
            ->exists();

        abort_unless($ok, 422, 'Target disposal status not valid.');

        $now    = Carbon::now();
        $prefix = 'DSP' . $now->format('ym');

        $last = Disposal::where('disposal_code', 'like', $prefix . '%')
            ->orderBy('disposal_code', 'desc')
            ->first();

        if ($last) {
            $lastSeq = (int) substr($last->disposal_code, -4);
            $seq     = $lastSeq + 1;
        } else {
            $seq = 1;
        }

        $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $uuid = (string) Str::uuid();

        [$path, $orig, $mime, $size] = $this->saveUpload(
            $request->file('file'),
            $asset,
            $code,
            null
        );

        $row = Disposal::create([
            'uuid'            => $uuid,
            'asset_uuid'      => $asset->uuid,
            'disposal_code'   => $code,
            'target_status'   => $target,
            'kode_status'     => 'APR',
            'note'            => $data['note'] ?? null,
            'file_path'       => $path,
            'pic_request_uid' => $uid,
            'file_name'       => $orig,
            'file_mime'       => $mime,
            'file_size'       => $size,
            'before_status'   => $asset->kode_status,

            // NEW: initial flow (User -> Dept Head -> Asset Mgt)
            'flow'            => $this->buildFlowTemplate($uid),
        ]);

        return response()->json([
            'ok'   => true,
            'id'   => $row->uuid,
            'code' => $row->disposal_code,
        ]);
    }

    public function show(string $uuid)
    {
        $d = Disposal::with(['asset'])->findOrFail($uuid);

        // NEW: normalize flow for frontend (optional, backward-compatible)
        $flow = $d->flow ?? null;
        if (is_string($flow) && $flow !== '') {
            $decoded = json_decode($flow, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flow = $decoded;
            } else {
                $flow = null;
            }
        }
        if (!$flow) {
            // If you open old record without flow, build default one (do NOT change kode_status here)
            $flow = $this->buildFlowTemplate($d->pic_request_uid ?? null);
        }

        return response()->json([
            'uuid'          => $d->uuid,
            'asset_uuid'    => $d->asset_uuid,
            'asset_code'    => $d->asset?->asset_code,
            'note'          => $d->note,
            'target_status' => $d->target_status,
            'kode_status'   => $d->kode_status,
            'file_url'      => $d->file_url,
            'file_name'     => $d->file_name,

            // NEW extra info for flow UI (safe even if columns not there yet)
            'ba_file_url'   => $d->ba_file_url ?? null,
            'ba_file_name'  => $d->ba_file_name ?? null,
            'flow'          => $flow,
        ]);
    }

    public function update(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'U'), 403);
        $d = Disposal::findOrFail($uuid);
        abort_if($d->kode_status !== 'APR', 422, 'Only pending requests can be edited.');

        $data = $request->validate([
            'asset_uuid'    => ['required', 'uuid', 'exists:assets,uuid'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'target_status' => ['nullable', 'string'],
            'remove_file'   => ['nullable', Rule::in(['0', '1'])],
            'file'          => ['nullable', 'file', 'max:10240'],
        ]);

        $target = $data['target_status'] ?? 'DIS';

        $ok = MasterStatus::where('kode', $target)->where(function ($q) {
            $q->where('type', 'Asset')->orWhereNull('type');
        })->exists();
        abort_unless($ok, 422, 'Target disposal status not valid.');

        $d->asset_uuid    = $data['asset_uuid'];
        $d->note          = $data['note'] ?? null;
        $d->target_status = $target;
        $asset = Assets::with(['assignment'])->findOrFail($data['asset_uuid']);

        // file handling
        if (($data['remove_file'] ?? '0') === '1') {
            if ($d->file_path) Storage::disk('public')->delete($d->file_path);
            $d->file_path = null;
        }
        if ($request->hasFile('file')) {
            [$path, $orig, $mime, $size] = $this->saveUpload($request->file('file'), $asset, $d->disposal_code, $d);
            $file_path = $path;
            $file_name = $orig;
            $file_mime = $mime;
            $file_size = $size;
        }
        $d->fill([
            'note'        => $data['note'] ?? null,
            'file_path'   => $file_path ?? $d->file_path,
            'file_name'   => $file_name ?? $d->file_name,
            'file_mime'   => $file_mime ?? $d->file_mime,
            'file_size'   => $file_size ?? $d->file_size,
        ])->save();


        return response()->json(['ok' => true]);
    }

    public function approve(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'APR'), 403);
        $d = Disposal::findOrFail($uuid);
        abort_if($d->kode_status !== 'APR', 422, 'Already processed.');

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        DB::transaction(function () use ($d, $uid) {
            $d->update([
                'kode_status'     => 'ACC',
                'pic_approve_uid' => $uid,
            ]);

            Assets::where('uuid', $d->asset_uuid)->update([
                'kode_status' => $d->target_status,
            ]);
        });

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'APR'), 403);
        $d = Disposal::findOrFail($uuid);
        abort_if($d->kode_status !== 'APR', 422, 'Already processed.');

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $d->update([
            'kode_status'     => 'REJ',
            'pic_approve_uid' => $uid,
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'D'), 403);
        $d = Disposal::findOrFail($uuid);
        $d->delete();
        return response()->json(['ok' => true]);
    }

    public function datatable(Request $r, string $assetUuid)
    {
        if (!$this->canReadDisposal()) {
            return DataTables::of(collect())->toJson();
        }
        $q = Disposal::query()
            ->where('asset_uuid', $assetUuid)
            ->with(['status', 'target'])
            ->orderByDesc('created_at');
        $user = $r->user();
        $canEdit   = $user && $user->hasAction('DISPOSAL', 'U');
        $canDelete = $user && $user->hasAction('DISPOSAL', 'D');
        $canApr    = $user && $user->hasAction('DISPOSAL', 'APR');

        return DataTables::of($q)
            ->addColumn('workflow_label', fn($t) => $t->status ? $t->kode_status . ' - ' . $t->status->name : $t->kode_status)
            ->addColumn('target_label',   fn($t) => $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status)
            ->addColumn('file', function ($r) {
                $btns = [];

                // 1) Original attachment from create/edit
                if ($r->file_path) {
                    $url  = url('storage/' . ltrim($r->file_path, '/'));
                    $btns[] = sprintf(
                        '<a class="btn btn-sm btn-light-primary me-1" target="_blank" href="%s">%s</a>',
                        e($url),
                        e($r->file_name ?: 'Attachment')
                    );
                }

                // 2) Uploaded Form Disposal (only if there is an uploaded file)
                if (!empty($r->flow_file_path)) {
                    $url  = url('storage/' . ltrim($r->flow_file_path, '/'));
                    $btns[] = sprintf(
                        '<a class="btn btn-sm btn-light-info me-1" target="_blank" href="%s">%s</a>',
                        e($url),
                        e($r->flow_file_name ?: 'Form Disposal')
                    );
                }

                // 3) Uploaded Berita Acara (only if there is an uploaded file)
                if (!empty($r->ba_file_path)) {
                    $url  = url('storage/' . ltrim($r->ba_file_path, '/'));
                    $btns[] = sprintf(
                        '<a class="btn btn-sm btn-light-warning" target="_blank" href="%s">%s</a>',
                        e($url),
                        e($r->ba_file_name ?: 'Berita Acara')
                    );
                }

                return $btns ? implode(' ', $btns) : '';
            })



            ->addColumn('actions', function ($t) use ($canEdit, $canDelete, $canApr) {
                $pending = $t->kode_status === 'APR';
                $btns = '<div class="btn-group btn-group-sm">';
                if ($pending) {
                    if ($canEdit) {
                        $btns .= '<button class="btn btn-light-primary btn-ds-edit" data-id="' . $t->uuid . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-ds-approve" data-id="' . $t->uuid . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-ds-reject" data-id="' . $t->uuid . '">Reject</button>';
                    }
                }
                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-ds-delete" data-id="' . $t->uuid . '">Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['file', 'actions'])
            ->toJson();
    }

    public function datatable_all(Request $request)
    {
        if (!$this->canReadDisposal()) {
            return DataTables::of(collect())->toJson();
        }

        $status      = trim((string) $request->input('status', ''));
        $requester   = trim((string) $request->input('requester', ''));
        $updatedFrom = $request->input('updated_from');
        $updatedTo   = $request->input('updated_to');
        $createdFrom = $request->input('created_from');
        $createdTo   = $request->input('created_to');
        $assetQ      = trim((string) $request->input('asset_q', ''));

        $q = Disposal::query()
            ->with(['status', 'target', 'asset'])
            ->orderByDesc('created_at');

        // old workflow param (keep for safety, but status takes priority)
        if ($request->filled('workflow') && $status === '') {
            $q->where('kode_status', $request->string('workflow'));
        }

        if ($status !== '') {
            $q->where('kode_status', $status);
        }

        if ($requester !== '') {
            $q->where('pic_request_uid', $requester);
        }

        if ($updatedFrom) {
            $q->whereDate('updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('updated_at', '<=', $updatedTo);
        }

        if ($createdFrom) {
            $q->whereDate('created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $q->whereDate('created_at', '<=', $createdTo);
        }

        if ($assetQ !== '') {
            $q->whereHas('asset', function ($w) use ($assetQ) {
                $w->where('asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('description', 'ilike', "%{$assetQ}%");
            });
        }

        $user = $request->user();
        $canEdit   = $user && $user->hasAction('DISPOSAL', 'U');
        $canDelete = $user && $user->hasAction('DISPOSAL', 'D');
        $canApr    = $user && $user->hasAction('DISPOSAL', 'APR');

        return DataTables::of($q)
            ->addColumn('asset_label', fn($t) => $t->asset ? $t->asset->asset_code : $t->asset_uuid)
            ->addColumn('workflow_label', fn($t) => $t->status ? $t->kode_status . ' - ' . $t->status->name : $t->kode_status)
            ->addColumn('target_label',   fn($t) => $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status)
            ->addColumn('file', function ($r) {
                $btns = [];

                // 1) Original attachment from create/edit
                if ($r->file_path) {
                    $url  = url('storage/' . ltrim($r->file_path, '/'));
                    $btns[] = sprintf(
                        '<a class="btn btn-sm btn-light-primary me-1" target="_blank" href="%s">%s</a>',
                        e($url),
                        e($r->file_name ?: 'Attachment')
                    );
                }

                // 2) Uploaded Form Disposal (only if there is an uploaded file)
                if (!empty($r->flow_file_path)) {
                    $url  = url('storage/' . ltrim($r->flow_file_path, '/'));
                    $btns[] = sprintf(
                        '<a class="btn btn-sm btn-light-info me-1" target="_blank" href="%s">%s</a>',
                        e($url),
                        e($r->flow_file_name ?: 'Form Disposal')
                    );
                }

                // 3) Uploaded Berita Acara (only if there is an uploaded file)
                if (!empty($r->ba_file_path)) {
                    $url  = url('storage/' . ltrim($r->ba_file_path, '/'));
                    $btns[] = sprintf(
                        '<a class="btn btn-sm btn-light-warning" target="_blank" href="%s">%s</a>',
                        e($url),
                        e($r->ba_file_name ?: 'Berita Acara')
                    );
                }

                return $btns ? implode(' ', $btns) : '';
            })
            ->addColumn('actions', function ($t) use ($canEdit, $canDelete, $canApr) {
                $pending = $t->kode_status === 'APR';
                $btns = '<div class="btn-group btn-group-sm">';
                if ($pending) {
                    if ($canEdit) {
                        $btns .= '<button class="btn btn-light-primary btn-ds-edit" data-id="' . $t->uuid . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-ds-approve" data-id="' . $t->uuid . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-ds-reject" data-id="' . $t->uuid . '">Reject</button>';
                    }
                }
                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-ds-delete" data-id="' . $t->uuid . '">Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['file', 'actions'])
            ->toJson();
    }


    private function saveUpload(?UploadedFile $file, Assets $asset, string $code, ?Disposal $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'disposals/' . $asset->uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);
        $options = ['disk' => $disk];
        $options['visibility'] = 'public';

        if ($existing && $existing->file_path) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }

    /* ======================================================================
     |  NEW: FLOW APPROVAL STEP (User -> Dept.Head -> Asset Mgt + BA upload)
     | ======================================================================*/

    public function approveStep(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'APR'), 403);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        return DB::transaction(function () use ($uuid, $uid, $request) {
            $d = Disposal::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            abort_if($d->kode_status !== 'APR', 422, 'Only pending disposals can be approved.');

            $flow = $d->flow ?? null;
            if (is_string($flow) && $flow !== '') {
                $decoded = json_decode($flow, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $flow = $decoded;
                } else {
                    $flow = null;
                }
            }
            if (!$flow) {
                $flow = $this->buildFlowTemplate($d->pic_request_uid ?: $uid);
            }

            $idx = $this->getNextPendingFlowIndex($flow);
            abort_if($idx === null, 422, 'All steps already approved.');

            $step     = &$flow['steps'][$idx];
            $stepCode = $step['code'] ?? null;
            $now      = now()->toDateTimeString();
            $isLast   = ($idx === count($flow['steps']) - 1);

            // Department validation (exclude SYSADMIN)
            $currentUser = auth()->user();
            $userRoles = $currentUser->roles()->pluck('kode')->toArray();
            $isSysAdmin = in_array('SYSADMIN', $userRoles);

            // DEPT_HEAD validation for first approval (exclude SYSADMIN and USER)
            if ($stepCode === 'dept_head' && in_array('DEPT_HEAD', $userRoles) && !$isSysAdmin && !in_array('USER', $userRoles)) {
                $userDept = $currentUser->kode_department;
                
                if (!$userDept) {
                    abort(422, 'Your department is not set. Please contact administrator.');
                }

                // Load asset with assignment to check owner's department
                $asset = Assets::with(['assignment.owner'])
                    ->where('uuid', $d->asset_uuid)
                    ->firstOrFail();

                $ownerCode = $asset->assignment?->asset_owner;
                if ($ownerCode) {
                    $ownerUc = MasterUserCode::where('kode', $ownerCode)->first();
                    if ($ownerUc && $ownerUc->kode !== $userDept) {
                        abort(422, 'This user department not matching. Expected: ' . $ownerUc->kode);
                    }
                }
            }

            // AM_ADMIN validation for last step (exclude SYSADMIN)
            if ($stepCode === 'asset_mgt' && in_array('AM_ADMIN', $userRoles) && !$isSysAdmin) {
                $userDept = $currentUser->kode_department;
                
                if (!$userDept) {
                    abort(422, 'Your department is not set. Please contact administrator.');
                }

                // Load asset with assignment if not already loaded
                if (!isset($asset)) {
                    $asset = Assets::with(['assignment.owner'])
                        ->where('uuid', $d->asset_uuid)
                        ->firstOrFail();
                }

                // For disposal, AM_ADMIN must match asset owner's department
                $ownerCode = $asset->assignment?->asset_owner;
                if ($ownerCode) {
                    $ownerUc = MasterUserCode::where('kode', $ownerCode)->first();
                    if ($ownerUc && $ownerUc->kode !== $userDept) {
                        abort(422, 'This user department not matching. Expected: ' . $ownerUc->kode);
                    }
                }
            }

            if ($isLast && $stepCode === 'asset_mgt') {
                $request->validate([
                    'ba_file'   => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,bmp', 'max:20480'],
                    'flow_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,bmp', 'max:20480'],
                ]);

                // Save BA
                [$baPath, $baOrig, $baMime, $baSize] = $this->saveBaUpload(
                    $request->file('ba_file'),
                    $d,
                    $d->disposal_code
                );

                $d->ba_file_path = $baPath;
                $d->ba_file_name = $baOrig;
                $d->ba_file_mime = $baMime;
                $d->ba_file_size = $baSize;

                if ($request->hasFile('flow_file')) {
                    [$formPath, $formOrig, $formMime, $formSize] = $this->saveFormUpload(
                        $request->file('flow_file'),
                        $d,
                        $d->disposal_code
                    );

                    $d->flow_file_path = $formPath;
                    $d->flow_file_name = $formOrig;
                    $d->flow_file_mime = $formMime;
                    $d->flow_file_size = $formSize;
                }

                Assets::where('uuid', $d->asset_uuid)->update([
                    'kode_status' => $d->target_status,
                ]);

                $d->kode_status     = 'ACC';
                $d->pic_approve_uid = $uid;
            }

            $step['approved_by'] = $uid;
            $step['approved_at'] = $now;

            $d->flow = $flow;
            $d->save();

            return response()->json([
                'ok'          => true,
                'id'          => $d->uuid,
                'kode_status' => $d->kode_status,
                'flow'        => $flow,
                'ba_file_url' => $d->ba_file_url ?? null,
            ]);
        });
    }
    private function saveFormUpload(UploadedFile $file, Disposal $d, string $code): array
    {
        $disk = 'public';
        $dir  = 'disposals_form/' . $d->asset_uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-form-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        if ($d->flow_file_path) {
            Storage::disk($disk)->delete($d->flow_file_path);
        }

        return [
            $path,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }

    /* ======================================================================
     |  NEW: DOWNLOAD EXCEL "FORM DISPOSAL ASET TETAP" (auto-fill)
     | ======================================================================*/

    public function downloadForm(Request $request, Disposal $disposal)
    {
        abort_unless($this->canReadDisposal(), 403);

        $disposal->load([
            'asset.value',
            'asset.location',
            'asset.assignment.owner.division',
        ]);

        $asset    = $disposal->asset;
        $assign   = $asset?->assignment;
        $owner    = $assign?->owner;
        $ownerDiv = $owner?->division;

        $createdAt   = $disposal->created_at?->timezone('Asia/Jakarta');
        $companyName = config('app.company_name', 'PT LRT Jakarta');

        $deptLabel = $owner?->department ?: '';
        $divLabel  = $ownerDiv?->name ?? '';

        $assetCode = $asset?->asset_code ?? '';
        $assetName = $asset?->asset_name ?? $asset?->description ?? '';

        $acqDate = null;
        if ($asset?->value?->actual_date) {
            $acqDate = $asset->value->actual_date instanceof \Carbon\Carbon
                ? $asset->value->actual_date
                : \Carbon\Carbon::parse($asset->value->actual_date);
        }

        $comCost = $asset?->value?->total ?? 0;

        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $asset?->uuid)
            ->first();

        $comAcc = $ledger?->acc_sum ?? 0; // Commercial Accumulated Depreciation (IDR)
        $comNbv = $ledger?->nbv_sum ?? 0; // Commercial Net Book Value (IDR)

        $taxNbv = $asset?->value?->tax_nbv ?? 0;

        $justification = $disposal->note ?? '';

        $templatePath = storage_path('app/public/template/form_disposal_asset_tetap.xlsx');
        $spreadsheet  = IOFactory::load($templatePath);
        $sheet        = $spreadsheet->getSheetByName('Form Disposal Aset Tetap')
            ?: $spreadsheet->getActiveSheet();

        $sheet->setCellValue('H7',  $companyName);
        $sheet->setCellValue('H8',  $disposal->disposal_code ?? '');
        $sheet->setCellValue('H9',  $createdAt ? $createdAt->format('d-m-Y') : '');
        $sheet->setCellValue('H10', $deptLabel);
        $sheet->setCellValue('H11', $divLabel);

        $sheet->setCellValue('H14', '');

        $sheet->setCellValue('H15', $assetCode);
        $sheet->setCellValue('H16', $assetName);
        $sheet->setCellValue('H17', $acqDate ? $acqDate->format('d-m-Y') : '');

        $sheet->setCellValue('H18', $comCost); // Commercial Acquisition Cost (IDR)
        $sheet->setCellValue('H19', $comAcc);  // Commercial Accumulated Depreciation (IDR)
        $sheet->setCellValue('H20', $comNbv);  // Commercial Net Book Value (IDR)
        $sheet->setCellValue('H21', $taxNbv);  // Tax Net Book Value (IDR)

        $sheet->setCellValue('H22', '');
        $sheet->setCellValue('H23', '');

        $sheet->setCellValue('H24', $justification);

        $fileName = 'Form Disposal Aset Tetap - ' . ($disposal->disposal_code ?? 'DSP') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    /* ======================================================================
     |  NEW: FLOW + FILE HELPERS
     | ======================================================================*/

    /**
     * Build default disposal flow:
     * 1. Create (User Departemen)
     * 2. Approval Dept.Head / Section
     * 3. Pelaksanaan & BA Disposal (Asset Management)
     */
    protected function buildFlowTemplate(?string $creatorName = null): array
    {
        $now = now()->toDateTimeString();

        return [
            'key'   => 'disposal_request',
            'steps' => [
                [
                    'code'        => 'create',
                    'label'       => 'Create Disposal Request',
                    'role'        => 'User Departemen',
                    'approved_by' => $creatorName,
                    'approved_at' => $creatorName ? $now : null,
                ],
                [
                    'code'        => 'dept_head',
                    'label'       => 'Approval Dept.Head / Section',
                    'role'        => 'Dept.Head / Section',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'asset_mgt',
                    'label'       => 'Pelaksanaan & BA Disposal (Asset Management)',
                    'role'        => 'Asset Management',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
            ],
        ];
    }

    protected function getNextPendingFlowIndex(?array $flow): ?int
    {
        if (!is_array($flow) || !isset($flow['steps']) || !is_array($flow['steps'])) {
            return null;
        }
        foreach ($flow['steps'] as $i => $step) {
            if (empty($step['approved_at'])) {
                return $i;
            }
        }
        return null;
    }

    private function saveBaUpload(UploadedFile $file, Disposal $d, string $code): array
    {
        $disk = 'public';
        $dir  = 'disposals_ba/' . $d->asset_uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-ba-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        if ($d->ba_file_path) {
            Storage::disk($disk)->delete($d->ba_file_path);
        }

        return [
            $path,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }

    public function downloadBa(Request $request, Disposal $disposal)
    {
        abort_unless($this->canReadDisposal(), 403);

        $disposal->load([
            'asset.location',
            'asset.assignment.owner',
        ]);

        $asset    = $disposal->asset;
        $location = $asset?->location;
        $owner    = $asset?->assignment?->owner;

        $assetName   = $asset?->asset_name ?? $asset?->description ?? '';
        $assetCode   = $asset?->asset_code ?? '';
        $formNumber  = $disposal->disposal_code ?? '';
        $locationLbl = $location
            ? trim(($location->kode ?? '') . ' ' . ($location->name ?? ''))
            : '';

        $keterangan  = $disposal->note ?? '';

        // tanggal BA: pakai updated_at jika sudah ACC, kalau tidak pakai now()
        $date = $disposal->updated_at
            ? $disposal->updated_at->copy()->timezone('Asia/Jakarta')
            : now('Asia/Jakarta');

        Carbon::setLocale('id'); // so translatedFormat uses Indonesian

        $hari   = $date->translatedFormat('l');        // Senin, Selasa, ...
        $tglBT  = $date->translatedFormat('d F Y');    // 17 November 2025

        $templatePath = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        $template     = new TemplateProcessor($templatePath);

        // these KEYS must match the placeholders inside the DOCX (without ${})
        $template->setValues([
            'HARI'                     => $hari,
            'TANGGAL_BULAN_TAHUN'      => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER' => $tglBT,
            'NAMA_ASET'                => $assetName,
            'NOMOR_ASET'               => $assetCode,
            'NOMOR_FORM'               => $formNumber,
            'LOKASI'                   => $locationLbl,
            'KETERANGAN'               => $keterangan,
        ]);

        $fileName = 'BA Disposal - ' . ($disposal->disposal_code ?? 'DSP') . '.docx';

        return response()->streamDownload(function () use ($template) {
            $template->saveAs('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
