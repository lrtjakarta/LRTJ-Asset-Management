<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AssetDeprTransferRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'assets_depr_transfer_requests';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    const STATUS_APR = 'APR'; // waiting approval
    const STATUS_ACC = 'ACC'; // accepted
    const STATUS_REJ = 'REJ'; // rejected

    protected $fillable = [
        'from_asset_uuid',
        'to_asset_uuid',
        'transfer_type',
        'amount',
        'actual_date',
        'note',
        'attachment_path',
        'kode_status',
        'requested_by',
        'approved_by',
        'approved_at',
        'group_uuid',
        'transfer_code'
    ];

    protected $casts = [
        'actual_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function fromAsset()
    {
        return $this->belongsTo(Assets::class, 'from_asset_uuid', 'uuid');
    }

    public function toAsset()
    {
        return $this->belongsTo(Assets::class, 'to_asset_uuid', 'uuid');
    }

    public function status()
    {
        return $this->belongsTo(MasterStatus::class, 'kode_status', 'kode_status');
    }
}
