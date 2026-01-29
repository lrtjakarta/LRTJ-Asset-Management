<?php

namespace App\Http\Controllers;

use App\Actions\NextChildNumber;
use App\Actions\BuildAssetCode;
use App\Actions\BuildGroupCategoryCode;
use App\Http\Requests\AssetStoreRequest;
use App\Http\Requests\AssetUpdateRequest;
use App\Models\{
    AssetDeprMonthly,
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;


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
    protected function canEditAssetStatus(\App\Models\User $user = null): bool
    {
        if (!$user) return false;

        return $user->hasAnyRoleKode(['AM_HEAD', 'AM_ADMIN', 'SYSADMIN']);
    }
    public function detail(string $uuid)
    {
        $asset = Assets::with([
            'classification.transaction',
            'classification.category',
            'classification.category2',
            'classification.subCategory',
            'identifiers',
            'assignment.owner',
            'assignment.user',
            'assignment.maintenance',
            'value',
            'documents',
            'assetClass',
            'status',
            'location',
            'sumber',
            'activeQr',
            'activeRfid',
        ])->findOrFail($uuid);

        // latest ending_balance for this asset
        $row = AssetDeprMonthly::query()
            ->where('asset_uuid', $asset->uuid)
            ->orderByDesc('period')
            ->select(['period', 'ending_balance', 'accumulated_depr_end'])
            ->first();

        $endingBalance = (float) ($row?->ending_balance ?? 0);
        $endingPeriod  = $row?->period;
        $depreciation = (float) ($row?->accumulated_depr_end ?? 0);
        $nbvHasil = (float) $asset->value?->total - $depreciation ?? 0;
        return view('assets.assets_detail', compact('asset', 'endingBalance', 'endingPeriod', 'depreciation', 'nbvHasil'));
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
        $lastDepr = DB::table('assets_depr_ledger_monthly as m')
            ->selectRaw("
            DISTINCT ON (m.asset_uuid)
            m.asset_uuid,
            m.period,
            m.accumulated_depr_end,
            m.ending_balance
        ")
            ->orderBy('m.asset_uuid')
            ->orderByDesc('m.period');

        $q = DB::table('assets as a')
            // child tables
            ->leftJoin('assets_identifiers as i', 'i.asset_uuid', 'a.uuid')
            ->leftJoin('assets_assignment  as g', 'g.asset_uuid', 'a.uuid')
            ->leftJoin('assets_value       as v', 'v.asset_uuid', 'a.uuid')
            ->leftJoin('assets_document    as d', 'd.asset_uuid', 'a.uuid')

            ->leftJoinSub($lastDepr, 'dm', function ($j) {
                $j->on('dm.asset_uuid', '=', 'a.uuid');
            })

            // master lookups (names)
            ->leftJoin('master_location     as ml',   'ml.kode',    'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',   'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',    'a.kode_status')
            ->leftJoin('master_uom          as mu',   'mu.kode',    'v.kode_uom')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode',  'a.kode_sumber')
            ->leftJoin('master_user_code    as ou',   'ou.kode',    'g.asset_owner')
            ->leftJoin('master_user_code    as uu',   'uu.kode',    'g.asset_user')
            ->leftJoin('master_user_code    as muw',  'muw.kode',   'g.asset_maintenance')
            ->whereNull('a.deleted_at')
            ->select(
                'a.uuid',
                'a.asset_code',
                'a.kode_group_category',
                'a.asset_number_parent',
                'a.asset_number_child',
                'a.description',
                'a.upload_code',

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

                DB::raw("COALESCE(dm.accumulated_depr_end, 0) as last_accumulated_depr"),
                DB::raw("COALESCE(v.total, 0) - COALESCE(dm.accumulated_depr_end, 0) as last_net_book_value"),
                DB::raw("dm.period as last_depr_period"),

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

        if ($assetClass = $request->input('asset_class')) {
            $q->where('a.kode_asset_class', $assetClass);
        }

        // Transaction / Company
        if ($transaction = $request->input('transaction')) {
            $q->where('mac.kode_transaction', $transaction);
        }

        // Location
        if ($location = $request->input('location')) {
            $q->where('a.kode_location', $location);
        }

        // Status
        if ($status = $request->input('status')) {
            $q->where('a.kode_status', $status);
        }

        // Owner
        if ($owner = $request->input('owner')) {
            $q->where('g.asset_owner', $owner);
        }

        // User
        if ($user = $request->input('user')) {
            $q->where('g.asset_user', $user);
        }

        // Maintenance
        if ($maintenance = $request->input('maintenance')) {
            $q->where('g.asset_maintenance', $maintenance);
        }

        // Sumber
        if ($sumber = $request->input('sumber')) {
            $q->where('a.kode_sumber', $sumber);
        }

        if ($search = trim((string) $request->input('asset_q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('a.asset_code', 'ilike', "%{$search}%")
                    ->orWhere('a.description', 'ilike', "%{$search}%");
            });
        }

        $dt = DataTables::of($q);

        $like = function ($keyword) {
            $k = trim((string)$keyword);
            return $k === '' ? null : "%{$k}%";
        };

        $dt->filterColumn('kode_location_label', function ($query, $keyword) use ($like) {
            if (!$pat = $like($keyword)) return;
            $query->where(function ($w) use ($pat) {
                $w->where('a.kode_location', 'ilike', $pat)
                    ->orWhere('ml.name', 'ilike', $pat);
            });
        });

        $dt->filterColumn('asset_owner_label', function ($query, $keyword) use ($like) {
            if (!$pat = $like($keyword)) return;
            $query->where(function ($w) use ($pat) {
                $w->where('g.asset_owner', 'ilike', $pat)
                    ->orWhere('ou.department', 'ilike', $pat);
            });
        });

        $dt->filterColumn('asset_user_label', function ($query, $keyword) use ($like) {
            if (!$pat = $like($keyword)) return;
            $query->where(function ($w) use ($pat) {
                $w->where('g.asset_user', 'ilike', $pat)
                    ->orWhere('uu.department', 'ilike', $pat);
            });
        });

        $dt->filterColumn('asset_maintenance_label', function ($query, $keyword) use ($like) {
            if (!$pat = $like($keyword)) return;
            $query->where(function ($w) use ($pat) {
                $w->where('g.asset_maintenance', 'ilike', $pat)
                    ->orWhere('muw.department', 'ilike', $pat);
            });
        });

        $dt->filterColumn('kode_status_label', function ($query, $keyword) use ($like) {
            if (!$pat = $like($keyword)) return;
            $query->where(function ($w) use ($pat) {
                $w->where('a.kode_status', 'ilike', $pat)
                    ->orWhere('ms.name', 'ilike', $pat);
            });
        });

        $dt->filterColumn('kode_asset_class_label', function ($query, $keyword) use ($like) {
            if (!$pat = $like($keyword)) return;
            $query->where(function ($w) use ($pat) {
                $w->where('a.kode_asset_class', 'ilike', $pat)
                    ->orWhere('mac.name', 'ilike', $pat);
            });
        });

        return $dt->make(true);
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
        abort_unless($request->user()?->hasAction('ASSETS', 'C'), 403);
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
            $now    = Carbon::now();
            $prefix = 'UPL' . $now->format('ym');

            $last = Assets::where('upload_code', 'like', $prefix . '%')
                ->orderBy('upload_code', 'desc')
                ->first();

            if ($last) {
                $lastSeq = (int) substr($last->upload_code, -4);
                $seq     = $lastSeq + 1;
            } else {
                $seq = 1;
            }

            $upl_code = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

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
                'upload_code'         => $upl_code,
                'notes'               => $v->notes,
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
                'price'             => $v->price ?? null,
                'quantity'          => $v->quantity ?? null,
                'is_pajak'          => $v->is_pajak ?? null,
                'vat_in'            => $vatIn ?? null,
                'kode_uom'          => $v->kode_uom ?? null,
                'total'             => round(($v->price * $v->quantity) + $vatIn, 2) ?? null,
                'useful_life_month' => $v->useful_life_month ?? null,
                'useful_life_year'  => round($v->useful_life_month / 12, 2) ?? null,
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
        abort_unless($request->user()?->hasAction('ASSETS', 'U'), 403);
        $v = (object) $request->validated();
        $asset = Assets::with(['classification'])->findOrFail($uuid);
        $canEditStatus = $request->user()?->hasAnyRoleKode(['AM_HEAD', 'AM_ADMIN', 'SYSADMIN']) ?? false;

        $inputStatus = $request->input('kode_status');

        if ($inputStatus !== null && $inputStatus !== $asset->kode_status && ! $canEditStatus) {
            abort(403, 'Status hanya bisa diubah oleh AM_HEAD / AM_ADMIN / SYSADMIN.');
        }

        if (!$canEditStatus) {
            $request->merge(['kode_status' => $asset->kode_status]);
        }
        $vatRate = (float) env('NILAI_PAJAK', 11);
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
                'kode_status'      => $v->kode_status,
                'kode_location'    => $v->kode_location,
                'kode_sumber'      => $v->kode_sumber,
                'notes'               => $v->notes,
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
                    'price'             => $v->price ?? null,
                    'quantity'          => $v->quantity ?? null,
                    'is_pajak'          => $v->is_pajak ?? null,
                    'vat_in'            => $vatIn ?? null,
                    'kode_uom'          => $v->kode_uom ?? null,
                    'total'             => round(($v->price * $v->quantity) + $vatIn, 2) ?? null,
                    'useful_life_month' => $v->useful_life_month ?? null,
                    'useful_life_year'  => round($v->useful_life_month / 12, 2) ?? null,
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
        abort_unless(auth()->user()?->hasAction('ASSETS', 'D'), 403);
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
        $type = trim((string) $request->get('type')) ?? null;

        $rows = Assets::query()
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('asset_code', 'ilike', "%{$q}%")
                        ->orWhere('description', 'ilike', "%{$q}%");
                });
            })
            ->when($type === 'disposal', function ($qq) {
                $qq->where('kode_status', '!=', 'DIS');
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
            ->with(['value' => function ($c) {
                $c->select(
                    'asset_uuid',
                    'price',
                    'quantity',
                    'vat_in',
                    'total',
                    'kode_uom',
                    'useful_life_month',
                    'actual_date'      // <-- NEW
                );
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

        $locName = $locCode
            ? MasterLocation::query()
            ->where('kode', $locCode)
            ->value('name')
            : null;

        $ownerLabel = $ownerCode ? ($ownerCode . ' - ' . ($deptByKode[$ownerCode] ?? '')) : '(empty)';
        $userLabel  = $userCode  ? ($userCode  . ' - ' . ($deptByKode[$userCode]  ?? '')) : '(empty)';
        $mntLabel   = $mntCode   ? ($mntCode   . ' - ' . ($deptByKode[$mntCode]   ?? '')) : '(empty)';
        $statusLabel   = $stCode ? ($stCode   . ' - ' . ($statusName ?? ''))        : '(empty)';
        $locationLabel = $locCode ? ($locCode . ' - ' . ($locName ?? ''))           : '(empty)';

        // ---- NEW: ledger sums for accumulated depreciation & NBV ----
        $ledger = DB::table('assets_depr_ledger_monthly')
            ->selectRaw('COALESCE(SUM(accumulated_depr_end),0) as acc_sum, COALESCE(SUM(ending_balance),0) as nbv_sum')
            ->where('asset_uuid', $a?->uuid)
            ->first();

        $accSum = $ledger?->acc_sum ?? 0;
        $nbvSum = $ledger?->nbv_sum ?? 0;

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

            // existing acquisition fields
            'price'              => $a->value?->price,
            'quantity'           => $a->value?->quantity,
            'vat_in'             => $a->value?->vat_in,
            'total'              => $a->value?->total,
            'kode_uom'           => $a->value?->kode_uom,
            'useful_life_month'  => $a->value?->useful_life_month,

            // ---- NEW fields ----
            'acquisition_date'        => $a->value?->actual_date?->format('d F Y'),  // Acquisition Date
            'commercial_acq_cost'     => $a->value?->total,        // Commercial Acquisition Cost (IDR)
            'commercial_accum_depr'   => $accSum,                  // Commercial Accumulated Depreciation (IDR)
            'commercial_nbv'          => $nbvSum,                  // Commercial Net Book Value (IDR)
        ]);
    }
    public function bulk_upload()
    {
        return view('assets.bulk_upload', [
            'success'        => null,
            'error'          => null,
            'import_summary' => null,
        ]);
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
        abort_unless($request->user()?->hasAction('ASSETS', 'C'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $uploaded    = $request->file('file');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploaded->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();

        // ===================== HELPERS =====================

        $normalize = function ($v) {
            $v = trim((string) $v);
            $v = mb_strtolower($v);
            $v = preg_replace('/[^a-z0-9]+/u', '_', $v);
            return trim($v, '_');
        };

        $asText = function ($raw) {
            if ($raw === null) return null;
            if ($raw instanceof \DateTimeInterface) return \Carbon\Carbon::instance($raw)->toDateString();

            if (is_string($raw)) {
                $raw = trim($raw);
                return $raw === '' ? null : $raw;
            }

            if (is_numeric($raw)) {
                $f = (float) $raw;
                if (floor($f) == $f) return (string) ((int) $f);
                $s = (string) $raw;
                $s = rtrim(rtrim($s, '0'), '.');
                return $s === '' ? null : $s;
            }

            $s = trim((string) $raw);
            return $s === '' ? null : $s;
        };

        $parseNum = function ($raw) {
            if ($raw === null) return null;
            if ($raw instanceof \DateTimeInterface) return null;

            if (is_string($raw)) {
                $raw = trim($raw);
                if ($raw === '') return null;
                $raw = preg_replace('/[^\d\.\-]/', '', $raw);
                if ($raw === '' || $raw === '-' || $raw === '.') return null;
            }
            return is_numeric($raw) ? (float) $raw : null;
        };

        $resolveUom = function ($raw) {
            if ($raw === null) return null;
            $txt = trim((string) $raw);
            if ($txt === '' || $txt === '0' || $txt === '0.0') return null;

            if (!\Illuminate\Support\Facades\Schema::hasTable('master_uom')) return null;

            $row = \Illuminate\Support\Facades\DB::table('master_uom')
                ->select('kode')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($txt) {
                    $q->where('kode', $txt)
                        ->orWhere('kode', strtoupper($txt))
                        ->orWhere('name', 'ilike', $txt)
                        ->orWhere('name', 'ilike', '%' . $txt . '%');
                })
                ->first();

            return $row ? $row->kode : null;
        };

        $toBool = function ($v) {
            $s = strtolower(trim((string) $v));
            return in_array($s, ['1', 'y', 'yes', 'true', 'ya'], true);
        };

        $getVatRate = function () {
            $raw = env('NILAI_PAJAK', 10);
            $s   = is_string($raw) ? trim($raw) : (string) $raw;
            if (strpos($s, '%') !== false) {
                $n = (float) str_replace('%', '', $s);
                return $n / 100.0;
            }
            $n = (float) $raw;
            return $n > 1 ? $n / 100.0 : $n;
        };

        $parseExcelDate = function ($raw, $cell) {
            if ($raw === null) return null;
            if (is_string($raw) && trim($raw) === '') return null;
            if (is_numeric($raw) && (float)$raw == 0.0) return null;

            if ($raw instanceof \DateTimeInterface) {
                return \Carbon\Carbon::instance($raw)->startOfDay();
            }

            if (is_numeric($raw)) {
                try {
                    if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell) || ((float)$raw > 20000 && (float)$raw < 60000)) {
                        $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);
                        return \Carbon\Carbon::instance($dt)->startOfDay();
                    }
                } catch (\Throwable $e) {
                }
            }

            $s = trim((string) $raw);
            if ($s === '') return null;

            $s = str_replace(['.', '/'], '-', $s);

            foreach (['Y-m-d', 'd-m-Y', 'd-m-y', 'Y-m-d H:i:s', 'd-m-Y H:i:s'] as $fmt) {
                try {
                    return \Carbon\Carbon::createFromFormat($fmt, $s)->startOfDay();
                } catch (\Throwable $e) {
                }
            }

            try {
                return \Carbon\Carbon::parse($s)->startOfDay();
            } catch (\Throwable $e) {
                return null;
            }
        };

        $humanDbError = function (\Illuminate\Database\QueryException $e) {
            $sqlState = $e->errorInfo[0] ?? null;
            $msg = $e->getMessage();

            if ($sqlState === '23502') {
                if (preg_match('/null value in column "([^"]+)"/i', $msg, $m)) {
                    return ['sqlstate' => $sqlState, 'reason' => "Kolom wajib kosong (NOT NULL): {$m[1]}", 'raw' => $msg];
                }
                return ['sqlstate' => $sqlState, 'reason' => "Kolom wajib kosong (NOT NULL constraint).", 'raw' => $msg];
            }

            if ($sqlState === '23505') {
                if (preg_match('/Key \(([^)]+)\)=\(([^)]+)\)/i', $msg, $m)) {
                    return ['sqlstate' => $sqlState, 'reason' => "Duplicate/Unique: {$m[1]} = {$m[2]}", 'raw' => $msg];
                }
                return ['sqlstate' => $sqlState, 'reason' => "Duplicate/Unique constraint.", 'raw' => $msg];
            }

            if ($sqlState === '23503') {
                return ['sqlstate' => $sqlState, 'reason' => "Foreign key gagal (kode master tidak ditemukan / relasi tidak valid).", 'raw' => $msg];
            }

            if ($sqlState === '22001') {
                return ['sqlstate' => $sqlState, 'reason' => "Data terlalu panjang untuk kolom (string truncation).", 'raw' => $msg];
            }

            $short = mb_substr($msg, 0, 220);
            return ['sqlstate' => $sqlState, 'reason' => $short, 'raw' => $msg];
        };

        $req = function ($val) {
            return !(is_null($val) || trim((string)$val) === '');
        };

        // ===================== HEADER =====================

        $headers  = [];
        $firstRow = $sheet->getRowIterator(1, 1)->current();
        $cellIter = $firstRow->getCellIterator();
        $cellIter->setIterateOnlyExistingCells(false);

        foreach ($cellIter as $col => $cell) {
            $headers[$col] = $normalize($cell->getValue());
        }

        $KNOWN = [
            // asset (utama)
            'asset_number_parent'  => 'asset.asset_number_parent',
            'asset_number_child'   => 'asset.asset_number_child',
            'asset_description'    => 'asset.description',
            'location'             => 'asset.kode_location',
            'asset_status'         => 'asset.kode_status',
            'asset_class'          => 'asset.kode_asset_class',
            'notes'                => 'asset.notes',

            // classification
            'company'              => 'classification.kode_asset_transaction',

            // identifiers
            'asset_number_maximo'  => 'identifiers.asset_number_maximo',
            'asset_number_erp_365' => 'identifiers.asset_number_dynamic_365',
            'asset_number_internal' => 'identifiers.asset_number_internal',
            'asset_alias'          => 'identifiers.alias',

            // assignment
            'owner'                => 'assignment.asset_owner',
            'user'                 => 'assignment.asset_user',
            'maintenance'          => 'assignment.asset_maintenance',

            // value
            'actual_date'          => 'value.actual_date',
            'capitalization_date'  => 'value.capitalization_date',
            'qty'                  => 'value.quantity',
            'uom'                  => 'value.kode_uom',
            'price'                => 'value.price',
            'pajak'                => 'value.is_pajak',
            'acquisition'          => 'value.total',
            'useful_life_month'    => 'value.useful_life_month',

            // documents
            'no_po_perjanjian_spk' => 'documents.no_po_perjanjian_spk',
            'nota_referensi'       => 'documents.nota_referensi',
        ];

        $colTargets = [];
        foreach ($headers as $col => $head) {
            if (!isset($KNOWN[$head])) continue;
            [$group, $field] = explode('.', $KNOWN[$head], 2);
            $colTargets[$col] = ['group' => $group, 'field' => $field, 'head' => $head];
        }

        if (empty($colTargets)) {
            return redirect()->route('assets.upload.bulk')
                ->with('error', 'Template Excel tidak dikenali. Pastikan header tidak diubah.');
        }

        $CODE_HEADS = [
            'asset_number_parent',
            'asset_number_child',
            'company',
            'asset_class',
            'location',
            'asset_status',
            'owner',
            'user',
            'maintenance',
            'uom',
            'asset_number_maximo',
            'asset_number_erp_365',
            'asset_number_internal',
            'asset_alias',
        ];
        $hasGroupCounters  = \Illuminate\Support\Facades\Schema::hasTable('asset_group_counters');
        $hasParentCounters = \Illuminate\Support\Facades\Schema::hasTable('asset_parent_counters');

        $syncCountersFromAsset = function (\App\Models\Assets $asset) use ($hasGroupCounters, $hasParentCounters) {
            $parent = trim((string) ($asset->asset_number_parent ?? ''));
            $child  = trim((string) ($asset->asset_number_child ?? ''));

            if ($parent === '' || $child === '') return;

            // group_code: pakai kode_group_category kalau ada, fallback LEFT(parent,5)
            $group = trim((string) ($asset->kode_group_category ?? ''));
            if ($group === '') $group = substr($parent, 0, 5);
            if ($group === '') return;

            // parent seq = 6 digit terakhir dari parent (AT123000001 -> 1)
            $digits = preg_replace('/\D+/', '', $parent);
            $last6  = substr($digits, -6);
            $parentSeq = (int) ltrim($last6, '0'); // "000001" -> 1, "" -> 0

            // child seq int ("00" -> 0, "05" -> 5)
            $childDigits = preg_replace('/\D+/', '', $child);
            $childInt = (int) ltrim($childDigits === '' ? '0' : $childDigits, '0');

            // === upsert group counter ===
            if ($hasGroupCounters) {
                \Illuminate\Support\Facades\DB::statement("
            INSERT INTO asset_group_counters (group_code, last_parent_seq, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
            ON CONFLICT (group_code) DO UPDATE
            SET last_parent_seq = GREATEST(asset_group_counters.last_parent_seq, EXCLUDED.last_parent_seq),
                updated_at = NOW()
        ", [$group, $parentSeq]);
            }

            // === upsert parent counter ===
            if ($hasParentCounters) {
                \Illuminate\Support\Facades\DB::statement("
            INSERT INTO asset_parent_counters (parent_code, last_child_seq, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
            ON CONFLICT (parent_code) DO UPDATE
            SET last_child_seq = GREATEST(asset_parent_counters.last_child_seq, EXCLUDED.last_child_seq),
                updated_at = NOW()
        ", [$parent, $childInt]);
            }
        };
        // ===================== TABLE DETECTION =====================

        $hasAssignments   = \Illuminate\Support\Facades\Schema::hasTable('assets_assignment');
        $tableValues      = \Illuminate\Support\Facades\Schema::hasTable('assets_values')
            ? 'assets_values'
            : (\Illuminate\Support\Facades\Schema::hasTable('assets_value') ? 'assets_value' : null);

        $tableIdentifiers = \Illuminate\Support\Facades\Schema::hasTable('assets_identifiers') ? 'assets_identifiers' : null;
        $tableClassif     = \Illuminate\Support\Facades\Schema::hasTable('assets_classification') ? 'assets_classification' : null;
        $tableDocuments   = \Illuminate\Support\Facades\Schema::hasTable('assets_document') ? 'assets_document' : null;

        $hasDeprPolicy  = \Illuminate\Support\Facades\Schema::hasTable('assets_depr_policy');
        $hasDeprMonthly = class_exists(\App\Models\AssetDeprMonthly::class);

        // ===================== AUTO DEPR GENERATOR =====================

        $seqCache = [];

        $nextDeprCode = function (\Carbon\Carbon $period) use (&$seqCache) {
            $prefix = 'DEP' . $period->format('ym');

            if (!array_key_exists($prefix, $seqCache)) {
                $last = \App\Models\AssetDeprMonthly::query()
                    ->whereNotNull('depr_code')
                    ->where('depr_code', 'like', $prefix . '%')
                    ->orderBy('depr_code', 'desc')
                    ->first();

                $seqCache[$prefix] = $last ? ((int) substr($last->depr_code, -4)) : 0;
            }

            $seqCache[$prefix]++;

            return $prefix . str_pad((string)$seqCache[$prefix], 4, '0', STR_PAD_LEFT);
        };

        $generateDeprHistory = function (string $assetUuid, \Carbon\Carbon $capDate, int $lifeMonths, float $capTotal) use ($nextDeprCode) {
            if ($lifeMonths <= 0) return;
            if ($capTotal <= 0) return;

            $cutoff = 16;

            $eligibleStart = $capDate->copy()->startOfMonth()
                ->addMonths(($capDate->day <= $cutoff) ? 1 : 2);

            $nowMonth = now()->startOfMonth();
            $endByLife = $eligibleStart->copy()->addMonths($lifeMonths - 1);
            $end = $endByLife->lte($nowMonth) ? $endByLife : $nowMonth;

            if ($eligibleStart->gt($end)) return;

            $opening = $capTotal;
            $accPrev = 0.0;

            $period = $eligibleStart->copy();

            $elapsed = 0;

            while ($period->lte($end)) {
                $remaining = max(0, $lifeMonths - $elapsed);

                $deprBase = max($opening - 0.0, 0.0);
                $deprExpense = ($remaining > 0)
                    ? floor($deprBase / $remaining)
                    : 0.0;

                $ending = $opening - $deprExpense;
                $accEnd = $accPrev + $deprExpense;

                \App\Models\AssetDeprMonthly::updateOrCreate(
                    [
                        'asset_uuid' => $assetUuid,
                        'period'     => $period->toDateString(),
                    ],
                    [
                        'opening_balance'         => $opening,
                        'additions'               => 0.0,
                        'transfers_in'            => 0.0,
                        'transfers_out'           => 0.0,
                        'disposals'               => 0.0,
                        'adjustment_value'        => 0.0,
                        'adjustment_depreciation' => 0.0,
                        'depr_expense'            => $deprExpense,
                        'accumulated_depr_end'    => $accEnd,
                        'ending_balance'          => $ending,
                        'depr_code'               => $nextDeprCode($period),
                    ]
                );

                // next month
                $opening = $ending;
                $accPrev = $accEnd;
                $elapsed++;

                $period->addMonth();
            }
        };

        // ===================== REPORTING =====================

        $rows      = 0;
        $inserted  = 0;
        $updated   = 0;
        $skipped   = 0;

        $rowErrors  = [];
        $failedRows = [];

        $pushFail = function ($rowIndex, $code, $reason, array $ctx = []) use (&$failedRows, &$rowErrors) {
            $failedRows[] = array_merge([
                'row'        => $rowIndex,
                'asset_code' => $code,
                'reason'     => $reason,
            ], $ctx);

            $mini = "Row {$rowIndex}";
            if ($code) $mini .= " ({$code})";
            $mini .= ": {$reason}";
            if (!empty($ctx['hint'])) $mini .= " | Hint: {$ctx['hint']}";
            $rowErrors[] = $mini;
        };

        // ===================== LOOP ROWS =====================

        foreach ($sheet->getRowIterator(2) as $row) {
            $rows++;
            $rowIndex = $row->getRowIndex();

            $current = ['asset_code' => null, 'parent' => null, 'child' => null];

            try {
                \Illuminate\Support\Facades\DB::beginTransaction();

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

                    $raw = in_array($target['head'], $CODE_HEADS, true)
                        ? $cell->getFormattedValue()
                        : $cell->getCalculatedValue();

                    if (is_string($raw)) $raw = trim($raw);
                    if ($raw === '') $raw = null;

                    if ($raw !== null && in_array($target['head'], $CODE_HEADS, true)) {
                        $raw = $asText($raw);
                    }

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
                                    if ($raw !== null) $valuePayload['kode_uom'] = (string) $raw;
                                    break;

                                case 'quantity':
                                case 'price':
                                case 'total':
                                    $n = $parseNum($raw);
                                    if ($n !== null) $valuePayload[$target['field']] = $n;
                                    break;

                                case 'is_pajak':
                                    if ($raw !== null) $isPajakFlag = $toBool($raw);
                                    break;

                                case 'useful_life_month':
                                    if ($raw !== null) $valuePayload['useful_life_month'] = (int) round((float) $raw);
                                    break;

                                case 'actual_date':
                                    $dt = $parseExcelDate($raw, $cell);
                                    if ($dt) $valuePayload['actual_date'] = $dt;
                                    break;

                                case 'capitalization_date':
                                    $dt = $parseExcelDate($raw, $cell);
                                    if ($dt) $valuePayload['capitalization_date'] = $dt;
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

                $parent = $asText($assetPayload['asset_number_parent'] ?? null);
                $child  = $asText($assetPayload['asset_number_child'] ?? null);

                if ($parent !== null) $parent = trim($parent);
                if ($child !== null)  $child  = trim($child);

                if ($req($child)) {
                    $digits = preg_replace('/\D+/', '', (string) $child);
                    $child = $digits !== '' ? str_pad($digits, 2, '0', STR_PAD_LEFT) : str_pad((string)$child, 2, '0', STR_PAD_LEFT);
                    $assetPayload['asset_number_child'] = $child;
                }

                $code = ($req($parent) && $req($child)) ? ($parent . '-' . $child) : null;

                $current['parent']     = $parent;
                $current['child']      = $child;
                $current['asset_code'] = $code;

                if (!$code) {
                    $skipped++;
                    $pushFail($rowIndex, null, 'Parent/Child kosong. Asset code tidak bisa dibentuk.', [
                        'parent' => $parent,
                        'child'  => $child,
                        'hint'   => 'Isi Asset Number Parent + Child. Child harus 2 digit (00/01/02...).',
                    ]);
                    \Illuminate\Support\Facades\DB::rollBack();
                    continue;
                }

                if (empty($assetPayload['kode_group_category'])) {
                    $assetPayload['kode_group_category'] = $parent ? substr($parent, 0, 5) : substr($code, 0, 5);
                }
                if (empty($assetPayload['kode_sumber'])) {
                    $assetPayload['kode_sumber'] = 'EXC';
                }

                $asset = \App\Models\Assets::withTrashed()->where('asset_code', $code)->first();
                $isNew = false;

                if (!$asset) {
                    $asset = new \App\Models\Assets();
                    $asset->uuid       = (string) \Illuminate\Support\Str::uuid();
                    $asset->asset_code = $code;
                    $isNew             = true;
                } else {
                    if (method_exists($asset, 'trashed') && $asset->trashed()) {
                        $asset->restore();
                    }
                }

                if ($isNew) {
                    $company    = $classificationPayload['kode_asset_transaction'] ?? null;
                    $assetClass = $assetPayload['kode_asset_class'] ?? null;
                    $desc       = $assetPayload['description'] ?? null;
                    $loc        = $assetPayload['kode_location'] ?? null;
                    $status     = $assetPayload['kode_status'] ?? null;

                    $owner = $assignmentPayload['asset_owner'] ?? null;
                    $user  = $assignmentPayload['asset_user'] ?? null;
                    $mnt   = $assignmentPayload['asset_maintenance'] ?? null;

                    $missing = [];
                    if (!$req($parent))     $missing[] = 'Asset Number Parent';
                    if (!$req($child))      $missing[] = 'Asset Number Child';
                    if (!$req($company))    $missing[] = 'Company';
                    if (!$req($assetClass)) $missing[] = 'Asset Class';
                    if (!$req($desc))       $missing[] = 'Asset Description';
                    if (!$req($loc))        $missing[] = 'Location';
                    if (!$req($status))     $missing[] = 'Asset Status';
                    if (!$req($owner))      $missing[] = 'Owner';
                    if (!$req($user))       $missing[] = 'User';
                    if (!$req($mnt))        $missing[] = 'Maintenance';

                    if (!empty($missing)) {
                        $skipped++;
                        $pushFail($rowIndex, $code, 'Wajib diisi untuk INSERT: ' . implode(', ', $missing), [
                            'hint' => 'Update boleh kosong, tapi Insert wajib lengkap.',
                        ]);
                        \Illuminate\Support\Facades\DB::rollBack();
                        continue;
                    }
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
                        'notes',
                    ] as $f
                ) {
                    if (array_key_exists($f, $assetPayload)) $asset->{$f} = $assetPayload[$f];
                }

                $asset->save();
                $isNew ? $inserted++ : $updated++;
                $syncCountersFromAsset($asset);

                // === QR ===
                try {
                    $displayLabel = $asset->asset_code . ($asset->description ? (' (' . $asset->description . ')') : '');
                    $labeledSvg   = $this->generate_qr($asset->uuid, $displayLabel);

                    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists('qrcodes')) {
                        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('qrcodes');
                    }

                    $qrRelativePath = "qrcodes/{$asset->uuid}.svg";
                    \Illuminate\Support\Facades\Storage::disk('public')->put($qrRelativePath, $labeledSvg);

                    $qr = \App\Models\AssetsQr::firstOrNew(['asset_uuid' => $asset->uuid]);
                    $qr->qr_data      = $asset->uuid;
                    $qr->image_path   = $qrRelativePath;
                    $qr->is_active    = true;
                    $qr->generated_at = now();
                    $qr->save();
                } catch (\Throwable $e) {
                    report($e);
                }

                // === Assignment upsert ===
                if ($hasAssignments && !empty($assignmentPayload)) {
                    $exists = \Illuminate\Support\Facades\DB::table('assets_assignment')->where('asset_uuid', $asset->uuid)->first();

                    $assignmentPayload['asset_uuid'] = $asset->uuid;
                    $assignmentPayload['updated_at'] = now();

                    if ($exists) {
                        \Illuminate\Support\Facades\DB::table('assets_assignment')->where('asset_uuid', $asset->uuid)->update($assignmentPayload);
                    } else {
                        $assignmentPayload['created_at'] = now();
                        \Illuminate\Support\Facades\DB::table('assets_assignment')->insert($assignmentPayload);
                    }
                }

                // === Values upsert (partial-safe) ===
                if ($tableValues && (!empty($valuePayload) || $isPajakFlag !== null)) {

                    $existsV = \Illuminate\Support\Facades\DB::table($tableValues)->where('asset_uuid', $asset->uuid)->first();

                    $baseQty   = $existsV ? (float)($existsV->quantity ?? 0) : 0.0;
                    $basePrice = $existsV ? (float)($existsV->price ?? 0)    : 0.0;
                    $basePajak = $existsV ? (int)($existsV->is_pajak ?? 0)   : 0;
                    $baseVat   = $existsV ? (float)($existsV->vat_in ?? 0)   : 0.0;

                    if (array_key_exists('kode_uom', $valuePayload)) {
                        $kode = $resolveUom($valuePayload['kode_uom']);
                        if ($kode) $valuePayload['kode_uom'] = $kode;
                        else unset($valuePayload['kode_uom']);
                    }

                    $qty   = array_key_exists('quantity', $valuePayload) ? (float)$valuePayload['quantity'] : $baseQty;
                    $price = array_key_exists('price',    $valuePayload) ? (float)$valuePayload['price']    : $basePrice;

                    $isPajak = ($isPajakFlag !== null) ? ($isPajakFlag ? 1 : 0) : $basePajak;

                    $needRecalc = array_key_exists('quantity', $valuePayload)
                        || array_key_exists('price', $valuePayload)
                        || ($isPajakFlag !== null)
                        || !array_key_exists('total', $valuePayload);

                    if ($needRecalc) {
                        $subtotal = $qty * $price;
                        $rate     = $getVatRate();
                        $vat      = $isPajak ? round($subtotal * $rate, 2) : 0.0;

                        $valuePayload['quantity'] = $qty;
                        $valuePayload['price']    = $price;
                        $valuePayload['is_pajak'] = $isPajak;
                        $valuePayload['vat_in']   = $vat;

                        if (!array_key_exists('total', $valuePayload) || $valuePayload['total'] === null) {
                            $valuePayload['total'] = round($subtotal + $vat, 2);
                        }
                    } else {
                        if ($isPajakFlag !== null) $valuePayload['is_pajak'] = $isPajak;
                        if (!array_key_exists('vat_in', $valuePayload) && $existsV) $valuePayload['vat_in'] = $baseVat;
                    }

                    if (isset($valuePayload['useful_life_month']) && $valuePayload['useful_life_month'] !== null) {
                        $months = (float) $valuePayload['useful_life_month'];
                        $valuePayload['useful_life_year'] = round($months / 12, 2);
                    }

                    $valuePayload['asset_uuid'] = $asset->uuid;
                    $valuePayload['updated_at'] = now();

                    if ($existsV) {
                        \Illuminate\Support\Facades\DB::table($tableValues)->where('asset_uuid', $asset->uuid)->update($valuePayload);
                    } else {
                        $valuePayload['created_at'] = now();
                        \Illuminate\Support\Facades\DB::table($tableValues)->insert($valuePayload);
                    }
                }

                if ($tableIdentifiers && !empty($identifiersPayload)) {
                    $existsI = \Illuminate\Support\Facades\DB::table($tableIdentifiers)->where('asset_uuid', $asset->uuid)->first();

                    $identifiersPayload['asset_uuid'] = $asset->uuid;
                    $identifiersPayload['updated_at'] = now();

                    if ($existsI) {
                        \Illuminate\Support\Facades\DB::table($tableIdentifiers)->where('asset_uuid', $asset->uuid)->update($identifiersPayload);
                    } else {
                        $identifiersPayload['created_at'] = now();
                        \Illuminate\Support\Facades\DB::table($tableIdentifiers)->insert($identifiersPayload);
                    }
                }

                if ($tableClassif && !empty($classificationPayload)) {
                    $existsC = \Illuminate\Support\Facades\DB::table($tableClassif)->where('asset_uuid', $asset->uuid)->first();

                    $classificationPayload['asset_uuid'] = $asset->uuid;
                    $classificationPayload['updated_at'] = now();

                    if ($existsC) {
                        \Illuminate\Support\Facades\DB::table($tableClassif)->where('asset_uuid', $asset->uuid)->update($classificationPayload);
                    } else {
                        $classificationPayload['created_at'] = now();
                        \Illuminate\Support\Facades\DB::table($tableClassif)->insert($classificationPayload);
                    }
                }

                if ($tableDocuments && !empty($documentsPayload)) {
                    $existsD = \Illuminate\Support\Facades\DB::table($tableDocuments)->where('asset_uuid', $asset->uuid)->first();

                    $documentsPayload['asset_uuid'] = $asset->uuid;
                    $documentsPayload['updated_at'] = now();

                    if ($existsD) {
                        \Illuminate\Support\Facades\DB::table($tableDocuments)->where('asset_uuid', $asset->uuid)->update($documentsPayload);
                    } else {
                        $documentsPayload['created_at'] = now();
                        \Illuminate\Support\Facades\DB::table($tableDocuments)->insert($documentsPayload);
                    }
                }

                // ===================== AUTO GENERATE DEPRECIATION (HISTORY) =====================
                if ($isNew && $hasDeprPolicy && $hasDeprMonthly) {
                    $capDate = $valuePayload['capitalization_date'] ?? $valuePayload['actual_date'] ?? null;
                    $life    = (int) ($valuePayload['useful_life_month'] ?? 0);

                    $capTotal = (float) ($valuePayload['total'] ?? 0);

                    if ($capDate instanceof \Carbon\Carbon && $life > 0 && $capTotal > 0) {
                        $policy = \App\Models\AssetDeprPolicy::query()->where('asset_uuid', $asset->uuid)->first();
                        if (!$policy) {
                            \App\Models\AssetDeprPolicy::create([
                                'asset_uuid'         => $asset->uuid,
                                'method'             => \App\Models\AssetDeprPolicy::METHOD_SL,
                                'useful_life_months' => $life,
                                'salvage_value'      => 0,
                                'depr_start_date'    => $capDate->toDateString(),
                                'convention'         => \App\Models\AssetDeprPolicy::CONVENTION_PRORATA_MONTH,
                                'cutoff_day'         => 16,
                                'start_rule'         => 'CUT_OFF_NEXT_OR_NEXT2',
                                'is_active'          => true,
                            ]);
                        } else {
                            // update minimal kalau kosong / tidak sinkron
                            $upd = [];
                            if (!$policy->depr_start_date) $upd['depr_start_date'] = $capDate->toDateString();
                            if ((int)($policy->useful_life_months ?? 0) <= 0) $upd['useful_life_months'] = $life;
                            if (!empty($upd)) {
                                \App\Models\AssetDeprPolicy::where('asset_uuid', $asset->uuid)->update($upd);
                            }
                        }

                        $generateDeprHistory($asset->uuid, $capDate, $life, $capTotal);
                    }
                }

                \Illuminate\Support\Facades\DB::commit();
            } catch (\Illuminate\Database\QueryException $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $skipped++;

                $info = $humanDbError($e);
                $pushFail($rowIndex, $current['asset_code'], $info['reason'], [
                    'parent'   => $current['parent'],
                    'child'    => $current['child'],
                    'sqlstate' => $info['sqlstate'],
                    'hint'     => 'Cek kolom wajib (NOT NULL), kode master, atau constraint unique/FK.',
                ]);

                continue;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $skipped++;

                $pushFail($rowIndex, $current['asset_code'], $e->getMessage(), [
                    'parent' => $current['parent'],
                    'child'  => $current['child'],
                    'hint'   => 'Periksa nilai pada kolom terkait (format angka/tanggal/kode master).',
                ]);

                continue;
            }
        }

        \Illuminate\Support\Facades\Log::info('ASSETS_UPLOAD_EXCEL_SUMMARY', [
            'rows'        => $rows,
            'inserted'    => $inserted,
            'updated'     => $updated,
            'skipped'     => $skipped,
            'errors'      => $rowErrors,
            'failed_rows' => $failedRows,
        ]);

        return view('assets.bulk_upload', [
            'success'        => "Import done. Inserted: {$inserted}, Updated: {$updated}, Skipped: {$skipped}.",
            'error'          => null,
            'import_summary' => [
                'rows'        => $rows,
                'inserted'    => $inserted,
                'updated'     => $updated,
                'skipped'     => $skipped,
                'errors'      => $rowErrors,
                'failed_rows' => $failedRows,
            ],
        ]);
    }
}
