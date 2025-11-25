<?php

namespace App\Http\Controllers\Api;

use App\Models\Assets;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class TransferApi extends Controller
{
    public function index(Request $request)
    {
        $v = validator($request->all(), [
            'asset_uuid'   => ['nullable'],
            'asset_uuid.*' => ['nullable', 'uuid'],

            'uuid'         => ['nullable', 'uuid'],
            'uuids'        => ['nullable'],
            'uuids.*'      => ['uuid'],

            'q'            => ['nullable', 'string', 'max:200'],
            'type'         => ['nullable', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],

            // NEW filters (mirroring datatable_all)
            'workflow'       => ['nullable', 'string', 'max:50'],   // kode_status
            'requester'    => ['nullable', 'string', 'max:255'],  // pic_request_uid
            'asset_q'      => ['nullable', 'string', 'max:200'],  // asset code/description search

            // date filters
            'from'         => ['nullable', 'date'],               // legacy – created_at from
            'to'           => ['nullable', 'date'],               // legacy – created_at to
            'created_from' => ['nullable', 'date'],
            'created_to'   => ['nullable', 'date'],
            'updated_from' => ['nullable', 'date'],
            'updated_to'   => ['nullable', 'date'],

            'sort_by'      => ['nullable', Rule::in(['created_at', 'updated_at', 'kode_status', 'type', 'asset_code', 'asset_description'])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'with_trashed' => ['nullable', Rule::in(['0', '1'])],
        ])->validate();

        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )->map(fn($x) => trim($x))
            ->filter(fn($x) => Str::isUuid($x))
            ->unique()
            ->values();

        $uuidSet = collect(
            is_array($request->uuids)
                ? $request->uuids
                : (is_string($request->uuids) ? explode(',', $request->uuids) : [])
        )->when($request->filled('uuid'), fn($c) => $c->push($request->uuid))
            ->map(fn($x) => trim($x))
            ->filter(fn($x) => Str::isUuid($x))
            ->unique()
            ->values();

        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $tz      = config('app.timezone', 'UTC');

        // unify created_* + from/to (for backward compatibility)
        $createdFrom = $v['created_from'] ?? $v['from'] ?? null;
        $createdTo   = $v['created_to']   ?? $v['to']   ?? null;
        $updatedFrom = $v['updated_from'] ?? null;
        $updatedTo   = $v['updated_to']   ?? null;

        $q = Transfer::query()
            ->select([
                'assets_transfers.*',

                DB::raw('a.asset_code        as asset_code'),
                DB::raw('a.description       as asset_description'),
                DB::raw('a.kode_location     as asset_kode_location'),
                DB::raw('a.kode_asset_class  as asset_kode_asset_class'),
                DB::raw('a.kode_status       as asset_kode_status'),
                DB::raw('a.kode_sumber       as asset_kode_sumber'),

                DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS asset_kode_location_label"),
                DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS asset_kode_asset_class_label"),
                DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS asset_kode_status_label"),
                DB::raw("CASE WHEN msrc.name IS NULL THEN a.kode_sumber     ELSE a.kode_sumber     || ' - ' || msrc.name END AS asset_kode_sumber_label"),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_transfers.asset_uuid')
            ->leftJoin('master_location     as ml',   'ml.kode',   '=', 'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',  '=', 'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',   '=', 'a.kode_status')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode', '=', 'a.kode_sumber')
            ->with(['status:uuid,kode,name'])
            ->when($request->boolean('with_trashed'), fn($x) => $x->withTrashed())
            ->when($assetUuids->isNotEmpty(), fn($x) => $x->whereIn('assets_transfers.asset_uuid', $assetUuids))
            ->when($uuidSet->isNotEmpty(),   fn($x) => $x->whereIn('assets_transfers.uuid', $uuidSet))
            ->when(!empty($v['type']),       fn($x) => $x->where('assets_transfers.type', $v['type']))

            // NEW: filter by transfer workflow status (APR/ACC/REJ/etc)
            ->when(!empty($v['workflow']),     fn($x) => $x->where('assets_transfers.kode_status', $v['workflow']))

            // NEW: filter by requester (pic_request_uid)
            ->when(!empty($v['requester']),  fn($x) => $x->where('assets_transfers.pic_request_uid', $v['requester']))

            // NEW: created_at date range (like datatable_all; date-only)
            ->when($createdFrom, fn($x) => $x->whereDate('assets_transfers.created_at', '>=', $createdFrom))
            ->when($createdTo,   fn($x) => $x->whereDate('assets_transfers.created_at', '<=', $createdTo))

            // NEW: updated_at date range
            ->when($updatedFrom, fn($x) => $x->whereDate('assets_transfers.updated_at', '>=', $updatedFrom))
            ->when($updatedTo,   fn($x) => $x->whereDate('assets_transfers.updated_at', '<=', $updatedTo))

            // existing generic "q" search
            ->when(!empty($v['q']), function ($x) use ($v) {
                $like = '%' . trim($v['q']) . '%';
                $x->where(function ($w) use ($like) {
                    $w->where('assets_transfers.transfer_code', 'ILIKE', $like)
                        ->orWhere('assets_transfers.kode_status',  'ILIKE', $like)
                        ->orWhere('assets_transfers.type',         'ILIKE', $like)
                        ->orWhere('a.asset_code',                  'ILIKE', $like)
                        ->orWhere('a.description',                 'ILIKE', $like)
                        ->orWhere('a.kode_location',               'ILIKE', $like)
                        ->orWhere('a.kode_asset_class',            'ILIKE', $like)
                        ->orWhere('a.kode_status',                 'ILIKE', $like)
                        ->orWhere('a.kode_sumber',                 'ILIKE', $like)
                        ->orWhere('ml.name',                       'ILIKE', $like)
                        ->orWhere('mac.name',                      'ILIKE', $like)
                        ->orWhere('ms.name',                       'ILIKE', $like)
                        ->orWhere('msrc.name',                     'ILIKE', $like);
                });
            })

            // NEW: asset_q = search only code/description (like datatable_all's asset_q)
            ->when(!empty($v['asset_q']), function ($x) use ($v) {
                $search = '%' . trim($v['asset_q']) . '%';
                $x->where(function ($w) use ($search) {
                    $w->where('a.asset_code',  'ILIKE', $search)
                        ->orWhere('a.description', 'ILIKE', $search);
                });
            });

        $sortColumns = [
            'created_at'        => 'assets_transfers.created_at',
            'updated_at'        => 'assets_transfers.updated_at',
            'kode_status'       => 'assets_transfers.kode_status',
            'type'              => 'assets_transfers.type',
            'asset_code'        => 'a.asset_code',
            'asset_description' => 'a.description',
        ];
        $q->orderBy($sortColumns[$sortBy] ?? 'assets_transfers.created_at', $sortDir);

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
                        'asset_uuid'   => $assetUuids->all(),
                        'uuid'         => $request->query('uuid'),
                        'uuids'        => $uuidSet->all(),
                        'q'            => $request->query('q'),
                        'type'         => $request->query('type'),
                        'workflow'       => $request->query('workflow'),
                        'requester'    => $request->query('requester'),
                        'asset_q'      => $request->query('asset_q'),
                        'from'         => $request->query('from'),
                        'to'           => $request->query('to'),
                        'created_from' => $request->query('created_from'),
                        'created_to'   => $request->query('created_to'),
                        'updated_from' => $request->query('updated_from'),
                        'updated_to'   => $request->query('updated_to'),
                        'with_trashed' => $request->query('with_trashed'),
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
                    'asset_uuid'   => $assetUuids->all(),
                    'uuid'         => $request->query('uuid'),
                    'uuids'        => $uuidSet->all(),
                    'q'            => $request->query('q'),
                    'type'         => $request->query('type'),
                    'workflow'       => $request->query('workflow'),
                    'requester'    => $request->query('requester'),
                    'asset_q'      => $request->query('asset_q'),
                    'from'         => $request->query('from'),
                    'to'           => $request->query('to'),
                    'created_from' => $request->query('created_from'),
                    'created_to'   => $request->query('created_to'),
                    'updated_from' => $request->query('updated_from'),
                    'updated_to'   => $request->query('updated_to'),
                    'with_trashed' => $request->query('with_trashed'),
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


    public function store(Request $request)
    {
        // --- AUTHORIZATION (same idea as web TransferController) ---
        $user = $request->user(); // works for Sanctum bearer AND web session
        abort_unless(
            $user && $user->hasAction('MOVEMENT', 'C'),
            403,
            'You are not allowed to create movement.'
        );

        $isBatch = is_array($request->input('items'));

        if ($isBatch) {
            $root = $request->validate([
                'items'                         => ['required', 'array', 'min:1'],
                'items.*.uuid'                  => ['nullable', 'uuid'],
                'items.*.asset_uuid'            => ['required', 'uuid', 'exists:assets,uuid'],
                'items.*.type'                  => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
                'items.*.after.value'           => ['required', 'string'],
                'items.*.note'                  => ['nullable', 'string', 'max:1000'],
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
                'type'              => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
                'after.value'       => ['required', 'string'],
                'note'              => ['nullable', 'string', 'max:1000'],
                'created_at'        => ['nullable', 'date'],

                'file'              => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
                'file_b64'          => ['nullable', 'array'],
                'file_b64.name'     => ['required_with:file_b64', 'string', 'max:255'],
                'file_b64.mime'     => ['required_with:file_b64', 'string', 'max:100'],
                'file_b64.data'     => ['required_with:file_b64', 'string'],
            ]);
        }

        // --- CURRENT USER NAME (PIC) – align with web TransferController ---
        $currentUid = (string) (
            $user->name
            ?? $user->username
            ?? data_get($request->session()->get('ldap_user'), 'name')
            ?? data_get($request->session()->get('ldap_user'), 'username', '')
        );

        abort_if($currentUid === '', 401, 'No session UID.');

        $items   = $isBatch ? $root['items'] : [$root];
        $results = [];

        foreach ($items as $idx => $payload) {
            try {
                $res = $this->createTransferFromPayload($request, $payload, $currentUid);
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

    private function createTransferFromPayload(Request $rootReq, array $data, string $currentUid): array
    {
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

        $asset = Assets::with(['assignment', 'status', 'location'])->findOrFail($data['asset_uuid']);

        $beforeCode = match ($data['type']) {
            'owner'       => $asset->assignment?->asset_owner,
            'user'        => $asset->assignment?->asset_user,
            'maintenance' => $asset->assignment?->asset_maintenance,
            'status'      => $asset->kode_status,
            'location'    => $asset->kode_location,
            default       => null,
        };

        $afterCode       = (string) data_get($data, 'after.value');
        $beforeLabel     = $this->resolveLabel($data['type'], $beforeCode);
        $afterLabel      = $this->resolveLabel($data['type'], $afterCode);
        $initialWorkflow = 'APR';

        $transfer = DB::transaction(function () use (
            $asset,
            $data,
            $initialWorkflow,
            $beforeCode,
            $afterCode,
            $beforeLabel,
            $afterLabel,
            $currentUid,
            $rootReq
        ) {
            $code = $this->generateTransferCode($asset->asset_code);

            $path = $orig = $mime = $size = null;

            if (!empty($data['file_b64'])) {
                $approxSize = $this->approxBase64Size($data['file_b64']['data']);
                if ($approxSize > 20 * 1024 * 1024) {
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

            $payload = [
                'uuid'             => $data['uuid'] ?? (string) Str::uuid(),
                'asset_uuid'       => $asset->uuid,
                'transfer_code'    => $code,
                'type'             => $data['type'],

                'before'           => ['value' => $beforeCode],
                'after'            => ['value' => $afterCode],

                'before_label'     => $beforeLabel,
                'after_label'      => $afterLabel,

                'kode_status'      => $initialWorkflow,
                'note'             => $data['note'] ?? null,
                'pic_request_uid'  => $currentUid,
                'pic_approve_uid'  => null,

                'file_path'        => $path,
                'file_name'        => $orig,
                'file_mime'        => $mime,
                'file_size'        => $size,
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            if (in_array($data['type'], ['owner', 'user', 'maintenance', 'location'], true)) {
                $payload['flow'] = $this->buildFlowTemplate($data['type'], $currentUid);
            }

            return Transfer::create($payload);
        });

        return [
            'ok'   => true,
            'id'   => $transfer->uuid,
            'code' => $transfer->transfer_code,
        ];
    }
    private function mapRows($rows, string $tz)
    {
        $codes = ['usercode' => [], 'status' => [], 'location' => []];

        foreach ($rows as $t) {
            $type   = (string) $t->type;
            $before = data_get($t->before, 'value');
            $after  = data_get($t->after,  'value');

            if (!$before && !$after) continue;

            switch ($type) {
                case 'owner':
                case 'user':
                case 'maintenance':
                    if ($before) $codes['usercode'][] = $before;
                    if ($after)  $codes['usercode'][] = $after;
                    break;
                case 'status':
                    if ($before) $codes['status'][] = $before;
                    if ($after)  $codes['status'][] = $after;
                    break;
                case 'location':
                    if ($before) $codes['location'][] = $before;
                    if ($after)  $codes['location'][] = $after;
                    break;
            }
        }

        $setUser   = collect($codes['usercode'])->filter()->unique();
        $setStatus = collect($codes['status'])->filter()->unique();
        $setLoc    = collect($codes['location'])->filter()->unique();

        $mapUser = $setUser->isNotEmpty()
            ? MasterUserCode::whereIn('kode', $setUser)->pluck('department', 'kode')->all()
            : [];

        $mapStatus = $setStatus->isNotEmpty()
            ? MasterStatus::whereIn('kode', $setStatus)
            ->where(fn($q) => $q->where('type', 'Asset')->orWhereNull('type'))
            ->pluck('name', 'kode')->all()
            : [];

        $mapLoc = $setLoc->isNotEmpty()
            ? MasterLocation::whereIn('kode', $setLoc)->pluck('name', 'kode')->all()
            : [];

        return $rows->map(function (Transfer $t) use ($mapUser, $mapStatus, $mapLoc, $tz) {
            $beforeCode = data_get($t->before, 'value');
            $afterCode  = data_get($t->after,  'value');

            $beforeDisplay = $this->labelFor($t->type, $beforeCode, $mapUser, $mapStatus, $mapLoc);
            $afterDisplay  = $this->labelFor($t->type, $afterCode,  $mapUser, $mapStatus, $mapLoc);

            $workflowLabel = $t->status
                ? ($t->kode_status . ' - ' . $t->status->name)
                : $t->kode_status;

            $asset = [
                'asset_uuid'                    => $t->asset_uuid,
                'asset_code'                    => $t->getAttribute('asset_code'),
                'asset_description'             => $t->getAttribute('asset_description'),
                'asset_kode_location'           => $t->getAttribute('asset_kode_location'),
                'asset_kode_asset_class'        => $t->getAttribute('asset_kode_asset_class'),
                'asset_kode_status'             => $t->getAttribute('asset_kode_status'),
                'asset_kode_sumber'             => $t->getAttribute('asset_kode_sumber'),
                'asset_kode_location_label'     => $t->getAttribute('asset_kode_location_label'),
                'asset_kode_asset_class_label'  => $t->getAttribute('asset_kode_asset_class_label'),
                'asset_kode_status_label'       => $t->getAttribute('asset_kode_status_label'),
                'asset_kode_sumber_label'       => $t->getAttribute('asset_kode_sumber_label'),
            ];

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
                    'download_url'  => route('files.download.kind', ['kind' => 'transfer', 'uuid' => $t->uuid]),
                    'file_url'      => $exists ? url('storage/' . ltrim($t->file_path, '/')) : null,
                ];
            }
            $flowFileObj = null;
            if ($t->flow_file_path) {
                $disk   = 'public';
                $fs     = Storage::disk($disk);
                $exists = $fs->exists($t->flow_file_path);
                $size   = $t->flow_file_size ?? ($exists ? $fs->size($t->flow_file_path) : null);
                $mtime  = $t->flow_file_updated_at
                    ? strtotime($t->flow_file_updated_at)
                    : ($exists ? $fs->lastModified($t->flow_file_path) : null);
                    
                $downloadUrl = route('files.download.kind', [
                    'kind' => 'transfer',
                    'uuid' => $t->uuid,
                    'slot' => 'form',
                ]);

                $flowFileObj = [
                    'name'          => $t->flow_file_name ?: basename($t->flow_file_path),
                    'mime'          => $t->flow_file_mime,
                    'size'          => $size,
                    'sha256'        => $t->flow_file_sha256 ?? null,
                    'last_modified' => $mtime ? gmdate('c', $mtime) : null,
                    'download_url'  => $downloadUrl,
                    'file_url'      => $exists ? $downloadUrl : null,
                ];
            }

            $formUrl = in_array($t->type, ['owner', 'user', 'maintenance'], true)
                ? route('transfers.downloadForms', ['transfer' => $t->uuid])
                : null;

            return [
                'uuid'              => $t->uuid,
                'transfer_code'     => $t->transfer_code,
                'type'              => $t->type,
                'kode_status'       => $t->kode_status,
                'workflow_label'    => $workflowLabel,

                'before_code'       => $beforeCode,
                'after_code'        => $afterCode,
                'before_display'    => $beforeDisplay,
                'after_display'     => $afterDisplay,

                'asset'             => $asset,

                'file'              => $fileObj,
                'flow_file'         => $flowFileObj,

                'form_download_url' => $formUrl,

                'created_at'        => optional($t->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'        => optional($t->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'        => optional($t->deleted_at)->timezone($tz)?->toIso8601String(),
            ];
        });
    }

    protected function buildFlowTemplate(string $type, ?string $creatorName = null): array
    {
        $now = now()->toDateTimeString();

        if ($type === 'location') {
            return [
                'key'   => 'movement_location',
                'steps' => [
                    [
                        'code'        => 'create',
                        'label'       => 'Create',
                        'role'        => 'User Departemen',
                        'approved_by' => $creatorName,
                        'approved_at' => $creatorName ? $now : null,
                    ],
                    [
                        'code'        => 'dept_head',
                        'label'       => 'Approval Dept.Head / Section',
                        'role'        => 'User - Dept.Head / Section',
                        'approved_by' => null,
                        'approved_at' => null,
                    ],
                    [
                        'code'        => 'asset_mgt',
                        'label'       => 'Completed (Asset Management)',
                        'role'        => 'Asset Management',
                        'approved_by' => null,
                        'approved_at' => null,
                    ],
                ],
            ];
        }

        return [
            'key'   => 'movement_assignment',
            'steps' => [
                [
                    'code'        => 'create',
                    'label'       => 'Create Request',
                    'role'        => 'User Departemen (New Owner/User/Maint)',
                    'approved_by' => $creatorName,
                    'approved_at' => $creatorName ? $now : null,
                ],
                [
                    'code'        => 'new_dept_head',
                    'label'       => 'Approval Dept.Head New Owner/User/Maint',
                    'role'        => 'User - Dept.Head / Section (New)',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'old_dept_head',
                    'label'       => 'Approval Dept.Head Old Owner/User/Maint (optional)',
                    'role'        => 'User - Dept.Head / Section (Old)',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
                [
                    'code'        => 'asset_mgt',
                    'label'       => 'Completed (Asset Management)',
                    'role'        => 'Asset Management',
                    'approved_by' => null,
                    'approved_at' => null,
                ],
            ],
        ];
    }

    protected function labelFor(string $type = null, ?string $code = null, array $mapUser = [], array $mapStatus = [], array $mapLoc = []): string
    {
        if (!$code) return '(empty)';

        return match ($type) {
            'owner', 'user', 'maintenance' => isset($mapUser[$code])   ? "{$code} - {$mapUser[$code]}"   : $code,
            'status'                       => isset($mapStatus[$code]) ? "{$code} - {$mapStatus[$code]}" : $code,
            'location'                     => isset($mapLoc[$code])    ? "{$code} - {$mapLoc[$code]}"    : $code,
            default                        => $code,
        };
    }

    private function saveUpload(?UploadedFile $file, string $code): array
    {
        if (!$file) return [null, null, null, null];

        $disk   = 'public';
        $folder = 'transfers/' . date('Y/m');
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
        $folder = 'transfers/' . date('Y/m');
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

    private function generateTransferCode(string $assetCode): string
    {
        $prefix = 'TRF-' . preg_replace('/[^A-Za-z0-9]/', '', $assetCode);
        return sprintf('%s-%s-%s', $prefix, now()->format('Ymd'), strtoupper(Str::random(6)));
    }
    public function downloadForms(Request $request, Transfer $transfer): StreamedResponse
    {
        // Authorization (API style, still fine)
        abort_unless($request->user()?->hasAction('MOVEMENT', 'R'), 403);

        // if (! in_array($transfer->type, ['owner', 'user', 'maintenance'], true)) {
        //     abort(404, 'Form Transfer only available for Owner/User/Maintenance movement.');
        // }

        $transfer->load([
            'asset.assignment.owner.division',
            'asset.assignment.user.division',
            'asset.assignment.maintenance.division',
            'asset.location',
        ]);

        $asset      = $transfer->asset;
        $tfNote     = $transfer->note;
        $assign     = $asset?->assignment;
        $beforeVal  = data_get($transfer->before, 'value');
        $afterVal   = data_get($transfer->after,  'value');
        $createdAt  = $transfer->created_at?->timezone('Asia/Jakarta');

        $beforeUc = null;

        switch ($transfer->type) {
            case 'owner':
                $beforeUc = $assign?->owner;
                break;
            case 'user':
                $beforeUc = $assign?->user;
                break;
            case 'maintenance':
                $beforeUc = $assign?->maintenance;
                break;
        }

        $afterUc = $afterVal
            ? MasterUserCode::with('division')->where('kode', $afterVal)->first()
            : null;

        $fromDeptLabel = '';
        $fromDivLabel  = '';
        $fromLocLabel  = '';

        $toDeptLabel   = '';
        $toDivLabel    = '';
        $toLocLabel    = '';

        if ($beforeUc) {
            $fromDeptLabel = trim($beforeUc->kode . ' - ' . $beforeUc->department);
            if ($beforeUc->division) {
                $fromDivLabel = trim($beforeUc->division->kode . ' - ' . $beforeUc->division->name);
            }
        } elseif ($beforeVal !== null && $beforeVal !== '') {
            $fromDeptLabel = $beforeVal;
        }

        if ($afterUc) {
            $toDeptLabel = trim($afterUc->kode . ' - ' . $afterUc->department);
            if ($afterUc->division) {
                $toDivLabel = trim($afterUc->division->kode . ' - ' . $afterUc->division->name);
            }
        } elseif ($afterVal !== null && $afterVal !== '') {
            $toDeptLabel = $afterVal;
        }

        $loc = $asset?->location;
        $locLabel = $loc ? trim($loc->kode . ' - ' . $loc->name) : '';
        $fromLocLabel = $locLabel;
        $toLocLabel   = $locLabel;

        $flowRaw   = $transfer->flow ?? null;
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

                    // e.g. "Create Disposal Request (User Departemen) - 21-11-2025 15:53 - Administrator"
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

        // Make sure this path exists in your project
        $templatePath = storage_path('app/public/template/form_transfer_asset.xlsx');
        $spreadsheet  = IOFactory::load($templatePath);

        $sheet = $spreadsheet->getSheetByName('Form Transfer');

        $sheet->setCellValue('V11', $transfer->transfer_code ?? '');

        $sheet->setCellValue('G15', $fromDeptLabel);
        $sheet->setCellValue('G16', $fromDivLabel);
        $sheet->setCellValue('G17', $fromLocLabel);
        $sheet->setCellValue('G18', $createdAt ? $createdAt->format('d-m-Y') : '');

        $sheet->setCellValue('S15', $toDeptLabel);
        $sheet->setCellValue('S16', $toDivLabel);
        $sheet->setCellValue('S17', $toLocLabel);
        $sheet->setCellValue('S18', $createdAt ? $createdAt->format('d-m-Y') : '');

        $sheet->setCellValue('C23', $asset?->asset_code ?? '');
        $sheet->setCellValue('D23', $asset?->description ?? '');

        $qty = $asset?->value?->quantity;
        $sheet->setCellValue('L23', $qty !== null ? $qty : '');

        $uomText = $asset?->value?->uom?->name
            ?? $asset?->value?->kode_uom
            ?? '';
        $sheet->setCellValue('N23', $uomText);

        $sheet->setCellValue('P23', $asset?->notes ?? '');

        $sheet->setCellValue('C30', $tfNote ?? '');

        $sheet->setCellValue('C34', $flowText);
        $sheet->getStyle('C34')->getAlignment()->setWrapText(true);

        $sheet->setCellValue('C36', $fromDeptLabel);
        $sheet->setCellValue('I36', $toDeptLabel);

        $fileName = 'Form Transfer Asset - ' . ($transfer->transfer_code ?? 'MOV') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
