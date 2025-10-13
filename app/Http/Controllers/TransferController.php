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
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_uuid'   => ['required', 'uuid', 'exists:assets,uuid'],
            'type'         => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value'  => ['required', 'string'],
            'note'         => ['nullable', 'string', 'max:1000'],
        ]);

        // logged-in UID (from your LDAP session)
        $currentUid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        if ($currentUid === '') {
            abort(401, 'No session UID.');
        }

        $asset    = Assets::with(['assignment', 'status', 'location'])->findOrFail($data['asset_uuid']);
        $totalRow = Transfer::where('asset_uuid', $data['asset_uuid'])->count();

        // ---- build BEFORE / AFTER + labels ----
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

        // WAITING FOR APPROVAL (master_status.kode = APR)
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
            $totalRow
        ) {
            // keep this short enough for your column length
            $code = sprintf('TRF-%s-%d', str_replace(['-', ' '], '', $asset->asset_code), $totalRow + 1);

            return Transfer::create([
                'uuid'             => (string) Str::uuid(),
                'asset_uuid'       => $asset->uuid,
                'transfer_code'    => $code,
                'type'             => $data['type'],

                // keep the JSON payloads
                'before'           => ['value' => $beforeCode],
                'after'            => ['value' => $afterCode],

                // and store the human labels for fast listing
                'before_label'     => $beforeLabel,
                'after_label'      => $afterLabel,

                'kode_status'      => $initialWorkflow,
                'note'             => $data['note'] ?? null,
                'pic_request_uid'  => $currentUid,
                'pic_approve_uid'  => null,
            ]);
        });

        return response()->json([
            'ok'   => true,
            'id'   => $transfer->uuid,
            'code' => $transfer->transfer_code,
        ]);
    }
    public function update(Request $request, string $uuid)
    {
        $data = $request->validate([
            'asset_uuid'  => ['required', 'uuid', 'exists:assets,uuid'],
            'type'        => ['required', Rule::in(['owner', 'user', 'maintenance', 'status', 'location'])],
            'after.value' => ['required', 'string'],
            'note'        => ['nullable', 'string', 'max:1000'],
        ]);

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be edited.');

        $asset = Assets::with(['assignment'])->findOrFail($data['asset_uuid']);

        // Re-compute BEFORE from current asset state (so UI stays truthful)
        $before = $this->currentValueForType($asset, $data['type']);
        $after  = ['value' => $data['after']['value']];

        $tf->fill([
            'type'        => $data['type'],
            'before'      => $before,
            'after'       => $after,
            'note'        => $data['note'] ?? null,
        ])->save();

        return response()->json(['ok' => true]);
    }

    /** Soft delete */
    public function destroy(string $uuid)
    {
        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        $tf->delete();

        return response()->json(['ok' => true]);
    }

     public function approve(Request $request, string $uuid)
    {
        $uid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        abort_if($uid === '', 401, 'No session UID.');

        DB::transaction(function () use ($uuid, $uid) {
            $tf = Transfer::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be approved.');

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
                    $asset->kode_location = $val;
                    $asset->save();
                    break;
            }

            $tf->kode_status      = 'ACC';           // Approved
            $tf->pic_approve_uid  = $uid;
            $tf->save();
        });

        return response()->json(['ok' => true]);
    }

    /** Reject (do NOT change the asset) */
    public function reject(Request $request, string $uuid)
    {
        $uid = (string) data_get($request->session()->get('ldap_user'), 'uid', '');
        abort_if($uid === '', 401, 'No session UID.');

        $tf = Transfer::where('uuid', $uuid)->firstOrFail();
        abort_if($tf->kode_status !== 'APR', 422, 'Only pending transfers can be rejected.');

        $tf->kode_status     = 'REJ';  // Rejected
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
        $q = Transfer::query()
            ->where('asset_uuid', $assetUuid)
            ->with('status') // for workflow label
            ->orderByDesc('created_at');

        return DataTables::of($q)
            // Workflow label (APR - Waiting for Approval)
            ->addColumn('workflow_label', function (Transfer $r) {
                return $r->status ? ($r->kode_status . ' - ' . $r->status->name) : $r->kode_status;
            })
            // Raw codes from JSON (optional)
            ->addColumn('before_code', fn(Transfer $t) => data_get($t->before, 'value'))
            ->addColumn('after_code',  fn(Transfer $t) => data_get($t->after,  'value'))

            // Computed labels (KODE - Name/Department)
            ->addColumn('before_display', function (Transfer $t) {
                return $this->resolveLabel($t->type, data_get($t->before, 'value'));
            })
            ->addColumn('after_display', function (Transfer $t) {
                return $this->resolveLabel($t->type, data_get($t->after, 'value'));
            })
            ->toJson();
    }

    /** Build BEFORE/AFTER payloads depending on transfer type */
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
    private function resolveLabel(string $type, ?string $code): string
    {
        static $cache = [
            'usercode' => [], // owner/user/maintenance
            'status'   => [],
            'location' => [],
        ];

        if (!$code) return '(empty)';

        switch ($type) {
            case 'owner':
            case 'user':
            case 'maintenance':
                if (!isset($cache['usercode'][$code])) {
                    $row = MasterUserCode::select('kode', 'department')
                        ->where('kode', $code)->first();
                    $cache['usercode'][$code] = $row ? "{$code} - {$row->department}" : $code;
                }
                return $cache['usercode'][$code];

            case 'status':
                if (!isset($cache['status'][$code])) {
                    $row = MasterStatus::select('kode', 'name')
                        ->where('kode', $code)
                        ->where(function ($q) {
                            // Only Asset statuses for asset transfers
                            $q->where('type', 'Asset')->orWhereNull('type');
                        })
                        ->first();
                    $cache['status'][$code] = $row ? "{$code} - {$row->name}" : $code;
                }
                return $cache['status'][$code];

            case 'location':
                if (!isset($cache['location'][$code])) {
                    $row = MasterLocation::select('kode', 'name')
                        ->where('kode', $code)->first();
                    $cache['location'][$code] = $row ? "{$code} - {$row->name}" : $code;
                }
                return $cache['location'][$code];

            default:
                return $code;
        }
    }
    /** Helper used by update(): fetch current "before" snapshot */
    private function currentValueForType(Assets $asset, string $type): array
    {
        switch ($type) {
            case 'owner':        return ['value' => $asset->assignment?->asset_owner];
            case 'user':         return ['value' => $asset->assignment?->asset_user];
            case 'maintenance':  return ['value' => $asset->assignment?->asset_maintenance];
            case 'status':       return ['value' => $asset->kode_status];
            case 'location':     return ['value' => $asset->kode_location];
            default:             return ['value' => null];
        }
    }
}
