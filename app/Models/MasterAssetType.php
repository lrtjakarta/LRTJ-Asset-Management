<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAssetType extends Model
{
    use SoftDeletes;

    protected $table = 'master_asset_type';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode','name','status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(MasterCategory::class, 'kode_asset_type', 'kode');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $type) {
            if ($type->isForceDeleting()) {
                // DB will restrict anyway, but we can pre-empt:
                if ($type->categories()->exists()) {
                    throw new \Exception('Cannot delete: categories still exist.');
                }
                return true;
            }

            // Soft delete path
            if ($type->categories()->exists()) {
                throw new \Exception('Cannot archive asset type: categories still exist.');
            }
            return true;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
