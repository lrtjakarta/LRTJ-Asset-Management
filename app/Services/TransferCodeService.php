<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class TransferCodeService
{
    /**
     * Build: TRF-{ASSET_CODE}-{YYYYMMDD}-{#####}
     * Sequence restarts per asset_code per day.
     */
    public function nextForAsset(string $assetCode): string
    {
        $date = now('Asia/Jakarta')->format('Ymd');

        return DB::transaction(function () use ($assetCode, $date) {
            DB::statement("
                CREATE TABLE IF NOT EXISTS transfer_counters (
                    asset_code TEXT NOT NULL,
                    ymd        TEXT NOT NULL,
                    last_seq   INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                    PRIMARY KEY (asset_code, ymd)
                )
            ");

            // ensure row exists
            DB::statement("
                INSERT INTO transfer_counters(asset_code, ymd, last_seq)
                VALUES (?, ?, 0)
                ON CONFLICT (asset_code, ymd) DO NOTHING
            ", [$assetCode, $date]);

            $row = DB::table('transfer_counters')
                ->where('asset_code', $assetCode)
                ->where('ymd', $date)
                ->lockForUpdate()
                ->first();

            $next = (int) $row->last_seq + 1;

            DB::table('transfer_counters')
                ->where('asset_code', $assetCode)
                ->where('ymd', $date)
                ->update(['last_seq' => $next, 'updated_at' => now()]);

            return sprintf('TRF-%s-%s-%05d', $assetCode, $next);
        });
    }
}