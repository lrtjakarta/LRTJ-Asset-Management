<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        Schema::create('master_category_2', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('kode', 50);
            $table->string('name', 191);
            $table->boolean('status')->default(true);

            // FK to master_category.kode (must be UNIQUE there)
            $table->string('kode_category', 50);

            $table->timestamps();
            $table->softDeletes();

            $table->index('kode');
            $table->index('kode_category', 'idx_master_category2_kode_category');

            $table->foreign('kode_category', 'fk_cat2_category_kode')
                ->references('kode')->on('master_category')
                ->onUpdate('cascade')
                ->onDelete('restrict'); // prevent delete of parent if children exist
        });

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS master_category_2_kode_unique_active
            ON master_category_2 (kode)
            WHERE deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('master_category_2', function (Blueprint $table) {
            $table->dropForeign('fk_cat2_category_kode');
        });
        Schema::dropIfExists('master_category_2');
    }
};
