<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RfidApiController extends Controller
{
    public function lookupByEpc(Request $request)
    {
        $data = $request->validate([
            'epcs'   => ['required', 'array', 'min:1', 'max:300'],
            'epcs.*' => ['required', 'string', 'max:128'],
        ]);

        // normalize + unique, buang empty
        $epcs = collect($data['epcs'])
            ->map(fn($v) => strtoupper(trim((string) $v)))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values();

        if ($epcs->isEmpty()) {
            return response()->json(['message' => 'EPC list is empty after normalization'], 422);
        }

        // Ambil tags sekali query
        $tags = AssetsRfid::query()
            ->active()
            ->whereIn('epc', $epcs->all())     // <-- ideal kalau epc disimpan uppercase
            ->get(['uuid', 'epc', 'asset_uuid', 'tag_type', 'encoded_at']);

        // Map epc -> tag
        $tagByEpc = $tags->keyBy('epc');

        // Ambil assets sekali query
        $assetUuids = $tags->pluck('asset_uuid')->unique()->values();

        $assets = Assets::query()
            ->with([
                'location:id,code,name',   // sesuaikan kolommu
                'status:id,code,name',
            ])
            ->whereIn('uuid', $assetUuids->all())
            ->get(['uuid', 'asset_code', 'description', 'kode_status', 'kode_location']);

        $assetByUuid = $assets->keyBy('uuid');

        // Build results per EPC (preserve input order)
        $results = $epcs->map(function (string $epc) use ($tagByEpc, $assetByUuid) {
            $tag = $tagByEpc->get($epc);
            if (! $tag) {
                return [
                    'epc' => $epc,
                    'status' => 'not_found',
                ];
            }

            $asset = $assetByUuid->get($tag->asset_uuid);
            if (! $asset) {
                return [
                    'epc' => $epc,
                    'status' => 'asset_missing',
                    'tag' => [
                        'uuid' => $tag->uuid,
                        'epc' => $tag->epc,
                        'tag_type' => $tag->tag_type,
                        'encoded_at' => optional($tag->encoded_at)->toIso8601String(),
                    ],
                ];
            }

            return [
                'epc' => $epc,
                'status' => 'ok',
                'tag' => [
                    'uuid' => $tag->uuid,
                    'epc' => $tag->epc,
                    'tag_type' => $tag->tag_type,
                    'encoded_at' => optional($tag->encoded_at)->toIso8601String(),
                ],
                'asset' => [
                    'uuid' => $asset->uuid,
                    'asset_code' => $asset->asset_code,
                    'description' => $asset->description,
                    'kode_status' => $asset->kode_status,
                    'kode_location' => $asset->kode_location,
                    'location_name' => optional($asset->location)->name,
                    'status_name' => optional($asset->status)->name,
                ],
            ];
        });

        return response()->json([
            'count' => $results->count(),
            'found' => $results->where('status', 'ok')->count(),
            'results' => $results,
        ]);
    }
}
