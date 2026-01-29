<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RfidApiController extends Controller
{
    public function lookupByEpc(Request $request)
    {
        $data = $request->validate([
            'epcs'   => ['required', 'array', 'min:1', 'max:300'],
            'epcs.*' => ['required', 'string', 'max:128'],
            'project_uuid' => ['nullable', 'uuid'],
        ]);
        $projectUuid = $data['project_uuid'] ?? null;
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
        $inProject = collect();

        if ($projectUuid) {
            $inProject = DB::table('asset_project_assets')
                ->where('project_uuid', $projectUuid)
                ->whereIn('asset_uuid', $assetUuids->all())
                ->pluck('asset_uuid')
                ->flip();
        }
        $assets = Assets::query()
            ->with(['location:uuid,kode,name', 'status:uuid,kode,name'])
            ->whereIn('uuid', $assetUuids->all())
            ->where('kode_status', '!=', 'DIS')
            ->when($projectUuid, function ($qb) use ($projectUuid) {
                $qb->whereExists(function ($sq) use ($projectUuid) {
                    $sq->selectRaw('1')
                        ->from('asset_project_assets as apa')
                        ->whereColumn('apa.asset_uuid', 'assets.uuid')
                        ->where('apa.project_uuid', $projectUuid);
                });
            })
            ->get(['uuid', 'asset_code', 'description', 'kode_status', 'kode_location']);

        $assetByUuid = $assets->keyBy('uuid');
        $disposedUuids = Assets::query()
            ->whereIn('uuid', $assetUuids->all())
            ->where('kode_status', 'DIS')
            ->pluck('uuid')
            ->flip();
        // Build results per EPC (preserve input order)
        $results = $epcs->map(function (string $epc) use ($tagByEpc, $assetByUuid, $disposedUuids, $inProject, $projectUuid) {
            $tag = $tagByEpc->get($epc);
            if (! $tag) return ['epc' => $epc, 'status' => 'not_found'];

            if ($projectUuid && !isset($inProject[$tag->asset_uuid])) {
                // tag ada, tapi asset bukan anggota project => silent ignore di frontend
                return [
                    'epc' => $epc,
                    'status' => 'not_in_project',
                ];
            }

            $asset = $assetByUuid->get($tag->asset_uuid);
            if (! $asset) {
                if (isset($disposedUuids[$tag->asset_uuid])) {
                    return [
                        'epc' => $epc,
                        'status' => 'disposed',
                        'tag' => [
                            'uuid' => $tag->uuid,
                            'epc' => $tag->epc,
                            'tag_type' => $tag->tag_type,
                            'encoded_at' => optional($tag->encoded_at)->toIso8601String(),
                        ],
                        'message' => 'Asset sudah di-dispose (DIS)',
                    ];
                }

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
