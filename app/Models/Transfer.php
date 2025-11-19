<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Transfer extends Model
{
    use SoftDeletes;

    protected $table = 'assets_transfers';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'asset_uuid',
        'transfer_code',
        'type',
        'before',
        'after',
        'flow', 
        'kode_status',
        'note',
        'pic_request_uid',
        'pic_approve_uid',
        'file_path',
        'file_name',
        'file_mime',
        'file_size',
        'flow_file_path',
        'flow_file_name',
        'flow_file_mime',
        'flow_file_size',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
        'flow'       => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (! $m->uuid) $m->uuid = (string) Str::uuid();
        });
        static::forceDeleted(function (self $t) {
            if ($t->file_path) Storage::disk('public')->delete($t->file_path);
        });
    }
    public function getRouteKeyName()
    {
        return 'uuid';
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
