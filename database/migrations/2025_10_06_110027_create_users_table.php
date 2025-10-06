<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();     // <-- primary login identity
            $table->string('name');
            $table->string('email')->nullable()->unique(); // optional for LDAP/static admin
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');               // random hash; never the LDAP password
            $table->rememberToken();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
