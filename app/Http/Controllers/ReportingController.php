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
    protected function canReadReporting(): bool
    {
        $u = auth()->user();
        return $u && method_exists($u, 'hasAction')
            ? $u->hasAction('REPORTING', 'R')
            : true;
    }
    protected function buildAssetDeprQuery(Request $request)
    {
        $period = $request->input('period'); // YYYY-MM-01 or null

        $q = DB::table('assets as a')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_value as v', 'v.asset_uuid', '=', 'a.uuid')
            ->leftJoin('assets_document as d', 'd.asset_uuid', '=', 'a.uuid')
            ->whereNull('a.deleted_at');


        if ($period) {
            $q->leftJoin('assets_depr_ledger_monthly as l', function ($join) use ($period) {
                $join->on('l.asset_uuid', '=', 'a.uuid')
                    ->where('l.period', '=', $period);
            });

            $q->whereNotNull('l.asset_uuid');
        } else {
            $latestLedger = DB::raw("(
            SELECT DISTINCT ON (asset_uuid)
                asset_uuid,
                period,
                depr_code,
                opening_balance,
                additions,
                transfers_in,
                transfers_out,
                disposals,
                adjustment_value,
                adjustment_depreciation,
                accumulated_depr_end,
                depr_expense,
                ending_balance,
                updated_at
            FROM assets_depr_ledger_monthly
            ORDER BY asset_uuid, period DESC, updated_at DESC
        ) as l");

            $q->leftJoin($latestLedger, 'l.asset_uuid', '=', 'a.uuid');
        }

        if ($vFilter = $request->input('asset_class')) {
            $q->where('a.kode_asset_class', $vFilter);
        }

        if ($vFilter = $request->input('transaction')) {
            $q->whereExists(function ($sub) use ($vFilter) {
                $sub->selectRaw('1')
                    ->from('master_asset_class as mac')
                    ->whereColumn('mac.kode', 'a.kode_asset_class')
                    ->where('mac.kode_transaction', $vFilter);
            });
        }

        if ($vFilter = $request->input('location')) {
            $q->where('a.kode_location', $vFilter);
        }

        if ($vFilter = $request->input('status')) {
            $q->where('a.kode_status', $vFilter);
        }

        if ($vFilter = $request->input('owner')) {
            $q->where('g.asset_owner', $vFilter);
        }

        if ($vFilter = $request->input('user')) {
            $q->where('g.asset_user', $vFilter);
        }

        if ($vFilter = $request->input('maintenance')) {
            $q->where('g.asset_maintenance', $vFilter);
        }

        if ($vFilter = $request->input('sumber')) {
            $q->where('a.kode_sumber', $vFilter);
        }

        if ($assetQ = trim((string) $request->input('asset_q', ''))) {
            $q->where(function ($w) use ($assetQ) {
                $w->where('a.asset_code', 'ilike', "%{$assetQ}%")
                    ->orWhere('a.description', 'ilike', "%{$assetQ}%");
            });
        }

        if ($vFilter = $request->input('cap_from')) {
            $q->whereDate('v.capitalization_date', '>=', $vFilter);
        }

        if ($vFilter = $request->input('cap_to')) {
            $q->whereDate('v.capitalization_date', '<=', $vFilter);
        }

        $q->select([
            'a.uuid',
            'a.asset_code',
            'a.description',
            'a.kode_asset_class',
            'a.kode_location',
            'a.kode_status',
            'a.kode_sumber',

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
            'l.accumulated_depr_end',
            'l.depr_expense',
            'l.ending_balance',

            'd.no_po_perjanjian_spk',
            'd.nota_referensi',
        ]);

        $q->addSelect([
            DB::raw("
            COALESCE(
                (
                    SELECT a.kode_location || ' - ' || ml.name
                    FROM master_location ml
                    WHERE ml.kode = a.kode_location
                    LIMIT 1
                ),
                a.kode_location
            ) AS kode_location_label
        "),

            DB::raw("
            COALESCE(
                (
                    SELECT a.kode_asset_class || ' - ' || mac.name
                    FROM master_asset_class mac
                    WHERE mac.kode = a.kode_asset_class
                    LIMIT 1
                ),
                a.kode_asset_class
            ) AS kode_asset_class_label
        "),

            DB::raw("
            COALESCE(
                (
                    SELECT a.kode_status || ' - ' || ms.name
                    FROM master_status ms
                    WHERE ms.kode = a.kode_status
                    LIMIT 1
                ),
                a.kode_status
            ) AS kode_status_label
        "),

            DB::raw("
            COALESCE(
                (
                    SELECT v.kode_uom || ' - ' || mu.name
                    FROM master_uom mu
                    WHERE mu.kode = v.kode_uom
                    LIMIT 1
                ),
                v.kode_uom
            ) AS kode_uom_label
        "),

            DB::raw("
            COALESCE(
                (
                    SELECT a.kode_sumber || ' - ' || msrc.name
                    FROM master_sumber msrc
                    WHERE msrc.kode = a.kode_sumber
                    LIMIT 1
                ),
                a.kode_sumber
            ) AS kode_sumber_label
        "),

            DB::raw("
            COALESCE(
                (
                    SELECT g.asset_owner || ' - ' || ou.department
                    FROM master_user_code ou
                    WHERE ou.kode = g.asset_owner
                    LIMIT 1
                ),
                g.asset_owner
            ) AS asset_owner_label
        "),

            DB::raw("
            COALESCE(
                (
                    SELECT g.asset_user || ' - ' || uu.department
                    FROM master_user_code uu
                    WHERE uu.kode = g.asset_user
                    LIMIT 1
                ),
                g.asset_user
            ) AS asset_user_label
        "),

            DB::raw("
            COALESCE(
                (
                    SELECT g.asset_maintenance || ' - ' || muw.department
                    FROM master_user_code muw
                    WHERE muw.kode = g.asset_maintenance
                    LIMIT 1
                ),
                g.asset_maintenance
            ) AS asset_maintenance_label
        "),

            DB::raw("
            COALESCE(v.total, 0) - COALESCE(l.accumulated_depr_end, 0) AS last_net_book_value
        "),

            DB::raw("
            to_char(
                COALESCE(l.updated_at, a.updated_at) AT TIME ZONE 'Asia/Jakarta',
                'YYYY-MM-DD\"T\"HH24:MI:SS'
            ) AS updated_at
        "),
        ]);

        return $q;
    }
    protected function applyGlobalSearch($query, string $search): void
    {
        $like = "%{$search}%";

        $query->where(function ($w) use ($like) {
            $w->where('a.asset_code', 'ilike', $like)
                ->orWhere('a.description', 'ilike', $like)
                ->orWhere('a.kode_asset_class', 'ilike', $like)
                ->orWhere('a.kode_location', 'ilike', $like)
                ->orWhere('a.kode_status', 'ilike', $like)
                ->orWhere('a.kode_sumber', 'ilike', $like)
                ->orWhere('g.asset_owner', 'ilike', $like)
                ->orWhere('g.asset_user', 'ilike', $like)
                ->orWhere('g.asset_maintenance', 'ilike', $like)
                ->orWhere('l.depr_code', 'ilike', $like)
                ->orWhere('d.no_po_perjanjian_spk', 'ilike', $like)
                ->orWhere('d.nota_referensi', 'ilike', $like)

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_location as ml')
                        ->whereColumn('ml.kode', 'a.kode_location')
                        ->where('ml.name', 'ilike', $like);
                })

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_asset_class as mac')
                        ->whereColumn('mac.kode', 'a.kode_asset_class')
                        ->where('mac.name', 'ilike', $like);
                })

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_status as ms')
                        ->whereColumn('ms.kode', 'a.kode_status')
                        ->where('ms.name', 'ilike', $like);
                })

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_uom as mu')
                        ->whereColumn('mu.kode', 'v.kode_uom')
                        ->where('mu.name', 'ilike', $like);
                })

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_sumber as msrc')
                        ->whereColumn('msrc.kode', 'a.kode_sumber')
                        ->where('msrc.name', 'ilike', $like);
                })

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_user_code as ou')
                        ->whereColumn('ou.kode', 'g.asset_owner')
                        ->where('ou.department', 'ilike', $like);
                })

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_user_code as uu')
                        ->whereColumn('uu.kode', 'g.asset_user')
                        ->where('uu.department', 'ilike', $like);
                })

                ->orWhereExists(function ($sub) use ($like) {
                    $sub->selectRaw('1')
                        ->from('master_user_code as muw')
                        ->whereColumn('muw.kode', 'g.asset_maintenance')
                        ->where('muw.department', 'ilike', $like);
                });
        });
    }

    public function index()
    {
        abort_unless($this->canReadReporting(), 403);
        return view('reporting.reporting');
    }

    public function datatableAssetDepr(Request $request)
    {
        if (!$this->canReadReporting()) {
            return DataTables::of(collect())->toJson();
        }

        $q = $this->buildAssetDeprQuery($request);

        return DataTables::of($q)
            ->filter(function ($query) use ($request) {
                $search = trim((string) data_get($request->input('search', []), 'value', ''));
                if ($search !== '') {
                    $this->applyGlobalSearch($query, $search);
                }
            }, true)
            ->make(true);
    }

    public function exportAssetDepr(Request $request)
    {
        abort_unless($this->canReadReporting(), 403);

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
            'Depr Date',
            'Depr Code',
            'Opening Balance',
            'Additions',
            'Transfer In',
            'Transfer Out',
            'Disposals',
            'Adjustment Value',
            'Adjustment Depr',
            'Accumulated Depreciation',
            'Net Book Value',
            'No PO/Perjanjian/SPK',
            'Note Reference',
            'Updated At',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporting');

        // Header row
        foreach ($headers as $colIdx => $label) {
            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($colIdx + 1) . '1',
                $label
            );
        }
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);

        $rowIdx = 2;
        $this->buildAssetDeprQuery($request)
            ->orderBy('a.asset_code')
            ->chunk(500, function ($rows) use ($sheet, &$rowIdx) {
                foreach ($rows as $r) {
                    $data = [
                        $r->asset_code,
                        $r->description,
                        $r->kode_asset_class_label,
                        $r->kode_location_label,
                        $r->kode_status_label,
                        $r->asset_owner_label,
                        $r->asset_user_label,
                        $r->price,
                        $r->quantity,
                        $r->vat_in,
                        $r->kode_uom_label,
                        $r->total,
                        $r->cap_date,
                        $r->period,
                        $r->depr_code,
                        $r->opening_balance,
                        $r->additions,
                        $r->transfers_in,
                        $r->transfers_out,
                        $r->disposals,
                        $r->adjustment_value,
                        $r->adjustment_depreciation,
                        $r->accumulated_depr_end,
                        $r->last_net_book_value,
                        $r->no_po_perjanjian_spk,
                        $r->nota_referensi,
                        $r->updated_at,
                    ];

                    foreach ($data as $colIdx => $value) {
                        $sheet->setCellValue(
                            Coordinate::stringFromColumnIndex($colIdx + 1) . $rowIdx,
                            $value ?? ''
                        );
                    }
                    $rowIdx++;
                }
            });

        $tmpFile  = tempnam(sys_get_temp_dir(), 'asset_depr_') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);
        $writer->save($tmpFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        $fileName = 'Reporting_Asset_Depr_' . now()->format('Ymd_His') . '.xlsx';
        $fileSize = filesize($tmpFile);

        return new StreamedResponse(function () use ($tmpFile) {
            $handle = fopen($tmpFile, 'rb');
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
            @unlink($tmpFile);
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Content-Length'      => $fileSize,
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
