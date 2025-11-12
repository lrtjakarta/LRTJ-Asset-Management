<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\AssetDeprMonthly;
use App\Models\AssetDeprTransferRequest;
use App\Models\Disposal;
use App\Models\Transfer;

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
}
