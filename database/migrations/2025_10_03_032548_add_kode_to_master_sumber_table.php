<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('master_sumber', function (Blueprint $table) {
            $table->string('kode', 50)->nullable(); // temporarily nullable to backfill
            $table->index('kode'); // simple index; we’ll add partial unique below
        });

        // Make kode unique for *active* rows only (exclude soft-deleted)
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS master_sumber_kode_unique_active
            ON master_sumber (kode)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        // drop partial index then column
        DB::statement('DROP INDEX IF EXISTS master_sumber_kode_unique_active');

        Schema::table('master_sumber', function (Blueprint $table) {
            $table->dropIndex(['kode']);
            $table->dropColumn('kode');
        });
    }
};
