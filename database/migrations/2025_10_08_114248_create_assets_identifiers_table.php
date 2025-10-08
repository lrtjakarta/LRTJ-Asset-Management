<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_identifiers', function (Blueprint $t) {
            $t->uuid('asset_uuid')->primary(); // one classification per asset
            $t->string('asset_number_maximo', 120)->nullable();
            $t->string('asset_number_dynamic_365', 120)->nullable();
            $t->string('asset_number_internal', 120)->nullable();

            $t->timestampsTz();

            $t->foreign('asset_uuid')->references('uuid')->on('assets')->onDelete('cascade');
            $t->index('asset_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_identifiers');
    }
};
