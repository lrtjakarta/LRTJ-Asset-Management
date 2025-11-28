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

    /**
     * GET /api/v1/stock-opname
     *
     * Union MOVEMENT (Transfer ACC) + DISPOSAL (ACC) dengan filter & pagination.
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
        ]);

        $tz      = config('app.timezone', 'UTC');
        $sortBy  = $v['sort_by'] ?? 'updated_at';
        $sortDir = $v['sort_dir'] ?? 'desc';

        // normalize asset_uuid: array atau string "uuid1,uuid2"
        $assetUuids = collect(
            is_array($request->asset_uuid)
                ? $request->asset_uuid
                : (is_string($request->asset_uuid) ? explode(',', $request->asset_uuid) : [])
        )->map(fn($x) => trim($x))
            ->filter(fn($x) => Str::isUuid($x))
            ->unique()
            ->values();

        $source    = $v['source'] ?? null;
        $tfType    = $v['tf_type'] ?? ($v['type'] ?? null);
        $assetQ    = trim((string) ($v['asset_q'] ?? ''));
        $requester = trim((string) ($v['requester'] ?? ''));
        $qSearch   = trim((string) ($v['q'] ?? ''));

        $dateFrom    = $v['date_from']    ?? null;
        $dateTo      = $v['date_to']      ?? null;
        $updatedFrom = $v['updated_from'] ?? null;
        $updatedTo   = $v['updated_to']   ?? null;
        $createdFrom = $v['created_from'] ?? null;
        $createdTo   = $v['created_to']   ?? null;

        // === base union queries (copy dari datatable) ===
        $t = DB::table('assets_transfers as t')
            ->join('assets as a', 'a.uuid', '=', 't.asset_uuid')
            ->selectRaw("
                t.uuid,
                t.asset_uuid,
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
            ->where('t.kode_status', 'ACC');

        $d = DB::table('assets_disposals as d')
            ->join('assets as a', 'a.uuid', '=', 'd.asset_uuid')
            ->selectRaw("
                d.uuid,
                d.asset_uuid,
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
            ->where('d.kode_status', 'ACC');

        if ($assetUuids->isNotEmpty()) {
            $t->whereIn('t.asset_uuid', $assetUuids);
            $d->whereIn('d.asset_uuid', $assetUuids);
        }

        if ($source === 'transfer') {
            $union = $t;
        } elseif ($source === 'disposal') {
            $union = $d;
        } else {
            $union = $t->unionAll($d);
        }

        $q = DB::query()->fromSub($union, 'u');

        if ($tfType) {
            $q->where('source_type', 'transfer')
                ->where('tf_type', $tfType);
        }

        if ($dateFrom) {
            $q->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('created_at', '<=', $dateTo);
        }

        if ($updatedFrom) {
            $q->whereDate('updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('updated_at', '<=', $updatedTo);
        }

        if ($assetQ !== '') {
            $q->where(function ($qq) use ($assetQ) {
                $qq->where('asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('description', 'ilike', "%{$assetQ}%");
            });
        }

        if ($requester !== '') {
            $q->where('pic_request_uid', 'ilike', "%{$requester}%");
        }

        if ($qSearch !== '') {
            $q->where(function ($qq) use ($qSearch) {
                $qq->where('code', 'ilike', "%{$qSearch}%")
                    ->orWhere('asset_code', 'ilike', "%{$qSearch}%")
                    ->orWhere('description', 'ilike', "%{$qSearch}%")
                    ->orWhere('note', 'ilike', "%{$qSearch}%")
                    ->orWhere('pic_request_uid', 'ilike', "%{$qSearch}%")
                    ->orWhere('pic_approve_uid', 'ilike', "%{$qSearch}%");
            });
        }

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
                        'q'           => $qSearch,
                        'source'      => $source,
                        'tf_type'     => $tfType,
                        'requester'   => $requester,
                        'asset_uuid'  => $assetUuids->all(),
                        'asset_q'     => $assetQ,
                        'date_from'   => $dateFrom,
                        'date_to'     => $dateTo,
                        'updated_from' => $updatedFrom,
                        'updated_to'  => $updatedTo,
                        'created_from' => $createdFrom,
                        'created_to'  => $createdTo,
                    ],
                ],
            ]);
        }

        $perPage   = (int) ($v['per_page'] ?? 15);
        $page      = (int) ($v['page'] ?? 1);

        $paginator = $q->orderBy($orderBy, $sortDir)->paginate($perPage, ['*'], 'page', $page)
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
     *
     * Create STOCK OPN transfer (OPNyyMMxxxx), langsung ACC.
     * Support:
     *  - file upload: file, flow_file
     *  - base64: file_b64, flow_file_b64
     */
    public function storeTransfer(Request $request)
    {
        abort_unless($this->canCreateStockOpname($request), 403);

        $user = $request->user();
        $uid  = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');

        $data = $request->validate([
            'uuid'                => ['nullable', 'uuid'],
            'asset_uuid'          => ['required', 'uuid', 'exists:assets,uuid'],
            'type'                => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'         => ['required', 'string'],
            'note'                => ['nullable', 'string', 'max:1000'],
            'created_at'          => ['nullable', 'date'],

            // file OR file_b64
            'file'                => ['nullable', 'file', 'max:51200'],
            'file_b64'            => ['nullable', 'array'],
            'file_b64.name'       => ['required_with:file_b64', 'string', 'max:255'],
            'file_b64.mime'       => ['required_with:file_b64', 'string', 'max:100'],
            'file_b64.data'       => ['required_with:file_b64', 'string'],

            // flow_file OR flow_file_b64
            'flow_file'           => ['nullable', 'file', 'max:51200'],
            'flow_file_b64'       => ['nullable', 'array'],
            'flow_file_b64.name'  => ['required_with:flow_file_b64', 'string', 'max:255'],
            'flow_file_b64.mime'  => ['required_with:flow_file_b64', 'string', 'max:100'],
            'flow_file_b64.data'  => ['required_with:flow_file_b64', 'string'],
        ]);

        // Idempotent by UUID
        if (!empty($data['uuid'])) {
            if ($existing = Transfer::where('uuid', $data['uuid'])->first()) {
                return response()->json([
                    'ok'        => true,
                    'duplicate' => true,
                    'via'       => 'uuid',
                    'id'        => $existing->uuid,
                    'code'      => $existing->transfer_code,
                ], 200);
            }
        }

        $asset = Assets::findOrFail($data['asset_uuid']);

        $res = DB::transaction(function () use ($asset, $data, $uid, $request) {
            // Lock asset untuk update assignment/status/location
            $assetSv = Assets::where('uuid', $asset->uuid)->lockForUpdate()->first();

            $before = null;
            switch ($data['type']) {
                case 'owner':
                    $before = optional($assetSv->assignment)->asset_owner;
                    $assetSv->assignment()->updateOrCreate(
                        ['asset_uuid' => $assetSv->uuid],
                        ['asset_owner' => data_get($data, 'after.value')]
                    );
                    break;
                case 'user':
                    $before = optional($assetSv->assignment)->asset_user;
                    $assetSv->assignment()->updateOrCreate(
                        ['asset_uuid' => $assetSv->uuid],
                        ['asset_user' => data_get($data, 'after.value')]
                    );
                    break;
                case 'maintenance':
                    $before = optional($assetSv->assignment)->asset_maintenance;
                    $assetSv->assignment()->updateOrCreate(
                        ['asset_uuid' => $assetSv->uuid],
                        ['asset_maintenance' => data_get($data, 'after.value')]
                    );
                    break;
                case 'status':
                    $before = $assetSv->kode_status;
                    $assetSv->kode_status = data_get($data, 'after.value');
                    $assetSv->save();
                    break;
                case 'location':
                    $before = $assetSv->kode_location;
                    $assetSv->kode_location = data_get($data, 'after.value');
                    $assetSv->save();
                    break;
            }

            $beforeLabel = $this->resolveLabel($data['type'], $before);
            $afterLabel  = $this->resolveLabel($data['type'], data_get($data, 'after.value'));

            $now    = Carbon::now();
            $prefix = 'OPN' . $now->format('ym');

            $last = Transfer::where('transfer_code', 'like', $prefix . '%')
                ->orderBy('transfer_code', 'desc')
                ->lockForUpdate()
                ->first();

            $seq  = $last ? ((int) substr($last->transfer_code, -4)) + 1 : 1;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $uuid = $data['uuid'] ?? (string) Str::uuid();

            // Files
            $path = $orig = $mime = $size = null;
            $flowPath = $flowOrig = $flowMime = $flowSize = null;

            // base64 file (take priority)
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
            } elseif ($request->file('file')) {
                [$path, $orig, $mime, $size] = $this->saveUploadTf(
                    $request->file('file'),
                    $asset,
                    $code,
                    null
                );
            }

            // base64 flow_file (signed form)
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
                    'transfers/' . $asset->uuid,
                    $code . '-form'
                );
            } elseif ($request->file('flow_file')) {
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
                'transfer_code'   => $code,
                'type'            => $data['type'],
                'before'          => ['value' => $before],
                'after'           => ['value' => data_get($data, 'after.value')],
                'before_label'    => $beforeLabel,
                'after_label'     => $afterLabel,
                'kode_status'     => 'ACC', // langsung approve
                'note'            => $data['note'] ?? null,
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

        return response()->json($res, 201);
    }

    /**
     * POST /api/v1/stock-opname/disposal
     *
     * Create STOCK OPN disposal (OPNyyMMxxxx), langsung ACC.
     * Support:
     *  - file upload: file, flow_file, ba_file
     *  - base64: file_b64, flow_file_b64, ba_file_b64
     */
    public function storeDisposal(Request $request)
    {
        abort_unless($this->canCreateStockOpname($request), 403);

        $user = $request->user();
        $uid  = $user ? ($user->name ?? $user->id) : null;
        abort_if(!$uid, 401, 'No session UID');

        $data = $request->validate([
            'uuid'          => ['nullable', 'uuid'],
            'asset_uuid'    => ['required', 'uuid', 'exists:assets,uuid'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'target_status' => ['nullable', 'string'],
            'reason'        => [
                'required',
                Rule::in(['Sale', 'Waste', 'Donate', 'Held']),
            ],
            'created_at'    => ['nullable', 'date'],

            // evidence file
            'file'          => ['nullable', 'file', 'max:51200'],
            'file_b64'      => ['nullable', 'array'],
            'file_b64.name' => ['required_with:file_b64', 'string', 'max:255'],
            'file_b64.mime' => ['required_with:file_b64', 'string', 'max:100'],
            'file_b64.data' => ['required_with:file_b64', 'string'],

            // flow form (xlsx)
            'flow_file'           => ['nullable', 'file', 'max:51200'],
            'flow_file_b64'       => ['nullable', 'array'],
            'flow_file_b64.name'  => ['required_with:flow_file_b64', 'string', 'max:255'],
            'flow_file_b64.mime'  => ['required_with:flow_file_b64', 'string', 'max:100'],
            'flow_file_b64.data'  => ['required_with:flow_file_b64', 'string'],

            // BA (docx)
            'ba_file'           => ['nullable', 'file', 'max:51200'],
            'ba_file_b64'       => ['nullable', 'array'],
            'ba_file_b64.name'  => ['required_with:ba_file_b64', 'string', 'max:255'],
            'ba_file_b64.mime'  => ['required_with:ba_file_b64', 'string', 'max:100'],
            'ba_file_b64.data'  => ['required_with:ba_file_b64', 'string'],
        ]);

        // Idempotent by UUID
        if (!empty($data['uuid'])) {
            if ($existing = Disposal::where('uuid', $data['uuid'])->first()) {
                return response()->json([
                    'ok'        => true,
                    'duplicate' => true,
                    'via'       => 'uuid',
                    'id'        => $existing->uuid,
                    'code'      => $existing->disposal_code,
                ], 200);
            }
        }

        $asset  = Assets::findOrFail($data['asset_uuid']);
        $target = $data['target_status'] ?? 'DIS';

        $ok = MasterStatus::where('kode', $target)
            ->where(function ($q) {
                $q->where('type', 'Asset')->orWhereNull('type');
            })
            ->exists();
        abort_unless($ok, 422, 'Target disposal status not valid');

        $res = DB::transaction(function () use ($asset, $data, $uid, $request, $target) {
            $now    = Carbon::now();
            $prefix = 'OPN' . $now->format('ym');

            $last = Disposal::where('disposal_code', 'like', $prefix . '%')
                ->orderBy('disposal_code', 'desc')
                ->lockForUpdate()
                ->first();

            $seq  = $last ? ((int) substr($last->disposal_code, -4)) + 1 : 1;
            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $uuid = $data['uuid'] ?? (string) Str::uuid();

            $path = $orig = $mime = $size = null;
            $flowPath = $flowOrig = $flowMime = $flowSize = null;
            $baPath = $baOrig = $baMime = $baSize = null;

            // evidence file
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
                    'disposals/' . $asset->uuid,
                    $code . '-file'
                );
            } elseif ($request->file('file')) {
                [$path, $orig, $mime, $size] = $this->saveUploadDis(
                    $request->file('file'),
                    $asset,
                    $code,
                    null
                );
            }

            // flow form
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
                    'disposals/' . $asset->uuid,
                    $code . '-form'
                );
            } elseif ($request->file('flow_file')) {
                [$flowPath, $flowOrig, $flowMime, $flowSize] = $this->saveUploadDis(
                    $request->file('flow_file'),
                    $asset,
                    $code,
                    null
                );
            }

            // BA file (wajib, minimal salah satu bentuk)
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
                    'disposals/' . $asset->uuid,
                    $code . '-ba'
                );
            } elseif ($request->file('ba_file')) {
                [$baPath, $baOrig, $baMime, $baSize] = $this->saveUploadDis(
                    $request->file('ba_file'),
                    $asset,
                    $code,
                    null
                );
            } else {
                // tidak ada BA sama sekali
                abort(422, 'BA file (ba_file or ba_file_b64) is required.');
            }

            $payload = [
                'uuid'            => $uuid,
                'asset_uuid'      => $asset->uuid,
                'disposal_code'   => $code,
                'target_status'   => $target,
                'kode_status'     => 'ACC',
                'note'            => $data['note'] ?? null,

                'file_path'       => $path,
                'file_name'       => $orig,
                'file_mime'       => $mime,
                'file_size'       => $size,

                'flow_file_path'  => $flowPath,
                'flow_file_name'  => $flowOrig,
                'flow_file_mime'  => $flowMime,
                'flow_file_size'  => $flowSize,

                'ba_file_path'    => $baPath,
                'ba_file_name'    => $baOrig,
                'ba_file_mime'    => $baMime,
                'ba_file_size'    => $baSize,

                'pic_request_uid' => $uid,
                'pic_approve_uid' => $uid,
                'before_status'   => $asset->kode_status,
                'reason'          => $data['reason'],
            ];

            if (!empty($data['created_at'])) {
                $payload['created_at'] = $data['created_at'];
            }

            $row = Disposal::create($payload);

            // update asset status
            $asset->kode_status = $target;
            $asset->save();

            return [
                'ok'   => true,
                'id'   => $row->uuid,
                'code' => $row->disposal_code,
            ];
        });

        return response()->json($res, 201);
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

            $beforeLabel = $this->resolveLabel($labelType, $beforeCode);
            $afterLabel  = $this->resolveLabel($labelType, $afterCode);

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

            return [
                'uuid'              => $r->uuid,
                'asset_uuid'        => $r->asset_uuid,
                'asset_code'        => $r->asset_code,
                'asset_description' => $r->description,
                'asset_label'       => $assetLabel,

                'source_type'       => $r->source_type,
                'source_label'      => $isTransfer ? 'MOVEMENT' : 'DISPOSAL',

                'type'              => $typeCode ? strtoupper($typeCode) : null,
                'code'              => $r->code,

                'before_value'      => $beforeCode,
                'after_value'       => $afterCode,
                'before_label'      => $beforeLabel,
                'after_label'       => $afterLabel,

                'note'              => $r->note,
                'pic_request_uid'   => $r->pic_request_uid,
                'pic_approve_uid'   => $r->pic_approve_uid,

                'file'              => $fileObj,
                'form_file'         => $formFileObj,
                'ba_file'           => $baFileObj,

                // download form/BA same route name seperti di controller web
                'form_download_url' => $isTransfer
                    ? route('stockopname.transfer.download.form', $r->uuid)
                    : route('stockopname.disposal.download.form', $r->uuid),
                'ba_download_url'   => $isTransfer
                    ? null
                    : route('stockopname.disposal.download.ba', $r->uuid),

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
     * GET /api/v1/stock-opname/preview/disposal-form/{assetUuid}
     *
     * Preview Form Disposal (XLSX) berdasarkan asset saja (belum ada record).
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
            $acqDate = \Carbon\Carbon::parse($asset->value->actual_date)
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

        \Carbon\Carbon::setLocale('id');

        $hari  = $date->translatedFormat('l');
        $tglBT = $date->translatedFormat('d F Y');

        $template = storage_path('app/public/template/berita_acara_disposal_asset_tetap.docx');
        if (!file_exists($template)) {
            return abort(404, 'Template not found');
        }

        $tpl = new TemplateProcessor($template);

        $tpl->setValues([
            'HARI'                      => $hari,
            'TANGGAL_BULAN_TAHUN'       => $tglBT,
            'TANGGAL_BULAN_TAHUN_FOOTER' => $tglBT,
            'NAMA_ASET'                 => $assetName,
            'NOMOR_ASET'                => $assetCode,
            'NOMOR_FORM'                => 'PREVIEW',
            'LOKASI'                    => $locationLbl,
            'DEPARTEMEN_OWNER'          => $ownerDept,
            'KETERANGAN'                => '',
        ]);

        $fileName = 'BA_Disposal_Preview_' . ($asset->asset_code ?? $assetUuid) . '.docx';

        return response()->streamDownload(function () use ($tpl) {
            $tpl->saveAs('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
