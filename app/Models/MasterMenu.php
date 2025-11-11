<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterMenu extends Model
{
    use SoftDeletes;

    protected $table = 'master_menu';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'kode',
        'name',
        'sort_order',
        'status',
    ];
    protected $casts = [
        'actions' => 'array',
    ];
}
