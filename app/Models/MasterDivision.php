<?php

namespace App\Models;

use App\Services\AssetReferenceGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MasterDivision extends Model
{
    use SoftDeletes;

    protected $table = 'master_division';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode','name','status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function usercode(): HasMany
    {
        return $this->hasMany(MasterUserCode::class, 'kode_division', 'kode');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            if (AssetReferenceGuard::isUsed('master_division', $model->kode)) {
                throw ValidationException::withMessages([
                    'delete' => "Cannot delete '{$model->name}' ({$model->kode}) because it is used by Assets.",
                ]);
            }

            if ($model->usercode()->exists()) {
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
