<?php

namespace App\Http\Controllers\Api;

use App\Models\Disposal;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DisposalApi extends Controller
{
    public function index(Request $request)
    {
        // Validate & normalize
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'workflow'     => ['nullable', 'string', 'max:50'],  // kode_status filter
            'target'       => ['nullable', 'string', 'max:50'],  // target_status filter
            'asset_uuid'   => ['nullable', 'uuid'],
            'sort_by'      => ['nullable', Rule::in(['created_at','updated_at','kode_status','target_status','asset_code'])],
            'sort_dir'     => ['nullable', Rule::in(['asc','desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', Rule::in(['0','1'])],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
        ])->validate();

        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $tz      = config('app.timezone', 'UTC');

        // Base query + join asset for searching/sorting by asset_code
        $q = Disposal::query()
            ->select([
                'disposals.*',
                DB::raw('a.asset_code as asset_code'),
                DB::raw('a.description as asset_description'),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'disposals.asset_uuid')
            ->with([
                'status:id,kode,name',
                'target:id,kode,name',
                'asset:uuid,asset_code,description',
            ])
            ->when($request->boolean('with_trashed'), fn($x) => $x->withTrashed())
            ->when(!empty($v['asset_uuid']), fn($x) => $x->where('disposals.asset_uuid', $v['asset_uuid']))
            ->when(!empty($v['workflow']),   fn($x) => $x->where('disposals.kode_status', $v['workflow']))
            ->when(!empty($v['target']),     fn($x) => $x->where('disposals.target_status', $v['target']))
            ->when(!empty($v['from']),       fn($x) => $x->where('disposals.created_at', '>=', $v['from']))
            ->when(!empty($v['to']),         fn($x) => $x->where('disposals.created_at', '<=', $v['to']))
            ->when(!empty($v['q']), function ($x) use ($v) {
                $like = '%'.trim($v['q']).'%';
                $x->where(function ($w) use ($like) {
                    $w->where('disposals.uuid',           'ILIKE', $like)
                      ->orWhere('disposals.kode_status',  'ILIKE', $like)
                      ->orWhere('disposals.target_status','ILIKE', $like)
                      ->orWhere('disposals.note',         'ILIKE', $like)
                      ->orWhere('a.asset_code',           'ILIKE', $like)
                      ->orWhere('a.description',          'ILIKE', $like);
                });
            });

        // Sort whitelist
        $sortColumns = [
            'created_at'    => 'disposals.created_at',
            'updated_at'    => 'disposals.updated_at',
            'kode_status'   => 'disposals.kode_status',
            'target_status' => 'disposals.target_status',
            'asset_code'    => 'a.asset_code',
        ];
        $q->orderBy($sortColumns[$sortBy] ?? 'disposals.created_at', $sortDir);

        // Pagination switch (omit per_page/page -> ALL data)
        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->get();
            $data = $this->mapRows($rows, $tz);

            return response()->json([
                'data' => $data,
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q'            => $request->query('q'),
                        'workflow'     => $request->query('workflow'),
                        'target'       => $request->query('target'),
                        'asset_uuid'   => $request->query('asset_uuid'),
                        'with_trashed' => $request->query('with_trashed'),
                        'from'         => $request->query('from'),
                        'to'           => $request->query('to'),
                    ],
                ],
                'links' => ['first'=>null,'prev'=>null,'next'=>null,'last'=>null],
            ]);
        }

        $perPage   = (int) ($v['per_page'] ?? 15);
        $paginator = $q->paginate($perPage)->appends($request->query());
        $data      = $this->mapRows($paginator->getCollection(), $tz);

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'paginated'    => true,
                'sort_by'      => $sortBy,
                'sort_dir'     => $sortDir,
                'filters'      => [
                    'q'            => $request->query('q'),
                    'workflow'     => $request->query('workflow'),
                    'target'       => $request->query('target'),
                    'asset_uuid'   => $request->query('asset_uuid'),
                    'with_trashed' => $request->query('with_trashed'),
                    'from'         => $request->query('from'),
                    'to'           => $request->query('to'),
                ],
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
                'last'  => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }

    public function show(string $uuid)
    {
        $tz  = config('app.timezone', 'UTC');
        $row = Disposal::with([
                'status:id,kode,name',
                'target:id,kode,name',
                'asset:uuid,asset_code,description',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $data = $this->mapRows(collect([$row]), $tz)->first();

        return response()->json(['data' => $data]);
    }

    /**
     * Map Disposal rows to API shape (no HTML).
     */
    protected function mapRows($rows, string $tz)
    {
        return $rows->map(function (Disposal $t) use ($tz) {
            $assetLabel    = $t->asset?->asset_code ?: $t->asset_uuid;
            $workflowLabel = $t->status ? ($t->kode_status . ' - ' . $t->status->name) : $t->kode_status;
            $targetLabel   = $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status;

            // Local storage file payload:
            // We'll build a stable download_url under /v1/files/{uuid}/download.
            $fileObj = null;
            if ($t->file_path) {
                $disk = 'public'; // local "public" disk
                $fs   = Storage::disk($disk);

                $exists = $fs->exists($t->file_path);
                $size   = $t->file_size ?? ($exists ? $fs->size($t->file_path) : null);
                $mtime  = $t->file_updated_at ? strtotime($t->file_updated_at) : ($exists ? $fs->lastModified($t->file_path) : null);

                $fileObj = [
                    'name'          => $t->file_name ?: basename($t->file_path),
                    'mime'          => $t->file_mime,
                    'size'          => $size,
                    'sha256'        => $t->file_sha256 ?? null,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'download_url'  => route('files.download', ['uuid' => $t->uuid]),
                    // For online preview (optional), local public URL:
                    'file_url'      => $exists ? url('storage/' . ltrim($t->file_path, '/')) : null,
                ];
            }

            return [
                'uuid'            => $t->uuid,
                'asset_uuid'      => $t->asset_uuid,
                'asset_code'      => $t->getAttribute('asset_code') ?? $t->asset?->asset_code,
                'asset_label'     => $assetLabel,

                'kode_status'     => $t->kode_status,
                'workflow_label'  => $workflowLabel,

                'target_status'   => $t->target_status,
                'target_label'    => $targetLabel,

                'note'            => $t->note,

                'file'            => $fileObj, // null if no file

                'created_at'      => optional($t->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'      => optional($t->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'      => optional($t->deleted_at)->timezone($tz)?->toIso8601String(),
            ];
        });
    }
}
