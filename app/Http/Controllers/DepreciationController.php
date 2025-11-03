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
    private const TRANSFER_TYPE_CARRY_OVER = 'carry_over_gross_accum';

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
                    NULLIF(p.useful_life_months, 0),
                    NULLIF(av.useful_life_month, 0),
                    CASE WHEN av.useful_life_year IS NOT NULL
                         THEN (av.useful_life_year * 12)::int
                         ELSE 0 END,
                    0
                ) AS useful_life_months
            "),
                'y.ending_balance_year as ending_balance_prev_year',
                DB::raw("
                GREATEST(
                  COALESCE(
                    (
                      -- total life in months
                      COALESCE(
                        NULLIF(p.useful_life_months, 0),
                        NULLIF(av.useful_life_month, 0),
                        CASE WHEN av.useful_life_year IS NOT NULL
                             THEN (av.useful_life_year * 12)::int
                             ELSE 0 END,
                        0
                      )
                      -
                      (
                        CASE
                          -- compute eligible start: month after cutoff window
                          WHEN p.depr_start_date IS NULL THEN 0
                          ELSE
                            CASE
                              WHEN date_trunc('month', assets_depr_ledger_monthly.period) <
                                (
                                  CASE
                                    WHEN EXTRACT(DAY FROM p.depr_start_date) <= COALESCE(p.cutoff_day,15)
                                      THEN (date_trunc('month', p.depr_start_date) + INTERVAL '1 month')::date
                                    ELSE (date_trunc('month', p.depr_start_date) + INTERVAL '2 month')::date
                                  END
                                )
                              THEN 0
                              ELSE
                                -- INCLUSIVE elapsed months from eligibleStart to period: diff + 1
                                (
                                  (DATE_PART('year', AGE(
                                      date_trunc('month', assets_depr_ledger_monthly.period),
                                      CASE
                                        WHEN EXTRACT(DAY FROM p.depr_start_date) <= COALESCE(p.cutoff_day,15)
                                          THEN (date_trunc('month', p.depr_start_date) + INTERVAL '1 month')::date
                                        ELSE (date_trunc('month', p.depr_start_date) + INTERVAL '2 month')::date
                                      END
                                  ))::int * 12)
                                  +
                                  DATE_PART('month', AGE(
                                      date_trunc('month', assets_depr_ledger_monthly.period),
                                      CASE
                                        WHEN EXTRACT(DAY FROM p.depr_start_date) <= COALESCE(p.cutoff_day,15)
                                          THEN (date_trunc('month', p.depr_start_date) + INTERVAL '1 month')::date
                                        ELSE (date_trunc('month', p.depr_start_date) + INTERVAL '2 month')::date
                                      END
                                  ))::int
                                  + 1
                                )
                            END
                        END
                      )
                    ),
                    0
                  ),
                0) AS remaining_useful_life_months
            "),
            ]);

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
                fn($qq) => $qq->where('fiscal_year', (int)$r->year)
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

            $assets = DB::table('assets')
                ->join('assets_value AS av', 'av.asset_uuid', '=', 'assets.uuid')
                ->leftJoin('assets_depr_policy AS p', 'p.asset_uuid', '=', 'assets.uuid')
                ->select(
                    'assets.uuid AS asset_uuid',
                    'av.capitalization_date',
                    'av.actual_date',
                    'av.created_at AS av_created_at',
                    'av.useful_life_month',
                    'av.useful_life_year',
                    'p.asset_uuid AS has_policy'
                )
                ->get();

            foreach ($assets as $row) {
                if ($row->has_policy) continue;

                $start = $row->capitalization_date
                    ?: ($row->actual_date ?: $row->av_created_at);
                if (!$start) continue;

                $life = (int)($row->useful_life_month ?? 0);
                if ($life <= 0 && isset($row->useful_life_year)) {
                    $life = (int) round(((float)$row->useful_life_year) * 12);
                }
                if ($life <= 0) $life = 60;

                AssetDeprPolicy::create([
                    'asset_uuid'         => $row->asset_uuid,
                    'method'             => AssetDeprPolicy::METHOD_SL,
                    'useful_life_months' => $life,
                    'salvage_value'      => 0,
                    'depr_start_date'    => Carbon::parse($start)->toDateString(),
                    'convention'         => AssetDeprPolicy::CONVENTION_PRORATA_MONTH,
                    'cutoff_day'         => 15,
                    'start_rule'         => 'CUT_OFF_NEXT_OR_NEXT2',
                    'is_active'          => true,
                ]);
            }

            $policies = AssetDeprPolicy::with('asset')->where('is_active', true)->get();

            foreach ($policies as $policy) {
                $assetUuid = $policy->asset_uuid;

                $av = DB::table('assets_value')
                    ->select('capitalization_date', 'actual_date', 'created_at', 'total')
                    ->where('asset_uuid', $assetUuid)
                    ->first();

                $capDate  = $av?->capitalization_date ?? $av?->actual_date ?? $av?->created_at;
                $capTotal = (float)($av?->total ?? 0);

                $prev = AssetDeprMonthly::where('asset_uuid', $assetUuid)
                    ->whereDate('period', '<', $period)
                    ->orderBy('period', 'desc')
                    ->first();

                $opening = (float) ($prev->ending_balance ?? $capTotal);
                $accPrev = (float) ($prev->accumulated_depr_end ?? 0.0);

                $additions = 0.0;
                $tin       = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)->whereDate('period', $period)->where('category', AssetDeprMovement::TRANSFER_IN)->sum('amount');
                $tout      = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)->whereDate('period', $period)->where('category', AssetDeprMovement::TRANSFER_OUT)->sum('amount');
                $disposals = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)->whereDate('period', $period)->where('category', AssetDeprMovement::DISPOSAL)->sum('amount');
                $adjValue  = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)->whereDate('period', $period)->where('category', AssetDeprMovement::ADJUSTMENT_VALUE)->sum('amount');
                $adjDepr   = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)->whereDate('period', $period)->where('category', AssetDeprMovement::ADJUSTMENT_DEPRECIATION)->sum('amount');

                $startBase     = $policy->depr_start_date ?: ($capDate ? Carbon::parse($capDate)->toDateString() : null);
                $eligibleStart = $startBase
                    ? Carbon::parse($startBase)->startOfMonth()->addMonths(
                        (Carbon::parse($startBase)->day <= ($policy->cutoff_day ?? 15)) ? 1 : 2
                    )
                    : $period;

                $eligible  = $eligibleStart->lte($period);
                $lifeMonths = (int) ($policy->useful_life_months ?: 60);
                if ($lifeMonths <= 0) $lifeMonths = 60;

                $elapsed   = $eligible ? $eligibleStart->diffInMonths($period) : 0;
                $remaining = $eligible ? max(0, $lifeMonths - $elapsed) : 0;

                $deprBase    = $eligible ? max(($opening + $adjValue + $tin - $tout - $disposals) - (float)$policy->salvage_value, 0.0) : 0.0;
                $deprExpense = ($eligible && $remaining > 0) ? floor($deprBase / $remaining) : 0.0;

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
            'asset_uuid'  => ['required', 'uuid'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'actual_date' => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:300'],
            'source_type' => ['nullable', 'string', 'max:64'],
            'source_uuid' => ['nullable', 'uuid'],
            'group_uuid'  => ['nullable', 'uuid'],
        ]);

        return $this->writeMovementWithCutoff($data, AssetDeprMovement::ADDITION);
    }

    public function recordTransfer(Request $r)
    {
        $data = $r->validate([
            'transfer_type'   => ['nullable', 'in:tf-val,' . self::TRANSFER_TYPE_CARRY_OVER],
            'from_asset_uuid' => ['required', 'uuid'],
            'to_asset_uuid'   => ['required', 'uuid', 'different:from_asset_uuid'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'actual_date'     => ['required', 'date'],
            'note'            => ['nullable', 'string', 'max:300'],
            'source_type'     => ['nullable', 'string', 'max:64'],
            'source_uuid'     => ['nullable', 'uuid'],
            'group_uuid'      => ['nullable', 'uuid'],
        ]);

        $type   = $data['transfer_type'] ?? 'tf-val';
        $period = Carbon::parse($data['actual_date'])->startOfMonth();
        $group  = $data['group_uuid'] ?? Str::uuid();

        $fromPolicy = AssetDeprPolicy::where('asset_uuid', $data['from_asset_uuid'])->where('is_active', true)->first();
        $toPolicy   = AssetDeprPolicy::where('asset_uuid', $data['to_asset_uuid'])->where('is_active', true)->first();
        $fromStart  = $this->calcDeprStartPeriod($data['actual_date'], $fromPolicy?->cutoff_day ?? 15);
        $toStart    = $this->calcDeprStartPeriod($data['actual_date'], $toPolicy?->cutoff_day ?? 15);

        if ($type === self::TRANSFER_TYPE_CARRY_OVER) {
            $info = $this->computeCarryOver($data['from_asset_uuid'], Carbon::parse($data['actual_date']), (float)$data['amount']);
            if ((float)$data['amount'] > $info['cap_remaining']) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Amount exceeds gross remaining. Total: %s, Lifetime transferred: %s, Remaining: %s.',
                        number_format($info['total_gross'], 2),
                        number_format($info['total_gross'] - $info['cap_remaining'], 2),
                        number_format($info['cap_remaining'], 2)
                    )
                ]);
            }

            DB::transaction(function () use ($data, $period, $group, $fromStart, $toStart, $info) {
                AssetDeprMovement::create([
                    'asset_uuid'        => $data['from_asset_uuid'],
                    'period'            => $period,
                    'category'          => AssetDeprMovement::TRANSFER_OUT,
                    'amount'            => $data['amount'],
                    'depr_start_period' => $fromStart,
                    'group_uuid'        => $group,
                    'source_type'       => $data['source_type'] ?? 'manual',
                    'source_uuid'       => $data['source_uuid'] ?? null,
                    'note'              => trim(($data['note'] ?? '') . ' | carry-over gross out'),
                ]);
                AssetDeprMovement::create([
                    'asset_uuid'        => $data['from_asset_uuid'],
                    'period'            => $period,
                    'category'          => AssetDeprMovement::ADJUSTMENT_DEPRECIATION,
                    'amount'            => -$info['acc_move'],
                    'depr_start_period' => $fromStart,
                    'group_uuid'        => $group,
                    'source_type'       => $data['source_type'] ?? 'manual',
                    'source_uuid'       => $data['source_uuid'] ?? null,
                    'note'              => trim(($data['note'] ?? '') . ' | carry-over accum -FROM'),
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
                    'note'              => trim(($data['note'] ?? '') . ' | carry-over gross in'),
                ]);
                AssetDeprMovement::create([
                    'asset_uuid'        => $data['to_asset_uuid'],
                    'period'            => $period,
                    'category'          => AssetDeprMovement::ADJUSTMENT_DEPRECIATION,
                    'amount'            => +$info['acc_move'],
                    'depr_start_period' => $toStart,
                    'group_uuid'        => $group,
                    'source_type'       => $data['source_type'] ?? 'manual',
                    'source_uuid'       => $data['source_uuid'] ?? null,
                    'note'              => trim(($data['note'] ?? '') . ' | carry-over accum +TO'),
                ]);
            });

            return response()->json([
                'ok' => true,
                'message' => 'Carry-Over transfer recorded',
                'group_uuid' => (string)$group
            ]);
        }

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

        return response()->json(['ok' => true, 'message' => 'Transfer recorded', 'group_uuid' => (string)$group]);
    }

    public function recordDisposal(Request $r)
    {
        $data = $r->validate([
            'asset_uuid'  => ['required', 'uuid'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'actual_date' => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:300'],
            'source_type' => ['nullable', 'string', 'max:64'],
            'source_uuid' => ['nullable', 'uuid'],
            'group_uuid'  => ['nullable', 'uuid'],
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
            'asset_uuid'  => ['required', 'uuid'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'actual_date' => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:300'],
        ]);

        return $this->writeMovementWithCutoff($data, AssetDeprMovement::ADJUSTMENT_VALUE);
    }

    public function recordAdjustmentDepreciation(Request $r)
    {
        $data = $r->validate([
            'asset_uuid'  => ['required', 'uuid'],
            'amount'      => ['required', 'numeric'],
            'actual_date' => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:300'],
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

    private function calcDeprStartPeriod(string|\DateTimeInterface $startDate, ?int $cutoffDay = 15): string
    {
        $cutoffDay = $cutoffDay ?: 15;
        $d = Carbon::parse($startDate);
        $addMonths = ($d->day <= $cutoffDay) ? 1 : 2;
        return $d->copy()->startOfMonth()->addMonths($addMonths)->toDateString();
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

        $lastNbv    = $lastRow ? (float) $lastRow->ending_balance : 0.0;
        $lastPeriod = $lastRow?->period;

        $nbvClamped = min($lastNbv, $total);

        $max = max(0.0, $total - $nbvClamped);

        $alreadyOut = (float) AssetDeprMovement::query()
            ->where('asset_uuid', $assetUuid)
            ->where('category', AssetDeprMovement::TRANSFER_OUT)
            ->whereDate('period', $monthStart)
            ->sum('amount');

        $remaining = max(0.0, $max - $alreadyOut);

        return [
            'begin_total'        => (float) $total,
            'last_closed_period' => $lastPeriod,
            'last_nbv'           => (float) $lastNbv,
            'max'                => (float) $max,
            'already_out'        => (float) $alreadyOut,
            'remaining'          => (float) $remaining,
        ];
    }

    public function carryOverPreview(Request $r)
    {
        $data = $r->validate([
            'from_asset_uuid' => ['required', 'uuid'],
            'actual_date'     => ['required', 'date'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
        ]);

        $info = $this->computeCarryOver(
            $data['from_asset_uuid'],
            Carbon::parse($data['actual_date']),
            (float)$data['amount']
        );

        return response()->json(['ok' => true] + $info);
    }

    private function lastClosedRowBeforeMonth(string $assetUuid, Carbon $monthStart): ?AssetDeprMonthly
    {
        return AssetDeprMonthly::where('asset_uuid', $assetUuid)
            ->whereDate('period', '<', $monthStart)
            ->orderBy('period', 'desc')
            ->first();
    }

    private function grossRemaining(string $assetUuid): float
    {
        $total = (float) DB::table('assets_value')->where('asset_uuid', $assetUuid)->value('total');
        $outAll = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)
            ->where('category', AssetDeprMovement::TRANSFER_OUT)
            ->sum('amount');
        return max(0.0, $total - $outAll);
    }

    private function computeCarryOver(string $fromUuid, Carbon $actualDate, float $amount): array
    {
        $monthStart = $actualDate->copy()->startOfMonth();

        $totalGross = (float) DB::table('assets_value')->where('asset_uuid', $fromUuid)->value('total');

        $last = $this->lastClosedRowBeforeMonth($fromUuid, $monthStart);
        $accAsOfLast = (float) ($last->accumulated_depr_end ?? 0);

        $ratio   = ($totalGross > 0) ? ($amount / $totalGross) : 0.0;
        $accMove = round($accAsOfLast * $ratio, 2);
        $nbvMove = max(0.0, $amount - $accMove);

        $capRemaining = $this->grossRemaining($fromUuid);

        return [
            'total_gross'        => $totalGross,
            'accum_as_of_last'   => $accAsOfLast,
            'ratio'              => $ratio,
            'acc_move'           => $accMove,
            'nbv_move'           => $nbvMove,
            'cap_remaining'      => $capRemaining,
            'last_closed_period' => optional($last)->period,
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
    private function deriveStartDateFromAV(object $av): ?string
    {
        $d = $av->capitalization_date ?? $av->actual_date ?? $av->in_service_date ?? $av->created_at ?? null;
        return $d ? Carbon::parse($d)->toDateString() : null;
    }

    private function deriveLifeMonthsFromAV(object $av): int
    {
        $life = (int)($av->useful_life_month ?? 0);
        if ($life <= 0 && isset($av->useful_life_months)) $life = (int)$av->useful_life_months;
        if ($life <= 0 && isset($av->useful_life_year))   $life = (int) round(((float)$av->useful_life_year) * 12);
        return $life > 0 ? $life : 60;
    }
}
