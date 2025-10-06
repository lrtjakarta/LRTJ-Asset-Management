<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
