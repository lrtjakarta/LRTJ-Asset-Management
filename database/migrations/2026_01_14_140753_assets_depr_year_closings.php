<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets_depr_year_closings', function (Blueprint $table) {
            $table->id();
            $table->integer('fiscal_year')->unique();
            $table->boolean('is_locked')->default(true);

            $table->string('built_by')->nullable();
            $table->timestamp('built_at')->nullable();

            $table->string('rolled_back_by')->nullable();
            $table->timestamp('rolled_back_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_depr_year_closings');
    }
};
