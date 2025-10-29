<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets_depr_movements', function (Blueprint $t) {
            $t->uuid('uuid')->primary();

            $t->uuid('asset_uuid');                       
            $t->date('period');                           

            $t->enum('category', [
                'ADDITION',              
                'TRANSFER_IN',
                'TRANSFER_OUT',
                'DISPOSAL',
                'ADJUSTMENT_VALUE',   
                'ADJUSTMENT_DEPRECIATION'
            ]);

            $t->decimal('amount', 18, 2);               

            $t->date('depr_start_period')->nullable();       

            $t->uuid('group_uuid')->nullable();             
            $t->string('source_type', 64)->nullable();    
            $t->uuid('source_uuid')->nullable();       
            $t->string('note', 300)->nullable();

            $t->timestamps();

            $t->foreign('asset_uuid')->references('uuid')->on('assets');
            $t->index(['asset_uuid','period']);
            $t->index(['period']);
            $t->index(['category']);
            $t->index(['source_type','source_uuid']);
            $t->index(['group_uuid']);
            $t->index(['depr_start_period']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('assets_depr_movements');
    }
};
