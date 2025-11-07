<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AssetDeprMonthly extends Model
{
    use HasUuids;

    protected $table = 'assets_depr_ledger_monthly';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid','period',
        'opening_balance','additions','transfers_in','transfers_out','disposals','adjustment_value',
        'adjustment_depreciation','depr_expense','accumulated_depr_end','ending_balance', 'depr_code'
    ];

    protected $casts = [
        'period' => 'date',
        'opening_balance' => 'decimal:2',
        'additions' => 'decimal:2',
        'transfers_in' => 'decimal:2',
        'transfers_out' => 'decimal:2',
        'disposals' => 'decimal:2',
        'adjustment_value' => 'decimal:2',
        'adjustment_depreciation' => 'decimal:2',
        'depr_expense' => 'decimal:2',
        'accumulated_depr_end' => 'decimal:2',
        'ending_balance' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_uuid');
    }
}
