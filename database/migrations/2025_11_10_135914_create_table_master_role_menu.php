<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_role_menu', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->string('role_kode', 50);   
            $table->string('menu_kode', 50); 

            $table->jsonb('actions')->default(DB::raw("'[]'::jsonb"));

            $table->boolean('status')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->unique(['role_kode', 'menu_kode'], 'master_role_menu_role_menu_unique');

            $table->foreign('role_kode')
                ->references('kode')
                ->on('master_role')
                ->cascadeOnDelete();

            $table->foreign('menu_kode')
                ->references('kode')
                ->on('master_menu')
                ->cascadeOnDelete();
        });

        DB::statement("CREATE INDEX master_role_menu_actions_gin ON master_role_menu USING GIN (actions jsonb_path_ops)");
    }

    public function down(): void
    {
        Schema::dropIfExists('master_role_menu');
    }
};
