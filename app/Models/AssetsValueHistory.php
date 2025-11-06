<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsValueHistory extends Model
{
    use SoftDeletes;

    protected $table = 'assets_value_history';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid','asset_uuid','before_payload','after_payload',
        'pic_request_uid','note', 'acq_code'
    ];

    protected $casts = [
        'before_payload' => 'array',
        'after_payload'  => 'array',
    ];
}
