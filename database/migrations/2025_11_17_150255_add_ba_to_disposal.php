<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets_disposals', function (Blueprint $table) {
            $table->string('ba_file_path')->nullable()->after('file_size');
            $table->string('ba_file_name')->nullable();
            $table->string('ba_file_mime')->nullable();
            $table->unsignedBigInteger('ba_file_size')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets_disposals', function (Blueprint $table) {
            $table->dropColumn(['ba_file_path', 'ba_file_name', 'ba_file_mime', 'ba_file_size']);
        });
    }
};
