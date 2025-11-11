<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('master_menu', function (Blueprint $table) {
            $table->json('actions')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('master_menu', function (Blueprint $table) {
            $table->dropColumn('actions');
        });
    }
};

