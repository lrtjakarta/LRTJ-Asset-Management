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

class TransferApi extends Controller
{
    public function index(Request $request)
    {
        $v = validator($request->all(), [
            'asset_uuid'   => ['nullable'],
            'asset_uuid.*' => ['nullable', 'uuid'],

            'q'            => ['nullable', 'string', 'max:200'],
            'type'         => ['nullable', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'sort_by'      => ['nullable', Rule::in(['created_at', 'updated_at', 'kode_status', 'type', 'asset_code', 'asset_description'])],
            'sort_dir'     => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
            'with_trashed' => ['nullable', Rule::in(['0', '1'])],
            'uuids'   => ['nullable'],
            'uuids.*' => ['uuid'],
        ])->validate();

        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )->map(fn($x) => trim($x))->filter()->unique()->values();
        $uuids = collect(is_array($request->uuids) ? $request->uuids
          : (is_string($request->uuids) ? explode(',', $request->uuids) : []))
          ->map(fn($x)=>trim($x))->filter()->unique()->values();

        foreach ($assetUuids as $u) {
            if (!Str::isUuid($u)) abort(422, "asset_uuid contains invalid UUID: {$u}");
        }

        $sortBy  = $v['sort_by'] ?? 'created_at';
        $sortDir = $v['sort_dir'] ?? 'desc';
        $tz      = config('app.timezone', 'UTC');

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
            ->when($uuids->isNotEmpty(),     fn($x) => $x->whereIn('assets_transfers.uuid', $uuids))
            ->when(!empty($v['type']), fn($x) => $x->where('assets_transfers.type', $v['type']))
            ->when(!empty($v['from']), fn($x) => $x->where('assets_transfers.created_at', '>=', $v['from']))
            ->when(!empty($v['to']),   fn($x) => $x->where('assets_transfers.created_at', '<=', $v['to']))
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
                        'q'            => $request->query('q'),
                        'type'         => $request->query('type'),
                        'from'         => $request->query('from'),
                        'to'           => $request->query('to'),
                        'with_trashed' => $request->query('with_trashed'),
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
                    'asset_uuid'   => $assetUuids->all(),
                    'q'            => $request->query('q'),
                    'type'         => $request->query('type'),
                    'from'         => $request->query('from'),
                    'to'           => $request->query('to'),
                    'with_trashed' => $request->query('with_trashed'),
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
        $tz = config('app.timezone', 'UTC');

        $row = Transfer::query()
            ->select([
                'assets_transfers.*',
                DB::raw('a.asset_code        as asset_code'),
                DB::raw('a.description       as asset_description'),
                DB::raw('a.kode_location     as asset_kode_location'),
                DB::raw('a.kode_asset_class  as asset_kode_asset_class'),
                DB::raw('a.kode_status       as asset_kode_status'),
                DB::raw('a.kode_sumber       as asset_kode_sumber'),
                DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS asset_kode_location_label"),
                DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name  END AS asset_kode_asset_class_label"),
                DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS asset_kode_status_label"),
                DB::raw("CASE WHEN msrc.name IS NULL THEN a.kode_sumber     ELSE a.kode_sumber     || ' - ' || msrc.name END AS asset_kode_sumber_label"),
            ])
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_transfers.asset_uuid')
            ->leftJoin('master_location     as ml',   'ml.kode',   '=', 'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',  '=', 'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',   '=', 'a.kode_status')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode', '=', 'a.kode_sumber')
            ->with(['status:uuid,kode,name'])
            ->where('assets_transfers.uuid', $uuid)
            ->firstOrFail();

        $data = $this->mapRows(collect([$row]), $tz)->first();

        return response()->json(['data' => $data]);
    }

    protected function mapRows($rows, string $tz)
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
                $mtime  = $t->file_updated_at ? strtotime($t->file_updated_at) : ($exists ? $fs->lastModified($t->file_path) : null);

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

            return [
                'uuid'           => $t->uuid,
                'transfer_code'  => $t->transfer_code,
                'type'           => $t->type,
                'kode_status'    => $t->kode_status,
                'workflow_label' => $workflowLabel,

                'before_code'    => $beforeCode,
                'after_code'     => $afterCode,
                'before_display' => $beforeDisplay,
                'after_display'  => $afterDisplay,

                'asset'          => $asset,
                'file'           => $fileObj,

                'created_at'     => optional($t->created_at)->timezone($tz)?->toIso8601String(),
                'updated_at'     => optional($t->updated_at)->timezone($tz)?->toIso8601String(),
                'deleted_at'     => optional($t->deleted_at)->timezone($tz)?->toIso8601String(),
            ];
        });
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

    public function store(Request $request)
    {
        if (is_array($request->input('items'))) {
            $items = $request->input('items');
            if (empty($items)) {
                return response()->json(['message' => 'items must be a non-empty array'], 422);
            }

            $results = [];
            foreach ($items as $idx => $item) {
                try {
                    $results[] = $this->createTransferFromPayload($request, $item, true);
                } catch (\Throwable $e) {
                    $results[] = [
                        'ok'    => false,
                        'index' => $idx,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'ok'      => true,
                'batch'   => true,
                'results' => $results,
            ]);
        }

        try {
            $res = $this->createTransferFromPayload($request, $request->all(), false);
            return response()->json($res, ($res['duplicate'] ?? false) ? 200 : 201);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }
    private function createTransferFromPayload(Request $rootReq, array $payload, bool $isBatch): array
    {
        $rules = [
            'uuid'              => ['nullable', 'uuid'],
            'client_request_id' => ['nullable', 'string', 'max:100'],
            'asset_uuid'        => ['required', 'uuid', 'exists:assets,uuid'],
            'type'              => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'       => ['required', 'string'],
            'note'              => ['nullable', 'string', 'max:1000'],
            'created_at'        => ['nullable', 'date'],
        ];

        if ($isBatch) {
            $rules['file_b64'] = ['nullable', 'array'];
            $rules['file_b64.name'] = ['required_with:file_b64', 'string', 'max:255'];
            $rules['file_b64.mime'] = ['required_with:file_b64', 'string', 'max:100'];
            $rules['file_b64.data'] = ['required_with:file_b64', 'string'];
        } else {
            $rules['file'] = ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'];
        }

        $data = validator($payload, $rules)->validate();

        $idemKey = $rootReq->header('Idempotency-Key') ?: ($data['client_request_id'] ?? null);

        $currentUid = (string) data_get($rootReq->session()->get('ldap_user'), 'uid', '');
        if ($currentUid === '') abort(401, 'No session UID.');

        if (!empty($data['uuid'])) {
            $existing = Transfer::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return [
                    'ok'        => true,
                    'duplicate' => true,
                    'via'       => 'uuid',
                    'id'        => $existing->uuid,
                    'code'      => $existing->transfer_code,
                ];
            }
        } elseif ($idemKey) {
            $existing = Transfer::where('idempotency_key', $idemKey)->first();
            if ($existing) {
                return [
                    'ok'        => true,
                    'duplicate' => true,
                    'via'       => 'idempotency_key',
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

        $afterCode   = (string) $data['after']['value'];
        $beforeLabel = $this->resolveLabel($data['type'], $beforeCode);
        $afterLabel  = $this->resolveLabel($data['type'], $afterCode);
        $initialWorkflow = 'APR';

        $transfer = DB::transaction(function () use ($asset, $data, $initialWorkflow, $beforeCode, $afterCode, $beforeLabel, $afterLabel, $currentUid, $rootReq, $idemKey, $isBatch) {
            $code = $this->generateTransferCode($asset->asset_code);

            $path = $orig = $mime = $size = null;

            if ($isBatch && !empty($data['file_b64'])) {
                [$path, $orig, $mime, $size] = $this->saveBase64File(
                    $data['file_b64']['data'],
                    $data['file_b64']['name'],
                    $data['file_b64']['mime'],
                    $asset,
                    $code
                );
            } elseif (!$isBatch) {
                [$path, $orig, $mime, $size] = $this->saveUpload($rootReq->file('file'), $asset, $code, null);
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
                'idempotency_key'  => $idemKey,
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            return Transfer::create($payload);
        });

        return [
            'ok'   => true,
            'id'   => $transfer->uuid,
            'code' => $transfer->transfer_code,
        ];
    }
    private function saveUpload(?UploadedFile $file, Assets $asset, string $code, ?string $subdir): array
    {
        if (!$file) return [null, null, null, null];

        $disk   = 'public';
        $folder = 'transfers/' . ($subdir ?: date('Y/m'));
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
        $folder = 'transfers/' . date('Y/m');
        $name   = $code . '__' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

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

        if (str_starts_with($b64, 'data:')) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }

        $binary = base64_decode($b64, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 file data.');
        }

        $storedPath = $folder . '/' . $name . '.' . strtolower($ext);
        Storage::disk($disk)->put($storedPath, $binary);

        return [
            $storedPath,
            $originalName,
            $mime,
            strlen($binary),
        ];
    }
    private function generateTransferCode(string $assetCode): string
    {
        $prefix = 'TRF-' . preg_replace('/[^A-Za-z0-9]/', '', $assetCode);
        return sprintf('%s-%s-%s', $prefix, now()->format('Ymd'), strtoupper(Str::random(6)));
    }
}
