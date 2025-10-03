<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCategory extends Model
{
    use SoftDeletes;

    protected $table = 'master_category';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode','name','kode_asset_type'];

    protected $casts = [
        'status' => 'boolean',
    ];

    // so routes bind by uuid instead of id
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function assetType()
    {
        // belongsTo by kode (owner key = master_asset_type.kode)
        return $this->belongsTo(MasterAssetType::class, 'kode_asset_type', 'kode');
    }
}
