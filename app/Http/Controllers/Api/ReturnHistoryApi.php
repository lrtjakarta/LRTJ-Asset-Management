<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReturnHistoryApi extends Controller
{
    public function index(Request $request)
    {
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'source_type'  => ['nullable', Rule::in(['transfer','disposal'])],
            'asset_uuid'   => ['nullable', 'uuid'],
            'asset_uuids'  => ['nullable'],
            'asset_uuids.*'=> ['uuid'],

            'uuid'         => ['nullable','uuid'],
            'uuids'        => ['nullable'],
            'uuids.*'      => ['uuid'],

            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
            'sort_by'      => ['nullable', Rule::in(['created_at','updated_at','source_type','source_code'])],
            'sort_dir'     => ['nullable', Rule::in(['asc','desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
        ])->validate();

        $parseUuidSet = function ($singleKey, $multiKey) use ($request) {
            return collect()
                ->merge(Arr::wrap($request->query($multiKey)))
                ->when($request->filled($singleKey), fn($c) => $c->push($request->query($singleKey)))
                ->flatMap(fn($v) => is_array($v) ? $v : (is_string($v) ? explode(',', $v) : []))
                ->map(fn($x) => trim($x))
                ->filter(fn($x) => $x !== '' && Str::isUuid($x))
                ->unique()
                ->values();
        };

        $assetUuids = $parseUuidSet('asset_uuid', 'asset_uuids');
        $uuids      = $parseUuidSet('uuid', 'uuids');

        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';

        $q = DB::table('return_history as r')
            ->leftJoin('assets as a', 'a.uuid', '=', 'r.asset_uuid')
            ->select([
                'r.uuid',
                'r.asset_uuid',
                'r.source_type',
                'r.source_id',
                'r.source_code',
                'r.note',
                'r.pic_request_uid',
                'r.created_at',
                'r.updated_at',
                DB::raw('a.asset_code as asset_code'),
                DB::raw('a.description as asset_description'),
            ])
            ->when(!empty($v['q']), function ($qb) use ($v) {
                $like = '%' . trim($v['q']) . '%';
                $qb->where(function ($w) use ($like) {
                    $w->where('r.source_code', 'ILIKE', $like)
                      ->orWhere('r.note',        'ILIKE', $like)
                      ->orWhere('r.pic_request_uid', 'ILIKE', $like)
                      ->orWhere('a.asset_code',  'ILIKE', $like)
                      ->orWhere('a.description', 'ILIKE', $like);
                });
            })
            ->when(!empty($v['source_type']), fn($qb) => $qb->where('r.source_type', $v['source_type']))
            ->when($assetUuids->isNotEmpty(), fn($qb) => $qb->whereIn('r.asset_uuid', $assetUuids->all()))
            ->when($uuids->isNotEmpty(),      fn($qb) => $qb->whereIn('r.uuid', $uuids->all()))
            ->when(!empty($v['from']),        fn($qb) => $qb->where('r.created_at', '>=', $v['from']))
            ->when(!empty($v['to']),          fn($qb) => $qb->where('r.created_at', '<=', $v['to']))
            ->orderBy("r.$sortBy", $sortDir);

        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();

            return response()->json([
                'data' => $rows->map(fn($r) => (array) $r),
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q'           => $request->query('q'),
                        'source_type' => $request->query('source_type'),
                        'asset_uuid'  => $request->query('asset_uuid'),
                        'asset_uuids' => $assetUuids->all(),
                        'uuid'        => $request->query('uuid'),
                        'uuids'       => $uuids->all(),
                        'from'        => $request->query('from'),
                        'to'          => $request->query('to'),
                    ],
                ],
                'links' => ['first'=>null,'prev'=>null,'next'=>null,'last'=>null],
            ]);
        }

        $perPage   = (int) ($v['per_page'] ?? 15);
        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($r) => (array) $r),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'paginated'    => true,
                'sort_by'      => $sortBy,
                'sort_dir'     => $sortDir,
                'filters'      => [
                    'q'           => $request->query('q'),
                    'source_type' => $request->query('source_type'),
                    'asset_uuid'  => $request->query('asset_uuid'),
                    'asset_uuids' => $assetUuids->all(),
                    'uuid'        => $request->query('uuid'),
                    'uuids'       => $uuids->all(),
                    'from'        => $request->query('from'),
                    'to'          => $request->query('to'),
                ],
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
                'last'  => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $isBatch = $request->has('items');

        if ($isBatch) {
            $data = $request->validate([
                'items'          => ['required', 'array', 'min:1'],
                'items.*.source' => ['required', 'string'],
                'items.*.note'   => ['nullable', 'string', 'max:1000'],
            ]);
        } else {
            $data = $request->validate([
                'source' => ['required', 'string'],
                'note'   => ['nullable', 'string', 'max:1000'],
            ]);
        }

        $uid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        abort_if($uid === '', 401, 'No session UID.');

        $processOne = function (string $source, ?string $note) use ($uid) {
            [$sourceType, $sourceId] = array_pad(explode('|', $source, 2), 2, null);
            if (!in_array($sourceType, ['transfer', 'disposal'], true) || !Str::isUuid($sourceId)) {
                return ['ok' => false, 'error' => 'invalid_source', 'message' => 'Invalid source selection.'];
            }

            try {
                return DB::transaction(function () use ($sourceType, $sourceId, $note, $uid) {
                    $existing = DB::table('return_history')
                        ->where('source_type', $sourceType)
                        ->where('source_id', $sourceId)
                        ->first();
                    if ($existing) {
                        return [
                            'ok' => true,
                            'id' => (string) $existing->uuid,
                            'idempotent' => true,
                            'source_type' => $sourceType,
                            'source_id'   => $sourceId,
                        ];
                    }

                    if ($sourceType === 'transfer') {
                        $src = DB::table('assets_transfers as t')
                            ->selectRaw("t.*, t.before->>'value' as before_value")
                            ->where('t.uuid', $sourceId)
                            ->whereNull('t.deleted_at')
                            ->first();

                        if (!$src) {
                            return ['ok' => false, 'error' => 'not_found', 'message' => 'Transfer not found.'];
                        }

                        if ($src->kode_status === 'RET') {
                            $existsHist = DB::table('return_history')
                                ->where('source_type', 'transfer')
                                ->where('source_id', $sourceId)
                                ->exists();
                            if (!$existsHist) {
                                $uuid = (string) Str::uuid();
                                DB::table('return_history')->insert([
                                    'uuid'            => $uuid,
                                    'asset_uuid'      => $src->asset_uuid,
                                    'source_type'     => 'transfer',
                                    'source_id'       => $sourceId,
                                    'source_code'     => $src->transfer_code ?? null,
                                    'note'            => $note,
                                    'pic_request_uid' => $uid,
                                    'created_at'      => now(),
                                    'updated_at'      => now(),
                                ]);
                                return ['ok' => true, 'id' => $uuid, 'idempotent' => true, 'source_type' => 'transfer', 'source_id' => $sourceId];
                            }
                            return ['ok' => true, 'idempotent' => true, 'source_type' => 'transfer', 'source_id' => $sourceId];
                        }

                        if ($src->kode_status !== 'ACC') {
                            return ['ok' => false, 'error' => 'not_accepted', 'message' => 'Transfer not accepted.'];
                        }

                        $chk = DB::table('assets_transfers')
                            ->whereNull('deleted_at')->where('kode_status', 'ACC')
                            ->where('asset_uuid', $src->asset_uuid)
                            ->where('type', $src->type)
                            ->orderByDesc('created_at')->orderByDesc('uuid')
                            ->first();
                        if (!$chk || $chk->uuid !== $src->uuid) {
                            return ['ok' => false, 'error' => 'stale', 'message' => 'A newer accepted transfer exists.'];
                        }

                        $assetUuid  = $src->asset_uuid;
                        $sourceCode = $src->transfer_code ?? null;
                        $before     = $src->before_value;

                        DB::table('assets')->where('uuid', $assetUuid)->lockForUpdate()->first();

                        switch ($src->type) {
                            case 'owner':
                                DB::table('assets_assignment')->updateOrInsert(
                                    ['asset_uuid' => $assetUuid],
                                    ['asset_owner' => $before, 'updated_at' => now()]
                                );
                                break;
                            case 'user':
                                DB::table('assets_assignment')->updateOrInsert(
                                    ['asset_uuid' => $assetUuid],
                                    ['asset_user' => $before, 'updated_at' => now()]
                                );
                                break;
                            case 'maintenance':
                                DB::table('assets_assignment')->updateOrInsert(
                                    ['asset_uuid' => $assetUuid],
                                    ['asset_maintenance' => $before, 'updated_at' => now()]
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
                                return ['ok' => false, 'error' => 'unsupported', 'message' => 'Unsupported transfer type.'];
                        }

                        DB::table('assets_transfers')
                            ->where('uuid', $sourceId)
                            ->where('kode_status', 'ACC')
                            ->update(['kode_status' => 'RET', 'updated_at' => now()]);

                        $uuid = (string) Str::uuid();
                        DB::table('return_history')->insert([
                            'uuid'            => $uuid,
                            'asset_uuid'      => $assetUuid,
                            'source_type'     => 'transfer',
                            'source_id'       => $sourceId,
                            'source_code'     => $sourceCode,
                            'note'            => $note,
                            'pic_request_uid' => $uid,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);

                        return ['ok' => true, 'id' => $uuid, 'source_type' => 'transfer', 'source_id' => $sourceId];
                    }

                    $src = DB::table('assets_disposals')
                        ->where('uuid', $sourceId)
                        ->whereNull('deleted_at')
                        ->first();

                    if (!$src) {
                        return ['ok' => false, 'error' => 'not_found', 'message' => 'Disposal not found.'];
                    }

                    if ($src->kode_status === 'RET') {
                        $existsHist = DB::table('return_history')
                            ->where('source_type', 'disposal')
                            ->where('source_id', $sourceId)
                            ->exists();
                        if (!$existsHist) {
                            $uuid = (string) Str::uuid();
                            DB::table('return_history')->insert([
                                'uuid'            => $uuid,
                                'asset_uuid'      => $src->asset_uuid,
                                'source_type'     => 'disposal',
                                'source_id'       => $sourceId,
                                'source_code'     => $src->disposal_code ?? null,
                                'note'            => $note,
                                'pic_request_uid' => $uid,
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ]);
                            return ['ok' => true, 'id' => $uuid, 'idempotent' => true, 'source_type' => 'disposal', 'source_id' => $sourceId];
                        }
                        return ['ok' => true, 'idempotent' => true, 'source_type' => 'disposal', 'source_id' => $sourceId];
                    }

                    if ($src->kode_status !== 'ACC') {
                        return ['ok' => false, 'error' => 'not_accepted', 'message' => 'Disposal not accepted.'];
                    }

                    $chk = DB::table('assets_disposals')
                        ->whereNull('deleted_at')->where('kode_status', 'ACC')
                        ->where('asset_uuid', $src->asset_uuid)
                        ->orderByDesc('created_at')->orderByDesc('uuid')
                        ->first();
                    if (!$chk || $chk->uuid !== $sourceId) {
                        return ['ok' => false, 'error' => 'stale', 'message' => 'A newer accepted disposal exists.'];
                    }

                    $assetUuid  = $src->asset_uuid;
                    $sourceCode = $src->disposal_code ?? null;

                    DB::table('assets')->where('uuid', $assetUuid)->lockForUpdate()->first();
                    DB::table('assets')->where('uuid', $assetUuid)->update([
                        'kode_status' => $src->before_status,
                        'updated_at'  => now(),
                    ]);

                    DB::table('assets_disposals')
                        ->where('uuid', $sourceId)
                        ->where('kode_status', 'ACC')
                        ->update(['kode_status' => 'RET', 'updated_at' => now()]);

                    $uuid = (string) Str::uuid();
                    DB::table('return_history')->insert([
                        'uuid'            => $uuid,
                        'asset_uuid'      => $assetUuid,
                        'source_type'     => 'disposal',
                        'source_id'       => $sourceId,
                        'source_code'     => $sourceCode,
                        'note'            => $note,
                        'pic_request_uid' => $uid,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    return ['ok' => true, 'id' => $uuid, 'source_type' => 'disposal', 'source_id' => $sourceId];
                });
            } catch (\Throwable $e) {
                return ['ok' => false, 'error' => 'server_error', 'message' => 'Failed to process return.'];
            }
        };

        if ($isBatch) {
            $results = collect($data['items'])->map(fn($it) => $processOne($it['source'], $it['note'] ?? null));
            return response()->json([
                'ok'      => $results->every(fn ($r) => data_get($r, 'ok') === true),
                'results' => $results,
            ], 200);
        }

        $r = $processOne($data['source'], $data['note'] ?? null);
        return response()->json($r, $r['ok'] ? 201 : 422);
    }
    public function options(Request $request)
    {
        $v = validator($request->all(), [
            'q'           => ['nullable', 'string', 'max:200'],
            'limit'       => ['nullable', 'integer', 'min:1', 'max:200'],
            'asset_uuid'  => ['nullable', 'uuid'],
            'source_type' => ['nullable', Rule::in(['transfer','disposal','all'])],
        ])->validate();

        $qStr       = trim((string) ($v['q'] ?? ''));
        $limit      = (int) ($v['limit'] ?? 100);
        $assetUuid  = (string) ($v['asset_uuid'] ?? '');
        $sourceType = (string) ($v['source_type'] ?? 'all');

        $transferAccepted = 'ACC';
        $disposalAccepted = 'ACC';

        $latestTransfersBase = DB::table('assets_transfers as t')
            ->whereNull('t.deleted_at')
            ->where('t.kode_status', $transferAccepted);

        if ($assetUuid !== '') {
            $latestTransfersBase->where('t.asset_uuid', $assetUuid);
        }

        $latestTransfers = $latestTransfersBase->selectRaw("
            t.uuid            as source_id,
            t.asset_uuid      as asset_uuid,
            t.transfer_code   as code,
            'transfer'        as source_type,
            t.type            as tf_type,
            COALESCE(NULLIF(t.before_label, ''), t.before->>'value', '') as before_label,
            COALESCE(NULLIF(t.after_label,  ''), t.after->>'value',  '') as after_label,
            t.created_at      as created_at,
            ROW_NUMBER() OVER (PARTITION BY t.asset_uuid, t.type ORDER BY t.created_at DESC, t.uuid DESC) as rn
        ");

        $latestDisposalsBase = DB::table('assets_disposals as d')
            ->whereNull('d.deleted_at')
            ->where('d.kode_status', $disposalAccepted);

        if ($assetUuid !== '') {
            $latestDisposalsBase->where('d.asset_uuid', $assetUuid);
        }

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

        if ($sourceType === 'transfer') {
            $union = $tLatest;
        } elseif ($sourceType === 'disposal') {
            $union = $dLatest;
        } else {
            $union = $tLatest->unionAll($dLatest);
        }

        $rows = DB::query()
            ->fromSub($union, 'u')
            ->join('assets as a', 'a.uuid', '=', 'u.asset_uuid')
            ->when($qStr !== '', function ($qq) use ($qStr) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $qStr) . '%';
                $qq->where(function ($w) use ($like) {
                    $w->where('u.code',        'ILIKE', $like)
                      ->orWhere('a.asset_code','ILIKE', $like)
                      ->orWhere('a.description','ILIKE', $like);
                });
            })
            ->orderByDesc('u.created_at')
            ->limit($limit)
            ->get();

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
}
