<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AssetsQr extends Model
{
    
    use SoftDeletes;
    protected $table = 'assets_qr';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid', 'qr_data', 'image_path',
        'is_active', 'generated_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'generated_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    protected static function booted()
    {
        static::deleting(function ($qr) {
            if ($qr->image_path && Storage::disk('public')->exists($qr->image_path)) {
                Storage::disk('public')->delete($qr->image_path);
            }
        });
    }

    public function asset() { return $this->belongsTo(Assets::class, 'asset_uuid'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
