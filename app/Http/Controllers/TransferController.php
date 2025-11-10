<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferStoreRequest;
use App\Http\Requests\TransferDecisionRequest;
use App\Models\Assets;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use App\Services\TransferCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TransferController extends Controller
{
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
        $q = Transfer::query()
            ->from('assets_transfers')
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_transfers.asset_uuid')
            ->with('status')
            ->select([
                'assets_transfers.*',
                'a.asset_code',
                'a.description as asset_desc',
            ])
            ->orderByDesc('assets_transfers.updated_at');

        if ($request->filled('workflow')) {
            $q->where('assets_transfers.kode_status', $request->string('workflow'));
        }

        return DataTables::of($q)
            ->addColumn('asset_label', function ($r) {
                $code = $r->asset_code ?? $r->asset_uuid;
                $desc = $r->asset_desc ? (' - ' . $r->asset_desc) : '';
                return $code . $desc;
            })
            ->addColumn('workflow_label', function ($r) {
                return $r->status ? ($r->kode_status . ' - ' . $r->status->name) : ($r->kode_status ?? '');
            })
            ->addColumn('before_show', fn($r) => $this->resolveLabel($r->type, data_get($r->before, 'value')))
            ->addColumn('after_show',  fn($r) => $this->resolveLabel($r->type, data_get($r->after,  'value')))

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
                if (!$r->file_path) return '';

                $url  = url('storage/' . ltrim($r->file_path, '/'));
                $name = $r->file_name ?: 'Attachment';

                return '<a class="btn btn-sm btn-light-primary" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            })
            ->addColumn('actions', function ($r) {
                $pending = $r->kode_status === 'APR';
                $id = e($r->uuid);
                $btns = '<div class="btn-group btn-group-sm">';
                if ($pending) {
                    $btns .= '<button class="btn btn-light-primary btn-tf-edit" data-id="' . $id . '">Edit</button>';
                    $btns .= '<button class="btn btn-light-success btn-tf-approve" data-id="' . $id . '">Accept</button>';
                    $btns .= '<button class="btn btn-light-warning btn-tf-reject" data-id="' . $id . '">Reject</button>';
                }
                $btns .= '<button class="btn btn-light-danger btn-tf-delete" data-id="' . $id . '">Delete</button>';
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['file', 'actions'])
            ->toJson();
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_uuid'   => ['required', 'uuid', 'exists:assets,uuid'],
            'type'         => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'  => ['required', 'string'],
            'note'         => ['nullable', 'string', 'max:1000'],
            'file'         => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
        ]);

        
        $currentUid = auth()->user()?->username;
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

        $afterCode   = (string) $data['after']['value'];
        $beforeLabel = $this->resolveLabel($data['type'], $beforeCode);
        $afterLabel  = $this->resolveLabel($data['type'], $afterCode);

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


            return Transfer::create([
                'uuid'             => (string) Str::uuid(),
                'asset_uuid'       => $asset->uuid,
                'transfer_code'    => $code,
                'type'             => $data['type'],

                'before'           => ['value' => $beforeCode],
                'after'            => ['value' => $afterCode],

                'before_label'     => $beforeLabel,
                'after_label'      => $afterLabel,

                'kode_status'      => $initialWorkflow,
                'note'             => $data['note'] ?? null,
                'pic_request_uid'  => $currentUid,
                'pic_approve_uid'  => null,
                'file_path'       => $path,
                'file_name'       => $orig,
                'file_mime'       => $mime,
                'file_size'       => $size,
            ]);
        });

        return response()->json([
            'ok'   => true,
            'id'   => $transfer->uuid,
            'code' => $transfer->transfer_code,
        ]);
    }

    public function show(string $uuid)
    {
        $transfer = Transfer::where('uuid', $uuid)->firstOrFail();

        $asset = Assets::select('uuid', 'asset_code', 'description')->find($transfer->asset_uuid);

        return response()->json([
            'uuid'        => $transfer->uuid,
            'asset_uuid'  => $transfer->asset_uuid,
            'asset_code'  => $asset->asset_code ?? null,
            'asset_desc'  => $asset->description ?? null,
            'asset_label' => $asset ? $asset->asset_code . ($asset->description ? (' - ' . $asset->description) : '') : null,
            'type'        => $transfer->type,
            'before'      => $transfer->before,
            'after'       => $transfer->after,
            'note'        => $transfer->note,
            'kode_status' => $transfer->kode_status,
            'file_url'    => $transfer->file_url,
            'file_name'    => $transfer->file_name,
        ]);
    }
    public function update(Request $request, string $uuid)
    {
        $data = $request->validate([
            'asset_uuid'  => ['required', 'uuid', 'exists:assets,uuid'],
            'type'        => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value' => ['required', 'string'],
            'note'        => ['nullable', 'string', 'max:1000'],
            'file'         => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
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
            'type'        => $data['type'],
            'before'      => $before,
            'after'       => $after,
            'note'        => $data['note'] ?? null,
            'file_path'       => $file_path ?? $tf->file_path,
            'file_name'       => $file_name ?? $tf->file_name,
            'file_mime'       => $file_mime ?? $tf->file_mime,
            'file_size'       => $file_size ?? $tf->file_size,
        ])->save();

        return response()->json(['ok' => true]);
    }

    /** Soft delete */
    public function destroy(string $uuid)
    {
        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        $tf->delete();

        return response()->json(['ok' => true]);
    }

    public function approve(Request $request, string $uuid)
    {
        $uid = auth()->user()?->username;
        abort_if(!$uid, 401, 'No session UID.');
        DB::transaction(function () use ($uuid, $uid) {
            $tf = Transfer::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be approved.');

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

            $tf->kode_status      = 'ACC';
            $tf->pic_approve_uid  = $uid;
            $tf->save();
        });

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, string $uuid)
    {
        $uid = auth()->user()?->username;
        abort_if(!$uid, 401, 'No session UID.');

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be rejected.');

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
            ->addColumn('file', function (Transfer $t) {
                if (!$t->file_path) return '';

                $url  = url('storage/' . ltrim($t->file_path, '/'));
                $name = $t->file_name ?: 'Attachment';

                return '<a class="btn btn-sm btn-light-primary" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            })
            ->rawColumns(['file'])
            ->toJson();
    }

    /** Build BEFORE/AFTER payloads depending on transfer type */
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
        $k = $code;
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

    /** Helper used by update(): fetch current "before" snapshot */
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
        $options = ['disk' => $disk];
        $options['visibility'] = 'public';

        if ($existing && $existing->file_path) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }
}
