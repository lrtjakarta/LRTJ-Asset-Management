<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferStoreRequest;
use App\Http\Requests\TransferDecisionRequest;
use App\Models\Assets;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterUserCode;
use App\Models\Transfer;
use App\Services\TransferCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TransferController extends Controller
{
    protected function canReadMovement(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAction('MOVEMENT', 'R');
    }

    /**
     * System admin should always be able to approve movement (in addition to normal MOVEMENT:APR).
     */
    protected function canApproveMovement(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Adjust to your actual sysadmin flag/method if different
        if (method_exists($user, 'isSysAdmin') && $user->isSysAdmin()) {
            return true;
        }

        if (property_exists($user, 'is_sysadmin') && $user->is_sysadmin) {
            return true;
        }

        return $user->hasAction('MOVEMENT', 'APR');
    }

    public function index()
    {
        $workflows = MasterStatus::query()
            ->where('type', 'Transfer')
            ->orderBy('kode')
            ->get(['kode', 'name']);

        return view('transfers.transfers', compact('workflows'));
    }

    public function datatable_all(Request $request)
    {
        if (!$this->canReadMovement()) {
            return DataTables::of(collect())->toJson();
        }

        $user      = $request->user();
        $canEdit   = $user && $user->hasAction('MOVEMENT', 'U');
        $canDelete = $user && $user->hasAction('MOVEMENT', 'D');
        $canApr    = $this->canApproveMovement();

        $type        = trim((string) $request->input('type', ''));
        $status      = trim((string) $request->input('status', ''));
        $requester   = trim((string) $request->input('requester', ''));
        $updatedFrom = $request->input('updated_from');
        $updatedTo   = $request->input('updated_to');
        $createdFrom = $request->input('created_from');
        $createdTo   = $request->input('created_to');
        $assetQ      = trim((string) $request->input('asset_q', ''));

        $q = Transfer::query()
            ->from('assets_transfers')
            ->leftJoin('assets as a', 'a.uuid', '=', 'assets_transfers.asset_uuid')
            ->with('status')
            ->select([
                'assets_transfers.*',
                'a.asset_code',
                'a.description as asset_desc',
            ])
            ->whereNull('assets_transfers.deleted_at')
            ->orderByDesc('assets_transfers.updated_at');

        if ($type !== '') {
            $q->where('assets_transfers.type', $type);
        }

        if ($status !== '') {
            $q->where('assets_transfers.kode_status', $status);
        }

        if ($requester !== '') {
            $q->where('assets_transfers.pic_request_uid', $requester);
        }

        if ($updatedFrom) {
            $q->whereDate('assets_transfers.updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('assets_transfers.updated_at', '<=', $updatedTo);
        }
        if ($createdFrom) {
            $q->whereDate('assets_transfers.created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $q->whereDate('assets_transfers.created_at', '<=', $createdTo);
        }
        if ($assetQ !== '') {
            $q->where(function ($w) use ($assetQ) {
                $w->where('a.asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('a.description', 'ilike', "%{$assetQ}%");
            });
        }

        return DataTables::of($q)
            ->addColumn('asset_label', function ($r) {
                $code = $r->asset_code ?? $r->asset_uuid;
                $desc = $r->asset_desc ? (' - ' . $r->asset_desc) : '';
                return $code . $desc;
            })
            ->addColumn('workflow_label', function ($r) {
                return $r->status
                    ? ($r->kode_status . ' - ' . $r->status->name)
                    : ($r->kode_status ?? '');
            })
            ->addColumn(
                'before_show',
                fn($r) => $this->resolveLabel($r->type, data_get($r->before, 'value'))
            )
            ->addColumn(
                'after_show',
                fn($r) => $this->resolveLabel($r->type, data_get($r->after, 'value'))
            )
            ->filterColumn('asset_label', function ($builder, $keyword) {
                $kw = '%' . mb_strtolower($keyword) . '%';
                $builder->where(function ($w) use ($kw) {
                    $w->whereRaw('LOWER(a.asset_code) LIKE ?', [$kw])
                        ->orWhereRaw('LOWER(a.description) LIKE ?', [$kw]);
                });
            })
            ->filterColumn('before_show', function ($builder, $keyword) {
                $kw = '%' . mb_strtolower($keyword) . '%';
                $builder->whereRaw('LOWER(CAST(assets_transfers.before AS TEXT)) LIKE ?', [$kw]);
            })
            ->filterColumn('after_show', function ($builder, $keyword) {
                $kw = '%' . mb_strtolower($keyword) . '%';
                $builder->whereRaw('LOWER(CAST(assets_transfers.after AS TEXT)) LIKE ?', [$kw]);
            })
            ->addColumn('file', function ($r) {
                if (!$r->file_path) return '';

                $url  = url('storage/' . ltrim($r->file_path, '/'));
                $name = $r->file_name ?: 'Attachment';

                return '<a class="btn btn-sm btn-light-primary" target="_blank" href="' . e($url) . '">' .
                    e($name) . '</a>';
            })
            ->addColumn('actions', function ($r) use ($canEdit, $canDelete, $canApr) {
                $pending = $r->kode_status === 'APR';
                $id      = e($r->uuid);

                $btns = '<div class="btn-group btn-group-sm">';

                if ($pending) {
                    if ($canEdit) {
                        $btns .= '<button class="btn btn-light-primary btn-tf-edit" data-id="' . $id . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-tf-approve" data-id="' . $id . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-tf-reject" data-id="' . $id . '">Reject</button>';
                    }
                }

                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-tf-delete" data-id="' . $id . '">Delete</button>';
                }

                $btns .= '</div>';
                return $btns;
            })
            ->addColumn('flow_label', function ($r) {
                if ($r->kode_status === 'REJ') {
                    $flow = is_array($r->flow) ? $r->flow : [];
                    $by   = $flow['rejected_by'] ?? $r->pic_approve_uid ?? '-';
                    return 'Rejected (by ' . e($by) . ')';
                }

                if (!is_array($r->flow)) return '';

                $steps = $r->flow['steps'] ?? [];
                if (!is_array($steps) || !count($steps)) return '';

                $nextIdx = null;
                foreach ($steps as $idx => $step) {
                    if (empty($step['approved_at'])) {
                        $nextIdx = $idx;
                        break;
                    }
                }

                if ($nextIdx === null) {
                    $last = end($steps);
                    $by   = $last['approved_by'] ?? '-';
                    return 'Completed (by ' . e($by) . ')';
                }

                $step  = $steps[$nextIdx];
                $role  = $step['role'] ?? $step['label'] ?? 'Step';
                return 'Waiting: ' . e($role);
            })

            ->rawColumns(['file', 'actions'])
            ->toJson();
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'C'), 403);

        $data = $request->validate([
            'asset_uuid'   => ['required', 'uuid', 'exists:assets,uuid'],
            'type'         => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'  => ['required', 'string'],
            'note'         => ['nullable', 'string', 'max:1000'],
            'file'         => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
        ]);

        $currentUid = auth()->user()?->name;
        abort_if(!$currentUid, 401, 'No session UID.');

        $asset    = Assets::with(['assignment', 'status', 'location'])->findOrFail($data['asset_uuid']);
        $totalRow = Transfer::where('asset_uuid', $data['asset_uuid'])->count(); // currently unused but kept

        // determine "before" code from asset
        $beforeCode = null;
        switch ($data['type']) {
            case 'owner':
                $beforeCode = $asset->assignment?->asset_owner;
                break;
            case 'user':
                $beforeCode = $asset->assignment?->asset_user;
                break;
            case 'maintenance':
                $beforeCode = $asset->assignment?->asset_maintenance;
                break;
            case 'status':
                $beforeCode = $asset->kode_status;
                break;
            case 'location':
                $beforeCode = $asset->kode_location;
                break;
        }

        $afterCode   = (string) $data['after']['value'];
        $beforeLabel = $this->resolveLabel($data['type'], $beforeCode);
        $afterLabel  = $this->resolveLabel($data['type'], $afterCode);
        $initialWorkflow = 'APR';

        $transfer = DB::transaction(function () use (
            $asset,
            $data,
            $beforeCode,
            $afterCode,
            $beforeLabel,
            $afterLabel,
            $initialWorkflow,
            $currentUid,
            $totalRow,
            $request
        ) {
            $now    = Carbon::now();
            $prefix = 'MOV' . $now->format('ym');

            $last = Transfer::where('transfer_code', 'like', $prefix . '%')
                ->orderBy('transfer_code', 'desc')
                ->first();

            if ($last) {
                $lastSeq = (int) substr($last->transfer_code, -4);
                $seq     = $lastSeq + 1;
            } else {
                $seq = 1;
            }

            $code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            [$path, $orig, $mime, $size] = $this->saveUpload($request->file('file'), $asset, $code, null);

            $payload = [
                'uuid'            => (string) Str::uuid(),
                'asset_uuid'      => $asset->uuid,
                'transfer_code'   => $code,
                'type'            => $data['type'],
                'before'          => ['value' => $beforeCode],
                'after'           => ['value' => $afterCode],
                'before_label'    => $beforeLabel,
                'after_label'     => $afterLabel,
                'kode_status'     => $initialWorkflow,
                'note'            => $data['note'] ?? null,
                'pic_request_uid' => $currentUid,
                'pic_approve_uid' => null,
                'file_path'       => $path,
                'file_name'       => $orig,
                'file_mime'       => $mime,
                'file_size'       => $size,
            ];

            // initialize flow for movement location
            if ($data['type'] === 'location') {
                $payload['flow'] = $this->buildLocationFlowTemplate($currentUid);
            }

            return Transfer::create($payload);
        });

        return response()->json([
            'ok'   => true,
            'id'   => $transfer->uuid,
            'code' => $transfer->transfer_code,
        ]);
    }

    public function show(string $id)
    {
        $transfer = Transfer::where('uuid', $id)->firstOrFail();

        $asset = Assets::select('uuid', 'asset_code', 'description')
            ->find($transfer->asset_uuid);

        $flow = $transfer->flow;

        if (is_string($flow) && $flow !== '') {
            $decoded = json_decode($flow, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $flow = $decoded;
            } else {
                $flow = null;
            }
        }

        $isLocation = strtolower((string) $transfer->type) === 'location';

        if ($isLocation) {
            $needsNewFlow =
                !is_array($flow) ||
                !isset($flow['steps']) ||
                !is_array($flow['steps']) ||
                !count($flow['steps']);

            if ($needsNewFlow) {
                $creator = $transfer->pic_request_uid ?: (auth()->user()->name ?? null);
                $flow    = $this->buildLocationFlowTemplate($creator);

                $transfer->flow = $flow;
                $transfer->save();
            }
        }

        return response()->json([
            'uuid'        => $transfer->uuid,
            'asset_uuid'  => $transfer->asset_uuid,
            'asset_code'  => $asset->asset_code ?? null,
            'asset_desc'  => $asset->description ?? null,
            'asset_label' => $asset
                ? $asset->asset_code . ($asset->description ? (' - ' . $asset->description) : '')
                : null,
            'type'        => $transfer->type,
            'before'      => $transfer->before,
            'after'       => $transfer->after,
            'note'        => $transfer->note,
            'kode_status' => $transfer->kode_status,
            'flow'        => $flow,
            'file_url'    => $transfer->file_url,
            'file_name'   => $transfer->file_name,
        ]);
    }



    public function update(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'U'), 403);

        $data = $request->validate([
            'asset_uuid'  => ['required', 'uuid', 'exists:assets,uuid'],
            'type'        => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value' => ['required', 'string'],
            'note'        => ['nullable', 'string', 'max:1000'],
            'file'        => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,gif,doc,docx,xls,xlsx,csv,txt', 'max:20480'],
        ]);

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be edited.');

        $asset = Assets::with(['assignment'])->findOrFail($data['asset_uuid']);

        $before = $this->currentValueForType($asset, $data['type']);
        $after  = ['value' => $data['after']['value']];

        if ($request->boolean('remove_file')) {
            if ($tf->file_path) Storage::disk('public')->delete($tf->file_path);
            $file_path = null;
            $file_name = null;
            $file_mime = null;
            $file_size = null;
        }

        if ($request->hasFile('file')) {
            [$path, $orig, $mime, $size] = $this->saveUpload($request->file('file'), $asset, $tf->transfer_code, $tf);
            $file_path = $path;
            $file_name = $orig;
            $file_mime = $mime;
            $file_size = $size;
        }

        $tf->fill([
            'type'       => $data['type'],
            'before'     => $before,
            'after'      => $after,
            'note'       => $data['note'] ?? null,
            'file_path'  => $file_path ?? $tf->file_path,
            'file_name'  => $file_name ?? $tf->file_name,
            'file_mime'  => $file_mime ?? $tf->file_mime,
            'file_size'  => $file_size ?? $tf->file_size,
        ])->save();

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $uuid)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'D'), 403);

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        $tf->delete();

        return response()->json(['ok' => true]);
    }

    public function approve(Request $request, string $uuid)
    {
        abort_unless($this->canApproveMovement(), 403);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        DB::transaction(function () use ($uuid, $uid) {
            /** @var \App\Models\Transfer $tf */
            $tf = Transfer::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be approved.');

            // if this is a location transfer that already has flow,
            // enforce using the multi-step endpoint instead
            if ($tf->type === 'location' && !empty($tf->flow)) {
                abort(422, 'Use location flow approval endpoint for this transfer.');
            }

            $asset = Assets::where('uuid', $tf->asset_uuid)->lockForUpdate()->firstOrFail();
            $val   = data_get($tf->after, 'value');

            switch ($tf->type) {
                case 'owner':
                    $asset->assignment()->updateOrCreate(['asset_uuid' => $asset->uuid], ['asset_owner' => $val]);
                    break;
                case 'user':
                    $asset->assignment()->updateOrCreate(['asset_uuid' => $asset->uuid], ['asset_user' => $val]);
                    break;
                case 'maintenance':
                    $asset->assignment()->updateOrCreate(['asset_uuid' => $asset->uuid], ['asset_maintenance' => $val]);
                    break;
                case 'status':
                    $asset->kode_status = $val;
                    $asset->save();
                    break;
                case 'location':
                    // simple one-step approval for legacy rows without flow
                    $asset->kode_location = $val;
                    $asset->save();
                    break;
            }

            $tf->kode_status     = 'ACC';
            $tf->pic_approve_uid = $uid;
            $tf->save();
        });

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, string $uuid)
    {
        abort_unless($this->canApproveMovement(), 403);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be rejected.');

        if (strtolower((string) $tf->type) === 'location') {
            $flow = $tf->flow ?? $this->buildLocationFlowTemplate($tf->pic_request_uid);

            $now = now()->toDateTimeString();

            if (is_array($flow) && isset($flow['steps']) && is_array($flow['steps'])) {
                $nextIdx = $this->getNextPendingFlowIndex($flow);
                if ($nextIdx !== null) {
                    $step = &$flow['steps'][$nextIdx];
                    $step['rejected_by'] = $uid;
                    $step['rejected_at'] = $now;
                }
            }

            $flow['rejected_by'] = $uid;
            $flow['rejected_at'] = $now;

            $tf->flow = $flow;
        }

        $tf->kode_status     = 'REJ';
        $tf->pic_approve_uid = $uid;
        $tf->save();

        return response()->json(['ok' => true]);
    }


    public function indexByAsset(Request $req, string $assetUuid)
    {
        $rows = Transfer::where('asset_uuid', $assetUuid)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['results' => $rows]);
    }

    public function datatable(Request $request, string $assetUuid)
    {
        if (!$this->canReadMovement()) {
            return DataTables::of(collect())->toJson();
        }

        $user      = $request->user();
        $canEdit   = $user && $user->hasAction('MOVEMENT', 'U');
        $canDelete = $user && $user->hasAction('MOVEMENT', 'D');
        $canApr    = $this->canApproveMovement();

        $q = Transfer::query()
            ->where('asset_uuid', $assetUuid)
            ->with('status')
            ->orderByDesc('created_at');

        return DataTables::of($q)
            ->addColumn('workflow_label', function (Transfer $r) {
                return $r->status ? ($r->kode_status . ' - ' . $r->status->name) : $r->kode_status;
            })
            ->addColumn('before_code', fn(Transfer $t) => data_get($t->before, 'value'))
            ->addColumn('after_code',  fn(Transfer $t) => data_get($t->after,  'value'))
            ->addColumn('before_display', function (Transfer $t) {
                return $this->resolveLabel($t->type, data_get($t->before, 'value'));
            })
            ->addColumn('after_display', function (Transfer $t) {
                return $this->resolveLabel($t->type, data_get($t->after, 'value'));
            })
            ->addColumn('file', function (Transfer $t) {
                if (!$t->file_path) return '';

                $url  = url('storage/' . ltrim($t->file_path, '/'));
                $name = $t->file_name ?: 'Attachment';

                return '<a class="btn btn-sm btn-light-primary" target="_blank" href="' . e($url) . '">' . e($name) . '</a>';
            })
            ->addColumn('actions', function ($r) use ($canEdit, $canDelete, $canApr) {
                $pending = $r->kode_status === 'APR';
                $id      = e($r->uuid);

                $btns = '<div class="btn-group btn-group-sm">';

                if ($pending) {
                    if ($canEdit) {
                        $btns .= '<button class="btn btn-light-primary btn-tf-edit" data-id="' . $id . '">Edit</button>';
                    }
                    if ($canApr) {
                        $btns .= '<button class="btn btn-light-success btn-tf-approve" data-id="' . $id . '">Accept</button>';
                        $btns .= '<button class="btn btn-light-warning btn-tf-reject" data-id="' . $id . '">Reject</button>';
                    }
                }

                if ($canDelete) {
                    $btns .= '<button class="btn btn-light-danger btn-tf-delete" data-id="' . $id . '">Delete</button>';
                }

                $btns .= '</div>';
                return $btns;
            })->addColumn('flow_label', function ($r) {
                if ($r->kode_status === 'REJ') {
                    $flow = is_array($r->flow) ? $r->flow : [];
                    $by   = $flow['rejected_by'] ?? $r->pic_approve_uid ?? '-';
                    return 'Rejected (by ' . e($by) . ')';
                }

                if (!is_array($r->flow)) return '';

                $steps = $r->flow['steps'] ?? [];
                if (!is_array($steps) || !count($steps)) return '';

                $nextIdx = null;
                foreach ($steps as $idx => $step) {
                    if (empty($step['approved_at'])) {
                        $nextIdx = $idx;
                        break;
                    }
                }

                if ($nextIdx === null) {
                    $last = end($steps);
                    $by   = $last['approved_by'] ?? '-';
                    return 'Completed (by ' . e($by) . ')';
                }

                $step  = $steps[$nextIdx];
                $role  = $step['role'] ?? $step['label'] ?? 'Step';
                return 'Waiting: ' . e($role);
            })

            ->rawColumns(['file', 'actions'])
            ->toJson();
    }

    protected function buildBeforeAfter(Assets $asset, string $type, string $afterValue): array
    {
        $before = ['value' => null, 'label' => null];
        $after  = ['value' => $afterValue, 'label' => null];

        switch ($type) {
            case 'owner':
                $before['value'] = $asset->assignment?->asset_owner;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->assignment?->owner)->department) : null;
                $uc = MasterUserCode::where('kode', $afterValue)->first();
                $after['label'] = $uc ? ($uc->kode . ' - ' . $uc->department) : $afterValue;
                break;

            case 'user':
                $before['value'] = $asset->assignment?->asset_user;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->assignment?->user)->department) : null;
                $uc = MasterUserCode::where('kode', $afterValue)->first();
                $after['label'] = $uc ? ($uc->kode . ' - ' . $uc->department) : $afterValue;
                break;

            case 'maintenance':
                $before['value'] = $asset->assignment?->asset_maintenance;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->assignment?->maintenance)->department) : null;
                $uc = MasterUserCode::where('kode', $afterValue)->first();
                $after['label'] = $uc ? ($uc->kode . ' - ' . $uc->department) : $afterValue;
                break;

            case 'status':
                $before['value'] = $asset->kode_status;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->status)->name) : null;
                $st = MasterStatus::where('kode', $afterValue)->first();
                $after['label'] = $st ? ($st->kode . ' - ' . $st->name) : $afterValue;
                break;

            case 'location':
                $before['value'] = $asset->kode_location;
                $before['label'] = $before['value'] ? ($before['value'] . ' - ' . optional($asset->location)->name) : null;
                $loc = MasterLocation::where('kode', $afterValue)->first();
                $after['label'] = $loc ? ($loc->kode . ' - ' . $loc->name) : $afterValue;
                break;
        }

        return [$before, $after];
    }

    private function resolveLabel(?string $type, ?string $code): string
    {
        $type = is_string($type) ? trim($type) : '';
        $code = is_string($code) ? trim($code) : '';

        if ($code === '') {
            return '(empty)';
        }
        if ($type === '') {
            return $code;
        }

        static $cache = [
            'usercode' => [],
            'status'   => [],
            'location' => [],
        ];
        $k  = $code;
        $ck = mb_strtoupper($code);

        switch ($type) {
            case 'owner':
            case 'user':
            case 'maintenance':
                if (!isset($cache['usercode'][$ck])) {
                    $row = MasterUserCode::select('kode', 'department')
                        ->where('kode', $code)
                        ->first();
                    $cache['usercode'][$ck] = $row ? "{$k} - {$row->department}" : $k;
                }
                return $cache['usercode'][$ck];

            case 'status':
                if (!isset($cache['status'][$ck])) {
                    $row = MasterStatus::select('kode', 'name', 'type')
                        ->where('kode', $code)
                        ->where(function ($q) {
                            $q->where('type', 'Asset')->orWhereNull('type');
                        })
                        ->first();
                    $cache['status'][$ck] = $row ? "{$k} - {$row->name}" : $k;
                }
                return $cache['status'][$ck];

            case 'location':
                if (!isset($cache['location'][$ck])) {
                    $row = MasterLocation::select('kode', 'name')
                        ->where('kode', $code)
                        ->first();
                    $cache['location'][$ck] = $row ? "{$k} - {$row->name}" : $k;
                }
                return $cache['location'][$ck];

            default:
                return $k;
        }
    }

    private function currentValueForType(Assets $asset, string $type): array
    {
        switch ($type) {
            case 'owner':
                return ['value' => $asset->assignment?->asset_owner];
            case 'user':
                return ['value' => $asset->assignment?->asset_user];
            case 'maintenance':
                return ['value' => $asset->assignment?->asset_maintenance];
            case 'status':
                return ['value' => $asset->kode_status];
            case 'location':
                return ['value' => $asset->kode_location];
            default:
                return ['value' => null];
        }
    }

    private function saveUpload(?UploadedFile $file, Assets $asset, string $code, ?Transfer $existing = null): array
    {
        if (!$file) return [null, null, null, null];

        $disk = 'public';
        $dir  = 'transfers/' . $asset->uuid;
        $ext  = $file->getClientOriginalExtension();
        $name = $code . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        $path = $file->storeAs($dir, $name, $disk);

        // clean old file if editing
        if ($existing && $existing->file_path) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        return [$path, $file->getClientOriginalName(), $file->getClientMimeType(), $file->getSize()];
    }

    protected function buildLocationFlowTemplate(?string $creatorName = null): array
    {
        $now = now()->toDateTimeString();

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

    public function approveLocationStep(Request $request, string $uuid)
    {
        abort_unless($this->canApproveMovement(), 403);

        $uid = auth()->user()?->name;
        abort_if(!$uid, 401, 'No session UID.');

        $now = now()->toDateTimeString();

        return DB::transaction(function () use ($uuid, $uid, $now) {
            /** @var \App\Models\Transfer $tf */
            $tf = Transfer::where('uuid', $uuid)->lockForUpdate()->firstOrFail();

            abort_if($tf->type !== 'location', 422, 'Flow approval only supported for location transfers.');
            abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be approved.');

            $flow = $tf->flow ?? $this->buildLocationFlowTemplate($tf->pic_request_uid);

            $nextIdx = $this->getNextPendingFlowIndex($flow);
            abort_if($nextIdx === null, 422, 'All steps already approved.');

            $step = &$flow['steps'][$nextIdx];
            $step['approved_by'] = $uid;
            $step['approved_at'] = $now;

            $isLastStep = ($nextIdx === count($flow['steps']) - 1);

            if ($isLastStep) {
                $asset = Assets::where('uuid', $tf->asset_uuid)->lockForUpdate()->firstOrFail();
                $val   = data_get($tf->after, 'value');

                $asset->kode_location = $val;
                $asset->save();

                $tf->kode_status     = 'ACC';
                $tf->pic_approve_uid = $uid;
            }

            $tf->flow = $flow;
            $tf->save();

            return response()->json([
                'ok'          => true,
                'completed'   => $isLastStep,
                'kode_status' => $tf->kode_status,
                'flow'        => $flow,
            ]);
        });
    }
}
