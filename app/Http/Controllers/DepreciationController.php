<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\{
    Assets,
    AssetDeprPolicy,
    AssetDeprMovement,
    AssetDeprMonthly,
    AssetDeprYearly,
    AssetDeprTransferRequest,
    MasterStatus
};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\AssetDeprMonthClosing;
use App\Models\AssetDeprYearClosing;

class DepreciationController extends Controller
{
    private const TRANSFER_TYPE_CARRY_OVER = 'carry_over_gross_accum';
    private const TRANSFER_TYPE_ACQ_FIX    = 'acq_fix';
    private const STATUS_APR = 'APR';
    private const STATUS_ACC = 'ACC';
    private const STATUS_REJ = 'REJ';
    /**
     * Guard semua operasi yang menulis ledger untuk suatu period.
     *
     * @param Carbon $period   startOfMonth
     * @param string $context  e.g. 'runMonth', 'adjDepr', 'approveTransfer'
     * @param array $opt       options:
     *   - require_prev_month: bool (default true)
     *   - enforce_first_run_min_eligible: bool (default true)
     *   - allow_when_no_closing: bool (default false) -> jika true, skip NO_CLOSING_YET check (jarang dipakai)
     *
     * @return array { year:int, period:string }
     * @throws \RuntimeException with coded message:
     *   YEAR_LOCKED|{year}|{context}
     *   PREV_YEAR_NOT_BUILT|{prev}|{year}|{context}
     *   PREV_MONTH_NOT_PROCESSED|{needPeriod}
     *   FIRST_RUN_MUST_START_AT_MIN_ELIGIBLE|{needPeriod}
     *   NO_CLOSING_YET|{context}
     */
    private function guardWritablePeriod(Carbon $period, string $context, array $opt = []): array
    {
        $period = $period->copy()->startOfMonth();
        $year   = (int) $period->year;

        $requirePrevMonth   = (bool) ($opt['require_prev_month'] ?? true);
        $enforceFirstRunMin = (bool) ($opt['enforce_first_run_min_eligible'] ?? true);

        // 1) year locked
        if ($this->isYearLocked($year)) {
            throw new \RuntimeException("YEAR_LOCKED|{$year}|{$context}");
        }

        // 2) chain gate prev-year (skip kalau prev-year kosong)
        $this->assertPrevYearBuiltUnlessEmpty($year, $context);

        // 3) prev-month gate / first-run gate
        $hasAnyClosing = AssetDeprMonthClosing::query()->limit(1)->exists();

        if (! $hasAnyClosing) {
            if ($enforceFirstRunMin) {
                $minEligible = DB::table('assets as a')
                    ->join('assets_value as av', 'av.asset_uuid', '=', 'a.uuid')
                    ->leftJoin('assets_depr_policy as p', 'p.asset_uuid', '=', 'a.uuid')
                    ->where('a.kode_status', '!=', 'DIS')
                    ->whereNotNull('av.capitalization_date')
                    ->selectRaw("
                    MIN(
                        CASE
                            WHEN EXTRACT(DAY FROM av.capitalization_date) <= COALESCE(p.cutoff_day, 15)
                                THEN (date_trunc('month', av.capitalization_date) + INTERVAL '0 month')::date
                            ELSE (date_trunc('month', av.capitalization_date) + INTERVAL '1 month')::date
                        END
                    ) as min_eligible
                ")
                    ->value('min_eligible');

                if ($minEligible) {
                    $first = Carbon::parse($minEligible)->startOfMonth()->toDateString();
                    if ($period->toDateString() !== $first) {
                        throw new \RuntimeException("FIRST_RUN_MUST_START_AT_MIN_ELIGIBLE|{$first}");
                    }
                }
            }

            return ['year' => $year, 'period' => $period->toDateString()];
        }

        if ($requirePrevMonth) {
            $this->ensurePrevMonthProcessed($period); // <-- sudah fixed (skip kalau prev-month kosong)
        }

        return ['year' => $year, 'period' => $period->toDateString()];
    }

    private function renderGuardError(\Throwable $e)
    {
        $msg = (string) $e->getMessage();

        if (str_starts_with($msg, 'YEAR_LOCKED|')) {
            [$_, $year] = array_pad(explode('|', $msg), 3, null);
            $year = (int) $year;

            return response()->json([
                'ok' => false,
                'code' => 'YEAR_LOCKED',
                'message' => "Year {$year} already built & locked. Rollback Build Year to reopen.",
                'year' => $year,
            ], 422);
        }

        if (str_starts_with($msg, 'PREV_YEAR_NOT_BUILT|')) {
            [$_, $needPrev, $targetYear] = array_pad(explode('|', $msg), 4, null);

            return response()->json([
                'ok' => false,
                'code' => 'PREV_YEAR_NOT_BUILT',
                'message' => "Year {$targetYear} cannot be processed before Build Year {$needPrev}.",
                'need_build_year' => (int) $needPrev,
                'target_year' => (int) $targetYear,
            ], 422);
        }

        if (str_starts_with($msg, 'PREV_MONTH_NOT_PROCESSED|')) {
            $need = explode('|', $msg)[1] ?? null;

            return response()->json([
                'ok' => false,
                'code' => 'PREV_MONTH_NOT_PROCESSED',
                'message' => 'Previous month depreciation must be processed first.',
                'need_period' => $need,
                'need_period_text' => $need ? Carbon::parse($need)->isoFormat('MMMM YYYY') : null,
            ], 422);
        }

        if (str_starts_with($msg, 'FIRST_RUN_MUST_START_AT_MIN_ELIGIBLE|')) {
            $need = explode('|', $msg)[1] ?? null;

            return response()->json([
                'ok' => false,
                'code' => 'FIRST_RUN_MUST_START_AT_MIN_ELIGIBLE',
                'message' => 'Data bulan ini belum tersedia. Silakan proses periode eligible pertama dulu.',
                'need_period' => $need,
                'need_period_text' => $need ? Carbon::parse($need)->isoFormat('MMMM YYYY') : null,
            ], 422);
        }

        return null;
    }

    private function monthHasEligibleAssets(Carbon $monthStart): bool
    {
        $m = $monthStart->copy()->startOfMonth()->toDateString(); // YYYY-MM-01

        // eligible_start <= monthStart => berarti bulan ini ada aset yang "sudah mulai eligible"
        return DB::table('assets as a')
            ->join('assets_value as av', 'av.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_depr_policy as p', 'p.asset_uuid', '=', 'a.uuid')
            ->where('a.kode_status', '!=', 'DIS')
            ->whereNotNull('av.capitalization_date')
            ->whereRaw("
            (
                CASE
                    WHEN EXTRACT(DAY FROM COALESCE(p.depr_start_date, av.capitalization_date)) <= COALESCE(p.cutoff_day, 15)
                        THEN date_trunc('month', COALESCE(p.depr_start_date, av.capitalization_date))::date
                    ELSE (date_trunc('month', COALESCE(p.depr_start_date, av.capitalization_date)) + INTERVAL '1 month')::date
                END
            ) <= ?::date
        ", [$m])
            ->limit(1)
            ->exists();
    }

    protected function canReadDepr(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAction('DEPRECIATION', 'R');
    }
    private function isYearLocked(int $year): bool
    {
        return AssetDeprYearClosing::query()
            ->where('fiscal_year', $year)
            ->where('is_locked', true)
            ->exists();
    }

    private function assertYearOpen(int $year, string $context = 'operation'): void
    {
        if ($this->isYearLocked($year)) {
            throw new \RuntimeException("YEAR_LOCKED|{$year}|{$context}");
        }
    }

    private function assertBuildYearAllowedNow(): void
    {
        // Build Year hanya boleh bulan Desember
        if ((int) now()->month !== 12) {
            throw new \RuntimeException("BUILD_YEAR_ONLY_DECEMBER");
        }
    }

    private function assertDecemberProcessed(int $year): void
    {
        // Pastikan Desember tahun itu sudah diproses (biar build year valid)
        $dec = Carbon::create($year, 12, 1)->startOfMonth()->toDateString();

        $ok = AssetDeprMonthClosing::query()
            ->whereDate('period', $dec)
            ->exists();

        if (! $ok) {
            throw new \RuntimeException("DECEMBER_NOT_PROCESSED|{$dec}");
        }
    }

    private function yearHasEligiblePeriods(int $year): bool
    {
        $start = Carbon::create($year, 1, 1)->startOfMonth()->toDateString();   // YYYY-01-01
        $end   = Carbon::create($year, 12, 1)->startOfMonth()->toDateString();  // YYYY-12-01

        // Eligible start month mengikuti rule cutoff & depr_start_date/policy
        return DB::table('assets as a')
            ->join('assets_value as av', 'av.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_depr_policy as p', 'p.asset_uuid', '=', 'a.uuid')
            ->where('a.kode_status', '!=', 'DIS')
            ->whereNotNull('av.capitalization_date')
            ->whereRaw("
            (
                CASE
                    WHEN EXTRACT(DAY FROM COALESCE(p.depr_start_date, av.capitalization_date)) <= COALESCE(p.cutoff_day, 15)
                        THEN date_trunc('month', COALESCE(p.depr_start_date, av.capitalization_date))::date
                    ELSE (date_trunc('month', COALESCE(p.depr_start_date, av.capitalization_date)) + INTERVAL '1 month')::date
                END
            ) BETWEEN ?::date AND ?::date
        ", [$start, $end])
            ->limit(1)
            ->exists();
    }
    private function isYearBuiltLocked(int $year): bool
    {
        return AssetDeprYearClosing::query()
            ->where('fiscal_year', $year)
            ->where('is_locked', true)
            ->exists();
    }

    private function assertPrevYearBuiltUnlessEmpty(int $year, string $context = 'operation'): void
    {
        $prev = $year - 1;
        if ($prev < 1900) return;

        if (! $this->yearHasEligiblePeriods($prev)) {
            return;
        }

        if (! $this->isYearBuiltLocked($prev)) {
            throw new \RuntimeException("PREV_YEAR_NOT_BUILT|{$prev}|{$year}|{$context}");
        }
    }

    private function canBuildYearNowForTarget(int $targetYear): bool
    {
        $now = now();
        $nowYear = (int) $now->year;
        $nowMonth = (int) $now->month;

        // Window build:
        // - Desember tahun target (misal Des 2026 build 2026)
        // - Januari tahun berikutnya (misal Jan 2027 build 2026)
        if ($nowMonth === 12 && $nowYear === $targetYear) return true;
        if ($nowMonth === 1 && $nowYear === ($targetYear + 1)) return true;

        return false;
    }

    private function assertBuildYearAllowedForTarget(int $targetYear): void
    {
        if (! $this->canBuildYearNowForTarget($targetYear)) {
            throw new \RuntimeException("BUILD_YEAR_WINDOW_CLOSED|{$targetYear}");
        }
    }

    /**
     * Rollback tidak boleh loncat:
     * - hanya boleh rollback "tahun yang tepat sebelum tahun sekarang" (nowYear-1)
     * - dan harus jadi locked year paling terakhir (max fiscal_year locked)
     */
    private function assertSequentialRollback(int $year): void
    {
        $nowYear = (int) now()->year;
        $expected = $nowYear - 1;

        if ($year !== $expected) {
            throw new \RuntimeException("ROLLBACK_ONLY_PREV_YEAR|{$expected}");
        }

        $maxLocked = AssetDeprYearClosing::query()
            ->where('is_locked', true)
            ->max('fiscal_year');

        if ((int)$maxLocked !== $year) {
            throw new \RuntimeException("ROLLBACK_MUST_BE_LATEST_LOCKED|{$maxLocked}");
        }
    }

    public function index()
    {
        return view('depreciation.depreciation');
    }

    public function periodIndex(Request $r)
    {
        abort_unless($this->canReadDepr(), 403);

        // ambil range tahun dari capitalization_date yang benar-benar ada
        $range = DB::table('assets_value as av')
            ->join('assets as a', 'a.uuid', '=', 'av.asset_uuid')
            ->where('a.kode_status', '!=', 'DIS')
            ->whereNotNull('av.capitalization_date')
            ->selectRaw('MIN(EXTRACT(YEAR FROM av.capitalization_date))::int as min_year')
            ->selectRaw('MAX(EXTRACT(YEAR FROM av.capitalization_date))::int as max_year')
            ->first();

        $minYear = (int)($range->min_year ?? now()->year);
        $maxYear = (int)($range->max_year ?? now()->year);

        // year yang dipilih user, fallback ke year terbaru yang ada data (atau current year)
        $year = (int) ($r->input('year') ?: $maxYear);

        // clamp biar ga keluar range
        if ($year < $minYear) $year = $minYear;
        if ($year > $maxYear) $year = $maxYear;

        $isEmpty = !DB::table('assets_depr_month_closings')->limit(1)->exists()
            && !DB::table('assets_depr_ledger_monthly')->limit(1)->exists();

        return view('depreciation.period', [
            'year' => $year,
            'minYear' => $minYear,
            'maxYear' => $maxYear,
            'isLedgerEmpty' => $isEmpty,
            'isDecember' => now()->month === 12,
            'nowYear' => now()->year,
        ]);
    }

    public function dtPeriod(Request $r)
    {
        if (! $this->canReadDepr()) {
            return DataTables::of(collect())->toJson();
        }

        $year = (int) ($r->input('year') ?: now()->year);

        $start = Carbon::create($year, 1, 1)->startOfMonth()->toDateString();  // YYYY-01-01
        $end   = Carbon::create($year, 12, 1)->startOfMonth()->toDateString(); // YYYY-12-01

        $currentMonth = now()->startOfMonth()->toDateString(); // YYYY-MM-01

        // Aggregation from ledger
        $agg = DB::table('assets_depr_ledger_monthly as m')
            ->selectRaw("
            date_trunc('month', m.period)::date as period,
            MAX(m.depr_code) as depr_code,
            COUNT(DISTINCT m.asset_uuid) as asset_count,
            COALESCE(SUM(m.depr_expense),0) as total_depr
        ")
            ->whereBetween('m.period', [$start, $end])
            ->groupByRaw("date_trunc('month', m.period)::date");

        // Month series
        $monthsSql = "(select generate_series(?::date, ?::date, interval '1 month')::date as period) as gs";

        $q = DB::query()
            ->fromRaw($monthsSql, [$start, $end])
            ->leftJoinSub($agg, 'a', function ($j) {
                $j->on('a.period', '=', 'gs.period');
            })
            // current month closing (processed or not)
            ->leftJoin('assets_depr_month_closings as c', 'c.period', '=', 'gs.period')
            // previous month closing (for gating)
            ->leftJoin('assets_depr_month_closings as pc', function ($j) {
                // pc.period = (gs.period - 1 month)
                $j->on('pc.period', '=', DB::raw("(gs.period - interval '1 month')::date"));
            })
            ->selectRaw("
            gs.period as period,
            COALESCE(a.depr_code,'') as depr_code,
            COALESCE(a.asset_count,0) as asset_count,
            COALESCE(a.total_depr,0) as total_depr,

            (c.period IS NOT NULL) as is_processed,
            (pc.period IS NOT NULL) as prev_processed,
            (gs.period = ?::date) as is_current
        ", [$currentMonth])
            ->orderBy('gs.period', 'asc');

        return DataTables::of($q)
            ->addColumn('period_ym', fn($row) => Carbon::parse($row->period)->format('Y-m'))
            ->addColumn('depr_code_display', function ($row) {
                // Always show deterministic code per month, suffix always 0001
                $d = Carbon::parse($row->period);
                $generated = 'DEP' . $d->format('ym') . '0001';
                return $row->depr_code ?: $generated;
            })
            ->addColumn('can_click', function ($row) {
                // RULE: $isProcessed || ($isCurrent && $prevProcessed)
                $isProcessed    = (bool) $row->is_processed;
                $isCurrent      = (bool) $row->is_current;
                $prevProcessed  = (bool) $row->prev_processed;

                return $isProcessed || ($isCurrent && $prevProcessed);
            })
            ->toJson();
    }



    public function initFirstRun(Request $request)
    {
        abort_unless($request->user()?->hasAction('DEPRECIATION', 'C'), 403);

        $already = DB::table('assets_depr_month_closings')->limit(1)->exists()
            || DB::table('assets_depr_ledger_monthly')->limit(1)->exists();

        if ($already) {
            return response()->json([
                'ok' => false,
                'message' => 'Initialize disabled: depreciation data already exists.',
            ], 409);
        }

        $minEligible = DB::table('assets as a')
            ->join('assets_value as av', 'av.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_depr_policy as p', 'p.asset_uuid', '=', 'a.uuid')
            ->where('a.kode_status', '!=', 'DIS')
            ->whereNotNull('av.capitalization_date')
            ->selectRaw("
            MIN(
                CASE
                    WHEN EXTRACT(DAY FROM av.capitalization_date) <= COALESCE(p.cutoff_day, 15)
                        THEN (date_trunc('month', av.capitalization_date) + INTERVAL '0 month')::date
                    ELSE (date_trunc('month', av.capitalization_date) + INTERVAL '1 month')::date
                END
            ) as min_eligible
        ")
            ->value('min_eligible');

        if (!$minEligible) {
            return response()->json([
                'ok' => false,
                'message' => 'Cannot initialize: no capitalization_date found in assets_value.',
            ], 422);
        }

        $firstPeriod = Carbon::parse($minEligible)->startOfMonth();

        try {
            $this->processMonthlyDepr($firstPeriod);

            return response()->json([
                'ok' => true,
                'period' => $firstPeriod->toDateString(),
                'period_month' => $firstPeriod->format('Y-m'),
                'message' => 'Initialized depreciation first run successfully.',
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage() ?: 'Initialize failed.',
            ], 500);
        }
    }


    public function dtPolicies(Request $r)
    {
        if (!$this->canReadDepr()) {
            return DataTables::of(collect())->toJson();
        }
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
        if (!$this->canReadDepr()) {
            return DataTables::of(collect())->toJson();
        }

        // =========================
        // NEW: PERIOD RANGE FILTER
        // =========================
        // Expect: period_from=YYYY-MM, period_to=YYYY-MM
        $periodFromMonth = trim((string) $r->input('period_from', '')); // ex: 2026-01
        $periodToMonth   = trim((string) $r->input('period_to', ''));   // ex: 2026-03

        // Default: current month only
        $from = $periodFromMonth !== ''
            ? Carbon::createFromFormat('Y-m', $periodFromMonth)->startOfMonth()
            : now()->startOfMonth();

        $to = $periodToMonth !== ''
            ? Carbon::createFromFormat('Y-m', $periodToMonth)->startOfMonth()
            : $from->copy();

        // Normalize if reversed
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $fromDate = $from->toDateString(); // YYYY-MM-01
        $toDate   = $to->toDateString();   // YYYY-MM-01

        // For "Ending Balance prev year" join (single year reference)
        $periodYear = (int) $to->year;
        $prevYear   = $periodYear - 1;

        // =========================
        // QUERY
        // =========================
        $q = AssetDeprMonthly::query()
            ->with('asset:uuid,asset_code,description,kode_status')
            ->leftJoin('assets_value as av', 'av.asset_uuid', '=', 'assets_depr_ledger_monthly.asset_uuid')
            ->leftJoin('assets_depr_policy as p', 'p.asset_uuid', '=', 'assets_depr_ledger_monthly.asset_uuid')
            ->leftJoin('assets_depr_yearly as y', function ($j) use ($prevYear) {
                $j->on('y.asset_uuid', '=', 'assets_depr_ledger_monthly.asset_uuid')
                    ->where('y.fiscal_year', '=', $prevYear);
            })
            // RANGE FILTER
            ->whereBetween('assets_depr_ledger_monthly.period', [$fromDate, $toDate])
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
                'assets_depr_ledger_monthly.depr_code',
                'av.capitalization_date as cap_date',
                'av.total as total_value',
                'y.ending_balance_year as ending_balance_prev_year',
                DB::raw("COALESCE(assets_depr_ledger_monthly.ending_balance, 0) - COALESCE(assets_depr_ledger_monthly.accumulated_depr_end, 0) as last_net_book_value"),
                DB::raw("
                CASE
                    WHEN av.capitalization_date IS NULL THEN NULL
                    ELSE
                    (date_trunc('month', assets_depr_ledger_monthly.period))::date
                END AS period_depr
            "),
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
                DB::raw("(
            WITH base AS (
                SELECT
                COALESCE(
                    NULLIF(p.useful_life_months, 0),
                    NULLIF(av.useful_life_month, 0),
                    CASE WHEN av.useful_life_year IS NOT NULL
                    THEN (av.useful_life_year * 12)::int
                    ELSE 0 END,
                    60
                ) AS life_months,

                COALESCE(p.depr_start_date, av.capitalization_date, av.actual_date, av.created_at) AS start_date,

                COALESCE(p.cutoff_day, 15) AS cutoff_day,

                date_trunc('month', assets_depr_ledger_monthly.period)::date AS period_m,

                COALESCE(assets_depr_ledger_monthly.depr_expense, 0) AS depr_expense,
                COALESCE(assets_depr_ledger_monthly.adjustment_depreciation, 0) AS adj_depr,
                COALESCE(assets_depr_ledger_monthly.accumulated_depr_end, 0) AS acc_end
            ),
            calc AS (
                SELECT
                life_months,
                start_date,
                cutoff_day,
                period_m,
                depr_expense,
                adj_depr,
                acc_end,
                CASE
                    WHEN start_date IS NULL THEN NULL
                    WHEN EXTRACT(DAY FROM start_date) <= cutoff_day
                    THEN date_trunc('month', start_date)::date
                    ELSE (date_trunc('month', start_date) + INTERVAL '1 month')::date
                END AS eligible_start
                FROM base
            )
            SELECT
                GREATEST(
                calc.life_months
                -
                CASE
                    -- belum eligible -> jangan berkurang
                    WHEN calc.eligible_start IS NULL OR calc.period_m < calc.eligible_start THEN 0

                    -- eligible tapi bulan ini tidak ada depresiasi sama sekali -> jangan berkurang
                    WHEN (calc.acc_end = 0 AND calc.depr_expense = 0 AND calc.adj_depr = 0) THEN 0

                    -- sudah mulai depresiasi -> bulan berjalan ikut dihitung
                    ELSE LEAST(
                    calc.life_months,
                    (DATE_PART('year', AGE(calc.period_m, calc.eligible_start))::int * 12)
                    + DATE_PART('month', AGE(calc.period_m, calc.eligible_start))::int
                    + 1
                    )
                END
                , 0)
            FROM calc
            ) AS remaining_useful_life_months")
            ]);

        // =========================
        // FILTERS (UNCHANGED)
        // =========================
        if ($status = $r->get('asset_status')) {
            $q->whereHas('asset', function ($qa) use ($status) {
                $qa->where('kode_status', $status);
            });
        }

        if ($capFrom = $r->get('cap_from')) {
            $q->whereDate('av.capitalization_date', '>=', $capFrom);
        }
        if ($capTo = $r->get('cap_to')) {
            $q->whereDate('av.capitalization_date', '<=', $capTo);
        }

        if ($assetQ = trim((string) $r->get('asset_q', ''))) {
            $q->whereHas('asset', function ($qa) use ($assetQ) {
                $qa->where('asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('description', 'ilike', "%{$assetQ}%");
            });
        }

        // Total depreciation for current filtered range
        $totalDepr = (clone $q)->sum('assets_depr_ledger_monthly.depr_expense');

        return DataTables::of($q)
            ->addColumn('asset_code', fn($row) => $row->asset?->asset_code)
            ->addColumn('asset_name', fn($row) => $row->asset?->description)
            ->addColumn('asset_uuid', fn($row) => $row->asset_uuid)
            ->addColumn('asset_kode_status', fn($row) => $row->asset?->kode_status)
            ->addColumn('asset_status_label', function ($row) {
                static $statusMap = null;

                if ($statusMap === null) {
                    $statusMap = MasterStatus::pluck('name', 'kode')->toArray();
                }

                $kode = $row->asset?->kode_status;
                if (!$kode) return null;

                $name = $statusMap[$kode] ?? null;
                return $name ? "{$kode} - {$name}" : $kode;
            })

            ->filterColumn('asset_code', function ($query, $keyword) {
                $keyword = trim($keyword);
                $query->whereHas('asset', function ($qa) use ($keyword) {
                    $qa->where('asset_code', 'ilike', "%{$keyword}%");
                });
            })
            ->filterColumn('asset_name', function ($query, $keyword) {
                $keyword = trim($keyword);
                $query->whereHas('asset', function ($qa) use ($keyword) {
                    $qa->where('description', 'ilike', "%{$keyword}%");
                });
            })
            ->filterColumn('asset_status_label', function ($query, $keyword) {
                $keyword = trim($keyword);
                $query->whereHas('asset', function ($qa) use ($keyword) {
                    $qa->where('kode_status', 'ilike', "%{$keyword}%");
                });
            })
            ->filterColumn('cap_date', function ($query, $keyword) {
                $keyword = trim($keyword);
                $query->whereRaw("to_char(av.capitalization_date,'YYYY-MM-DD') ILIKE ?", ["%{$keyword}%"]);
            })

            ->with('total_depr', (float) $totalDepr)
            ->toJson();
    }


    public function dtYearly(Request $r)
    {
        if (!$this->canReadDepr()) {
            return DataTables::of(collect())->toJson();
        }
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

    // public function runMonth(Request $request)
    // {
    //     abort_unless($request->user()?->hasAction('DEPRECIATION', 'C'), 403);

    //     $to = $request->filled('period')
    //         ? Carbon::parse($request->input('period'))->startOfMonth()
    //         : now()->startOfMonth();

    //     $fromPolicy = AssetDeprPolicy::whereNotNull('depr_start_date')
    //         ->min(DB::raw("date_trunc('month', depr_start_date)::date"));

    //     $from = $fromPolicy
    //         ? Carbon::parse($fromPolicy)->startOfMonth()
    //         : $to->copy();

    //     $this->processMonthlyDeprRange($from, $to);

    //     return response()->json(['ok' => true]);
    // }

    // private function processMonthlyDeprRange(Carbon $from, Carbon $to): void
    // {
    //     $from = $from->copy()->startOfMonth();
    //     $to   = $to->copy()->startOfMonth();

    //     if ($from->gt($to)) {
    //         return;
    //     }

    //     while ($from->lte($to)) {
    //         $this->processMonthlyDepr($from);
    //         $from->addMonth();
    //     }
    // }
    private function ensurePrevMonthProcessed(Carbon $period): void
    {
        $prev = $period->copy()->subMonth()->startOfMonth();

        // Kalau bulan sebelumnya memang tidak ada aset eligible, jangan dipaksa ada closing.
        if (! $this->monthHasEligibleAssets($prev)) {
            return;
        }

        $exists = AssetDeprMonthClosing::query()
            ->whereDate('period', $prev->toDateString())
            ->exists();

        if (! $exists) {
            throw new \RuntimeException("PREV_MONTH_NOT_PROCESSED|{$prev->toDateString()}");
        }
    }

    public function runMonth(Request $request)
    {
        abort_unless($request->user()?->hasAction('DEPRECIATION', 'C'), 403);

        $periodInput = $request->input('period');
        $period = $periodInput
            ? Carbon::parse($periodInput)->startOfMonth()
            : now()->startOfMonth();

        try {
            $this->guardWritablePeriod($period, 'runMonth', [
                'require_prev_month' => true,
                'enforce_first_run_min_eligible' => true,
            ]);

            $this->processMonthlyDepr($period);

            return response()->json([
                'ok' => true,
                'period' => $period->toDateString(),
            ]);
        } catch (\Throwable $e) {
            if ($resp = $this->renderGuardError($e)) return $resp;

            report($e);
            return response()->json([
                'ok' => false,
                'code' => 'SERVER_ERROR',
                'message' => $e->getMessage() ?: 'Failed to process depreciation.',
            ], 500);
        }
    }


    private function processMonthlyDepr(Carbon $period): void
    {
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

                    'p.asset_uuid         AS has_policy',
                    'p.depr_start_date    AS policy_depr_start_date',
                    'p.useful_life_months AS policy_life_months',
                    'p.cutoff_day         AS policy_cutoff_day'
                )
                ->where('assets.kode_status', '!=', 'DIS')
                ->get();

            foreach ($assets as $row) {
                $startFromAv = $row->capitalization_date
                    ?: ($row->actual_date ?: $row->av_created_at);

                $lifeFromAv = (int) ($row->useful_life_month ?? 0);
                if ($lifeFromAv <= 0 && isset($row->useful_life_year)) {
                    $lifeFromAv = (int) round(((float) $row->useful_life_year) * 12);
                }
                if ($lifeFromAv <= 0) {
                    $lifeFromAv = 60;
                }

                if (!$row->has_policy) {
                    if (!$startFromAv) {
                        continue;
                    }

                    AssetDeprPolicy::create([
                        'asset_uuid'         => $row->asset_uuid,
                        'method'             => AssetDeprPolicy::METHOD_SL,
                        'useful_life_months' => $lifeFromAv,
                        'salvage_value'      => 0,
                        'depr_start_date'    => Carbon::parse($startFromAv)->toDateString(),
                        'convention'         => AssetDeprPolicy::CONVENTION_PRORATA_MONTH,
                        'cutoff_day'         => 15,
                        'start_rule'         => 'CUT_OFF_NEXT_OR_NEXT2',
                        'is_active'          => true,
                    ]);

                    continue;
                }

                if ($startFromAv) {
                    $newStart = Carbon::parse($startFromAv)->toDateString();

                    if (
                        !$row->policy_depr_start_date ||
                        Carbon::parse($newStart)->lt(Carbon::parse($row->policy_depr_start_date))
                    ) {
                        AssetDeprPolicy::where('asset_uuid', $row->asset_uuid)
                            ->update(['depr_start_date' => $newStart]);
                    }
                }

                if ($lifeFromAv > 0 && (int) $row->policy_life_months <= 0) {
                    AssetDeprPolicy::where('asset_uuid', $row->asset_uuid)
                        ->update(['useful_life_months' => $lifeFromAv]);
                }
            }

            $policies = AssetDeprPolicy::with('asset')
                ->where('is_active', true)
                ->whereHas('asset', function ($q) {
                    $q->where('kode_status', '!=', 'DIS');
                })
                ->get();

            foreach ($policies as $policy) {
                $assetUuid = $policy->asset_uuid;

                $av = DB::table('assets_value')
                    ->select('capitalization_date', 'actual_date', 'created_at', 'total')
                    ->where('asset_uuid', $assetUuid)
                    ->first();

                $capDate  = $av?->capitalization_date ?? $av?->actual_date ?? $av?->created_at;
                $capTotal = (float) ($av?->total ?? 0);

                if ($capDate) {
                    $assetStartMonth = Carbon::parse($capDate)->startOfMonth();
                    if ($period->lt($assetStartMonth)) {
                        continue;
                    }
                }

                $prev = AssetDeprMonthly::where('asset_uuid', $assetUuid)
                    ->whereDate('period', '<', $period)
                    ->orderBy('period', 'desc')
                    ->first();

                $opening = (float) ($prev->ending_balance ?? $capTotal);
                $accPrev = (float) ($prev->accumulated_depr_end ?? 0.0);

                $additions = 0.0;
                $tin       = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)
                    ->whereDate('period', $period)
                    ->where('category', AssetDeprMovement::TRANSFER_IN)
                    ->sum('amount');
                $tout      = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)
                    ->whereDate('period', $period)
                    ->where('category', AssetDeprMovement::TRANSFER_OUT)
                    ->sum('amount');
                $disposals = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)
                    ->whereDate('period', $period)
                    ->where('category', AssetDeprMovement::DISPOSAL)
                    ->sum('amount');
                $adjValue  = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)
                    ->whereDate('period', $period)
                    ->where('category', AssetDeprMovement::ADJUSTMENT_VALUE)
                    ->sum('amount');
                $adjDepr   = (float) AssetDeprMovement::where('asset_uuid', $assetUuid)
                    ->whereDate('period', $period)
                    ->where('category', AssetDeprMovement::ADJUSTMENT_DEPRECIATION)
                    ->sum('amount');

                $startBase = $policy->depr_start_date
                    ?: ($capDate ? Carbon::parse($capDate)->toDateString() : null);

                $eligibleStart = $startBase
                    ? Carbon::parse($startBase)->startOfMonth()->addMonths(
                        (Carbon::parse($startBase)->day <= ($policy->cutoff_day ?? 15)) ? 0 : 1
                    )
                    : $period;

                $eligible   = $eligibleStart->lte($period);
                $lifeMonths = (int) ($policy->useful_life_months ?: 60);
                if ($lifeMonths <= 0) $lifeMonths = 60;

                $elapsed   = $eligible ? $eligibleStart->diffInMonths($period) : 0;
                $remaining = $eligible ? max(0, $lifeMonths - $elapsed) : 0;

                $deprBase = $eligible
                    ? max(
                        ($opening + $adjValue + $tin - $tout - $disposals)
                            - (float) $policy->salvage_value,
                        0.0
                    )
                    : 0.0;

                $deprExpense = ($eligible && $remaining > 0)
                    ? floor($deprBase / $remaining)
                    : 0.0;

                $ending = $opening
                    + $additions
                    + $tin
                    - $tout
                    - $disposals
                    + $adjValue
                    - $deprExpense
                    + $adjDepr;

                $accEnd = $accPrev + $deprExpense + $adjDepr;
                $txCode = 'DEP' . $period->format('ym') . '0001';

                AssetDeprMonthly::updateOrCreate(
                    [
                        'asset_uuid' => $assetUuid,
                        'period'     => $period->toDateString(),
                    ],
                    [
                        'opening_balance'         => $opening,
                        'additions'               => $additions,
                        'transfers_in'            => $tin,
                        'transfers_out'           => $tout,
                        'disposals'               => $disposals,
                        'adjustment_value'        => $adjValue,
                        'adjustment_depreciation' => $adjDepr,
                        'depr_expense'            => $deprExpense,
                        'accumulated_depr_end'    => $accEnd,
                        'ending_balance'          => $ending,
                        'depr_code'               => $txCode,
                    ]
                );
            }

            $rowCount = AssetDeprMonthly::query()
                ->whereDate('period', $period->toDateString())
                ->count();

            AssetDeprMonthClosing::updateOrCreate(
                ['period' => $period->toDateString()],
                [
                    'row_count' => (int) $rowCount,
                    'processed_by' => auth()->user()?->name,
                    'processed_at' => now(),
                ]
            );
        });
    }

    public function buildYear(Request $request)
    {
        abort_unless($request->user()?->hasAction('DEPRECIATION', 'C'), 403);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1900', 'max:3000'],
        ]);

        $year = (int) $data['year'];

        try {
            $this->assertBuildYearAllowedNow();
            $this->assertYearOpen($year, 'buildYear');
            $this->assertDecemberProcessed($year); // wajib Dec sudah diproses
        } catch (\Throwable $e) {

            if ($e->getMessage() === 'BUILD_YEAR_ONLY_DECEMBER') {
                return response()->json([
                    'ok' => false,
                    'code' => 'BUILD_YEAR_ONLY_DECEMBER',
                    'message' => 'Build Year hanya boleh dilakukan di bulan Desember.',
                ], 422);
            }

            if (str_starts_with($e->getMessage(), 'YEAR_LOCKED|')) {
                return response()->json([
                    'ok' => false,
                    'code' => 'YEAR_LOCKED',
                    'message' => "Year {$year} sudah di-build & dikunci. Rollback dulu kalau mau ubah.",
                    'year' => $year,
                ], 422);
            }

            if (str_starts_with($e->getMessage(), 'DECEMBER_NOT_PROCESSED|')) {
                $need = explode('|', $e->getMessage())[1] ?? null;
                return response()->json([
                    'ok' => false,
                    'code' => 'DECEMBER_NOT_PROCESSED',
                    'message' => 'Build Year butuh periode Desember sudah diproses dulu.',
                    'need_period' => $need,
                    'need_period_text' => $need ? Carbon::parse($need)->isoFormat('MMMM YYYY') : null,
                ], 422);
            }

            throw $e;
        }

        DB::transaction(function () use ($year) {
            $start = "{$year}-01-01";
            $end   = "{$year}-12-31";

            $rows = AssetDeprMonthly::query()
                ->selectRaw("
                asset_uuid,
                SUM(CASE WHEN EXTRACT(MONTH FROM period) = 1 THEN opening_balance ELSE 0 END) AS opening_balance,
                SUM(additions + transfers_in - transfers_out - disposals + adjustment_value) AS total_additions,
                SUM(depr_expense) AS depr_expense_year,
                SUM(adjustment_depreciation) AS adjustment_depreciation_year,
                MAX(accumulated_depr_end) AS accumulated_depr_end,
                MAX(ending_balance) AS ending_balance_year
            ")
                ->whereBetween('period', [$start, $end])
                ->groupBy('asset_uuid')
                ->get();

            foreach ($rows as $row) {
                AssetDeprYearly::updateOrCreate(
                    [
                        'asset_uuid'  => $row->asset_uuid,
                        'fiscal_year' => $year,
                    ],
                    [
                        'opening_balance'              => (float) $row->opening_balance,
                        'total_additions'              => (float) $row->total_additions,
                        'depr_expense_year'            => (float) $row->depr_expense_year,
                        'adjustment_depreciation_year' => (float) $row->adjustment_depreciation_year,
                        'accumulated_depr_end'         => (float) $row->accumulated_depr_end,
                        'ending_balance_year'          => (float) $row->ending_balance_year,
                    ]
                );
            }

            // lock year setelah build
            AssetDeprYearClosing::updateOrCreate(
                ['fiscal_year' => $year],
                [
                    'is_locked' => true,
                    'built_by'  => auth()->user()?->name,
                    'built_at'  => now(),
                ]
            );
        });

        return response()->json([
            'ok'      => true,
            'message' => "Year {$year} built & locked.",
            'year'    => $year,
        ]);
    }

    public function yearStatus(Request $r)
    {
        abort_unless($this->canReadDepr(), 403);

        $year = (int) ($r->input('year') ?: now()->year);

        $locked = AssetDeprYearClosing::query()
            ->where('fiscal_year', $year)
            ->where('is_locked', true)
            ->exists();

        return response()->json([
            'ok' => true,
            'year' => $year,
            'locked' => $locked,
            'can_build_now' => $this->canBuildYearNowForTarget($year),
            'now_year' => (int) now()->year,
            'now_month' => (int) now()->month,
        ]);
    }


    public function rollbackYear(Request $request)
    {
        abort_unless($request->user()?->hasAction('DEPRECIATION', 'C'), 403);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1900', 'max:3000'],
        ]);

        $year = (int) $data['year'];
        try {
            $this->assertSequentialRollback($year);
        } catch (\Throwable $e) {
            if (str_starts_with($e->getMessage(), 'ROLLBACK_ONLY_PREV_YEAR|')) {
                $need = explode('|', $e->getMessage())[1] ?? null;

                return response()->json([
                    'ok' => false,
                    'code' => 'ROLLBACK_ONLY_PREV_YEAR',
                    'message' => "Rollback hanya boleh untuk tahun sebelumnya: {$need}.",
                    'allowed_year' => (int)$need,
                ], 422);
            }

            if (str_starts_with($e->getMessage(), 'ROLLBACK_MUST_BE_LATEST_LOCKED|')) {
                $need = explode('|', $e->getMessage())[1] ?? null;

                return response()->json([
                    'ok' => false,
                    'code' => 'ROLLBACK_MUST_BE_LATEST_LOCKED',
                    'message' => "Rollback tidak boleh loncat. Harus rollback locked year paling terakhir: {$need}.",
                    'latest_locked_year' => $need !== null ? (int)$need : null,
                ], 422);
            }

            throw $e;
        }

        DB::transaction(function () use ($year) {

            // delete yearly summary
            AssetDeprYearly::query()
                ->where('fiscal_year', $year)
                ->delete();

            // unlock year
            AssetDeprYearClosing::updateOrCreate(
                ['fiscal_year' => $year],
                [
                    'is_locked' => false,
                    'rolled_back_by' => auth()->user()?->name,
                    'rolled_back_at' => now(),
                ]
            );
        });

        return response()->json([
            'ok' => true,
            'message' => "Year {$year} unlocked (rollback build year).",
            'year' => $year,
        ]);
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
            'transfer_type'   => ['nullable', 'in:tf-val,' . self::TRANSFER_TYPE_CARRY_OVER . ',' . self::TRANSFER_TYPE_ACQ_FIX],
            'from_asset_uuid' => ['required', 'uuid'],
            'to_asset_uuid'   => ['required', 'uuid', 'different:from_asset_uuid'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'actual_date'     => ['required', 'date'],
            'note'            => ['nullable', 'string', 'max:300'],
            'source_type'     => ['nullable', 'string', 'max:64'],
            'source_uuid'     => ['nullable', 'uuid'],
            'group_uuid'      => ['nullable', 'uuid'],
        ]);

        $msg = $this->applyTransfer($data);

        return response()->json([
            'ok'         => true,
            'message'    => $msg,
        ]);
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
        abort_unless($r->user()?->hasAction('DEPRECIATION', 'C'), 403);

        $data = $r->validate([
            'asset_uuid'  => ['required', 'uuid'],
            'amount'      => ['required', 'numeric', 'not_in:0'],
            'actual_date' => ['required', 'date'],
            'note'        => ['nullable', 'string', 'max:300'],
        ]);

        $actual  = Carbon::parse($data['actual_date']);
        $period  = $actual->copy()->startOfMonth();
        $current = now()->startOfMonth();

        // Rule: hanya periode berjalan
        if (! $period->equalTo($current)) {
            return response()->json([
                'ok' => false,
                'code' => 'ADJ_ONLY_CURRENT_PERIOD',
                'message' => 'Adjustment Depreciation hanya bisa dilakukan di periode berjalan.',
                'need_period' => $current->toDateString(),
                'need_period_text' => $current->isoFormat('MMMM YYYY'),
            ], 422);
        }

        try {
            // optional: pastikan current month sudah pernah diproses
            $currentClosingExists = AssetDeprMonthClosing::query()
                ->whereDate('period', $period->toDateString())
                ->exists();

            if (! $currentClosingExists) {
                return response()->json([
                    'ok' => false,
                    'code' => 'CURRENT_MONTH_NOT_PROCESSED',
                    'message' => 'Periode berjalan belum diproses. Jalankan "Process Depreciation Month" dulu.',
                    'need_period' => $period->toDateString(),
                    'need_period_text' => $period->isoFormat('MMMM YYYY'),
                ], 422);
            }

            // Guard: year lock + chain gate + prev-month processed (skip kalau prev-month kosong)
            $this->guardWritablePeriod($period, 'adjDepr', [
                'require_prev_month' => true,
                'enforce_first_run_min_eligible' => false,
            ]);

            AssetDeprMovement::create([
                'asset_uuid'        => $data['asset_uuid'],
                'period'            => $period,
                'category'          => AssetDeprMovement::ADJUSTMENT_DEPRECIATION,
                'amount'            => (float) $data['amount'],
                'depr_start_period' => $period,
                'source_type'       => 'manual',
                'note'              => $data['note'] ?? null,
            ]);

            // recompute month
            $this->processMonthlyDepr($period);

            return response()->json([
                'ok' => true,
                'message' => 'Depreciation adjustment recorded (current period).',
                'period' => $period->toDateString(),
            ]);
        } catch (\Throwable $e) {
            if ($resp = $this->renderGuardError($e)) return $resp;

            report($e);
            return response()->json([
                'ok' => false,
                'code' => 'SERVER_ERROR',
                'message' => $e->getMessage() ?: 'Failed to save adjustment.',
            ], 500);
        }
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
        $addMonths = ($d->day <= $cutoffDay) ? 0 : 1;
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

        $total = (float) DB::table('assets_value')->where('asset_uuid', $assetUuid)->value('total');

        $lastRow = AssetDeprMonthly::query()
            ->where('asset_uuid', $assetUuid)
            ->whereDate('period', '<', $monthStart)
            ->orderBy('period', 'desc')
            ->first();

        $lastNbv    = $lastRow ? (float) $lastRow->ending_balance : 0.0;
        $lastPeriod = $lastRow?->period;

        $nbvClamped = min($lastNbv, $total);
        $max        = max(0.0, $total - $nbvClamped);

        return [
            'begin_total'        => (float) $total,
            'last_closed_period' => $lastPeriod,
            'last_nbv'           => (float) $lastNbv,
            'max'                => (float) $max,
            'already_out'        => 0.0,        // no longer relevant
            'remaining'          => (float) $max // remaining == max
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
        return (float) DB::table('assets_value')->where('asset_uuid', $assetUuid)->value('total');
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

    private function componentMonthsYtd(AssetDeprPolicy $policy, Carbon $transferPeriod): int
    {
        $yearStart   = $transferPeriod->copy()->startOfYear();
        $monthBefore = $transferPeriod->copy()->subMonth()->startOfMonth();

        if ($monthBefore->lt($yearStart)) {
            return 0;
        }

        $startDate = Carbon::parse($policy->depr_start_date);
        $cutoff    = $policy->cutoff_day ?? 15;

        $addMonths = ($startDate->day <= $cutoff) ? 0 : 1;
        $eligibleStart = $startDate->copy()->startOfMonth()->addMonths($addMonths);

        $effectiveStart = $eligibleStart->gt($yearStart) ? $eligibleStart : $yearStart;

        if ($monthBefore->lt($effectiveStart)) {
            return 0;
        }

        return $effectiveStart->diffInMonths($monthBefore) + 1;
    }

    private function applyTransfer(array $data): string
    {
        $type   = $data['transfer_type'] ?? 'tf-val';
        $period = Carbon::parse($data['actual_date'])->startOfMonth();
        $group  = $data['group_uuid'] ?? Str::uuid();

        $fromPolicy = AssetDeprPolicy::where('asset_uuid', $data['from_asset_uuid'])->where('is_active', true)->first();
        $toPolicy   = AssetDeprPolicy::where('asset_uuid', $data['to_asset_uuid'])->where('is_active', true)->first();
        $fromStart  = $this->calcDeprStartPeriod($data['actual_date'], $fromPolicy?->cutoff_day ?? 15);
        $toStart    = $this->calcDeprStartPeriod($data['actual_date'], $toPolicy?->cutoff_day ?? 15);

        // === Case 2: Acquisition Fix (acq_fix) ===
        if ($type === self::TRANSFER_TYPE_ACQ_FIX) {

            $amount = (float) $data['amount'];

            $fromTotal = (float) DB::table('assets_value')
                ->where('asset_uuid', $data['from_asset_uuid'])
                ->value('total');

            if ($amount > $fromTotal) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount exceeds gross value of the source asset.',
                ]);
            }

            if (!$fromPolicy || !$toPolicy) {
                throw ValidationException::withMessages([
                    'from_asset_uuid' => 'Both assets must have active depreciation policies for acquisition correction.',
                ]);
            }

            $fromLife = (int) ($fromPolicy->useful_life_months ?: 0);
            $toLife   = (int) ($toPolicy->useful_life_months   ?: 0);

            if ($fromLife <= 0 || $toLife <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Useful life (months) must be set for both assets for acquisition correction.',
                ]);
            }

            $fromRate = $amount / $fromLife;
            $toRate   = $amount / $toLife;

            $monthsFromYtd = $this->componentMonthsYtd($fromPolicy, $period);
            $monthsToYtd   = $this->componentMonthsYtd($toPolicy, $period);

            $depFromYtd = $fromRate * $monthsFromYtd;
            $depToYtd   = $toRate   * $monthsToYtd;

            $adjDepA = $depToYtd;
            $adjDepB = -$depFromYtd;

            DB::transaction(function () use ($data, $period, $group, $amount, $fromStart, $toStart, $adjDepA, $adjDepB) {

                // Gross out FROM
                AssetDeprMovement::create([
                    'asset_uuid'        => $data['from_asset_uuid'],
                    'period'            => $period,
                    'category'          => AssetDeprMovement::TRANSFER_OUT,
                    'amount'            => $amount,
                    'depr_start_period' => $fromStart,
                    'group_uuid'        => $group,
                    'source_type'       => $data['source_type'] ?? 'manual',
                    'source_uuid'       => $data['source_uuid'] ?? null,
                    'note'              => trim(($data['note'] ?? '') . ' | acq-fix gross OUT'),
                ]);

                AssetDeprMovement::create([
                    'asset_uuid'        => $data['to_asset_uuid'],
                    'period'            => $period,
                    'category'          => AssetDeprMovement::TRANSFER_IN,
                    'amount'            => $amount,
                    'depr_start_period' => $toStart,
                    'group_uuid'        => $group,
                    'source_type'       => $data['source_type'] ?? 'manual',
                    'source_uuid'       => $data['source_uuid'] ?? null,
                    'note'              => trim(($data['note'] ?? '') . ' | acq-fix gross IN'),
                ]);

                if (abs($adjDepB) > 0.0001) {
                    AssetDeprMovement::create([
                        'asset_uuid'        => $data['from_asset_uuid'],
                        'period'            => $period,
                        'category'          => AssetDeprMovement::ADJUSTMENT_DEPRECIATION,
                        'amount'            => $adjDepB,
                        'depr_start_period' => $period,
                        'group_uuid'        => $group,
                        'source_type'       => $data['source_type'] ?? 'manual',
                        'source_uuid'       => $data['source_uuid'] ?? null,
                        'note'              => trim(($data['note'] ?? '') . ' | acq-fix reverse YTD'),
                    ]);
                }

                if (abs($adjDepA) > 0.0001) {
                    AssetDeprMovement::create([
                        'asset_uuid'        => $data['to_asset_uuid'],
                        'period'            => $period,
                        'category'          => AssetDeprMovement::ADJUSTMENT_DEPRECIATION,
                        'amount'            => $adjDepA,
                        'depr_start_period' => $period,
                        'group_uuid'        => $group,
                        'source_type'       => $data['source_type'] ?? 'manual',
                        'source_uuid'       => $data['source_uuid'] ?? null,
                        'note'              => trim(($data['note'] ?? '') . ' | acq-fix add YTD'),
                    ]);
                }
            });

            return 'Acquisition correction transfer recorded';
        }

        // === Case 3: Carry-over (gross + accum) ===
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
                // FROM
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

                // TO
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

            return 'Carry-Over transfer recorded';
        }

        // === Case 1: tf-val (gross only, with NBV cap per month) ===
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

        return 'Transfer recorded';
    }

    /* ============================================================
     *  TRANSFER REQUESTS
     * ============================================================
     */

    public function transferRequestsIndex()
    {
        abort_unless($this->canReadTransferRequests(), 403);
        return view('depreciation.transfer_requests');
    }
    public function dtTransferRequests(Request $r)
    {
        if (!$this->canReadTransferRequests()) {
            return DataTables::of(collect())->toJson();
        }

        $q = AssetDeprTransferRequest::query()
            ->with([
                'fromAsset:uuid,asset_code,description',
                'toAsset:uuid,asset_code,description',
            ]);

        // type
        if ($type = $r->get('type')) {
            $q->where('transfer_type', $type);
        }

        // status approval
        if ($status = $r->get('status')) {
            $q->where('kode_status', $status);
        }

        // requester
        if ($req = $r->get('requester')) {
            $q->where('requested_by', $req);
        }

        // request date range (created_at)
        if ($from = $r->get('req_from')) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $r->get('req_to')) {
            $q->whereDate('created_at', '<=', $to);
        }

        // approve date range (approved_at)
        if ($from = $r->get('apr_from')) {
            $q->whereDate('approved_at', '>=', $from);
        }
        if ($to = $r->get('apr_to')) {
            $q->whereDate('approved_at', '<=', $to);
        }

        if ($search = trim($r->get('asset_q', ''))) {
            $q->where(function ($qq) use ($search) {
                $qq->whereHas('fromAsset', function ($qa) use ($search) {
                    $qa->where('asset_code', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                })->orWhereHas('toAsset', function ($qa) use ($search) {
                    $qa->where('asset_code', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            });
        }
        $dt = DataTables::eloquent($q)
            ->addColumn('from_uuid', fn($row) => $row->fromAsset?->uuid)
            ->addColumn('from_code', fn($row) => $row->fromAsset?->asset_code)
            ->addColumn('from_name', fn($row) => $row->fromAsset?->description)
            ->addColumn('to_uuid',   fn($row) => $row->toAsset?->uuid)
            ->addColumn('to_code',   fn($row) => $row->toAsset?->asset_code)
            ->addColumn('to_name',   fn($row) => $row->toAsset?->description)
            ->addColumn('attachment_url', fn($row) => $row->attachment_path ? Storage::url($row->attachment_path) : null);

        $dt->filterColumn('from_asset_uuid', function ($query, $keyword) {
            $k = trim((string)$keyword);
            if ($k === '') return;
            $query->whereHas('fromAsset', function ($qa) use ($k) {
                $qa->where('asset_code', 'ilike', "%{$k}%")
                    ->orWhere('description', 'ilike', "%{$k}%");
            });
        });

        $dt->filterColumn('to_asset_uuid', function ($query, $keyword) {
            $k = trim((string)$keyword);
            if ($k === '') return;
            $query->whereHas('toAsset', function ($qa) use ($k) {
                $qa->where('asset_code', 'ilike', "%{$k}%")
                    ->orWhere('description', 'ilike', "%{$k}%");
            });
        });

        return $dt->toJson();
    }


    public function storeTransferRequest(Request $r)
    {
        abort_unless($this->canCreateTransferRequests(), 403);
        $data = $r->validate([
            'transfer_type'   => ['nullable', 'in:tf-val,' . self::TRANSFER_TYPE_CARRY_OVER . ',' . self::TRANSFER_TYPE_ACQ_FIX],
            'from_asset_uuid' => ['required', 'uuid'],
            'to_asset_uuid'   => ['required', 'uuid', 'different:from_asset_uuid'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'actual_date'     => ['required', 'date'],
            'note'            => ['nullable', 'string', 'max:300'],
            'attachment'      => ['nullable', 'file', 'max:5120'],
        ]);

        $type = $data['transfer_type'] ?? 'tf-val';
        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        if ($type === 'tf-val') {
            $cap = $this->computeMaxTransfer($data['from_asset_uuid'], Carbon::parse($data['actual_date']));
            if ((float)$data['amount'] > $cap['remaining']) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount exceeds max allowed for that month (tf-val).',
                ]);
            }
        }

        $path = null;
        if ($r->hasFile('attachment')) {
            $path = $r->file('attachment')->store('depr_transfer_attachments', 'public');
        }

        $now    = Carbon::now();
        $prefix = 'TRF' . $now->format('ym');

        $last = AssetDeprTransferRequest::where('transfer_code', 'like', $prefix . '%')
            ->orderBy('transfer_code', 'desc')
            ->first();

        if ($last) {
            $lastSeq = (int) substr($last->transfer_code, -4);
            $seq     = $lastSeq + 1;
        } else {
            $seq = 1;
        }

        $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);


        $req = AssetDeprTransferRequest::create([
            'from_asset_uuid' => $data['from_asset_uuid'],
            'to_asset_uuid'   => $data['to_asset_uuid'],
            'transfer_type'   => $type,
            'amount'          => $data['amount'],
            'actual_date'     => $data['actual_date'],
            'note'            => $data['note'] ?? null,
            'attachment_path' => $path,
            'kode_status'     => self::STATUS_APR,
            'requested_by'    => $uid,
            'group_uuid'      => Str::uuid(),
            'transfer_code'   => $code,
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Transfer request saved. Waiting for approval.',
            'uuid'    => (string) $req->uuid,
        ]);
    }

    public function approveTransferRequest(Request $r, string $uuid)
    {
        abort_unless($this->canApproveTransferRequests(), 403);
        $req = AssetDeprTransferRequest::where('uuid', $uuid)
            ->where('kode_status', self::STATUS_APR)
            ->firstOrFail();

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        DB::transaction(function () use ($req, $uid) {
            $this->applyTransfer([
                'transfer_type'   => $req->transfer_type,
                'from_asset_uuid' => $req->from_asset_uuid,
                'to_asset_uuid'   => $req->to_asset_uuid,
                'amount'          => (float) $req->amount,
                'actual_date'     => $req->actual_date->toDateString(),
                'note'            => $req->note,
                'source_type'     => 'depr_transfer_request',
                'source_uuid'     => $req->uuid,
                'group_uuid'      => $req->group_uuid,
            ]);

            $req->kode_status = self::STATUS_ACC;
            $req->approved_by = $uid;
            $req->approved_at = now();
            $req->save();
        });

        // runmonth to change immediately the table
        $period = Carbon::parse($req->actual_date)->startOfMonth();
        $this->processMonthlyDepr($period);

        return response()->json([
            'ok'      => true,
            'message' => 'Transfer approved, posted into depreciation, and month recomputed.',
        ]);
    }

    public function rejectTransferRequest(Request $r, string $uuid)
    {
        abort_unless($this->canApproveTransferRequests(), 403);
        $data = $r->validate([
            'status_note' => ['nullable', 'string', 'max:500'],
        ]);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $req = AssetDeprTransferRequest::where('uuid', $uuid)
            ->where('kode_status', self::STATUS_APR)
            ->firstOrFail();

        $req->kode_status = self::STATUS_REJ;
        $req->approved_by = $uid;
        $req->approved_at = now();
        if (!empty($data['status_note'])) {
            $req->note = trim(($req->note ? $req->note . ' | ' : '') . 'REJ: ' . $data['status_note']);
        }
        $req->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Transfer rejected.',
        ]);
    }

    public function destroyTransferRequest(string $uuid)
    {
        abort_unless($this->canDeleteTransferRequests(), 403);
        $req = AssetDeprTransferRequest::where('uuid', $uuid)->firstOrFail();

        if ($req->kode_status !== self::STATUS_APR) {
            return response()->json([
                'ok'      => false,
                'message' => 'Only APR requests can be deleted.',
            ], 422);
        }

        $req->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Transfer request deleted.',
        ]);
    }
    public function downloadTransferRequestAttachment(string $uuid)
    {
        abort_unless($this->canReadTransferRequests(), 403);
        $req = AssetDeprTransferRequest::where('uuid', $uuid)->firstOrFail();

        if (!$req->attachment_path || !Storage::disk('public')->exists($req->attachment_path)) {
            abort(404, 'Attachment not found.');
        }

        return Storage::disk('public')->download(
            $req->attachment_path,
            basename($req->attachment_path)
        );
    }
    public function updateTransferRequest(Request $r, string $uuid)
    {
        abort_unless($this->canUpdateTransferRequests(), 403);
        $req = AssetDeprTransferRequest::where('uuid', $uuid)->firstOrFail();

        if ($req->kode_status !== self::STATUS_APR) {
            return response()->json([
                'ok'      => false,
                'message' => 'Only APR (waiting approval) requests can be edited.',
            ], 422);
        }

        $data = $r->validate([
            'transfer_type' => ['nullable', 'in:tf-val,' . self::TRANSFER_TYPE_CARRY_OVER . ',' . self::TRANSFER_TYPE_ACQ_FIX],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'actual_date'   => ['required', 'date'],
            'note'          => ['nullable', 'string', 'max:300'],
            'attachment'    => ['nullable', 'file', 'max:5120'],
        ]);

        $type = $data['transfer_type'] ?? $req->transfer_type;

        if ($type === 'tf-val') {
            $cap = $this->computeMaxTransfer(
                $req->from_asset_uuid,
                Carbon::parse($data['actual_date'])
            );

            if ((float) $data['amount'] > $cap['remaining']) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount exceeds max allowed for that month (tf-val).',
                ]);
            }
        }

        $path = $req->attachment_path;
        if ($r->hasFile('attachment')) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $path = $r->file('attachment')->store('depr_transfer_attachments', 'public');
        }

        $req->transfer_type   = $type;
        $req->amount          = $data['amount'];
        $req->actual_date     = $data['actual_date'];
        $req->note            = $data['note'] ?? null;
        $req->attachment_path = $path;
        $req->save();

        return response()->json([
            'ok'      => true,
            'message' => 'Transfer request updated.',
        ]);
    }
    protected function canReadTransferRequests(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('TRANSFER', 'R');
    }

    protected function canCreateTransferRequests(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('TRANSFER', 'C');
    }

    protected function canUpdateTransferRequests(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('TRANSFER', 'U');
    }

    protected function canApproveTransferRequests(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('TRANSFER', 'APR');
    }

    protected function canDeleteTransferRequests(): bool
    {
        $u = auth()->user();
        return $u && $u->hasAction('TRANSFER', 'D');
    }
}
