<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AssetDeprPolicy extends Model
{
    use HasUuids;

    protected $table = 'assets_depr_policy';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    public const METHOD_SL = 'SL';
    public const CONVENTION_PRORATA_MONTH = 'PRORATA_MONTH';
    public const CONVENTION_FULL_MONTH    = 'FULL_MONTH';
    public const CONVENTION_HALF_MONTH    = 'HALF_MONTH';
    public const CONVENTION_PRORATA_DAILY = 'PRORATA_DAILY';

    protected $fillable = [
        'asset_uuid','method','useful_life_months','salvage_value','depr_start_date',
        'convention','cutoff_day','start_rule','is_active'
    ];

    protected $casts = [
        'salvage_value' => 'decimal:2',
        'depr_start_date' => 'date',
        'is_active' => 'boolean',
        'cutoff_day' => 'integer',
        'useful_life_months' => 'integer',
    ];

    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_uuid');
    }
}
