<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ensure pgcrypto for gen_random_uuid() (safe to run repeatedly)
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        Schema::create('master_sumber', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 191);
            $table->boolean('status')->default(true); // true=active, false=inactive
            $table->timestamps();                     // created_at, updated_at
            $table->softDeletes();                    // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_sumber');
    }
};
