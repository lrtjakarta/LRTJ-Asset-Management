<?php

namespace App\Models;

use App\Services\AssetReferenceGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MasterAssetClass extends Model
{

    use SoftDeletes;

    protected $table = 'master_asset_class';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode', 'name', 'status', 'kode_transaction'];

    protected $casts = [
        'status' => 'boolean',
    ];


    
    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            // Block delete (soft or force) if used by Assets
            if (AssetReferenceGuard::isUsed('master_asset_class', $model->kode)) {
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
    
    public function transaction()
    {
        return $this->belongsTo(MasterTransaction::class, 'kode_transaction', 'kode');
    }
}
