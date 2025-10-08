<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        Schema::create('assets', function (Blueprint $t) {
            $t->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));

            // As-is from Excel
            $t->string('kode_group_category', 50); // from asset_classification (kept as text)
            $t->string('asset_code', 120)->unique(); // (asset_number_parent + asset_number_child)
            $t->string('asset_number_parent', 50);   // parent sequence source (from kode_group_category)
            $t->string('asset_number_child', 2);     // "00", "01", ... depends on parent
            $t->text('description');

            // Refs (konversi from sheet)
            $t->string('kode_asset_class', 50)->nullable(); // FK master_asset_class
            $t->string('kode_status', 50)->nullable();      // FK master_status
            $t->string('kode_location', 50)->nullable();    // FK master_location
            $t->string('kode_sumber', 50)->nullable();      // FK master_sumber

            $t->timestampsTz();
            $t->softDeletesTz();

            // FKs to masters by kode (ON UPDATE CASCADE, ON DELETE RESTRICT)
            $t->foreign('kode_asset_class')->references('kode')->on('master_asset_class')
              ->onUpdate('cascade')->onDelete('restrict');
            $t->foreign('kode_status')->references('kode')->on('master_status')
              ->onUpdate('cascade')->onDelete('restrict');
            $t->foreign('kode_location')->references('kode')->on('master_location')
              ->onUpdate('cascade')->onDelete('restrict');
            $t->foreign('kode_sumber')->references('kode')->on('master_sumber')
              ->onUpdate('cascade')->onDelete('restrict');

            // Optional unique guard: avoid duplicate numbering per parent+child if you want
            $t->unique(['asset_number_parent', 'asset_number_child']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
