<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Trim to avoid 'KD-1 ' vs 'KD-1'
        DB::statement("UPDATE master_category SET kode = TRIM(kode) WHERE kode IS NOT NULL");
        // Will fail if duplicates exist — resolve first.
        DB::statement("ALTER TABLE master_category ADD CONSTRAINT master_category_kode_unique_all UNIQUE (kode)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE master_category DROP CONSTRAINT IF EXISTS master_category_kode_unique_all");
    }
};
