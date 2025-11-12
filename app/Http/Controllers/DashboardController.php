<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\AssetDeprMonthly;
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
        $movementAgg = $this->aggregateStatus(Transfer::query());
        $disposalAgg = $this->aggregateStatus(Disposal::query());
        $valueAgg    = $this->aggregateStatus(AssetDeprTransferRequest::query());

        $movement = [
            'waiting'  => (int) ($movementAgg->waiting  ?? 0),
            'rejected' => (int) ($movementAgg->rejected ?? 0),
        ];

        $transferValue = [
            'waiting'  => (int) ($valueAgg->waiting  ?? 0),
            'rejected' => (int) ($valueAgg->rejected ?? 0),
        ];

        $disposal = [
            'waiting'  => (int) ($disposalAgg->waiting  ?? 0),
            'rejected' => (int) ($disposalAgg->rejected ?? 0),
        ];

        return response()->json([
            'movement'       => $movement,
            'transfer_value' => $transferValue,
            'disposal'       => $disposal,
        ]);
    }

    protected function aggregateStatus($query)
    {
        return $query
            ->selectRaw("
                SUM(CASE WHEN kode_status = 'APR' THEN 1 ELSE 0 END)          AS waiting,
                SUM(CASE WHEN kode_status = 'REJ' THEN 1 ELSE 0 END)          AS rejected
            ")
            ->first();
    }

    public function acquisitionMonthly()
    {
        $rows = DB::table('assets_value')
            ->whereNull('deleted_at')
            ->whereNotNull('capitalization_date')
            ->selectRaw("DATE_TRUNC('month', capitalization_date)::date as month")
            ->selectRaw("COUNT(*) as qty")
            ->selectRaw("SUM(total) as amount")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = $rows->map(function ($r) {
            return [
                'month'  => Carbon::parse($r->month)->toDateString(),
                'qty'    => (int) $r->qty,
                'amount' => (float) $r->amount,
            ];
        });

        return response()->json([
            'months'       => $months,
            'total_qty'    => $months->sum('qty'),
            'total_amount' => $months->sum('amount'),
        ]);
    }
    public function deprMonthly(Request $request)
    {
        $rows = DB::table('assets_depr_ledger_monthly')
            ->selectRaw("date_trunc('month', period)::date AS month")
            ->selectRaw("SUM(depr_expense + adjustment_depreciation) AS depr")
            ->selectRaw("SUM(ending_balance) AS nbv")
            ->groupByRaw("date_trunc('month', period)")
            ->orderByRaw("date_trunc('month', period)")
            ->get();

        $totalDepr = $rows->sum('depr');
        $latestNbv = optional($rows->last())->nbv ?? 0;

        return response()->json([
            'months'      => $rows,
            'total_depr'  => (float) $totalDepr,
            'total_nbv'   => (float) $latestNbv,
        ]);
    }
}
