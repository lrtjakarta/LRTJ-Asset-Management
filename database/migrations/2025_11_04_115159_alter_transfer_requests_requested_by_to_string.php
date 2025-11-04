<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets_depr_transfer_requests', function (Blueprint $table) {
            $table->string('requested_by', 100)->nullable()->change();
            $table->string('approved_by', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assets_depr_transfer_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by')->nullable()->change();
            $table->unsignedBigInteger('approved_by')->nullable()->change();
        });
    }
};