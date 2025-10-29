<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets_depr_ledger_monthly', function (Blueprint $t) {
            $t->uuid('uuid')->primary();

            $t->uuid('asset_uuid');
            $t->date('period');

            $t->decimal('opening_balance', 18, 2)->default(0);
            $t->decimal('additions', 18, 2)->default(0);
            $t->decimal('transfers_in', 18, 2)->default(0);
            $t->decimal('transfers_out', 18, 2)->default(0);
            $t->decimal('disposals', 18, 2)->default(0);
            $t->decimal('adjustment_value', 18, 2)->default(0);     

            $t->decimal('adjustment_depreciation', 18, 2)->default(0);   
            $t->decimal('depr_expense', 18, 2)->default(0);            

            $t->decimal('accumulated_depr_end', 18, 2)->default(0); 
            $t->decimal('ending_balance', 18, 2)->default(0);         

            $t->timestamps();

            $t->unique(['asset_uuid','period']);
            $t->foreign('asset_uuid')->references('uuid')->on('assets');
            $t->index(['period']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('assets_depr_ledger_monthly');
    }
};
