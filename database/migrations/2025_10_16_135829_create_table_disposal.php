<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets_disposals', function (Blueprint $t) {
            $t->uuid('uuid')->primary();
            $t->uuid('asset_uuid')->index();
            $t->string('disposal_code', 64)->unique();

            $t->string('target_status', 32);

            $t->string('kode_status', 32)->index();

            $t->text('note')->nullable();

            $t->string('file_path', 255)->nullable();

            $t->string('pic_request_uid', 64);
            $t->string('pic_approve_uid', 64)->nullable();

            $t->timestamps();
            $t->softDeletes();
            $t->foreign('asset_uuid')->references('uuid')->on('assets')->cascadeOnDelete();
            $t->index('asset_uuid');
            $t->index('kode_status');
        });
    }

    public function down(): void {
        Schema::dropIfExists('assets_disposals');
    }
};