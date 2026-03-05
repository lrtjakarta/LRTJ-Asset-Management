<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Support\Facades\Log;

class SatoRfidPrinter
{
    public function printByAssetUuids(array $assetUuids): void
    {
        try {
            $assets = Assets::with('rfids')
                ->whereIn('uuid', $assetUuids)
                ->get();

            if ($assets->isEmpty()) {
                Log::warning('SatoRfidPrinter: No assets found for printing.', [
                    'assetUuids' => $assetUuids
                ]);
                return; // jangan throw exception
            }

            $sbplAll = '';

            foreach ($assets as $asset) {
                $rfid = $asset->rfids; // hasOne

                // kalau belum punya tag, generate dari UUID (32 hex)
                if (! $rfid) {
                    $epc = strtoupper(str_replace('-', '', $asset->uuid));
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

        } catch (\Throwable $e) {
            // jangan crash request
            Log::error('RFID printing failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * SBPL per label (print + tulis EPC)
     */
    protected function buildLabelCommand(Assets $asset, AssetsRfid $rfid): string
    {
        $esc = "\x1B";
        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', $rfid->epc));

        $cmd  = $esc . "A";

        // Asset code
        $cmd .= $esc . "H0100" . $esc . "V0080" . $esc . "L0202";
        $cmd .= $esc . "X" . $asset->asset_code . "\r\n";

        // Description
        $desc = mb_substr($asset->description ?? '', 0, 30);
        $cmd .= $esc . "H0100" . $esc . "V0140" . $esc . "L0101";
        $cmd .= $esc . "X" . $desc . "\r\n";

        // EPC WRITE
        $cmd .= $esc . "IP0" . $epc . "\r\n";

        $cmd .= $esc . "Q1";
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
            Log::error("RFID printer unreachable", [
                'host' => $host,
                'port' => $port,
                'errno' => $errno,
                'errstr' => $errstr
            ]);

            return; // jangan throw exception
        }

        fwrite($fp, $data);
        fclose($fp);
    }
}