<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ensure UUID generator is available (Postgres)
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        // 1) master_asset_type
        Schema::create('master_asset_type', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('kode', 50);
            $table->string('name', 191);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // FK requires a FULL UNIQUE (not partial)
            $table->unique('kode', 'master_asset_type_kode_unique_all');
        });

        // 2) master_category (FK → master_asset_type.kode)
        Schema::create('master_category', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('kode', 50);
            $table->string('name', 191);
            $table->string('kode_asset_type', 50);
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('kode');
            $table->index('kode_asset_type', 'idx_master_category_kode_asset_type');

            $table->foreign('kode_asset_type', 'fk_category_asset_type_kode')
                ->references('kode')->on('master_asset_type')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->unique('kode', 'master_category_kode_unique_active');
        });
    }

    public function down(): void
    {
        // Drop child table first (remove FK), then parent
        Schema::table('master_category', function (Blueprint $table) {
            if (Schema::hasColumn('master_category', 'kode_asset_type')) {
                $table->dropForeign('fk_category_asset_type_kode');
            }
        });

        Schema::dropIfExists('master_category');
        Schema::dropIfExists('master_asset_type');
    }
};
