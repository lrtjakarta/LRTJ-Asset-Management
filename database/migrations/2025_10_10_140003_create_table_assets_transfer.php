<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_transfers', function (Blueprint $t) {
            // ensure column exists
            if (! Schema::hasColumn('asset_transfers', 'kode_status')) {
                $t->string('kode_status', 16)->index();
            }
            $t->foreign('kode_status')
              ->references('kode')->on('master_status')
              ->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('asset_transfers', function (Blueprint $t) {
            $t->dropForeign(['kode_status']);
        });
    }
};
