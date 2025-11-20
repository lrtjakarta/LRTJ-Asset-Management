<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterAssetClass;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterTransaction;
use App\Models\MasterUserCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MasterDataApi extends Controller
{
    public function master_transaction(Request $request)
    {
        // Validate & normalize query params
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'status'       => ['nullable', 'in:0,1'],
            'sort_by'      => ['nullable', 'in:kode,name,created_at,updated_at'],
            'sort_dir'     => ['nullable', 'in:asc,desc'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', 'in:0,1'],
        ])->validate();

        $q = MasterTransaction::query();

        if ($request->boolean('with_trashed')) {
            $q->withTrashed();
        }

        if (!empty($v['q'])) {
            $search = trim($v['q']);
            $q->where(function ($w) use ($search) {
                $w->where('kode', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%");
            });
        }

        if (isset($v['status'])) {
            $q->where('status', (bool) $v['status']);
        }

        $sortBy  = $v['sort_by']  ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $q->orderBy($sortBy, $sortDir);

        // ---- Unpaginated when no pagination params are provided ----
        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();

            return response()->json([
                'data' => $rows->map(fn($r) => [
                    'uuid'        => $r->uuid,
                    'kode'        => $r->kode,
                    'name'        => $r->name,
                    'status'      => (bool) $r->status,
                    'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
                ]),
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q' => $request->query('q'),
                        'status' => $request->query('status'),
                        'with_trashed' => $request->query('with_trashed'),
                    ],
                ],
                'links' => [
                    'first' => null,
                    'prev' => null,
                    'next' => null,
                    'last' => null,
                ],
            ]);
        }

        // ---- Paginated path (per_page/page present) ----
        $perPage   = $v['per_page'] ?? 15;
        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($r) => [
                'uuid'        => $r->uuid,
                'kode'        => $r->kode,
                'name'        => $r->name,
                'status'      => (bool) $r->status,
                'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
            ]),
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
                    'q' => $request->query('q'),
                    'status' => $request->query('status'),
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

    public function master_status(Request $request)
    {
        // Validate & normalize query params
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'status'       => ['nullable', 'in:0,1'],
            'type'         => ['nullable', 'string', 'max:50'],
            'sort_by'      => ['nullable', 'in:kode,name,type,created_at,updated_at'],
            'sort_dir'     => ['nullable', 'in:asc,desc'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', 'in:0,1'],
        ])->validate();

        $q = MasterStatus::query();

        if ($request->boolean('with_trashed')) {
            $q->withTrashed();
        }

        if (!empty($v['q'])) {
            $search = trim($v['q']);
            $q->where(function ($w) use ($search) {
                $w->where('kode', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%")
                    ->orWhere('type', 'ilike', "%{$search}%");
            });
        }

        if (isset($v['status'])) {
            $q->where('status', (bool) $v['status']);
        }

        $q->when(!empty($v['type']), fn($qb) => $qb->where('type', $v['type']));

        $sortBy  = $v['sort_by']  ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $q->orderBy($sortBy, $sortDir);

        // ---- Unpaginated when no pagination params are provided ----
        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();

            return response()->json([
                'data' => $rows->map(fn($r) => [
                    'uuid'        => $r->uuid,
                    'kode'        => $r->kode,
                    'name'        => $r->name,
                    'type'        => $r->type,
                    'status'      => (bool) $r->status,
                    'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
                ]),
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q' => $request->query('q'),
                        'status' => $request->query('status'),
                        'with_trashed' => $request->query('with_trashed'),
                    ],
                ],
                'links' => [
                    'first' => null,
                    'prev' => null,
                    'next' => null,
                    'last' => null,
                ],
            ]);
        }

        // ---- Paginated path (per_page/page present) ----
        $perPage   = $v['per_page'] ?? 15;
        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($r) => [
                'uuid'        => $r->uuid,
                'kode'        => $r->kode,
                'name'        => $r->name,
                'type'        => $r->type,
                'status'      => (bool) $r->status,
                'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
            ]),
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
                    'q' => $request->query('q'),
                    'status' => $request->query('status'),
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

    public function master_location(Request $request)
    {
        // Validate & normalize query params
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'status'       => ['nullable', 'in:0,1'],
            'sort_by'      => ['nullable', 'in:kode,name,created_at,updated_at'],
            'sort_dir'     => ['nullable', 'in:asc,desc'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', 'in:0,1'],
        ])->validate();

        $q = MasterLocation::query();

        if ($request->boolean('with_trashed')) {
            $q->withTrashed();
        }

        if (!empty($v['q'])) {
            $search = trim($v['q']);
            $q->where(function ($w) use ($search) {
                $w->where('kode', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%");
            });
        }

        if (isset($v['status'])) {
            $q->where('status', (bool) $v['status']);
        }

        $sortBy  = $v['sort_by']  ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $q->orderBy($sortBy, $sortDir);

        // ---- Unpaginated when no pagination params are provided ----
        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();

            return response()->json([
                'data' => $rows->map(fn($r) => [
                    'uuid'        => $r->uuid,
                    'kode'        => $r->kode,
                    'name'        => $r->name,
                    'status'      => (bool) $r->status,
                    'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
                ]),
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q' => $request->query('q'),
                        'status' => $request->query('status'),
                        'with_trashed' => $request->query('with_trashed'),
                    ],
                ],
                'links' => [
                    'first' => null,
                    'prev' => null,
                    'next' => null,
                    'last' => null,
                ],
            ]);
        }

        // ---- Paginated path (per_page/page present) ----
        $perPage   = $v['per_page'] ?? 15;
        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($r) => [
                'uuid'        => $r->uuid,
                'kode'        => $r->kode,
                'name'        => $r->name,
                'status'      => (bool) $r->status,
                'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
            ]),
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
                    'q' => $request->query('q'),
                    'status' => $request->query('status'),
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
    public function master_asset_class(Request $request)
    {
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'status'       => ['nullable', Rule::in(['0', '1'])],
            'sort_by'      => ['nullable', Rule::in([
                'kode',
                'name',
                'kode_transaction',
                'transaction_name',
                'created_at',
                'updated_at'
            ])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', Rule::in(['0', '1'])],
        ])->validate();

        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $tz      = config('app.timezone');

        $q = MasterAssetClass::query()
            ->select([
                'master_asset_class.uuid',
                'master_asset_class.kode',
                'master_asset_class.kode_transaction',
                'master_asset_class.name',
                'master_asset_class.status',
                'master_asset_class.created_at',
                'master_asset_class.updated_at',
                'master_asset_class.deleted_at',
                DB::raw('mt.name as transaction_name'),
                DB::raw("
                    CASE
                        WHEN mt.kode IS NULL THEN master_asset_class.kode_transaction
                        ELSE master_asset_class.kode_transaction || ' - ' || mt.name
                    END AS transaction_label
                "),
            ])
            ->leftJoin('master_transaction as mt', 'mt.kode', '=', 'master_asset_class.kode_transaction');

        if ($request->boolean('with_trashed')) {
            $q->withTrashed();
        }

        if (!empty($v['q'])) {
            $search = trim($v['q']);
            $q->where(function ($w) use ($search) {
                $w->where('master_asset_class.kode',             'ILIKE', "%{$search}%")
                    ->orWhere('master_asset_class.name',           'ILIKE', "%{$search}%")
                    ->orWhere('master_asset_class.kode_transaction', 'ILIKE', "%{$search}%")
                    ->orWhere('mt.name',                           'ILIKE', "%{$search}%");
            });
        }

        if (isset($v['status'])) {
            $q->where('master_asset_class.status', (bool) $v['status']);
        }

        $sortColumns = [
            'kode'              => 'master_asset_class.kode',
            'name'              => 'master_asset_class.name',
            'kode_transaction'  => 'master_asset_class.kode_transaction',
            'transaction_name'  => 'mt.name',
            'created_at'        => 'master_asset_class.created_at',
            'updated_at'        => 'master_asset_class.updated_at',
        ];
        $q->orderBy($sortColumns[$sortBy] ?? 'master_asset_class.created_at', $sortDir);

        $paginationRequested = $request->has('per_page') || $request->has('page');
        if (!$paginationRequested) {
            $rows = $q->get();

            return response()->json([
                'data' => $rows->map(fn($r) => [
                    'uuid'               => $r->uuid,
                    'kode'               => $r->kode,
                    'kode_transaction'   => $r->kode_transaction,
                    'transaction_name'   => $r->transaction_name,
                    'transaction_label'  => $r->transaction_label,
                    'name'               => $r->name,
                    'status'             => (bool) $r->status,
                    'created_at'         => optional($r->created_at)->timezone($tz)?->toIso8601String(),
                    'updated_at'         => optional($r->updated_at)->timezone($tz)?->toIso8601String(),
                    'deleted_at'         => optional($r->deleted_at)->timezone($tz)?->toIso8601String(),
                ]),
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q'            => $request->query('q'),
                        'status'       => $request->query('status'),
                        'with_trashed' => $request->query('with_trashed'),
                    ],
                ],
                'links' => ['first' => null, 'prev' => null, 'next' => null, 'last' => null],
            ]);
        }

        // ---- Paginated path ----
        $perPage   = (int) ($v['per_page'] ?? 15);
        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($r) => [
                'uuid'               => $r->uuid,
                'kode'               => $r->kode,
                'kode_transaction'   => $r->kode_transaction,
                'transaction_name'   => $r->transaction_name,
                'transaction_label'  => $r->transaction_label,
                'name'               => $r->name,
                'status'             => (bool) $r->status,
                'created_at'         => optional($r->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'         => optional($r->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'         => optional($r->deleted_at)->timezone($tz)?->toIso8601String(),
            ]),
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
                    'q'            => $request->query('q'),
                    'status'       => $request->query('status'),
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
    public function master_user_code(Request $request)
    {
        // Validate & normalize query params
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'status'       => ['nullable', 'in:0,1'],
            'sort_by'      => ['nullable', 'in:kode,department,created_at,updated_at'],
            'sort_dir'     => ['nullable', 'in:asc,desc'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', 'in:0,1'],
        ])->validate();

        $q = MasterUserCode::query();

        if ($request->boolean('with_trashed')) {
            $q->withTrashed();
        }

        if (!empty($v['q'])) {
            $search = trim($v['q']);
            $q->where(function ($w) use ($search) {
                $w->where('kode', 'ilike', "%{$search}%")
                    ->orWhere('department', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if (isset($v['status'])) {
            $q->where('status', (bool) $v['status']);
        }

        $sortBy  = $v['sort_by']  ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $q->orderBy($sortBy, $sortDir);

        // ---- Unpaginated when no pagination params are provided ----
        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();

            return response()->json([
                'data' => $rows->map(fn($r) => [
                    'uuid'        => $r->uuid,
                    'kode'        => $r->kode,
                    'department'  => $r->department,
                    'description' => $r->description,
                    'status'      => (bool) $r->status,
                    'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                    'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
                ]),
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q' => $request->query('q'),
                        'status' => $request->query('status'),
                        'with_trashed' => $request->query('with_trashed'),
                    ],
                ],
                'links' => [
                    'first' => null,
                    'prev' => null,
                    'next' => null,
                    'last' => null,
                ],
            ]);
        }

        // ---- Paginated path (per_page/page present) ----
        $perPage   = $v['per_page'] ?? 15;
        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($r) => [
                'uuid'        => $r->uuid,
                'kode'        => $r->kode,
                'department'  => $r->department,
                'description' => $r->description,
                'status'      => (bool) $r->status,
                'created_at'  => optional($r->created_at)->timezone(config('app.timezone'))->toIso8601String(),
                'updated_at'  => optional($r->updated_at)->timezone(config('app.timezone'))->toIso8601String(),
                'deleted_at'  => optional($r->deleted_at)->timezone(config('app.timezone'))?->toIso8601String(),
            ]),
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
                    'q' => $request->query('q'),
                    'status' => $request->query('status'),
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
    public function users(Request $request)
    {
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'status'       => ['nullable', Rule::in(['0', '1'])],
            'sort_by'      => ['nullable', Rule::in(['uid', 'name', 'email', 'created_at', 'updated_at'])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', Rule::in(['0', '1'])],
        ])->validate();

        $tz = config('app.timezone', 'UTC');

        $q = User::query();

        // If User uses SoftDeletes, you can enable this
        if ($request->boolean('with_trashed') && in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(User::class),
            true
        )) {
            $q->withTrashed();
        }

        if (!empty($v['q'])) {
            $search = trim($v['q']);
            $q->where(function ($w) use ($search) {
                $w->where('uid', 'ILIKE', "%{$search}%")
                    ->orWhere('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // Optional: if your users table has a boolean "status" column
        if (isset($v['status']) && Schema::hasColumn('users', 'status')) {
            $q->where('status', (bool) $v['status']);
        }

        $sortBy  = $v['sort_by']  ?? 'name';
        $sortDir = $v['sort_dir'] ?? 'asc';

        $sortColumns = [
            'uid'        => 'uid',
            'name'       => 'name',
            'email'      => 'email',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $q->orderBy($sortColumns[$sortBy] ?? 'name', $sortDir);

        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();

            return response()->json([
                'data' => $rows->map(function (User $r) use ($tz) {
                    return [
                        'id'          => $r->id,
                        'uid'         => $r->uid ?? null,
                        'name'        => $r->name ?? null,
                        'email'       => $r->email ?? null,
                        // convenience label for selectors (uid - name)
                        'label'       => trim(($r->uid ? $r->uid . ' - ' : '') . ($r->name ?? '')),

                        'status'      => Schema::hasColumn('users', 'status') ? (bool) $r->status : null,
                        'created_at'  => optional($r->created_at)->timezone($tz)?->toIso8601String(),
                        'updated_at'  => optional($r->updated_at)->timezone($tz)?->toIso8601String(),
                        'deleted_at'  => optional($r->deleted_at)->timezone($tz)?->toIso8601String(),
                    ];
                }),
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q'            => $request->query('q'),
                        'status'       => $request->query('status'),
                        'with_trashed' => $request->query('with_trashed'),
                    ],
                ],
                'links' => [
                    'first' => null,
                    'prev'  => null,
                    'next'  => null,
                    'last'  => null,
                ],
            ]);
        }

        // Paginated path
        $perPage   = (int) ($v['per_page'] ?? 15);
        $paginator = $q->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $paginator->getCollection()->map(function (User $r) use ($tz) {
                return [
                    'id'          => $r->id,
                    'uid'         => $r->uid ?? null,
                    'name'        => $r->name ?? null,
                    'email'       => $r->email ?? null,
                    'label'       => trim(($r->uid ? $r->uid . ' - ' : '') . ($r->name ?? '')),

                    'status'      => Schema::hasColumn('users', 'status') ? (bool) $r->status : null,
                    'created_at'  => optional($r->created_at)->timezone($tz)?->toIso8601String(),
                    'updated_at'  => optional($r->updated_at)->timezone($tz)?->toIso8601String(),
                    'deleted_at'  => optional($r->deleted_at)->timezone($tz)?->toIso8601String(),
                ];
            }),
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
                    'q'            => $request->query('q'),
                    'status'       => $request->query('status'),
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
}
