<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\MasterAssetClass;
use App\Models\MasterLocation;
use App\Models\MasterStatus;
use App\Models\MasterSumber;
use App\Models\MasterTransaction;
use App\Models\MasterUOM;
use App\Models\MasterUserCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ExportController
{
    public function master_transaction_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MASTER_DATA', 'R'), 403);
        $rows = MasterTransaction::select(['uuid', 'kode', 'name', 'status', 'updated_at'])
            ->orderBy('kode')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master Company');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Status');
        $sheet->setCellValue('D1', 'Updated At');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row->kode);
            $sheet->setCellValue("B{$rowNum}", $row->name);
            $sheet->setCellValue("C{$rowNum}", $row->status ? 'Active' : 'Inactive');
            $sheet->setCellValue("D{$rowNum}", optional($row->updated_at)->format('Y-m-d H:i:s'));

            $rowNum++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $fileName = 'master_company_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function master_location_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MASTER_DATA', 'R'), 403);
        $rows = MasterLocation::select(['uuid', 'kode', 'name', 'status', 'updated_at'])
            ->orderBy('kode')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master Location');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Status');
        $sheet->setCellValue('D1', 'Updated At');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row->kode);
            $sheet->setCellValue("B{$rowNum}", $row->name);
            $sheet->setCellValue("C{$rowNum}", $row->status ? 'Active' : 'Inactive');
            $sheet->setCellValue("D{$rowNum}", optional($row->updated_at)->format('Y-m-d H:i:s'));

            $rowNum++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $fileName = 'master_location_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function master_uom_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MASTER_DATA', 'R'), 403);
        $rows = MasterUOM::select(['uuid', 'kode', 'name', 'status', 'updated_at'])
            ->orderBy('kode')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master UOM');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Status');
        $sheet->setCellValue('D1', 'Updated At');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row->kode);
            $sheet->setCellValue("B{$rowNum}", $row->name);
            $sheet->setCellValue("C{$rowNum}", $row->status ? 'Active' : 'Inactive');
            $sheet->setCellValue("D{$rowNum}", optional($row->updated_at)->format('Y-m-d H:i:s'));

            $rowNum++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $fileName = 'master_uom_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function master_sumber_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MASTER_DATA', 'R'), 403);
        $rows = MasterSumber::select(['uuid', 'kode', 'name', 'status', 'updated_at'])
            ->orderBy('kode')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master Sumber');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Status');
        $sheet->setCellValue('D1', 'Updated At');

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row->kode);
            $sheet->setCellValue("B{$rowNum}", $row->name);
            $sheet->setCellValue("C{$rowNum}", $row->status ? 'Active' : 'Inactive');
            $sheet->setCellValue("D{$rowNum}", optional($row->updated_at)->format('Y-m-d H:i:s'));

            $rowNum++;
        }
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $fileName = 'master_sumber_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function master_status_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MASTER_DATA', 'R'), 403);
        $rows = MasterStatus::select(['uuid', 'kode', 'name', 'type', 'status', 'updated_at'])
            ->orderBy('kode')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master Status');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Type');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Updated At');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row->kode);
            $sheet->setCellValue("B{$rowNum}", $row->name);
            $sheet->setCellValue("C{$rowNum}", $row->type);
            $sheet->setCellValue("D{$rowNum}", $row->status ? 'Active' : 'Inactive');
            $sheet->setCellValue("E{$rowNum}", optional($row->updated_at)->format('Y-m-d H:i:s'));

            $rowNum++;
        }
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $fileName = 'master_status_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function master_asset_class_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MASTER_DATA', 'R'), 403);
        $rows = MasterAssetClass::select(['uuid', 'kode', 'kode_transaction', 'name', 'status', 'updated_at'])
            ->orderBy('kode')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master Asset Class');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Name');
        $sheet->setCellValue('C1', 'Company');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Updated At');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $trxCache = [];

        $rowNum = 2;
        foreach ($rows as $row) {
            $trxCode = $row->kode_transaction;

            if (!isset($trxCache[$trxCode])) {
                $trx = MasterTransaction::where('kode', $trxCode)->first();
                $trxCache[$trxCode] = $trx
                    ? ($trx->kode . ' - ' . $trx->name)
                    : $trxCode;
            }

            $sheet->setCellValue("A{$rowNum}", $row->kode);
            $sheet->setCellValue("B{$rowNum}", $row->name);
            $sheet->setCellValue("C{$rowNum}", $trxCache[$trxCode]);
            $sheet->setCellValue("D{$rowNum}", $row->status ? 'Active' : 'Inactive');
            $sheet->setCellValue("E{$rowNum}", optional($row->updated_at)->format('Y-m-d H:i:s'));

            $rowNum++;
        }
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $fileName = 'master_asset_class_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function master_user_code_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MASTER_DATA', 'R'), 403);
        $rows = MasterUserCode::select(['uuid', 'kode', 'department', 'description', 'status', 'updated_at'])
            ->orderBy('kode')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Master User Code');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Department');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Updated At');

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNum}", $row->kode);
            $sheet->setCellValue("B{$rowNum}", $row->department);
            $sheet->setCellValue("C{$rowNum}", $row->description);
            $sheet->setCellValue("D{$rowNum}", $row->status ? 'Active' : 'Inactive');
            $sheet->setCellValue("E{$rowNum}", optional($row->updated_at)->format('Y-m-d H:i:s'));

            $rowNum++;
        }
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $fileName = 'master_user_code_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function assets_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('ASSETS', 'R'), 403);
        $q = DB::table('assets as a')
            ->leftJoin('assets_identifiers as i', 'i.asset_uuid', 'a.uuid')
            ->leftJoin('assets_assignment  as g', 'g.asset_uuid', 'a.uuid')
            ->leftJoin('assets_value       as v', 'v.asset_uuid', 'a.uuid')
            ->leftJoin('assets_document    as d', 'd.asset_uuid', 'a.uuid')
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
                'a.asset_code',
                DB::raw("CASE WHEN mac.name IS NULL THEN a.kode_asset_class ELSE a.kode_asset_class || ' - ' || mac.name END AS asset_class"),
                'a.asset_number_parent',
                'a.asset_number_child',
                'a.description',
                'a.upload_code',
                'i.asset_number_maximo',
                'i.asset_number_dynamic_365',
                'i.asset_number_internal',
                'i.alias',
                DB::raw("CASE WHEN ou.department  IS NULL THEN g.asset_owner      ELSE g.asset_owner         || ' - ' || ou.department  END AS asset_owner"),
                DB::raw("CASE WHEN uu.department  IS NULL THEN g.asset_user       ELSE g.asset_user          || ' - ' || uu.department  END AS asset_user"),
                DB::raw("CASE WHEN muw.department IS NULL THEN g.asset_maintenance ELSE g.asset_maintenance  || ' - ' || muw.department END AS asset_maintenance"),
                DB::raw("CASE WHEN ml.name  IS NULL THEN a.kode_location    ELSE a.kode_location    || ' - ' || ml.name  END AS location"),
                DB::raw("CASE WHEN ms.name  IS NULL THEN a.kode_status      ELSE a.kode_status      || ' - ' || ms.name  END AS status_label"),
                'v.price',
                'v.quantity',
                'v.vat_in',
                DB::raw("CASE WHEN mu.name  IS NULL THEN v.kode_uom         ELSE v.kode_uom         || ' - ' || mu.name  END AS uom_label"),
                'v.total',
                'v.useful_life_month',
                'v.useful_life_year',
                'd.no_po_perjanjian_spk',
                'd.nota_referensi',
                DB::raw("CASE WHEN msrc.name IS NULL THEN a.kode_sumber     ELSE a.kode_sumber      || ' - ' || msrc.name END AS sumber_label"),
                DB::raw("to_char(a.updated_at at time zone 'Asia/Jakarta','YYYY-MM-DD HH24:MI') as updated_at")
            );

        if ($assetClass = $request->get('asset_class')) {
            $q->where('a.kode_asset_class', $assetClass);
        }
        if ($transaction = $request->get('transaction')) {
            $q->where('mac.kode_transaction', $transaction);
        }
        if ($location = $request->get('location')) {
            $q->where('a.kode_location', $location);
        }
        if ($status = $request->get('status')) {
            $q->where('a.kode_status', $status);
        }
        if ($owner = $request->get('owner')) {
            $q->where('g.asset_owner', $owner);
        }
        if ($user = $request->get('user')) {
            $q->where('g.asset_user', $user);
        }
        if ($maintenance = $request->get('maintenance')) {
            $q->where('g.asset_maintenance', $maintenance);
        }
        if ($sumber = $request->get('sumber')) {
            $q->where('a.kode_sumber', $sumber);
        }
        if ($search = trim((string) $request->get('asset_q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('a.asset_code', 'ilike', "%{$search}%")
                    ->orWhere('a.description', 'ilike', "%{$search}%");
            });
        }

        $rows = $q->orderBy('a.asset_code')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'A1' => 'Asset Code',
            'B1' => 'Asset Class',
            'C1' => 'Parent',
            'D1' => 'Child',
            'E1' => 'Description',
            'F1' => 'Upload Code',
            'G1' => 'Asset Number Maximo',
            'H1' => 'Asset Number D365',
            'I1' => 'Asset Number Internal',
            'J1' => 'Alias',
            'K1' => 'Asset Owner',
            'L1' => 'Asset User',
            'M1' => 'Asset Maintenance',
            'N1' => 'Location',
            'O1' => 'Status',
            'P1' => 'Price',
            'Q1' => 'Quantity',
            'R1' => 'VAT In',
            'S1' => 'UOM',
            'T1' => 'Total',
            'U1' => 'Useful Life (Month)',
            'V1' => 'Useful Life (Year)',
            'W1' => 'No PO/Perjanjian/SPK',
            'X1' => 'Note Reference',
            'Y1' => 'Sumber',
            'Z1' => 'Updated At',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $rowNum = 2;
        foreach ($rows as $r) {
            $sheet->setCellValue("A{$rowNum}", $r->asset_code);
            $sheet->setCellValue("B{$rowNum}", $r->asset_class);
            $sheet->setCellValue("C{$rowNum}", $r->asset_number_parent);
            $sheet->setCellValue("D{$rowNum}", $r->asset_number_child);
            $sheet->setCellValue("E{$rowNum}", $r->description);
            $sheet->setCellValue("F{$rowNum}", $r->upload_code);
            $sheet->setCellValue("G{$rowNum}", $r->asset_number_maximo);
            $sheet->setCellValue("H{$rowNum}", $r->asset_number_dynamic_365);
            $sheet->setCellValue("I{$rowNum}", $r->asset_number_internal);
            $sheet->setCellValue("J{$rowNum}", $r->alias);
            $sheet->setCellValue("K{$rowNum}", $r->asset_owner);
            $sheet->setCellValue("L{$rowNum}", $r->asset_user);
            $sheet->setCellValue("M{$rowNum}", $r->asset_maintenance);
            $sheet->setCellValue("N{$rowNum}", $r->location);
            $sheet->setCellValue("O{$rowNum}", $r->status_label);
            $sheet->setCellValue("P{$rowNum}", $r->price);
            $sheet->setCellValue("Q{$rowNum}", $r->quantity);
            $sheet->setCellValue("R{$rowNum}", $r->vat_in);
            $sheet->setCellValue("S{$rowNum}", $r->uom_label);
            $sheet->setCellValue("T{$rowNum}", $r->total);
            $sheet->setCellValue("U{$rowNum}", $r->useful_life_month);
            $sheet->setCellValue("V{$rowNum}", $r->useful_life_year);
            $sheet->setCellValue("W{$rowNum}", $r->no_po_perjanjian_spk);
            $sheet->setCellValue("X{$rowNum}", $r->nota_referensi);
            $sheet->setCellValue("Y{$rowNum}", $r->sumber_label);
            $sheet->setCellValue("Z{$rowNum}", $r->updated_at);

            $rowNum++;
        }

        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'assets-' . now()->format('Ymd-His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function stock_opname_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('STOCK_OPN', 'R'), 403);

        $source    = $request->input('source');
        $tfType    = $request->input('tf_type');
        $dateFrom  = $request->input('date_from');
        $dateTo    = $request->input('date_to');
        $assetLike = $request->input('asset');
        $users     = $request->input('users');

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
            t.updated_at
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
            d.updated_at
        ")
            ->whereNull('d.deleted_at')
            ->where('d.kode_status', 'ACC');

        if ($source === 'transfer') {
            $union = $t;
        } elseif ($source === 'disposal') {
            $union = $d;
        } else {
            $union = $t->unionAll($d);
        }

        $q = DB::query()->fromSub($union, 'u');

        if ($tfType) {
            $q->where('source_type', 'transfer')->where('tf_type', $tfType);
        }
        if ($dateFrom) {
            $q->whereDate('updated_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $q->whereDate('updated_at', '<=', $dateTo);
        }
        if ($assetLike) {
            $q->where(function ($qq) use ($assetLike) {
                $qq->where('asset_code', 'ilike', "%{$assetLike}%")
                    ->orWhere('description', 'ilike', "%{$assetLike}%");
            });
        }
        if ($users) {
            $q->where('pic_request_uid', $users);
        }

        $rows = $q->orderBy('updated_at', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Opname');

        // Header row
        $sheet->setCellValue('A1', 'Asset Code');
        $sheet->setCellValue('B1', 'Asset Description');
        $sheet->setCellValue('C1', 'Transaction Number');
        $sheet->setCellValue('D1', 'Source');
        $sheet->setCellValue('E1', 'Type');
        $sheet->setCellValue('F1', 'Before');
        $sheet->setCellValue('G1', 'After');
        $sheet->setCellValue('H1', 'Note');
        $sheet->setCellValue('I1', 'Requester');
        $sheet->setCellValue('J1', 'Approver');
        $sheet->setCellValue('K1', 'Updated At');

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $r) {
            $source  = strtoupper($r->source_type);
            $type    = $r->source_type === 'transfer'
                ? strtoupper($r->tf_type)
                : 'DISPOSAL';

            $before  = $r->before_val;
            $after   = $r->after_val;

            $updated = $r->updated_at
                ? Carbon::parse($r->updated_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i')
                : '';

            $sheet->setCellValue("A{$rowNum}", $r->asset_code);
            $sheet->setCellValue("B{$rowNum}", $r->description);
            $sheet->setCellValue("C{$rowNum}", $r->code);
            $sheet->setCellValue("D{$rowNum}", $source);
            $sheet->setCellValue("E{$rowNum}", $type);
            $sheet->setCellValue("F{$rowNum}", $before);
            $sheet->setCellValue("G{$rowNum}", $after);
            $sheet->setCellValue("H{$rowNum}", $r->note);
            $sheet->setCellValue("I{$rowNum}", $r->pic_request_uid);
            $sheet->setCellValue("J{$rowNum}", $r->pic_approve_uid);
            $sheet->setCellValue("K{$rowNum}", $updated);

            $rowNum++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'stock_opname_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    public function movement_export(Request $request)
    {
        abort_unless($request->user()?->hasAction('MOVEMENT', 'R'), 403);

        $type        = trim((string) $request->input('type', ''));
        $status      = trim((string) $request->input('status', ''));
        $requester   = trim((string) $request->input('requester', ''));
        $updatedFrom = $request->input('updated_from');
        $updatedTo   = $request->input('updated_to');
        $createdFrom = $request->input('created_from');
        $createdTo   = $request->input('created_to');
        $assetQ      = trim((string) $request->input('asset_q', ''));

        $q = DB::table('assets_transfers as t')
            ->leftJoin('assets as a', 'a.uuid', '=', 't.asset_uuid')
            ->leftJoin('master_status as ms', 'ms.kode', '=', 't.kode_status')
            ->selectRaw("
            t.transfer_code,
            a.asset_code,
            a.description as asset_desc,
            t.type,
            COALESCE(t.before->>'value','') as before_val,
            COALESCE(t.after->>'value','')  as after_val,
            t.note,
            t.pic_request_uid,
            t.pic_approve_uid,
            t.kode_status,
            ms.name as status_name,
            t.file_name,
            t.created_at,
            t.updated_at
        ")
            ->whereNull('t.deleted_at');

        if ($type !== '') {
            $q->where('t.type', $type);
        }
        if ($status !== '') {
            $q->where('t.kode_status', $status);
        }
        if ($requester !== '') {
            $q->where('t.pic_request_uid', $requester);
        }
        if ($updatedFrom) {
            $q->whereDate('t.updated_at', '>=', $updatedFrom);
        }
        if ($updatedTo) {
            $q->whereDate('t.updated_at', '<=', $updatedTo);
        }
        if ($createdFrom) {
            $q->whereDate('t.created_at', '>=', $createdFrom);
        }
        if ($createdTo) {
            $q->whereDate('t.created_at', '<=', $createdTo);
        }
        if ($assetQ !== '') {
            $q->where(function ($w) use ($assetQ) {
                $w->where('a.asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('a.description', 'ilike', "%{$assetQ}%");
            });
        }
        $rows = $q->orderBy('t.updated_at', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movement');

        // header
        $sheet->setCellValue('A1', 'Transaction Number');
        $sheet->setCellValue('B1', 'Asset Code');
        $sheet->setCellValue('C1', 'Asset Description');
        $sheet->setCellValue('D1', 'Type');
        $sheet->setCellValue('E1', 'Before');
        $sheet->setCellValue('F1', 'After');
        $sheet->setCellValue('G1', 'Note');
        $sheet->setCellValue('H1', 'Requester');
        $sheet->setCellValue('I1', 'Approver');
        $sheet->setCellValue('J1', 'Status');
        $sheet->setCellValue('K1', 'File Name');
        $sheet->setCellValue('L1', 'Created At');
        $sheet->setCellValue('M1', 'Updated At');

        $sheet->getStyle('A1:M1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($rows as $r) {
            $statusLabel = $r->kode_status
                ? ($r->kode_status . ($r->status_name ? (' - ' . $r->status_name) : ''))
                : '';

            $created = $r->created_at
                ? Carbon::parse($r->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i')
                : '';

            $updated = $r->updated_at
                ? Carbon::parse($r->updated_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i')
                : '';

            $sheet->setCellValue("A{$rowNum}", $r->transfer_code);
            $sheet->setCellValue("B{$rowNum}", $r->asset_code);
            $sheet->setCellValue("C{$rowNum}", $r->asset_desc);
            $sheet->setCellValue("D{$rowNum}", strtoupper($r->type ?? ''));
            $sheet->setCellValue("E{$rowNum}", $r->before_val);
            $sheet->setCellValue("F{$rowNum}", $r->after_val);
            $sheet->setCellValue("G{$rowNum}", $r->note);
            $sheet->setCellValue("H{$rowNum}", $r->pic_request_uid);
            $sheet->setCellValue("I{$rowNum}", $r->pic_approve_uid);
            $sheet->setCellValue("J{$rowNum}", $statusLabel);
            $sheet->setCellValue("K{$rowNum}", $r->file_name);
            $sheet->setCellValue("L{$rowNum}", $created);
            $sheet->setCellValue("M{$rowNum}", $updated);

            $rowNum++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'movement_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
