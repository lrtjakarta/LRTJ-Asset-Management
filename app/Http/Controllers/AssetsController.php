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
    AssetsRfid
};
use App\Services\AssetNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;


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

        return view('assets.assets_edit', compact('asset'));
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

        $group  = $groupBuilder->handle(
            $v->kode_asset_transaction,
            $v->kode_asset_type,
            $v->kode_category,
            $v->kode_category_2,
            $v->kode_sub_category
        );

        // Generate numbers
        $parent = $num->nextParent($group);
        $child  = $num->nextChild($parent);
        $code   = $parent . '-' . $child;
        $uuid   = (string) Str::uuid();
        $vatRate = (float) env('NILAI_PAJAK', 10);

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
                'kode_asset_type'        => $v->kode_asset_type,
                'kode_category'          => $v->kode_category,
                'kode_category_2'        => $v->kode_category_2,
                'kode_sub_category'      => $v->kode_sub_category,
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
        string $uuid
    ) {
        $v = (object) $request->validated();
        
        $asset = Assets::findOrFail($uuid);

        $asset->load('classification');
        $asset->load('documents');
        $asset->load('identifiers');
        $asset->load('assignment');
        $asset->load('value');
        $asset->load('qrs');
        $vatRate = (float) env('NILAI_PAJAK', 10);

        $newGroup = $groupBuilder->handle(
            $v->kode_asset_transaction,
            $v->kode_asset_type,
            $v->kode_category,
            $v->kode_category_2,
            $v->kode_sub_category
        );

        $recode = $newGroup !== $asset->kode_group_category;

        DB::transaction(function () use ($v, $asset, $recode, $newGroup, $num, $vatRate, $uuid) {

            if ($recode) {
                if ($asset->kode_group_category && $asset->asset_number_parent) {
                    $num->rollbackParentIfLatest(
                        $asset->kode_group_category,
                        $asset->asset_number_parent,
                        $asset->uuid
                    );
                }
                $newParent = $num->nextParent($newGroup);
                $newChild  = $num->nextChild($newParent);

                $asset->fill([
                    'description'         => $v->description,
                    'kode_group_category' => $newGroup,
                    'asset_number_parent' => $newParent,
                    'asset_number_child'  => $newChild,
                    'asset_code'          => $newParent . '-' . $newChild,
                ])->save();
            }

            // asset core fields
            $asset->fill([
                'description'         => $v->description,
                'kode_asset_class'    => $v->kode_asset_class,
                'kode_status'         => $v->kode_status,
                'kode_location'       => $v->kode_location,
                'kode_sumber'         => $v->kode_sumber,
            ])->save();

            // classification
            $asset->classification()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'kode_asset_transaction' => $v->kode_asset_transaction,
                    'kode_asset_type'        => $v->kode_asset_type,
                    'kode_category'          => $v->kode_category,
                    'kode_category_2'        => $v->kode_category_2,
                    'kode_sub_category'      => $v->kode_sub_category,
                ]
            );

            // document
            $asset->documents()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'no_po_perjanjian_spk'  => $v->no_po_perjanjian_spk,
                    'nota_referensi'        => $v->nota_referensi,
                    'no_document'           => $v->no_document,
                ]
            );

            // identifier
            $asset->identifiers()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'asset_number_maximo'       => $v->asset_number_maximo,
                    'asset_number_dynamic_365'  => $v->asset_number_dynamic_365,
                    'asset_number_internal'     => $v->asset_number_internal,
                ]
            );

            // assignment
            $asset->assignment()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],
                [
                    'asset_owner'       => $v->asset_owner,
                    'asset_user'        => $v->asset_user,
                    'asset_maintenance' => $v->asset_maintenance,
                ]
            );

            // value            
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

            // Build labeled SVG (your helper)
            $label = $asset->code . ' (' . $v->description . ') ';
            $labeledSvg = $this->generate_qr($asset->uuid, $label);
            if (! Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }
            Storage::disk('public')->delete($qrRelativePath);
            Storage::disk('public')->put($qrRelativePath, $labeledSvg);
            $asset->qrs()->updateOrCreate(
                ['asset_uuid' => $asset->uuid],                  // attributes (WHERE)
                [
                    'qr_data'      => $asset->uuid,                // values (SET …)
                    'image_path'   => $qrRelativePath,
                    'is_active'    => true,
                    'generated_at' => now(),                     // or \Carbon\Carbon::now()
                ]
            );
        });


        return redirect()->route('assets.detail', $asset->uuid)->with('success', 'Asset updated.');
    }

    public function destroy(Assets $asset)
    {
        $asset->delete();
        return response()->json(['ok' => true, 'message' => 'Asset moved to trash']);
    }
}
