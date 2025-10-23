<?php

namespace App\Http\Controllers;

use App\Actions\NextChildNumber;
use App\Actions\BuildAssetCode;
use App\Actions\BuildGroupCategoryCode;
use App\Http\Requests\AssetStoreRequest;
use App\Http\Requests\AssetUpdateRequest;
use App\Models\{
    Assets,
    AssetsIdentifier,
    AssetsClassification,
    AssetsAssignment,
    AssetsValue,
    AssetsDocument,
    AssetsQr,
    AssetsRfid,
    MasterLocation,
    MasterStatus,
    MasterUserCode
};
use App\Services\AssetChildSequencer;
use App\Services\AssetNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssetsController extends Controller
{
    /** Page */
    public function index()
    {
        return view('assets.assets');
    }
    public function create()
    {
        return view('assets.assets_create');
    }
    public function detail(string $uuid)
    {
        $asset = Assets::with([
            'classification.transaction',
            'classification.assetType',
            'classification.category',
            'classification.category2',
            'classification.subCategory',
            'identifiers',
            'assignment.owner',
            'assignment.user',
            'assignment.maintenance',
            'value.uom',
            'documents',
            'assetClass',
            'status',
            'location',
            'sumber',
            'activeQr',
            'activeRfid',
        ])->findOrFail($uuid);

        return view('assets.assets_detail', compact('asset'));
    }
    public function edit(string $uuid)
    {
        $asset = Assets::with([
            'classification',
            'identifiers',
            'assignment',
            'value',
            'documents',
        ])->findOrFail($uuid);
        $parentUuid = null;
        if ($asset->asset_number_child !== '00') {
            $parentUuid = Assets::where('asset_number_parent', $asset->asset_number_parent)
                ->where('asset_number_child', '00')
                ->value('uuid');
        }
        return view('assets.assets_edit', [
            'asset' => $asset,
            'parentUuid' => $parentUuid,
        ]);
    }
    public function datatable(Request $request)
    {
        $q = DB::table('assets as a')
            // child tables
            ->leftJoin('assets_identifiers as i', 'i.asset_uuid', 'a.uuid')
            ->leftJoin('assets_assignment  as g', 'g.asset_uuid', 'a.uuid')
            ->leftJoin('assets_value       as v', 'v.asset_uuid', 'a.uuid')
            ->leftJoin('assets_document    as d', 'd.asset_uuid', 'a.uuid')
            // master lookups (names)
            ->leftJoin('master_location     as ml',   'ml.kode',    'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',   'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',    'a.kode_status')
            ->leftJoin('master_uom          as mu',   'mu.kode',    'v.kode_uom')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode',  'a.kode_sumber')
            ->leftJoin('master_user_code    as ou',   'ou.kode',    'g.asset_owner')
            ->leftJoin('master_user_code    as uu',   'uu.kode',    'g.asset_user')
            ->leftJoin('master_user_code    as muw',  'muw.kode',   'g.asset_maintenance')
            ->where('a.deleted_at', null)
            ->select(
                'a.uuid',
                'a.asset_code',
                'a.kode_group_category',
                'a.asset_number_parent',
                'a.asset_number_child',
                'a.description',
                'i.asset_number_maximo',
                'i.asset_number_dynamic_365',
                'i.asset_number_internal',
                'i.alias',
                'g.asset_owner',
                'g.asset_user',
                'g.asset_maintenance',
                'a.kode_location',
                'a.kode_asset_class',
                'a.kode_status',
                'v.price',
                'v.quantity',
                'v.vat_in',
                'v.kode_uom',
                'v.total',
                'v.useful_life_month',
                'v.useful_life_year',
                'd.no_po_perjanjian_spk',
                'd.nota_referensi',
                'd.no_document',
                'a.kode_sumber',
                DB::raw("
                    CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS kode_location_label,
                    CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS kode_asset_class_label,
                    CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS kode_status_label,
                    CASE WHEN mu.name  IS NULL THEN v.kode_uom         ELSE v.kode_uom         || ' - ' || mu.name  END AS kode_uom_label,
                    CASE WHEN msrc.name IS NULL THEN a.kode_sumber     ELSE a.kode_sumber      || ' - ' || msrc.name END AS kode_sumber_label,
                    
                    CASE WHEN ou.department  IS NULL THEN g.asset_owner      ELSE g.asset_owner         || ' - ' || ou.department  END AS asset_owner_label,
                    CASE WHEN uu.department  IS NULL THEN g.asset_user       ELSE g.asset_user          || ' - ' || uu.department  END AS asset_user_label,
                    CASE WHEN muw.department IS NULL THEN g.asset_maintenance ELSE g.asset_maintenance  || ' - ' || muw.department END AS asset_maintenance_label,
                    to_char(a.updated_at at time zone 'Asia/Jakarta','YYYY-MM-DD HH24:MI') as updated_at
                ")
            );

        return DataTables::of($q)->make(true);
    }
    private function generate_qr(string $uuid, string $asset_code)
    {
        $size   = 300;
        $margin = 0;
        $svgQr  = QrCode::format('svg')
            ->size($size)
            ->margin($margin)
            ->errorCorrection('M')
            ->generate($uuid);
        $label     = $asset_code;
        $fontSize  = 16;
        $pad       = 10;
        $textBox   = $fontSize + 8;
        $totalH    = $size + $pad + $textBox + $pad;
        $labelEsc  = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $textY    = $size + $pad + $fontSize;

        if (preg_match('/<svg[^>]*>(.*)<\/svg>/is', $svgQr, $m)) {
            $qrInner = $m[1];
        } else {
            $qrInner = $svgQr;
        }
        $labeledSvg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$totalH}" viewBox="0 0 {$size} {$totalH}">
            <g id="qr" transform="translate(0,0)">
                {$qrInner}
            </g>
            <text x="50%" y="{$textY}" text-anchor="middle" font-family="sans-serif" font-size="{$fontSize}">
                {$labelEsc}
            </text>
            </svg>
            SVG;

        return $labeledSvg;
    }

    public function store(
        AssetStoreRequest $request,
        BuildGroupCategoryCode $groupBuilder,
        AssetNumberingService $num
    ) {
        $v = (object) $request->validated();
        $mode = $v->mode;
        $uuid   = (string) Str::uuid();
        $vatRate = (float) env('NILAI_PAJAK', 10);

        if ($mode === 'existing') {
            $parent_get = Assets::query()
                ->where('uuid', $v->parent_uuid)
                ->firstOrFail();
            abort_if($parent_get->asset_number_child !== '00', 422, 'Selected asset is not a parent.');

            // Generate numbers
            $group  = $parent_get->kode_group_category;
            $parent = $parent_get->asset_number_parent;
            $child  = $num->nextChild($parent);
            $code   = $parent . '-' . $child;
        } else {
            // $group  = $groupBuilder->handle(
            //     $v->kode_asset_transaction,
            //     $v->kode_asset_type,
            //     $v->kode_category,
            //     $v->kode_category_2,
            //     $v->kode_sub_category
            // );
            $group  = $groupBuilder->handle2(
                $v->kode_asset_transaction,
                $v->kode_asset_class
            );

            // Generate numbers
            $parent = $num->nextParent($group);
            $child  = $num->nextChild($parent);
            $code   = $parent . '-' . $child;
        }

        $asset = DB::transaction(function () use ($v, $group, $parent, $child, $code, $uuid, $vatRate) {
            $asset = Assets::create([
                'uuid'                => $uuid,
                'kode_group_category' => $group,
                'asset_number_parent' => $parent,
                'asset_number_child'  => $child,
                'asset_code'          => $code,
                'description'         => $v->description,
                'kode_asset_class'    => $v->kode_asset_class,
                'kode_status'         => $v->kode_status,
                'kode_location'       => $v->kode_location,
                'kode_sumber'         => $v->kode_sumber,
            ]);

            AssetsClassification::create([
                'asset_uuid'             => $asset->uuid,
                'kode_asset_transaction' => $v->kode_asset_transaction,
                'kode_asset_type'        => $v->kode_asset_type ?? null,
                'kode_category'          => $v->kode_category ?? null,
                'kode_category_2'        => $v->kode_category_2 ?? null,
                'kode_sub_category'      => $v->kode_sub_category ?? null,
            ]);

            AssetsDocument::create([
                'asset_uuid'            => $asset->uuid,
                'no_po_perjanjian_spk'  => $v->no_po_perjanjian_spk,
                'nota_referensi'        => $v->nota_referensi,
                'no_document'           => $v->no_document,
            ]);

            AssetsIdentifier::create([
                'asset_uuid'                => $asset->uuid,
                'asset_number_maximo'       => $v->asset_number_maximo,
                'asset_number_dynamic_365'  => $v->asset_number_dynamic_365,
                'asset_number_internal'     => $v->asset_number_internal,
                'alias'                     => $v->alias,
            ]);

            AssetsAssignment::create([
                'asset_uuid'        => $asset->uuid,
                'asset_owner'       => $v->asset_owner,
                'asset_user'        => $v->asset_user,
                'asset_maintenance' => $v->asset_maintenance,
            ]);

            $vatIn = $v->is_pajak ? round($v->price * ($vatRate / 100), 2) : 0.00;
            AssetsValue::create([
                'asset_uuid'        => $asset->uuid,
                'price'             => $v->price,
                'quantity'          => $v->quantity,
                'is_pajak'          => $v->is_pajak,
                'vat_in'            => $vatIn,
                'kode_uom'          => $v->kode_uom,
                'total'             => round(($v->price * $v->quantity) + $vatIn, 2),
                'useful_life_month' => $v->useful_life_month,
                'useful_life_year'  => round($v->useful_life_month / 12, 2),
            ]);

            // QR            
            $qrRelativePath = "qrcodes/{$uuid}.svg";
            AssetsQr::create([
                'asset_uuid'  => $uuid,
                'qr_data'     => $uuid,
                'image_path'  => $qrRelativePath,
                'is_active'   => true,
                'generated_at' => Carbon::now(),
            ]);

            $labeledSvg = $this->generate_qr($uuid, $code . ' (' . $v->description . ') ');
            if (! Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }
            Storage::disk('public')->put($qrRelativePath, $labeledSvg);

            return $asset;
        });



        return redirect()->route('assets.index')->with('success', 'Asset has been created.');
    }

    public function update(
        AssetUpdateRequest $request,
        Assets $asset,
        BuildGroupCategoryCode $groupBuilder,
        AssetNumberingService $num,
        AssetChildSequencer $sequencer,
        string $uuid
    ) {
        $v = (object) $request->validated();
        $asset = Assets::with(['classification'])->findOrFail($uuid);

        $vatRate = (float) env('NILAI_PAJAK', 10);
        // $oldParent = $asset->asset_number_parent;
        // $oldGroup  = $asset->kode_group_category;

        // if ($v->mode === 'existing') {
        //     $parent = Assets::where('uuid', $v->parent_uuid)->firstOrFail();
        //     abort_if($parent->asset_number_child !== '00', 422, 'Selected asset is not a parent.');
        //     $newGroup  = $parent->kode_group_category;
        //     $newParent = $parent->asset_number_parent;
        // } else {
        //     $newGroup  = $groupBuilder->handle(
        //         $v->kode_asset_transaction,
        //         $v->kode_asset_type,
        //         $v->kode_category,
        //         $v->kode_category_2,
        //         $v->kode_sub_category
        //     );
        //     $newParent = null;
        // }

        // $recode = ($newGroup !== $oldGroup) || ($v->mode === 'existing' && $newParent !== $oldParent);
        DB::transaction(function () use ($v, $asset, $num, $sequencer, $vatRate) {

            // if ($recode) {
            //     if ($oldParent) {
            //         $num->rollbackChildIfLatest($oldParent, $asset->asset_number_child, $asset->uuid);
            //     }
            // if ($v->mode === 'existing') {
            //     $targetParent = $newParent;
            //     $nextChild    = $num->nextChild($targetParent);
            // } else {
            //     $targetParent = $num->nextParent($newGroup);
            //     $nextChild    = $num->nextChild($targetParent);
            // }

            // $asset->fill([
            // 'description'         => $v->description,
            // 'kode_group_category' => $newGroup,
            // 'asset_number_parent' => $targetParent,
            // 'asset_number_child'  => $nextChild,
            // 'asset_code'          => $targetParent . '-' . $nextChild,
            // ])->save();

            //     $sequencer->normalizeChildren($targetParent);
            //     if ($oldParent && $oldParent !== $targetParent) {
            //         $sequencer->normalizeChildren($oldParent);
            //     }
            // }

            $asset->fill([
                'description'      => $v->description,
                // 'kode_asset_class' => $v->kode_asset_class,
                // 'kode_status'      => $v->kode_status,
                'kode_location'    => $v->kode_location,
                'kode_sumber'      => $v->kode_sumber,
            ])->save();

            // $asset->classification()->updateOrCreate(
            //     ['asset_uuid' => $asset->uuid],
            //     [
            //         'kode_asset_transaction' => $v->kode_asset_transaction,
            //         'kode_asset_type'        => $v->kode_asset_type,
            //         'kode_category'          => $v->kode_category,
            //         'kode_category_2'        => $v->kode_category_2,
            //         'kode_sub_category'      => $v->kode_sub_category,
            //     ]
            // );

            $asset->documents()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'no_po_perjanjian_spk' => $v->no_po_perjanjian_spk,
                    'nota_referensi'       => $v->nota_referensi,
                    'no_document'          => $v->no_document,
                ]
            );

            $asset->identifiers()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'asset_number_maximo'      => $v->asset_number_maximo,
                    'asset_number_dynamic_365' => $v->asset_number_dynamic_365,
                    'asset_number_internal'    => $v->asset_number_internal,
                    'alias'                    => $v->alias,
                ]
            );

            $asset->assignment()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'asset_owner'       => $v->asset_owner,
                    'asset_user'        => $v->asset_user,
                    'asset_maintenance' => $v->asset_maintenance,
                ]
            );

            $vatIn = $v->is_pajak ? round($v->price * ($vatRate / 100), 2) : 0.00;
            $asset->value()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'price'             => $v->price,
                    'quantity'          => $v->quantity,
                    'is_pajak'          => $v->is_pajak,
                    'vat_in'            => $vatIn,
                    'kode_uom'          => $v->kode_uom,
                    'total'             => round(($v->price * $v->quantity) + $vatIn, 2),
                    'useful_life_month' => $v->useful_life_month,
                    'useful_life_year'  => round($v->useful_life_month / 12, 2),
                ]
            );
            $qrRelativePath = "qrcodes/{$asset->uuid}.svg";
            $label          = $asset->asset_code . ' (' . $v->description . ') ';
            $svg            = $this->generate_qr($asset->uuid, $label);
            if (!Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }
            Storage::disk('public')->put($qrRelativePath, $svg);
            $asset->qrs()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                ['qr_data' => $asset->uuid, 'image_path' => $qrRelativePath, 'is_active' => true, 'generated_at' => now()]
            );
        });

        return redirect()->route('assets.detail', $asset->uuid)->with('success', 'Asset updated.');
    }


    public function destroy(string $uuid)
    {
        $it = Assets::where('uuid', $uuid)->firstOrFail();
        $it->delete();
        return redirect()->route('assets.index')->with('success', 'Asset Deleted.');
    }
    public function select_asset_parent(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $builder = Assets::query()
            ->where('asset_number_child', '00')
            ->whereNull('deleted_at')
            ->orderBy('asset_code');

        if ($q !== '') {
            $builder->where(function ($w) use ($q) {
                $ilike = '%' . Str::of($q)->lower() . '%';
                $w->whereRaw('LOWER(asset_code) LIKE ?', [$ilike])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$ilike]);
            });
        }

        $total = (clone $builder)->count();
        $rows  = $builder->forPage($page, $perPage)->get(['uuid', 'asset_code', 'description']);

        $results = $rows->map(fn($r) => [
            'id'   => $r->uuid,
            'text' => "{$r->asset_code} - {$r->description}",
        ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }
    public function asset_parent_meta(string $uuid)
    {
        // $row = Assets::where('uuid', $uuid)->where('asset_number_child', '00')->firstOrFail();
        $asset = Assets::findOrFail($uuid);
        $asset->load('classification');

        abort_unless($asset->asset_number_child === '00', 422, 'Selected asset is not a parent.');

        $c = $asset->classification;
        return response()->json([
            'id'    => $asset->uuid,
            'label' => "{$asset->asset_code} - {$asset->description}",
            'kode_group_category'     => $asset->kode_group_category,
            'asset_number_parent'     => $asset->asset_number_parent,
            'classification' => [
                'kode_asset_transaction' => $c?->kode_asset_transaction,
                'kode_asset_type'        => $c?->kode_asset_type,
                'kode_category'          => $c?->kode_category,
                'kode_category_2'        => $c?->kode_category_2,
                'kode_sub_category'      => $c?->kode_sub_category,
                'kode_asset_class'       => $asset->kode_asset_class,
            ]
        ]);
    }

    public function select_assets(Request $request)
    {
        $q = trim($request->string('q'));
        $page = max(1, (int) $request->input('page', 1));
        $per  = 10;

        $rows = Assets::query()
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('asset_code', 'ilike', "%{$q}%")
                        ->orWhere('description', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('asset_code')
            ->paginate($per, ['*'], 'page', $page);

        $results = $rows->getCollection()->map(function ($a) {
            return [
                'id'   => $a->uuid,
                'text' => $a->asset_code . ' - ' . ($a->description ?? ''),
            ];
        });

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => $rows->hasMorePages()],
        ]);
    }
    public function brief(string $uuid)
    {
        $a = Assets::query()
            ->select('uuid', 'asset_code', 'description', 'kode_status', 'kode_location')
            ->with(['assignment' => function ($q) {
                $q->select('asset_uuid', 'asset_owner', 'asset_user', 'asset_maintenance');
            }])
            ->findOrFail($uuid);

        $ownerCode = $a->assignment?->asset_owner;
        $userCode  = $a->assignment?->asset_user;
        $mntCode   = $a->assignment?->asset_maintenance;
        $stCode    = $a->kode_status;
        $locCode   = $a->kode_location;

        $ucodes = collect([$ownerCode, $userCode, $mntCode])
            ->filter()
            ->unique()
            ->values();

        $deptByKode = $ucodes->isEmpty()
            ? collect()
            : MasterUserCode::query()
            ->whereIn('kode', $ucodes)
            ->pluck('department', 'kode');

        $statusName = $stCode
            ? MasterStatus::query()
            ->where('kode', $stCode)
            ->where(function ($q) {
                $q->where('type', 'Asset')->orWhereNull('type');
            })
            ->value('name')
            : null;

        // Resolve location name
        $locName = $locCode
            ? MasterLocation::query()
            ->where('kode', $locCode)
            ->value('name')
            : null;

        $ownerLabel = $ownerCode ? ($ownerCode . ' - ' . ($deptByKode[$ownerCode] ?? '')) : '(empty)';
        $userLabel  = $userCode  ? ($userCode . ' - ' . ($deptByKode[$userCode]  ?? '')) : '(empty)';
        $mntLabel   = $mntCode   ? ($mntCode  . ' - ' . ($deptByKode[$mntCode]   ?? '')) : '(empty)';
        $statusLabel   = $stCode ? ($stCode   . ' - ' . ($statusName ?? ''))        : '(empty)';
        $locationLabel = $locCode ? ($locCode . ' - ' . ($locName ?? ''))           : '(empty)';

        return response()->json([
            'asset_uuid'       => $a->uuid,
            'asset_label'      => trim($a->asset_code . ' ' . ($a->description ? ('- ' . $a->description) : '')),

            'owner_code'       => $ownerCode,
            'owner_label'      => $ownerLabel,

            'user_code'        => $userCode,
            'user_label'       => $userLabel,

            'maintenance_code' => $mntCode,
            'maintenance_label' => $mntLabel,

            'status_code'      => $stCode,
            'status_label'     => $statusLabel,

            'location_code'    => $locCode,
            'location_label'   => $locationLabel,
        ]);
    }
    public function bulk_upload()
    {
        return view('assets.bulk_upload');
    }
    public function download_template()
    {
        $relativePath = 'template/Template Asset Bulk.xlsx';

        if (!Storage::disk('public')->exists($relativePath)) {
            abort(404, 'Template file not found at storage/app/public/' . $relativePath);
        }

        return Storage::disk('public')->download(
            $relativePath,
            'Asset_Bulk_Template.xlsx'
        );
    }
    public function upload_excel(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $uploaded     = $request->file('file');
        $spreadsheet  = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploaded->getPathname());
        $sheet        = $spreadsheet->getActiveSheet();

        $normalize = function ($v) {
            $v = trim((string)$v);
            $v = mb_strtolower($v);
            $v = preg_replace('/[^a-z0-9]+/u', '_', $v);
            return trim($v, '_');
        };

        $resolveUom = function ($raw) {
            if ($raw === null) return null;
            $txt = trim((string)$raw);
            if ($txt === '' || $txt === '0' || $txt === '0.0') return null;

            $row = DB::table('master_uom')
                ->select('kode')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($txt) {
                    $q->where('kode', $txt)
                        ->orWhere('name', 'ilike', $txt)
                        ->orWhere('kode', strtoupper($txt))
                        ->orWhere('name', 'ilike', '%' . $txt . '%');
                })
                ->first();

            return $row ? $row->kode : null;
        };

        $toBool = function ($v) {
            $s = strtolower(trim((string)$v));
            return in_array($s, ['1', 'y', 'yes', 'true', 'ya'], true);
        };

        $getVatRate = function () {
            $raw = env('NILAI_PAJAK', 10);
            $s   = is_string($raw) ? trim($raw) : (string)$raw;
            if (strpos($s, '%') !== false) {
                $n = (float)str_replace('%', '', $s);
                return $n / 100.0;
            }
            $n = (float)$raw;
            return $n > 1 ? $n / 100.0 : $n;
        };

        $headers = [];
        $firstRow = $sheet->getRowIterator(1, 1)->current();
        $cellIter = $firstRow->getCellIterator();
        $cellIter->setIterateOnlyExistingCells(false);
        foreach ($cellIter as $col => $cell) {
            $headers[$col] = $normalize($cell->getValue());
        }

        $KNOWN = [
            'asset_code'               => 'asset.asset_code',
            'asset_number_parent'      => 'asset.asset_number_parent',
            'asset_number_child'       => 'asset.asset_number_child',
            'kode_group_category'      => 'asset.kode_group_category',
            'asset_description'        => 'asset.description',
            'asset_status'             => 'asset.kode_status',
            'location'                 => 'asset.kode_location',
            'asset_class'              => 'asset.kode_asset_class',
            'sumber'                   => 'asset.kode_sumber',

            'asset_number_maximo'      => 'identifiers.asset_number_maximo',
            'asset_number_erp_365'     => 'identifiers.asset_number_dynamic_365',
            'asset_number_internal'    => 'identifiers.asset_number_internal',
            'asset_alias'              => 'identifiers.alias',

            'company'                  => 'classification.kode_asset_transaction',

            'owner'                    => 'assignment.asset_owner',
            'user'                     => 'assignment.asset_user',
            'maintenance'              => 'assignment.asset_maintenance',

            'acquisition'              => 'value.total',
            'total'                    => 'value.total',
            'uom'                      => 'value.kode_uom',
            'qty'                      => 'value.quantity',
            'prices'                   => 'value.price',
            'price'                    => 'value.price',
            'pajak'                    => 'value.is_pajak',
            'is_pajak'                 => 'value.is_pajak',
            'useful_life_month'        => 'value.useful_life_month',

            'no_po_perjanjian_spk'     => 'documents.no_po_perjanjian_spk',
            'nota_referensi'           => 'documents.nota_referensi',
            'no_document'              => 'documents.no_document',
        ];

        $colTargets = [];
        foreach ($headers as $col => $head) {
            if (!isset($KNOWN[$head])) continue;
            [$group, $field] = explode('.', $KNOWN[$head], 2);
            $colTargets[$col] = ['group' => $group, 'field' => $field, 'head' => $head];
        }

        $hasAssignments    = Schema::hasTable('assets_assignment');
        $tableValues       = Schema::hasTable('assets_values')
            ? 'assets_values'
            : (Schema::hasTable('assets_value') ? 'assets_value' : null);
        $tableIdentifiers  = Schema::hasTable('assets_identifiers')     ? 'assets_identifiers'     : null;
        $tableClassif      = Schema::hasTable('assets_classification') ? 'assets_classification' : null;
        $tableDocuments    = Schema::hasTable('assets_document')       ? 'assets_document'       : null;

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($sheet->getRowIterator(2) as $row) {
                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(false);

                $assetPayload          = [];
                $assignmentPayload     = [];
                $valuePayload          = [];
                $identifiersPayload    = [];
                $classificationPayload = [];
                $documentsPayload      = [];

                $isPajakFlag = null;

                foreach ($cells as $col => $cell) {
                    if (!isset($colTargets[$col])) continue;

                    $target = $colTargets[$col];
                    $raw    = $cell->getCalculatedValue();
                    if (is_string($raw)) $raw = trim($raw);
                    if ($raw === '') $raw = null;

                    switch ($target['group']) {
                        case 'asset':
                            if ($raw !== null) $assetPayload[$target['field']] = $raw;
                            break;

                        case 'assignment':
                            if ($raw !== null) $assignmentPayload[$target['field']] = $raw;
                            break;

                        case 'value':
                            switch ($target['field']) {
                                case 'kode_uom':
                                    if ($raw !== null) $valuePayload['kode_uom'] = (string)$raw;
                                    break;
                                case 'quantity':
                                case 'price':
                                case 'total':
                                    if ($raw !== null) {
                                        $valuePayload[$target['field']] =
                                            is_numeric($raw) ? (float)$raw : (float)preg_replace('/[^\d.-]/', '', (string)$raw);
                                    }
                                    break;
                                case 'is_pajak':
                                    if ($raw !== null) $isPajakFlag = $toBool($raw);
                                    break;
                                case 'useful_life_month':
                                    if ($raw !== null) $valuePayload['useful_life_month'] = (int)round((float)$raw);
                                    break;
                            }
                            break;

                        case 'identifiers':
                            if ($raw !== null) $identifiersPayload[$target['field']] = $raw;
                            break;

                        case 'classification':
                            if ($raw !== null) $classificationPayload[$target['field']] = $raw;
                            break;

                        case 'documents':
                            if ($raw !== null) $documentsPayload[$target['field']] = $raw;
                            break;
                    }
                }

                $parent = $assetPayload['asset_number_parent'] ?? null;
                $child  = $assetPayload['asset_number_child']  ?? null;
                if ($parent !== null) $parent = (string)$parent;
                if ($child  !== null) $child  = (string)$child;

                $code = $assetPayload['asset_code'] ?? null;
                if ($parent && $child) $code = $parent . '-' . $child;
                if (!$code) {
                    $skipped++;
                    continue;
                }
                $assetPayload['asset_code'] = $code;

                if (empty($assetPayload['kode_group_category'])) {
                    $assetPayload['kode_group_category'] = $parent ? substr($parent, 0, 5) : substr($code, 0, 5);
                }
                if (empty($assetPayload['kode_sumber'])) {
                    $assetPayload['kode_sumber'] = 'KD-1';
                }

                $asset = Assets::where('asset_code', $code)->first();
                $isNew = false;
                if (!$asset) {
                    $asset = new Assets();
                    $asset->uuid = (string) Str::uuid();
                    $asset->asset_code = $code;
                    $isNew = true;
                }
                foreach (
                    [
                        'description',
                        'kode_status',
                        'kode_location',
                        'kode_asset_class',
                        'asset_number_parent',
                        'asset_number_child',
                        'kode_group_category',
                        'kode_sumber',
                    ] as $f
                ) {
                    if (array_key_exists($f, $assetPayload)) $asset->{$f} = $assetPayload[$f];
                }
                $asset->save();
                $isNew ? $inserted++ : $updated++;

                if ($hasAssignments && !empty($assignmentPayload)) {
                    $exists = DB::table('assets_assignment')->where('asset_uuid', $asset->uuid)->first();
                    $assignmentPayload['asset_uuid'] = $asset->uuid;
                    $assignmentPayload['updated_at'] = now();
                    if ($exists) {
                        DB::table('assets_assignment')->where('asset_uuid', $asset->uuid)->update($assignmentPayload);
                    } else {
                        $assignmentPayload['created_at'] = now();
                        DB::table('assets_assignment')->insert($assignmentPayload);
                    }
                }

                if ($tableValues && (!empty($valuePayload) || $isPajakFlag !== null)) {
                    if (array_key_exists('kode_uom', $valuePayload)) {
                        $kode = $resolveUom($valuePayload['kode_uom']);
                        if ($kode) {
                            $valuePayload['kode_uom'] = $kode;
                        } else {
                            unset($valuePayload['kode_uom']);
                        }
                    }

                    $qty      = (float)($valuePayload['quantity'] ?? 0);
                    $price    = (float)($valuePayload['price'] ?? 0);
                    $subtotal = $qty * $price;

                    if ($isPajakFlag !== null) {
                        $valuePayload['is_pajak'] = $isPajakFlag ? 1 : 0;
                        $rate = $getVatRate();
                        $valuePayload['vat_in'] = $isPajakFlag ? round($subtotal * $rate, 2) : 0.0;
                    }

                    if (!array_key_exists('total', $valuePayload)) {
                        $vat = (float)($valuePayload['vat_in'] ?? 0);
                        $valuePayload['total'] = round($subtotal + $vat, 2);
                    }
                    if (isset($valuePayload['useful_life_month']) && $valuePayload['useful_life_month'] !== null) {
                        $months = (float)$valuePayload['useful_life_month'];
                        $valuePayload['useful_life_year'] = round($months / 12, 2);
                    }

                    $existsV = DB::table($tableValues)->where('asset_uuid', $asset->uuid)->first();
                    $valuePayload['asset_uuid'] = $asset->uuid;
                    $valuePayload['updated_at'] = now();
                    if ($existsV) {
                        DB::table($tableValues)->where('asset_uuid', $asset->uuid)->update($valuePayload);
                    } else {
                        $valuePayload['created_at'] = now();
                        DB::table($tableValues)->insert($valuePayload);
                    }
                }

                if ($tableIdentifiers && !empty($identifiersPayload)) {
                    $existsI = DB::table($tableIdentifiers)->where('asset_uuid', $asset->uuid)->first();
                    $identifiersPayload['asset_uuid'] = $asset->uuid;
                    $identifiersPayload['updated_at'] = now();
                    if ($existsI) {
                        DB::table($tableIdentifiers)->where('asset_uuid', $asset->uuid)->update($identifiersPayload);
                    } else {
                        $identifiersPayload['created_at'] = now();
                        DB::table($tableIdentifiers)->insert($identifiersPayload);
                    }
                }

                if ($tableClassif && !empty($classificationPayload)) {
                    $existsC = DB::table($tableClassif)->where('asset_uuid', $asset->uuid)->first();
                    $classificationPayload['asset_uuid'] = $asset->uuid;
                    $classificationPayload['updated_at'] = now();
                    if ($existsC) {
                        DB::table($tableClassif)->where('asset_uuid', $asset->uuid)->update($classificationPayload);
                    } else {
                        $classificationPayload['created_at'] = now();
                        DB::table($tableClassif)->insert($classificationPayload);
                    }
                }

                if ($tableDocuments && !empty($documentsPayload)) {
                    $existsD = DB::table($tableDocuments)->where('asset_uuid', $asset->uuid)->first();
                    $documentsPayload['asset_uuid'] = $asset->uuid;
                    $documentsPayload['updated_at'] = now();
                    if ($existsD) {
                        DB::table($tableDocuments)->where('asset_uuid', $asset->uuid)->update($documentsPayload);
                    } else {
                        $documentsPayload['created_at'] = now();
                        DB::table($tableDocuments)->insert($documentsPayload);
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        return back()->with('success', "Import done. Inserted: {$inserted}, Updated: {$updated}, Skipped: {$skipped}.");
    }
}
