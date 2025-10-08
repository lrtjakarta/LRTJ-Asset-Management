<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assets extends Model
{
    use SoftDeletes;

    protected $table = 'assets';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'kode_group_category',
        'asset_code',
        'asset_number_parent',
        'asset_number_child',
        'description',
        'kode_asset_class',
        'kode_status',
        'kode_location',
        'kode_sumber',
    ];

    protected $guarded = ['asset_number_parent', 'asset_number_child', 'asset_code'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /* ---------------- Relations (1:1) ---------------- */
    public function identifiers()
    {
        return $this->hasOne(AssetsIdentifier::class, 'asset_uuid');
    }
    public function classification()
    {
        return $this->hasOne(AssetsClassification::class, 'asset_uuid');
    }
    public function assignment()
    {
        return $this->hasOne(AssetsAssignment::class, 'asset_uuid');
    }
    public function value()
    {
        return $this->hasOne(AssetsValue::class, 'asset_uuid');
    }
    public function documents()
    {
        return $this->hasOne(AssetsDocument::class, 'asset_uuid');
    }
    public function qrs()
    {
        return $this->hasOne(AssetsQr::class, 'asset_uuid');
    }
    public function rfids()
    {
        return $this->hasOne(AssetsRfid::class, 'asset_uuid');
    }

    /* --------- Masters (by kode on assets table) --------- */
    public function assetClass()
    {
        return $this->belongsTo(MasterAssetClass::class, 'kode_asset_class', 'kode');
    }
    public function status()
    {
        return $this->belongsTo(MasterStatus::class, 'kode_status', 'kode');
    }
    public function location()
    {
        return $this->belongsTo(MasterLocation::class, 'kode_location', 'kode');
    }
    public function sumber()
    {
        return $this->belongsTo(MasterSumber::class, 'kode_sumber', 'kode');
    }

    /* ---------------- Convenience scopes ---------------- */
    public function scopeParentChild($q, string $parent, string $child)
    {
        return $q->where('asset_number_parent', $parent)->where('asset_number_child', $child);
    }

    public function scopeWithMasters($q)
    {
        return $q->with([
            'classification.assetType',
            'classification.category',
            'classification.category2',
            'classification.subCategory',
            'assetClass',
            'status',
            'location',
            'sumber',
            'assignment.owner',
            'assignment.user',
            'assignment.maintenance',
            'value.uom',
        ]);
    }

    /* ------------- Helpers ------------- */
    public function getDisplayCodeAttribute(): string
    {
        return $this->asset_number_parent .'-'. $this->asset_number_child; // equals asset_code in your design
    }

    public function activeQr()
    {
        return $this->hasOne(AssetsQr::class, 'asset_uuid')->where('is_active', true);
    }

    public function activeRfid()
    {
        return $this->hasOne(AssetsRfid::class, 'asset_uuid')->where('is_active', true);
    }
}
