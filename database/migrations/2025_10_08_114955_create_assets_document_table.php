<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_document', function (Blueprint $t) {
            $t->uuid('asset_uuid')->primary();
            $t->string('no_po_perjanjian_spk', 120)->nullable();
            $t->string('nota_referensi', 120)->nullable();
            $t->string('no_document', 120)->nullable();
            $t->timestampsTz();

            $t->foreign('asset_uuid')->references('uuid')->on('assets')->onDelete('cascade');
            $t->index('asset_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_document');
    }
};
