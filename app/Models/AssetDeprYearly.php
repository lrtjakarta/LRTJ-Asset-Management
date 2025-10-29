<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AssetDeprYearly extends Model
{
    use HasUuids;

    protected $table = 'assets_depr_yearly';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid','fiscal_year',
        'opening_balance','total_additions','depr_expense_year','adjustment_depreciation_year',
        'accumulated_depr_end','ending_balance_year',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'total_additions' => 'decimal:2',
        'depr_expense_year' => 'decimal:2',
        'adjustment_depreciation_year' => 'decimal:2',
        'accumulated_depr_end' => 'decimal:2',
        'ending_balance_year' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_uuid');
    }
}
