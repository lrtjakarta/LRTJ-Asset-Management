<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets_transfers', function (Blueprint $t) {
            $t->string('file_path', 255)->nullable();
            $t->string('file_name', 255)->nullable();
            $t->string('file_mime', 127)->nullable();
            $t->unsignedBigInteger('file_size')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets_transfers', function (Blueprint $t) {
            $t->dropColumn(['file_path','file_name','file_mime','file_size']);
        });
    }
};

