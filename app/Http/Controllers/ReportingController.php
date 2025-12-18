<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportingController extends Controller
{
    protected function canReadReporting()
    {
        $u = auth()->user();
        // Adjust 'REPORTING' to your actual menu code if needed
        return $u && method_exists($u, 'hasAction')
            ? $u->hasAction('REPORTING', 'R')
            : true;
    }

    public function index()
    {
        abort_unless($this->canReadReporting(), 403);
        return view('reporting.reporting');
    }

    /**
     * Base query used by datatable + export
     */
    protected function buildAssetDeprQuery(Request $request)
    {
        $period = $request->input('period'); // expected 'YYYY-MM-01' or empty

        $q = DB::table('assets as a')
            // child tables
            ->leftJoin('assets_identifiers as i', 'i.asset_uuid', 'a.uuid')
            ->leftJoin('assets_assignment  as g', 'g.asset_uuid', 'a.uuid')
            ->leftJoin('assets_value       as v', 'v.asset_uuid', 'a.uuid')
            ->leftJoin('assets_document    as d', 'd.asset_uuid', 'a.uuid')
            ->leftJoin('assets_depr_ledger_monthly as l', 'l.asset_uuid', 'a.uuid')
            // master lookups
            ->leftJoin('master_location     as ml',   'ml.kode',    'a.kode_location')
            ->leftJoin('master_asset_class  as mac',  'mac.kode',   'a.kode_asset_class')
            ->leftJoin('master_status       as ms',   'ms.kode',    'a.kode_status')
            ->leftJoin('master_uom          as mu',   'mu.kode',    'v.kode_uom')
            ->leftJoin('master_sumber       as msrc', 'msrc.kode',  'a.kode_sumber')
            ->leftJoin('master_user_code    as ou',   'ou.kode',    'g.asset_owner')
            ->leftJoin('master_user_code    as uu',   'uu.kode',    'g.asset_user')
            ->leftJoin('master_user_code    as muw',  'muw.kode',   'g.asset_maintenance')
            ->whereNull('a.deleted_at');

        // === Asset-side filters (same as Assets datatable) ===

        if ($assetClass = $request->input('asset_class')) {
            $q->where('a.kode_asset_class', $assetClass);
        }

        if ($transaction = $request->input('transaction')) {
            $q->where('mac.kode_transaction', $transaction);
        }

        if ($location = $request->input('location')) {
            $q->where('a.kode_location', $location);
        }

        if ($status = $request->input('status')) {
            $q->where('a.kode_status', $status);
        }

        if ($owner = $request->input('owner')) {
            $q->where('g.asset_owner', $owner);
        }

        if ($user = $request->input('user')) {
            $q->where('g.asset_user', $user);
        }

        if ($maintenance = $request->input('maintenance')) {
            $q->where('g.asset_maintenance', $maintenance);
        }

        if ($sumber = $request->input('sumber')) {
            $q->where('a.kode_sumber', $sumber);
        }

        if ($assetQ = trim((string) $request->input('asset_q', ''))) {
            $q->where(function ($w) use ($assetQ) {
                $w->where('a.asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('a.description', 'ilike', "%{$assetQ}%");
            });
        }

        // === Depreciation-side filters (same as Depreciation dtMonthly) ===

        if ($capFrom = $request->input('cap_from')) {
            $q->whereDate('v.capitalization_date', '>=', $capFrom);
        }

        if ($capTo = $request->input('cap_to')) {
            $q->whereDate('v.capitalization_date', '<=', $capTo);
        }

        if ($period) {
            // expect 'YYYY-MM-01'
            $q->whereDate('l.period', $period);
        }

        // === Select columns (combined) ===

        $q->select([
            'a.uuid',
            'a.asset_code',
            'a.description',
            'a.kode_asset_class',
            'a.kode_location',
            'a.kode_status',
            'g.asset_owner',
            'g.asset_user',
            'g.asset_maintenance',

            'v.price',
            'v.quantity',
            'v.vat_in',
            'v.kode_uom',
            'v.total',
            'v.capitalization_date as cap_date',

            'l.period',
            'l.depr_code',
            'l.opening_balance',
            'l.additions',
            'l.transfers_in',
            'l.transfers_out',
            'l.disposals',
            'l.adjustment_value',
            'l.adjustment_depreciation',
            'l.depr_expense',
            'l.ending_balance',

            'd.no_po_perjanjian_spk',
            'd.nota_referensi',

            DB::raw("
                CASE WHEN ml.name  IS NULL THEN a.kode_location
                     ELSE a.kode_location    || ' - ' || ml.name
                END AS kode_location_label
            "),
            DB::raw("
                CASE WHEN mac.name IS NULL THEN a.kode_asset_class
                     ELSE a.kode_asset_class || ' - ' || mac.name
                END AS kode_asset_class_label
            "),
            DB::raw("
                CASE WHEN ms.name  IS NULL THEN a.kode_status
                     ELSE a.kode_status      || ' - ' || ms.name
                END AS kode_status_label
            "),
            DB::raw("
                CASE WHEN mu.name  IS NULL THEN v.kode_uom
                     ELSE v.kode_uom         || ' - ' || mu.name
                END AS kode_uom_label
            "),
            DB::raw("
                CASE WHEN msrc.name IS NULL THEN a.kode_sumber
                     ELSE a.kode_sumber      || ' - ' || msrc.name
                END AS kode_sumber_label
            "),
            DB::raw("
                CASE WHEN ou.department  IS NULL THEN g.asset_owner
                     ELSE g.asset_owner         || ' - ' || ou.department
                END AS asset_owner_label
            "),
            DB::raw("
                CASE WHEN uu.department  IS NULL THEN g.asset_user
                     ELSE g.asset_user          || ' - ' || uu.department
                END AS asset_user_label
            "),
            DB::raw("
                CASE WHEN muw.department IS NULL THEN g.asset_maintenance
                     ELSE g.asset_maintenance  || ' - ' || muw.department
                END AS asset_maintenance_label
            "),
            DB::raw("
                  to_char(
                    COALESCE(l.updated_at, a.updated_at) at time zone 'Asia/Jakarta',
                    'YYYY-MM-DD\"T\"HH24:MI:SS'
                ) as updated_at
            "),
        ]);

        return $q;
    }

    /**
     * Datatables endpoint
     */
    public function datatableAssetDepr(Request $request)
    {
        if (!$this->canReadReporting()) {
            return DataTables::of(collect())->toJson();
        }

        $q = $this->buildAssetDeprQuery($request);

        return DataTables::of($q)->make(true);
    }

    /**
     * Export Excel
     */
    public function exportAssetDepr(Request $request)
    {
        abort_unless($this->canReadReporting(), 403);

        $rows = $this->buildAssetDeprQuery($request)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporting');
        $set = function ($colIndex, $rowIndex, $value) use ($sheet) {
            $cell = Coordinate::stringFromColumnIndex($colIndex) . $rowIndex;
            $sheet->setCellValue($cell, $value);
        };

        // Header row
        $headers = [
            'Asset Code',
            'Asset Description',
            'Asset Class',
            'Location',
            'Status',
            'Owner',
            'User',
            'Price',
            'Quantity',
            'VAT In',
            'UOM',
            'Total',

            'Cap Date',
            'Period',
            'Depr Code',
            'Opening Balance',
            'Additions',
            'Transfer In',
            'Transfer Out',
            'Disposals',
            'Adjustment Value',
            'Adjustment Depr',
            'Depreciation',
            'Ending Balance',

            'No PO/Perjanjian/SPK',
            'Note Reference',
            'Updated At',
        ];

        $col = 1;
        foreach ($headers as $h) {
            $set($col++, 1, $h);
        }

        // Data rows
        $rowIdx = 2;
        foreach ($rows as $r) {
            $col = 1;

            $set($col++, $rowIdx, $r->asset_code);
            $set($col++, $rowIdx, $r->description);
            $set($col++, $rowIdx, $r->kode_asset_class_label);
            $set($col++, $rowIdx, $r->kode_location_label);
            $set($col++, $rowIdx, $r->kode_status_label);
            $set($col++, $rowIdx, $r->asset_owner_label);
            $set($col++, $rowIdx, $r->asset_user_label);

            $set($col++, $rowIdx, $r->price);
            $set($col++, $rowIdx, $r->quantity);
            $set($col++, $rowIdx, $r->vat_in);
            $set($col++, $rowIdx, $r->kode_uom_label);
            $set($col++, $rowIdx, $r->total);

            $set($col++, $rowIdx, $r->cap_date);
            $set($col++, $rowIdx, $r->period);
            $set($col++, $rowIdx, $r->depr_code);
            $set($col++, $rowIdx, $r->opening_balance);
            $set($col++, $rowIdx, $r->additions);
            $set($col++, $rowIdx, $r->transfers_in);
            $set($col++, $rowIdx, $r->transfers_out);
            $set($col++, $rowIdx, $r->disposals);
            $set($col++, $rowIdx, $r->adjustment_value);
            $set($col++, $rowIdx, $r->adjustment_depreciation);
            $set($col++, $rowIdx, $r->depr_expense);
            $set($col++, $rowIdx, $r->ending_balance);

            $set($col++, $rowIdx, $r->no_po_perjanjian_spk);
            $set($col++, $rowIdx, $r->nota_referensi);
            $set($col++, $rowIdx, $r->updated_at);

            $rowIdx++;
        }

        $fileName = 'Reporting_Asset_Depr_' . now()->format('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment;filename=\"{$fileName}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
