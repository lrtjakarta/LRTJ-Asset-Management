<?php

namespace App\Models;

use App\Services\AssetReferenceGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MasterCategory extends Model
{
    use SoftDeletes;

    protected $table = 'master_category';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode','name','kode_asset_type', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function categories2(): HasMany
    {
        return $this->hasMany(MasterCategory2::class, 'kode_category', 'kode');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            if (AssetReferenceGuard::isUsed('master_category', $model->kode)) {
                throw ValidationException::withMessages([
                    'delete' => "Cannot delete '{$model->name}' ({$model->kode}) because it is used by Assets.",
                ]);
            }

            if ($model->categories2()->exists()) {
                $action = $model->isForceDeleting() ? 'delete' : 'archive';
                throw ValidationException::withMessages([
                    'delete' => "Cannot {$action} category '{$model->name}' because Category 2 entries still exist.",
                ]);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function assetType()
    {
        return $this->belongsTo(MasterAssetType::class, 'kode_asset_type', 'kode');
    }
}
