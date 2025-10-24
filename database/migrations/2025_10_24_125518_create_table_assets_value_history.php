<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets_value_history', function (Blueprint $t) {
            $t->uuid('uuid')->primary();
            $t->uuid('asset_uuid')->index();
            $t->json('before_payload')->nullable();
            $t->json('after_payload');

            $t->string('pic_request_uid', 100)->nullable()->index();
            $t->string('note', 1000)->nullable();

            $t->timestamps();
            $t->softDeletes();

            $t->foreign('asset_uuid')->references('uuid')->on('assets');
        });
    }
    public function down(): void {
        Schema::dropIfExists('assets_values_history');
    }
};
