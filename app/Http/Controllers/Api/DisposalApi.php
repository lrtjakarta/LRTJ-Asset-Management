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
            'workflow'     => ['nullable', 'string', 'max:50'], // old workflow
            'target'       => ['nullable', 'string', 'max:50'],

            'uuid'         => ['nullable', 'uuid'],
            'uuids'        => ['nullable'],
            'uuids.*'      => ['uuid'],

            'asset_uuid'   => ['nullable'],
            'asset_uuid.*' => ['nullable', 'uuid'],

            // datatable_all-style filters
            'status'       => ['nullable', 'string', 'max:50'],
            'requester'    => ['nullable', 'string', 'max:255'],
            'updated_from' => ['nullable', 'date'],
            'updated_to'   => ['nullable', 'date'],
            'created_from' => ['nullable', 'date'],
            'created_to'   => ['nullable', 'date'],
            'asset_q'      => ['nullable', 'string', 'max:200'],

            'sort_by'      => ['nullable', Rule::in(['created_at', 'updated_at', 'kode_status', 'target_status', 'asset_code'])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', Rule::in(['0', '1'])],

            // legacy created_at range
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
        ])->validate();

        $tz      = config('app.timezone', 'UTC');
        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';

        // normalize uuid set
        $uuidSet = collect(
            is_array($request->uuids)
                ? $request->uuids
                : (is_string($request->uuids) ? explode(',', $request->uuids) : [])
        )->when($request->filled('uuid'), fn($c) => $c->push($request->uuid))
            ->map(fn($x) => trim($x))
            ->filter(fn($x) => Str::isUuid($x))
            ->unique()
            ->values();

        // normalize asset_uuid
        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )->map(fn($x) => trim($x))
            ->filter(fn($x) => Str::isUuid($x))
            ->unique()
            ->values();

        // datatable_all params
        $status       = trim((string) ($v['status'] ?? ''));
        $requester    = trim((string) ($v['requester'] ?? ''));
        $updatedFrom  = $v['updated_from'] ?? null;
        $updatedTo    = $v['updated_to'] ?? null;
        $createdFrom  = $v['created_from'] ?? null;
        $createdTo    = $v['created_to'] ?? null;
        $assetQ       = trim((string) ($v['asset_q'] ?? ''));

        $q = Disposal::query()
            ->select([
                'assets_disposals.*',
                DB::raw('a.asset_code as asset_code'),
                DB::raw('a.description as asset_description'),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_disposals.asset_uuid')
            ->with([
                'status:uuid,kode,name',
                'target:uuid,kode,name',
                'asset:uuid,asset_code,description',
            ])
            ->when($request->boolean('with_trashed'), fn($x) => $x->withTrashed())
            ->when($uuidSet->isNotEmpty(),    fn($x) => $x->whereIn('assets_disposals.uuid', $uuidSet))
            ->when($assetUuids->isNotEmpty(), fn($x) => $x->whereIn('assets_disposals.asset_uuid', $assetUuids))
            // keep old "target"
            ->when(!empty($v['target']),      fn($x) => $x->where('assets_disposals.target_status', $v['target']));

        // === workflow / status logic (match datatable_all) ===
        if ($request->filled('workflow') && $status === '') {
            $q->where('assets_disposals.kode_status', $v['workflow']);
        }

        if ($status !== '') {
            $q->where('assets_disposals.kode_status', $status);
        }

        if ($requester !== '') {
            $q->where('assets_disposals.pic_request_uid', $requester);
        }

        if ($updatedFrom) {
            $q->whereDate('assets_disposals.updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('assets_disposals.updated_at', '<=', $updatedTo);
        }

        if ($createdFrom) {
            $q->whereDate('assets_disposals.created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $q->whereDate('assets_disposals.created_at', '<=', $createdTo);
        }

        // legacy created_at range
        if (!empty($v['from'])) {
            $q->where('assets_disposals.created_at', '>=', $v['from']);
        }
        if (!empty($v['to'])) {
            $q->where('assets_disposals.created_at', '<=', $v['to']);
        }

        // asset_q (asset_code / description)
        if ($assetQ !== '') {
            $q->where(function ($w) use ($assetQ) {
                $w->where('a.asset_code', 'ILIKE', "%{$assetQ}%")
                    ->orWhere('a.description', 'ILIKE', "%{$assetQ}%");
            });
        }

        // generic q search (keep old behavior)
        if (!empty($v['q'])) {
            $like = '%' . trim($v['q']) . '%';
            $q->where(function ($w) use ($like) {
                $w->where('assets_disposals.disposal_code',  'ILIKE', $like)
                    ->orWhere('assets_disposals.kode_status',   'ILIKE', $like)
                    ->orWhere('assets_disposals.target_status', 'ILIKE', $like)
                    ->orWhere('assets_disposals.note',          'ILIKE', $like)
                    ->orWhere('a.asset_code',                   'ILIKE', $like)
                    ->orWhere('a.description',                  'ILIKE', $like);
            });
        }

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
                        'status'       => $request->query('status'),
                        'requester'    => $request->query('requester'),
                        'updated_from' => $request->query('updated_from'),
                        'updated_to'   => $request->query('updated_to'),
                        'created_from' => $request->query('created_from'),
                        'created_to'   => $request->query('created_to'),
                        'asset_q'      => $request->query('asset_q'),
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
                    'status'       => $request->query('status'),
                    'requester'    => $request->query('requester'),
                    'updated_from' => $request->query('updated_from'),
                    'updated_to'   => $request->query('updated_to'),
                    'created_from' => $request->query('created_from'),
                    'created_to'   => $request->query('created_to'),
                    'asset_q'      => $request->query('asset_q'),
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
                'items'                         => ['required', 'array', 'min:1'],
                'items.*.uuid'                  => ['nullable', 'uuid'],
                'items.*.asset_uuid'            => ['required', 'uuid', 'exists:assets,uuid'],
                'items.*.note'                  => ['nullable', 'string', 'max:1000'],
                'items.*.target_status'         => ['nullable', 'string', 'max:50'],
                'items.*.created_at'            => ['nullable', 'date'],
                'items.*.file_b64'              => ['nullable', 'array'],
                'items.*.file_b64.name'         => ['required_with:items.*.file_b64', 'string', 'max:255'],
                'items.*.file_b64.mime'         => ['required_with:items.*.file_b64', 'string', 'max:100'],
                'items.*.file_b64.data'         => ['required_with:items.*.file_b64', 'string'],
            ]);
        } else {
            $root = $request->validate([
                'uuid'              => ['nullable', 'uuid'],
                'asset_uuid'        => ['required', 'uuid', 'exists:assets,uuid'],
                'note'              => ['nullable', 'string', 'max:1000'],
                'target_status'     => ['nullable', 'string', 'max:50'],
                'created_at'        => ['nullable', 'date'],
                'file'              => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
                'file_b64'          => ['nullable', 'array'],
                'file_b64.name'     => ['required_with:file_b64', 'string', 'max:255'],
                'file_b64.mime'     => ['required_with:file_b64', 'string', 'max:100'],
                'file_b64.data'     => ['required_with:file_b64', 'string'],
            ]);
        }

        $uid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        abort_if($uid === '', 401, 'No session UID.');

        $items   = $isBatch ? $root['items'] : [$root];
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
                return response()->json(
                    $res,
                    ($res['ok'] ?? false)
                        ? (($res['duplicate'] ?? false) ? 200 : 201)
                        : 422
                );
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
                return [
                    'ok'        => true,
                    'duplicate' => true,
                    'via'       => 'uuid',
                    'id'        => $existing->uuid,
                    'code'      => $existing->disposal_code,
                ];
            }
        }

        $asset = Assets::select('uuid', 'asset_code', 'kode_status')->findOrFail($data['asset_uuid']);

        $target = $data['target_status'] ?? 'DIS';
        $ok = MasterStatus::where('kode', $target)
            ->where(fn($q) => $q->where('type', 'Asset')->orWhereNull('type'))
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

                // NEW: initial flow (same idea as DisposalController)
                'flow'            => $this->buildFlowTemplate($uid),
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            return Disposal::create($payload);
        });

        return ['ok' => true, 'id' => $row->uuid, 'code' => $row->disposal_code];
    }

    /**
     * Normalize flow for API: always return an array
     * If DB flow is null/empty/invalid, build default template (do NOT change kode_status).
     */
    protected function normalizeFlow(Disposal $d): array
    {
        $flow = $d->flow;

        // If casted to array already, use it
        if (is_array($flow) && isset($flow['steps'])) {
            return $flow;
        }

        // If it's a non-empty string, try to decode
        if (is_string($flow) && $flow !== '') {
            $decoded = json_decode($flow, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['steps'])) {
                return $decoded;
            }
        }

        // Fallback: build default template using pic_request_uid if available
        return $this->buildFlowTemplate($d->pic_request_uid ?? null);
    }

    protected function getNextPendingFlowIndex(?array $flow): ?int
    {
        if (!is_array($flow) || !isset($flow['steps']) || !is_array($flow['steps'])) {
            return null;
        }
        foreach ($flow['steps'] as $idx => $step) {
            if (empty($step['approved_at'])) {
                return $idx;
            }
        }
        return null;
    }

    protected function mapRows($rows, string $tz)
    {
        return $rows->map(function (Disposal $t) use ($tz) {
            $workflowLabel = $t->status ? ($t->kode_status . ' - ' . $t->status->name) : $t->kode_status;
            $targetLabel   = $t->target ? ($t->target_status . ' - ' . $t->target->name) : $t->target_status;

            // main attachment (original file)
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
                    // prefer kind-based download route
                    'download_url'  => route('files.download.kind', ['kind' => 'disposal', 'uuid' => $t->uuid]),
                    'file_url'      => $exists ? url('storage/' . ltrim($t->file_path, '/')) : null,
                ];
            }

            // flow form file
            $formFileObj = null;
            if ($t->flow_file_path) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($t->flow_file_path);
                $size   = $t->flow_file_size ?? ($exists ? $fs->size($t->flow_file_path) : null);
                $mtime  = $exists ? $fs->lastModified($t->flow_file_path) : null;

                $formFileObj = [
                    'name'          => $t->flow_file_name ?: basename($t->flow_file_path),
                    'mime'          => $t->flow_file_mime,
                    'size'          => $size,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'file_url'      => $exists ? url('storage/' . ltrim($t->flow_file_path, '/')) : null,
                ];
            }

            // BA file
            $baFileObj = null;
            if ($t->ba_file_path) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($t->ba_file_path);
                $size   = $t->ba_file_size ?? ($exists ? $fs->size($t->ba_file_path) : null);
                $mtime  = $exists ? $fs->lastModified($t->ba_file_path) : null;

                $baFileObj = [
                    'name'          => $t->ba_file_name ?: basename($t->ba_file_path),
                    'mime'          => $t->ba_file_mime,
                    'size'          => $size,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'file_url'      => $exists ? url('storage/' . ltrim($t->ba_file_path, '/')) : null,
                ];
            }

            // FLOW
            $flow         = $this->normalizeFlow($t);
            $pendingIndex = $this->getNextPendingFlowIndex($flow);

            return [
                'uuid'            => $t->uuid,
                'disposal_code'   => $t->disposal_code,

                'asset_uuid'      => $t->asset_uuid,
                'asset_code'      => $t->getAttribute('asset_code') ?? $t->asset?->asset_code,
                'asset_description' => $t->getAttribute('asset_description') ?? $t->asset?->description,
                'asset_label'     => ($t->asset?->asset_code ? $t->asset->asset_code : $t->asset_uuid),

                'kode_status'     => $t->kode_status,
                'workflow_label'  => $workflowLabel,

                'target_status'   => $t->target_status,
                'target_label'    => $targetLabel,

                'before_status'   => $t->before_status,
                'pic_request_uid' => $t->pic_request_uid,
                'pic_approve_uid' => $t->pic_approve_uid,

                'note'            => $t->note,
                'file'            => $fileObj,

                // form/BA info
                'form_file'       => $formFileObj,
                'ba_file'         => $baFileObj,
                'form_file_url'   => $t->flow_file_url ?? null,
                'ba_file_url'     => $t->ba_file_url ?? null,
                'form_download_url' => route('disposal.form', $t->uuid),
                'ba_download_url'   => route('disposal.ba', $t->uuid),

                // FLOW
                'flow'              => $flow,
                'flow_pending_index' => $pendingIndex,

                'created_at'      => optional($t->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'      => optional($t->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'      => optional($t->deleted_at)->timezone($tz)?->toIso8601String(),
            ];
        });
    }

    /**
     * Build default disposal flow (same semantics as controller):
     * 1. Create (User Department) – auto-approved by creator
     * 2. Dept.Head / Section
     * 3. Asset Management (BA)
     */
    protected function buildFlowTemplate(?string $creatorName = null): array
    {
        $now = now()->toDateTimeString();

        return [
            'key'   => 'disposal_request',
            'steps' => [
                [
                    'code'        => 'create',
                    'label'       => 'Create Disposal Request',
                    'role'        => 'User Department',
                    'approved_by' => $creatorName,
                    'approved_at' => $creatorName ? $now : null,
                ],
                [
                    'code'        => 'dept_head',
                    'label'       => 'Approval Dept.Head / Section',
                    'role'        => 'Dept.Head / Section',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'asset_mgt',
                    'label'       => 'Execution & BA Disposal (Asset Management)',
                    'role'        => 'Asset Management',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
            ],
        ];
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
            'image/png',
            'image/jpeg',
            'image/webp',
            'image/gif',
            'text/plain',
            'text/csv',
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
