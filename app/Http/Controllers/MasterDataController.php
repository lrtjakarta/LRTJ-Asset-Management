<?php

namespace App\Http\Controllers;

use App\Models\MasterAssetClass;
use App\Models\MasterAssetType;
use App\Models\MasterCategory;
use App\Models\MasterCategory2;
use App\Models\MasterGroupCategory;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterSubCategory;
use App\Models\MasterSumber;
use App\Models\MasterTransaction;
use App\Models\MasterUOM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class MasterDataController
{
    // FRONTEND BLADE
    public function master_sumber()
    {
        return view('master_data.master_sumber');
    }
    public function master_transaction()
    {
        return view('master_data.master_transaction');
    }
    public function master_asset_type()
    {
        return view('master_data.master_asset_type');
    }
    public function master_category()
    {
        return view('master_data.master_category');
    }
    public function master_category_2()
    {
        return view('master_data.master_category_2');
    }
    public function master_sub_category()
    {
        return view('master_data.master_sub_category');
    }
    public function master_location()
    {
        return view('master_data.master_location');
    }
    public function master_group_category()
    {
        return view('master_data.master_group_category');
    }
    public function master_uom()
    {
        return view('master_data.master_uom');
    }
    public function master_status()
    {
        return view('master_data.master_status');
    }
    public function master_asset_class()
    {
        return view('master_data.master_asset_class');
    }

    // DATA TABLE
    public function master_sumber_data(Request $request)
    {
        $q = MasterSumber::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_transaction_data(Request $request)
    {
        $q = MasterTransaction::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_asset_type_data(Request $request)
    {
        $q = MasterAssetType::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_category_data(Request $request)
    {
        $q = MasterCategory::query()
            ->select(['uuid', 'kode', 'name', 'kode_asset_type', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('asset_type_label', function ($r) {
                static $cache = [];
                return $cache[$r->kode_asset_type]
                    ??= optional(MasterAssetType::where('kode', $r->kode_asset_type)->first())
                    ?->kode . ' - ' . optional(MasterAssetType::where('kode', $r->kode_asset_type)->first())->name
                    ?? $r->kode_asset_type;
            })
            ->addColumn('status_badge', fn($r) =>
            $r->status
                ? '<span class="badge badge-light-success">Active</span>'
                : '<span class="badge badge-light-danger">Inactive</span>')
            ->addColumn('actions', fn($r) => '
            <div class="btn-group">
              <button class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $r->uuid . '">Edit</button>
              <button class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $r->uuid . '">Delete</button>
            </div>')
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_category_2_data(Request $request)
    {
        $q = MasterCategory2::query()
            ->select(['uuid', 'kode', 'name', 'kode_category', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('category_label', function ($r) {
                static $cache = [];
                return $cache[$r->kode_category]
                    ??= optional(MasterCategory::where('kode', $r->kode_category)->first())
                    ?->kode . ' - ' . optional(MasterCategory::where('kode', $r->kode_category)->first())->name
                    ?? $r->kode_category;
            })
            ->addColumn('status_badge', fn($r) =>
            $r->status
                ? '<span class="badge badge-light-success">Active</span>'
                : '<span class="badge badge-light-danger">Inactive</span>')
            ->addColumn('actions', fn($r) => '
            <div class="btn-group">
              <button class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $r->uuid . '">Edit</button>
              <button class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $r->uuid . '">Delete</button>
            </div>')
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_sub_category_data(Request $request)
    {
        $q = MasterSubCategory::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_location_data(Request $request)
    {
        $q = MasterLocation::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_group_category_data(Request $request)
    {
        $q = MasterGroupCategory::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_uom_data(Request $request)
    {
        $q = MasterUOM::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_status_data(Request $request)
    {
        $q = MasterStatus::query()->select(['uuid', 'kode', 'name', 'type', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }
    public function master_asset_class_data(Request $request)
    {
        $q = MasterAssetClass::query()->select(['uuid', 'kode', 'name', 'status', 'updated_at']);

        return DataTables::of($q)
            ->addColumn('status_badge', function ($row) {
                return $row->status
                    ? '<span class="badge badge-light-success">Active</span>'
                    : '<span class="badge badge-light-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($row) {
                // data-uuid used by JS to open modal / delete
                return '
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-light-primary btn-edit" data-uuid="' . $row->uuid . '">Edit</button>
                  <button type="button" class="btn btn-sm btn-light-danger btn-delete" data-uuid="' . $row->uuid . '">Delete</button>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    // SAVE AND EDIT
    public function master_sumber_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_sumber', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_sumber', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterSumber::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Sumber updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterSumber::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Sumber created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_transaction_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_transaction', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_transaction', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterTransaction::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Transaction updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterTransaction::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Transaction created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_asset_type_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_asset_type', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_asset_type', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterAssetType::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Asset Type updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterAssetType::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Asset Type created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_category_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        $nameRule = Rule::unique('master_transaction', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_transaction', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'            => ['required', 'string', 'max:50',  $kodeRule],
            'name'            => ['required', 'string', 'max:191', $nameRule],
            'kode_asset_type' => ['required', 'string', 'max:50', 'exists:master_asset_type,kode'],
            'status'          => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'            => $data['kode'],
            'name'            => $data['name'],
            'kode_asset_type' => $data['kode_asset_type'],
            'status'          => (bool) $data['status'],
        ];

        if ($uuid) {
            $item = MasterCategory::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);
            return response()->json(['ok' => true, 'message' => 'Category updated.', 'uuid' => $item->uuid]);
        } else {
            $item = MasterCategory::create($payload);
            return response()->json(['ok' => true, 'message' => 'Category created.', 'uuid' => $item->uuid]);
        }
    }
    public function master_category_2_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        $nameRule = Rule::unique('master_category_2', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_category_2', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'          => ['required', 'string', 'max:50', $kodeRule],
            'name'          => ['required', 'string', 'max:191', $nameRule],
            'kode_category' => ['required', 'string', 'max:50', 'exists:master_category,kode'],
            'status'        => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'          => $data['kode'],
            'name'          => $data['name'],
            'kode_category' => $data['kode_category'],
            'status'        => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterCategory2::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Category 2 updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterCategory2::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Category 2 created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_sub_category_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_sub_category', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_sub_category', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterSubCategory::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Sub Category updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterSubCategory::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Sub Category created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_location_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_location', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_location', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterLocation::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Location updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterLocation::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Location created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_group_category_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_group_category', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_group_category', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterGroupCategory::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Group Category updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterGroupCategory::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Group Category created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_uom_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_uom', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_uom', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterUOM::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'UOM updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterUOM::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'UOM created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_status_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_status', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_status', 'kode')->whereNull('deleted_at');
        $typeRule = Rule::unique('master_status', 'type')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
            $typeRule = $typeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'type'   => ['required', 'string', 'max:191', $typeRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'type'   => $data['type'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterStatus::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Status updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterStatus::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Status created.',
                'uuid' => $item->uuid,
            ]);
        }
    }
    public function master_asset_class_save(Request $request)
    {
        $uuid = $request->string('uuid')->trim()->toString() ?: null;

        // Validation: unique name; ignore current uuid if updating
        $nameRule = Rule::unique('master_asset_class', 'name')->whereNull('deleted_at');
        $kodeRule = Rule::unique('master_asset_class', 'kode')->whereNull('deleted_at');
        if ($uuid) {
            $nameRule = $nameRule->ignore($uuid, 'uuid');
            $kodeRule = $kodeRule->ignore($uuid, 'uuid');
        }

        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', $kodeRule],
            'name'   => ['required', 'string', 'max:191', $nameRule],
            'status' => ['required', Rule::in(['0', '1', 0, 1, true, false])],
        ]);

        $payload = [
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => (bool) $data['status'],
        ];

        if ($uuid) {
            // update
            $item = MasterAssetClass::where('uuid', $uuid)->firstOrFail();
            $item->update($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Asset Class updated.',
                'uuid' => $item->uuid,
            ]);
        } else {
            // create
            $item = MasterAssetClass::create($payload);

            return response()->json([
                'ok' => true,
                'message' => 'Asset Class created.',
                'uuid' => $item->uuid,
            ]);
        }
    }

    // GET DATA TO SHOW IT IN MODAL
    public function master_sumber_show(string $uuid)
    {
        $it = MasterSumber::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_transaction_show(string $uuid)
    {
        $it = MasterTransaction::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_asset_type_show(string $uuid)
    {
        $it = MasterAssetType::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_category_show(string $uuid)
    {
        $it = MasterCategory::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'            => $it->uuid,
                'kode'            => $it->kode,
                'name'            => $it->name,
                'status'          => $it->status ? 1 : 0,
                'kode_asset_type' => $it->kode_asset_type,
            ],
        ]);
    }
    public function master_category_2_show(string $uuid)
    {
        $it = MasterCategory2::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
                'kode_category' => $it->kode_category,
            ],
        ]);
    }
    public function master_sub_category_show(string $uuid)
    {
        $it = MasterSubCategory::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_location_show(string $uuid)
    {
        $it = MasterLocation::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_group_category_show(string $uuid)
    {
        $it = MasterGroupCategory::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_uom_show(string $uuid)
    {
        $it = MasterUOM::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_status_show(string $uuid)
    {
        $it = MasterStatus::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'type'   => $it->type,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }
    public function master_asset_class_show(string $uuid)
    {
        $it = MasterAssetClass::where('uuid', $uuid)->firstOrFail();
        return response()->json([
            'ok' => true,
            'data' => [
                'uuid'   => $it->uuid,
                'kode'   => $it->kode,
                'name'   => $it->name,
                'status' => $it->status ? 1 : 0,
            ],
        ]);
    }

    // SOFT DELETE
    public function master_sumber_delete(string $uuid)
    {
        $it = MasterSumber::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Sumber deleted.',
        ]);
    }
    public function master_transaction_delete(string $uuid)
    {
        $it = MasterTransaction::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Transaction deleted.',
        ]);
    }
    public function master_asset_type_delete(string $uuid)
    {
        $it = MasterAssetType::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Asset Type deleted.',
        ]);
    }
    public function master_category_delete(string $uuid)
    {
        $it = MasterCategory::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Category deleted.',
        ]);
    }
    public function master_category_2_delete(string $uuid)
    {
        $it = MasterCategory2::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Category 2 deleted.',
        ]);
    }
    public function master_sub_category_delete(string $uuid)
    {
        $it = MasterSubCategory::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Sub Category deleted.',
        ]);
    }
    public function master_location_delete(string $uuid)
    {
        $it = MasterLocation::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Location deleted.',
        ]);
    }
    public function master_group_category_delete(string $uuid)
    {
        $it = MasterGroupCategory::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Group Category deleted.',
        ]);
    }
    public function master_uom_delete(string $uuid)
    {
        $it = MasterUOM::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'UOM deleted.',
        ]);
    }
    public function master_status_delete(string $uuid)
    {
        $it = MasterStatus::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Status deleted.',
        ]);
    }
    public function master_asset_class_delete(string $uuid)
    {
        $it = MasterAssetClass::where('uuid', $uuid)->firstOrFail();
        $it->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Asset Class deleted.',
        ]);
    }

    // AJAX CALL FOR OPTIONS
    public function select_master_asset_type(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $q = MasterAssetType::query()->select(['kode', 'name'])->where('status', true)->orderBy('kode');

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('kode', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%");
            });
        }

        $rows = $q->limit(20)->get()
            ->map(fn($r) => ['id' => $r->kode, 'text' => "{$r->kode} - {$r->name}"]);

        return response()->json(['results' => $rows]);
    }
    public function select_master_category(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $q = MasterCategory::query()->select(['kode', 'name'])->where('status', true)->orderBy('kode');

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('kode', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%");
            });
        }

        $rows = $q->limit(20)->get()
            ->map(fn($r) => ['id' => $r->kode, 'text' => "{$r->kode} - {$r->name}"]);

        return response()->json(['results' => $rows]);
    }
}
