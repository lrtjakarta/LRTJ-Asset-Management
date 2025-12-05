<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Http\Request;

class RfidApiController extends Controller
{
    public function lookupByEpc(Request $request)
    {
        $epc = trim((string) $request->query('epc', ''));

        if ($epc === '') {
            return response()->json([
                'message' => 'EPC is required',
            ], 422);
        }

        $epcNorm = strtoupper($epc);

        $tag = AssetsRfid::whereRaw('upper(epc) = ?', [$epcNorm])
            ->active()
            ->whereNull('deleted_at')
            ->first();

        if (! $tag) {
            return response()->json([
                'message' => 'RFID tag not found',
            ], 404);
        }

        $asset = Assets::with([
                'activeQr',
                'activeRfid',
                'location',
                'assetClass',
                'status',
            ])
            ->where('uuid', $tag->asset_uuid)
            ->first();

        if (! $asset) {
            return response()->json([
                'message' => 'Asset not found for this RFID',
            ], 404);
        }

        return response()->json([
            'tag' => [
                'uuid'       => $tag->uuid,
                'epc'        => $tag->epc,
                'tag_type'   => $tag->tag_type,
                'encoded_at' => optional($tag->encoded_at)->toIso8601String(),
            ],
            'asset' => [
                'uuid'         => $asset->uuid,
                'asset_code'   => $asset->asset_code,
                'description'  => $asset->description,
                'kode_status'  => $asset->kode_status,
                'kode_location'=> $asset->kode_location,
                'location_name'=> optional($asset->location)->name,
                'status_name'  => optional($asset->status)->name,
            ],
        ]);
    }
}
