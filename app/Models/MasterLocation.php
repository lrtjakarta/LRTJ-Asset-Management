<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterLocation extends Model
{
    use SoftDeletes;

    protected $table = 'master_location';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['kode','name','status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    // If you want route-model binding by uuid:
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
