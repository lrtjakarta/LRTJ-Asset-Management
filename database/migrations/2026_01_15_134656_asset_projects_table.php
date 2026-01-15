<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_projects', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('name', 150);
            $t->string('code', 50)->nullable()->unique();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_projects');
    }
};
