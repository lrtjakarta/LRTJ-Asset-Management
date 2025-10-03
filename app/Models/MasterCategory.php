<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function categories2(): HasMany
    {
        return $this->hasMany(MasterCategory2::class, 'kode_category', 'kode');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $type) {
            if ($type->isForceDeleting()) {
                // DB will restrict anyway, but we can pre-empt:
                if ($type->categories2()->exists()) {
                    throw new \Exception('Cannot delete: categories 2 still exist.');
                }
                return true;
            }

            // Soft delete path
            if ($type->categories2()->exists()) {
                throw new \Exception('Cannot archive asset type: categories 2 still exist.');
            }
            return true;
        });
    }

    // so routes bind by uuid instead of id
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function assetType()
    {
        return $this->belongsTo(MasterAssetType::class, 'kode_asset_type', 'kode');
    }
}
