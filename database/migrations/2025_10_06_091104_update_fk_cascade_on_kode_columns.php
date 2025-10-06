<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Trim whitespace to reduce accidental dupes like "KD-1 " vs "KD-1"
        $tables = [
            'master_sumber',
            'master_transaction',
            'master_asset_type',
            'master_category',
            'master_category_2',
            'master_sub_category',
            'master_location',
            'master_group_category',
            'master_uom',
            'master_status',
            'master_asset_class',
        ];

        foreach ($tables as $t) {
            DB::statement("UPDATE {$t} SET kode = TRIM(kode) WHERE kode IS NOT NULL");

            // Make kode NOT NULL if needed (optional but recommended)
            // Comment this out if your app still allows nulls.
            try {
                DB::statement("ALTER TABLE {$t} ALTER COLUMN kode SET NOT NULL");
            } catch (\Throwable $e) {
                // ignore if already NOT NULL or column missing
            }

            // Add a FULL UNIQUE constraint (required by Postgres FK)
            $conName = "{$t}_kode_unique_all";
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_constraint WHERE conname = '{$conName}'
                    ) THEN
                        ALTER TABLE {$t} ADD CONSTRAINT {$conName} UNIQUE (kode);
                    END IF;
                END$$;
            ");
        }
    }

    public function down(): void
    {
        $tables = [
            'master_sumber',
            'master_transaction',
            'master_asset_type',
            'master_category',
            'master_category_2',
            'master_sub_category',
            'master_location',
            'master_group_category',
            'master_uom',
            'master_status',
            'master_asset_class',
        ];

        foreach ($tables as $t) {
            $conName = "{$t}_kode_unique_all";
            DB::statement("
                DO $$
                BEGIN
                    IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = '{$conName}') THEN
                        ALTER TABLE {$t} DROP CONSTRAINT {$conName};
                    END IF;
                END$$;
            ");
        }
    }
};
