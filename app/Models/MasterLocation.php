<?php

namespace App\Models;

use App\Services\AssetReferenceGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MasterLocation extends Model
{
    use SoftDeletes;

    protected $table = 'master_location';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode','name','status'];

    protected $casts = [
        'status' => 'boolean',
    ];
    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            if (AssetReferenceGuard::isUsed('master_location', $model->kode)) {
                throw ValidationException::withMessages([
                    'delete' => "Cannot delete '{$model->name}' ({$model->kode}) because it is used by Assets.",
                ]);
            }

        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
