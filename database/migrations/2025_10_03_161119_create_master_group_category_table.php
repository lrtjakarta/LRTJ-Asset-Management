<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Needed for gen_random_uuid() on Postgres
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        Schema::create('master_group_category', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('kode', 50);
            $table->string('name', 191);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('kode');
        });

        // Unique among active (non-trashed) rows only
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS master_group_category_kode_unique_active
            ON master_group_category (kode)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS master_group_category_kode_unique_active');
        Schema::dropIfExists('master_group_category');
    }
};
