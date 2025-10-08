<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        foreach ([
            'assets_identifiers',
            'assets_classification',
            'assets_assignment',
            'assets_value',
            'assets_document',
            'assets_qr',
            'assets_rfid',
        ] as $table) {
            Schema::table($table, function (Blueprint $t) {
                if (!Schema::hasColumn($t->getTable(), 'deleted_at')) {
                    $t->softDeletesTz();
                }
            });
        }
    }
    public function down(): void {
        foreach ([
            'assets_identifiers',
            'assets_classification',
            'assets_assignment',
            'assets_value',
            'assets_document',
            'assets_qr',
            'assets_rfid',
        ] as $table) {
            Schema::table($table, function (Blueprint $t) {
                if (Schema::hasColumn($t->getTable(), 'deleted_at')) {
                    $t->dropSoftDeletes();
                }
            });
        }
    }
};
