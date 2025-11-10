<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_role', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('gen_random_uuid()'));

            $table->unsignedBigInteger('user_id');
            $table->string('role_kode', 50);

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['user_id', 'role_kode'], 'user_role_user_role_unique');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('role_kode')
                ->references('kode')
                ->on('master_role')
                ->cascadeOnDelete();
        });

        // optional: backfill from users.role_kode if you already used it
        DB::table('users')
            ->whereNotNull('role_kode')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('user_role')->updateOrInsert(
                        ['user_id' => $user->id, 'role_kode' => $user->role_kode],
                        ['uuid' => DB::raw('gen_random_uuid()')]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
