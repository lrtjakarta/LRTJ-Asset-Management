<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetDeprMonthClosing extends Model
{
    protected $table = 'assets_depr_month_closings';

    protected $fillable = [
        'period',
        'row_count',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'period' => 'date',
        'processed_at' => 'datetime',
    ];
}
