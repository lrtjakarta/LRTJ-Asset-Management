<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterRoleMenu extends Model
{
    use SoftDeletes;

    protected $table = 'master_role_menu';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'actions' => 'array',
    ];

    protected $fillable = [
        'uuid',
        'role_kode',
        'menu_kode',
        'actions',
        'status',
    ];
}
