<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetProject extends Model
{
  use SoftDeletes;

  protected $table = 'asset_projects';
  protected $primaryKey = 'uuid';
  public $incrementing = false;
  protected $keyType = 'string';

  protected $fillable = ['uuid','name','status','created_by'];

  public function getRouteKeyName(): string
  {
    return 'uuid';
  }
}