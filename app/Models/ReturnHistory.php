<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnHistory extends Model
{
    use SoftDeletes;

    protected $table = 'return_history';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'asset_uuid',
        'source_type',
        'source_id',
        'source_code',
        'note',
        'pic_request_uid',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function asset()
    {
        return $this->belongsTo(Assets::class, 'asset_uuid', 'uuid');
    }

    public function source()
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }
    public function transfer()
    {
        return $this->belongsTo(Transfer::class, 'source_id', 'uuid')
            ->where('return_history.source_type', 'transfer');
    }

    public function disposal()
    {
        return $this->belongsTo(Disposal::class, 'source_id', 'uuid')
            ->where('return_history.source_type', 'disposal');
    }
}
