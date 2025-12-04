<?php

namespace App\Http\Controllers;

use App\Models\AssetDeprTransferRequest;
use App\Models\Assets;
use App\Models\AssetsValueHistory;
use App\Models\Disposal;
use App\Models\MasterAssetClass;
use App\Models\MasterSumber;
use App\Models\MasterTransaction;
use App\Models\MasterCategory;
use App\Models\MasterCategory2;
use App\Models\MasterDivision;
use App\Models\MasterGroupCategory;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterSubCategory;
use App\Models\MasterUOM;
use App\Models\MasterUserCode;
use App\Models\ReturnHistory;
use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Services\AssetChildSequencer;



class TrashController extends Controller
{
    /**
     * Map of all soft-deleted resources we want to show.
     * Add your models/tables here (one place).
     */
    protected function map(): array
    {
        return [
            'master_sumber' => [
                'table'     => 'master_sumber',
                'model'     => MasterSumber::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_transaction' => [
                'table'     => 'master_transaction',
                'model'     => MasterTransaction::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_division' => [
                'table'     => 'master_division',
                'model'     => MasterDivision::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_category' => [
                'table'     => 'master_category',
                'model'     => MasterCategory::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_category_2' => [
                'table'     => 'master_category_2',
                'model'     => MasterCategory2::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_sub_category' => [
                'table'     => 'master_sub_category',
                'model'     => MasterSubCategory::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_location' => [
                'table'     => 'master_location',
                'model'     => MasterLocation::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            // 'master_group_category' => [
            //     'table'     => 'master_group_category',
            //     'model'     => MasterGroupCategory::class,
            //     'pk'        => 'uuid',
            //     'label_col' => 'name',
            // ],
            'master_uom' => [
                'table'     => 'master_uom',
                'model'     => MasterUOM::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_status' => [
                'table'     => 'master_status',
                'model'     => MasterStatus::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_asset_class' => [
                'table'     => 'master_asset_class',
                'model'     => MasterAssetClass::class,
                'pk'        => 'uuid',
                'label_col' => 'name',
            ],
            'master_user_code' => [
                'table'     => 'master_user_code',
                'model'     => MasterUserCode::class,
                'pk'        => 'uuid',
                'label_col' => 'department',
            ],
            'assets' => [
                'table'      => 'assets',
                'model'      => Assets::class,
                'pk'         => 'uuid',
                'label_col'  => 'asset_code',
            ],
            'transfer' => [
                'table'      => 'assets_transfers',
                'model'      => Transfer::class,
                'pk'         => 'uuid',
                'label_col'  => 'transfer_code',
            ],
            'disposal' => [
                'table'      => 'assets_disposals',
                'model'      => Disposal::class,
                'pk'         => 'uuid',
                'label_col'  => 'disposal_code',
            ],
            'return' => [
                'table'      => 'return_history',
                'model'      => ReturnHistory::class,
                'pk'         => 'uuid',
                'label_col'  => 'source_code',
            ],
            'depreciation transfers request' => [
                'table'      => 'assets_depr_transfer_requests',
                'model'      => AssetDeprTransferRequest::class,
                'pk'         => 'uuid',
                'label_col'  => 'transfer_code',
            ],
            'acquisition' => [
                'table'      => 'assets_value_history',
                'model'      => AssetsValueHistory::class,
                'pk'         => 'uuid',
                'label_col'  => 'acq_code',
            ],
        ];
    }

    public function index()
    {
        return view('trash.trash');
    }

    public function data(Request $request)
    {
        $map = $this->map();
        if (empty($map)) {
            return DataTables::of(collect())->make(true);
        }

        $user = $request->user();
        $canEdit   = $user && $user->hasAction('TRASH', 'U');
        $canDelete = $user && $user->hasAction('TRASH', 'D');

        $builders = [];
        foreach ($map as $type => $cfg) {
            $tbl   = $cfg['table'];
            $pk    = $cfg['pk'];
            $label = $cfg['label_col'];

            $builders[] = DB::table($tbl)
                ->selectRaw('?::text as type, ' . $pk . '::text as id, ' . $label . '::text as label, deleted_at', [$type])
                ->whereNotNull('deleted_at');
        }

        $union = array_shift($builders);
        foreach ($builders as $b) {
            $union->unionAll($b);
        }

        $query = DB::query()->fromSub($union, 't');

        return DataTables::of($query)
            ->editColumn('deleted_at', fn($r) => $r->deleted_at ? \Carbon\Carbon::parse($r->deleted_at)->format('Y-m-d H:i') : '')
            ->addColumn('actions', function ($r) use ($canEdit, $canDelete) {
                $btns = '<div class="btn-group">';
                if ($canEdit) {
                    $btns .= '<button type="button" class="btn btn-sm btn-light-success btn-restore" data-type="' . $r->type . '" data-id="' . $r->id . '">Restore</button>';
                }
                if ($canDelete) {
                    // $btns .= '<button type="button" class="btn btn-sm btn-light-danger btn-force" data-type="' . $r->type . '" data-id="' . $r->id . '">Force Delete</button>';
                }
                $btns .= '</div>';
                return $btns;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function restore(string $type, string $id)
    {
        $cfg = $this->map()[$type] ?? null;
        abort_unless($cfg, 404, 'Unknown type');

        $model = $cfg['model'];
        $pk    = $cfg['pk'];

        $row = $model::onlyTrashed()->where($pk, $id)->firstOrFail();
        $row->restore();

        return response()->json(['ok' => true, 'message' => 'Restored']);
    }

    public function force(string $type, string $id)
    {
        $cfg = $this->map()[$type] ?? null;
        abort_unless($cfg, 404, 'Unknown type');

        $model = $cfg['model'];
        $pk    = $cfg['pk'];
        $row = $model::onlyTrashed()->where($pk, $id)->firstOrFail();

        if ($type === 'assets') {
            $parent = $row->asset_number_parent;
            $row->forceDelete();

            app(AssetChildSequencer::class)->normalizeChildren($parent);

            return response()->json(['ok' => true, 'message' => 'Asset permanently deleted.']);
        }

        $row->forceDelete();
        return response()->json(['ok' => true, 'message' => 'Permanently deleted.']);
    }
}
