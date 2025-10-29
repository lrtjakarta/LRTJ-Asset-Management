<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets_depr_policy', function (Blueprint $t) {
            $t->uuid('uuid')->primary();
            $t->uuid('asset_uuid')->unique();                 
            $t->enum('method', ['SL']);                       
            $t->unsignedInteger('useful_life_months');        
            $t->decimal('salvage_value', 18, 2)->default(0);
            $t->date('depr_start_date');                      
            $t->enum('convention', ['PRORATA_MONTH','FULL_MONTH','HALF_MONTH','PRORATA_DAILY'])
              ->default('PRORATA_MONTH');

            $t->unsignedTinyInteger('cutoff_day')->default(15);
            $t->enum('start_rule', ['CUT_OFF_NEXT_OR_NEXT2'])->default('CUT_OFF_NEXT_OR_NEXT2');

            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->foreign('asset_uuid')->references('uuid')->on('assets');
        });
    }
    public function down(): void {
        Schema::dropIfExists('assets_depr_policy');
    }
};
