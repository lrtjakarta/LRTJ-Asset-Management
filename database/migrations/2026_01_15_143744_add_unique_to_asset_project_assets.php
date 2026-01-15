<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_project_assets', function (Blueprint $table) {
            // If you already have duplicates, this will fail.
            // We'll delete duplicates first (below) then add unique.
        });

        DB::statement("
            DELETE FROM asset_project_assets a
            USING asset_project_assets b
            WHERE a.ctid < b.ctid
              AND a.project_uuid = b.project_uuid
              AND a.asset_uuid = b.asset_uuid
        ");

        Schema::table('asset_project_assets', function (Blueprint $table) {
            $table->unique(['project_uuid', 'asset_uuid'], 'asset_project_assets_project_asset_unique');
        });
    }

    public function down(): void
    {
        Schema::table('asset_project_assets', function (Blueprint $table) {
            $table->dropUnique('asset_project_assets_project_asset_unique');
        });
    }
};