<?php

namespace App\Http\Controllers\Api;

use App\Models\Assets;
use App\Models\Disposal;
use App\Models\MasterStatus;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class DisposalApi extends Controller
{
    public function index(Request $request)
    {
        // Validate & normalize
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'workflow'     => ['nullable', 'string', 'max:50'],
            'target'       => ['nullable', 'string', 'max:50'],
            'asset_uuid'   => ['nullable'],
            'asset_uuid.*' => ['nullable', 'uuid'],
            'sort_by'      => ['nullable', Rule::in(['created_at', 'updated_at', 'kode_status', 'target_status', 'asset_code'])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', Rule::in(['0', '1'])],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
            'uuids'        => ['nullable'],
            'uuids.*'      => ['uuid'],
        ])->validate();

        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $tz      = config('app.timezone', 'UTC');

        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )->map(fn($x) => trim($x))->filter()->unique()->values();

        $uuids = collect(is_array($request->uuids) ? $request->uuids
            : (is_string($request->uuids) ? explode(',', $request->uuids) : []))
            ->map(fn($x) => trim($x))->filter()->unique()->values();

        $q = Disposal::query()
            ->select([
                'assets_disposals.*',
                DB::raw('a.asset_code as asset_code'),
                DB::raw('a.description as asset_description'),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_disposals.asset_uuid')
            ->with([
                'status:id,kode,name',
                'target:id,kode,name',
                'asset:uuid,asset_code,description',
            ])
            ->when($request->boolean('with_trashed'), fn($x) => $x->withTrashed())
            // ->when(!empty($v['asset_uuid']), fn($x) => $x->where('assets_disposals.asset_uuid', $v['asset_uuid']))
            ->when(!empty($v['workflow']),   fn($x) => $x->where('assets_disposals.kode_status', $v['workflow']))
            ->when(!empty($v['target']),     fn($x) => $x->where('assets_disposals.target_status', $v['target']))
            ->when(!empty($v['from']),       fn($x) => $x->where('assets_disposals.created_at', '>=', $v['from']))
            ->when(!empty($v['to']),         fn($x) => $x->where('assets_disposals.created_at', '<=', $v['to']))
            ->when($uuids->isNotEmpty(),     fn($x) => $x->whereIn('assets_disposals.uuid', $uuids))
            ->when($assetUuids->isNotEmpty(), fn($x) => $x->whereIn('assets_disposals.asset_uuid', $assetUuids))
            ->when(!empty($v['q']), function ($x) use ($v) {
                $like = '%' . trim($v['q']) . '%';
                $x->where(function ($w) use ($like) {
                    $w->where('assets_disposals.uuid',           'ILIKE', $like)
                        ->orWhere('assets_disposals.kode_status',  'ILIKE', $like)
                        ->orWhere('assets_disposals.target_status', 'ILIKE', $like)
                        ->orWhere('assets_disposals.note',         'ILIKE', $like)
                        ->orWhere('a.asset_code',           'ILIKE', $like)
                        ->orWhere('a.description',          'ILIKE', $like);
                });
            });

        $sortColumns = [
            'created_at'    => 'assets_disposals.created_at',
            'updated_at'    => 'assets_disposals.updated_at',
            'kode_status'   => 'assets_disposals.kode_status',
            'target_status' => 'assets_disposals.target_status',
            'asset_code'    => 'a.asset_code',
        ];
        $q->orderBy($sortColumns[$sortBy] ?? 'assets_disposals.created_at', $sortDir);

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
                        'uuids' => $uuids->all(),
                    ],
                ],
                'links' => ['first' => null, 'prev' => null, 'next' => null, 'last' => null],
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
                    'uuids' => $uuids->all(),
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

    protected function mapRows($rows, string $tz)
    {
        return $rows->map(function (Disposal $t) use ($tz) {
            $assetLabel    = $t->asset?->asset_code ?: $t->asset_uuid;
            $workflowLabel = $t->status ? ($t->kode_status . ' - ' . $t->status->name) : $t->kode_status;
            $targetLabel   = $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status;
            $fileObj = null;
            if ($t->file_path) {
                $disk = 'public';
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
    public function store(Request $request)
    {
        if (is_array($request->input('items'))) {
            $items = $request->input('items');
            if (empty($items)) {
                return response()->json(['message' => 'items must be a non-empty array'], 422);
            }

            $results = [];
            foreach ($items as $idx => $payload) {
                try {
                    $res = $this->createDisposalFromPayload($request, $payload, /*isBatch*/ true);
                    $results[] = ['index' => $idx] + $res;
                } catch (\Throwable $e) {
                    $results[] = ['index' => $idx, 'ok' => false, 'error' => $e->getMessage()];
                }
            }

            return response()->json(['ok' => true, 'batch' => true, 'results' => $results], 200);
        }

        // Single mode
        try {
            $res = $this->createDisposalFromPayload($request, $request->all(), /*isBatch*/ false);
            return response()->json($res, ($res['duplicate'] ?? false) ? 200 : 201);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }
    private function createDisposalFromPayload(Request $rootReq, array $payload, bool $isBatch): array
    {
        // ---- Validate ----
        $rules = [
            'uuid'              => ['nullable', 'uuid'],
            'client_request_id' => ['nullable', 'string', 'max:100'],
            'asset_uuid'        => ['required', 'uuid', 'exists:assets,uuid'],
            'note'              => ['nullable', 'string', 'max:1000'],
            'target_status'     => ['nullable', 'string', 'max:50'], // defaulted to 'DIS'
            'created_at'        => ['nullable', 'date'],
        ];

        if ($isBatch) {
            $rules['file_b64'] = ['nullable', 'array'];
            $rules['file_b64.name'] = ['required_with:file_b64', 'string', 'max:255'];
            $rules['file_b64.mime'] = ['required_with:file_b64', 'string', 'max:100'];
            $rules['file_b64.data'] = ['required_with:file_b64', 'string']; // base64 string
        } else {
            $rules['file'] = ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'];
        }

        $data = validator($payload, $rules)->validate();

        // ---- Auth (LDAP session) ----
        $uid = (string) data_get($rootReq->session()->get('ldap_user'), 'uid', '');
        abort_if($uid === '', 401, 'No session UID.');

        // ---- Idempotency ----
        $idemKey = $rootReq->header('Idempotency-Key') ?: ($data['client_request_id'] ?? null);
        if (!empty($data['uuid'])) {
            if ($existing = Disposal::where('uuid', $data['uuid'])->first()) {
                return ['ok' => true, 'duplicate' => true, 'via' => 'uuid', 'id' => $existing->uuid, 'code' => $existing->disposal_code];
            }
        } elseif ($idemKey) {
            if ($existing = Disposal::where('idempotency_key', $idemKey)->first()) {
                return ['ok' => true, 'duplicate' => true, 'via' => 'idempotency_key', 'id' => $existing->uuid, 'code' => $existing->disposal_code];
            }
        }

        $asset = Assets::select('uuid', 'asset_code', 'kode_status')->findOrFail($data['asset_uuid']);

        $target = $data['target_status'] ?? 'DIS';
        $ok = MasterStatus::where('kode', $target)->where(fn($q) => $q->where('type', 'Asset')->orWhereNull('type'))->exists();
        abort_unless($ok, 422, 'Target disposal status not valid.');

        $seq  = Disposal::where('asset_uuid', $asset->uuid)->count() + 1;
        $code = 'DSP-' . str_replace(['-', ' '], '', $asset->asset_code) . '-' . $seq;

        // ---- Save file ----
        $path = $orig = $mime = $size = null;
        if ($isBatch && !empty($data['file_b64'])) {
            [$path, $orig, $mime, $size] = $this->saveBase64File(
                $data['file_b64']['data'],
                $data['file_b64']['name'],
                $data['file_b64']['mime'],
                $asset,
                $code
            );
        } else {
            [$path, $orig, $mime, $size] = $this->saveUpload($rootReq->file('file'), $asset, $code, null);
        }

        // ---- Create row ----
        $row = DB::transaction(function () use ($data, $asset, $target, $code, $uid, $path, $orig, $mime, $size, $idemKey) {
            $payload = [
                'uuid'            => $data['uuid'] ?? (string) Str::uuid(),
                'asset_uuid'      => $asset->uuid,
                'disposal_code'   => $code,
                'target_status'   => $target,
                'kode_status'     => 'APR',                 // waiting for approval
                'note'            => $data['note'] ?? null,
                'file_path'       => $path,
                'file_name'       => $orig,
                'file_mime'       => $mime,
                'file_size'       => $size,
                'pic_request_uid' => $uid,
                'before_status'   => $asset->kode_status,   // keep current asset status
                'idempotency_key' => $idemKey,              // nullable column recommended
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            return Disposal::create($payload);
        });

        return ['ok' => true, 'id' => $row->uuid, 'code' => $row->disposal_code];
    }
    private function saveUpload(?UploadedFile $file, Assets $asset, string $code, ?string $subdir): array
    {
        if (!$file) return [null, null, null, null];

        $disk   = 'public';
        $folder = 'disposals/' . ($subdir ?: date('Y/m'));
        $name   = $code . '__' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $ext    = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        $storedPath = $file->storeAs($folder, $name . '.' . $ext, $disk);

        return [
            $storedPath,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }
    private function saveBase64File(string $b64, string $originalName, string $mime, Assets $asset, string $code): array
    {
        $disk   = 'public';
        $folder = 'disposals/' . date('Y/m');
        $name   = $code . '__' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

        // infer extension from mime or original
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        if (!$ext) {
            $map = [
                'application/pdf' => 'pdf',
                'image/png'       => 'png',
                'image/jpeg'      => 'jpg',
                'image/webp'      => 'webp',
                'image/gif'       => 'gif',
                'text/plain'      => 'txt',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'text/csv' => 'csv',
            ];
            $ext = $map[strtolower($mime)] ?? 'bin';
        }

        // strip data URL prefix if present
        if (str_starts_with($b64, 'data:')) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }

        $binary = base64_decode($b64, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 file data.');
        }

        $storedPath = $folder . '/' . $name . '.' . strtolower($ext);
        Storage::disk($disk)->put($storedPath, $binary);

        return [$storedPath, $originalName, $mime, strlen($binary)];
    }
}
