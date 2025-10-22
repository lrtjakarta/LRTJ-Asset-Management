<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsClassification extends Model
{
    use SoftDeletes;
    protected $table = 'assets_classification';
    protected $primaryKey = 'asset_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid',
        'kode_asset_transaction',
        'kode_asset_type',
        'kode_category',
        'kode_category_2',
        'kode_sub_category',
    ];

    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function asset()        { return $this->belongsTo(Assets::class, 'asset_uuid'); }

    // Masters by kode
    public function transaction()  { return $this->belongsTo(MasterTransaction::class, 'kode_asset_transaction', 'kode'); }
    public function assetType()    { return $this->belongsTo(MasterAssetType::class, 'kode_asset_type', 'kode'); }
    public function category()     { return $this->belongsTo(MasterCategory::class, 'kode_category', 'kode'); }
    public function category2()    { return $this->belongsTo(MasterCategory2::class, 'kode_category_2', 'kode'); }
    public function subCategory()    { return $this->belongsTo(MasterSubCategory::class, 'kode_sub_category', 'kode'); }
}
