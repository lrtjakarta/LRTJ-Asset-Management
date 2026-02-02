<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use NumberFormatter;

class AssetsApi extends Controller
{
    /**
     * Format to IDR currency string.
     * - Uses intl NumberFormatter if available
     * - Fallback: "Rp 1.234.567"
     */
    protected function formatIdr($value): string
    {
        $n = (float) ($value ?? 0);

        // Prefer intl if installed
        if (class_exists(NumberFormatter::class)) {
            $fmt = new NumberFormatter('id_ID', NumberFormatter::CURRENCY);
            $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
            $out = $fmt->formatCurrency($n, 'IDR');
            if (is_string($out) && $out !== '') return $out;
        }

        // Fallback (no intl)
        return 'Rp ' . number_format($n, 0, ',', '.');
    }

    public function index(Request $request)
    {
        // ---- Validate & normalize ----
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'asset_q'      => ['nullable', 'string', 'max:200'],
            'status'       => ['nullable', 'string', 'max:50'],
            'location'     => ['nullable', 'string', 'max:50'],
            'asset_class'  => ['nullable', 'string', 'max:50'],
            'owner'        => ['nullable', 'string', 'max:50'],
            'user'         => ['nullable', 'string', 'max:50'],
            'maintenance'  => ['nullable', 'string', 'max:50'],
            'sumber'       => ['nullable', 'string', 'max:50'],
            'transaction'  => ['nullable', 'string', 'max:50'],

            'uuid'         => ['nullable', 'uuid'],
            'uuids'        => ['nullable'],
            'uuids.*'      => ['uuid'],

            'price_min'    => ['nullable', 'numeric'],
            'price_max'    => ['nullable', 'numeric'],
            'total_min'    => ['nullable', 'numeric'],
            'total_max'    => ['nullable', 'numeric'],

            'updated_from' => ['nullable', 'date'],
            'updated_to'   => ['nullable', 'date'],

            'project_uuid' => ['nullable', 'uuid'],

            // sorting/paging
            'sort_by'      => ['nullable', 'in:a.updated_at,a.created_at,a.asset_code,a.description,a.kode_location,a.kode_asset_class,a.kode_status,v.price,v.total'],
            'sort_dir'     => ['nullable', 'in:asc,desc'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', 'in:0,1'],
        ])->validate();

        $withTrashed = $request->boolean('with_trashed');
        $sortBy  = $v['sort_by']  ?? 'a.updated_at';
        $sortDir = $v['sort_dir'] ?? 'desc';

        // normalize uuid / uuids
        $uuids = collect()
            ->merge(Arr::wrap($request->query('uuids')))
            ->when($request->filled('uuid'), fn($c) => $c->push($request->query('uuid')))
            ->flatMap(fn($val) => is_array($val) ? $val : (is_string($val) ? explode(',', $val) : []))
            ->map(fn($x) => trim((string) $x))
            ->filter(fn($x) => $x !== '' && Str::isUuid($x))
            ->unique()
            ->values();

        // ---- base query: mirrored from datatable() ----
        $q = DB::table('assets as a')
            // child tables
            ->leftJoin('assets_identifiers as i', 'i.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_assignment  as g', 'g.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_value       as v', 'v.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_document    as d', 'd.asset_uuid', '=', 'a.uuid')
            // master lookups (names)
            ->leftJoin('master_location     as ml',   'ml.kode',   '=', 'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',  '=', 'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',   '=', 'a.kode_status')
            ->leftJoin('master_uom          as mu',   'mu.kode',   '=', 'v.kode_uom')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode', '=', 'a.kode_sumber')
            ->leftJoin('master_user_code    as ou',   'ou.kode',   '=', 'g.asset_owner')
            ->leftJoin('master_user_code    as uu',   'uu.kode',   '=', 'g.asset_user')
            ->leftJoin('master_user_code    as muw',  'muw.kode',  '=', 'g.asset_maintenance')

            /**
             * IMPORTANT:
             * Match AssetsController@detail:
             * - pick latest monthly ledger row per asset (ORDER BY period DESC LIMIT 1)
             * Using ROW_NUMBER window to keep it fast in list.
             */
            ->leftJoin(DB::raw("(
                SELECT asset_uuid, period, ending_balance, accumulated_depr_end
                FROM (
                    SELECT
                        asset_uuid,
                        period,
                        ending_balance,
                        accumulated_depr_end,
                        ROW_NUMBER() OVER (PARTITION BY asset_uuid ORDER BY period DESC) AS rn
                    FROM assets_depr_ledger_monthly
                ) x
                WHERE x.rn = 1
            ) as ld"), 'ld.asset_uuid', '=', 'a.uuid')

            ->when(!$withTrashed, fn($qb) => $qb->whereNull('a.deleted_at'));

        // ---- search (q / asset_q) ----
        $q->when(
            !empty($v['asset_q'] ?? null) || !empty($v['q'] ?? null),
            function ($qb) use ($v) {
                $term = !empty($v['asset_q'] ?? null) ? $v['asset_q'] : $v['q'];
                $like = '%' . trim((string) $term) . '%';

                if (!empty($v['asset_q'] ?? null)) {
                    // focused asset search (like datatable)
                    $qb->where(function ($w) use ($like) {
                        $w->where('a.asset_code', 'ILIKE', $like)
                            ->orWhere('a.description', 'ILIKE', $like);
                    });
                } else {
                    // wide search
                    $qb->where(function ($w) use ($like) {
                        $w->where('a.asset_code', 'ILIKE', $like)
                            ->orWhere('a.description', 'ILIKE', $like)
                            ->orWhere('mac.name', 'ILIKE', $like)
                            ->orWhere('ms.name', 'ILIKE', $like)
                            ->orWhere('msrc.name', 'ILIKE', $like)
                            ->orWhere('ml.name', 'ILIKE', $like)
                            ->orWhere('mu.name', 'ILIKE', $like)
                            ->orWhere('ou.department', 'ILIKE', $like)
                            ->orWhere('uu.department', 'ILIKE', $like)
                            ->orWhere('muw.department', 'ILIKE', $like)
                            ->orWhere('i.asset_number_internal', 'ILIKE', $like)
                            ->orWhere('i.asset_number_maximo', 'ILIKE', $like)
                            ->orWhere('i.asset_number_dynamic_365', 'ILIKE', $like)
                            ->orWhere('a.kode_location', 'ILIKE', $like)
                            ->orWhere('a.kode_asset_class', 'ILIKE', $like)
                            ->orWhere('a.kode_status', 'ILIKE', $like)
                            ->orWhere('d.no_po_perjanjian_spk', 'ILIKE', $like)
                            ->orWhere('d.nota_referensi', 'ILIKE', $like)
                            ->orWhere('d.no_document', 'ILIKE', $like)
                            ->orWhere('g.asset_owner', 'ILIKE', $like)
                            ->orWhere('g.asset_user', 'ILIKE', $like)
                            ->orWhere('g.asset_maintenance', 'ILIKE', $like);
                    });
                }
            }
        );

        // ---- project filter (asset belongs to project) ----
        $projectUuid = $v['project_uuid'] ?? null;
        $q->when($projectUuid, function ($qb) use ($projectUuid) {
            $qb->whereExists(function ($sq) use ($projectUuid) {
                $sq->select(DB::raw(1))
                    ->from('asset_project_assets as apa')
                    ->whereColumn('apa.asset_uuid', 'a.uuid')
                    ->where('apa.project_uuid', $projectUuid);
            });
        });

        // ---- filters ----
        $q->when(!empty($v['asset_class']), fn($qb) => $qb->where('a.kode_asset_class', $v['asset_class']));
        $q->when(!empty($v['transaction']), fn($qb) => $qb->where('mac.kode_transaction', $v['transaction']));
        $q->when(!empty($v['location']),   fn($qb) => $qb->where('a.kode_location', $v['location']));
        $q->when(!empty($v['status']),     fn($qb) => $qb->where('a.kode_status', $v['status']));
        $q->when(!empty($v['owner']),      fn($qb) => $qb->where('g.asset_owner', $v['owner']));
        $q->when(!empty($v['user']),       fn($qb) => $qb->where('g.asset_user', $v['user']));
        $q->when(!empty($v['maintenance']), fn($qb) => $qb->where('g.asset_maintenance', $v['maintenance']));
        $q->when(!empty($v['sumber']),     fn($qb) => $qb->where('a.kode_sumber', $v['sumber']));

        // uuid / value / date range filters
        $q->when($uuids->isNotEmpty(), fn($qb) => $qb->whereIn('a.uuid', $uuids->all()));

        $q->when(isset($v['price_min']) || isset($v['price_max']), function ($qb) use ($v) {
            $min = $v['price_min'] ?? null;
            $max = $v['price_max'] ?? null;
            if ($min !== null) $qb->where('v.price', '>=', $min);
            if ($max !== null) $qb->where('v.price', '<=', $max);
        });

        $q->when(isset($v['total_min']) || isset($v['total_max']), function ($qb) use ($v) {
            $min = $v['total_min'] ?? null;
            $max = $v['total_max'] ?? null;
            if ($min !== null) $qb->where('v.total', '>=', $min);
            if ($max !== null) $qb->where('v.total', '<=', $max);
        });

        $q->when(!empty($v['updated_from']), fn($qb) => $qb->where('a.updated_at', '>=', $v['updated_from']));
        $q->when(!empty($v['updated_to']),   fn($qb) => $qb->where('a.updated_at', '<=', $v['updated_to']));

        // ---- SELECT ----
        $q->select(
            'a.uuid',
            'a.asset_code',
            'a.kode_group_category',
            'a.asset_number_parent',
            'a.asset_number_child',
            'a.description',
            'a.upload_code',

            'i.asset_number_maximo',
            'i.asset_number_dynamic_365',
            'i.asset_number_internal',
            'i.alias',

            'g.asset_owner',
            'g.asset_user',
            'g.asset_maintenance',

            'a.kode_location',
            'a.kode_asset_class',
            'a.kode_status',

            DB::raw('COALESCE(v.price, 0)::numeric(18,2) AS price'),
            DB::raw('COALESCE(v.quantity, 0)::numeric(18,0) AS quantity'),
            'v.vat_in',
            'v.kode_uom',
            DB::raw('COALESCE(v.total, 0)::numeric(18,2) AS total'),
            'v.useful_life_month',
            'v.useful_life_year',

            'd.no_po_perjanjian_spk',
            'd.nota_referensi',
            'd.no_document',

            'a.kode_sumber',
            'a.created_at',
            'a.deleted_at',

            DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS kode_location_label"),

            // acquisition date
            // 'v.actual_date as acquisition_date',
            DB::raw("TO_CHAR(v.actual_date, 'DD FMMonth YYYY') AS acquisition_date"),

            /**
             * Commercial fields (match AssetsController@detail):
             * - commercial_acq_cost = v.total
             * - commercial_accum_depr = latest accumulated_depr_end (ld)
             * - commercial_nbv = v.total - latest accumulated_depr_end
             *
             * Cast to numeric to keep consistent money-like numeric type.
             */
            DB::raw('COALESCE(v.total, 0)::numeric(18,2) AS commercial_acq_cost'),
            DB::raw('COALESCE(ld.accumulated_depr_end, 0)::numeric(18,2) AS commercial_accum_depr'),
            DB::raw('(COALESCE(v.total, 0) - COALESCE(ld.accumulated_depr_end, 0))::numeric(18,2) AS commercial_nbv'),

            // optional: you can expose period too (useful for UI)
            DB::raw('ld.period AS commercial_depr_period'),

            DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS kode_asset_class_label"),
            DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS kode_status_label"),
            DB::raw("CASE WHEN mu.name  IS NULL THEN v.kode_uom         ELSE v.kode_uom         || ' - ' || mu.name  END AS kode_uom_label"),
            DB::raw("CASE WHEN msrc.name IS NULL THEN a.kode_sumber     ELSE a.kode_sumber      || ' - ' || msrc.name END AS kode_sumber_label"),
            DB::raw("CASE WHEN ou.department  IS NULL THEN g.asset_owner      ELSE g.asset_owner         || ' - ' || ou.department  END AS asset_owner_label"),
            DB::raw("CASE WHEN uu.department  IS NULL THEN g.asset_user       ELSE g.asset_user          || ' - ' || uu.department  END AS asset_user_label"),
            DB::raw("CASE WHEN muw.department IS NULL THEN g.asset_maintenance ELSE g.asset_maintenance || ' - ' || muw.department END AS asset_maintenance_label"),
            DB::raw("to_char(a.updated_at at time zone 'Asia/Jakarta','YYYY-MM-DD HH24:MI') as updated_at_local"),
        )
            ->orderBy($sortBy, $sortDir);

        // Exclude disposed assets by default
        $q->where('a.kode_status', '!=', 'DIS');

        // ---- pagination wrapper ----
        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();

            $data = $rows->map(function ($r) {
                $r = (array) $r;

                // add formatted currency fields
                $r['commercial_acq_cost']   = $this->formatIdr($r['commercial_acq_cost'] ?? 0);
                $r['commercial_accum_depr'] = $this->formatIdr($r['commercial_accum_depr'] ?? 0);
                $r['commercial_nbv']        = $this->formatIdr($r['commercial_nbv'] ?? 0);
                $r['total']        = $this->formatIdr($r['total'] ?? 0);
                $r['price']        = $this->formatIdr($r['price'] ?? 0);

                return $r;
            });

            return response()->json([
                'data' => $data,
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q'            => $request->query('q'),
                        'asset_q'      => $request->query('asset_q'),
                        'status'       => $request->query('status'),
                        'location'     => $request->query('location'),
                        'asset_class'  => $request->query('asset_class'),
                        'owner'        => $request->query('owner'),
                        'user'         => $request->query('user'),
                        'maintenance'  => $request->query('maintenance'),
                        'sumber'       => $request->query('sumber'),
                        'transaction'  => $request->query('transaction'),
                        'with_trashed' => $request->query('with_trashed'),
                        'price_min'    => $request->query('price_min'),
                        'price_max'    => $request->query('price_max'),
                        'total_min'    => $request->query('total_min'),
                        'total_max'    => $request->query('total_max'),
                        'updated_from' => $request->query('updated_from'),
                        'updated_to'   => $request->query('updated_to'),
                        'project_uuid' => $request->query('project_uuid'),
                        'uuid'         => $request->query('uuid'),
                        'uuids'        => $uuids->all(),
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

        $perPage   = (int) ($v['per_page'] ?? 15);
        $paginator = $q->paginate($perPage)->appends($request->query());

        $data = $paginator->getCollection()->map(function ($r) {
            $r = (array) $r;

            // add formatted currency fields
            $r['commercial_acq_cost']   = $this->formatIdr($r['commercial_acq_cost'] ?? 0);
            $r['commercial_accum_depr'] = $this->formatIdr($r['commercial_accum_depr'] ?? 0);
            $r['commercial_nbv']        = $this->formatIdr($r['commercial_nbv'] ?? 0);
            $r['total']        = $this->formatIdr($r['total'] ?? 0);
            $r['price']        = $this->formatIdr($r['price'] ?? 0);

            return $r;
        });

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
                    'q'            => $request->query('q'),
                    'asset_q'      => $request->query('asset_q'),
                    'status'       => $request->query('status'),
                    'location'     => $request->query('location'),
                    'asset_class'  => $request->query('asset_class'),
                    'owner'        => $request->query('owner'),
                    'user'         => $request->query('user'),
                    'maintenance'  => $request->query('maintenance'),
                    'sumber'       => $request->query('sumber'),
                    'transaction'  => $request->query('transaction'),
                    'with_trashed' => $request->query('with_trashed'),
                    'price_min'    => $request->query('price_min'),
                    'price_max'    => $request->query('price_max'),
                    'total_min'    => $request->query('total_min'),
                    'total_max'    => $request->query('total_max'),
                    'updated_from' => $request->query('updated_from'),
                    'updated_to'   => $request->query('updated_to'),
                    'project_uuid' => $request->query('project_uuid'),
                    'uuid'         => $request->query('uuid'),
                    'uuids'        => $uuids->all(),
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
