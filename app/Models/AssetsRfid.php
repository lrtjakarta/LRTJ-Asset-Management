<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsRfid extends Model
{
    
    use SoftDeletes;
    protected $table = 'assets_rfid';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid', 'epc', 'tag_type',
        'encoded_at', 'is_active', 'deactivated_at', 'note',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'encoded_at'     => 'datetime',
        'deactivated_at' => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function asset() { return $this->belongsTo(Assets::class, 'asset_uuid'); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
