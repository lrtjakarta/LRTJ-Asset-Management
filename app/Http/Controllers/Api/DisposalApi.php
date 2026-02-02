<?php

namespace App\Http\Controllers\Api;

use App\Models\Assets;
use App\Models\Disposal;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

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
            'project_uuid' => ['nullable', 'uuid'],
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
                DB::raw('ap.uuid   as project_uuid'),
                DB::raw('ap.name   as project_name'),
                DB::raw('ap.status as project_status'),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_disposals.asset_uuid')
            ->leftJoin('asset_projects as ap', 'ap.uuid', '=', 'assets_disposals.project_uuid')

            ->with([
                'status:uuid,kode,name',
                'target:uuid,kode,name',
                'asset.value',
                'asset.location',
                'asset.status',
                'asset.assignment.owner',
                'asset.assignment.user',
                'asset.assignment.maintenance',
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

        $row = Disposal::query()
            ->select([
                'assets_disposals.*',
                DB::raw('a.asset_code as asset_code'),
                DB::raw('a.description as asset_description'),
                DB::raw('ap.uuid   as project_uuid'),
                DB::raw('ap.name   as project_name'),
                DB::raw('ap.status as project_status'),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_disposals.asset_uuid')
            ->leftJoin('asset_projects as ap', 'ap.uuid', '=', 'assets_disposals.project_uuid')
            ->with([
                'status:uuid,kode,name',
                'target:uuid,kode,name',
                'asset.value',
                'asset.location',
                'asset.status',
                'asset.assignment.owner',
                'asset.assignment.user',
                'asset.assignment.maintenance',
            ])
            ->where('assets_disposals.uuid', $uuid)
            ->firstOrFail();

        $data = $this->mapRows(collect([$row]), $tz)->first();

        return response()->json(['data' => $data]);
    }
    public function store(Request $request)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'C'), 403);

        $user = $request->user();
        $uid  = $user?->name;
        abort_if(!$uid, 401, 'No session UID.');

        // Offline batch? items[] = [...]
        $isBatch = is_array($request->input('items'));

        if ($isBatch) {
            $root = $request->validate([
                'items'                         => ['required', 'array', 'min:1'],
                'items.*.uuid'                  => ['nullable', 'uuid'],
                'items.*.asset_uuid'            => ['required', 'uuid', 'exists:assets,uuid'],
                'items.*.note'                  => ['nullable', 'string', 'max:1000'],
                'items.*.target_status'         => ['nullable', 'string'],
                'items.*.reason'                => [
                    'required',
                    Rule::in(['Sale', 'Waste', 'Donate', 'Held']),
                ],
                'items.*.created_at'            => ['nullable', 'date'],

                // offline attachment (base64)
                'items.*.file_b64'              => ['nullable', 'array'],
                'items.*.file_b64.name'         => ['required_with:items.*.file_b64', 'string', 'max:255'],
                'items.*.file_b64.mime'         => ['required_with:items.*.file_b64', 'string', 'max:100'],
                'items.*.file_b64.data'         => ['required_with:items.*.file_b64', 'string'],
            ]);
        } else {
            // Single payload (web or direct API)
            $root = $request->validate([
                'uuid'              => ['nullable', 'uuid'],
                'asset_uuid'        => ['required', 'uuid', 'exists:assets,uuid'],
                'note'              => ['nullable', 'string', 'max:1000'],
                'target_status'     => ['nullable', 'string'],
                'reason'            => [
                    'required',
                    Rule::in(['Sale', 'Waste', 'Donate', 'Held']),
                ],
                'created_at'        => ['nullable', 'date'],

                // standard file upload (web / multipart)
                'file'              => [
                    'nullable',
                    'file',
                    'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt',
                    'max:20480',
                ],

                // offline attachment (base64) – for mobile
                'file_b64'          => ['nullable', 'array'],
                'file_b64.name'     => ['required_with:file_b64', 'string', 'max:255'],
                'file_b64.mime'     => ['required_with:file_b64', 'string', 'max:100'],
                'file_b64.data'     => ['required_with:file_b64', 'string'],
            ]);
        }

        $items   = $isBatch ? $root['items'] : [$root];
        $results = [];

        foreach ($items as $idx => $payload) {
            try {
                $res = $this->createDisposalFromPayload($request, $payload, $uid);
            } catch (\Throwable $e) {
                $res = [
                    'ok'    => false,
                    'index' => $idx,
                    'error' => $e->getMessage(),
                ];
            }

            if ($isBatch) {
                $results[] = $res;
            } else {
                // Single mode: return HTTP 201 / 200 / 422
                return response()->json(
                    $res,
                    ($res['ok'] ?? false)
                        ? (($res['duplicate'] ?? false) ? 200 : 201)
                        : 422
                );
            }
        }

        // Batch mode: wrap all results
        return response()->json([
            'ok'      => collect($results)->every(fn($r) => data_get($r, 'ok') === true),
            'batch'   => true,
            'results' => $results,
        ]);
    }
    private function createDisposalFromPayload(Request $rootReq, array $data, string $uid): array
    {
        // Idempotent by UUID (offline replay)
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

        $asset = Assets::select('uuid', 'asset_code', 'kode_status')
            ->findOrFail($data['asset_uuid']);

        $target = $data['target_status'] ?? 'DIS';

        $ok = MasterStatus::where('kode', $target)
            ->where(function ($q) {
                $q->where('type', 'Asset')->orWhereNull('type');
            })
            ->exists();

        abort_unless($ok, 422, 'Target disposal status not valid.');

        $now    = Carbon::now();
        $prefix = 'DSP' . $now->format('ym');

        $last = Disposal::where('disposal_code', 'like', $prefix . '%')
            ->orderBy('disposal_code', 'desc')
            ->first();

        if ($last) {
            $lastSeq = (int) substr($last->disposal_code, -4);
            $seq     = $lastSeq + 1;
        } else {
            $seq = 1;
        }

        $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $uuid = $data['uuid'] ?? (string) Str::uuid();

        $path = $orig = $mime = $size = null;

        // 1) Offline base64 file from mobile (takes priority)
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
        }
        // 2) Normal uploaded file (web / multipart)
        elseif ($rootReq->file('file')) {
            [$path, $orig, $mime, $size] = $this->saveUpload(
                $rootReq->file('file'),
                $asset,
                $code,
                null
            );
        }

        $payload = [
            'uuid'            => $uuid,
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
            'reason'          => $data['reason'], // required in validation
            'flow'            => $this->buildFlowTemplate($uid),
        ];

        if (!empty($data['created_at'])) {
            $payload['created_at'] = $data['created_at'];
        }

        $row = Disposal::create($payload);

        return [
            'ok'   => true,
            'id'   => $row->uuid,
            'code' => $row->disposal_code,
        ];
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
        // Preload depreciation ledger sums per asset (like AssetsApi)
        $assetUuids = $rows->pluck('asset_uuid')->filter()->unique()->values();

        $ledgerMap = [];
        if ($assetUuids->isNotEmpty()) {
            $ledgerRows = DB::table('assets_depr_ledger_monthly')
                ->selectRaw('asset_uuid, COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
                ->whereIn('asset_uuid', $assetUuids)
                ->groupBy('asset_uuid')
                ->get();

            $ledgerMap = $ledgerRows->keyBy('asset_uuid')->map(function ($r) {
                return [
                    'acc_sum' => (float) $r->acc_sum,
                    'nbv_sum' => (float) $r->nbv_sum,
                ];
            })->all();
        }

        return $rows->map(function (Disposal $t) use ($tz, $ledgerMap) {
            $workflowLabel = $t->status
                ? ($t->kode_status . ' - ' . $t->status->name)
                : $t->kode_status;

            $targetLabel   = $t->target
                ? ($t->target_status . ' - ' . $t->target->name)
                : $t->target_status;

            // --- Asset & relations ---
            $asset     = $t->asset;
            $assign    = $asset?->assignment;
            $ownerUc   = $assign?->owner;
            $userUc    = $assign?->user;
            $maintUc   = $assign?->maintenance;
            $loc       = $asset?->location;
            $statusRel = $asset?->status;
            $value     = $asset?->value;

            // Location
            $locCode = $loc?->kode ?? $asset?->kode_location;
            $locLabel = $locCode
                ? trim($locCode . ' - ' . ($loc->name ?? ''))
                : null;

            // Status
            $statusCode = $statusRel?->kode ?? $asset?->kode_status;
            $statusLabel = $statusCode
                ? trim($statusCode . ' - ' . ($statusRel->name ?? ''))
                : null;

            // Owner / User / Maintenance (from assignment + MasterUserCode)
            $ownerCode = $assign?->asset_owner ?? $ownerUc?->kode;
            $ownerLabel = $ownerCode
                ? trim($ownerCode . ' - ' . ($ownerUc?->department ?? ''))
                : null;

            $userCode = $assign?->asset_user ?? $userUc?->kode;
            $userLabel = $userCode
                ? trim($userCode . ' - ' . ($userUc?->department ?? ''))
                : null;

            $maintCode = $assign?->asset_maintenance ?? $maintUc?->kode;
            $maintLabel = $maintCode
                ? trim($maintCode . ' - ' . ($maintUc?->department ?? ''))
                : null;

            // Acquisition date like AssetsApi
            $acqDateFmt = null;
            if ($value?->actual_date) {
                if ($value->actual_date instanceof Carbon) {
                    $acqDateFmt = $value->actual_date->format('d F Y');
                } else {
                    try {
                        $acqDateFmt = Carbon::parse($value->actual_date)->format('d F Y');
                    } catch (\Throwable $e) {
                        $acqDateFmt = (string) $value->actual_date;
                    }
                }
            }

            // Ledger sums (Commercial Accumulated Depreciation + NBV)
            $ledger = $ledgerMap[$t->asset_uuid] ?? null;
            $accSum = $ledger['acc_sum'] ?? 0;
            $nbvSum = $ledger['nbv_sum'] ?? 0;

            // Build asset block (same spirit as AssetsApi)
            $assetArr = [
                'asset_uuid'        => $t->asset_uuid,
                'asset_code'        => $t->getAttribute('asset_code') ?? $asset?->asset_code,
                'asset_description' => $t->getAttribute('asset_description') ?? $asset?->description,
                'asset_label'       => $asset?->asset_code
                    ? ($asset->asset_code . ' — ' . ($asset->description ?? ''))
                    : $t->asset_uuid,

                // location & status
                'asset_kode_location'       => $locCode,
                'asset_kode_location_label' => $locLabel,
                'asset_kode_status'         => $statusCode,
                'asset_kode_status_label'   => $statusLabel,

                // owner / user / maintenance
                'asset_owner'             => $ownerCode,
                'asset_owner_label'       => $ownerLabel,
                'asset_user'              => $userCode,
                'asset_user_label'        => $userLabel,
                'asset_maintenance'       => $maintCode,
                'asset_maintenance_label' => $maintLabel,

                // value fields (from AssetsApi)
                'price'             => $value?->price,
                'quantity'          => $value?->quantity,
                'vat_in'            => $value?->vat_in,
                'total'             => $value?->total,
                'kode_uom'          => $value?->kode_uom,
                'useful_life_month' => $value?->useful_life_month,

                // NEW: depreciation / NBV (same meaning as AssetsApi)
                'acquisition_date'       => $acqDateFmt,        // Acquisition Date
                'commercial_acq_cost'    => intval($value?->total),     // Commercial Acquisition Cost (IDR)
                'commercial_accum_depr'  => intval($accSum),            // Commercial Accumulated Depreciation (IDR)
                'commercial_nbv'         => intval($nbvSum),            // Commercial Net Book Value (IDR)
            ];
            $projectUuid   = $t->getAttribute('project_uuid');
            $projectName   = $t->getAttribute('project_name');
            $projectStatus = $t->getAttribute('project_status');
            $project = null;
            if ($projectUuid) {
                $project = [
                    'uuid'   => $projectUuid,
                    'name'   => $projectName,
                    'status' => $projectStatus, // OPEN / CLOSED
                ];
            }
            // main attachment (original file)
            $fileObj = null;
            if ($t->file_path) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($t->file_path);
                $size   = $t->file_size ?? ($exists ? $fs->size($t->file_path) : null);
                $mtime  = $t->file_updated_at
                    ? strtotime($t->file_updated_at)
                    : ($exists ? $fs->lastModified($t->file_path) : null);

                $fileObj = [
                    'name'          => $t->file_name ?: basename($t->file_path),
                    'mime'          => $t->file_mime,
                    'size'          => $size,
                    'sha256'        => $t->file_sha256 ?? null,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
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

                $downloadUrl = route('files.download.kind', [
                    'kind' => 'disposal',
                    'uuid' => $t->uuid,
                    'slot' => 'form',
                ]);

                $formFileObj = [
                    'name'          => $t->flow_file_name ?: basename($t->flow_file_path),
                    'mime'          => $t->flow_file_mime,
                    'size'          => $size,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'download_url'  => $downloadUrl,
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

                $downloadUrl = route('files.download.kind', [
                    'kind' => 'disposal',
                    'uuid' => $t->uuid,
                    'slot' => 'ba',
                ]);

                $baFileObj = [
                    'name'          => $t->ba_file_name ?: basename($t->ba_file_path),
                    'mime'          => $t->ba_file_mime,
                    'size'          => $size,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'download_url'  => $downloadUrl,
                    'file_url'      => $exists ? url('storage/' . ltrim($t->ba_file_path, '/')) : null,
                ];
            }

            // FLOW
            $flow         = $this->normalizeFlow($t);
            $pendingIndex = $this->getNextPendingFlowIndex($flow);

            return [
                'uuid'              => $t->uuid,
                'disposal_code'     => $t->disposal_code,

                // FULL asset block (with location/status/owner/user/maintenance + value fields)
                'asset'             => $assetArr,

                'kode_status'       => $t->kode_status,
                'workflow_label'    => $workflowLabel,
                'reason'            => $t->reason,

                'target_status'     => $t->target_status,
                'target_label'      => $targetLabel,

                'before_status'     => $t->before_status,
                'pic_request_uid'   => $t->pic_request_uid,
                'pic_approve_uid'   => $t->pic_approve_uid,

                'note'              => $t->note,
                'file'              => $fileObj,

                'project'        => $project,
                'project_uuid'   => $projectUuid,
                'project_name'   => $projectName,
                'project_status' => $projectStatus,
                
                'form_file'         => $formFileObj,
                'ba_file'           => $baFileObj,
                'form_file_url'     => $t->flow_file_url ?? null,
                'ba_file_url'       => $t->ba_file_url ?? null,
                'form_download_url' => route('disposals.form.download', ['uuid' => $t->uuid]),
                'ba_download_url'   => route('disposals.ba.download',   ['uuid' => $t->uuid]),

                'flow'               => $flow,
                'flow_pending_index' => $pendingIndex,

                'created_at'        => optional($t->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'        => optional($t->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'        => optional($t->deleted_at)->timezone($tz)?->toIso8601String(),
            ];
        });
    }

    protected function buildFlowTemplate(?string $creatorName = null): array
    {
        $now = now()->toDateTimeString();

        return [
            'key'   => 'disposal_request',
            'steps' => [
                [
                    'code'        => 'create',
                    'label'       => 'Create Disposal Request',
                    'role'        => 'User Departemen',
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
                    'code'        => 'am_head',
                    'label'       => 'Approval Asset Management Head',
                    'role'        => 'Asset Management Head',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'akp_head',
                    'label'       => 'Approval Accounting',
                    'role'        => 'Dept.Head AKP',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'asset_mgt',
                    'label'       => 'Pelaksanaan & BA Disposal (Asset Management)',
                    'role'        => 'Asset Management',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
            ],
        ];
    }


    private function saveUpload(?UploadedFile $file, Assets $asset, string $code, ?Disposal $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'disposals/' . $asset->uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        if ($existing && $existing->file_path) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [
            $path,
            $file->getClientOriginalName(),
            $file->getClientMimeType(),
            $file->getSize(),
        ];
    }

    private function saveBaBase64(array $file, Disposal $d, string $code): array
    {
        $mime = $file['mime'] ?? '';
        $data = $file['data'] ?? '';
        $name = $file['name'] ?? 'ba';

        $this->assertAllowedMime($mime);

        $approx = $this->approxBase64Size($data);
        if ($approx > 20 * 1024 * 1024) {
            throw new \RuntimeException('BA base64 file too large (max 20MB).');
        }

        if (str_starts_with($data, 'data:')) {
            $data = substr($data, strpos($data, ',') + 1);
        }

        $binary = base64_decode($data, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 BA file data.');
        }

        $disk = 'public';
        $dir  = 'disposals_ba/' . $d->asset_uuid;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: match (strtolower($mime)) {
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

        $filename  = $code . '-ba-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $storedPath = $dir . '/' . $filename;

        Storage::disk($disk)->put($storedPath, $binary);

        if ($d->ba_file_path) {
            Storage::disk($disk)->delete($d->ba_file_path);
        }

        return [$storedPath, $name, $mime, strlen($binary)];
    }

    private function saveFormBase64(array $file, Disposal $d, string $code): array
    {
        $mime = $file['mime'] ?? '';
        $data = $file['data'] ?? '';
        $name = $file['name'] ?? 'form';

        $this->assertAllowedMime($mime);

        $approx = $this->approxBase64Size($data);
        if ($approx > 20 * 1024 * 1024) {
            throw new \RuntimeException('Form base64 file too large (max 20MB).');
        }

        if (str_starts_with($data, 'data:')) {
            $data = substr($data, strpos($data, ',') + 1);
        }

        $binary = base64_decode($data, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 Form file data.');
        }

        $disk = 'public';
        $dir  = 'disposals_form/' . $d->asset_uuid;

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION) ?: match (strtolower($mime)) {
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

        $filename  = $code . '-form-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;
        $storedPath = $dir . '/' . $filename;

        Storage::disk($disk)->put($storedPath, $binary);

        if ($d->flow_file_path) {
            Storage::disk($disk)->delete($d->flow_file_path);
        }

        return [$storedPath, $name, $mime, strlen($binary)];
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
    public function downloadBa(Request $request): StreamedResponse
    {
        // inline the permission check for API
        abort_unless($request->user()?->hasAction('DISPOSAL', 'R'), 403);

        $v = $request->validate([
            'uuid' => ['required', 'uuid', 'exists:assets_disposals,uuid'],
        ]);

        $disposal = Disposal::with([
            'asset.location',
            'asset.assignment.owner',
        ])->where('uuid', $v['uuid'])->firstOrFail();

        $asset    = $disposal->asset;
        $location = $asset?->location;
        $owner    = $asset?->assignment?->owner;

        $assetName   = $asset?->asset_name ?? $asset?->description ?? '';
        $assetCode   = $asset?->asset_code ?? '';
        $formNumber  = $disposal->disposal_code ?? '';
        $locationLbl = $location
            ? trim(($location->kode ?? '') . ' ' . ($location->name ?? ''))
            : '';

        $keterangan  = $disposal->note ?? '';

        // tanggal BA: pakai updated_at jika sudah ACC, kalau tidak pakai now()
        $date = $disposal->updated_at
            ? $disposal->updated_at->copy()->timezone('Asia/Jakarta')
            : now('Asia/Jakarta');

        Carbon::setLocale('id'); // so translatedFormat uses Indonesian

        $hari   = $date->translatedFormat('l');        // Senin, Selasa, ...
        $tglBT  = $date->translatedFormat('d F Y');    // 17 November 2025

        $templatePath = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        $template     = new TemplateProcessor($templatePath);

        // these KEYS must match the placeholders inside the DOCX (without ${})
        $template->setValues([
            'HARI'                        => $hari,
            'TANGGAL_BULAN_TAHUN'         => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER'  => $tglBT,
            'NAMA_ASET'                   => $assetName,
            'NOMOR_ASET'                  => $assetCode,
            'NOMOR_FORM'                  => $formNumber,
            'LOKASI'                      => $locationLbl,
            'KETERANGAN'                  => $keterangan,
        ]);

        $fileName = 'BA Disposal - ' . ($disposal->disposal_code ?? 'DSP') . '.docx';

        return response()->streamDownload(function () use ($template) {
            $template->saveAs('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
    /**
     * GET /api/v1/disposals/form?uuid=...
     * Return Disposal Form XLSX as download.
     */
    public function downloadForm(Request $request): StreamedResponse
    {
        // inline the permission check for API
        abort_unless($request->user()?->hasAction('DISPOSAL', 'R'), 403);

        $v = $request->validate([
            'uuid' => ['required', 'uuid', 'exists:assets_disposals,uuid'],
        ]);

        $disposal = Disposal::with([
            'asset.value',
            'asset.location',
            'asset.assignment.owner.division',
        ])->where('uuid', $v['uuid'])->firstOrFail();

        $asset    = $disposal->asset;
        $reason   = $disposal->reason ?? '';
        $assign   = $asset?->assignment;
        $owner    = $assign?->owner;
        $ownerDiv = $owner?->division;

        $createdAt   = $disposal->created_at?->timezone('Asia/Jakarta');
        $companyName = config('app.company_name', 'PT LRT Jakarta');

        $deptLabel = $owner?->department ?: '';
        $divLabel  = $ownerDiv?->name ?? '';

        $assetCode = $asset?->asset_code ?? '';
        $assetName = $asset?->asset_name ?? $asset?->description ?? '';

        $acqDate = null;
        if ($asset?->value?->actual_date) {
            $acqDate = $asset->value->actual_date instanceof Carbon
                ? $asset->value->actual_date
                : Carbon::parse($asset->value->actual_date);
        }

        $comCost = $asset?->value?->total ?? 0;

        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $asset?->uuid)
            ->first();

        $comAcc = $ledger?->acc_sum ?? 0; // Commercial Accumulated Depreciation (IDR)
        $comNbv = $ledger?->nbv_sum ?? 0; // Commercial Net Book Value (IDR)

        $taxNbv = $asset?->value?->tax_nbv ?? 0;

        $justification = $disposal->note ?? '';

        // ========= build "done" flow steps text =========
        $flowRaw   = $disposal->flow ?? null;
        $flowArray = null;

        if (is_string($flowRaw) && $flowRaw !== '') {
            $decoded = json_decode($flowRaw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flowArray = $decoded;
            }
        } elseif (is_array($flowRaw)) {
            $flowArray = $flowRaw;
        }

        $doneLines = [];
        if (!empty($flowArray['steps']) && is_array($flowArray['steps'])) {
            foreach ($flowArray['steps'] as $st) {
                if (!empty($st['approved_at'])) {
                    $label = $st['label'] ?? ($st['code'] ?? '');
                    $role  = $st['role'] ?? '';
                    $by    = $st['approved_by'] ?? '';
                    $atRaw = $st['approved_at'];

                    try {
                        $at = Carbon::parse($atRaw, 'Asia/Jakarta')
                            ->timezone('Asia/Jakarta')
                            ->format('d-m-Y H:i');
                    } catch (\Throwable $e) {
                        $at = $atRaw;
                    }

                    $line = trim(
                        ($label ?: '') .
                            ($role ? ' (' . $role . ')' : '') .
                            ($at ? ' - ' . $at : '') .
                            ($by ? ' - ' . $by : '')
                    );

                    if ($line !== '') {
                        $doneLines[] = $line;
                    }
                }
            }
        }

        $flowText = implode("\n", $doneLines);
        // ========= END flow text =========

        $templatePath = storage_path('app/public/template/form_disposal_asset_tetap.xlsx');
        $spreadsheet  = IOFactory::load($templatePath);
        $sheet        = $spreadsheet->getSheetByName('Form Disposal Aset Tetap')
            ?: $spreadsheet->getActiveSheet();

        $sheet->setCellValue('H7',  $companyName);
        $sheet->setCellValue('H8',  $disposal->disposal_code ?? '');
        $sheet->setCellValue('H9',  $createdAt ? $createdAt->format('d-m-Y') : '');
        $sheet->setCellValue('H10', $deptLabel);
        $sheet->setCellValue('H11', $divLabel);

        $sheet->setCellValue('H14', $reason);

        $sheet->setCellValue('H15', $assetCode);
        $sheet->setCellValue('H16', $assetName);
        $sheet->setCellValue('H17', $acqDate ? $acqDate->format('d-m-Y') : '');

        $sheet->setCellValue('H18', $comCost); // Commercial Acquisition Cost (IDR)
        $sheet->setCellValue('H19', $comAcc);  // Commercial Accumulated Depreciation (IDR)
        $sheet->setCellValue('H20', $comNbv);  // Commercial Net Book Value (IDR)
        $sheet->setCellValue('H21', $taxNbv);  // Tax Net Book Value (IDR)

        $sheet->setCellValue('H22', '');
        $sheet->setCellValue('H23', '');

        $sheet->setCellValue('H24', $justification);

        $sheet->setCellValue('C26', $flowText);
        $sheet->getStyle('C26')->getAlignment()->setWrapText(true);

        $fileName = 'Form Disposal Aset Tetap - ' . ($disposal->disposal_code ?? 'DSP') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function approve(Request $request)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'APR'), 403);

        $user = $request->user();
        $uid  = $user?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $v = $request->validate([
            'items'                           => ['required', 'array', 'min:1'],
            'items.*.uuid'                    => ['required', 'uuid', 'exists:assets_disposals,uuid'],

            // For FINAL step (asset_mgt) – BA file (required there)
            'items.*.ba_file_b64'             => ['nullable', 'array'],
            'items.*.ba_file_b64.name'        => ['required_with:items.*.ba_file_b64', 'string', 'max:255'],
            'items.*.ba_file_b64.mime'        => ['required_with:items.*.ba_file_b64', 'string', 'max:100'],
            'items.*.ba_file_b64.data'        => ['required_with:items.*.ba_file_b64', 'string'],

            // Optional FLOW file (Form Disposal) at final step
            'items.*.flow_file_b64'           => ['nullable', 'array'],
            'items.*.flow_file_b64.name'      => ['required_with:items.*.flow_file_b64', 'string', 'max:255'],
            'items.*.flow_file_b64.mime'      => ['required_with:items.*.flow_file_b64', 'string', 'max:100'],
            'items.*.flow_file_b64.data'      => ['required_with:items.*.flow_file_b64', 'string'],
        ]);

        $items   = $v['items'];
        $results = [];

        foreach ($items as $index => $item) {
            try {
                $res = DB::transaction(function () use ($item, $uid) {
                    return $this->approveSingleDisposalForApi($item, $uid);
                });
            } catch (\Throwable $e) {
                $res = [
                    'ok'    => false,
                    'index' => $index,
                    'uuid'  => $item['uuid'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
            $results[] = $res;
        }

        return response()->json([
            'ok'      => collect($results)->every(fn($r) => data_get($r, 'ok') === true),
            'batch'   => true,
            'results' => $results,
        ]);
    }
    private function approveSingleDisposalForApi(array $item, string $uid): array
    {
        $d = Disposal::where('uuid', $item['uuid'])->lockForUpdate()->firstOrFail();
        abort_if($d->kode_status !== 'APR', 422, 'Only pending disposals can be approved.');

        // Normalize / build flow (same idea as controller)
        $flow = $d->flow ?? null;
        if (is_string($flow) && $flow !== '') {
            $decoded = json_decode($flow, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flow = $decoded;
            } else {
                $flow = null;
            }
        }
        if (!$flow) {
            $flow = $this->buildFlowTemplate($d->pic_request_uid ?: $uid);
        }

        $idx = $this->getNextPendingFlowIndex($flow);
        abort_if($idx === null, 422, 'All steps already approved.');

        $step     = &$flow['steps'][$idx];
        $stepCode = $step['code'] ?? null;
        $now      = now()->toDateTimeString();
        $isLast   = ($idx === count($flow['steps']) - 1);

        // === role / department checks (same semantics as controller) ===
        $currentUser = auth()->user();
        $userRoles   = $currentUser?->roles()->pluck('kode')->toArray() ?? [];
        $isSysAdmin  = in_array('SYSADMIN', $userRoles);
        $userDept    = $currentUser->kode_department ?? null;

        // STEP 2: Dept Head
        if ($stepCode === 'dept_head') {
            if (!in_array('DEPT_HEAD', $userRoles) && !$isSysAdmin) {
                abort(403, 'Only Dept Head can approve this step.');
            }

            if (!$userDept && !$isSysAdmin) {
                abort(422, 'Your department is not set. Please contact administrator.');
            }

            $asset = Assets::with(['assignment.owner'])
                ->where('uuid', $d->asset_uuid)
                ->firstOrFail();

            $ownerCode = $asset->assignment?->asset_owner;
            if ($ownerCode) {
                $ownerUc = MasterUserCode::where('kode', $ownerCode)->first();
                if ($ownerUc && $ownerUc->kode !== $userDept && !$isSysAdmin) {
                    abort(422, 'This user department not matching. Expected: ' . $ownerUc->kode);
                }
            }
        }

        // STEP 3: AM_HEAD
        if ($stepCode === 'am_head') {
            if (!in_array('AM_HEAD', $userRoles) && !$isSysAdmin) {
                abort(403, 'Only AM_HEAD can approve this step.');
            }
        }

        // STEP 4: AKP Head
        if ($stepCode === 'akp_head') {
            if (!in_array('DEPT_HEAD', $userRoles) && !$isSysAdmin) {
                abort(403, 'Only Dept Head AKP can approve this step.');
            }

            if (!$userDept && !$isSysAdmin) {
                abort(422, 'Your department is not set. Please contact administrator.');
            }

            if ($userDept !== 'AKP' && !$isSysAdmin) {
                abort(422, 'This step can only be approved by Dept Head with code AKP.');
            }
        }

        // STEP 5: Asset Management execution (final)
        if ($stepCode === 'asset_mgt' && in_array('AM_ADMIN', $userRoles) && !$isSysAdmin) {
            // if (!$userDept) {
            //     abort(422, 'Your department is not set. Please contact administrator.');
            // }

            if (!isset($asset)) {
                $asset = Assets::with(['assignment.owner'])
                    ->where('uuid', $d->asset_uuid)
                    ->firstOrFail();
            }

            $ownerCode = $asset->assignment?->asset_owner;
            if ($ownerCode) {
                $ownerUc = MasterUserCode::where('kode', $ownerCode)->first();
                // if ($ownerUc && $ownerUc->kode !== $userDept && !$isSysAdmin) {
                //     abort(422, 'This user department not matching. Expected: ' . $ownerUc->kode);
                // }
            }
        }

        // === Final step processing: BA + Form + change asset status ===
        if ($isLast && $stepCode === 'asset_mgt') {
            $baB64   = $item['ba_file_b64']   ?? null;
            $formB64 = $item['flow_file_b64'] ?? null;

            if (!$baB64) {
                abort(422, 'BA file (ba_file_b64) is required to complete final step.');
            }

            [$baPath, $baOrig, $baMime, $baSize] = $this->saveBaBase64(
                $baB64,
                $d,
                $d->disposal_code
            );

            $d->ba_file_path = $baPath;
            $d->ba_file_name = $baOrig;
            $d->ba_file_mime = $baMime;
            $d->ba_file_size = $baSize;

            if ($formB64) {
                [$formPath, $formOrig, $formMime, $formSize] = $this->saveFormBase64(
                    $formB64,
                    $d,
                    $d->disposal_code
                );

                $d->flow_file_path = $formPath;
                $d->flow_file_name = $formOrig;
                $d->flow_file_mime = $formMime;
                $d->flow_file_size = $formSize;
            }

            // Update asset status to target_status
            Assets::where('uuid', $d->asset_uuid)->update([
                'kode_status' => $d->target_status,
            ]);

            $d->kode_status     = 'ACC';
            $d->pic_approve_uid = $uid;
        }

        // Mark this step approved
        $step['approved_by'] = $uid;
        $step['approved_at'] = $now;

        $d->flow = $flow;
        $d->save();

        return [
            'ok'          => true,
            'id'          => $d->uuid,
            'kode_status' => $d->kode_status,
            'flow'        => $flow,
            'ba_file_url' => $d->ba_file_url ?? null,
        ];
    }
    public function reject(Request $request)
    {
        abort_unless($request->user()?->hasAction('DISPOSAL', 'APR'), 403);

        $user = $request->user();
        $uid  = $user?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $v = $request->validate([
            'items'              => ['required', 'array', 'min:1'],
            'items.*.uuid'       => ['required', 'uuid', 'exists:assets_disposals,uuid'],
        ]);

        $results = [];

        foreach ($v['items'] as $index => $item) {
            try {
                $res = DB::transaction(function () use ($item, $uid) {
                    $d = Disposal::where('uuid', $item['uuid'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    abort_if(
                        $d->kode_status !== 'APR',
                        422,
                        'Only pending disposals can be rejected.'
                    );

                    // normalize/build flow (same pattern as controller / approveStep)
                    $flow = $d->flow ?? null;
                    if (is_string($flow) && $flow !== '') {
                        $decoded = json_decode($flow, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $flow = $decoded;
                        } else {
                            $flow = null;
                        }
                    }
                    if (!$flow) {
                        $flow = $this->buildFlowTemplate($d->pic_request_uid ?? null);
                    }

                    $now = now()->toDateTimeString();

                    // Mark current pending step as rejected (if any)
                    if (is_array($flow) && isset($flow['steps']) && is_array($flow['steps'])) {
                        $nextIdx = $this->getNextPendingFlowIndex($flow);
                        if ($nextIdx !== null) {
                            $step = &$flow['steps'][$nextIdx];
                            $step['rejected_by'] = $uid;
                            $step['rejected_at'] = $now;
                        }
                    }

                    // Root-level rejection info
                    $flow['rejected_by'] = $uid;
                    $flow['rejected_at'] = $now;

                    $d->flow             = $flow;
                    $d->kode_status      = 'REJ';
                    $d->pic_approve_uid  = $uid;
                    $d->save();

                    return [
                        'ok'          => true,
                        'id'          => $d->uuid,
                        'kode_status' => $d->kode_status,
                        'flow'        => $flow,
                    ];
                });
            } catch (\Throwable $e) {
                $res = [
                    'ok'    => false,
                    'index' => $index,
                    'uuid'  => $item['uuid'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }

            $results[] = $res;
        }

        return response()->json([
            'ok'      => collect($results)->every(fn($r) => data_get($r, 'ok') === true),
            'batch'   => true,
            'results' => $results,
        ]);
    }
}
