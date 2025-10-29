<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AssetDeprMovement extends Model
{
    use HasUuids;

    protected $table = 'assets_depr_movements';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    // Categories
    public const ADDITION               = 'ADDITION';
    public const TRANSFER_IN            = 'TRANSFER_IN';
    public const TRANSFER_OUT           = 'TRANSFER_OUT';
    public const DISPOSAL               = 'DISPOSAL';
    public const ADJUSTMENT_VALUE       = 'ADJUSTMENT_VALUE';
    public const ADJUSTMENT_DEPRECIATION= 'ADJUSTMENT_DEPRECIATION';

    protected $fillable = [
        'asset_uuid','period','category','amount',
        'depr_start_period','group_uuid','source_type','source_uuid','note'
    ];

    protected $casts = [
        'period' => 'date',
        'depr_start_period' => 'date',
        'amount' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_uuid');
    }
}
