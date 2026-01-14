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

            // kalau belum punya tag, generate dari UUID (32 hex)
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
        $esc = "\x1B";
        $stx = "\x02";
        $etx = "\x03";

        // ===== ambil EPC 32 HEX =====
        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', (string) $rfid->epc));
        $epc = substr(str_pad($epc, 32, '0', STR_PAD_LEFT), -32);

        // ===== data =====
        $uuid      = strtolower((string) $asset->uuid);
        $assetCode = (string) ($asset->asset_code ?? '');
        $desc      = mb_substr((string) ($asset->description ?? ''), 0, 40);
        $location  = mb_substr((string) ($asset->location_name ?? ''), 0, 40);

        // ===== font settings (ikut NiceLabel) =====
        $ttf = 'SATO0.ttf';
        $smallH = '024'; // tinggi font
        $smallW = '028'; // lebar font

        // kalau mau sebagian dibuat lebih gede, kamu bisa ganti misal:
        // $bigH = '038'; $bigW = '042';

        // helper: TTF text (sesuai PRN: %0 + H + V + P02 + RH0,ttf,0,h,w,text)
        $ttfText = function (string $H, string $V, string $text, string $h = null, string $w = null) use ($esc, $ttf, $smallH, $smallW) {
            $h = $h ?? $smallH;
            $w = $w ?? $smallW;

            // urutan mengikuti PRN:
            // ESC %0
            // ESC Hxxxx
            // ESC Vyyyyy
            // ESC P02
            // ESC RH0,SATO0.ttf,0,hhh,www,<text>
            return
                $esc . "%0" .
                $esc . "H{$H}" .
                $esc . "V{$V}" .
                $esc . "P02" .
                $esc . "RH0,{$ttf},0,{$h},{$w}," . $text;
        };

        // ===== START LABEL (ikut header PRN) =====
        $cmd = '';
        $cmd .= $stx;
        $cmd .= $esc . "A";
        $cmd .= $esc . "A3V+00000H+0000"; // <<< ORIENTASI (yang “hijau”)
        $cmd .= $esc . "CS6";
        $cmd .= $esc . "#F5";
        $cmd .= $esc . "PS";              // opsional, NiceLabel pakai
        $cmd .= $esc . "WKLabel";          // opsional, NiceLabel pakai

        // ===== QR (ikut PRN: H0599 V00026) =====
        $cmd .= $esc . "%0";
        $cmd .= $esc . "H0599" . $esc . "V00026";
        $cmd .= $esc . "2D30,L,06,1,0";
        $cmd .= $esc . "DN0036," . $uuid;

        // ===== TEXT (ikut PRN positions) =====
        $cmd .= $ttfText('0036', '00113', $assetCode);
        $cmd .= $ttfText('0036', '00144', $desc);
        // kalau kamu punya owner, tinggal ganti sumber datanya
        $cmd .= $ttfText('0036', '00177', 'Owner Asset: ' . (($asset->owner_name ?? '') ?: ''));
        $cmd .= $ttfText('0036', '00206', 'Lokasi: ' . $location);
        $cmd .= $ttfText('0036', '00237', 'RFID: ' . $epc);

        // ===== QTY + END =====
        $cmd .= $esc . "Q1";
        $cmd .= $esc . "Z";
        $cmd .= $etx;

        // (opsional) debug ke log biar gampang compare dgn NiceLabel
        // Log::info('SBPL bytes len=' . strlen($cmd));

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
