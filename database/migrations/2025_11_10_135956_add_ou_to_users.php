<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ou', 100)->nullable()->after('email');

            $table->string('role_kode', 50)->nullable()->after('ou');

            $table->foreign('role_kode')
                ->references('kode')
                ->on('master_role')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_kode']);
            $table->dropColumn(['ou', 'role_kode']);
        });
    }
};
