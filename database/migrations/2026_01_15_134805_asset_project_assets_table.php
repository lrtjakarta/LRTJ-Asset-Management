<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_project_assets', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('project_id');
            $t->uuid('asset_uuid');
            $t->timestamps();

            $t->foreign('project_id')->references('id')->on('asset_projects');
            $t->foreign('asset_uuid')->references('uuid')->on('assets');

            $t->unique(['project_id', 'asset_uuid']);
            $t->index(['asset_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_project_assets');
    }
};
