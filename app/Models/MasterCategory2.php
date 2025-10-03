<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterCategory2 extends Model
{
    use SoftDeletes;

    protected $table = 'master_category_2';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode','name','status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    // so routes bind by uuid instead of id
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
