<?php

namespace App\Models;

use App\Services\AssetChildSequencer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Assets extends Model
{
    use SoftDeletes;

    protected $table = 'assets';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });


        static::deleting(function (self $asset) {
            $rels = [
                'identifiers',
                'classification',
                'assignment',
                'value',
                'documents',
                'qrs',
                'rfids',
                'transfer',
                'disposal',
                'return_history'
            ];

            if ($asset->isForceDeleting()) {
                foreach ($rels as $rel) {
                    $child = $asset->{$rel}()->withTrashed()->first();
                    if ($child) {
                        // delete files if any
                        if (method_exists($child, 'getAttribute')) {
                            $path = $child->getAttribute('image_path') ?? null;
                            if ($path && Storage::disk('public')->exists($path)) {
                                Storage::disk('public')->delete($path);
                            }
                        }
                        $asset->{$rel}()->withTrashed()->forceDelete();
                    }
                }
            } else {
                // Soft delete children
                foreach ($rels as $rel) {
                    $asset->{$rel}()->delete();
                }
            }
        });

        // Restore the children too
        static::restored(function (self $asset) {
            foreach ([
                'identifiers',
                'classification',
                'assignment',
                'value',
                'documents',
                'qrs',
                'rfids',
                'transfer',
                'disposal',
                'return_history'
            ] as $rel) {
                $asset->{$rel}()->withTrashed()->restore();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

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
        'upload_code',
        'notes'
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
    public function transfer()
    {
        return $this->hasMany(Transfer::class, 'asset_uuid');
    }
    public function disposal()
    {
        return $this->hasMany(Disposal::class, 'asset_uuid');
    }
    public function return_history()
    {
        return $this->hasMany(ReturnHistory::class, 'asset_uuid');
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
        return $this->asset_number_parent . '-' . $this->asset_number_child;
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
