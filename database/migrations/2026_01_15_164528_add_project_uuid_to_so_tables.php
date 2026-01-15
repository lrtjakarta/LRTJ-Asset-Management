<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets_transfers', function (Blueprint $t) {
            if (!Schema::hasColumn('assets_transfers', 'project_uuid')) {
                $t->uuid('project_uuid')->nullable()->index();
            }
        });

        Schema::table('assets_disposals', function (Blueprint $t) {
            if (!Schema::hasColumn('assets_disposals', 'project_uuid')) {
                $t->uuid('project_uuid')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets_transfers', function (Blueprint $t) {
            if (Schema::hasColumn('assets_transfers', 'project_uuid')) {
                $t->dropColumn('project_uuid');
            }
        });

        Schema::table('assets_disposals', function (Blueprint $t) {
            if (Schema::hasColumn('assets_disposals', 'project_uuid')) {
                $t->dropColumn('project_uuid');
            }
        });
    }
};
