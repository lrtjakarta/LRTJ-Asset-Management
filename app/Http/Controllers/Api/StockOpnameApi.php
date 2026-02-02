<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assets;
use App\Models\Disposal;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Validator;

class StockOpnameApi extends Controller
{
    protected function canReadStockOpname(Request $request): bool
    {
        $u = $request->user();
        return $u && method_exists($u, 'hasAction')
            ? $u->hasAction('STOCK_OPN', 'R')
            : true;
    }

    protected function canCreateStockOpname(Request $request): bool
    {
        $u = $request->user();
        return $u && method_exists($u, 'hasAction')
            ? $u->hasAction('STOCK_OPN', 'C')
            : true;
    }
    protected function assertOpenProject(string $projectUuid): void
    {
        $projectOk = DB::table('asset_projects')
            ->whereNull('deleted_at')
            ->where('uuid', $projectUuid)
            ->where('status', 'OPEN')
            ->exists();

        abort_if(!$projectOk, 422, 'Project not found or CLOSED');
    }

    protected function attachAssetToProject(string $projectUuid, string $assetUuid): void
    {
        DB::table('asset_project_assets')->updateOrInsert(
            [
                'project_uuid' => $projectUuid,
                'asset_uuid'   => $assetUuid,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
    /**
     * GET /api/v1/stock-opname
     *
     * Union MOVEMENT (Transfer ACC) + DISPOSAL (ACC) dengan filter & pagination.
     */
    /**
     * GET /api/v1/stock-opname
     *
     * Union MOVEMENT (Transfer ACC) + DISPOSAL (ACC) dengan filter & pagination.
     * NOTE: khusus correction: hanya yang punya project_uuid (whereNotNull)
     */
    public function index(Request $request)
    {
        abort_unless($this->canReadStockOpname($request), 403);

        $v = $request->validate([
            'q'           => ['nullable', 'string', 'max:200'],
            'source'      => ['nullable', Rule::in(['transfer', 'disposal'])], // null = both
            'tf_type'     => ['nullable', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'type'        => ['nullable', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'requester'   => ['nullable', 'string', 'max:255'],

            'asset_uuid'   => ['nullable'],
            'asset_uuid.*' => ['uuid'],

            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date'],
            'updated_from' => ['nullable', 'date'],
            'updated_to'   => ['nullable', 'date'],
            'created_from' => ['nullable', 'date'],
            'created_to'   => ['nullable', 'date'],

            'asset_q'      => ['nullable', 'string', 'max:200'],

            'sort_by'  => ['nullable', Rule::in(['updated_at', 'code', 'asset_code', 'type', 'source'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],

            'project_uuid' => ['nullable', 'uuid'],
        ]);

        $tz      = config('app.timezone', 'UTC');
        $sortBy  = $v['sort_by'] ?? 'updated_at';
        $sortDir = $v['sort_dir'] ?? 'desc';

        // normalize asset_uuid: array atau string "uuid1,uuid2"
        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )
            ->map(fn($x) => trim((string) $x))
            ->filter(fn($x) => Str::isUuid($x))
            ->unique()
            ->values();

        $source      = $v['source'] ?? null;
        $tfType      = $v['tf_type'] ?? ($v['type'] ?? null);
        $assetQ      = trim((string) ($v['asset_q'] ?? ''));
        $requester   = trim((string) ($v['requester'] ?? ''));
        $qSearch     = trim((string) ($v['q'] ?? ''));
        $projectUuid = $v['project_uuid'] ?? null;

        $dateFrom    = $v['date_from']    ?? null;
        $dateTo      = $v['date_to']      ?? null;
        $updatedFrom = $v['updated_from'] ?? null;
        $updatedTo   = $v['updated_to']   ?? null;
        $createdFrom = $v['created_from'] ?? null;
        $createdTo   = $v['created_to']   ?? null;

        /**
         * IMPORTANT:
         * - Kolom select antara transfer & disposal HARUS sama persis (jumlah & urutan)
         * - Kita include project_status buat Android (t.projectStatus)
         */

        // === base transfer query ===
        $t = DB::table('assets_transfers as t')
            ->join('assets as a', 'a.uuid', '=', 't.asset_uuid')
            ->leftJoin('asset_projects as p', 'p.uuid', '=', 't.project_uuid')
            ->selectRaw("
            t.uuid,
            t.asset_uuid,
            t.project_uuid,
            COALESCE(p.name,'')   as project_name,
            COALESCE(p.status,'') as project_status,

            a.asset_code,
            a.description,

            t.transfer_code       as code,
            'transfer'            as source_type,
            t.type                as tf_type,

            COALESCE(t.before->>'value','') as before_val,
            COALESCE(t.after->>'value','')  as after_val,

            t.pic_request_uid,
            t.pic_approve_uid,
            t.note,

            t.file_name,
            t.file_path,

            t.flow_file_name,
            t.flow_file_path,

            NULL::text            as ba_file_name,
            NULL::text            as ba_file_path,

            t.updated_at,
            t.created_at
        ")
            ->whereNull('t.deleted_at')
            ->whereNotNull('t.project_uuid')
            ->where('t.kode_status', 'ACC');

        // === base disposal query ===
        $d = DB::table('assets_disposals as d')
            ->join('assets as a', 'a.uuid', '=', 'd.asset_uuid')
            ->leftJoin('asset_projects as p', 'p.uuid', '=', 'd.project_uuid')
            ->selectRaw("
            d.uuid,
            d.asset_uuid,
            d.project_uuid,
            COALESCE(p.name,'')   as project_name,
            COALESCE(p.status,'') as project_status,

            a.asset_code,
            a.description,

            d.disposal_code       as code,
            'disposal'            as source_type,
            NULL::text            as tf_type,

            COALESCE(d.before_status,'') as before_val,
            COALESCE('DIS','')           as after_val,

            d.pic_request_uid,
            d.pic_approve_uid,
            d.note,

            d.file_name,
            d.file_path,

            d.flow_file_name,
            d.flow_file_path,

            d.ba_file_name,
            d.ba_file_path,

            d.updated_at,
            d.created_at
        ")
            ->whereNull('d.deleted_at')
            ->whereNotNull('d.project_uuid')
            ->where('d.kode_status', 'ACC');

        // filters that must apply to BOTH base queries (before union)
        if ($assetUuids->isNotEmpty()) {
            $t->whereIn('t.asset_uuid', $assetUuids);
            $d->whereIn('d.asset_uuid', $assetUuids);
        }

        if (!empty($projectUuid)) {
            $t->where('t.project_uuid', $projectUuid);
            $d->where('d.project_uuid', $projectUuid);
        }

        // build union
        if ($source === 'transfer') {
            $union = $t;
        } elseif ($source === 'disposal') {
            $union = $d;
        } else {
            // unionAll returns builder of $t
            $union = $t->unionAll($d);
        }

        $q = DB::query()->fromSub($union, 'u');

        /**
         * tf_type filter: hanya masuk akal buat transfer.
         * Jangan sampai disposal ikut “ke-buang” kalau source=both.
         */
        if (!empty($tfType)) {
            $q->where(function ($qq) use ($tfType) {
                $qq->where('source_type', 'transfer')
                    ->where('tf_type', $tfType);
            });
        }

        // created_at filter (pakai date_from/date_to)
        if ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        }

        // optional created_from/created_to (override style)
        if ($createdFrom) {
            $q->whereDate('created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $q->whereDate('created_at', '<=', $createdTo);
        }

        // updated_at filter
        if ($updatedFrom) {
            $q->whereDate('updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('updated_at', '<=', $updatedTo);
        }

        // asset_q filter
        if ($assetQ !== '') {
            $q->where(function ($qq) use ($assetQ) {
                $qq->where('asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('description', 'ilike', "%{$assetQ}%");
            });
        }

        // requester filter (simple contains)
        if ($requester !== '') {
            $q->where('pic_request_uid', 'ilike', "%{$requester}%");
        }

        // global search
        if ($qSearch !== '') {
            $q->where(function ($qq) use ($qSearch) {
                $qq->where('code', 'ilike', "%{$qSearch}%")
                    ->orWhere('asset_code', 'ilike', "%{$qSearch}%")
                    ->orWhere('description', 'ilike', "%{$qSearch}%")
                    ->orWhere('project_name', 'ilike', "%{$qSearch}%")
                    ->orWhere('project_status', 'ilike', "%{$qSearch}%")
                    ->orWhere('note', 'ilike', "%{$qSearch}%")
                    ->orWhere('pic_request_uid', 'ilike', "%{$qSearch}%")
                    ->orWhere('pic_approve_uid', 'ilike', "%{$qSearch}%");
            });
        }

        // sorting
        $sortColumns = [
            'updated_at' => 'updated_at',
            'code'       => 'code',
            'asset_code' => 'asset_code',
            'type'       => 'tf_type',
            'source'     => 'source_type',
        ];
        $orderBy = $sortColumns[$sortBy] ?? 'updated_at';

        $paginationRequested = $request->has('per_page') || $request->has('page');

        if (!$paginationRequested) {
            $rows = $q->orderBy($orderBy, $sortDir)->get();
            $data = $this->mapRows($rows, $tz);

            return response()->json([
                'data' => $data,
                'meta' => [
                    'total'     => $rows->count(),
                    'paginated' => false,
                    'sort_by'   => $sortBy,
                    'sort_dir'  => $sortDir,
                    'filters'   => [
                        'q'            => $qSearch,
                        'source'       => $source,
                        'tf_type'      => $tfType,
                        'requester'    => $requester,
                        'asset_uuid'   => $assetUuids->all(),
                        'asset_q'      => $assetQ,
                        'project_uuid' => $projectUuid,
                        'date_from'    => $dateFrom,
                        'date_to'      => $dateTo,
                        'updated_from' => $updatedFrom,
                        'updated_to'   => $updatedTo,
                        'created_from' => $createdFrom,
                        'created_to'   => $createdTo,
                    ],
                ],
            ]);
        }

        $perPage   = (int) ($v['per_page'] ?? 15);
        $page      = (int) ($v['page'] ?? 1);

        $paginator = $q->orderBy($orderBy, $sortDir)
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        $data = $this->mapRows($paginator->getCollection(), $tz);

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
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
                'last'  => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }


    /**
     * POST /api/v1/stock-opname/transfer
     */
    public function storeTransfer(Request $request)
    {
        abort_unless($this->canCreateStockOpname($request), 403);

        $user = $request->user();
        $uid  = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');

        // MODE BATCH
        if (is_array($request->input('items'))) {
            $data = $request->validate([
                'project_uuid'               => ['required', 'uuid'],

                'items'                      => ['required', 'array', 'min:1'],

                'items.*.uuid'               => ['nullable', 'uuid'],
                'items.*.asset_uuid'         => ['required', 'uuid', 'exists:assets,uuid'],
                'items.*.type'               => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
                'items.*.after.value'        => ['required', 'string'],
                'items.*.is_same'            => ['nullable', 'boolean'],
                'items.*.note'               => ['nullable', 'string', 'max:1000'],
                'items.*.created_at'         => ['nullable', 'date'],

                // evidence (optional)
                'items.*.file_b64'           => ['nullable', 'array'],
                'items.*.file_b64.name'      => ['required_with:items.*.file_b64', 'string', 'max:255'],
                'items.*.file_b64.mime'      => ['required_with:items.*.file_b64', 'string', 'max:100'],
                'items.*.file_b64.data'      => ['required_with:items.*.file_b64', 'string'],

                // flow form (optional)
                'items.*.flow_file_b64'      => ['nullable', 'array'],
                'items.*.flow_file_b64.name' => ['required_with:items.*.flow_file_b64', 'string', 'max:255'],
                'items.*.flow_file_b64.mime' => ['required_with:items.*.flow_file_b64', 'string', 'max:100'],
                'items.*.flow_file_b64.data' => ['required_with:items.*.flow_file_b64', 'string'],
            ]);

            $projectUuid = $data['project_uuid'];
            $this->assertOpenProject($projectUuid);

            $results = [];

            foreach ($data['items'] as $idx => $item) {
                try {
                    // inject project_uuid per item biar create() konsisten
                    $item['project_uuid'] = $projectUuid;

                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $item['uuid'] ?? null,
                        'result'       => $this->createStockOpnameTransfer($item, (string) $uid),
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $item['uuid'] ?? null,
                        'error'        => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'ok'    => true,
                'mode'  => 'batch',
                'items' => $results,
            ], 201);
        }

        // MODE SINGLE
        $data = $request->validate([
            'project_uuid'         => ['required', 'uuid'],

            'uuid'                 => ['nullable', 'uuid'],
            'asset_uuid'           => ['required', 'uuid', 'exists:assets,uuid'],
            'type'                 => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'          => ['required', 'string'],
            'is_same'              => ['nullable', 'boolean'],
            'note'                 => ['nullable', 'string', 'max:1000'],
            'created_at'           => ['nullable', 'date'],

            'file'                 => ['nullable', 'file', 'max:51200'],
            'file_b64'             => ['nullable', 'array'],
            'file_b64.name'        => ['required_with:file_b64', 'string', 'max:255'],
            'file_b64.mime'        => ['required_with:file_b64', 'string', 'max:100'],
            'file_b64.data'        => ['required_with:file_b64', 'string'],

            'flow_file'            => ['nullable', 'file', 'max:51200'],
            'flow_file_b64'        => ['nullable', 'array'],
            'flow_file_b64.name'   => ['required_with:flow_file_b64', 'string', 'max:255'],
            'flow_file_b64.mime'   => ['required_with:flow_file_b64', 'string', 'max:100'],
            'flow_file_b64.data'   => ['required_with:flow_file_b64', 'string'],
        ]);

        $this->assertOpenProject($data['project_uuid']);

        $res = $this->createStockOpnameTransfer($data, (string) $uid, $request);

        return response()->json($res, 201);
    }
    protected function createStockOpnameTransfer(array $data, string $uid, ?Request $request = null): array
    {
        // Idempotent by UUID
        if (!empty($data['uuid'])) {
            if ($existing = Transfer::where('uuid', $data['uuid'])->first()) {
                return [
                    'ok'        => true,
                    'duplicate' => true,
                    'via'       => 'uuid',
                    'id'        => $existing->uuid,
                    'code'      => $existing->transfer_code,
                ];
            }
        }

        $projectUuid = $data['project_uuid'] ?? null;
        abort_if(!$projectUuid, 422, 'project_uuid required');

        $asset = Assets::findOrFail($data['asset_uuid']);

        return DB::transaction(function () use ($asset, $data, $uid, $request, $projectUuid) {
            $assetSv = Assets::where('uuid', $asset->uuid)->lockForUpdate()->firstOrFail();

            // ✅ Auto-attach asset to project (NO abort)
            $this->attachAssetToProject($projectUuid, $assetSv->uuid);

            // BEFORE (current)
            $before = null;
            switch ($data['type']) {
                case 'owner':
                    $before = optional($assetSv->assignment)->asset_owner;
                    break;
                case 'user':
                    $before = optional($assetSv->assignment)->asset_user;
                    break;
                case 'maintenance':
                    $before = optional($assetSv->assignment)->asset_maintenance;
                    break;
                case 'status':
                    $before = $assetSv->kode_status;
                    break;
                case 'location':
                    $before = $assetSv->kode_location;
                    break;
            }

            $isSame = (bool)($data['is_same'] ?? false);

            // AFTER (requested)
            $after = data_get($data, 'after.value');
            if ($isSame) $after = $before; // hard enforce "Sesuai"

            // Update asset only if not sesuai + changed
            if (!$isSame && (string)$after !== (string)$before) {
                switch ($data['type']) {
                    case 'owner':
                        $assetSv->assignment()->updateOrCreate(
                            ['asset_uuid' => $assetSv->uuid],
                            ['asset_owner' => $after]
                        );
                        break;
                    case 'user':
                        $assetSv->assignment()->updateOrCreate(
                            ['asset_uuid' => $assetSv->uuid],
                            ['asset_user' => $after]
                        );
                        break;
                    case 'maintenance':
                        $assetSv->assignment()->updateOrCreate(
                            ['asset_uuid' => $assetSv->uuid],
                            ['asset_maintenance' => $after]
                        );
                        break;
                    case 'status':
                        $assetSv->kode_status = $after;
                        $assetSv->save();
                        break;
                    case 'location':
                        $assetSv->kode_location = $after;
                        $assetSv->save();
                        break;
                }
            }

            $beforeLabel = $this->resolveLabel($data['type'], $before);
            $afterLabel  = $this->resolveLabel($data['type'], $after);

            $note = trim((string)($data['note'] ?? ''));
            if ((string)$after === (string)$before) {
                $note = trim('[SESUAI] ' . $note);
            }

            // Code generator
            $now    = Carbon::now();
            $prefix = 'OPN' . $now->format('ym');

            $last = Transfer::where('transfer_code', 'like', $prefix . '%')
                ->orderBy('transfer_code', 'desc')
                ->lockForUpdate()
                ->first();

            $seq  = $last ? ((int)substr($last->transfer_code, -4)) + 1 : 1;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $uuid = $data['uuid'] ?? (string) Str::uuid();

            $path = $orig = $mime = $size = null;
            $flowPath = $flowOrig = $flowMime = $flowSize = null;

            // evidence (file or b64)
            if (!empty($data['file_b64'])) {
                $this->assertAllowedMime($data['file_b64']['mime']);
                $approx = $this->approxBase64Size($data['file_b64']['data']);
                if ($approx > 50 * 1024 * 1024) {
                    throw new \RuntimeException('Base64 file too large (max 50MB).');
                }
                [$path, $orig, $mime, $size] = $this->saveBase64ToDisk(
                    $data['file_b64']['data'],
                    $data['file_b64']['name'],
                    $data['file_b64']['mime'],
                    'transfers/' . $asset->uuid,
                    $code . '-file'
                );
            } elseif ($request && $request->file('file')) {
                [$path, $orig, $mime, $size] = $this->saveUploadTf(
                    $request->file('file'),
                    $asset,
                    $code,
                    null
                );
            }

            // flow form (file or b64)
            if (!empty($data['flow_file_b64'])) {
                $this->assertAllowedMime($data['flow_file_b64']['mime']);
                $approx = $this->approxBase64Size($data['flow_file_b64']['data']);
                if ($approx > 50 * 1024 * 1024) {
                    throw new \RuntimeException('Base64 file too large (max 50MB).');
                }
                [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveBase64ToDisk(
                    $data['flow_file_b64']['data'],
                    $data['flow_file_b64']['name'],
                    $data['flow_file_b64']['mime'],
                    'transfers/' . $asset->uuid,
                    $code . '-form'
                );
            } elseif ($request && $request->file('flow_file')) {
                [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveUploadTf(
                    $request->file('flow_file'),
                    $asset,
                    $code,
                    null
                );
            }

            $payload = [
                'uuid'            => $uuid,
                'asset_uuid'      => $asset->uuid,
                'project_uuid'    => $projectUuid,

                'transfer_code'   => $code,
                'type'            => $data['type'],
                'before'          => ['value' => $before],
                'after'           => ['value' => $after],
                'before_label'    => $beforeLabel,
                'after_label'     => $afterLabel,

                'kode_status'     => 'ACC',
                'note'            => $note ?: null,
                'pic_request_uid' => $uid,
                'pic_approve_uid' => $uid,

                'file_path'       => $path,
                'file_name'       => $orig,
                'file_mime'       => $mime,
                'file_size'       => $size,

                'flow_file_path'  => $flowPath,
                'flow_file_name'  => $flowOrig,
                'flow_file_mime'  => $flowMime,
                'flow_file_size'  => $flowSize,
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            $row = Transfer::create($payload);

            return [
                'ok'   => true,
                'id'   => $row->uuid,
                'code' => $row->transfer_code,
            ];
        });
    }


    /**
     * POST /api/v1/stock-opname/disposal
     */
    public function storeDisposal(Request $request)
    {
        abort_unless($this->canCreateStockOpname($request), 403);

        $user = $request->user();
        $uid  = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');

        // MODE BATCH
        if (is_array($request->input('items'))) {
            $data = $request->validate([
                'project_uuid'               => ['required', 'uuid'],

                'items'                      => ['required', 'array', 'min:1'],

                'items.*.uuid'               => ['nullable', 'uuid'],
                'items.*.asset_uuid'         => ['required', 'uuid', 'exists:assets,uuid'],
                'items.*.note'               => ['nullable', 'string', 'max:1000'],
                'items.*.target_status'      => ['nullable', 'string'],
                'items.*.reason'             => ['required', Rule::in(['Sale', 'Waste', 'Donate', 'Held'])],
                'items.*.created_at'         => ['nullable', 'date'],

                // evidence optional
                'items.*.file_b64'           => ['nullable', 'array'],
                'items.*.file_b64.name'      => ['required_with:items.*.file_b64', 'string', 'max:255'],
                'items.*.file_b64.mime'      => ['required_with:items.*.file_b64', 'string', 'max:100'],
                'items.*.file_b64.data'      => ['required_with:items.*.file_b64', 'string'],

                // flow REQUIRED (b64)
                'items.*.flow_file_b64'      => ['required', 'array'],
                'items.*.flow_file_b64.name' => ['required', 'string', 'max:255'],
                'items.*.flow_file_b64.mime' => ['required', 'string', 'max:100'],
                'items.*.flow_file_b64.data' => ['required', 'string'],

                // ba REQUIRED (b64)
                'items.*.ba_file_b64'        => ['required', 'array'],
                'items.*.ba_file_b64.name'   => ['required', 'string', 'max:255'],
                'items.*.ba_file_b64.mime'   => ['required', 'string', 'max:100'],
                'items.*.ba_file_b64.data'   => ['required', 'string'],
            ]);

            $projectUuid = $data['project_uuid'];
            $this->assertOpenProject($projectUuid);

            $results = [];

            foreach ($data['items'] as $idx => $item) {
                try {
                    $item['project_uuid'] = $projectUuid;

                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $item['uuid'] ?? null,
                        'result'       => $this->createStockOpnameDisposal($item, (string) $uid),
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $item['uuid'] ?? null,
                        'error'        => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'ok'    => true,
                'mode'  => 'batch',
                'items' => $results,
            ], 201);
        }

        // MODE SINGLE (multipart OR base64)
        $data = $request->validate([
            'project_uuid'   => ['required', 'uuid'],
            'uuid'           => ['nullable', 'uuid'],
            'asset_uuid'     => ['required', 'uuid', 'exists:assets,uuid'],
            'note'           => ['nullable', 'string', 'max:1000'],
            'target_status'  => ['nullable', 'string'],
            'reason'         => ['required', Rule::in(['Sale', 'Waste', 'Donate', 'Held'])],
            'created_at'     => ['nullable', 'date'],

            // evidence optional
            'file'           => ['nullable', 'file', 'max:51200'],
            'file_b64'       => ['nullable', 'array'],
            'file_b64.name'  => ['required_with:file_b64', 'string', 'max:255'],
            'file_b64.mime'  => ['required_with:file_b64', 'string', 'max:100'],
            'file_b64.data'  => ['required_with:file_b64', 'string'],

            // flow REQUIRED (either file or b64)
            'flow_file'      => ['required_without:flow_file_b64', 'file', 'max:51200'],
            'flow_file_b64'  => ['required_without:flow_file', 'array'],
            'flow_file_b64.name' => ['required_with:flow_file_b64', 'string', 'max:255'],
            'flow_file_b64.mime' => ['required_with:flow_file_b64', 'string', 'max:100'],
            'flow_file_b64.data' => ['required_with:flow_file_b64', 'string'],

            // ba REQUIRED (either file or b64)
            'ba_file'        => ['required_without:ba_file_b64', 'file', 'max:51200'],
            'ba_file_b64'    => ['required_without:ba_file', 'array'],
            'ba_file_b64.name' => ['required_with:ba_file_b64', 'string', 'max:255'],
            'ba_file_b64.mime' => ['required_with:ba_file_b64', 'string', 'max:100'],
            'ba_file_b64.data' => ['required_with:ba_file_b64', 'string'],
        ]);

        $this->assertOpenProject($data['project_uuid']);

        $res = $this->createStockOpnameDisposal($data, (string) $uid, $request);
        return response()->json($res, 201);
    }

    protected function createStockOpnameDisposal(array $data, string $uid, ?Request $request = null): array
    {
        // Idempotent by UUID
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

        $projectUuid = $data['project_uuid'] ?? null;
        abort_if(!$projectUuid, 422, 'project_uuid required');

        $asset = Assets::findOrFail($data['asset_uuid']);

        return DB::transaction(function () use ($asset, $data, $uid, $request, $projectUuid) {
            // lock asset row
            $assetSv = Assets::where('uuid', $asset->uuid)->lockForUpdate()->firstOrFail();

            $this->attachAssetToProject($projectUuid, $assetSv->uuid);

            // BEFORE/AFTER (disposal)
            $beforeStatus = (string) ($assetSv->kode_status ?? '');
            $afterStatus  = 'DIS';

            // Note
            $note = trim((string)($data['note'] ?? ''));

            // Code generator (mirip transfer)
            $now    = Carbon::now();
            $prefix = 'OPN' . $now->format('ym');

            $last = Disposal::where('disposal_code', 'like', $prefix . '%')
                ->orderBy('disposal_code', 'desc')
                ->lockForUpdate()
                ->first();

            $seq  = $last ? ((int)substr($last->disposal_code, -4)) + 1 : 1;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $uuid = $data['uuid'] ?? (string) Str::uuid();

            // ======================
            // FILES (evidence optional, flow required, BA required)
            // ======================
            $filePath = $fileOrig = $fileMime = $fileSize = null;

            $flowPath = $flowOrig = $flowMime = $flowSize = null;
            $baPath   = $baOrig   = $baMime   = $baSize   = null;

            // evidence optional: base64 OR multipart
            if (!empty($data['file_b64'])) {
                $this->assertAllowedMime($data['file_b64']['mime']);
                $approx = $this->approxBase64Size($data['file_b64']['data']);
                if ($approx > 50 * 1024 * 1024) {
                    throw new \RuntimeException('Base64 file too large (max 50MB).');
                }
                [$filePath, $fileOrig, $fileMime, $fileSize] = $this->saveBase64ToDisk(
                    $data['file_b64']['data'],
                    $data['file_b64']['name'],
                    $data['file_b64']['mime'],
                    'disposals/' . $assetSv->uuid,
                    $code . '-file'
                );
            } elseif ($request && $request->file('file')) {
                // kalau kamu sudah punya helper upload untuk disposal, ganti ke helper itu
                [$filePath, $fileOrig, $fileMime, $fileSize] = $this->saveUploadTf(
                    $request->file('file'),
                    $assetSv,
                    $code,
                    null
                );
            }

            // flow REQUIRED: base64 OR multipart
            if (!empty($data['flow_file_b64'])) {
                $this->assertAllowedMime($data['flow_file_b64']['mime']);
                $approx = $this->approxBase64Size($data['flow_file_b64']['data']);
                if ($approx > 50 * 1024 * 1024) {
                    throw new \RuntimeException('Base64 flow file too large (max 50MB).');
                }
                [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveBase64ToDisk(
                    $data['flow_file_b64']['data'],
                    $data['flow_file_b64']['name'],
                    $data['flow_file_b64']['mime'],
                    'disposals/' . $assetSv->uuid,
                    $code . '-form'
                );
            } elseif ($request && $request->file('flow_file')) {
                [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveUploadTf(
                    $request->file('flow_file'),
                    $assetSv,
                    $code,
                    null
                );
            }

            // BA REQUIRED: base64 OR multipart
            if (!empty($data['ba_file_b64'])) {
                $this->assertAllowedMime($data['ba_file_b64']['mime']);
                $approx = $this->approxBase64Size($data['ba_file_b64']['data']);
                if ($approx > 50 * 1024 * 1024) {
                    throw new \RuntimeException('Base64 BA file too large (max 50MB).');
                }
                [$baPath, $baOrig, $baMime, $baSize] = $this->saveBase64ToDisk(
                    $data['ba_file_b64']['data'],
                    $data['ba_file_b64']['name'],
                    $data['ba_file_b64']['mime'],
                    'disposals/' . $assetSv->uuid,
                    $code . '-ba'
                );
            } elseif ($request && $request->file('ba_file')) {
                [$baPath, $baOrig, $baMime, $baSize] = $this->saveUploadTf(
                    $request->file('ba_file'),
                    $assetSv,
                    $code,
                    null
                );
            }

            // enforce required
            if (!$flowPath || !$baPath) {
                throw new \RuntimeException('flow_file and ba_file are required for disposal');
            }

            // disposal fields
            $reason = $data['reason'] ?? null;
            if (!$reason) {
                throw new \RuntimeException('reason is required');
            }

            $targetStatus = $data['target_status'] ?? null;

            $payload = [
                'uuid'          => $uuid,
                'asset_uuid'    => $assetSv->uuid,
                'project_uuid'  => $projectUuid,

                'disposal_code' => $code,
                'reason'        => $reason,
                'target_status' => $targetStatus,

                'before_status' => $beforeStatus,
                'after_status'  => $afterStatus,

                'kode_status'   => 'ACC',
                'note'          => $note ?: null,

                'pic_request_uid' => $uid,
                'pic_approve_uid' => $uid,

                // evidence optional
                'file_path'     => $filePath,
                'file_name'     => $fileOrig,
                'file_mime'     => $fileMime,
                'file_size'     => $fileSize,

                // flow required
                'flow_file_path' => $flowPath,
                'flow_file_name' => $flowOrig,
                'flow_file_mime' => $flowMime,
                'flow_file_size' => $flowSize,

                // BA required
                'ba_file_path'   => $baPath,
                'ba_file_name'   => $baOrig,
                'ba_file_mime'   => $baMime,
                'ba_file_size'   => $baSize,
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            $row = Disposal::create($payload);

            // OPTIONAL: kalau controller kamu juga langsung set asset status DIS saat ACC,
            // uncomment ini (kalau memang business rule-nya begitu):
            if ($assetSv->kode_status !== 'DIS') {
                $assetSv->kode_status = 'DIS';
                $assetSv->save();
            }

            return [
                'ok'   => true,
                'id'   => $row->uuid,
                'code' => $row->disposal_code,
            ];
        });
    }


    // ======================= helpers =========================

    protected function mapRows($rows, string $tz)
    {
        return collect($rows)->map(function ($r) use ($tz) {
            $isTransfer = $r->source_type === 'transfer';

            $typeCode = $isTransfer
                ? ($r->tf_type ?? null)
                : 'DISPOSAL';

            $beforeCode = $r->before_val;
            $afterCode  = $r->after_val;

            $labelType  = $isTransfer ? $typeCode : 'status';

            $beforeLabel = $this->resolveLabel((string) $labelType, (string) $beforeCode);
            $afterLabel  = $this->resolveLabel((string) $labelType, (string) $afterCode);

            $assetLabel = trim(($r->asset_code ?? '') . ' — ' . ($r->description ?? ''));

            // main attachment
            $fileObj = null;
            if (!empty($r->file_path)) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($r->file_path);
                $size   = $exists ? $fs->size($r->file_path) : null;
                $mtime  = $exists ? $fs->lastModified($r->file_path) : null;

                $fileObj = [
                    'name'          => $r->file_name ?: basename($r->file_path),
                    'size'          => $size,
                    'mime'          => null,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'file_url'      => $exists ? url('storage/' . ltrim($r->file_path, '/')) : null,
                ];
            }

            // flow form
            $formFileObj = null;
            if (!empty($r->flow_file_path)) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($r->flow_file_path);
                $size   = $exists ? $fs->size($r->flow_file_path) : null;
                $mtime  = $exists ? $fs->lastModified($r->flow_file_path) : null;

                $formFileObj = [
                    'name'          => $r->flow_file_name ?: basename($r->flow_file_path),
                    'size'          => $size,
                    'mime'          => null,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'file_url'      => $exists ? url('storage/' . ltrim($r->flow_file_path, '/')) : null,
                ];
            }

            // BA file (only disposal)
            $baFileObj = null;
            if (!empty($r->ba_file_path)) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($r->ba_file_path);
                $size   = $exists ? $fs->size($r->ba_file_path) : null;
                $mtime  = $exists ? $fs->lastModified($r->ba_file_path) : null;

                $baFileObj = [
                    'name'          => $r->ba_file_name ?: basename($r->ba_file_path),
                    'size'          => $size,
                    'mime'          => null,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'file_url'      => $exists ? url('storage/' . ltrim($r->ba_file_path, '/')) : null,
                ];
            }

            $updatedAt = $r->updated_at
                ? Carbon::parse($r->updated_at, $tz)->timezone($tz)->toIso8601String()
                : null;

            // ✅ IMPORTANT FIX: pakai API preview route names (bukan route web)
            $formDownloadUrl = $isTransfer
                ? route('api.stockopname.preview.transfer-form', [
                    'asset_uuid'   => $r->asset_uuid,
                    'target_type'  => $r->tf_type,
                    'target_value' => $r->after_val,
                ])
                : route('api.stockopname.preview.disposal-form', [
                    'asset_uuid' => $r->asset_uuid,
                ]);

            $baDownloadUrl = $isTransfer
                ? null
                : route('api.stockopname.preview.disposal-ba', [
                    'asset_uuid' => $r->asset_uuid,
                ]);

            return [
                'uuid'              => $r->uuid,
                'asset_uuid'        => $r->asset_uuid,
                'asset_code'        => $r->asset_code,
                'asset_description' => $r->description,
                'asset_label'       => $assetLabel,

                'source_type'       => $r->source_type,
                'source_label'      => $isTransfer ? 'MOVEMENT' : 'DISPOSAL',

                'type'              => $typeCode ? strtoupper((string) $typeCode) : null,
                'code'              => $r->code,

                'before_value'      => $beforeCode,
                'after_value'       => $afterCode,
                'before_label'      => $beforeLabel,
                'after_label'       => $afterLabel,

                'project_uuid'      => $r->project_uuid,
                'project_name'      => $r->project_name,
                'note'              => $r->note,
                'pic_request_uid'   => $r->pic_request_uid,
                'pic_approve_uid'   => $r->pic_approve_uid,

                'file'              => $fileObj,
                'form_file'         => $formFileObj,
                'ba_file'           => $baFileObj,

                'form_download_url' => $formDownloadUrl,
                'ba_download_url'   => $baDownloadUrl,

                'updated_at'        => $updatedAt,
            ];
        });
    }

    private function saveUploadTf($file, Assets $asset, string $code, $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'transfers/' . $asset->uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        if ($existing && !empty($existing->file_path)) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [
            $path,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }

    private function saveUploadDis($file, Assets $asset, string $code, $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'disposals/' . $asset->uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        if ($existing && !empty($existing->file_path)) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [
            $path,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }

    private function saveBase64ToDisk(string $b64, string $originalName, string $mime, string $dir, string $prefix): array
    {
        $disk = 'public';

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
            default          => 'bin',
        });

        $filename   = $prefix . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $storedPath = trim($dir, '/') . '/' . $filename;

        Storage::disk($disk)->put($storedPath, $binary);

        return [$storedPath, $originalName, $mime, strlen($binary)];
    }

    private function approxBase64Size(string $b64): int
    {
        if (str_starts_with($b64, 'data:')) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }
        $len     = strlen($b64);
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

    private function resolveLabel($type, $code)
    {
        $type = is_string($type) ? trim($type) : '';
        $code = is_string($code) ? trim($code) : '';
        if ($code === '') return '(empty)';
        if ($type === '') return $code;

        switch ($type) {
            case 'owner':
            case 'user':
            case 'maintenance':
                $uc = MasterUserCode::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $uc ? trim($uc->kode . ' - ' . $uc->department) : $code;

            case 'status':
                $s = MasterStatus::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $s ? trim($s->kode . ' - ' . $s->name) : $code;

            case 'location':
                $l = MasterLocation::whereRaw('upper(kode)=?', [mb_strtoupper($code)])->first();
                return $l ? trim($l->kode . ' - ' . $l->name) : $code;

            default:
                return $code;
        }
    }

    /**
     * GET /api/v1/stock-opname/preview/disposal-form?asset_uuid=...
     */
    public function previewDisposalForm(Request $request)
    {
        abort_unless($this->canReadStockOpname($request), 403);

        $v = $request->validate([
            'asset_uuid' => ['required', 'uuid', 'exists:assets,uuid'],
        ]);

        $assetUuid = $v['asset_uuid'];

        $asset = Assets::with([
            'assignment.owner.division',
            'location',
            'value',
        ])->findOrFail($assetUuid);

        $assign   = $asset?->assignment;
        $owner    = $assign?->owner;
        $ownerDiv = $owner?->division;

        $now         = now()->timezone('Asia/Jakarta');
        $companyName = config('app.company_name', 'PT LRT Jakarta');

        $deptLabel = $owner?->department ?: '';
        $divLabel  = $ownerDiv?->name ?? '';

        $assetCode = $asset?->asset_code ?? '';
        $assetName = $asset?->asset_name ?? $asset?->description ?? '';

        $acqDate = null;
        if ($asset?->value?->actual_date) {
            $acqDate = Carbon::parse($asset->value->actual_date)
                ->timezone('Asia/Jakarta');
        }

        $comCost = $asset?->value?->total ?? 0;

        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $asset?->uuid)
            ->first();

        $comAcc = $ledger?->acc_sum ?? 0;
        $comNbv = $ledger?->nbv_sum ?? 0;
        $taxNbv = $asset?->value?->tax_nbv ?? 0;

        $template = storage_path('app/public/template/form_disposal_asset_tetap.xlsx');
        if (!file_exists($template)) {
            return abort(404, 'Template not found');
        }

        $spreadsheet = IOFactory::load($template);
        $sheet = $spreadsheet->getSheetByName('Form Disposal Aset Tetap')
            ?? $spreadsheet->getActiveSheet();

        $sheet->setCellValue('H7',  $companyName);
        $sheet->setCellValue('H8',  '');
        $sheet->setCellValue('H9',  $now->format('d-m-Y'));
        $sheet->setCellValue('H10', $deptLabel);
        $sheet->setCellValue('H11', $divLabel);

        $sheet->setCellValue('H14', '');

        $sheet->setCellValue('H15', $assetCode);
        $sheet->setCellValue('H16', $assetName);
        $sheet->setCellValue('H17', $acqDate ? $acqDate->format('d-m-Y') : '');

        $sheet->setCellValue('H18', $comCost);
        $sheet->setCellValue('H19', $comAcc);
        $sheet->setCellValue('H20', $comNbv);
        $sheet->setCellValue('H21', $taxNbv);

        $sheet->setCellValue('H22', '');
        $sheet->setCellValue('H23', '');

        $sheet->setCellValue('H24', '');

        $fileName = 'Form_Disposal_Preview_' . ($asset->asset_code ?? $assetUuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * GET /api/v1/stock-opname/preview/disposal-ba?asset_uuid=...
     */
    public function previewDisposalBa(Request $request)
    {
        abort_unless($this->canReadStockOpname($request), 403);

        $v = $request->validate([
            'asset_uuid' => ['required', 'uuid', 'exists:assets,uuid'],
        ]);

        $assetUuid = $v['asset_uuid'];

        $asset = Assets::with([
            'location',
            'assignment.owner',
        ])->findOrFail($assetUuid);

        $location = $asset?->location;
        $owner    = $asset?->assignment?->owner;

        $assetName = $asset?->asset_name ?? $asset?->description ?? '';
        $assetCode = $asset?->asset_code ?? '';

        $locationLbl = $location
            ? trim(($location->kode ?? '') . ' - ' . ($location->name ?? ''))
            : '';
        $ownerDept = $owner?->department ?? '';

        $date = now()->timezone('Asia/Jakarta');

        Carbon::setLocale('id');
        $hari  = $date->translatedFormat('l');
        $tglBT = $date->translatedFormat('d F Y');

        $template = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        if (!file_exists($template)) {
            return abort(404, 'Template not found');
        }

        $tpl = new TemplateProcessor($template);

        $tpl->setValues([
            'HARI'                        => $hari,
            'TANGGAL_BULAN_TAHUN'         => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER'  => $tglBT,
            'NAMA_ASET'                   => $assetName,
            'NOMOR_ASET'                  => $assetCode,
            'NOMOR_FORM'                  => 'PREVIEW',
            'LOKASI'                      => $locationLbl,
            'DEPARTEMEN_OWNER'            => $ownerDept,
            'KETERANGAN'                  => '',
        ]);

        $fileName = 'BA_Disposal_Preview_' . ($asset->asset_code ?? $assetUuid) . '.docx';

        return response()->streamDownload(function () use ($tpl) {
            $tpl->saveAs('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * GET /api/v1/stock-opname/preview/transfer-form?asset_uuid=...&target_type=...&target_value=...
     */
    public function previewTransferForm(Request $request)
    {
        abort_unless($this->canReadStockOpname($request), 403);

        $v = $request->validate([
            'asset_uuid'   => ['required', 'uuid', 'exists:assets,uuid'],
            'target_type'  => ['nullable', Rule::in(['owner', 'user', 'maintenance', 'location'])],
            'target_value' => ['nullable', 'string', 'max:100'],
        ]);

        $assetUuid   = $v['asset_uuid'];
        $targetType  = $v['target_type']  ?? null;
        $targetValue = $v['target_value'] ?? null;

        $asset = Assets::with([
            'assignment.owner.division',
            'assignment.user.division',
            'assignment.maintenance.division',
            'location',
            'value.uom',
        ])->findOrFail($assetUuid);

        $template = storage_path('app/public/template/form_transfer_asset.xlsx');
        if (!file_exists($template)) {
            return abort(404, 'Template not found');
        }

        $spreadsheet = IOFactory::load($template);
        $sheet       = $spreadsheet->getSheetByName('Form Transfer')
            ?? $spreadsheet->getActiveSheet();

        $assign   = $asset->assignment;
        $owner    = $assign?->owner;
        $ownerDiv = $owner?->division;

        $fromDeptLabel = $owner
            ? trim(($owner->kode ?? '') . ' - ' . ($owner->department ?? ''))
            : '';
        $fromDivLabel = $ownerDiv?->name ?? '';

        $loc          = $asset->location;
        $fromLocLabel = $loc
            ? trim(($loc->kode ?? '') . ' - ' . ($loc->name ?? ''))
            : '';

        $now = now()->timezone('Asia/Jakarta');

        // Transfer Code (V11) – kosong untuk preview
        $sheet->setCellValue('V11', '');

        // FROM section (G15-G18)
        $sheet->setCellValue('G15', $fromDeptLabel);
        $sheet->setCellValue('G16', $fromDivLabel);
        $sheet->setCellValue('G17', $fromLocLabel);
        $sheet->setCellValue('G18', $now->format('d-m-Y'));

        // TO section (S15-S18)
        $toDeptLabel      = '';
        $toDivLabel       = '';
        $toLocLabel       = '';
        $toSignatureLabel = '';

        if ($targetType && $targetValue) {
            if (in_array($targetType, ['owner', 'user', 'maintenance'], true)) {
                $targetUc = MasterUserCode::with('division')
                    ->where('kode', $targetValue)
                    ->first();

                if ($targetUc) {
                    $toDeptLabel      = trim(($targetUc->kode ?? '') . ' - ' . ($targetUc->department ?? ''));
                    $toDivLabel       = $targetUc->division?->name ?? '';
                    $toSignatureLabel = $toDeptLabel;
                }
                $toLocLabel = $fromLocLabel;
            } elseif ($targetType === 'location') {
                $targetLoc = MasterLocation::where('kode', $targetValue)->first();
                if ($targetLoc) {
                    $toLocLabel = trim(($targetLoc->kode ?? '') . ' - ' . ($targetLoc->name ?? ''));
                }
                $toDeptLabel      = $fromDeptLabel;
                $toDivLabel       = $fromDivLabel;
                $toSignatureLabel = $toDeptLabel;
            }
        }

        $sheet->setCellValue('S15', $toDeptLabel);
        $sheet->setCellValue('S16', $toDivLabel);
        $sheet->setCellValue('S17', $toLocLabel);
        $sheet->setCellValue('S18', $now->format('d-m-Y'));

        // Asset details (row 23)
        $sheet->setCellValue('C23', $asset->asset_code ?? '');
        $sheet->setCellValue('D23', $asset->description ?? '');

        $qty = $asset->value?->quantity;
        $sheet->setCellValue('L23', $qty !== null ? $qty : '');

        $uomText = $asset->value?->uom?->name
            ?? $asset->value?->kode_uom
            ?? '';
        $sheet->setCellValue('N23', $uomText);

        $sheet->setCellValue('P23', $asset->notes ?? '');

        // Signature sections
        $sheet->setCellValue('C36', $fromDeptLabel);
        $sheet->setCellValue('I36', $toDeptLabel);

        $fileName = 'Form_Transfer_Preview_' . ($asset->asset_code ?? $assetUuid) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function projectClose(Request $request, string $projectUuid)
    {
        abort_unless($this->canCreateStockOpname($request), 403);
        abort_if(!Str::isUuid($projectUuid), 422, 'Invalid project_uuid');

        // support root array OR {items:[...]}
        $payload = $request->all();
        $items = (is_array($payload) && array_key_exists('items', $payload))
            ? ($payload['items'] ?? [])
            : $payload;

        if (!is_array($items) || (is_array($items) && !array_is_list($items))) {
            $items = [$items];
        }

        $validator = Validator::make(
            ['items' => $items],
            [
                'items' => ['required', 'array', 'min:1', 'max:200'],
                'items.*.uuid'       => ['nullable', 'uuid'],  // offline request id
                'items.*.created_at' => ['nullable', 'date'],
                'items.*.note'       => ['nullable', 'string', 'max:1000'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'ok'    => false,
                'mode'  => 'batch',
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $items = $validator->validated()['items'];

        $results = [];

        DB::transaction(function () use ($items, $projectUuid, &$results) {
            // lock project row
            $project = DB::table('asset_projects')
                ->whereNull('deleted_at')
                ->where('uuid', $projectUuid)
                ->lockForUpdate()
                ->first();

            if (!$project) {
                // semua item gagal (tapi tetap bentuk batch)
                foreach ($items as $idx => $it) {
                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $it['uuid'] ?? null,
                        'error'        => 'Project not found',
                    ];
                }
                return;
            }

            // idempotent
            if ($project->status !== 'CLOSED') {
                DB::table('asset_projects')
                    ->where('uuid', $projectUuid)
                    ->update([
                        'status'     => 'CLOSED',
                        'updated_at' => now(),
                    ]);
            }

            foreach ($items as $idx => $it) {
                $results[] = [
                    'index'        => $idx,
                    'request_uuid' => $it['uuid'] ?? null,
                    'result'       => [
                        'ok'           => true,
                        'project_uuid' => $projectUuid,
                        'status'       => 'CLOSED',
                    ],
                ];
            }
        });

        return response()->json([
            'ok'    => true,
            'mode'  => 'batch',
            'items' => $results,
        ], 200);
    }
    public function projectReopen(Request $request, string $projectUuid)
    {
        abort_unless($this->canCreateStockOpname($request), 403);
        abort_if(!Str::isUuid($projectUuid), 422, 'Invalid project_uuid');

        $payload = $request->all();
        $items = (is_array($payload) && array_key_exists('items', $payload))
            ? ($payload['items'] ?? [])
            : $payload;

        if (!is_array($items) || (is_array($items) && !array_is_list($items))) {
            $items = [$items];
        }

        $validator = Validator::make(
            ['items' => $items],
            [
                'items' => ['required', 'array', 'min:1', 'max:200'],
                'items.*.uuid'       => ['nullable', 'uuid'],
                'items.*.created_at' => ['nullable', 'date'],
                'items.*.note'       => ['nullable', 'string', 'max:1000'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'ok'    => false,
                'mode'  => 'batch',
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $items = $validator->validated()['items'];

        $results = [];

        DB::transaction(function () use ($items, $projectUuid, &$results) {
            $project = DB::table('asset_projects')
                ->whereNull('deleted_at')
                ->where('uuid', $projectUuid)
                ->lockForUpdate()
                ->first();

            if (!$project) {
                foreach ($items as $idx => $it) {
                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $it['uuid'] ?? null,
                        'error'        => 'Project not found',
                    ];
                }
                return;
            }

            // idempotent
            if ($project->status !== 'OPEN') {
                DB::table('asset_projects')
                    ->where('uuid', $projectUuid)
                    ->update([
                        'status'     => 'OPEN',
                        'updated_at' => now(),
                    ]);
            }

            foreach ($items as $idx => $it) {
                $results[] = [
                    'index'        => $idx,
                    'request_uuid' => $it['uuid'] ?? null,
                    'result'       => [
                        'ok'           => true,
                        'project_uuid' => $projectUuid,
                        'status'       => 'OPEN',
                    ],
                ];
            }
        });

        return response()->json([
            'ok'    => true,
            'mode'  => 'batch',
            'items' => $results,
        ], 200);
    }
    public function stockOpnameDone(Request $request, string $projectUuid)
    {
        abort_unless($this->canCreateStockOpname($request), 403);
        abort_if(!Str::isUuid($projectUuid), 422, 'Invalid project_uuid');

        $project = DB::table('asset_projects')
            ->whereNull('deleted_at')
            ->where('uuid', $projectUuid)
            ->first();

        abort_if(!$project, 404, 'Project not found');
        abort_if($project->status === 'CLOSED', 422, 'Project already CLOSED');

        // support root array OR {items:[...]}
        $payload = $request->all();
        $items = (is_array($payload) && array_key_exists('items', $payload))
            ? ($payload['items'] ?? [])
            : $payload;

        // enforce array (even if client accidentally sends object)
        if (!is_array($items) || (is_array($items) && !array_is_list($items))) {
            // try to treat it as single object
            $items = [$items];
        }

        $validator = Validator::make(
            ['items' => $items],
            [
                'items' => ['required', 'array', 'min:1', 'max:500'],
                'items.*.uuid'       => ['nullable', 'uuid'], // offline request id
                'items.*.asset_uuid' => ['required', 'uuid'],
                'items.*.done'       => ['required', 'boolean'],
                'items.*.created_at' => ['nullable', 'date'],
                'items.*.note'       => ['nullable', 'string', 'max:1000'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'ok'    => false,
                'mode'  => 'batch',
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $items = $validator->validated()['items'];

        $by = optional($request->user())->name
            ?? optional($request->user())->email
            ?? null;

        $now = now();

        $results = [];

        DB::transaction(function () use ($items, $projectUuid, $by, $now, &$results) {
            foreach ($items as $idx => $it) {
                $reqUuid   = $it['uuid'] ?? null;
                $assetUuid = $it['asset_uuid'];
                $done      = (bool) $it['done'];

                try {
                    $exists = DB::table('asset_project_assets')
                        ->where('project_uuid', $projectUuid)
                        ->where('asset_uuid', $assetUuid)
                        ->exists();

                    if (!$exists) {
                        $results[] = [
                            'index'        => $idx,
                            'request_uuid' => $reqUuid,
                            'error'        => 'Asset not assigned to this project',
                        ];
                        continue;
                    }

                    DB::table('asset_project_assets')
                        ->where('project_uuid', $projectUuid)
                        ->where('asset_uuid', $assetUuid)
                        ->update([
                            'stock_opname_done' => $done,
                            'stock_opname_at'   => $done ? $now : null,
                            'stock_opname_by'   => $done ? $by  : null,
                            'updated_at'        => $now,
                        ]);

                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $reqUuid,
                        'result'       => [
                            'ok'           => true,
                            'project_uuid' => $projectUuid,
                            'asset_uuid'   => $assetUuid,
                            'done'         => $done,
                        ],
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'index'        => $idx,
                        'request_uuid' => $reqUuid,
                        'error'        => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'ok'    => true,
            'mode'  => 'batch',
            'items' => $results,
        ], 200);
    }
    public function projectShow(Request $request, string $projectUuid)
    {
        abort_unless($this->canReadStockOpname($request), 403);
        abort_if(!Str::isUuid($projectUuid), 422, 'Invalid project_uuid');

        $v = $request->validate([
            'q'        => ['nullable', 'string', 'max:200'],
            'done'     => ['nullable', Rule::in(['0', '1', 'all'])],
            'sort_by'  => ['nullable', Rule::in(['asset_code', 'assigned_at', 'done'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $project = DB::table('asset_projects')
            ->whereNull('deleted_at')
            ->where('uuid', $projectUuid)
            ->first();

        abort_if(!$project, 404, 'Project not found');

        $qSearch = trim((string)($v['q'] ?? ''));
        $done    = $v['done'] ?? 'all';

        $sortBy  = $v['sort_by'] ?? 'asset_code';
        $sortDir = $v['sort_dir'] ?? 'asc';
        $perPage = (int)($v['per_page'] ?? 15);
        $page    = (int)($v['page'] ?? 1);

        $q = DB::table('asset_project_assets as pa')
            ->join('assets as a', 'a.uuid', '=', 'pa.asset_uuid')
            ->leftJoin('master_asset_class as mac', 'mac.kode', '=', 'a.kode_asset_class')
            ->leftJoin('master_location as ml', 'ml.kode', '=', 'a.kode_location')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 'a.kode_status')
            ->leftJoin('assets_value as av', 'av.asset_uuid', '=', 'a.uuid')
            ->where('pa.project_uuid', $projectUuid)
            ->whereNull('a.deleted_at');

        if ($qSearch !== '') {
            $q->where(function ($qq) use ($qSearch) {
                $qq->where('a.asset_code', 'ilike', "%{$qSearch}%")
                    ->orWhere('a.description', 'ilike', "%{$qSearch}%")
                    ->orWhere('a.uuid', 'ilike', "%{$qSearch}%");
            });
        }

        if ($done !== 'all') {
            $q->whereRaw("COALESCE(pa.stock_opname_done,false) = " . ($done === '1' ? 'true' : 'false'));
        }

        $q->selectRaw("
        a.uuid as asset_uuid,
        a.asset_code,
        a.description,
        a.kode_asset_class,
        a.kode_location,
        a.kode_status,

        pa.created_at as assigned_at,
        pa.stock_opname_done,
        pa.stock_opname_at,
        pa.stock_opname_by,

        COALESCE(av.total, 0) as total,

        CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS kode_asset_class_label,
        CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS kode_location_label,
        CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS kode_status_label
    ");

        // sorting
        if ($sortBy === 'assigned_at') {
            $q->orderBy('pa.created_at', $sortDir);
        } elseif ($sortBy === 'done') {
            $q->orderByRaw("COALESCE(pa.stock_opname_done,false) " . $sortDir)
                ->orderBy('a.asset_code', 'asc');
        } else {
            $q->orderBy('a.asset_code', $sortDir);
        }

        $paginator = $q->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        // summary counts (buat header app)
        $assetCount = (int) DB::table('asset_project_assets')->where('project_uuid', $projectUuid)->count();
        $doneCount  = (int) DB::table('asset_project_assets')
            ->where('project_uuid', $projectUuid)
            ->whereRaw("COALESCE(stock_opname_done,false)=true")
            ->count();

        return response()->json([
            'data' => $paginator->items(),
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

                // project summary (biar 1 call cukup)
                'project' => [
                    'uuid'        => $project->uuid,
                    'name'        => $project->name,
                    'status'      => $project->status,
                    'asset_count' => $assetCount,
                    'done_count'  => $doneCount,
                    'open_count'  => max(0, $assetCount - $doneCount),
                    'created_at'  => $project->created_at ?? null,
                    'updated_at'  => $project->updated_at ?? null,
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
    public function projects(Request $request)
    {
        abort_unless($this->canReadStockOpname($request), 403);

        $v = $request->validate([
            'q'        => ['nullable', 'string', 'max:200'],
            'status'   => ['nullable', Rule::in(['OPEN', 'CLOSED'])], // default OPEN
            'sort_by'  => ['nullable', Rule::in(['created_at', 'name', 'status', 'updated_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $q       = trim((string)($v['q'] ?? ''));
        $status  = $v['status'] ?? 'OPEN';
        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $perPage = (int)($v['per_page'] ?? 15);
        $page    = (int)($v['page'] ?? 1);

        // base query (aggregates)
        $base = DB::table('asset_projects as p')
            ->leftJoin('asset_project_assets as pa', 'pa.project_uuid', '=', 'p.uuid')
            ->whereNull('p.deleted_at')
            ->where('p.status', $status);

        if ($q !== '') {
            $base->where(function ($qq) use ($q) {
                $qq->where('p.name', 'ilike', "%{$q}%")
                    ->orWhere('p.uuid', 'ilike', "%{$q}%");
            });
        }

        $base->groupBy('p.uuid', 'p.name', 'p.status', 'p.created_at', 'p.updated_at');

        $base->selectRaw("
        p.uuid,
        p.name,
        p.status,
        p.created_at,
        p.updated_at,
        COUNT(pa.asset_uuid) as asset_count,
        COALESCE(SUM(CASE WHEN COALESCE(pa.stock_opname_done,false)=true THEN 1 ELSE 0 END),0) as done_count
    ");

        $paginator = $base
            ->orderBy("p.$sortBy", $sortDir)
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        // map open_count
        $rows = collect($paginator->items())->map(function ($r) {
            $assetCount = (int)($r->asset_count ?? 0);
            $doneCount  = (int)($r->done_count ?? 0);
            return [
                'uuid'        => $r->uuid,
                'name'        => $r->name,
                'status'      => $r->status,
                'asset_count' => $assetCount,
                'done_count'  => $doneCount,
                'open_count'  => max(0, $assetCount - $doneCount),
                'created_at'  => $r->created_at,
                'updated_at'  => $r->updated_at,
            ];
        })->values();

        return response()->json([
            'data' => $rows,
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
            ],
            'links' => [
                'first' => $paginator->url(1),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
                'last'  => $paginator->url($paginator->lastPage()),
            ],
        ]);
    }
}
