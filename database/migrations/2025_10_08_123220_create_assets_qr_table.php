<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_qr', function (Blueprint $t) {
            $t->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->uuid('asset_uuid');
            $t->text('qr_data');            // use asset_code or a deep-link URL
            $t->text('image_path')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestampTz('generated_at')->useCurrent();
            $t->timestampsTz();

            $t->foreign('asset_uuid')->references('uuid')->on('assets')->onDelete('cascade');
            $t->index('asset_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_qr');
    }
};
