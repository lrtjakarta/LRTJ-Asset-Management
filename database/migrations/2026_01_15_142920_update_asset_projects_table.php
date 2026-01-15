<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_projects', function (Blueprint $t) {
            if (!Schema::hasColumn('asset_projects', 'uuid')) {
                $t->uuid('uuid')->nullable()->unique();
            }
            if (!Schema::hasColumn('asset_projects', 'status')) {
                $t->string('status', 10)->default('OPEN');
            }
        });

        // backfill uuid
        DB::table('asset_projects')->whereNull('uuid')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $r) {
                DB::table('asset_projects')->where('id', $r->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });

        Schema::table('asset_projects', function (Blueprint $t) {
            $t->uuid('uuid')->nullable(false)->change();
        });

        // pivot change: add project_uuid + backfill from old project_id
        Schema::table('asset_project_assets', function (Blueprint $t) {
            if (!Schema::hasColumn('asset_project_assets', 'project_uuid')) {
                $t->uuid('project_uuid')->nullable()->index();
            }
        });

        // backfill project_uuid from project_id
        DB::statement("
      UPDATE asset_project_assets pa
      SET project_uuid = p.uuid
      FROM asset_projects p
      WHERE pa.project_id = p.id AND pa.project_uuid IS NULL
    ");

        // then (manual step if you want): drop old project_id + change PK
    }

    public function down(): void
    {
        // optional
    }
};
