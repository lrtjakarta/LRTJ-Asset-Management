<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDeprYearClosing extends Model
{
    protected $table = 'assets_depr_year_closings';

    protected $fillable = [
        'fiscal_year',
        'is_locked',
        'built_by',
        'built_at',
        'rolled_back_by',
        'rolled_back_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'built_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];
}
