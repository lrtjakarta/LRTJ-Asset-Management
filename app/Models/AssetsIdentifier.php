<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsIdentifier extends Model
{
    use SoftDeletes;
    protected $table = 'assets_identifiers';
    protected $primaryKey = 'asset_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid',
        'asset_number_maximo',
        'asset_number_dynamic_365',
        'asset_number_internal',
    ];

    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function asset() { return $this->belongsTo(Assets::class, 'asset_uuid'); }
}
