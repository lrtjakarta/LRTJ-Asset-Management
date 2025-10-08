<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_classification', function (Blueprint $t) {
            $t->uuid('asset_uuid')->primary(); // one classification per asset
            $t->string('kode_asset_transaction', 50); // FK master_transaction (A/J...)
            $t->string('kode_asset_type', 50);        // FK master_asset_type (1/2/3..)
            $t->string('kode_category', 50);          // FK master_category
            $t->string('kode_category_2', 50)->nullable(); // FK master_category_2
            $t->string('kode_sub_category', 50)->nullable(); // FK master_sub_category

            $t->timestampsTz();

            $t->foreign('asset_uuid')->references('uuid')->on('assets')->onDelete('cascade');

            $t->foreign('kode_asset_transaction')->references('kode')->on('master_transaction')
              ->onUpdate('cascade')->onDelete('restrict');
            $t->foreign('kode_asset_type')->references('kode')->on('master_asset_type')
              ->onUpdate('cascade')->onDelete('restrict');
            $t->foreign('kode_category')->references('kode')->on('master_category')
              ->onUpdate('cascade')->onDelete('restrict');
            $t->foreign('kode_category_2')->references('kode')->on('master_category_2')
              ->onUpdate('cascade')->onDelete('restrict');
            $t->foreign('kode_sub_category')->references('kode')->on('master_sub_category')
              ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_classification');
    }
};
