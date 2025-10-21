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
     * Preferred: /v1/files/{kind}/{uuid}/download
     * kind = disposal|transfer
     */
    public function download(Request $request, string $kind, string $uuid)
    {
        $row = $this->resolveRow($kind, $uuid);

        [$disk, $fs] = $this->disk();

        if (!$row->file_path || !$fs->exists($row->file_path)) {
            abort(404, 'File not found');
        }

        [$mime, $size, $mtime, $etag] = $this->probe($row->file_path, $row);

        $headers = array_filter([
            'Content-Type'      => $mime,
            'Content-Length'    => $size,
            'ETag'              => "\"$etag\"",
            'Last-Modified'     => gmdate('D, d M Y H:i:s', $mtime) . ' GMT',
            'Cache-Control'     => 'public, max-age=31536000, immutable',
            'Accept-Ranges'     => 'bytes',
            'Content-Disposition' => 'inline; filename="' . addslashes($row->file_name ?: basename($row->file_path)) . '"',
            'X-File-Kind'       => $kind,
            'X-File-Id'         => (string) $row->uuid,
        ]);

        // Conditional requests
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

                $stream = $fs->readStream($row->file_path);
                if ($start > 0) fseek($stream, $start);

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

        $absolutePath = Storage::disk($disk)->path($row->file_path);
        return response()->file($absolutePath, $headers);
    }

    public function downloadLegacy(Request $request, string $uuid)
    {
        $row = Disposal::where('uuid', $uuid)->first()
            ?? Transfer::where('uuid', $uuid)->first();

        abort_if(!$row, 404, 'Record not found');

        $kind = $row instanceof Disposal ? 'disposal' : 'transfer';
        return $this->download($request, $kind, $uuid);
    }

    public function manifest(Request $request)
    {
        $since = $request->date('since');
        $kind  = strtolower($request->query('kind', 'all'));
        if (!in_array($kind, ['disposal', 'transfer', 'all'], true)) {
            return response()->json(['message' => 'Invalid kind. Use disposal|transfer|all'], 422);
        }

        $data = collect();

        // Helper to build a query safely (columns may not exist)
        $build = function (string $table, $model) use ($since) {
            $cols = ['uuid', 'file_path', 'file_name', 'file_mime', 'file_size', 'updated_at'];

            // add optional columns if present
            if (Schema::hasColumn($table, 'file_sha256')) $cols[] = 'file_sha256';
            if (Schema::hasColumn($table, 'file_etag'))   $cols[] = 'file_etag';
            if (Schema::hasColumn($table, 'deleted_at'))  $cols[] = 'deleted_at';

            $q = $model::query()
                ->whereNotNull('file_path')
                ->select($cols);

            // default: exclude soft-deleted if the table has deleted_at
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
            $data = $data->merge($this->mapRowsForManifest($rows, 'disposal'));
        }

        if ($kind === 'transfer' || $kind === 'all') {
            $rows = $build('assets_transfers', Transfer::class);
            $data = $data->merge($this->mapRowsForManifest($rows, 'transfer'));
        }

        return response()->json([
            'data' => $data->values(),
            'meta' => [
                'count' => $data->count(),
                'since' => $since?->toIso8601String(),
                'kind'  => $kind,
            ],
        ]);
    }

    // ----------------------
    // Helpers
    // ----------------------
    private function mapRowsForManifest($rows, string $kind)
    {
        $disk = 'public';
        $fs   = Storage::disk($disk);

        return collect($rows)->map(function ($r) use ($fs, $disk, $kind) {
            $filePath = $r->file_path;
            $exists   = $filePath ? $fs->exists($filePath) : false;

            $size  = $r->file_size ?? ($exists ? $fs->size($filePath) : null);
            $mtime = $exists ? $fs->lastModified($filePath) : null;

            // Prefer DB-provided SHA/ETag if present, else weak etag from mtime+size
            $etag   = $r->file_etag   ?? null;
            $sha256 = $r->file_sha256 ?? null;
            if (!$etag && ($mtime !== null || $size !== null)) {
                $etag = ($mtime ?? 0) . ':' . ($size ?? 0);
            }

            return [
                'id'            => (string) $r->uuid,
                'kind'          => $kind,
                'name'          => $r->file_name ?: ($filePath ? basename($filePath) : null),
                'mime'          => $r->file_mime,
                'size'          => $size,
                'sha256'        => $sha256,
                'etag'          => $etag,
                'last_modified' => optional($r->updated_at)->toIso8601String(),
                'download_url'  => route('files.download.kind', ['kind' => $kind, 'uuid' => $r->uuid]),
                // Optional: direct storage URL (if publicly accessible)
                'file_url'      => $exists ? url('storage/' . ltrim($filePath, '/')) : null,
            ];
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
        // Local public disk for /storage symlink
        $disk = 'public';
        return [$disk, Storage::disk($disk)];
    }

    private function probe(string $path, $row): array
    {
        [$disk, $fs] = $this->disk();

        $mime  = $row->file_mime ?: $fs->mimeType($path);
        $size  = $row->file_size ?? $fs->size($path);
        $mtime = $row->file_updated_at ? strtotime($row->file_updated_at) : $fs->lastModified($path);

        $weak   = $row->file_sha256 ?: ($mtime . ':' . $size);
        $etag   = $row->file_etag ?: $weak;

        return [$mime, $size, $mtime, $etag];
    }
}
