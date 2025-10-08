<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // one row per group code -> parent sequence
        Schema::create('asset_group_counters', function (Blueprint $t) {
            $t->string('group_code', 50)->primary();
            $t->unsignedBigInteger('last_parent_seq')->default(0);
            $t->timestampsTz();
        });

        // one row per parent code -> child sequence
        Schema::create('asset_parent_counters', function (Blueprint $t) {
            $t->string('parent_code', 100)->primary(); // e.g., AT123000001
            $t->unsignedBigInteger('last_child_seq')->default(0);
            $t->timestampsTz();
        });

        // (optional) hard uniqueness on numbers in assets table
        Schema::table('assets', function (Blueprint $t) {
            $t->unique(['asset_number_parent']);
            // $t->unique(['asset_number_parent','asset_number_child']);
            // $t->unique(['asset_code']);
        });
    }

    public function down(): void {
        Schema::table('assets', function (Blueprint $t) {
            $t->dropUnique(['assets_asset_number_parent_unique']);
            // $t->dropUnique(['assets_asset_number_parent_asset_number_child_unique']);
            // $t->dropUnique(['assets_asset_code_unique']);
        });
        Schema::dropIfExists('asset_parent_counters');
        Schema::dropIfExists('asset_group_counters');
    }
};
