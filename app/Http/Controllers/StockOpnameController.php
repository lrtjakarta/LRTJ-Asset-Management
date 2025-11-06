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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StockOpnameController extends Controller
{
    public function index()
    {
        return view('stock_opname.stock_opname');
    }
    public function datatable(Request $request)
    {
        $draw   = (int) $request->input('draw', 1);
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = trim(data_get($request->input('search', []), 'value', ''));

        $source    = $request->input('source');
        $tfType    = $request->input('tf_type');
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');
        $assetLike = $request->input('asset');

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
                $asset = $r->asset_code . ' — ' . $r->description;
                $detail = $r->source_type === 'transfer'
                    ? (strtoupper($r->tf_type) . ' • ' . $r->before_val . ' → ' . $r->after_val)
                    : 'DISPOSAL';
                $file = $r->file_path
                    ? '<a href="' . e(Storage::url($r->file_path)) . '" target="_blank">' . e($r->file_name ?: 'File') . '</a>'
                    : '';

                return [
                    'asset'           => $asset,
                    'asset_code'      => $r->asset_code,
                    'asset_uuid'      => $r->asset_uuid,
                    'code'            => $r->code,
                    'source'          => strtoupper($r->source_type),
                    'type'            => $r->source_type === 'transfer' ? strtoupper($r->tf_type) : 'DISPOSAL',
                    'detail'          => $detail,
                    'note'            => $r->note,
                    'pic_request_uid' => $r->pic_request_uid,
                    'pic_approve_uid' => $r->pic_approve_uid,
                    'file'            => $file,
                    'updated_at'      => $r->updated_at,
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
                    ? (strtoupper($r->tf_type) . ' • ' . $r->before_label . ' → ' . $r->after_label)
                    : ('DISPOSAL' . ($r->before_label ? ' • ' . $r->before_label . ' → ' . $r->after_label : ''));

                $file = $r->file_path
                    ? '<a href="' . e(Storage::url($r->file_path)) . '" target="_blank">' . e($r->file_name ?: 'File') . '</a>'
                    : '';

                return [
                    'code'            => $r->code,
                    'source'          => $r->source_type === 'transfer' ? 'TRANSFER' : 'DISPOSAL',
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
        $data = $request->validate([
            'asset_uuid'   => ['required', 'uuid', 'exists:assets,uuid'],
            'type'         => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'  => ['required', 'string'],
            'note'         => ['nullable', 'string', 'max:1000'],
            'file'         => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
        ]);

        $currentUid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        if ($currentUid === '') {
            abort(401, 'No session UID.');
        }

        $asset    = Assets::with(['assignment', 'status', 'location'])->findOrFail($data['asset_uuid']);
        $totalRow = Transfer::where('asset_uuid', $data['asset_uuid'])->count();

        $afterCode   = (string) $data['after']['value'];

        $assetSv = Assets::where('uuid', $data['asset_uuid'])->lockForUpdate()->firstOrFail();
        $beforeCode = null;
        switch ($data['type']) {
            case 'owner':
                $beforeCode = $assetSv->assignment?->asset_owner;
                $assetSv->assignment()->updateOrCreate(['asset_uuid' => $data['asset_uuid']], ['asset_owner' => $afterCode]);
                break;
            case 'user':
                $beforeCode = $assetSv->assignment?->asset_user;
                $assetSv->assignment()->updateOrCreate(['asset_uuid' => $data['asset_uuid']], ['asset_user' => $afterCode]);
                break;
            case 'maintenance':
                $beforeCode = $assetSv->assignment?->asset_maintenance;
                $assetSv->assignment()->updateOrCreate(['asset_uuid' => $data['asset_uuid']], ['asset_maintenance' => $afterCode]);
                break;
            case 'status':
                $beforeCode = $asset->kode_status;
                $assetSv->kode_status = $afterCode;
                $assetSv->save();
                break;
            case 'location':
                $beforeCode = $asset->kode_location;
                $assetSv->kode_location = $afterCode;
                $assetSv->save();
                break;
        }

        $beforeLabel = $this->resolveLabel($data['type'], $beforeCode);
        $afterLabel  = $this->resolveLabel($data['type'], $afterCode);

        $initialWorkflow = 'ACC';

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
            $prefix = 'OPN' . $now->format('ym');
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

            [$path, $orig, $mime, $size] = $this->saveUploadTf($request->file('file'), $asset, $code, null);


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
                'pic_approve_uid'  => $currentUid,
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
    private function saveUploadTf(?UploadedFile $file, Assets $asset, string $code, ?Transfer $existing = null): array
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
    public function store_disposal(Request $request)
    {
        $data = $request->validate([
            'asset_uuid'    => ['required', 'uuid', 'exists:assets,uuid'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'target_status' => ['nullable', 'string'],
            'file'          => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
        ]);

        $uid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        abort_if($uid === '', 401, 'No session UID.');

        $asset = Assets::select('uuid', 'asset_code', 'kode_status')->findOrFail($data['asset_uuid']);

        $target = $data['target_status'] ?? 'DIS';

        $ok = MasterStatus::where('kode', $target)->where(function ($q) {
            $q->where('type', 'Asset')->orWhereNull('type');
        })->exists();
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
        $path = null;

        [$path, $orig, $mime, $size] = $this->saveUploadDis($request->file('file'), $asset, $code, null);


        $row = Disposal::create([
            'uuid'            => $uuid,
            'asset_uuid'      => $asset->uuid,
            'disposal_code'   => $code,
            'target_status'   => $target,
            'kode_status'     => 'ACC',
            'note'            => $data['note'] ?? null,
            'file_path'       => $path,
            'pic_request_uid' => $uid,
            'pic_approve_uid' => $uid,
            'file_name'       => $orig,
            'file_mime'       => $mime,
            'file_size'       => $size,
            'before_status'   => $asset->kode_status,
        ]);

        Assets::where('uuid', $asset->uuid)->update([
            'kode_status' => 'DIS',
        ]);

        return response()->json(['ok' => true, 'id' => $row->uuid, 'code' => $row->disposal_code]);
    }

    private function saveUploadDis(?UploadedFile $file, Assets $asset, string $code, ?Disposal $existing = null): array
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
}
