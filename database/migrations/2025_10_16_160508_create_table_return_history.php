<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_history', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('asset_uuid')->index();
            $table->string('source_type', 16);
            $table->uuid('source_id');
            $table->string('source_code', 64)->nullable();
            $table->text('note')->nullable();
            $table->string('pic_request_uid', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['asset_uuid', 'created_at']);
            $table->index(['source_type', 'source_id']);
            $table->index('source_code');
        });

        try {
            DB::statement("
                ALTER TABLE return_history
                ADD CONSTRAINT return_history_source_type_chk
                CHECK (source_type IN ('transfer', 'disposal'))
            ");
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE return_history DROP CONSTRAINT IF EXISTS return_history_source_type_chk");
        } catch (\Throwable $e) {
        }

        Schema::dropIfExists('return_history');
    }
};
