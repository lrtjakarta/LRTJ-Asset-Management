<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets_disposals', function (Blueprint $table) {
            $table->jsonb('flow')->nullable()->after('before_status');

            $table->string('flow_file_path')->nullable()->after('file_path');
            $table->string('flow_file_name')->nullable()->after('flow_file_path');
            $table->string('flow_file_mime', 100)->nullable()->after('flow_file_name');
            $table->unsignedBigInteger('flow_file_size')->nullable()->after('flow_file_mime');
        });
    }

    public function down(): void
    {
        Schema::table('assets_disposals', function (Blueprint $table) {
            $table->dropColumn([
                'flow',
                'flow_file_path',
                'flow_file_name',
                'flow_file_mime',
                'flow_file_size',
            ]);
        });
    }
};
