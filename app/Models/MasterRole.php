<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterRole extends Model
{
    use SoftDeletes;

    protected $table = 'master_role';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'kode',
        'name',
        'status',
    ];
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_role',
            'role_kode',
            'user_id',
            'kode',
            'id'
        );
    }
}
