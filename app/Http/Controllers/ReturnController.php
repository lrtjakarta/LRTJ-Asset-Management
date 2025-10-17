<?php

namespace App\Http\Controllers;

use App\Models\Assets;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\ReturnHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ReturnController extends Controller
{
    public function index()
    {
        return view('return.return');
    }
    public function options(Request $request)
    {
        $q         = trim((string) $request->get('q', ''));
        $limit     = max(10, (int) $request->get('limit', 100));
        $assetUuid = (string) $request->get('asset_uuid', '');

        $transferAccepted = 'ACC';
        $disposalAccepted = 'ACC';

        $latestTransfersBase = DB::table('assets_transfers as t')
            ->whereNull('t.deleted_at')
            ->where('t.kode_status', $transferAccepted);

        $latestDisposalsBase = DB::table('assets_disposals as d')
            ->whereNull('d.deleted_at')
            ->where('d.kode_status', $disposalAccepted);

        if ($assetUuid !== '') {
            $latestTransfersBase->where('t.asset_uuid', $assetUuid);
            $latestDisposalsBase->where('d.asset_uuid', $assetUuid);
        }

        $latestTransfers = $latestTransfersBase->selectRaw("
            t.uuid            as source_id,
            t.asset_uuid      as asset_uuid,
            t.transfer_code   as code,
            'transfer'        as source_type,
            t.type            as tf_type,
            COALESCE(t.before->>'value', '') as before_label,
            COALESCE(t.after->>'value',  '') as after_label,
            t.created_at      as created_at,
            ROW_NUMBER() OVER (PARTITION BY t.asset_uuid, t.type ORDER BY t.created_at DESC, t.uuid DESC) as rn
        ");

        $latestDisposals = $latestDisposalsBase->selectRaw("
            d.uuid            as source_id,
            d.asset_uuid      as asset_uuid,
            d.disposal_code   as code,
            'disposal'        as source_type,
            NULL::text        as tf_type,
            NULL::text        as before_label,
            NULL::text        as after_label,
            d.created_at      as created_at,
            ROW_NUMBER() OVER (PARTITION BY d.asset_uuid ORDER BY d.created_at DESC, d.uuid DESC) as rn
        ");

        $tLatest = DB::query()->fromSub($latestTransfers, 'x')->where('x.rn', 1);
        $dLatest = DB::query()->fromSub($latestDisposals, 'y')->where('y.rn', 1);

        $union = $tLatest->unionAll($dLatest);

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->join('assets as a', 'a.uuid', '=', 'u.asset_uuid')
            ->when($q !== '', function ($qq) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $qq->where(function ($w) use ($like) {
                    $w->where('u.code', 'ilike', $like)
                        ->orWhere('a.asset_code', 'ilike', $like)
                        ->orWhere('a.description', 'ilike', $like);
                });
            })
            ->orderByDesc('u.created_at')
            ->limit($limit)
            ->get();

        // Format for Select2
        $results = $rows->map(function ($r) {
            $assetLabel = trim(($r->asset_code ?? '') . ' - ' . ($r->description ?? ''));
            $text = ($r->source_type === 'transfer')
                ? "{$r->code} • " . strtoupper($r->tf_type) . " • {$assetLabel} • {$r->before_label} → {$r->after_label}"
                : "{$r->code} • DISPOSAL • {$assetLabel}";

            return [
                'id'            => $r->source_type . '|' . $r->source_id,
                'text'          => $text,
                'source_type'   => $r->source_type,
                'source_id'     => $r->source_id,
                'code'          => $r->code,
                'asset_uuid'    => $r->asset_uuid,
                'asset_label'   => $assetLabel,
                'type'          => $r->tf_type,
                'before_label'  => $r->before_label,
                'after_label'   => $r->after_label,
                'created_at'    => $r->created_at,
            ];
        });

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => false],
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'source' => ['required', 'string'],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        [$sourceType, $sourceId] = array_pad(explode('|', $data['source'], 2), 2, null);
        if (!in_array($sourceType, ['transfer', 'disposal'], true) || !Str::isUuid($sourceId)) {
            abort(422, 'Invalid source selection.');
        }

        $uid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        if ($uid === '') abort(401, 'No session UID.');

        if ($sourceType === 'transfer') {
            $src = DB::table('assets_transfers')->where('uuid', $sourceId)
                ->whereNull('deleted_at')->where('kode_status', 'ACC')->first();
            abort_if(!$src, 404, 'Source not found or not accepted.');
            $chk = DB::table('assets_transfers')
                ->whereNull('deleted_at')->where('kode_status', 'ACC')
                ->where('asset_uuid', $src->asset_uuid)
                ->where('type', $src->type)
                ->orderByDesc('created_at')->orderByDesc('uuid')
                ->first();
            abort_if(!$chk || $chk->uuid !== $sourceId, 422, 'Source is no longer the latest accepted transfer.');

            $sourceCode = $src->transfer_code ?? null;
            $assetUuid  = $src->asset_uuid;
            DB::table('assets_transfers')->where('uuid', $sourceId)->update(['kode_status'=> 'RET']);
        } else {
            // disposal
            $src = DB::table('assets_disposals')->where('uuid', $sourceId)
                ->whereNull('deleted_at')->where('kode_status', 'ACC')->first();
            abort_if(!$src, 404, 'Source not found or not accepted.');
            $chk = DB::table('assets_disposals')
                ->whereNull('deleted_at')->where('kode_status', 'ACC')
                ->where('asset_uuid', $src->asset_uuid)
                ->orderByDesc('created_at')->orderByDesc('uuid')
                ->first();
            abort_if(!$chk || $chk->uuid !== $sourceId, 422, 'Source is no longer the latest accepted disposal.');

            $sourceCode = $src->disposal_code ?? null;
            $assetUuid  = $src->asset_uuid;
            DB::table('assets_disposals')->where('uuid', $sourceId)->update(['kode_status'=> 'RET']);
        }

        $id = (string) Str::uuid();
        DB::table('return_history')->insert([
            'uuid'            => $id,
            'asset_uuid'      => $assetUuid,
            'source_type'     => $sourceType,
            'source_id'       => $sourceId,
            'source_code'     => $sourceCode,
            'note'            => $data['note'] ?? null,
            'pic_request_uid' => $uid,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['ok' => true, 'uuid' => $id, 'code' => $sourceCode]);
    }
    public function datatable_by_asset(Request $request, string $assetUuid)
    {
        $q = ReturnHistory::query()
            ->where('asset_uuid', $assetUuid)
            ->with(['source'])
            ->orderByDesc('created_at');

        $resolve = function (?string $type, ?string $code): string {
            if (!$code) return '(empty)';
            switch ($type) {
                case 'owner':
                case 'user':
                case 'maintenance': {
                        $row = MasterUserCode::select('kode', 'department')->where('kode', $code)->first();
                        return $row ? ($code . ' - ' . $row->department) : $code;
                    }
                case 'status': {
                        $row = MasterStatus::select('kode', 'name')->where('kode', $code)->first();
                        return $row ? ($code . ' - ' . $row->name) : $code;
                    }
                case 'location': {
                        $row = MasterLocation::select('kode', 'name')->where('kode', $code)->first();
                        return $row ? ($code . ' - ' . $row->name) : $code;
                    }
                default:
                    return $code;
            }
        };

        return DataTables::of($q)
            ->addColumn('source_type_label', fn(ReturnHistory $r) => strtoupper($r->source_type))
            ->addColumn('source_detail', function (ReturnHistory $r) use ($resolve) {
                if ($r->source_type === 'transfer' && $r->source) {
                    $t = $r->source;
                    $type = strtoupper($t->type);
                    $before = $resolve($t->type, data_get($t->before, 'value'));
                    $after  = $resolve($t->type, data_get($t->after,  'value'));
                    return "{$type}: {$before} &rarr; <span class=\"fw-bold\">{$after}</span>";
                }
                // For disposal returns: simple label
                if ($r->source_type === 'disposal') {
                    return 'DISPOSAL';
                }
                return '';
            })
            ->addColumn('actions', function (ReturnHistory $r) {
                return '<div class="btn-group btn-group-sm">
                        <button class="btn btn-light-danger btn-ret-delete" data-id="' . $r->uuid . '">Delete</button>
                    </div>';
            })
            ->rawColumns(['source_detail', 'actions'])
            ->toJson();
    }

    public function datatable_all(Request $request)
    {
        $q = ReturnHistory::query()
            ->from('return_history')
            ->with(['source'])
            ->leftJoin('assets as a', 'a.uuid', '=', 'return_history.asset_uuid')
            ->orderByDesc('created_at')
            ->select([
                'return_history.*',
                'a.asset_code',
                'a.description as asset_desc',
            ]);

        $resolve = function (?string $type, ?string $code): string {
            if (!$code) return '(empty)';
            switch ($type) {
                case 'owner':
                case 'user':
                case 'maintenance': {
                        $row = MasterUserCode::select('kode', 'department')->where('kode', $code)->first();
                        return $row ? ($code . ' - ' . $row->department) : $code;
                    }
                case 'status': {
                        $row = MasterStatus::select('kode', 'name')->where('kode', $code)->first();
                        return $row ? ($code . ' - ' . $row->name) : $code;
                    }
                case 'location': {
                        $row = MasterLocation::select('kode', 'name')->where('kode', $code)->first();
                        return $row ? ($code . ' - ' . $row->name) : $code;
                    }
                default:
                    return $code;
            }
        };

        return DataTables::of($q)
            ->addColumn('source_type_label', fn(ReturnHistory $r) => strtoupper($r->source_type))
            ->addColumn('source_detail', function (ReturnHistory $r) use ($resolve) {
                if ($r->source_type === 'transfer' && $r->source) {
                    $t = $r->source;
                    $type = strtoupper($t->type);
                    $before = $resolve($t->type, data_get($t->before, 'value'));
                    $after  = $resolve($t->type, data_get($t->after,  'value'));
                    return "{$type}: {$before} &rarr; <span class=\"fw-bold\">{$after}</span>";
                }
                // For disposal returns: simple label
                if ($r->source_type === 'disposal') {
                    return 'DISPOSAL';
                }
                return '';
            })
            ->addColumn('actions', function (ReturnHistory $r) {
                return '<div class="btn-group btn-group-sm">
                        <button class="btn btn-light-danger btn-ret-delete" data-id="' . $r->uuid . '">Delete</button>
                    </div>';
            })
            ->rawColumns(['source_detail', 'actions'])
            ->toJson();
    }

    /** Soft delete */
    public function destroy(string $uuid)
    {
        $tf = ReturnHistory::where('uuid', $uuid)->firstOrFail();
        $tf->delete();

        return response()->json(['ok' => true]);
    }
}
