<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_assignment', function (Blueprint $t) {
            $t->uuid('asset_uuid')->primary(); // current assignment for asset
            $t->string('asset_owner', 50)->nullable();        // master_user_code.kode
            $t->string('asset_user', 50)->nullable();         // master_user_code.kode
            $t->string('asset_maintenance', 50)->nullable();  // master_user_code.kode
            $t->timestampsTz();

            $t->foreign('asset_uuid')->references('uuid')->on('assets')->onDelete('cascade');

            foreach (['asset_owner','asset_user','asset_maintenance'] as $col) {
                $t->foreign($col)->references('kode')->on('master_user_code')
                  ->onUpdate('cascade')->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_assignment');
    }
};
