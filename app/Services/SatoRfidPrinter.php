<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Support\Facades\Log;

class SatoRfidPrinter
{
    public function printByAssetUuids(array $assetUuids): void
    {
        $assets = Assets::query()
            ->with('rfids')
            ->whereIn('uuid', $assetUuids)
            ->get();

        if ($assets->isEmpty()) {
            throw new \RuntimeException('No assets found for printing.');
        }

        $sbplAll = '';

        foreach ($assets as $asset) {
            $rfid = $asset->rfids instanceof \Illuminate\Support\Collection
                ? $asset->rfids->first()
                : $asset->rfids;

            if (! $rfid) {
                $epc = strtoupper(str_replace('-', '', (string) $asset->uuid));
                $epc = preg_replace('/[^0-9A-F]/', '', $epc);
                $epc = substr(str_pad($epc, 32, '0', STR_PAD_LEFT), -32);

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

    protected function buildLabelCommand(Assets $asset, AssetsRfid $rfid): string
    {
        $esc  = "\x1B";
        $crlf = "\r\n";

        // EPC 32 HEX
        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', (string) $rfid->epc));
        $epc = substr(str_pad($epc, 32, '0', STR_PAD_LEFT), -32);

        $assetCode = (string) ($asset->asset_code ?? '');
        $desc      = mb_substr((string) ($asset->description ?? ''), 0, 40);
        $location  = mb_substr((string) ($asset->location_name ?? ''), 0, 40); // sesuaikan field kamu

        // ===== START JOB =====
        $cmd  = $esc . "A";

        // (Optional) set print mode / charset (kalau mau persis nice label)
        // $cmd .= $esc . "CS6"; // kadang dipakai untuk charset

        // ===== RFID WRITE =====
        // CL4NX style: IP0e:h,epc:<HEX>,fsw:1;
        // fsw:1 -> force single write (umum dipakai)
        $cmd .= $esc . "IP0e:h,epc:" . $epc . ",fsw:1;" . $crlf;

        // ===== QR (optional) =====
        // Kalau mau QR UUID seperti nice label (DN0036,<uuid> + 2D30...)
        // NOTE: Ini contoh; posisi & ukuran silakan tweak.
        $uuid = strtolower((string) $asset->uuid);
        $cmd .= $esc . "H0036" . $esc . "V00026";
        $cmd .= $esc . "2D30,L,06,1,0";
        $cmd .= $esc . "DN0036," . $uuid . $crlf;

        // ===== TEXT PRINT =====
        // Asset code (lebih besar)
        $cmd .= $esc . "H0036" . $esc . "V00144" . $esc . "L0202";
        $cmd .= $esc . "X" . $assetCode . $crlf;

        // Desc
        $cmd .= $esc . "H0036" . $esc . "V00175" . $esc . "L0101";
        $cmd .= $esc . "X" . $desc . $crlf;

        // Location
        $cmd .= $esc . "H0036" . $esc . "V00206" . $esc . "L0101";
        $cmd .= $esc . "X" . "Lokasi: " . $location . $crlf;

        // RFID text (buat verifikasi visual)
        $cmd .= $esc . "H0036" . $esc . "V00237" . $esc . "L0101";
        $cmd .= $esc . "X" . "RFID: " . $epc . $crlf;

        // Qty
        $cmd .= $esc . "Q1";

        // END JOB
        $cmd .= $esc . "Z";

        return $cmd;
    }

    protected function sendToPrinter(string $data): void
    {
        $cfg     = config('printing.sato_rfid');
        $host    = $cfg['host'] ?? '192.168.18.51';
        $port    = (int) ($cfg['port'] ?? 9100);
        $timeout = (int) ($cfg['timeout'] ?? 5);

        $errno  = 0;
        $errstr = '';

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (! $fp) {
            Log::error("SatoRfidPrinter: cannot connect to {$host}:{$port}", compact('errno', 'errstr'));
            throw new \RuntimeException("Cannot connect to RFID printer: {$errstr} ({$errno})");
        }

        stream_set_timeout($fp, $timeout);

        // write all bytes (anti kepotong)
        $len = strlen($data);
        $off = 0;
        while ($off < $len) {
            $w = fwrite($fp, substr($data, $off));
            if ($w === false) {
                fclose($fp);
                throw new \RuntimeException("Failed writing to printer socket.");
            }
            $off += $w;
        }

        fclose($fp);
    }
}