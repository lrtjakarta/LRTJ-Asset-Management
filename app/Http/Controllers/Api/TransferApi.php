<?php

namespace App\Http\Controllers\Api;

use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransferApi extends Controller
{
    public function index(Request $request)
    {
        $v = validator($request->all(), [
            'asset_uuid'   => ['nullable'],
            'asset_uuid.*' => ['nullable','uuid'],

            'q'            => ['nullable', 'string', 'max:200'],
            'type'         => ['nullable', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'sort_by'      => ['nullable', Rule::in(['created_at', 'updated_at', 'kode_status', 'type', 'asset_code', 'asset_description'])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
            'with_trashed' => ['nullable', Rule::in(['0','1'])],
        ])->validate();

        // normalize asset_uuid list
        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )->map(fn($x) => trim($x))->filter()->unique()->values();

        foreach ($assetUuids as $u) {
            if (!Str::isUuid($u)) abort(422, "asset_uuid contains invalid UUID: {$u}");
        }

        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $tz      = config('app.timezone', 'UTC');

        $q = Transfer::query()
            ->select([
                'assets_transfers.*',

                // --- Asset core fields ---
                DB::raw('a.asset_code        as asset_code'),
                DB::raw('a.description       as asset_description'),
                DB::raw('a.kode_location     as asset_kode_location'),
                DB::raw('a.kode_asset_class  as asset_kode_asset_class'),
                DB::raw('a.kode_status       as asset_kode_status'),
                DB::raw('a.kode_sumber       as asset_kode_sumber'),

                // --- Asset label fields (KODE - Name) ---
                DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS asset_kode_location_label"),
                DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS asset_kode_asset_class_label"),
                DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS asset_kode_status_label"),
                DB::raw("CASE WHEN msrc.name IS NULL THEN a.kode_sumber     ELSE a.kode_sumber     || ' - ' || msrc.name END AS asset_kode_sumber_label"),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_transfers.asset_uuid')
            ->leftJoin('master_location     as ml',   'ml.kode',   '=', 'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',  '=', 'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',   '=', 'a.kode_status')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode', '=', 'a.kode_sumber')
            ->with(['status:uuid,kode,name'])
            ->when($request->boolean('with_trashed'), fn($x) => $x->withTrashed())
            // filters
            ->when($assetUuids->isNotEmpty(), fn($x) => $x->whereIn('assets_transfers.asset_uuid', $assetUuids))
            ->when(!empty($v['type']), fn($x) => $x->where('assets_transfers.type', $v['type']))
            ->when(!empty($v['from']), fn($x) => $x->where('assets_transfers.created_at', '>=', $v['from']))
            ->when(!empty($v['to']),   fn($x) => $x->where('assets_transfers.created_at', '<=', $v['to']))
            // free text across transfer + asset + master names
            ->when(!empty($v['q']), function ($x) use ($v) {
                $like = '%' . trim($v['q']) . '%';
                $x->where(function ($w) use ($like) {
                    $w->where('assets_transfers.transfer_code', 'ILIKE', $like)
                      ->orWhere('assets_transfers.kode_status',  'ILIKE', $like)
                      ->orWhere('assets_transfers.type',         'ILIKE', $like)
                      ->orWhere('a.asset_code',                  'ILIKE', $like)
                      ->orWhere('a.description',                 'ILIKE', $like)
                      ->orWhere('a.kode_location',               'ILIKE', $like)
                      ->orWhere('a.kode_asset_class',            'ILIKE', $like)
                      ->orWhere('a.kode_status',                 'ILIKE', $like)
                      ->orWhere('a.kode_sumber',                 'ILIKE', $like)
                      ->orWhere('ml.name',                       'ILIKE', $like)
                      ->orWhere('mac.name',                      'ILIKE', $like)
                      ->orWhere('ms.name',                       'ILIKE', $like)
                      ->orWhere('msrc.name',                     'ILIKE', $like);
                });
            });

        // safe sorting
        $sortColumns = [
            'created_at'        => 'assets_transfers.created_at',
            'updated_at'        => 'assets_transfers.updated_at',
            'kode_status'       => 'assets_transfers.kode_status',
            'type'              => 'assets_transfers.type',
            'asset_code'        => 'a.asset_code',
            'asset_description' => 'a.description',
        ];
        $q->orderBy($sortColumns[$sortBy] ?? 'assets_transfers.created_at', $sortDir);

        // ALL data if no pagination params
        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();
            $data = $this->mapRows($rows, $tz);

            return response()->json([
                'data' => $data,
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'asset_uuid'   => $assetUuids->all(),
                        'q'            => $request->query('q'),
                        'type'         => $request->query('type'),
                        'from'         => $request->query('from'),
                        'to'           => $request->query('to'),
                        'with_trashed' => $request->query('with_trashed'),
                    ],
                ],
                'links' => ['first' => null, 'prev' => null, 'next' => null, 'last' => null],
            ]);
        }

        $perPage   = (int) ($v['per_page'] ?? 15);
        $paginator = $q->paginate($perPage)->appends($request->query());
        $data      = $this->mapRows($paginator->getCollection(), $tz);

        return response()->json([
            'data' => $data,
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
                    'asset_uuid'   => $assetUuids->all(),
                    'q'            => $request->query('q'),
                    'type'         => $request->query('type'),
                    'from'         => $request->query('from'),
                    'to'           => $request->query('to'),
                    'with_trashed' => $request->query('with_trashed'),
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

    public function show(string $uuid)
    {
        $tz = config('app.timezone', 'UTC');

        $row = Transfer::query()
            ->select([
                'assets_transfers.*',
                DB::raw('a.asset_code        as asset_code'),
                DB::raw('a.description       as asset_description'),
                DB::raw('a.kode_location     as asset_kode_location'),
                DB::raw('a.kode_asset_class  as asset_kode_asset_class'),
                DB::raw('a.kode_status       as asset_kode_status'),
                DB::raw('a.kode_sumber       as asset_kode_sumber'),
                DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS asset_kode_location_label"),
                DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name  END AS asset_kode_asset_class_label"),
                DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS asset_kode_status_label"),
                DB::raw("CASE WHEN msrc.name IS NULL THEN a.kode_sumber     ELSE a.kode_sumber     || ' - ' || msrc.name END AS asset_kode_sumber_label"),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_transfers.asset_uuid')
            ->leftJoin('master_location     as ml',   'ml.kode',   '=', 'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',  '=', 'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',   '=', 'a.kode_status')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode', '=', 'a.kode_sumber')
            ->with(['status:uuid,kode,name'])
            ->where('assets_transfers.uuid', $uuid)
            ->firstOrFail();

        $data = $this->mapRows(collect([$row]), $tz)->first();

        return response()->json(['data' => $data]);
    }

    protected function mapRows($rows, string $tz)
    {
        $codes = ['usercode' => [], 'status' => [], 'location' => []];

        foreach ($rows as $t) {
            $type   = (string) $t->type;
            $before = data_get($t->before, 'value');
            $after  = data_get($t->after,  'value');

            if (!$before && !$after) continue;

            switch ($type) {
                case 'owner':
                case 'user':
                case 'maintenance':
                    if ($before) $codes['usercode'][] = $before;
                    if ($after)  $codes['usercode'][] = $after;
                    break;
                case 'status':
                    if ($before) $codes['status'][] = $before;
                    if ($after)  $codes['status'][] = $after;
                    break;
                case 'location':
                    if ($before) $codes['location'][] = $before;
                    if ($after)  $codes['location'][] = $after;
                    break;
            }
        }

        $setUser   = collect($codes['usercode'])->filter()->unique();
        $setStatus = collect($codes['status'])->filter()->unique();
        $setLoc    = collect($codes['location'])->filter()->unique();

        $mapUser = $setUser->isNotEmpty()
            ? MasterUserCode::whereIn('kode', $setUser)->pluck('department', 'kode')->all()
            : [];

        $mapStatus = $setStatus->isNotEmpty()
            ? MasterStatus::whereIn('kode', $setStatus)
                ->where(fn($q) => $q->where('type', 'Asset')->orWhereNull('type'))
                ->pluck('name', 'kode')->all()
            : [];

        $mapLoc = $setLoc->isNotEmpty()
            ? MasterLocation::whereIn('kode', $setLoc)->pluck('name', 'kode')->all()
            : [];

        return $rows->map(function (Transfer $t) use ($mapUser, $mapStatus, $mapLoc, $tz) {
            $beforeCode = data_get($t->before, 'value');
            $afterCode  = data_get($t->after,  'value');

            $beforeDisplay = $this->labelFor($t->type, $beforeCode, $mapUser, $mapStatus, $mapLoc);
            $afterDisplay  = $this->labelFor($t->type, $afterCode,  $mapUser, $mapStatus, $mapLoc);

            $workflowLabel = $t->status
                ? ($t->kode_status . ' - ' . $t->status->name)
                : $t->kode_status;

            // asset bundle from joined columns
            $asset = [
                'asset_uuid'                    => $t->asset_uuid,
                'asset_code'                    => $t->getAttribute('asset_code'),
                'asset_description'             => $t->getAttribute('asset_description'),
                'asset_kode_location'           => $t->getAttribute('asset_kode_location'),
                'asset_kode_asset_class'        => $t->getAttribute('asset_kode_asset_class'),
                'asset_kode_status'             => $t->getAttribute('asset_kode_status'),
                'asset_kode_sumber'             => $t->getAttribute('asset_kode_sumber'),
                'asset_kode_location_label'     => $t->getAttribute('asset_kode_location_label'),
                'asset_kode_asset_class_label'  => $t->getAttribute('asset_kode_asset_class_label'),
                'asset_kode_status_label'       => $t->getAttribute('asset_kode_status_label'),
                'asset_kode_sumber_label'       => $t->getAttribute('asset_kode_sumber_label'),
            ];

            // file payload (local public disk) — same shape as Disposal
            $fileObj = null;
            if ($t->file_path) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($t->file_path);
                $size   = $t->file_size ?? ($exists ? $fs->size($t->file_path) : null);
                $mtime  = $t->file_updated_at ? strtotime($t->file_updated_at) : ($exists ? $fs->lastModified($t->file_path) : null);

                $fileObj = [
                    'name'          => $t->file_name ?: basename($t->file_path),
                    'mime'          => $t->file_mime,
                    'size'          => $size,
                    'sha256'        => $t->file_sha256 ?? null,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'download_url'  => route('files.download.kind', ['kind' => 'transfer', 'uuid' => $t->uuid]),
                    'file_url'      => $exists ? url('storage/' . ltrim($t->file_path, '/')) : null,
                ];
            }

            return [
                'uuid'           => $t->uuid,
                'transfer_code'  => $t->transfer_code,
                'type'           => $t->type,
                'kode_status'    => $t->kode_status,
                'workflow_label' => $workflowLabel,

                // before/after codes & labels
                'before_code'    => $beforeCode,
                'after_code'     => $afterCode,
                'before_display' => $beforeDisplay,
                'after_display'  => $afterDisplay,

                // asset & file
                'asset'          => $asset,
                'file'           => $fileObj,

                // timestamps
                'created_at'     => optional($t->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'     => optional($t->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'     => optional($t->deleted_at)->timezone($tz)?->toIso8601String(),
            ];
        });
    }

    protected function labelFor(string $type = null, ?string $code = null, array $mapUser = [], array $mapStatus = [], array $mapLoc = []): string
    {
        if (!$code) return '(empty)';

        return match ($type) {
            'owner', 'user', 'maintenance' => isset($mapUser[$code])   ? "{$code} - {$mapUser[$code]}"   : $code,
            'status'                       => isset($mapStatus[$code]) ? "{$code} - {$mapStatus[$code]}" : $code,
            'location'                     => isset($mapLoc[$code])    ? "{$code} - {$mapLoc[$code]}"    : $code,
            default                        => $code,
        };
    }
}
