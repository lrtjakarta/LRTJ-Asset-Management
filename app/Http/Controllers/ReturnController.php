<?php

namespace App\Http\Controllers;

use App\Models\Assets;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\ReturnHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ReturnController extends Controller
{
    private function canReadReturn(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('RETURN', 'R');
    }

    private function canCreateReturn(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('RETURN', 'C');
    }

    private function canDeleteReturn(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('RETURN', 'D');
    }
    public function index()
    {
        return view('return.return');
    }
    public function options(Request $request)
    {
        if (!$this->canCreateReturn()) {
            return response()->json([
                'results'    => [],
                'pagination' => ['more' => false],
            ]);
        }
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
                ? "{$r->code} • MOVEMENT • {$assetLabel} • {$r->before_label} → {$r->after_label}"
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
        abort_unless($this->canCreateReturn(), 403, 'You are not allowed to create return.');
        $data = $request->validate([
            'source' => ['required', 'string'],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        [$sourceType, $sourceId] = array_pad(explode('|', $data['source'], 2), 2, null);
        if (!in_array($sourceType, ['transfer', 'disposal'], true) || !Str::isUuid($sourceId)) {
            abort(422, 'Invalid source selection.');
        }
        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        DB::transaction(function () use ($sourceType, $sourceId, $data, $uid) {
            if ($sourceType === 'transfer') {
                $src = DB::table('assets_transfers as t')
                    ->selectRaw("t.*, t.before->>'value' as before_value")
                    ->where('t.uuid', $sourceId)
                    ->whereNull('t.deleted_at')
                    ->where('t.kode_status', 'ACC')
                    ->first();

                abort_if(!$src, 404, 'Source not found or not accepted.');

                $chk = DB::table('assets_transfers')
                    ->whereNull('deleted_at')->where('kode_status', 'ACC')
                    ->where('asset_uuid', $src->asset_uuid)
                    ->where('type', $src->type)
                    ->orderByDesc('created_at')->orderByDesc('uuid')
                    ->first();
                abort_if(!$chk || $chk->uuid !== $src->uuid, 422, 'Source is no longer the latest accepted transfer.');

                $assetUuid  = $src->asset_uuid;
                $sourceCode = $src->transfer_code ?? null;
                $before     = $src->before_value;

                $asset = Assets::with('assignment')->findOrFail($assetUuid);

                switch ($src->type) {
                    case 'owner':
                        $asset->assignment()->updateOrCreate(
                            ['asset_uuid' => $asset->uuid],
                            ['asset_owner' => $before]
                        );
                        break;
                    case 'user':
                        $asset->assignment()->updateOrCreate(
                            ['asset_uuid' => $asset->uuid],
                            ['asset_user' => $before]
                        );
                        break;
                    case 'maintenance':
                        $asset->assignment()->updateOrCreate(
                            ['asset_uuid' => $asset->uuid],
                            ['asset_maintenance' => $before]
                        );
                        break;
                    case 'status':
                        DB::table('assets')->where('uuid', $assetUuid)->update([
                            'kode_status' => $before,
                            'updated_at'  => now(),
                        ]);
                        break;
                    case 'location':
                        DB::table('assets')->where('uuid', $assetUuid)->update([
                            'kode_location' => $before,
                            'updated_at'    => now(),
                        ]);
                        break;
                    default:
                        abort(422, 'Unsupported transfer type.');
                }

                DB::table('assets_transfers')->where('uuid', $sourceId)->update([
                    'kode_status' => 'RET',
                    'updated_at'  => now(),
                ]);
            } else {
                // ---- disposal ----
                $src = DB::table('assets_disposals')
                    ->where('uuid', $sourceId)
                    ->whereNull('deleted_at')
                    ->where('kode_status', 'ACC')
                    ->first();

                abort_if(!$src, 404, 'Source not found or not accepted.');

                $chk = DB::table('assets_disposals')
                    ->whereNull('deleted_at')->where('kode_status', 'ACC')
                    ->where('asset_uuid', $src->asset_uuid)
                    ->orderByDesc('created_at')->orderByDesc('uuid')
                    ->first();
                abort_if(!$chk || $chk->uuid !== $src->uuid, 422, 'Source is no longer the latest accepted disposal.');

                $assetUuid  = $src->asset_uuid;
                $sourceCode = $src->disposal_code ?? null;

                DB::table('assets')->where('uuid', $assetUuid)->update([
                    'kode_status' => $src->before_status,
                    'updated_at'  => now(),
                ]);

                DB::table('assets_disposals')->where('uuid', $sourceId)->update([
                    'kode_status' => 'RET',
                    'updated_at'  => now(),
                ]);
            }

            $now    = Carbon::now();
            $prefix = 'RET' . $now->format('ym');
            $last = ReturnHistory::where('return_code', 'like', $prefix . '%')
                ->orderBy('return_code', 'desc')
                ->first();

            if ($last) {
                $lastSeq = (int) substr($last->return_code, -4);
                $seq     = $lastSeq + 1;
            } else {
                $seq = 1;
            }
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            DB::table('return_history')->insert([
                'uuid'            => (string) Str::uuid(),
                'asset_uuid'      => $assetUuid,
                'source_type'     => $sourceType,
                'source_id'       => $sourceId,
                'source_code'     => $sourceCode,
                'note'            => $data['note'] ?? null,
                'pic_request_uid' => $uid,
                'created_at'      => now(),
                'updated_at'      => now(),
                'return_code'     => $code,
            ]);
        });

        return response()->json(['ok' => true]);
    }
    public function datatable_by_asset(Request $request, string $assetUuid)
    {
        if (!$this->canReadReturn()) {
            return DataTables::of(collect())->toJson();
        }
        $q = ReturnHistory::query()
            ->where('asset_uuid', $assetUuid)
            ->with(['source'])
            ->orderByDesc('created_at');

        $user = $request->user();
        $canDelete = $user && $user->hasAction('RETURN', 'D');

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
            ->addColumn('source_type_label', function (ReturnHistory $r) {
                if ($r->source_type === 'transfer' && $r->source) {
                    return 'MOVEMENT';
                }
                if ($r->source_type === 'disposal') {
                    return 'DISPOSAL';
                }
                return '';
            })
            ->addColumn('source_detail', function (ReturnHistory $r) use ($resolve) {
                if ($r->source_type === 'transfer' && $r->source) {
                    $t = $r->source;
                    $type = "MOVEMENT";
                    $before = $resolve($t->type, data_get($t->before, 'value'));
                    $after  = $resolve($t->type, data_get($t->after,  'value'));
                    return "{$type}: {$before} &rarr; <span class=\"fw-bold\">{$after}</span>";
                }
                if ($r->source_type === 'disposal') {
                    return 'DISPOSAL';
                }
                return '';
            })
            ->addColumn('actions', function (ReturnHistory $r)  use ($canDelete) {
                if ($canDelete) {
                    return '<div class="btn-group btn-group-sm">
                        <button class="btn btn-light-danger btn-ret-delete" data-id="' . $r->uuid . '">Delete</button>
                    </div>';
                } else {
                    return '-';
                }
            })
            ->rawColumns(['source_detail', 'actions'])
            ->toJson();
    }
    public function datatable_all(Request $request)
    {
        if (!$this->canReadReturn()) {
            return DataTables::of(collect())->toJson();
        }

        $source    = $request->input('source');
        $tfType    = $request->input('tf_type');
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');  
        $assetLike = $request->input('asset');    
        $users     = $request->input('users');   

        $q = ReturnHistory::query()
            ->from('return_history')
            ->with(['source', 'asset'])
            ->leftJoin('assets as a', 'a.uuid', '=', 'return_history.asset_uuid')
            ->orderByDesc('return_history.created_at')
            ->select([
                'return_history.*',
                'a.asset_code',
                'a.description',
            ]);

        if ($source) {
            $q->where('return_history.source_type', $source);
        }

        if ($tfType) {
            $q->where('return_history.source_type', 'transfer')
                ->whereHas('source', function ($t) use ($tfType) {
                    $t->where('type', $tfType);
                });
        }

        if ($dateFrom) {
            $q->whereDate('return_history.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('return_history.created_at', '<=', $dateTo);
        }

        if ($assetLike) {
            $q->where(function ($w) use ($assetLike) {
                $w->where('a.asset_code', 'ilike', "%{$assetLike}%")
                    ->orWhere('a.description', 'ilike', "%{$assetLike}%");
            });
        }

        if ($users) {
            $q->where('return_history.pic_request_uid', $users);
        }

        $user = $request->user();
        $canDelete = $user && $user->hasAction('RETURN', 'D');

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
            ->addColumn('source_type_label', function (ReturnHistory $r) {
                if ($r->source_type === 'transfer' && $r->source) {
                    return 'MOVEMENT';
                }
                if ($r->source_type === 'disposal') {
                    return 'DISPOSAL';
                }
                return '';
            })
            ->addColumn('source_detail', function (ReturnHistory $r) use ($resolve) {
                if ($r->source_type === 'transfer' && $r->source) {
                    $t = $r->source;
                    $type = 'MOVEMENT';
                    $before = $resolve($t->type, data_get($t->before, 'value'));
                    $after  = $resolve($t->type, data_get($t->after,  'value'));
                    return "{$type}: {$before} &rarr; <span class=\"fw-bold\">{$after}</span>";
                }
                if ($r->source_type === 'disposal') {
                    return 'DISPOSAL';
                }
                return '';
            })
            ->addColumn('actions', function (ReturnHistory $r) use ($canDelete) {
                if ($canDelete) {
                    return '<div class="btn-group btn-group-sm">
                    <button class="btn btn-light-danger btn-ret-delete" data-id="' . $r->uuid . '">Delete</button>
                </div>';
                }
                return '-';
            })
            ->rawColumns(['source_detail', 'actions'])
            ->toJson();
    }


    /** Soft delete */
    public function destroy(string $uuid)
    {
        abort_unless($this->canDeleteReturn(), 403, 'You are not allowed to delete return history.');
        $tf = ReturnHistory::where('uuid', $uuid)->firstOrFail();
        $tf->delete();

        return response()->json(['ok' => true]);
    }
}
