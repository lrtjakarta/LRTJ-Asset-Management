<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_depr_transfer_requests', function (Blueprint $table) {
            $table->uuid('uuid')->primary();

            $table->uuid('from_asset_uuid');
            $table->uuid('to_asset_uuid');
            $table->string('transfer_type', 64)->default('tf-val');

            $table->decimal('amount', 18, 2);
            $table->date('actual_date');

            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('kode_status', 10);
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('group_uuid')->nullable();
            $table->timestamps();

            $table->foreign('from_asset_uuid')->references('uuid')->on('assets');
            $table->foreign('to_asset_uuid')->references('uuid')->on('assets');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_depr_transfer_requests');
    }
};
