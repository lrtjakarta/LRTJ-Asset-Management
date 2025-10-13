<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use Illuminate\Http\Request;

class MasterDataApi extends Controller
{
    /**
     * Query params:
     *  - q: search (e.g: kode/department/description)
     *  - status: 0|1 (optional)
     *  - sort_by: kode|department|created_at (default: created_at)
     *  - sort_dir: asc|desc (default: desc)
     *  - per_page: 1..100 (default: 15)
     *  - page: >=1 (default: 1)
     *  - with_trashed: 0|1 (optional)
     */


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
}
