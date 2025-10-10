<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('assets_transfers', function (Blueprint $t) {
            $t->string('pic_request_uid', 190)->after('type');
            $t->string('pic_approve_uid', 190)->nullable()->after('pic_request_uid');
            $t->string('kode_status', 64)->nullable()->change(); // was required earlier
        });
    }
    public function down(): void {
        Schema::table('assets_transfers', function (Blueprint $t) {
            $t->dropColumn(['pic_request_uid','pic_approve_uid']);
        });
    }
};
