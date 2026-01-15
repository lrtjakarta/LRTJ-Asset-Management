<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // --- Ensure asset_projects.uuid exists before this ---
        // Pivot must become: (project_uuid uuid, asset_uuid uuid, timestamps)

        // 1) add project_uuid if missing
        Schema::table('asset_project_assets', function (Blueprint $t) {
            if (!Schema::hasColumn('asset_project_assets', 'project_uuid')) {
                $t->uuid('project_uuid')->nullable()->index();
            }
        });

        // 2) backfill project_uuid from project_id (if project_id exists)
        if (Schema::hasColumn('asset_project_assets', 'project_id')) {
            DB::statement("
                UPDATE asset_project_assets pa
                SET project_uuid = p.uuid
                FROM asset_projects p
                WHERE pa.project_id = p.id
                  AND pa.project_uuid IS NULL
            ");
        }

        // 3) if there are still nulls, you HAVE orphan rows -> delete them
        DB::statement("DELETE FROM asset_project_assets WHERE project_uuid IS NULL");

        // 4) delete duplicates for uuid pair
        DB::statement("
            DELETE FROM asset_project_assets a
            USING asset_project_assets b
            WHERE a.ctid < b.ctid
              AND a.project_uuid = b.project_uuid
              AND a.asset_uuid = b.asset_uuid
        ");

        // 5) drop old project_id column (this removes NOT NULL issue forever)
        if (Schema::hasColumn('asset_project_assets', 'project_id')) {
            Schema::table('asset_project_assets', function (Blueprint $t) {
                $t->dropColumn('project_id');
            });
        }

        // 6) enforce NOT NULL on project_uuid
        Schema::table('asset_project_assets', function (Blueprint $t) {
            $t->uuid('project_uuid')->nullable(false)->change();
        });

        // 7) add unique constraint required for upsert
        Schema::table('asset_project_assets', function (Blueprint $t) {
            $t->unique(['project_uuid', 'asset_uuid'], 'asset_project_assets_uniq_project_asset');
        });
    }

    public function down(): void
    {
        Schema::table('asset_project_assets', function (Blueprint $t) {
            $t->dropUnique('asset_project_assets_uniq_project_asset');
        });
    }
};
