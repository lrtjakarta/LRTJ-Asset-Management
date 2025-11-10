<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_menu', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('kode', 50)->unique();
            $table->string('name', 191);

            $table->string('route', 191)->nullable();
            $table->string('url', 191)->nullable();

            $table->uuid('parent_uuid')->nullable();

            $table->integer('sort_order')->default(0);

            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_menu');
    }
};
