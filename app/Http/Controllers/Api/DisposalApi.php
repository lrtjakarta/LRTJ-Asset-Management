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
        $v = validator($request->all(), [
            'q'            => ['nullable', 'string', 'max:200'],
            'workflow'     => ['nullable', 'string', 'max:50'],
            'target'       => ['nullable', 'string', 'max:50'],

            'uuid'         => ['nullable', 'uuid'],
            'uuids'        => ['nullable'],
            'uuids.*'      => ['uuid'],

            'asset_uuid'   => ['nullable'],
            'asset_uuid.*' => ['nullable', 'uuid'],

            'sort_by'      => ['nullable', Rule::in(['created_at', 'updated_at', 'kode_status', 'target_status', 'asset_code'])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', Rule::in(['0', '1'])],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
        ])->validate();

        $tz = config('app.timezone', 'UTC');
        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';

        $uuidSet = collect(
            is_array($request->uuids)
                ? $request->uuids
                : (is_string($request->uuids) ? explode(',', $request->uuids) : [])
        )->when($request->filled('uuid'), fn($c) => $c->push($request->uuid))
         ->map(fn($x) => trim($x))
         ->filter(fn($x) => Str::isUuid($x))
         ->unique()->values();

        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )->map(fn($x) => trim($x))
         ->filter(fn($x) => Str::isUuid($x))
         ->unique()->values();

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
            ->when($uuidSet->isNotEmpty(),      fn($x) => $x->whereIn('assets_disposals.uuid', $uuidSet))
            ->when($assetUuids->isNotEmpty(),   fn($x) => $x->whereIn('assets_disposals.asset_uuid', $assetUuids))
            ->when(!empty($v['workflow']),      fn($x) => $x->where('assets_disposals.kode_status',   $v['workflow']))
            ->when(!empty($v['target']),        fn($x) => $x->where('assets_disposals.target_status', $v['target']))
            ->when(!empty($v['from']),          fn($x) => $x->where('assets_disposals.created_at', '>=', $v['from']))
            ->when(!empty($v['to']),            fn($x) => $x->where('assets_disposals.created_at', '<=', $v['to']))
            ->when(!empty($v['q']), function ($x) use ($v) {
                $like = '%' . trim($v['q']) . '%';
                $x->where(function ($w) use ($like) {
                    $w->where('assets_disposals.disposal_code',   'ILIKE', $like)
                      ->orWhere('assets_disposals.kode_status',    'ILIKE', $like)
                      ->orWhere('assets_disposals.target_status',  'ILIKE', $like)
                      ->orWhere('assets_disposals.note',           'ILIKE', $like)
                      ->orWhere('a.asset_code',                    'ILIKE', $like)
                      ->orWhere('a.description',                   'ILIKE', $like);
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
                        'uuid'         => $request->query('uuid'),
                        'uuids'        => $uuidSet->all(),
                        'asset_uuid'   => $assetUuids->all(),
                        'q'            => $request->query('q'),
                        'workflow'     => $request->query('workflow'),
                        'target'       => $request->query('target'),
                        'with_trashed' => $request->query('with_trashed'),
                        'from'         => $request->query('from'),
                        'to'           => $request->query('to'),
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
                    'uuid'         => $request->query('uuid'),
                    'uuids'        => $uuidSet->all(),
                    'asset_uuid'   => $assetUuids->all(),
                    'q'            => $request->query('q'),
                    'workflow'     => $request->query('workflow'),
                    'target'       => $request->query('target'),
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
        $tz = config('app.timezone', 'UTC');

        $row = Disposal::with([
            'status:id,kode,name',
            'target:id,kode,name',
            'asset:uuid,asset_code,description',
        ])->where('uuid', $uuid)->firstOrFail();

        $data = $this->mapRows(collect([$row]), $tz)->first();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $isBatch = is_array($request->input('items'));

        if ($isBatch) {
            $root = $request->validate([
                'items'                         => ['required','array','min:1'],
                'items.*.uuid'                  => ['nullable','uuid'],
                'items.*.asset_uuid'            => ['required','uuid','exists:assets,uuid'],
                'items.*.note'                  => ['nullable','string','max:1000'],
                'items.*.target_status'         => ['nullable','string','max:50'],
                'items.*.created_at'            => ['nullable','date'],
                'items.*.file_b64'              => ['nullable','array'],
                'items.*.file_b64.name'         => ['required_with:items.*.file_b64','string','max:255'],
                'items.*.file_b64.mime'         => ['required_with:items.*.file_b64','string','max:100'],
                'items.*.file_b64.data'         => ['required_with:items.*.file_b64','string'],
            ]);
        } else {
            $root = $request->validate([
                'uuid'              => ['nullable','uuid'],
                'asset_uuid'        => ['required','uuid','exists:assets,uuid'],
                'note'              => ['nullable','string','max:1000'],
                'target_status'     => ['nullable','string','max:50'],
                'created_at'        => ['nullable','date'],
                'file'              => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
                'file_b64'          => ['nullable','array'],
                'file_b64.name'     => ['required_with:file_b64', 'string','max:255'],
                'file_b64.mime'     => ['required_with:file_b64', 'string','max:100'],
                'file_b64.data'     => ['required_with:file_b64', 'string'],
            ]);
        }

        $uid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        abort_if($uid === '', 401, 'No session UID.');

        $items   = $isBatch ? $root['items'] : [ $root ];
        $results = [];

        foreach ($items as $idx => $payload) {
            try {
                $res = $this->createDisposalFromPayload($request, $payload, $uid);
            } catch (\Throwable $e) {
                $res = ['ok' => false, 'index' => $idx, 'error' => $e->getMessage()];
            }

            if ($isBatch) {
                $results[] = $res;
            } else {
                return response()->json($res, ($res['ok'] ?? false) ? (($res['duplicate'] ?? false) ? 200 : 201) : 422);
            }
        }

        return response()->json([
            'ok'      => collect($results)->every(fn($r) => data_get($r, 'ok') === true),
            'batch'   => true,
            'results' => $results,
        ]);
    }

    private function createDisposalFromPayload(Request $rootReq, array $data, string $uid): array
    {
        if (!empty($data['uuid'])) {
            if ($existing = Disposal::where('uuid', $data['uuid'])->first()) {
                return ['ok' => true, 'duplicate' => true, 'via' => 'uuid', 'id' => $existing->uuid, 'code' => $existing->disposal_code];
            }
        }

        $asset = Assets::select('uuid','asset_code','kode_status')->findOrFail($data['asset_uuid']);

        $target = $data['target_status'] ?? 'DIS';
        $ok = MasterStatus::where('kode', $target)
            ->where(fn($q) => $q->where('type','Asset')->orWhereNull('type'))
            ->exists();
        abort_unless($ok, 422, 'Target disposal status not valid.');

        $seq  = Disposal::where('asset_uuid', $asset->uuid)->count() + 1;
        $code = 'DSP-' . str_replace(['-', ' '], '', $asset->asset_code) . '-' . $seq;

        $path = $orig = $mime = $size = null;
        if (!empty($data['file_b64'])) {
            $approx = $this->approxBase64Size($data['file_b64']['data']);
            if ($approx > 20 * 1024 * 1024) {
                throw new \RuntimeException('Base64 file too large (max 20MB).');
            }
            $this->assertAllowedMime($data['file_b64']['mime']);

            [$path, $orig, $mime, $size] = $this->saveBase64File(
                $data['file_b64']['data'],
                $data['file_b64']['name'],
                $data['file_b64']['mime'],
                $code
            );
        } elseif ($rootReq->file('file')) {
            [$path, $orig, $mime, $size] = $this->saveUpload($rootReq->file('file'), $code);
        }

        $row = DB::transaction(function () use ($data, $asset, $target, $code, $uid, $path, $orig, $mime, $size) {
            $payload = [
                'uuid'            => $data['uuid'] ?? (string) Str::uuid(),
                'asset_uuid'      => $asset->uuid,
                'disposal_code'   => $code,
                'target_status'   => $target,
                'kode_status'     => 'APR',
                'note'            => $data['note'] ?? null,

                'file_path'       => $path,
                'file_name'       => $orig,
                'file_mime'       => $mime,
                'file_size'       => $size,

                'pic_request_uid' => $uid,
                'before_status'   => $asset->kode_status,
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            return Disposal::create($payload);
        });

        return ['ok' => true, 'id' => $row->uuid, 'code' => $row->disposal_code];
    }

    protected function mapRows($rows, string $tz)
    {
        return $rows->map(function (Disposal $t) use ($tz) {
            $workflowLabel = $t->status ? ($t->kode_status . ' - ' . $t->status->name) : $t->kode_status;
            $targetLabel   = $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status;

            $fileObj = null;
            if ($t->file_path) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
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
                'asset_label'     => ($t->asset?->asset_code ? $t->asset->asset_code : $t->asset_uuid),

                'kode_status'     => $t->kode_status,
                'workflow_label'  => $workflowLabel,

                'target_status'   => $t->target_status,
                'target_label'    => $targetLabel,

                'note'            => $t->note,
                'file'            => $fileObj,

                'created_at'      => optional($t->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'      => optional($t->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'      => optional($t->deleted_at)->timezone($tz)?->toIso8601String(),
            ];
        });
    }

    private function saveUpload(?UploadedFile $file, string $code): array
    {
        if (!$file) return [null, null, null, null];

        $disk   = 'public';
        $folder = 'disposals/' . date('Y/m');
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

    private function saveBase64File(string $b64, string $originalName, string $mime, string $code): array
    {
        $disk   = 'public';
        $folder = 'disposals/' . date('Y/m');
        $name   = $code . '__' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

        if (str_starts_with($b64, 'data:')) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }

        $binary = base64_decode($b64, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 file data.');
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: match (strtolower($mime)) {
            'application/pdf' => 'pdf',
            'image/png'       => 'png',
            'image/jpeg'      => 'jpg',
            'image/webp'      => 'webp',
            'image/gif'       => 'gif',
            'text/plain'      => 'txt',
            'text/csv'        => 'csv',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            default => 'bin',
        });

        $storedPath = $folder . '/' . $name . '.' . $ext;
        Storage::disk($disk)->put($storedPath, $binary);

        return [$storedPath, $originalName, $mime, strlen($binary)];
    }

    private function approxBase64Size(string $b64): int
    {
        if (str_starts_with($b64, 'data:')) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }
        $len = strlen($b64);
        $padding = substr($b64, -2) === '==' ? 2 : (substr($b64, -1) === '=' ? 1 : 0);
        return (int) (($len * 3) / 4) - $padding;
    }

    private function assertAllowedMime(string $mime): void
    {
        $allowed = [
            'application/pdf',
            'image/png','image/jpeg','image/webp','image/gif',
            'text/plain','text/csv',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        if (!in_array(strtolower($mime), $allowed, true)) {
            throw new \RuntimeException('MIME not allowed: ' . $mime);
        }
    }
}
