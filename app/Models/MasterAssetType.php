<?php

namespace App\Models;

use App\Services\AssetReferenceGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

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
        static::deleting(function (self $model) {
            if (AssetReferenceGuard::isUsed('master_asset_type', $model->kode)) {
                throw ValidationException::withMessages([
                    'delete' => "Cannot delete '{$model->name}' ({$model->kode}) because it is used by Assets.",
                ]);
            }

            if ($model->categories()->exists()) {
                $action = $model->isForceDeleting() ? 'delete' : 'archive';
                throw ValidationException::withMessages([
                    'delete' => "Cannot {$action} category '{$model->name}' because Category entries still exist.",
                ]);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
