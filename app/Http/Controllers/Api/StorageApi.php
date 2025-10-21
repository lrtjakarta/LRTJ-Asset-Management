<?php

namespace App\Http\Controllers\Api;

use App\Models\Disposal;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        validator($request->all(), [
            'kind'  => ['nullable', Rule::in(['disposal', 'transfer', 'all'])],
            'since' => ['nullable', 'date'],
        ])->validate();

        $kind  = $request->input('kind', 'all');
        $since = $request->date('since');

        $rows = collect();

        if ($kind === 'disposal' || $kind === 'all') {
            $q = Disposal::query()
                ->whereNotNull('file_path')
                ->select(['uuid', 'file_path', 'file_name', 'file_mime', 'file_size', 'file_sha256', 'file_etag', 'updated_at']);
            if ($since) $q->where('updated_at', '>=', $since);
            $rows = $rows->concat($q->get()->map(function ($r) {
                return [
                    'kind'          => 'disposal',
                    'id'            => (string) $r->uuid,
                    'name'          => $r->file_name ?: basename($r->file_path),
                    'mime'          => $r->file_mime,
                    'size'          => $r->file_size,
                    'sha256'        => $r->file_sha256,
                    'etag'          => $r->file_etag ?: null,
                    'last_modified' => optional($r->updated_at)->toIso8601String(),
                    'download_url'  => route('files.download.kind', ['kind' => 'disposal', 'uuid' => $r->uuid]),
                ];
            }));
        }

        if ($kind === 'transfer' || $kind === 'all') {
            $q = Transfer::query()
                ->whereNotNull('file_path')
                ->select(['uuid', 'file_path', 'file_name', 'file_mime', 'file_size', 'file_sha256', 'file_etag', 'updated_at']);
            if ($since) $q->where('updated_at', '>=', $since);
            $rows = $rows->concat($q->get()->map(function ($r) {
                return [
                    'kind'          => 'transfer',
                    'id'            => (string) $r->uuid,
                    'name'          => $r->file_name ?: basename($r->file_path),
                    'mime'          => $r->file_mime,
                    'size'          => $r->file_size,
                    'sha256'        => $r->file_sha256,
                    'etag'          => $r->file_etag ?: null,
                    'last_modified' => optional($r->updated_at)->toIso8601String(),
                    'download_url'  => route('files.download.kind', ['kind' => 'transfer', 'uuid' => $r->uuid]),
                ];
            }));
        }

        $rows = $rows->sortByDesc('last_modified')->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'count' => $rows->count(),
                'since' => $since?->toIso8601String(),
                'kind'  => $kind,
            ],
        ]);
    }

    // ----------------------
    // Helpers
    // ----------------------

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
