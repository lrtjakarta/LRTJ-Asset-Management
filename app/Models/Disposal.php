<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Disposal extends Model
{
    use SoftDeletes;

    protected $table = 'assets_disposals';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'asset_uuid',
        'disposal_code',
        'target_status',
        'kode_status',
        'note',
        'file_path',
        'pic_request_uid',
        'pic_approve_uid',
        'file_name',
        'file_mime',
        'file_size',
        'before_status',
        'flow_file_path',
        'flow_file_name',
        'flow_file_mime',
        'flow_file_size',
        'flow',
        'ba_file_path',
        'ba_file_name',
        'ba_file_mime',
        'ba_file_size',
        'reason',
        'project_uuid'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'flow'       => 'array',
    ];
    protected $appends = ['file_url', 'ba_file_url', 'flow_file_url'];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path
            ? Storage::disk('public')->url($this->file_path)
            : null;
    }

    public function getBaFileUrlAttribute(): ?string
    {
        return $this->ba_file_path
            ? Storage::disk('public')->url($this->ba_file_path)
            : null;
    }
    public function getFlowFileUrlAttribute(): ?string
    {
        return $this->flow_file_path
            ? Storage::disk('public')->url($this->flow_file_path)
            : null;
    }
    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (!$m->uuid) {
                $m->uuid = (string) Str::uuid();
            }
        });

        static::forceDeleted(function (self $t) {
            if ($t->file_path) {
                Storage::disk('public')->delete($t->file_path);
            }
            if ($t->ba_file_path) {
                Storage::disk('public')->delete($t->ba_file_path);
            }
            if ($t->flow_file_path) {
                Storage::disk('public')->delete($t->flow_file_path);
            }
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

    public function target()
    {
        return $this->belongsTo(MasterStatus::class, 'target_status', 'kode');
    }
}
