<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets_depr_yearly', function (Blueprint $t) {
            $t->uuid('uuid')->primary();

            $t->uuid('asset_uuid');
            $t->integer('fiscal_year'); 

            $t->decimal('opening_balance', 18, 2)->default(0);
            $t->decimal('total_additions', 18, 2)->default(0);   
            $t->decimal('depr_expense_year', 18, 2)->default(0);
            $t->decimal('adjustment_depreciation_year', 18, 2)->default(0);
            $t->decimal('accumulated_depr_end', 18, 2)->default(0); 
            $t->decimal('ending_balance_year', 18, 2)->default(0);  

            $t->timestamps();

            $t->unique(['asset_uuid','fiscal_year']);
            $t->foreign('asset_uuid')->references('uuid')->on('assets');
            $t->index(['fiscal_year']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('assets_depr_yearly');
    }
};
