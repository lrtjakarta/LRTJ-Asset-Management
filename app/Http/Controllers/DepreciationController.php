<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\{
    Assets,
    AssetDeprPolicy,
    AssetDeprMovement,
    AssetDeprMonthly,
    AssetDeprYearly
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class DepreciationController extends Controller
{
    public function index()
    {
        return view('depreciation.depreciation');
    }


    public function dtPolicies(Request $r)
    {
        $q = AssetDeprPolicy::query()
            ->with('asset:uuid,asset_code,description')
            ->when(
                $r->asset_q,
                fn($qq) =>
                $qq->whereHas('asset', function ($qa) use ($r) {
                    $s = trim($r->asset_q);
                    $qa->where('asset_code', 'ilike', "%{$s}%")
                        ->orWhere('description', 'ilike', "%{$s}%");
                })
            );

        return DataTables::eloquent($q)
            ->addColumn('asset_code', fn($row) => $row->asset?->asset_code)
            ->addColumn('asset_name', fn($row) => $row->asset?->description)
            ->toJson();
    }

    public function dtMonthly(Request $r)
    {
        $period = $r->period
            ? Carbon::parse($r->period)->startOfMonth()->toDateString()
            : now()->startOfMonth()->toDateString();

        $periodYear = (int)Carbon::parse($period)->year;
        $prevYear   = $periodYear - 1;

        $q = AssetDeprMonthly::query()
            ->with('asset:uuid,asset_code,description')
            ->leftJoin('assets_value as av', 'av.asset_uuid', '=', 'assets_depr_ledger_monthly.asset_uuid')
            ->leftJoin('assets_depr_policy as p', 'p.asset_uuid', '=', 'assets_depr_ledger_monthly.asset_uuid')
            ->leftJoin('assets_depr_yearly as y', function ($j) use ($prevYear) {
                $j->on('y.asset_uuid', '=', 'assets_depr_ledger_monthly.asset_uuid')
                    ->where('y.fiscal_year', '=', $prevYear);
            })
            ->whereDate('assets_depr_ledger_monthly.period', $period)
            ->select([
                'assets_depr_ledger_monthly.uuid',
                'assets_depr_ledger_monthly.asset_uuid',
                'assets_depr_ledger_monthly.period',
                'assets_depr_ledger_monthly.opening_balance',
                'assets_depr_ledger_monthly.additions',
                'assets_depr_ledger_monthly.transfers_in',
                'assets_depr_ledger_monthly.transfers_out',
                'assets_depr_ledger_monthly.disposals',
                'assets_depr_ledger_monthly.adjustment_value',
                'assets_depr_ledger_monthly.adjustment_depreciation',
                'assets_depr_ledger_monthly.depr_expense',
                'assets_depr_ledger_monthly.accumulated_depr_end',
                'assets_depr_ledger_monthly.ending_balance',
                'av.capitalization_date as cap_date',

                'av.total as total_value',
                DB::raw("
                COALESCE(
                    NULLIF(av.useful_life_month, 0)
                ) as useful_life_months
            "),

                'y.ending_balance_year as ending_balance_prev_year',
                DB::raw("
                GREATEST(
                    COALESCE(
                        (
                            -- total life in months (same expression as above)
                            COALESCE(
                                NULLIF(p.useful_life_months, 0),
                                NULLIF(av.useful_life_month, 0),
                                CASE WHEN av.useful_life_year IS NOT NULL THEN (av.useful_life_year * 12)::int ELSE NULL END,
                                0
                            )
                            -
                            -- elapsed months from FIRST ELIGIBLE PERIOD (cutoff applied) up to the requested period
                            GREATEST(
                                (
                                    DATE_PART('year', AGE(
                                        date_trunc('month', assets_depr_ledger_monthly.period),
                                        CASE
                                          WHEN p.depr_start_date IS NULL THEN NULL
                                          WHEN EXTRACT(DAY FROM p.depr_start_date) <= COALESCE(p.cutoff_day,15)
                                            THEN (date_trunc('month', p.depr_start_date) + INTERVAL '1 month')::date
                                          ELSE (date_trunc('month', p.depr_start_date) + INTERVAL '2 month')::date
                                        END
                                    ))::int * 12
                                    +
                                    DATE_PART('month', AGE(
                                        date_trunc('month', assets_depr_ledger_monthly.period),
                                        CASE
                                          WHEN p.depr_start_date IS NULL THEN NULL
                                          WHEN EXTRACT(DAY FROM p.depr_start_date) <= COALESCE(p.cutoff_day,15)
                                            THEN (date_trunc('month', p.depr_start_date) + INTERVAL '1 month')::date
                                          ELSE (date_trunc('month', p.depr_start_date) + INTERVAL '2 month')::date
                                        END
                                    ))::int
                                ),
                                0
                            )
                        ),
                        0
                    ),
                0) AS remaining_useful_life_months
            "),
            ])
            ->when($r->asset_q, function ($qq) use ($r) {
                $s = trim($r->asset_q);
                $qq->whereHas('asset', function ($qa) use ($s) {
                    $qa->where('asset_code', 'ilike', "%{$s}%")
                        ->orWhere('description', 'ilike', "%{$s}%");
                });
            });

        return DataTables::of($q)
            ->addColumn('asset_code', fn($row) => $row->asset?->asset_code)
            ->addColumn('asset_name', fn($row) => $row->asset?->description)
            ->addColumn('asset_uuid', fn($row) => $row->asset_uuid)
            ->toJson();
    }

    public function dtYearly(Request $r)
    {
        $q = AssetDeprYearly::query()
            ->with('asset:uuid,asset_code,description')
            ->when(
                $r->year,
                fn($qq) =>
                $qq->where('fiscal_year', (int)$r->year)
            )
            ->when(
                $r->asset_q,
                fn($qq) =>
                $qq->whereHas('asset', function ($qa) use ($r) {
                    $s = trim($r->asset_q);
                    $qa->where('asset_code', 'ilike', "%{$s}%")
                        ->orWhere('description', 'ilike', "%{$s}%");
                })
            );

        return DataTables::eloquent($q)
            ->addColumn('asset_code', fn($row) => $row->asset?->asset_code)
            ->addColumn('asset_name', fn($row) => $row->asset?->description)
            ->toJson();
    }


    public function runMonth(Request $r)
    {
        $r->validate(['period' => ['required', 'date']]);
        $period = Carbon::parse($r->period)->startOfMonth();

        DB::transaction(function () use ($period) {
            $missing = [];
            $assets = DB::table('assets')
                ->select('assets.uuid as asset_uuid', 'assets.asset_code')
                ->join('assets_value AS av', 'av.asset_uuid', '=', 'assets.uuid')
                ->leftJoin('assets_depr_policy AS p', 'p.asset_uuid', '=', 'assets.uuid')
                ->whereNull('p.asset_uuid')
                ->get();

            foreach ($assets as $a) {
                $av = DB::table('assets_value')->where('asset_uuid', $a->asset_uuid)->first();
                if (!$av) continue;
                $deprStart =
                    ($av->capitalization_date ?? null)
                    ?: ($av->actual_date ?? null)
                    ?: ($av->in_service_date ?? null)
                    ?: ($av->created_at ?? null);

                if (!$deprStart) {
                    $missing[] = $a->asset_code ?: $a->asset_uuid;
                    continue;
                }

                $lifeMonths = (int)($av->useful_life_month ?? 0);
                if ($lifeMonths <= 0 && $av->useful_life_year) {
                    $lifeMonths = (int)round(((float)$av->useful_life_year) * 12);
                }
                if ($lifeMonths <= 0) $lifeMonths = 60;

                AssetDeprPolicy::create([
                    'asset_uuid'         => $a->asset_uuid,
                    'method'             => AssetDeprPolicy::METHOD_SL,
                    'useful_life_months' => $lifeMonths,
                    'salvage_value'      => 0,
                    'depr_start_date'    => Carbon::parse($deprStart)->toDateString(),
                    'convention'         => AssetDeprPolicy::CONVENTION_PRORATA_MONTH,
                    'cutoff_day'         => 15,
                    'start_rule'         => 'CUT_OFF_NEXT_OR_NEXT2',
                    'is_active'          => true,
                ]);
            }

            $policies = AssetDeprPolicy::with('asset')->where('is_active', true)->get();

            foreach ($policies as $policy) {
                $assetUuid = $policy->asset_uuid;

                $av = DB::table('assets_value')->where('asset_uuid', $assetUuid)->first();
                $capDate = $av?->capitalization_date ?? $av?->actual_date;
                $capTotal = (float)($av?->total ?? 0);

                $prev = AssetDeprMonthly::where('asset_uuid', $assetUuid)
                    ->whereDate('period', $period->copy()->subMonth()->startOfMonth())
                    ->first();

                $opening = (float) ($prev->ending_balance ?? 0.0);
                $accPrev = (float) ($prev->accumulated_depr_end ?? 0.0);

                if (!$prev && $capDate) {
                    $cap = Carbon::parse($capDate)->endOfMonth();
                    if ($cap->gte($period->copy()->startOfMonth())) {
                        $opening = $capTotal;
                    } else {
                        $opening = $capTotal;
                    }
                }

                $additions = $tin = $tout = $disposals = $adjValue = $adjDepr = 0.0;

                $startPeriod = $this->calcDeprStartPeriod($policy->depr_start_date, $policy->cutoff_day ?? 15);
                $startPeriodCarbon = Carbon::parse($startPeriod)->startOfMonth();

                $eligibleThisMonth = $startPeriodCarbon->lte($period);

                $elapsed = $eligibleThisMonth ? max(0, $this->monthsElapsed($policy->depr_start_date, $period, $policy->convention)) : 0;
                $remainingMonths = $eligibleThisMonth ? max(0, ((int)$policy->useful_life_months) - $elapsed) : 0;

                $deprBase = $eligibleThisMonth
                    ? max(($opening + $adjValue + $tin - $tout - $disposals) - (float)$policy->salvage_value, 0.0)
                    : 0.0;

                $deprExpense = ($eligibleThisMonth && $remainingMonths > 0) ? floor($deprBase / $remainingMonths) : 0.0;

                $ending = $opening + $additions + $tin - $tout - $disposals + $adjValue - $deprExpense + $adjDepr;
                $accEnd = $accPrev + $deprExpense + $adjDepr;

                AssetDeprMonthly::updateOrCreate(
                    ['asset_uuid' => $assetUuid, 'period' => $period->toDateString()],
                    [
                        'opening_balance'          => $opening,
                        'additions'                => $additions,
                        'transfers_in'             => $tin,
                        'transfers_out'            => $tout,
                        'disposals'                => $disposals,
                        'adjustment_value'         => $adjValue,
                        'adjustment_depreciation'  => $adjDepr,
                        'depr_expense'             => $deprExpense,
                        'accumulated_depr_end'     => $accEnd,
                        'ending_balance'           => $ending,
                    ]
                );
            }
        });

        return response()->json(['ok' => true, 'message' => "Depreciation processed for {$period->toDateString()}"]);
    }


    public function buildYear(Request $r)
    {
        $r->validate(['year' => ['required', 'integer', 'min:1900', 'max:3000']]);
        $year = (int) $r->year;

        DB::transaction(function () use ($year) {
            $rows = AssetDeprMonthly::query()
                ->selectRaw("
                    asset_uuid,
                    SUM(CASE WHEN EXTRACT(MONTH FROM period)=1 THEN opening_balance ELSE 0 END) AS opening_balance,
                    SUM(additions + transfers_in - transfers_out - disposals + adjustment_value) AS total_additions,
                    SUM(depr_expense) AS depr_expense_year,
                    SUM(adjustment_depreciation) AS adjustment_depreciation_year,
                    MAX(accumulated_depr_end) AS accumulated_depr_end,
                    MAX(ending_balance) AS ending_balance_year
                ")
                ->whereYear('period', $year)
                ->groupBy('asset_uuid')
                ->get();

            foreach ($rows as $r) {
                AssetDeprYearly::updateOrCreate(
                    ['asset_uuid' => $r->asset_uuid, 'fiscal_year' => $year],
                    [
                        'opening_balance'              => (float)$r->opening_balance,
                        'total_additions'              => (float)$r->total_additions,
                        'depr_expense_year'            => (float)$r->depr_expense_year,
                        'adjustment_depreciation_year' => (float)$r->adjustment_depreciation_year,
                        'accumulated_depr_end'         => (float)$r->accumulated_depr_end,
                        'ending_balance_year'          => (float)$r->ending_balance_year,
                    ]
                );
            }
        });

        return response()->json(['ok' => true, 'message' => "Yearly summary built for {$year}"]);
    }

    public function recordAddition(Request $r)
    {
        $data = $r->validate([
            'asset_uuid' => ['required', 'uuid'],
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'actual_date' => ['required', 'date'],
            'note'       => ['nullable', 'string', 'max:300'],
            'source_type' => ['nullable', 'string', 'max:64'],
            'source_uuid' => ['nullable', 'uuid'],
            'group_uuid' => ['nullable', 'uuid'],
        ]);

        return $this->writeMovementWithCutoff($data, AssetDeprMovement::ADDITION);
    }

    public function recordTransfer(Request $r)
    {
        $data = $r->validate([
            'from_asset_uuid' => ['required', 'uuid'],
            'to_asset_uuid'   => ['required', 'uuid', 'different:from_asset_uuid'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'actual_date'     => ['required', 'date'],
            'note'            => ['nullable', 'string', 'max:300'],
            'source_type'     => ['nullable', 'string', 'max:64'],
            'source_uuid'     => ['nullable', 'uuid'],
            'group_uuid'      => ['nullable', 'uuid'],
        ]);

        $period = Carbon::parse($data['actual_date'])->startOfMonth();
        $group  = $data['group_uuid'] ?? Str::uuid();

        $cap = $this->computeMaxTransfer($data['from_asset_uuid'], Carbon::parse($data['actual_date']));
        if ((float)$data['amount'] > $cap['remaining']) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Amount exceeds max allowed for %s. Beginning Value: %s, Last NBV (%s): %s, Max: %s, Already transferred this month: %s, Remaining: %s.',
                    Carbon::parse($data['actual_date'])->toDateString(),
                    number_format($cap['begin_total'], 2),
                    optional($cap['last_closed_period'])->format('Y-m-d') ?? '-',
                    number_format($cap['last_nbv'], 2),
                    number_format($cap['max'], 2),
                    number_format($cap['already_out'], 2),
                    number_format($cap['remaining'], 2),
                )
            ]);
        }

        $fromPolicy = AssetDeprPolicy::where('asset_uuid', $data['from_asset_uuid'])->where('is_active', true)->first();
        $toPolicy   = AssetDeprPolicy::where('asset_uuid', $data['to_asset_uuid'])->where('is_active', true)->first();

        $fromStart = $this->calcDeprStartPeriod($data['actual_date'], $fromPolicy?->cutoff_day ?? 15);
        $toStart   = $this->calcDeprStartPeriod($data['actual_date'], $toPolicy?->cutoff_day ?? 15);

        DB::transaction(function () use ($data, $period, $group, $fromStart, $toStart) {
            AssetDeprMovement::create([
                'asset_uuid'        => $data['from_asset_uuid'],
                'period'            => $period,
                'category'          => AssetDeprMovement::TRANSFER_OUT,
                'amount'            => $data['amount'],
                'depr_start_period' => $fromStart,
                'group_uuid'        => $group,
                'source_type'       => $data['source_type'] ?? 'manual',
                'source_uuid'       => $data['source_uuid'] ?? null,
                'note'              => $data['note'] ?? null,
            ]);

            AssetDeprMovement::create([
                'asset_uuid'        => $data['to_asset_uuid'],
                'period'            => $period,
                'category'          => AssetDeprMovement::TRANSFER_IN,
                'amount'            => $data['amount'],
                'depr_start_period' => $toStart,
                'group_uuid'        => $group,
                'source_type'       => $data['source_type'] ?? 'manual',
                'source_uuid'       => $data['source_uuid'] ?? null,
                'note'              => $data['note'] ?? null,
            ]);
        });

        return response()->json(['ok' => true, 'message' => 'Transfer recorded', 'group_uuid' => (string)$group, 'cap' => $cap]);
    }

    public function recordDisposal(Request $r)
    {
        $data = $r->validate([
            'asset_uuid' => ['required', 'uuid'],
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'actual_date' => ['required', 'date'],
            'note'       => ['nullable', 'string', 'max:300'],
            'source_type' => ['nullable', 'string', 'max:64'],
            'source_uuid' => ['nullable', 'uuid'],
            'group_uuid' => ['nullable', 'uuid'],
        ]);

        $period = Carbon::parse($data['actual_date'])->startOfMonth();

        AssetDeprMovement::create([
            'asset_uuid'        => $data['asset_uuid'],
            'period'            => $period,
            'category'          => AssetDeprMovement::DISPOSAL,
            'amount'            => $data['amount'],
            'depr_start_period' => $period,
            'group_uuid'        => $data['group_uuid'] ?? null,
            'source_type'       => $data['source_type'] ?? 'manual',
            'source_uuid'       => $data['source_uuid'] ?? null,
            'note'              => $data['note'] ?? null,
        ]);

        return response()->json(['ok' => true, 'message' => 'Disposal recorded']);
    }

    public function recordAdjustmentValue(Request $r)
    {
        $data = $r->validate([
            'asset_uuid' => ['required', 'uuid'],
            'amount'     => ['required', 'numeric', 'min:0.01'],
            'actual_date' => ['required', 'date'],
            'note'       => ['nullable', 'string', 'max:300'],
        ]);

        return $this->writeMovementWithCutoff($data, AssetDeprMovement::ADJUSTMENT_VALUE);
    }

    public function recordAdjustmentDepreciation(Request $r)
    {
        $data = $r->validate([
            'asset_uuid' => ['required', 'uuid'],
            'amount'     => ['required', 'numeric'],
            'actual_date' => ['required', 'date'],
            'note'       => ['nullable', 'string', 'max:300'],
        ]);

        $period = Carbon::parse($data['actual_date'])->startOfMonth();

        AssetDeprMovement::create([
            'asset_uuid'        => $data['asset_uuid'],
            'period'            => $period,
            'category'          => AssetDeprMovement::ADJUSTMENT_DEPRECIATION,
            'amount'            => abs($data['amount']),
            'depr_start_period' => $period,
            'source_type'       => 'manual',
            'note'              => $data['note'] ?? null,
        ]);

        return response()->json(['ok' => true, 'message' => 'Depreciation adjustment recorded']);
    }


    private function writeMovementWithCutoff(array $data, string $category)
    {
        $period = Carbon::parse($data['actual_date'])->startOfMonth();
        $policy = AssetDeprPolicy::where('asset_uuid', $data['asset_uuid'])->where('is_active', true)->first();

        $deprStart = $this->calcDeprStartPeriod($data['actual_date'], $policy?->cutoff_day ?? 15);

        AssetDeprMovement::create([
            'asset_uuid'        => $data['asset_uuid'],
            'period'            => $period,
            'category'          => $category,
            'amount'            => $data['amount'],
            'depr_start_period' => $deprStart,
            'source_type'       => $data['source_type'] ?? 'manual',
            'source_uuid'       => $data['source_uuid'] ?? null,
            'group_uuid'        => $data['group_uuid'] ?? null,
            'note'              => $data['note'] ?? null,
        ]);

        return response()->json(['ok' => true, 'message' => "{$category} recorded"]);
    }

    private function calcDeprStartPeriod(string|\DateTimeInterface $actualDate, int $cutoffDay): string
    {
        $d = Carbon::parse($actualDate);
        $monthStart = $d->copy()->startOfMonth();
        if ((int)$d->format('d') <= $cutoffDay) {
            return $monthStart->copy()->addMonth()->toDateString();
        }
        return $monthStart->copy()->addMonths(2)->toDateString();
    }

    private function monthsElapsed(string|\DateTimeInterface $startDate, Carbon $period, string $convention): int
    {
        $start = Carbon::parse($startDate)->startOfMonth();
        return max(0, $start->diffInMonths($period));
    }
    private function computeMaxTransfer(string $assetUuid, Carbon $actualDate): array
    {
        $monthStart = $actualDate->copy()->startOfMonth();

        $total = (float) DB::table('assets_value')
            ->where('asset_uuid', $assetUuid)
            ->value('total');

        $lastRow = AssetDeprMonthly::query()
            ->where('asset_uuid', $assetUuid)
            ->whereDate('period', '<', $monthStart)
            ->orderBy('period', 'desc')
            ->first();

        $lastNbv  = $lastRow ? (float)$lastRow->ending_balance : $total;
        $lastPeriod = $lastRow?->period;

        $max = max(0.0, $total - $lastNbv);

        $alreadyOut = (float) AssetDeprMovement::query()
            ->where('asset_uuid', $assetUuid)
            ->where('category', AssetDeprMovement::TRANSFER_OUT)
            ->whereDate('period', $monthStart)
            ->sum('amount');

        $remaining = max(0.0, $max - $alreadyOut);

        return [
            'begin_total'        => $total,
            'last_closed_period' => $lastPeriod,
            'last_nbv'           => $lastNbv,
            'max'                => $max,
            'already_out'        => $alreadyOut,
            'remaining'          => $remaining,
        ];
    }
    public function transferLimit(Request $r)
    {
        $data = $r->validate([
            'from_asset_uuid' => ['required', 'uuid'],
            'actual_date'     => ['required', 'date'],
        ]);

        $cap = $this->computeMaxTransfer($data['from_asset_uuid'], Carbon::parse($data['actual_date']));
        return response()->json(['ok' => true] + $cap);
    }
    public function assetSearch(Request $r)
    {
        $q = trim($r->get('q', ''));
        $rows = Assets::query()
            ->select('uuid', 'asset_code', 'description')
            ->when($q, fn($qq) => $qq->where('asset_code', 'ilike', "%{$q}%")
                ->orWhere('description', 'ilike', "%{$q}%"))
            ->orderBy('asset_code')
            ->limit(20)
            ->get();
        return response()->json(['data' => $rows]);
    }
}
