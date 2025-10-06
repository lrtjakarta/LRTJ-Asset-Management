<?php
// database/migrations/2025_10_06_000000_create_master_user_code_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        Schema::create('master_user_code', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('kode', 50);        // natural key for FKs
            $table->string('department', 191);
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('department');
            $table->index('status');

            // FULL UNIQUE (required by Postgres FKs)
            $table->unique('kode', 'master_user_code_kode_unique_all');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_user_code');
    }
};
