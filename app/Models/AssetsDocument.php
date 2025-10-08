<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetsDocument extends Model
{
    use SoftDeletes;
    protected $table = 'assets_document';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_uuid',
        'no_po_perjanjian_spk',
        'nota_referensi',
        'no_document',
    ];

    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function asset() { return $this->belongsTo(Assets::class, 'asset_uuid'); }
}
