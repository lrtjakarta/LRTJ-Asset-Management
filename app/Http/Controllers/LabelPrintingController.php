<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SatoRfidPrinter;

class LabelPrintingController extends Controller
{
    /**
     * Halaman utama Printing Label:
     * - Re-use datatable `assets.datatable`
     * - User pilih asset yang mau dicetak labelnya
     */
    public function index()
    {
        return view('labels.printing');
    }

    /**
     * Terima daftar asset (uuid) lalu tampilkan halaman siap cetak.
     */
    public function print(Request $request)
    {
        $raw = $request->input('asset_uuids');
        $uuids = [];

        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $uuids = array_values(array_filter($decoded, function ($v) {
                    return is_string($v) && $v !== '';
                }));
            }
        }

        if (empty($uuids)) {
            return redirect()
                ->route('label.index')
                ->with('error', 'Tidak ada asset yang dipilih untuk dicetak.');
        }

        $items = DB::table('assets as a')
            ->leftJoin('assets_assignment as g', 'g.asset_uuid', 'a.uuid')
            ->leftJoin('master_location as ml', 'ml.kode', 'a.kode_location')
            ->leftJoin('assets_qr as q', function ($join) {
                $join->on('q.asset_uuid', '=', 'a.uuid')
                    ->whereNull('q.deleted_at')
                    ->where('q.is_active', true);
            })
            ->leftJoin('assets_rfid as r', function ($join) {
                $join->on('r.asset_uuid', '=', 'a.uuid')
                    ->whereNull('r.deleted_at')
                    ->where('r.is_active', true);
            })
            ->select(
                'a.uuid',
                'a.asset_code',
                'a.description',
                'a.kode_location',
                DB::raw("COALESCE(ml.name, '') as location_name"),
                'g.asset_owner',
                'g.asset_user',
                'q.image_path as qr_image_path',
                'r.epc as rfid_epc'
            )
            ->whereIn('a.uuid', $uuids)
            ->orderBy('a.asset_code')
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('label.index')
                ->with('error', 'Asset yang dipilih tidak ditemukan.');
        }

        return view('labels.print', [
            'items' => $items,
        ]);
    }
    public function printRfidLan(Request $request, SatoRfidPrinter $printer)
    {
        $raw = $request->input('asset_uuids');
        $uuids = [];

        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $uuids = array_values(array_filter($decoded, function ($v) {
                    return is_string($v) && $v !== '';
                }));
            }
        }

        if (empty($uuids)) {
            return back()->with('error', 'Tidak ada asset yang dipilih untuk print RFID.');
        }

        $size = $request->input('label_size', '100x40');
        if (!in_array($size, ['100x40', '60x40'], true)) {
            $size = '100x40';
        }

        try {
            $printer->printByAssetUuids($uuids, $size);

            \App\Models\AssetsRfid::whereIn('asset_uuid', $uuids)
                ->update(['encoded_at' => now()]);

            return back()->with('success', "Perintah print RFID ($size) sudah dikirim ke printer.");
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal mengirim ke printer: ' . $e->getMessage());
        }
    }
}
