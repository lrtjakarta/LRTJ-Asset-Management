<?php

namespace App\Http\Controllers\Api;

use App\Models\Disposal;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StorageApi extends Controller
{
    /**
     * Preferred: /v1/files/{kind}/{uuid}/download?slot=main|form|ba
     * kind = disposal|transfer
     * slot:
     *  - main  : main attachment (file_path, file_name, ...)
     *  - form  : flow / form file (flow_file_path, flow_file_name, ...)
     *  - ba    : BA file (ba_file_path, ba_file_name, ...)
     */
    public function download(Request $request, string $kind, string $uuid)
    {
        $slot = strtolower($request->query('slot', 'main'));
        if (!in_array($slot, ['main', 'form', 'ba'], true)) {
            $slot = 'main';
        }

        $row = $this->resolveRow($kind, $uuid);

        // Will throw 404 if path not found
        [$path, $mime, $size, $mtime, $etag, $fileName] = $this->probe($slot, $row);

        [$disk, $fs] = $this->disk();

        $headers = array_filter([
            'Content-Type'        => $mime,
            'Content-Length'      => $size,
            'ETag'                => "\"$etag\"",
            'Last-Modified'       => gmdate('D, d M Y H:i:s', $mtime) . ' GMT',
            'Cache-Control'       => 'public, max-age=31536000, immutable',
            'Accept-Ranges'       => 'bytes',
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            'X-File-Kind'         => $kind,
            'X-File-Id'           => (string) $row->uuid,
            'X-File-Slot'         => $slot,
        ]);

        // Conditional GET
        if ($request->headers->get('If-None-Match') === "\"$etag\"") {
            return response()->noContent(304)->withHeaders($headers);
        }
        if ($request->headers->has('If-Modified-Since')) {
            $since = strtotime($request->headers->get('If-Modified-Since'));
            if ($since && $since >= $mtime) {
                return response()->noContent(304)->withHeaders($headers);
            }
        }

        // Range support
        if ($request->headers->has('Range')) {
            [$unit, $range] = explode('=', $request->headers->get('Range'), 2);
            if (strtolower($unit) === 'bytes') {
                [$start, $end] = array_pad(array_map('intval', explode('-', $range)), 2, null);
                $start  = max(0, $start);
                $end    = ($end !== null) ? min($end, $size - 1) : ($size - 1);
                $length = $end - $start + 1;

                $stream = $fs->readStream($path);
                if ($start > 0) {
                    fseek($stream, $start);
                }

                return response()->stream(function () use ($stream, $length) {
                    $sent = 0;
                    while (!feof($stream) && $sent < $length) {
                        $chunk = fread($stream, min(8192, $length - $sent));
                        if ($chunk === false) break;
                        $sent += strlen($chunk);
                        echo $chunk;
                        flush();
                    }
                    fclose($stream);
                }, 206, $headers + [
                    'Content-Range'  => "bytes $start-$end/$size",
                    'Content-Length' => $length,
                ]);
            }
        }

        $absolutePath = Storage::disk($disk)->path($path);
        return response()->file($absolutePath, $headers);
    }

    /**
     * Legacy: /v1/files/{uuid}/download
     * Uses main slot for either disposal/transfer.
     */
    public function downloadLegacy(Request $request, string $uuid)
    {
        $row = Disposal::where('uuid', $uuid)->first()
            ?? Transfer::where('uuid', $uuid)->first();

        abort_if(!$row, 404, 'Record not found');

        $kind = $row instanceof Disposal ? 'disposal' : 'transfer';
        return $this->download($request, $kind, $uuid);
    }

    /**
     * List files for offline sync.
     *
     * GET /v1/files/manifest?since=2025-01-01T00:00:00Z&kind=disposal|transfer|all&slot=main|form|ba|all
     *
     * Returns:
     * [
     *   {
     *     "id": "disposal-uuid",
     *     "kind": "disposal",
     *     "slot": "main|form|ba",
     *     "name": "file.pdf",
     *     "mime": "application/pdf",
     *     "size": 12345,
     *     "sha256": null,
     *     "etag": "mtime:size",
     *     "last_modified": "2025-11-20T09:30:00+07:00",
     *     "download_url": ".../v1/files/disposal/{uuid}/download?slot=main",
     *     "file_url": ".../storage/..."
     *   },
     *   ...
     * ]
     */
    public function manifest(Request $request)
    {
        $since = $request->date('since');
        $kind  = strtolower($request->query('kind', 'all'));
        $slot  = strtolower($request->query('slot', 'all'));

        if (!in_array($kind, ['disposal', 'transfer', 'all'], true)) {
            return response()->json(['message' => 'Invalid kind. Use disposal|transfer|all'], 422);
        }
        if (!in_array($slot, ['main', 'form', 'ba', 'all'], true)) {
            return response()->json(['message' => 'Invalid slot. Use main|form|ba|all'], 422);
        }

        $data = collect();

        $build = function (string $table, $model) use ($since) {
            $cols = ['uuid', 'file_path', 'file_name', 'file_mime', 'file_size', 'updated_at'];

            // Optional columns – only added if physically present on table
            foreach (
                [
                    'file_sha256',
                    'file_etag',
                    'deleted_at',
                    'flow_file_path',
                    'flow_file_name',
                    'flow_file_mime',
                    'flow_file_size',
                    'flow_file_sha256',
                    'flow_file_etag',
                    'ba_file_path',
                    'ba_file_name',
                    'ba_file_mime',
                    'ba_file_size',
                    'ba_file_sha256',
                    'ba_file_etag',
                ] as $col
            ) {
                if (Schema::hasColumn($table, $col)) {
                    $cols[] = $col;
                }
            }

            $q = $model::query()
                ->whereNotNull('file_path') // main file must exist to be relevant
                ->select($cols);

            if (in_array('deleted_at', $cols, true)) {
                $q->whereNull('deleted_at');
            }

            if ($since) {
                $q->where('updated_at', '>=', $since);
            }

            return $q->get();
        };

        if ($kind === 'disposal' || $kind === 'all') {
            $rows = $build('assets_disposals', Disposal::class);
            $data = $data->merge($this->mapRowsForManifest($rows, 'disposal', $slot));
        }

        if ($kind === 'transfer' || $kind === 'all') {
            $rows = $build('assets_transfers', Transfer::class);
            $data = $data->merge($this->mapRowsForManifest($rows, 'transfer', $slot));
        }

        return response()->json([
            'data' => $data->values(),
            'meta' => [
                'count' => $data->count(),
                'since' => $since?->toIso8601String(),
                'kind'  => $kind,
                'slot'  => $slot,
            ],
        ]);
    }

    // ----------------------
    // Helpers
    // ----------------------
    private function mapRowsForManifest($rows, string $kind, string $slot = 'all')
    {
        $disk = 'public';
        $fs   = Storage::disk($disk);

        $slotsFilter = match ($slot) {
            'main' => ['main'],
            'form' => ['form'],
            'ba'   => ['ba'],
            default => ['main', 'form', 'ba'],
        };

        return collect($rows)->flatMap(function ($r) use ($fs, $disk, $kind, $slotsFilter) {
            $items = [];

            $addItem = function (string $slotName, ?string $path, ?string $name, ?string $mime, ?int $sizeCol, ?string $shaCol, ?string $etagCol) use (&$items, $fs, $disk, $kind, $r, $slotsFilter) {
                if (!in_array($slotName, $slotsFilter, true)) {
                    return;
                }
                if (!$path) {
                    return;
                }

                $exists = $fs->exists($path);
                if (!$exists) {
                    return;
                }

                $size  = $sizeCol ?? $fs->size($path);
                $mtime = $fs->lastModified($path);

                $sha256 = $shaCol ?: null;
                $etag   = $etagCol ?: (($mtime ?? 0) . ':' . ($size ?? 0));

                $items[] = [
                    'id'            => (string) $r->uuid,
                    'kind'          => $kind,
                    'slot'          => $slotName,
                    'name'          => $name ?: basename($path),
                    'mime'          => $mime,
                    'size'          => $size,
                    'sha256'        => $sha256,
                    'etag'          => $etag,
                    'last_modified' => optional($r->updated_at)->toIso8601String(),
                    'download_url'  => route('files.download.kind', ['kind' => $kind, 'uuid' => $r->uuid]) . '?slot=' . $slotName,
                    'file_url'      => url('storage/' . ltrim($path, '/')),
                ];
            };

            // main attachment
            $addItem(
                'main',
                $r->file_path,
                $r->file_name ?? null,
                $r->file_mime ?? null,
                $r->file_size ?? null,
                $r->file_sha256 ?? null,
                $r->file_etag ?? null
            );

            // flow / form file (if columns exist and value not null)
            $addItem(
                'form',
                $r->flow_file_path ?? null,
                $r->flow_file_name ?? null,
                $r->flow_file_mime ?? null,
                $r->flow_file_size ?? null,
                $r->flow_file_sha256 ?? null,
                $r->flow_file_etag ?? null
            );

            // BA file (if columns exist and value not null)
            $addItem(
                'ba',
                $r->ba_file_path ?? null,
                $r->ba_file_name ?? null,
                $r->ba_file_mime ?? null,
                $r->ba_file_size ?? null,
                $r->ba_file_sha256 ?? null,
                $r->ba_file_etag ?? null
            );

            return $items;
        });
    }

    private function resolveRow(string $kind, string $uuid)
    {
        return match ($kind) {
            'disposal' => Disposal::where('uuid', $uuid)->firstOrFail(),
            'transfer' => Transfer::where('uuid', $uuid)->firstOrFail(),
            default    => abort(404, 'Unsupported kind'),
        };
    }

    private function disk(): array
    {
        $disk = 'public';
        return [$disk, Storage::disk($disk)];
    }

    /**
     * Resolve real path + metadata for a given slot.
     *
     * @return array [path, mime, size, mtime, etag, fileName]
     */
    private function probe(string $slot, $row): array
    {
        [$disk, $fs] = $this->disk();

        $path = $mime = $name = null;
        $size = $mtime = null;
        $sha256 = $etag = null;

        switch ($slot) {
            case 'form':
                $path   = $row->flow_file_path ?? null;
                $mime   = $row->flow_file_mime ?? null;
                $size   = $row->flow_file_size ?? null;
                $name   = $row->flow_file_name ?? null;
                $sha256 = $row->flow_file_sha256 ?? null;
                $etag   = $row->flow_file_etag ?? null;
                break;

            case 'ba':
                $path   = $row->ba_file_path ?? null;
                $mime   = $row->ba_file_mime ?? null;
                $size   = $row->ba_file_size ?? null;
                $name   = $row->ba_file_name ?? null;
                $sha256 = $row->ba_file_sha256 ?? null;
                $etag   = $row->ba_file_etag ?? null;
                break;

            case 'main':
            default:
                $path   = $row->file_path ?? null;
                $mime   = $row->file_mime ?? null;
                $size   = $row->file_size ?? null;
                $name   = $row->file_name ?? null;
                $sha256 = $row->file_sha256 ?? null;
                $etag   = $row->file_etag ?? null;
                break;
        }

        if (!$path || !$fs->exists($path)) {
            abort(404, 'File not found');
        }

        $size  = $size ?? $fs->size($path);
        $mtime = $fs->lastModified($path);
        $mime  = $mime ?: $fs->mimeType($path);

        $weak = $sha256 ?: ($mtime . ':' . $size);
        $etag = $etag ?: $weak;
        $name = $name ?: basename($path);

        return [$path, $mime, $size, $mtime, $etag, $name];
    }
}
