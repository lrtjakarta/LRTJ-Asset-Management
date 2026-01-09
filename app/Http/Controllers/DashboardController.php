<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AssetDeprTransferRequest;
use App\Models\Disposal;
use App\Models\Transfer;
use Carbon\Carbon;

class DashboardController
{
    public function dashboard()
    {
        return view('dashboard');
    }

    /**
     * Normalize date range from request.
     * Accept: from=YYYY-MM-DD, to=YYYY-MM-DD
     * Default: from = start of current year, to = today (end of day)
     */
    protected function getDateRange(Request $request): array
    {
        $yearCtx = $request->filled('from')
            ? Carbon::parse($request->input('from'))->year
            : now()->year;

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::create($yearCtx, 1, 1)->startOfDay();

        // Default "to" = end of that year (Dec 31 23:59:59)
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::create($yearCtx, 12, 31)->endOfDay();

        // swap if reversed
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }


    public function data(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $movementAgg = $this->aggregateStatus(Transfer::query(), $from, $to, 'created_at');
        $disposalAgg = $this->aggregateStatus(Disposal::query(), $from, $to, 'created_at');
        $valueAgg    = $this->aggregateStatus(AssetDeprTransferRequest::query(), $from, $to, 'created_at');

        return response()->json([
            'movement'       => [
                'waiting'  => (int) ($movementAgg->waiting  ?? 0),
                'rejected' => (int) ($movementAgg->rejected ?? 0),
                'total'    => (int) ($movementAgg->total    ?? 0),
            ],
            'transfer_value' => [
                'waiting'  => (int) ($valueAgg->waiting  ?? 0),
                'rejected' => (int) ($valueAgg->rejected ?? 0),
                'total'    => (int) ($valueAgg->total    ?? 0),
            ],
            'disposal'       => [
                'waiting'  => (int) ($disposalAgg->waiting  ?? 0),
                'rejected' => (int) ($disposalAgg->rejected ?? 0),
                'total'    => (int) ($disposalAgg->total    ?? 0),
            ],
        ]);
    }

    protected function aggregateStatus($query, Carbon $from, Carbon $to, string $dateColumn = 'created_at')
    {
        $query->whereBetween($dateColumn, [$from, $to]);

        return $query->selectRaw("
            SUM(CASE WHEN kode_status = 'ACC' THEN 1 ELSE 0 END) AS total,
            SUM(CASE WHEN kode_status = 'APR' THEN 1 ELSE 0 END) AS waiting,
            SUM(CASE WHEN kode_status = 'REJ' THEN 1 ELSE 0 END) AS rejected
        ")->first();
    }

    public function acquisitionMonthly(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $owner      = $request->input('owner');
        $location   = $request->input('location');
        $assetClass = $request->input('asset_class');

        // --- base query for totals (NO date filter) ---
        $base = DB::table('assets_value as v')
            ->join('assets as a', 'a.uuid', '=', 'v.asset_uuid')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', '=', 'a.uuid')
            ->whereNull('v.deleted_at')
            ->whereNotNull('v.capitalization_date');

        if ($owner)      $base->where('g.asset_owner', $owner);
        if ($location)   $base->where('a.kode_location', $location);
        if ($assetClass) $base->where('a.kode_asset_class', $assetClass);

        // total all-time (tetap)
        $totalsAll = (clone $base)
            ->selectRaw('COUNT(*) as total_qty')
            ->selectRaw('COALESCE(SUM(v.total), 0) as total_amount')
            ->first();

        // total for selected range
        $totalsRange = (clone $base)
            ->whereBetween('v.capitalization_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COUNT(*) as total_qty')
            ->selectRaw('COALESCE(SUM(v.total), 0) as total_amount')
            ->first();

        // chart rows (per month within range)
        $rows = (clone $base)
            ->whereBetween('v.capitalization_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("DATE_TRUNC('month', v.capitalization_date)::date as month")
            ->selectRaw("COUNT(*) as qty")
            ->selectRaw("COALESCE(SUM(v.total), 0) as amount")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = $rows->map(fn($r) => [
            'month'  => Carbon::parse($r->month)->toDateString(),
            'qty'    => (int) $r->qty,
            'amount' => (float) $r->amount,
        ]);

        return response()->json([
            'months'            => $months,

            'total_qty'         => (int) ($totalsAll->total_qty ?? 0),
            'total_amount'      => (float) ($totalsAll->total_amount ?? 0),

            'total_qty_range'   => (int) ($totalsRange->total_qty ?? 0),
            'total_amount_range' => (float) ($totalsRange->total_amount ?? 0),
        ]);
    }

    public function deprMonthly(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $owner      = $request->input('owner');
        $location   = $request->input('location');
        $assetClass = $request->input('asset_class');

        // yearCtx untuk "per-year" (pakai year dari from kalau ada)
        $yearCtx = $request->filled('from')
            ? Carbon::parse($request->input('from'))->year
            : now()->year;

        // range query (chart + total range)
        $fromPeriod = $from->copy()->startOfMonth()->startOfDay();
        $toPeriod   = $to->copy()->endOfMonth()->endOfDay();

        $base = DB::table('assets_depr_ledger_monthly as l')
            ->join('assets as a', 'a.uuid', '=', 'l.asset_uuid')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', '=', 'a.uuid')
            ->whereBetween('l.period', [$fromPeriod, $toPeriod]);

        if ($owner)      $base->where('g.asset_owner', $owner);
        if ($location)   $base->where('a.kode_location', $location);
        if ($assetClass) $base->where('a.kode_asset_class', $assetClass);

        // total in range = snapshot bulan terakhir dalam range
        $lastMonth = (clone $base)
            ->selectRaw("MAX(date_trunc('month', l.period)) AS last_month")
            ->value('last_month');

        $totalDepr = 0.0;
        if ($lastMonth) {
            $totalDepr = (float) ((clone $base)
                ->whereRaw("date_trunc('month', l.period) = ?", [$lastMonth])
                ->selectRaw("COALESCE(SUM(l.accumulated_depr_end), 0) AS total_depr")
                ->value('total_depr') ?? 0);
        }

        $rows = (clone $base)
            ->selectRaw("date_trunc('month', l.period)::date AS month")
            ->selectRaw("SUM(l.accumulated_depr_end) AS depr")
            ->selectRaw("SUM(l.ending_balance) AS nbv")
            ->groupByRaw("date_trunc('month', l.period)")
            ->orderByRaw("date_trunc('month', l.period)")
            ->get();

        $totalNbvRange = (float) (optional($rows->last())->nbv ?? 0);

        // ===== per-year totals (yearCtx full year) =====
        $yearFrom = Carbon::create($yearCtx, 1, 1)->startOfMonth()->startOfDay();
        $yearTo   = Carbon::create($yearCtx, 12, 31)->endOfMonth()->endOfDay();

        $baseYear = DB::table('assets_depr_ledger_monthly as l')
            ->join('assets as a', 'a.uuid', '=', 'l.asset_uuid')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', '=', 'a.uuid')
            ->whereBetween('l.period', [$yearFrom, $yearTo]);

        if ($owner)      $baseYear->where('g.asset_owner', $owner);
        if ($location)   $baseYear->where('a.kode_location', $location);
        if ($assetClass) $baseYear->where('a.kode_asset_class', $assetClass);

        $lastMonthYear = (clone $baseYear)
            ->selectRaw("MAX(date_trunc('month', l.period)) AS last_month")
            ->value('last_month');

        $totalDeprYear = 0.0;
        $totalNbvYear  = 0.0;

        if ($lastMonthYear) {
            $snap = (clone $baseYear)
                ->whereRaw("date_trunc('month', l.period) = ?", [$lastMonthYear])
                ->selectRaw("COALESCE(SUM(l.accumulated_depr_end), 0) AS total_depr")
                ->selectRaw("COALESCE(SUM(l.ending_balance), 0) AS total_nbv")
                ->first();

            $totalDeprYear = (float) ($snap->total_depr ?? 0);
            $totalNbvYear  = (float) ($snap->total_nbv ?? 0);
        }

        return response()->json([
            'months'            => $rows,

            'total_depr'        => $totalDepr,
            'total_nbv'         => $totalNbvRange,

            'year_ctx'          => $yearCtx,
            'total_depr_year'   => $totalDeprYear,
            'total_nbv_year'    => $totalNbvYear,
        ]);
    }

    // ownerStatus tetap (tidak pakai month/year). kalau mau ikut range juga, bilang.
    public function ownerStatus(Request $request)
    {
        $owner      = $request->input('owner');
        $location   = $request->input('location');
        $assetClass = $request->input('asset_class');

        $q = DB::table('assets as a')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', '=', 'a.uuid')
            ->leftJoin('master_user_code as ou', 'ou.kode', '=', 'g.asset_owner')
            ->join('assets_value as v', 'v.asset_uuid', '=', 'a.uuid')
            ->whereNull('a.deleted_at')
            ->whereIn('a.kode_status', ['DIS', 'IDL', 'OPE', 'RPR']);

        if ($owner)      $q->where('g.asset_owner', $owner);
        if ($location)   $q->where('a.kode_location', $location);
        if ($assetClass) $q->where('a.kode_asset_class', $assetClass);

        $rows = $q->selectRaw("
                COALESCE(g.asset_owner, '-')        AS owner_code,
                COALESCE(ou.department, 'Unknown')  AS owner_name,
                a.kode_status                       AS status,
                COUNT(*)                            AS n
            ")
            ->groupBy(
                DB::raw("COALESCE(g.asset_owner, '-')"),
                DB::raw("COALESCE(ou.department, 'Unknown')"),
                'a.kode_status'
            )
            ->get();

        $byOwner = [];
        foreach ($rows as $r) {
            $key = $r->owner_code ?: '-';
            if (!isset($byOwner[$key])) {
                $byOwner[$key] = ['owner' => $key, 'dis' => 0, 'idl' => 0, 'ope' => 0, 'rpr' => 0];
            }
            $byOwner[$key][strtolower($r->status)] += (int) $r->n;
        }

        return response()->json([
            'data' => array_values(collect($byOwner)->sortBy('owner', SORT_NATURAL | SORT_FLAG_CASE)->all())
        ]);
    }
}
