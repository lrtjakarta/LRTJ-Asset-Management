<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_rfid', function (Blueprint $t) {
            $t->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $t->uuid('asset_uuid');
            $t->string('epc', 128)->unique(); // EPC/UID; fill when encoded
            $t->string('tag_type', 16)->default('NFC'); // UHF/HF/NFC
            $t->timestampTz('encoded_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestampTz('deactivated_at')->nullable();
            $t->text('note')->nullable();
            $t->timestampsTz();

            $t->foreign('asset_uuid')->references('uuid')->on('assets')->onDelete('cascade');
            $t->index('asset_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_rfid_tag');
    }
};
