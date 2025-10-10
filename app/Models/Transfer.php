<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transfer extends Model
{
    use SoftDeletes;

    protected $table = 'assets_transfers';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid', 'asset_uuid', 'transfer_code', 'type',
        'before', 'after', 'kode_status', 'note','pic_request_uid','pic_approve_uid',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (! $m->uuid) $m->uuid = (string) Str::uuid();
        });
    }

    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_uuid', 'uuid');
    }

    public function status()
    {
        return $this->belongsTo(MasterStatus::class, 'kode_status', 'kode');
    }
}
