<?php

namespace App\Models;

use App\Services\AssetReferenceGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class MasterUserCode extends Model
{
    use SoftDeletes;

    protected $table = 'master_user_code';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode',
        'department',
        'description',
        'status',
        'kode_division'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
    
    protected static function booted(): void
    {
        static::deleting(function (self $model) {
            if (AssetReferenceGuard::isUsed('master_user_code', $model->kode)) {
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
    
    public function division()
    {
        return $this->belongsTo(MasterDivision::class, 'kode_division', 'kode');
    }
}
