<?php

namespace App\Http\Controllers;

use App\Models\Assets;
use App\Models\Disposal;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\TemplateProcessor;

class StockOpnameController extends Controller
{
    protected function canReadStockOpname()
    {
        $u = auth()->user();
        return $u && method_exists($u, 'hasAction') ? $u->hasAction('STOCK_OPN', 'R') : true;
    }

    protected function canCreateStockOpname()
    {
        $u = auth()->user();
        return $u && method_exists($u, 'hasAction') ? $u->hasAction('STOCK_OPN', 'C') : true;
    }

    public function index()
    {
        abort_unless($this->canReadStockOpname(), 403);
        return view('stock_opname.stock_opname');
    }

    public function datatable(Request $request)
    {
        if (!$this->canReadStockOpname()) {
            $draw = (int) $request->input('draw', 1);
            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim(data_get($request->input('search', []), 'value', ''));

        $source    = $request->input('source');
        $tfType    = $request->input('tf_type');
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');
        $assetLike = $request->input('asset');
        $users = $request->input('users');

        $t = DB::table('assets_transfers as t')
            ->join('assets as a', 'a.uuid', '=', 't.asset_uuid')
            ->selectRaw("
                t.uuid,
                t.asset_uuid,
                a.asset_code,
                a.description,
                t.transfer_code       as code,
                'transfer'            as source_type,
                t.type                as tf_type,
                COALESCE(t.before->>'value','') as before_val,
                COALESCE(t.after->>'value','')  as after_val,
                t.pic_request_uid,
                t.pic_approve_uid,
                t.note,
                t.file_name,
                t.file_path,
                t.flow_file_name,
                t.flow_file_path,
                NULL::text            as ba_file_name,
                NULL::text            as ba_file_path,
                t.updated_at
            ")
            ->whereNull('t.deleted_at')
            ->where('t.kode_status', 'ACC');

        $d = DB::table('assets_disposals as d')
            ->join('assets as a', 'a.uuid', '=', 'd.asset_uuid')
            ->selectRaw("
                d.uuid,
                d.asset_uuid,
                a.asset_code,
                a.description,
                d.disposal_code       as code,
                'disposal'            as source_type,
                NULL::text            as tf_type,
                COALESCE(d.before_status,'') as before_val,
                COALESCE('DIS','')  as after_val,
                d.pic_request_uid,
                d.pic_approve_uid,
                d.note,
                d.file_name,
                d.file_path,
                d.flow_file_name,
                d.flow_file_path,
                d.ba_file_name,
                d.ba_file_path,
                d.updated_at
            ")
            ->whereNull('d.deleted_at')
            ->where('d.kode_status', 'ACC');

        if ($source === 'transfer') {
            $union = $t;
        } elseif ($source === 'disposal') {
            $union = $d;
        } else {
            $union = $t->unionAll($d);
        }

        $q = DB::query()->fromSub($union, 'u');

        if ($tfType) {
            $q->where('source_type', 'transfer')->where('tf_type', $tfType);
        }
        if ($dateFrom) {
            $q->whereDate('updated_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('updated_at', '<=', $dateTo);
        }
        if ($assetLike) {
            $q->where(function ($qq) use ($assetLike) {
                $qq->where('asset_code', 'ilike', "%{$assetLike}%")
                    ->orWhere('description', 'ilike', "%{$assetLike}%");
            });
        }
        if ($users) {
            $q->where('pic_request_uid', $users);
        }
        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('code', 'ilike', "%{$search}%")
                    ->orWhere('asset_code', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('note', 'ilike', "%{$search}%")
                    ->orWhere('pic_request_uid', 'ilike', "%{$search}%")
                    ->orWhere('pic_approve_uid', 'ilike', "%{$search}%");
            });
        }

        $totalFiltered = (clone $q)->count();

        $orderColIdx = (int) data_get($request->input('order', []), '0.column', 9);
        $orderDir    = data_get($request->input('order', []), '0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $columns     = [
            'asset_code',
            'code',
            'source_type',
            'tf_type',
            'before_val',
            'note',
            'pic_request_uid',
            'pic_approve_uid',
            'file_path',
            'updated_at',
        ];
        $orderBy = $columns[$orderColIdx] ?? 'updated_at';

        $rows = $q->orderBy($orderBy, $orderDir)
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($r) {
                $asset_label = ($r->asset_code ?? '') . ' — ' . ($r->description ?? '');
                $isTransfer = $r->source_type === 'transfer';

                // Build detailed label with full names
                $detail = '';
                if ($isTransfer) {
                    $beforeLabel = $r->before_val;
                    $afterLabel = $r->after_val;

                    // Get full usercode details for before/after values
                    if ($r->before_val) {
                        $beforeUc = MasterUserCode::with('division')->where('kode', $r->before_val)->first();
                        if ($beforeUc) {
                            $beforeLabel = $beforeUc->kode . ' - ' . $beforeUc->department;
                        }
                    }

                    if ($r->after_val) {
                        $afterUc = MasterUserCode::with('division')->where('kode', $r->after_val)->first();
                        if ($afterUc) {
                            $afterLabel = '<strong>' . $afterUc->kode . ' - ' . $afterUc->department . '</strong>';
                        }
                    }

                    $detail = strtoupper($r->tf_type) . ' - ' . $beforeLabel . ' → ' . $afterLabel;
                } else {
                    $detail = 'DISPOSAL';
                }

                // Build file links - show all uploaded files
                $files = [];
                if (!empty($r->file_path)) {
                    $url = Storage::url($r->file_path);
                    $name = $r->file_name ?: 'Attachment';
                    $files[] = '<a class="btn btn-sm btn-light-primary me-1" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
                }
                if (!empty($r->flow_file_path)) {
                    $url = Storage::url($r->flow_file_path);
                    $name = $r->flow_file_name ?: 'Signed Form';
                    $files[] = '<a class="btn btn-sm btn-light-success me-1" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
                }
                if (!empty($r->ba_file_path)) {
                    $url = Storage::url($r->ba_file_path);
                    $name = $r->ba_file_name ?: 'Berita Acara';
                    $files[] = '<a class="btn btn-sm btn-light-warning" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
                }
                $file = implode(' ', $files);

                // Build actions buttons
                $actions = '';
                if ($isTransfer) {
                    // Transfer: show form download for owner/user/maintenance types
                    if (in_array($r->tf_type, ['owner', 'user', 'maintenance'], true)) {
                        $formUrl = route('stockopname.transfer.download.form', $r->uuid);
                        $actions .= '<a href="' . e($formUrl) . '" class="btn btn-light-primary btn-sm me-1" target="_blank">Form</a>';
                    }
                } else {
                    // Disposal: show both form and BA download
                    $formUrl = route('stockopname.disposal.download.form', $r->uuid);
                    $baUrl = route('stockopname.disposal.download.ba', $r->uuid);
                    $actions .= '<a href="' . e($formUrl) . '" class="btn btn-light-info btn-sm me-1" target="_blank">Form</a>';
                    $actions .= '<a href="' . e($baUrl) . '" class="btn btn-light-warning btn-sm" target="_blank">BA</a>';
                }

                return [
                    'uuid'             => $r->uuid,
                    'asset_uuid'       => $r->asset_uuid,
                    'asset_code'       => $r->asset_code,
                    'asset_label'      => $asset_label,
                    'code'             => $r->code,
                    'source'           => $isTransfer ? 'MOVEMENT' : 'DISPOSAL',
                    'type'             => $isTransfer ? strtoupper($r->tf_type) : 'DISPOSAL',
                    'detail'           => $detail,
                    'note'             => $r->note,
                    'pic_request_uid'  => $r->pic_request_uid,
                    'pic_approve_uid'  => $r->pic_approve_uid,
                    'file'             => $file,
                    'updated_at'       => $r->updated_at,
                    'actions'          => $actions,
                ];
            });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $totalFiltered,
            'recordsFiltered' => $totalFiltered,
            'data'            => $rows,
        ]);
    }

    public function dataByAsset(string $uuid, Request $request)
    {
        if (!$this->canReadStockOpname()) {
            return response()->json(['data' => []]);
        }
        $qT = DB::table('assets_transfers as t')
            ->selectRaw("
        t.uuid,
        t.asset_uuid,
        t.transfer_code  as code,
        'transfer'       as source_type,
        t.type           as tf_type,
        COALESCE(t.before->>'value','') as before_label,
        COALESCE(t.after->>'value','')  as after_label,
        t.pic_request_uid,
        t.pic_approve_uid,
        t.note,
        t.file_name,
        t.file_path,
        t.updated_at
        ")
            ->whereNull('t.deleted_at')
            ->where('t.kode_status', 'ACC')
            ->where('t.asset_uuid', $uuid);

        $qD = DB::table('assets_disposals as d')
            ->selectRaw("
        d.uuid,
        d.asset_uuid,
        d.disposal_code  as code,
        'disposal'       as source_type,
        NULL::text       as tf_type,
        COALESCE(d.before_status,'') as before_label,
        COALESCE('DIS','')  as after_label,
        d.pic_request_uid,
        d.pic_approve_uid,
        d.note,
        d.file_name,
        d.file_path,
        d.updated_at
    ")
            ->whereNull('d.deleted_at')
            ->where('d.kode_status', 'ACC')
            ->where('d.asset_uuid', $uuid);

        $union = $qT->unionAll($qD);

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($r) {
                $detail = $r->source_type === 'transfer'
                    ? (strtoupper($r->tf_type) . ' - ' . $r->before_label . ' → ' . $r->after_label)
                    : ('DISPOSAL' . ($r->before_label ? ' - ' . $r->before_label . ' → ' . $r->after_label : ''));

                $file = $r->file_path
                    ? '<a href="' . e(Storage::url($r->file_path)) . '" target="_blank">' . e($r->file_name ?: 'File') . '</a>'
                    : '';

                return [
                    'code'            => $r->code,
                    'source'          => $r->source_type === 'transfer' ? 'MOVEMENT' : 'DISPOSAL',
                    'type'            => $r->source_type === 'transfer' ? strtoupper($r->tf_type) : 'DISPOSAL',
                    'detail'          => $detail,
                    'note'            => $r->note,
                    'pic_request_uid' => $r->pic_request_uid,
                    'pic_approve_uid' => $r->pic_approve_uid,
                    'file'            => $file,
                    'updated_at'      => $r->updated_at,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function store_transfer(Request $request)
    {
        abort_unless($this->canCreateStockOpname(), 403);
        $data = $request->validate(['asset_uuid' => ['required', 'uuid', 'exists:assets,uuid'], 'type' => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])], 'after.value' => ['required', 'string'], 'note' => ['nullable', 'string', 'max:1000'], 'file' => ['nullable', 'file', 'max:51200'], 'flow_file' => ['nullable', 'file', 'max:51200']]);
        $user = auth()->user();
        $uid = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');
        $asset = Assets::findOrFail($data['asset_uuid']);
        $assetSv = Assets::where('uuid', $asset->uuid)->lockForUpdate()->first();
        $before = null;
        switch ($data['type']) {
            case 'owner':
                $before = optional($assetSv->assignment)->asset_owner;
                $assetSv->assignment()->updateOrCreate(['asset_uuid' => $assetSv->uuid], ['asset_owner' => data_get($data, 'after.value')]);
                break;
            case 'user':
                $before = optional($assetSv->assignment)->asset_user;
                $assetSv->assignment()->updateOrCreate(['asset_uuid' => $assetSv->uuid], ['asset_user' => data_get($data, 'after.value')]);
                break;
            case 'maintenance':
                $before = optional($assetSv->assignment)->asset_maintenance;
                $assetSv->assignment()->updateOrCreate(['asset_uuid' => $assetSv->uuid], ['asset_maintenance' => data_get($data, 'after.value')]);
                break;
            case 'status':
                $before = $assetSv->kode_status;
                $assetSv->kode_status = data_get($data, 'after.value');
                $assetSv->save();
                break;
            case 'location':
                $before = $assetSv->kode_location;
                $assetSv->kode_location = data_get($data, 'after.value');
                $assetSv->save();
                break;
        }

        $beforeLabel = $this->resolveLabel($data['type'], $before);
        $afterLabel = $this->resolveLabel($data['type'], data_get($data, 'after.value'));

        $transfer = DB::transaction(function () use ($asset, $data, $before, $beforeLabel, $afterLabel, $uid, $request) {
            $now = Carbon::now();
            $prefix = 'OPN' . $now->format('ym');
            $last = Transfer::where('transfer_code', 'like', $prefix . '%')->orderBy('transfer_code', 'desc')->first();
            $seq = $last ? ((int)substr($last->transfer_code, -4)) + 1 : 1;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
            [$path, $orig, $mime, $size] = $this->saveUploadTf($request->file('file'), $asset, $code, null);
            [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveUploadTf($request->file('flow_file'), $asset, $code, null);
            return Transfer::create([
                'uuid' => (string)Str::uuid(),
                'asset_uuid' => $asset->uuid,
                'transfer_code' => $code,
                'type' => $data['type'],
                'before' => ['value' => $before],
                'after' => ['value' => data_get($data, 'after.value')],
                'before_label' => $beforeLabel,
                'after_label' => $afterLabel,
                'kode_status' => 'ACC',
                'note' => $data['note'] ?? null,
                'pic_request_uid' => $uid,
                'pic_approve_uid' => $uid,
                'file_path' => $path,
                'file_name' => $orig,
                'file_mime' => $mime,
                'file_size' => $size,
                'flow_file_path' => $flowPath,
                'flow_file_name' => $flowOrig,
                'flow_file_mime' => $flowMime,
                'flow_file_size' => $flowSize
            ]);
        });
        return response()->json(['ok' => true, 'id' => $transfer->uuid, 'code' => $transfer->transfer_code]);
    }

    public function store_disposal(Request $request)
    {
        abort_unless($this->canCreateStockOpname(), 403);
        $data = $request->validate([
            'asset_uuid' => ['required', 'uuid', 'exists:assets,uuid'],
            'note' => ['nullable', 'string', 'max:1000'],
            'target_status' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:51200'],
            'flow_file' => ['nullable', 'file', 'max:51200'],
            'ba_file' => ['nullable', 'file', 'max:51200']
        ]);
        $user = auth()->user();
        $uid = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');
        $asset = Assets::findOrFail($data['asset_uuid']);
        $target = $data['target_status'] ?? 'DIS';
        $ok = MasterStatus::where('kode', $target)->where(function ($q) {
            $q->where('type', 'Asset')->orWhereNull('type');
        })->exists();
        abort_unless($ok, 422, 'Target disposal status not valid');
        $now = Carbon::now();
        $prefix = 'OPN' . $now->format('ym');
        $last = Disposal::where('disposal_code', 'like', $prefix . '%')->orderBy('disposal_code', 'desc')->first();
        $seq = $last ? ((int)substr($last->disposal_code, -4)) + 1 : 1;
        $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        [$path, $orig, $mime, $size] = $this->saveUploadDis($request->file('file'), $asset, $code, null);
        [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveUploadDis($request->file('flow_file'), $asset, $code, null);
        [$baPath, $baOrig, $baMime, $baSize] = $this->saveUploadDis($request->file('ba_file'), $asset, $code, null);
        $row = Disposal::create([
            'uuid' => (string)Str::uuid(),
            'asset_uuid' => $asset->uuid,
            'disposal_code' => $code,
            'target_status' => $target,
            'kode_status' => 'ACC',
            'note' => $data['note'] ?? null,
            'file_path' => $path,
            'file_name' => $orig,
            'file_mime' => $mime,
            'file_size' => $size,
            'flow_file_path' => $flowPath,
            'flow_file_name' => $flowOrig,
            'flow_file_mime' => $flowMime,
            'flow_file_size' => $flowSize,
            'ba_file_path' => $baPath,
            'ba_file_name' => $baOrig,
            'ba_file_mime' => $baMime,
            'ba_file_size' => $baSize,
            'pic_request_uid' => $uid,
            'pic_approve_uid' => $uid,
            'before_status' => $asset->kode_status
        ]);
        $asset->kode_status = $target;
        $asset->save();
        return response()->json(['ok' => true, 'id' => $row->uuid, 'code' => $row->disposal_code]);
    }

    public function downloadTransferForm($uuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $transfer = Transfer::with([
            'asset.assignment.owner.division',
            'asset.assignment.user.division',
            'asset.assignment.maintenance.division',
            'asset.location',
            'asset.value.uom'
        ])->findOrFail($uuid);

        $asset = $transfer->asset;
        $assign = $asset?->assignment;
        $beforeVal = data_get($transfer->before, 'value');
        $afterVal = data_get($transfer->after, 'value');
        $createdAt = $transfer->created_at?->timezone('Asia/Jakarta');

        $template = storage_path('app/public/template/form_transfer_asset.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Transfer');

        // Determine FROM usercode based on transfer type
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

        // Get AFTER usercode
        $afterUc = $afterVal
            ? MasterUserCode::with('division')->where('kode', $afterVal)->first()
            : null;

        // Build FROM labels
        $fromDeptLabel = '';
        $fromDivLabel = '';
        if ($beforeUc) {
            $fromDeptLabel = trim($beforeUc->kode . ' - ' . $beforeUc->department);
            if ($beforeUc->division) {
                $fromDivLabel = trim($beforeUc->division->kode . ' - ' . $beforeUc->division->name);
            }
        } elseif ($beforeVal !== null && $beforeVal !== '') {
            $fromDeptLabel = $beforeVal;
        }

        // Build TO labels
        $toDeptLabel = '';
        $toDivLabel = '';
        if ($afterUc) {
            $toDeptLabel = trim($afterUc->kode . ' - ' . $afterUc->department);
            if ($afterUc->division) {
                $toDivLabel = trim($afterUc->division->kode . ' - ' . $afterUc->division->name);
            }
        } elseif ($afterVal !== null && $afterVal !== '') {
            $toDeptLabel = $afterVal;
        }

        // Location labels
        $loc = $asset?->location;
        $locLabel = $loc ? trim($loc->kode . ' - ' . $loc->name) : '';
        $fromLocLabel = $locLabel;
        $toLocLabel = $locLabel;

        // V11: Transfer Code
        $sheet->setCellValue('V11', $transfer->transfer_code ?? '');

        // FROM section (G15-G18)
        $sheet->setCellValue('G15', $fromDeptLabel);
        $sheet->setCellValue('G16', $fromDivLabel);
        $sheet->setCellValue('G17', $fromLocLabel);
        $sheet->setCellValue('G18', $createdAt ? $createdAt->format('d-m-Y') : '');

        // TO section (S15-S18)
        $sheet->setCellValue('S15', $toDeptLabel);
        $sheet->setCellValue('S16', $toDivLabel);
        $sheet->setCellValue('S17', $toLocLabel);
        $sheet->setCellValue('S18', $createdAt ? $createdAt->format('d-m-Y') : '');

        // Asset details (row 23)
        $sheet->setCellValue('C23', $asset?->asset_code ?? '');
        $sheet->setCellValue('D23', $asset?->description ?? '');

        $qty = $asset?->value?->quantity;
        $sheet->setCellValue('L23', $qty !== null ? $qty : '');

        $uomText = $asset?->value?->uom?->name
            ?? $asset?->value?->kode_uom
            ?? '';
        $sheet->setCellValue('N23', $uomText);

        $sheet->setCellValue('P23', $asset?->notes ?? '');

        // Signature sections
        $sheet->setCellValue('C37', $fromDeptLabel);
        $sheet->setCellValue('C38', '');
        $sheet->setCellValue('I37', $toDeptLabel);
        $sheet->setCellValue('I38', '');

        $fileName = 'Form_Transfer_' . ($transfer->transfer_code ?? $uuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function downloadDisposalForm($uuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $disposal = Disposal::with([
            'asset.location',
            'asset.value',
            'asset.assignment.owner.division',
        ])->findOrFail($uuid);

        $asset = $disposal->asset;
        $assign = $asset?->assignment;
        $owner = $assign?->owner;
        $ownerDiv = $owner?->division;

        $createdAt = $disposal->created_at?->timezone('Asia/Jakarta');
        $companyName = config('app.company_name', 'PT LRT Jakarta');

        $deptLabel = $owner?->department ?: '';
        $divLabel = $ownerDiv?->name ?? '';

        $assetCode = $asset?->asset_code ?? '';
        $assetName = $asset?->asset_name ?? $asset?->description ?? '';

        $acqDate = null;
        if ($asset?->value?->actual_date) {
            $acqDate = \Carbon\Carbon::parse($asset->value->actual_date)->timezone('Asia/Jakarta');
        }

        $comCost = $asset?->value?->total ?? 0;

        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $asset?->uuid)
            ->first();

        $comAcc = $ledger?->acc_sum ?? 0;
        $comNbv = $ledger?->nbv_sum ?? 0;
        $taxNbv = $asset?->value?->tax_nbv ?? 0;

        $justification = $disposal->note ?? '';

        $template = storage_path('app/public/template/form_disposal_asset_tetap.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Disposal Aset Tetap') ?? $spreadsheet->getActiveSheet();

        $sheet->setCellValue('H7', $companyName);
        $sheet->setCellValue('H8', $disposal->disposal_code ?? '');
        $sheet->setCellValue('H9', $createdAt ? $createdAt->format('d-m-Y') : '');
        $sheet->setCellValue('H10', $deptLabel);
        $sheet->setCellValue('H11', $divLabel);

        $sheet->setCellValue('H14', '');

        $sheet->setCellValue('H15', $assetCode);
        $sheet->setCellValue('H16', $assetName);
        $sheet->setCellValue('H17', $acqDate ? $acqDate->format('d-m-Y') : '');

        $sheet->setCellValue('H18', $comCost);
        $sheet->setCellValue('H19', $comAcc);
        $sheet->setCellValue('H20', $comNbv);
        $sheet->setCellValue('H21', $taxNbv);

        $sheet->setCellValue('H22', '');
        $sheet->setCellValue('H23', '');

        $sheet->setCellValue('H24', $justification);

        $fileName = 'Form_Disposal_' . ($disposal->disposal_code ?? $uuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function downloadDisposalBa($uuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $disposal = Disposal::with([
            'asset.location',
            'asset.assignment.owner',
        ])->findOrFail($uuid);

        $asset = $disposal->asset;
        $location = $asset?->location;
        $owner = $asset?->assignment?->owner;

        $assetName = $asset?->asset_name ?? $asset?->description ?? '';
        $assetCode = $asset?->asset_code ?? '';
        $formNumber = $disposal->disposal_code ?? '';
        $locationLbl = $location
            ? trim(($location->kode ?? '') . ' - ' . ($location->name ?? ''))
            : '';
        $ownerDept = $owner?->department ?? '';
        $keterangan = $disposal->note ?? '';

        $date = $disposal->updated_at
            ? $disposal->updated_at->copy()->timezone('Asia/Jakarta')
            : now()->timezone('Asia/Jakarta');

        \Carbon\Carbon::setLocale('id');

        $hari = $date->translatedFormat('l');
        $tglBT = $date->translatedFormat('d F Y');

        $template = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $tpl = new TemplateProcessor($template);

        $tpl->setValues([
            'HARI' => $hari,
            'TANGGAL_BULAN_TAHUN' => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER' => $tglBT,
            'NAMA_ASET' => $assetName,
            'NOMOR_ASET' => $assetCode,
            'NOMOR_FORM' => $formNumber,
            'LOKASI' => $locationLbl,
            'DEPARTEMEN_OWNER' => $ownerDept,
            'KETERANGAN' => $keterangan,
        ]);

        $fileName = 'BA_Disposal_' . ($disposal->disposal_code ?? $uuid) . '.docx';

        return response()->streamDownload(function () use ($tpl) {
            $tpl->saveAs('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    // Preview endpoints: generate templates based on selected asset (no record required)
    public function previewTransferForm(Request $request, $assetUuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $asset = Assets::with(['assignment.owner.division', 'assignment.user.division', 'assignment.maintenance.division', 'location', 'value.uom'])->findOrFail($assetUuid);

        $template = storage_path('app/public/template/form_transfer_asset.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Transfer');

        $assign = $asset->assignment;
        $owner = $assign?->owner;
        $ownerDiv = $owner?->division;

        $fromDeptLabel = $owner ? trim($owner->kode . ' - ' . $owner->department) : '';
        $fromDivLabel = $ownerDiv ? $ownerDiv->name : '';

        $loc = $asset->location;
        $fromLocLabel = $loc ? trim($loc->kode . ' - ' . $loc->name) : '';

        $now = now()->timezone('Asia/Jakarta');

        // V11: Transfer Code
        $sheet->setCellValue('V11', 'PREVIEW');

        // FROM section (G15-G18)
        $sheet->setCellValue('G15', $fromDeptLabel);
        $sheet->setCellValue('G16', $fromDivLabel);
        $sheet->setCellValue('G17', $fromLocLabel);
        $sheet->setCellValue('G18', $now->format('d-m-Y'));

        // TO section (S15-S18) - based on target selector
        $targetType = $request->input('target_type');
        $targetValue = $request->input('target_value');

        $toDeptLabel = '';
        $toDivLabel = '';
        $toLocLabel = '';
        $toSignatureLabel = '';

        if ($targetType && $targetValue) {
            if ($targetType === 'owner') {
                $targetOwner = MasterUserCode::with('division')->where('kode', $targetValue)->first();
                if ($targetOwner) {
                    $toDeptLabel = trim($targetOwner->kode . ' - ' . $targetOwner->department);
                    $toDivLabel = $targetOwner->division?->name ?? '';
                    $toSignatureLabel = $toDeptLabel;
                }
                $toLocLabel = $fromLocLabel; // same location
            } elseif ($targetType === 'user') {
                $targetUser = MasterUserCode::with('division')->where('kode', $targetValue)->first();
                if ($targetUser) {
                    $toDeptLabel = trim($targetUser->kode . ' - ' . $targetUser->department);
                    $toDivLabel = $targetUser->division?->name ?? '';
                    $toSignatureLabel = $toDeptLabel;
                }
                $toLocLabel = $fromLocLabel; // same location
            } elseif ($targetType === 'maintenance') {
                $targetMaint = MasterUserCode::with('division')->where('kode', $targetValue)->first();
                if ($targetMaint) {
                    $toDeptLabel = trim($targetMaint->kode . ' - ' . $targetMaint->department);
                    $toDivLabel = $targetMaint->division?->name ?? '';
                    $toSignatureLabel = $toDeptLabel;
                }
                $toLocLabel = $fromLocLabel; // same location
            } elseif ($targetType === 'location') {
                $targetLoc = MasterLocation::where('kode', $targetValue)->first();
                if ($targetLoc) {
                    $toLocLabel = trim($targetLoc->kode . ' - ' . $targetLoc->name);
                }
                $toDeptLabel = $fromDeptLabel; // same department
                $toDivLabel = $fromDivLabel; // same division
                $toSignatureLabel = $toDeptLabel;
            }
        }

        $sheet->setCellValue('S15', $toDeptLabel);
        $sheet->setCellValue('S16', $toDivLabel);
        $sheet->setCellValue('S17', $toLocLabel);
        $sheet->setCellValue('S18', $now->format('d-m-Y'));

        // Asset details (row 23)
        $sheet->setCellValue('C23', $asset->asset_code ?? '');
        $sheet->setCellValue('D23', $asset->description ?? '');

        $qty = $asset->value?->quantity;
        $sheet->setCellValue('L23', $qty !== null ? $qty : '');

        $uomText = $asset->value?->uom?->name ?? $asset->value?->kode_uom ?? '';
        $sheet->setCellValue('N23', $uomText);

        $sheet->setCellValue('P23', $asset->notes ?? '');

        // Signature sections
        $sheet->setCellValue('C37', $fromDeptLabel); // FROM signature
        $sheet->setCellValue('C38', '');
        $sheet->setCellValue('I37', $toSignatureLabel); // TO signature
        $sheet->setCellValue('I38', '');

        $fileName = 'Form_Transfer_Preview_' . ($asset->asset_code ?? $assetUuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function previewDisposalForm($assetUuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $asset = Assets::with([
            'assignment.owner.division',
            'location',
            'value',
        ])->findOrFail($assetUuid);

        $assign = $asset?->assignment;
        $owner = $assign?->owner;
        $ownerDiv = $owner?->division;

        $now = now()->timezone('Asia/Jakarta');
        $companyName = config('app.company_name', 'PT LRT Jakarta');

        $deptLabel = $owner?->department ?: '';
        $divLabel = $ownerDiv?->name ?? '';

        $assetCode = $asset?->asset_code ?? '';
        $assetName = $asset?->asset_name ?? $asset?->description ?? '';

        $acqDate = null;
        if ($asset?->value?->actual_date) {
            $acqDate = \Carbon\Carbon::parse($asset->value->actual_date)->timezone('Asia/Jakarta');
        }

        $comCost = $asset?->value?->total ?? 0;

        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $asset?->uuid)
            ->first();

        $comAcc = $ledger?->acc_sum ?? 0;
        $comNbv = $ledger?->nbv_sum ?? 0;
        $taxNbv = $asset?->value?->tax_nbv ?? 0;

        $template = storage_path('app/public/template/form_disposal_asset_tetap.xlsx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Disposal Aset Tetap') ?? $spreadsheet->getActiveSheet();

        $sheet->setCellValue('H7', $companyName);
        $sheet->setCellValue('H8', 'PREVIEW');
        $sheet->setCellValue('H9', $now->format('d-m-Y'));
        $sheet->setCellValue('H10', $deptLabel);
        $sheet->setCellValue('H11', $divLabel);

        $sheet->setCellValue('H14', '');

        $sheet->setCellValue('H15', $assetCode);
        $sheet->setCellValue('H16', $assetName);
        $sheet->setCellValue('H17', $acqDate ? $acqDate->format('d-m-Y') : '');

        $sheet->setCellValue('H18', $comCost);
        $sheet->setCellValue('H19', $comAcc);
        $sheet->setCellValue('H20', $comNbv);
        $sheet->setCellValue('H21', $taxNbv);

        $sheet->setCellValue('H22', '');
        $sheet->setCellValue('H23', '');

        $sheet->setCellValue('H24', '');

        $fileName = 'Form_Disposal_Preview_' . ($asset->asset_code ?? $assetUuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function previewDisposalBa($assetUuid)
    {
        abort_unless($this->canReadStockOpname(), 403);

        $asset = Assets::with([
            'location',
            'assignment.owner',
        ])->findOrFail($assetUuid);

        $location = $asset?->location;
        $owner = $asset?->assignment?->owner;

        $assetName = $asset?->asset_name ?? $asset?->description ?? '';
        $assetCode = $asset?->asset_code ?? '';
        $locationLbl = $location
            ? trim(($location->kode ?? '') . ' - ' . ($location->name ?? ''))
            : '';
        $ownerDept = $owner?->department ?? '';

        $date = now()->timezone('Asia/Jakarta');

        \Carbon\Carbon::setLocale('id');

        $hari = $date->translatedFormat('l');
        $tglBT = $date->translatedFormat('d F Y');

        $template = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        if (!file_exists($template)) return abort(404, 'Template not found');

        $tpl = new TemplateProcessor($template);

        $tpl->setValues([
            'HARI' => $hari,
            'TANGGAL_BULAN_TAHUN' => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER' => $tglBT,
            'NAMA_ASET' => $assetName,
            'NOMOR_ASET' => $assetCode,
            'NOMOR_FORM' => 'PREVIEW',
            'LOKASI' => $locationLbl,
            'DEPARTEMEN_OWNER' => $ownerDept,
            'KETERANGAN' => '',
        ]);

        $fileName = 'BA_Disposal_Preview_' . ($asset->asset_code ?? $assetUuid) . '.docx';

        return response()->streamDownload(function () use ($tpl) {
            $tpl->saveAs('php://output');
        }, $fileName, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    private function saveUploadTf($file, Assets $asset, $code, $existing = null)
    {
        if (!$file) return [null, null, null, null];
        $disk = 'public';
        $dir = 'transfers/' . $asset->uuid;
        $ext = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $file->storeAs($dir, $name, $disk);
        if ($existing && !empty($existing->file_path)) {
            Storage::disk($disk)->delete($existing->file_path);
        }
        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }

    private function saveUploadDis($file, Assets $asset, $code, $existing = null)
    {
        if (!$file) return [null, null, null, null];
        $disk = 'public';
        $dir = 'disposals/' . $asset->uuid;
        $ext = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $path = $file->storeAs($dir, $name, $disk);
        if ($existing && !empty($existing->file_path)) {
            Storage::disk($disk)->delete($existing->file_path);
        }
        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }

    private function resolveLabel($type, $code)
    {
        $type = is_string($type) ? trim($type) : '';
        $code = is_string($code) ? trim($code) : '';
        if ($code === '') return '(empty)';
        if ($type === '') return $code;

        switch ($type) {
            case 'owner':
            case 'user':
            case 'maintenance':
                $uc = MasterUserCode::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $uc ? trim($uc->kode . ' - ' . $uc->name) : $code;
            case 'status':
                $s = MasterStatus::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $s ? trim($s->kode . ' - ' . $s->name) : $code;
            case 'location':
                $l = MasterLocation::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $l ? trim($l->kode . ' - ' . $l->name) : $code;
            default:
                return $code;
        }
    }
}
