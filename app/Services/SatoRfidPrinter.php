<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SatoRfidPrinter
{
    public function printByAssetUuids(array $assetUuids): void
    {
        // load asset + relasi rfid
        $assets = Assets::with('rfids')
            ->whereIn('uuid', $assetUuids)
            ->get();

        if ($assets->isEmpty()) {
            throw new \RuntimeException('No assets found for printing.');
        }

        $sbplAll = '';

        foreach ($assets as $asset) {
            $rfid = $asset->rfids; // hasOne

            // kalau belum ada RFID, generate baru dari UUID
            if (! $rfid) {
                $epc = strtoupper(str_replace('-', '', $asset->uuid));

                $rfid = AssetsRfid::create([
                    'asset_uuid' => $asset->uuid,
                    'epc'        => $epc,
                    'tag_type'   => 'UHF', // yang dipakai Sato
                    'is_active'  => true,
                ]);
            }

            $sbplAll .= $this->buildLabelCommand($asset, $rfid);
        }

        $this->sendToPrinter($sbplAll);
    }

    /**
     * Build SBPL string untuk 1 label (1 asset + 1 tag).
     * NOTE: bagian command-nya sesuaikan
     * dari manual / All-In-One Tool yang sudah WORK.
     */
    protected function buildLabelCommand(Assets $asset, AssetsRfid $rfid): string
    {
        $esc = "\x1B"; // ESC character
        $epc = strtoupper($rfid->epc);

        // TODO: ganti isi $cmd sesuai SBPL real

        $cmd  = $esc . "A";  // start format

        // tulis asset code di label
        // Hxxxx = horizontal, Vxxxx = vertical, L/F = font, X = print text
        $cmd .= $esc . "H0100" . $esc . "V0100" . $esc . "L0202";
        $cmd .= $esc . "X" . $asset->asset_code . "\r\n";

        // tulis description di baris bawah
        $cmd .= $esc . "H0100" . $esc . "V0200" . $esc . "L0101";
        $cmd .= $esc . "X" . mb_substr($asset->description ?? '', 0, 30) . "\r\n";

        // *** RFID EPC WRITE ***
        // Sintaks ambil dari sample Sato (<ESC>IP0 + EPC atau sejenis).
        // (contoh, HARUS kamu sesuaikan):
        $cmd .= $esc . "IP0" . $epc . "\r\n";

        // Print quantity 1
        $cmd .= $esc . "Q1";
        $cmd .= $esc . "Z"; // end format

        return $cmd;
    }

    protected function sendToPrinter(string $data): void
    {
        $cfg   = config('printing.sato_rfid');
        $host  = $cfg['host'] ?? '127.0.0.1';
        $port  = (int) ($cfg['port'] ?? 9100);
        $timeout = (int) ($cfg['timeout'] ?? 5);

        $errno  = 0;
        $errstr = '';

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if (! $fp) {
            Log::error("SatoRfidPrinter: cannot connect to {$host}:{$port}", [
                'errno'  => $errno,
                'errstr' => $errstr,
            ]);
            throw new \RuntimeException("Cannot connect to RFID printer: {$errstr} ({$errno})");
        }

        fwrite($fp, $data);
        fclose($fp);
    }
}
