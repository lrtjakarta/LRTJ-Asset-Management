<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferStoreRequest;
use App\Http\Requests\TransferDecisionRequest;
use App\Models\Assets;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use App\Models\User;
use App\Services\TransferCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class TransferController extends Controller
{
    protected function canReadMovement(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAction('MOVEMENT', 'R');
    }

    protected function canApproveMovement(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAction('MOVEMENT', 'APR');
    }

    public function index()
    {
        $workflows = MasterStatus::query()
            ->where('type', 'Transfer')
            ->orderBy('kode')
            ->get(['kode', 'name']);

        return view('transfers.transfers', compact('workflows'));
    }

    public function datatable_all(Request $request)
    {
        if (!$this->canReadMovement()) {
            return DataTables::of(collect())->toJson();
        }

        $user        = $request->user();
        $canEdit     = $user && $user->hasAction('MOVEMENT', 'U');
        $canDelete   = $user && $user->hasAction('MOVEMENT', 'D');
        $canApr      = $this->canApproveMovement();

        $type        = trim((string) $request->input('type', ''));
        $status      = trim((string) $request->input('status', ''));
        $requester   = trim((string) $request->input('requester', ''));
        $updatedFrom = $request->input('updated_from');
        $updatedTo   = $request->input('updated_to');
        $createdFrom = $request->input('created_from');
        $createdTo   = $request->input('created_to');
        $assetQ      = trim((string) $request->input('asset_q', ''));

        $q = Transfer::query()
            ->from('assets_transfers')
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_transfers.asset_uuid')
            ->with('status')
            ->select([
                'assets_transfers.*',
                'a.asset_code',
                'a.description',
            ])
            ->whereNull('assets_transfers.deleted_at')
            ->orderByDesc('assets_transfers.updated_at');

        if ($type !== '') {
            $q->where('assets_transfers.type', $type);
        }

        if ($status !== '') {
            $q->where('assets_transfers.kode_status', $status);
        }

        if ($requester !== '') {
            $q->where('assets_transfers.pic_request_uid', $requester);
        }

        if ($updatedFrom) {
            $q->whereDate('assets_transfers.updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('assets_transfers.updated_at', '<=', $updatedTo);
        }
        if ($createdFrom) {
            $q->whereDate('assets_transfers.created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $q->whereDate('assets_transfers.created_at', '<=', $createdTo);
        }
        if ($assetQ !== '') {
            $q->where(function ($w) use ($assetQ) {
                $w->where('a.asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('a.description', 'ilike', "%{$assetQ}%");
            });
        }

        return DataTables::of($q)
            ->addColumn('asset_label', function ($r) {
                $code = $r->asset_code ?? $r->asset_uuid;
                $desc = $r->description ? (' - ' . $r->description) : '';
                return $code . $desc;
            })
            ->addColumn('workflow_label', function ($r) {
                return $r->status
                    ? ($r->kode_status . ' - ' . $r->status->name)
                    : ($r->kode_status ?? '');
            })
            ->addColumn(
                'before_show',
                fn($r) => $this->resolveLabel($r->type, data_get($r->before, 'value'))
            )
            ->addColumn(
                'after_show',
                fn($r) => $this->resolveLabel($r->type, data_get($r->after, 'value'))
            )
            ->filterColumn('asset_label', function ($builder, $keyword) {
                $kw = '%' . mb_strtolower($keyword) . '%';
                $builder->where(function ($w) use ($kw) {
                    $w->whereRaw('LOWER(a.asset_code) LIKE ?', [$kw])
                        ->orWhereRaw('LOWER(a.description) LIKE ?', [$kw]);
                });
            })
            ->filterColumn('before_show', function ($builder, $keyword) {
                $kw = '%' . mb_strtolower($keyword) . '%';
                $builder->whereRaw('LOWER(CAST(assets_transfers.before AS TEXT)) LIKE ?', [$kw]);
            })
            ->filterColumn('after_show', function ($builder, $keyword) {
                $kw = '%' . mb_strtolower($keyword) . '%';
                $builder->whereRaw('LOWER(CAST(assets_transfers.after AS TEXT)) LIKE ?', [$kw]);
            })
            ->addColumn('file', function ($r) {
                $links = [];

                if ($r->file_path) {
                    $url  = url('storage/' . ltrim($r->file_path, '/'));
                    $name = $r->file_name ?: 'Request Attachment';

                    $links[] = '<a class="btn btn-sm btn-light-primary me-1" target="_blank" href="' . e($url) . '">'
                        . e($name) . '</a>';
                }

                if ($r->flow_file_path) {
                    $url  = url('storage/' . ltrim($r->flow_file_path, '/'));
                    $name = $r->flow_file_name ?: 'Signed Form';

                    $links[] = '<a class="btn btn-sm btn-light-success" target="_blank" href="' . e($url) . '">'
                        . e($name) . '</a>';
                }

                if (! count($links)) {
                    return '';
                }

                return implode(' ', $links);
            })

            ->addColumn('actions', function ($r) use ($canEdit, $canDelete, $canApr) {
                $pending = $r->kode_status === 'APR';
                $id      = e($r->uuid);

                $btns = '<div class="btn-group btn-group-sm">';

                if ($pending) {
                    if ($canEdit) {
                        // $btns .= '<button class="btn btn-light-primary btn-tf-edit" data-id="' . $id . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-tf-approve" data-id="' . $id . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-tf-reject" data-id="' . $id . '">Reject</button>';
                    }
                }
                if (in_array($r->type, ['owner', 'user', 'maintenance'], true)) {
                    $btns .= '<a href="' . route('transfer.form', $id) . '" class="btn btn-light-primary btn-sm me-1" target="_blank">
                Form
              </a>';
                }

                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-tf-delete" data-id="' . $id . '">Delete</button>';
                }

                $btns .= '</div>';
                return $btns;
            })
            ->addColumn('flow_label', function ($r) {
                if ($r->kode_status === 'REJ') {
                    $flow = is_array($r->flow) ? $r->flow : [];
                    $by   = $flow['rejected_by'] ?? $r->pic_approve_uid ?? '-';
                    return 'Rejected (by ' . e($by) . ')';
                }

                if (!is_array($r->flow)) return '';

                $steps = $r->flow['steps'] ?? [];
                if (!is_array($steps) || !count($steps)) return '';

                $nextIdx = null;
                foreach ($steps as $idx => $step) {
                    if (empty($step['approved_at'])) {
                        $nextIdx = $idx;
                        break;
                    }
                }

                if ($nextIdx === null) {
                    $last = end($steps);
                    $by   = $last['approved_by'] ?? '-';
                    return 'Completed (by ' . e($by) . ')';
                }

                $step  = $steps[$nextIdx];
                $role  = $step['role'] ?? $step['label'] ?? 'Step';
                return 'Waiting: ' . e($role);
            })

            ->rawColumns(['file', 'actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'C'), 403);

        $data = $request->validate([
            'asset_uuid'   => ['required', 'uuid', 'exists:assets,uuid'],
            'type'         => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'  => ['required', 'string'],
            'note'         => ['nullable', 'string', 'max:1000'],
            'file'         => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
        ]);

        $currentUid = auth()->user()?->name;
        abort_if(!$currentUid, 401, 'No session UID.');

        $asset    = Assets::with(['assignment', 'status', 'location'])->findOrFail($data['asset_uuid']);
        $totalRow = Transfer::where('asset_uuid', $data['asset_uuid'])->count();

        $beforeCode = null;
        switch ($data['type']) {
            case 'owner':
                $beforeCode = $asset->assignment?->asset_owner;
                break;
            case 'user':
                $beforeCode = $asset->assignment?->asset_user;
                break;
            case 'maintenance':
                $beforeCode = $asset->assignment?->asset_maintenance;
                break;
            case 'status':
                $beforeCode = $asset->kode_status;
                break;
            case 'location':
                $beforeCode = $asset->kode_location;
                break;
        }

        $afterCode       = (string) $data['after']['value'];
        $beforeLabel     = $this->resolveLabel($data['type'], $beforeCode);
        $afterLabel      = $this->resolveLabel($data['type'], $afterCode);
        $initialWorkflow = 'APR';

        $transfer = DB::transaction(function () use (
            $asset,
            $data,
            $beforeCode,
            $afterCode,
            $beforeLabel,
            $afterLabel,
            $initialWorkflow,
            $currentUid,
            $totalRow,
            $request
        ) {
            $now    = Carbon::now();
            $prefix = 'MOV' . $now->format('ym');

            $last = Transfer::where('transfer_code', 'like', $prefix . '%')
                ->orderBy('transfer_code', 'desc')
                ->first();

            if ($last) {
                $lastSeq = (int) substr($last->transfer_code, -4);
                $seq     = $lastSeq + 1;
            } else {
                $seq = 1;
            }

            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            [$path, $orig, $mime, $size] = $this->saveUpload($request->file('file'), $asset, $code, null);

            $payload = [
                'uuid'            => (string) Str::uuid(),
                'asset_uuid'      => $asset->uuid,
                'transfer_code'   => $code,
                'type'            => $data['type'],
                'before'          => ['value' => $beforeCode],
                'after'           => ['value' => $afterCode],
                'before_label'    => $beforeLabel,
                'after_label'     => $afterLabel,
                'kode_status'     => $initialWorkflow,
                'note'            => $data['note'] ?? null,
                'pic_request_uid' => $currentUid,
                'pic_approve_uid' => null,
                'file_path'       => $path,
                'file_name'       => $orig,
                'file_mime'       => $mime,
                'file_size'       => $size,
            ];

            if (in_array($data['type'], ['owner', 'user', 'maintenance', 'location'], true)) {
                $payload['flow'] = $this->buildFlowTemplate($data['type'], $currentUid);
            }

            return Transfer::create($payload);
        });

        return response()->json([
            'ok'   => true,
            'id'   => $transfer->uuid,
            'code' => $transfer->transfer_code,
        ]);
    }

    public function show(string $id)
    {
        $transfer = Transfer::where('uuid', $id)->firstOrFail();

        $asset = Assets::select('uuid', 'asset_code', 'description')
            ->find($transfer->asset_uuid);

        $flow = $transfer->flow;

        if (is_string($flow) && $flow !== '') {
            $decoded = json_decode($flow, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flow = $decoded;
            } else {
                $flow = null;
            }
        }

        if (in_array($transfer->type, ['owner', 'user', 'maintenance', 'location'], true)) {
            $needsNewFlow =
                !is_array($flow) ||
                !isset($flow['steps']) ||
                !is_array($flow['steps']) ||
                !count($flow['steps']);

            if ($needsNewFlow) {
                $creator = $transfer->pic_request_uid ?: (auth()->user()->name ?? null);
                $flow    = $this->buildFlowTemplate($transfer->type, $creator);

                $transfer->flow = $flow;
                $transfer->save();
            }
        }

        return response()->json([
            'uuid'        => $transfer->uuid,
            'asset_uuid'  => $transfer->asset_uuid,
            'asset_code'  => $asset->asset_code ?? null,
            'asset_desc'  => $asset->description ?? null,
            'asset_label' => $asset
                ? $asset->asset_code . ($asset->description ? (' - ' . $asset->description) : '')
                : null,
            'type'        => $transfer->type,
            'before'      => $transfer->before,
            'after'       => $transfer->after,
            'note'        => $transfer->note,
            'kode_status' => $transfer->kode_status,
            'flow'        => $flow,
            'file_url'    => $transfer->file_url,
            'file_name'   => $transfer->file_name,
        ]);
    }

    public function update(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'U'), 403);

        $data = $request->validate([
            'asset_uuid'  => ['required', 'uuid', 'exists:assets,uuid'],
            'type'        => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value' => ['required', 'string'],
            'note'        => ['nullable', 'string', 'max:1000'],
            'file'        => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
        ]);

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be edited.');

        $asset = Assets::with(['assignment'])->findOrFail($data['asset_uuid']);

        $before = $this->currentValueForType($asset, $data['type']);
        $after  = ['value' => $data['after']['value']];

        if ($request->boolean('remove_file')) {
            if ($tf->file_path) Storage::disk('public')->delete($tf->file_path);
            $file_path = null;
            $file_name = null;
            $file_mime = null;
            $file_size = null;
        }

        if ($request->hasFile('file')) {
            [$path, $orig, $mime, $size] = $this->saveUpload($request->file('file'), $asset, $tf->transfer_code, $tf);
            $file_path = $path;
            $file_name = $orig;
            $file_mime = $mime;
            $file_size = $size;
        }

        $tf->fill([
            'type'       => $data['type'],
            'before'     => $before,
            'after'      => $after,
            'note'       => $data['note'] ?? null,
            'file_path'  => $file_path ?? $tf->file_path,
            'file_name'  => $file_name ?? $tf->file_name,
            'file_mime'  => $file_mime ?? $tf->file_mime,
            'file_size'  => $file_size ?? $tf->file_size,
        ])->save();

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'D'), 403);

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        $tf->delete();

        return response()->json(['ok' => true]);
    }

    public function approve(Request $request, string $uuid)
    {
        abort_unless($this->canApproveMovement(), 403);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        DB::transaction(function () use ($uuid, $uid) {
            $tf = Transfer::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be approved.');

            if (in_array($tf->type, ['owner', 'user', 'maintenance', 'location'], true) && !empty($tf->flow)) {
                abort(422, 'Use flow approval endpoint for this transfer.');
            }

            $asset = Assets::where('uuid', $tf->asset_uuid)->lockForUpdate()->firstOrFail();
            $val   = data_get($tf->after, 'value');

            switch ($tf->type) {
                case 'owner':
                    $asset->assignment()->updateOrCreate(['asset_uuid' => $asset->uuid], ['asset_owner' => $val]);
                    break;
                case 'user':
                    $asset->assignment()->updateOrCreate(['asset_uuid' => $asset->uuid], ['asset_user' => $val]);
                    break;
                case 'maintenance':
                    $asset->assignment()->updateOrCreate(['asset_uuid' => $asset->uuid], ['asset_maintenance' => $val]);
                    break;
                case 'status':
                    $asset->kode_status = $val;
                    $asset->save();
                    break;
                case 'location':
                    $asset->kode_location = $val;
                    $asset->save();
                    break;
            }

            $tf->kode_status     = 'ACC';
            $tf->pic_approve_uid = $uid;
            $tf->save();
        });

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, string $uuid)
    {
        abort_unless($this->canApproveMovement(), 403);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be rejected.');

        if (in_array(strtolower((string) $tf->type), ['owner', 'user', 'maintenance', 'location'], true)) {
            $flow = $tf->flow ?? $this->buildFlowTemplate($tf->type, $tf->pic_request_uid);

            $now = now()->toDateTimeString();

            if (is_array($flow) && isset($flow['steps']) && is_array($flow['steps'])) {
                $nextIdx = $this->getNextPendingFlowIndex($flow);
                if ($nextIdx !== null) {
                    $step = &$flow['steps'][$nextIdx];
                    $step['rejected_by'] = $uid;
                    $step['rejected_at'] = $now;
                }
            }

            $flow['rejected_by'] = $uid;
            $flow['rejected_at'] = $now;

            $tf->flow = $flow;
        }

        $tf->kode_status     = 'REJ';
        $tf->pic_approve_uid = $uid;
        $tf->save();

        return response()->json(['ok' => true]);
    }

    public function indexByAsset(Request $req, string $assetUuid)
    {
        $rows = Transfer::where('asset_uuid', $assetUuid)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['results' => $rows]);
    }

    public function datatable(Request $request, string $assetUuid)
    {
        if (!$this->canReadMovement()) {
            return DataTables::of(collect())->toJson();
        }

        $user      = $request->user();
        $canEdit   = $user && $user->hasAction('MOVEMENT', 'U');
        $canDelete = $user && $user->hasAction('MOVEMENT', 'D');
        $canApr    = $this->canApproveMovement();

        $q = Transfer::query()
            ->where('asset_uuid', $assetUuid)
            ->with('status')
            ->orderByDesc('created_at');

        return DataTables::of($q)
            ->addColumn('workflow_label', function (Transfer $r) {
                return $r->status ? ($r->kode_status . ' - ' . $r->status->name) : $r->kode_status;
            })
            ->addColumn('before_code', fn(Transfer $t) => data_get($t->before, 'value'))
            ->addColumn('after_code',  fn(Transfer $t) => data_get($t->after,  'value'))
            ->addColumn('before_display', function (Transfer $t) {
                return $this->resolveLabel($t->type, data_get($t->before, 'value'));
            })
            ->addColumn('after_display', function (Transfer $t) {
                return $this->resolveLabel($t->type, data_get($t->after, 'value'));
            })
            ->addColumn('file', function ($r) {
                $links = [];

                if ($r->file_path) {
                    $url  = url('storage/' . ltrim($r->file_path, '/'));
                    $name = $r->file_name ?: 'Request Attachment';

                    $links[] = '<a class="btn btn-sm btn-light-primary me-1" target="_blank" href="' . e($url) . '">'
                        . e($name) . '</a>';
                }

                if ($r->flow_file_path) {
                    $url  = url('storage/' . ltrim($r->flow_file_path, '/'));
                    $name = $r->flow_file_name ?: 'Signed Form';

                    $links[] = '<a class="btn btn-sm btn-light-success" target="_blank" href="' . e($url) . '">'
                        . e($name) . '</a>';
                }

                if (! count($links)) {
                    return '';
                }

                return implode(' ', $links);
            })

            ->addColumn('actions', function ($r) use ($canEdit, $canDelete, $canApr) {
                $pending = $r->kode_status === 'APR';
                $id      = e($r->uuid);

                $btns = '<div class="btn-group btn-group-sm">';

                if ($pending) {
                    if ($canEdit) {
                        // $btns .= '<button class="btn btn-light-primary btn-tf-edit" data-id="' . $id . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-tf-approve" data-id="' . $id . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-tf-reject" data-id="' . $id . '">Reject</button>';
                    }
                }
                if (in_array($r->type, ['owner', 'user', 'maintenance'], true)) {
                    $btns .= '<a href="' . route('transfer.form', $id) . '" class="btn btn-light-primary btn-sm me-1" target="_blank">
                Form
              </a>';
                }

                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-tf-delete" data-id="' . $id . '">Delete</button>';
                }

                $btns .= '</div>';
                return $btns;
            })
            ->addColumn('flow_label', function ($r) {
                if ($r->kode_status === 'REJ') {
                    $flow = is_array($r->flow) ? $r->flow : [];
                    $by   = $flow['rejected_by'] ?? $r->pic_approve_uid ?? '-';
                    return 'Rejected (by ' . e($by) . ')';
                }

                if (!is_array($r->flow)) return '';

                $steps = $r->flow['steps'] ?? [];
                if (!is_array($steps) || !count($steps)) return '';

                $nextIdx = null;
                foreach ($steps as $idx => $step) {
                    if (empty($step['approved_at'])) {
                        $nextIdx = $idx;
                        break;
                    }
                }

                if ($nextIdx === null) {
                    $last = end($steps);
                    $by   = $last['approved_by'] ?? '-';
                    return 'Completed (by ' . e($by) . ')';
                }

                $step  = $steps[$nextIdx];
                $role  = $step['role'] ?? $step['label'] ?? 'Step';
                return 'Waiting: ' . e($role);
            })

            ->rawColumns(['file', 'actions'])
            ->toJson();
    }

    protected function buildBeforeAfter(Assets $asset, string $type, string $afterValue): array
    {
        $before = ['value' => null, 'label' => null];
        $after  = ['value' => $afterValue, 'label' => null];

        switch ($type) {
            case 'owner':
                $before['value'] = $asset->assignment?->asset_owner;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->assignment?->owner)->department) : null;
                $uc = MasterUserCode::where('kode', $afterValue)->first();
                $after['label'] = $uc ? ($uc->kode . ' - ' . $uc->department) : $afterValue;
                break;

            case 'user':
                $before['value'] = $asset->assignment?->asset_user;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->assignment?->user)->department) : null;
                $uc = MasterUserCode::where('kode', $afterValue)->first();
                $after['label'] = $uc ? ($uc->kode . ' - ' . $uc->department) : $afterValue;
                break;

            case 'maintenance':
                $before['value'] = $asset->assignment?->asset_maintenance;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->assignment?->maintenance)->department) : null;
                $uc = MasterUserCode::where('kode', $afterValue)->first();
                $after['label'] = $uc ? ($uc->kode . ' - ' . $uc->department) : $afterValue;
                break;

            case 'status':
                $before['value'] = $asset->kode_status;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->status)->name) : null;
                $st = MasterStatus::where('kode', $afterValue)->first();
                $after['label'] = $st ? ($st->kode . ' - ' . $st->name) : $afterValue;
                break;

            case 'location':
                $before['value'] = $asset->kode_location;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->location)->name) : null;
                $loc = MasterLocation::where('kode', $afterValue)->first();
                $after['label'] = $loc ? ($loc->kode . ' - ' . $loc->name) : $afterValue;
                break;
        }

        return [$before, $after];
    }

    private function resolveLabel(?string $type, ?string $code): string
    {
        $type = is_string($type) ? trim($type) : '';
        $code = is_string($code) ? trim($code) : '';

        if ($code === '') {
            return '(empty)';
        }
        if ($type === '') {
            return $code;
        }

        static $cache = [
            'usercode' => [],
            'status'   => [],
            'location' => [],
        ];
        $k  = $code;
        $ck = mb_strtoupper($code);

        switch ($type) {
            case 'owner':
            case 'user':
            case 'maintenance':
                if (!isset($cache['usercode'][$ck])) {
                    $row = MasterUserCode::select('kode', 'department')
                        ->where('kode', $code)
                        ->first();
                    $cache['usercode'][$ck] = $row ? "{$k} - {$row->department}" : $k;
                }
                return $cache['usercode'][$ck];

            case 'status':
                if (!isset($cache['status'][$ck])) {
                    $row = MasterStatus::select('kode', 'name', 'type')
                        ->where('kode', $code)
                        ->where(function ($q) {
                            $q->where('type', 'Asset')->orWhereNull('type');
                        })
                        ->first();
                    $cache['status'][$ck] = $row ? "{$k} - {$row->name}" : $k;
                }
                return $cache['status'][$ck];

            case 'location':
                if (!isset($cache['location'][$ck])) {
                    $row = MasterLocation::select('kode', 'name')
                        ->where('kode', $code)
                        ->first();
                    $cache['location'][$ck] = $row ? "{$k} - {$row->name}" : $k;
                }
                return $cache['location'][$ck];

            default:
                return $k;
        }
    }

    private function currentValueForType(Assets $asset, string $type): array
    {
        switch ($type) {
            case 'owner':
                return ['value' => $asset->assignment?->asset_owner];
            case 'user':
                return ['value' => $asset->assignment?->asset_user];
            case 'maintenance':
                return ['value' => $asset->assignment?->asset_maintenance];
            case 'status':
                return ['value' => $asset->kode_status];
            case 'location':
                return ['value' => $asset->kode_location];
            default:
                return ['value' => null];
        }
    }

    private function saveUpload(?UploadedFile $file, Assets $asset, string $code, ?Transfer $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'transfers/' . $asset->uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        if ($existing && $existing->file_path) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }
    private function saveFlowUpload(?UploadedFile $file, Assets $asset, string $code, ?Transfer $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'transfers/' . $asset->uuid . '/flow';
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-FLOW-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        if ($existing && $existing->flow_file_path) {
            Storage::disk($disk)->delete($existing->flow_file_path);
        }

        return [
            $path,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }

    protected function buildFlowTemplate(string $type, ?string $creatorName = null): array
    {
        $now = now()->toDateTimeString();

        if ($type === 'location') {
            return [
                'key'   => 'movement_location',
                'steps' => [
                    [
                        'code'        => 'create',
                        'label'       => 'Create',
                        'role'        => 'User Departemen',
                        'approved_by' => $creatorName,
                        'approved_at' => $creatorName ? $now : null,
                    ],
                    [
                        'code'        => 'dept_head',
                        'label'       => 'Approval Dept.Head / Section',
                        'role'        => 'User - Dept.Head / Section',
                        'approved_by' => null,
                        'approved_at' => null,
                    ],
                    [
                        'code'        => 'asset_mgt',
                        'label'       => 'Completed (Asset Management)',
                        'role'        => 'Asset Management',
                        'approved_by' => null,
                        'approved_at' => null,
                    ],
                ],
            ];
        }

        return [
            'key'   => 'movement_assignment',
            'steps' => [
                [
                    'code'        => 'create',
                    'label'       => 'Create Request',
                    'role'        => 'User Departemen',
                    'approved_by' => $creatorName,
                    'approved_at' => $creatorName ? $now : null,
                ],
                [
                    'code'        => 'new_dept_head',
                    'label'       => 'Approval Dept.Head / Section (Move To)',
                    'role'        => 'User - Dept.Head / Section',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'old_dept_head',
                    'label'       => 'Approval Dept.Head / Section (Move From)',
                    'role'        => 'User - Dept.Head / Section',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'asset_mgt',
                    'label'       => 'Completed (Asset Management)',
                    'role'        => 'Asset Management',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
            ],
        ];
    }

    protected function buildLocationFlowTemplate(?string $creatorName = null): array
    {
        return $this->buildFlowTemplate('location', $creatorName);
    }

    protected function getNextPendingFlowIndex(?array $flow): ?int
    {
        if (!is_array($flow) || !isset($flow['steps']) || !is_array($flow['steps'])) {
            return null;
        }

        foreach ($flow['steps'] as $idx => $step) {
            if (empty($step['approved_at'])) {
                return $idx;
            }
        }

        return null;
    }
    public function approveStep(Request $request, string $uuid)
    {
        abort_unless($this->canApproveMovement(), 403);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $now = now()->toDateTimeString();

        return DB::transaction(function () use ($uuid, $uid, $now, $request) {
            $tf = Transfer::where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be approved.');

            $flow = $tf->flow ?? $this->buildLocationFlowTemplate($tf->pic_request_uid);

            $nextIdx = $this->getNextPendingFlowIndex($flow);
            abort_if($nextIdx === null, 422, 'All steps already approved.');

            $step = &$flow['steps'][$nextIdx];

            // Department validation (exclude SYSADMIN)
            $currentUser = auth()->user();
            $userRoles = $currentUser->roles()->pluck('kode')->toArray();
            $isSysAdmin = in_array('SYSADMIN', $userRoles);

            // DEPT_HEAD validation (exclude SYSADMIN and USER)
            if (in_array('DEPT_HEAD', $userRoles) && !$isSysAdmin && !in_array('USER', $userRoles)) {
                $userDept = $currentUser->kode_department;

                if (!$userDept) {
                    abort(422, 'Your department is not set. Please contact administrator.');
                }

                // Load asset with assignment relationships
                $asset = Assets::with(['assignment.owner', 'assignment.user', 'assignment.maintenance'])
                    ->where('uuid', $tf->asset_uuid)
                    ->firstOrFail();

                $stepCode = $step['code'] ?? '';

                if ($tf->type === 'location') {
                    // For location type, first approval (dept_head) must match asset owner's department
                    if ($stepCode === 'dept_head') {
                        $ownerCode = $asset->assignment?->asset_owner;
                        if ($ownerCode) {
                            $ownerUc = MasterUserCode::where('kode', $ownerCode)->first();
                            if ($ownerUc && $ownerUc->kode !== $userDept) {
                                abort(422, 'This user department not matching. Expected: ' . $ownerUc->kode);
                            }
                        }
                    }
                } elseif (in_array($tf->type, ['owner', 'user', 'maintenance'], true)) {
                    $beforeVal = data_get($tf->before, 'value');
                    $afterVal  = data_get($tf->after, 'value');

                    if ($stepCode === 'new_dept_head') {
                        // First approval: must match NEW (after) department
                        if ($afterVal) {
                            $afterUc = MasterUserCode::where('kode', $afterVal)->first();
                            if ($afterUc && $afterUc->kode !== $userDept) {
                                abort(422, 'This user department not matching. Expected: ' . $afterUc->kode);
                            }
                        }
                    } elseif ($stepCode === 'old_dept_head') {
                        // Second approval: must match OLD (before) department
                        if ($beforeVal) {
                            $beforeUc = MasterUserCode::where('kode', $beforeVal)->first();
                            if ($beforeUc && $beforeUc->kode !== $userDept) {
                                abort(422, 'This user department not matching. Expected: ' . $beforeUc->kode);
                            }
                        }
                    }
                }
            }

            // AM_ADMIN validation for last step (exclude SYSADMIN)
            $stepCode = $step['code'] ?? '';
            if ($stepCode === 'asset_mgt') {
                if (!in_array('AM_HEAD', $userRoles) && !$isSysAdmin) {
                    abort(403, 'Only Asset Management Head can approve this step.');
                }
                $userDept = $currentUser->kode_department;

                // if (!$userDept) {
                //     abort(422, 'Your department is not set. Please contact administrator.');
                // }

                // Load asset with assignment relationships if not already loaded
                if (!isset($asset)) {
                    $asset = Assets::with(['assignment.owner', 'assignment.user', 'assignment.maintenance'])
                        ->where('uuid', $tf->asset_uuid)
                        ->firstOrFail();
                }

                if ($tf->type === 'location') {
                    // For location type, AM_ADMIN must match asset owner's department
                    $ownerCode = $asset->assignment?->asset_owner;
                    if ($ownerCode) {
                        $ownerUc = MasterUserCode::where('kode', $ownerCode)->first();
                        if ($ownerUc && $ownerUc->kode !== $userDept) {
                            abort(422, 'This user department not matching. Expected: ' . $ownerUc->kode);
                        }
                    }
                } elseif (in_array($tf->type, ['owner', 'user', 'maintenance'], true)) {
                    // For owner/user/maintenance, AM_ADMIN must match NEW (after) department
                    $afterVal = data_get($tf->after, 'value');
                    if ($afterVal) {
                        $afterUc = MasterUserCode::where('kode', $afterVal)->first();
                        if ($afterUc && $afterUc->kode !== $userDept) {
                            abort(422, 'This user department not matching. Expected: ' . $afterUc->kode);
                        }
                    }
                }
            }

            $step['approved_by'] = $uid;
            $step['approved_at'] = $now;

            $isLastStep = ($nextIdx === count($flow['steps']) - 1);

            if ($isLastStep) {
                $asset = Assets::where('uuid', $tf->asset_uuid)->lockForUpdate()->firstOrFail();
                $val   = data_get($tf->after, 'value');

                switch ($tf->type) {
                    case 'owner':
                        $asset->assignment()->updateOrCreate(
                            ['asset_uuid' => $asset->uuid],
                            ['asset_owner' => $val]
                        );
                        break;

                    case 'user':
                        $asset->assignment()->updateOrCreate(
                            ['asset_uuid' => $asset->uuid],
                            ['asset_user' => $val]
                        );
                        break;

                    case 'maintenance':
                        $asset->assignment()->updateOrCreate(
                            ['asset_uuid' => $asset->uuid],
                            ['asset_maintenance' => $val]
                        );
                        break;

                    case 'status':
                        $asset->kode_status = $val;
                        $asset->save();
                        break;

                    case 'location':
                        $asset->kode_location = $val;
                        $asset->save();
                        break;

                    default:
                        break;
                }

                $tf->kode_status     = 'ACC';
                $tf->pic_approve_uid = $uid;
            }

            if (
                $isLastStep && in_array($tf->type, ['owner', 'user', 'maintenance'], true)
                && $request->hasFile('signed_form')
            ) {
                [$path, $orig, $mime, $size] = $this->saveUpload(
                    $request->file('signed_form'),
                    $asset,
                    $tf->transfer_code,
                    $tf
                );
                $tf->flow_file_path = $path;
                $tf->flow_file_name = $orig;
                $tf->flow_file_mime = $mime;
                $tf->flow_file_size = $size;
            }

            $tf->flow = $flow;
            $tf->save();

            return response()->json([
                'ok'          => true,
                'completed'   => $isLastStep,
                'kode_status' => $tf->kode_status,
                'flow'        => $flow,
            ]);
        });
    }


    public function downloadForm(Request $request, Transfer $transfer)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'R'), 403);

        if (! in_array($transfer->type, ['owner', 'user', 'maintenance'], true)) {
            abort(404, 'Form Transfer only available for Owner/User/Maintenance movement.');
        }

        $transfer->load([
            'asset.assignment.owner.division',
            'asset.assignment.user.division',
            'asset.assignment.maintenance.division',
            'asset.location',
        ]);

        $asset      = $transfer->asset;
        $tfNote     = $transfer->note;
        $assign     = $asset?->assignment;
        $beforeVal  = data_get($transfer->before, 'value');
        $afterVal   = data_get($transfer->after,  'value');
        $createdAt  = $transfer->created_at?->timezone('Asia/Jakarta');

        $beforeUc = null;

        switch ($transfer->type) {
            case 'owner':
                $beforeUc = $assign?->owner;
                break;
            case 'user':
                $beforeUc = $assign?->user;
                break;
            case 'maintenance':
                $beforeUc = $assign?->maintenance;
                break;
        }

        $afterUc = $afterVal
            ? MasterUserCode::with('division')->where('kode', $afterVal)->first()
            : null;

        $fromDeptLabel = '';
        $fromDivLabel  = '';
        $fromLocLabel  = '';

        $toDeptLabel   = '';
        $toDivLabel    = '';
        $toLocLabel    = '';

        if ($beforeUc) {
            $fromDeptLabel = trim($beforeUc->kode . ' - ' . $beforeUc->department);
            if ($beforeUc->division) {
                $fromDivLabel = trim($beforeUc->division->kode . ' - ' . $beforeUc->division->name);
            }
        } elseif ($beforeVal !== null && $beforeVal !== '') {
            $fromDeptLabel = $beforeVal;
        }

        if ($afterUc) {
            $toDeptLabel = trim($afterUc->kode . ' - ' . $afterUc->department);
            if ($afterUc->division) {
                $toDivLabel = trim($afterUc->division->kode . ' - ' . $afterUc->division->name);
            }
        } elseif ($afterVal !== null && $afterVal !== '') {
            $toDeptLabel = $afterVal;
        }

        $loc = $asset?->location;
        $locLabel = $loc ? trim($loc->kode . ' - ' . $loc->name) : '';
        $fromLocLabel = $locLabel;
        $toLocLabel   = $locLabel;

        $flowRaw   = $transfer->flow ?? null;
        $flowArray = null;

        if (is_string($flowRaw) && $flowRaw !== '') {
            $decoded = json_decode($flowRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flowArray = $decoded;
            }
        } elseif (is_array($flowRaw)) {
            $flowArray = $flowRaw;
        }
        $doneLines = [];
        if (!empty($flowArray['steps']) && is_array($flowArray['steps'])) {
            foreach ($flowArray['steps'] as $st) {
                if (!empty($st['approved_at'])) {
                    $label = $st['label'] ?? ($st['code'] ?? '');
                    $role  = $st['role'] ?? '';
                    $by    = $st['approved_by'] ?? '';
                    $atRaw = $st['approved_at'];

                    try {
                        $at = \Carbon\Carbon::parse($atRaw, 'Asia/Jakarta')
                            ->timezone('Asia/Jakarta')
                            ->format('d-m-Y H:i');
                    } catch (\Throwable $e) {
                        $at = $atRaw;
                    }

                    // e.g. "Create Disposal Request (User Departemen) - 21-11-2025 15:53 - Administrator"
                    $line = trim(
                        ($label ?: '') .
                            ($role ? ' (' . $role . ')' : '') .
                            ($at ? ' - ' . $at : '') .
                            ($by ? ' - ' . $by : '')
                    );

                    if ($line !== '') {
                        $doneLines[] = $line;
                    }
                }
            }
        }

        $flowText = implode("\n", $doneLines);

        $templatePath = storage_path('app/public/template/form_transfer_asset.xlsx');
        $spreadsheet  = IOFactory::load($templatePath);

        $sheet = $spreadsheet->getSheetByName('Form Transfer');

        $sheet->setCellValue('V11', $transfer->transfer_code ?? '');

        $sheet->setCellValue('G15', $fromDeptLabel);
        $sheet->setCellValue('G16', $fromDivLabel);
        $sheet->setCellValue('G17', $fromLocLabel);
        $sheet->setCellValue('G18', $createdAt ? $createdAt->format('d-m-Y') : '');

        $sheet->setCellValue('S15', $toDeptLabel);
        $sheet->setCellValue('S16', $toDivLabel);
        $sheet->setCellValue('S17', $toLocLabel);
        $sheet->setCellValue('S18', $createdAt ? $createdAt->format('d-m-Y') : '');

        $sheet->setCellValue('C23', $asset?->asset_code ?? '');
        $sheet->setCellValue('D23', $asset?->description ?? '');

        $qty = $asset?->value?->quantity;
        $sheet->setCellValue('L23', $qty !== null ? $qty : '');

        $uomText = $asset?->value?->uom?->name
            ?? $asset?->value?->kode_uom
            ?? '';
        $sheet->setCellValue('N23', $uomText);

        $sheet->setCellValue('P23', $asset?->notes ?? '');

        $sheet->setCellValue('C30', $tfNote ?? '');

        $sheet->setCellValue('C34', $flowText);
        $sheet->getStyle('C34')->getAlignment()->setWrapText(true);


        $sheet->setCellValue('C36', $fromDeptLabel);

        $sheet->setCellValue('I36', $toDeptLabel);

        $fileName = 'Form Transfer Asset - ' . ($transfer->transfer_code ?? 'MOV') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
