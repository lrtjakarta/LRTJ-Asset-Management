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

    public function data(Request $request)
    {
        $year  = (int) ($request->input('year') ?: Carbon::now()->year);
        $month = $request->input('month');

        $movementAgg = $this->aggregateStatus(Transfer::query(), $year, $month, 'created_at');
        $disposalAgg = $this->aggregateStatus(Disposal::query(), $year, $month, 'created_at');
        $valueAgg    = $this->aggregateStatus(AssetDeprTransferRequest::query(), $year, $month, 'created_at');

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

    protected function aggregateStatus($query, int $year, $month = null, string $dateColumn = 'created_at')
    {
        $query->whereYear($dateColumn, $year);
        if ($month !== null && $month !== '') {
            $query->whereMonth($dateColumn, (int) $month);
        }

        return $query->selectRaw("
            SUM(CASE WHEN kode_status = 'ACC' THEN 1 ELSE 0 END) AS total,
            SUM(CASE WHEN kode_status = 'APR' THEN 1 ELSE 0 END) AS waiting,
            SUM(CASE WHEN kode_status = 'REJ' THEN 1 ELSE 0 END) AS rejected
        ")
            ->first();
    }

    public function acquisitionMonthly(Request $request)
    {
        $year       = (int) ($request->input('year') ?: Carbon::now()->year);
        $month      = $request->input('month');
        $owner      = $request->input('owner');
        $location   = $request->input('location');
        $assetClass = $request->input('asset_class');

        // --- base query for totals (NO year/month filter) ---
        $base = DB::table('assets_value as v')
            ->join('assets as a', 'a.uuid', '=', 'v.asset_uuid')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', '=', 'a.uuid')
            ->whereNull('v.deleted_at')
            ->whereNotNull('v.capitalization_date');

        if ($owner)      $base->where('g.asset_owner', $owner);
        if ($location)   $base->where('a.kode_location', $location);
        if ($assetClass) $base->where('a.kode_asset_class', $assetClass);

        $totals = (clone $base)
            ->selectRaw('COUNT(*) as total_qty')
            ->selectRaw('COALESCE(SUM(v.total), 0) as total_amount')
            ->first();

        $totals_per_yeaer = (clone $base)
            ->selectRaw('COUNT(*) as total_qty')
            ->selectRaw('COALESCE(SUM(v.total), 0) as total_amount')
            ->whereYear('v.capitalization_date', $year)
            ->first();

        $q = (clone $base)->whereYear('v.capitalization_date', $year);

        if ($month !== null && $month !== '') {
            $q->whereMonth('v.capitalization_date', (int) $month);
        }

        $rows = $q->selectRaw("DATE_TRUNC('month', v.capitalization_date)::date as month")
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
            'months'       => $months,
            'total_qty'    => (int) ($totals->total_qty ?? 0),
            'total_amount' => (float) ($totals->total_amount ?? 0),
            'total_qty_per_year'    => (int) ($totals_per_year->total_qty ?? 0),
            'total_amount_per_year' => (float) ($totals_per_year->total_amount ?? 0),
        ]);
    }

    public function deprMonthly(Request $request)
    {
        $year       = (int) ($request->input('year') ?: Carbon::now()->year);
        $month      = $request->input('month');
        $owner      = $request->input('owner');
        $location   = $request->input('location');
        $assetClass = $request->input('asset_class');

        $base = DB::table('assets_depr_ledger_monthly as l')
            ->join('assets as a', 'a.uuid', '=', 'l.asset_uuid')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', '=', 'a.uuid')
            ->whereYear('l.period', $year);

        if ($owner)      $base->where('g.asset_owner', $owner);
        if ($location)   $base->where('a.kode_location', $location);
        if ($assetClass) $base->where('a.kode_asset_class', $assetClass);

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

        $q = clone $base;
        if ($month !== null && $month !== '') {
            $q->whereMonth('l.period', (int) $month);
        }

        $rows = $q->selectRaw("date_trunc('month', l.period)::date AS month")
            ->selectRaw("SUM(l.accumulated_depr_end) AS depr")
            ->selectRaw("SUM(l.ending_balance) AS nbv")
            ->groupByRaw("date_trunc('month', l.period)")
            ->orderByRaw("date_trunc('month', l.period)")
            ->get();

        return response()->json([
            'months'     => $rows,
            'total_depr' => $totalDepr,
            'total_nbv'  => (float) (optional($rows->last())->nbv ?? 0),
        ]);
    }
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
            $key   = $r->owner_code ?: '-';
            $label = ($r->owner_code ?: '-') . ' - ' . ($r->owner_name ?: 'Unknown');

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
