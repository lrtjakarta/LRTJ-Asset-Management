<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Drop the old UNIQUE (parent only)
        // If your name differs, adjust it. Laravel's default is table_column_unique.
        Schema::table('assets', function (Blueprint $t) {
            $t->dropUnique('assets_asset_number_parent_unique');
        });

        // 2) Ensure composite UNIQUE on (parent, child)
        // Use raw with IF NOT EXISTS to be safe on repeatable deploys.
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS assets_parent_child_unique
            ON assets (asset_number_parent, asset_number_child)
        ");
    }

    public function down(): void
    {
        // Revert: drop composite, recreate old single unique
        DB::statement("DROP INDEX IF EXISTS assets_parent_child_unique");

        Schema::table('assets', function (Blueprint $t) {
            $t->unique('asset_number_parent', 'assets_asset_number_parent_unique');
        });
    }
};