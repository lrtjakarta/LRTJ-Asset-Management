<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsValue extends Model
{
    use SoftDeletes;
    protected $table = 'assets_value';
    protected $primaryKey = 'asset_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid',
        'price',
        'quantity',
        'is_pajak',
        'vat_in',
        'kode_uom',
        'total',
        'useful_life_month',
        'useful_life_year',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'decimal:3',
        'is_pajak' => 'boolean',
        'vat_in' => 'decimal:2',
        'total' => 'decimal:2',
        'useful_life_month' => 'integer',
        'useful_life_year'  => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function asset()   { return $this->belongsTo(Assets::class, 'asset_uuid'); }
    public function uom()     { return $this->belongsTo(MasterUOM::class, 'kode_uom', 'kode'); }
}
