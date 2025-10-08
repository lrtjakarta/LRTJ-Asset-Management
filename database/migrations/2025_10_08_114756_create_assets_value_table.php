<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_value', function (Blueprint $t) {
            $t->uuid('asset_uuid')->primary();
            $t->decimal('price', 18, 2)->nullable();
            $t->decimal('quantity', 18, 3)->nullable(); // allow fractional qty if needed
            $t->boolean('is_pajak')->default(true);     // default yes
            $t->decimal('vat_in', 18, 2)->nullable();   // price * env('NILAI_PAJAK')%
            $t->string('kode_uom', 50)->nullable();     // FK master_uom
            $t->decimal('total', 18, 2)->nullable();    // price*qty + vat
            $t->integer('useful_life_month')->nullable();
            $t->decimal('useful_life_year', 6, 2)->nullable(); // month/12 with 2 decimals
            $t->timestampsTz();

            $t->foreign('asset_uuid')->references('uuid')->on('assets')->onDelete('cascade');
            $t->foreign('kode_uom')->references('kode')->on('master_uom')
              ->onUpdate('cascade')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_value');
    }
};
