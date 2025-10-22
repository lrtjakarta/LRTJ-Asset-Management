<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsAssignment extends Model
{
    
    use SoftDeletes;
    protected $table = 'assets_assignment';
    protected $primaryKey = 'asset_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid',
        'asset_owner',
        'asset_user',
        'asset_maintenance',
    ];

    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function asset() { return $this->belongsTo(Assets::class, 'asset_uuid'); }

    public function owner()       { return $this->belongsTo(MasterUserCode::class, 'asset_owner', 'kode'); }
    public function user()        { return $this->belongsTo(MasterUserCode::class, 'asset_user', 'kode'); }
    public function maintenance() { return $this->belongsTo(MasterUserCode::class, 'asset_maintenance', 'kode'); }
}
