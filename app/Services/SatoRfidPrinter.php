<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Support\Facades\Log;

class SatoRfidPrinter
{
    public function printByAssetUuids(array $assetUuids): void
    {
        $assets = Assets::with('rfids')
            ->whereIn('uuid', $assetUuids)
            ->get();

        if ($assets->isEmpty()) {
            throw new \RuntimeException('No assets found for printing.');
        }

        $sbplAll = '';

        foreach ($assets as $asset) {
            $rfid = $asset->rfids; // hasOne

            // kalau belum punya tag, generate dari UUID (32 hex)
            if (! $rfid) {
                $epc = strtoupper(str_replace('-', '', $asset->uuid));
                // pastikan hanya HEX
                $epc = preg_replace('/[^0-9A-F]/', '', $epc);

                $rfid = AssetsRfid::create([
                    'asset_uuid' => $asset->uuid,
                    'epc'        => $epc,
                    'tag_type'   => 'UHF',
                    'is_active'  => true,
                ]);
            }

            $sbplAll .= $this->buildLabelCommand($asset, $rfid);
        }

        $this->sendToPrinter($sbplAll);
    }

    /**
     * SBPL per label (print + tulis EPC).
     * Contoh ini pakai "old" EPC write: <ESC>IP0xxxxxxxx...
     * Kalau nanti sudah dapat SBPL dari SATO All-In-One, tinggal
     * ganti isi function ini sesuai template yang sudah terbukti jalan.
     */
    protected function buildLabelCommand(Assets $asset, AssetsRfid $rfid): string
    {
        $esc = "\x1B";
        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', $rfid->epc));

        // --- MULAI FORMAT ---
        $cmd  = $esc . "A";

        // Asset code (font besar sedikit)
        $cmd .= $esc . "H0100" . $esc . "V0080" . $esc . "L0202";
        $cmd .= $esc . "X" . $asset->asset_code . "\r\n";

        // Description (dipotong biar muat)
        $desc = mb_substr($asset->description ?? '', 0, 30);
        $cmd .= $esc . "H0100" . $esc . "V0140" . $esc . "L0101";
        $cmd .= $esc . "X" . $desc . "\r\n";

        // *** EPC WRITE ***
        // Old format: <ESC>IP0 + EPC_HEX
        // Contoh dari manual:
        //   <ESC>A
        //   ...
        //   <ESC>IP08000000040000001
        //   <ESC>Q1
        //   <ESC>Z
        $cmd .= $esc . "IP0" . $epc . "\r\n";

        // Qty 1 label
        $cmd .= $esc . "Q1";
        // END
        $cmd .= $esc . "Z";

        return $cmd;
    }

    protected function sendToPrinter(string $data): void
    {
        $cfg     = config('printing.sato_rfid');
        $host    = $cfg['host'] ?? '127.0.0.1';
        $port    = (int) ($cfg['port'] ?? 9100);
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
