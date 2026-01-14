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
            ->with([
                'rfids',
                'location',
                'assignment.owner',
            ])
            ->whereIn('uuid', $assetUuids)
            ->get();
        if ($assets->isEmpty()) throw new \RuntimeException('No assets found.');

        $sbplAll = $this->buildInitBlock();

        foreach ($assets as $asset) {
            $rfid = $asset->rfids instanceof \Illuminate\Support\Collection ? $asset->rfids->first() : $asset->rfids;

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

            $sbplAll .= $this->buildPrintBlock($asset, $rfid);
        }

        $this->sendToPrinter($sbplAll);
    }
    protected function buildInitBlock(): string
    {
        $esc = "\x1B";
        $stx = "\x02";
        $etx = "\x03";

        // persis seperti Label.prn block pertama
        return $stx
            . $esc . "A"
            . $esc . "A3V+00000H+0000"
            . $esc . "CS6"
            . $esc . "#F5"
            . $esc . "A1V00320H0799"
            . $esc . "Z"
            . $etx;
    }

    protected function buildPrintBlock(Assets $asset, AssetsRfid $rfid): string
    {
        $esc = "\x1B";
        $stx = "\x02";
        $etx = "\x03";

        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', (string) $rfid->epc));
        $epc = substr(str_pad($epc, 32, '0', STR_PAD_LEFT), -32);

        $uuid      = strtolower((string) $asset->uuid);
        $assetCode = (string) ($asset->asset_code ?? '');
        $desc      = mb_substr((string) ($asset->description ?? ''), 0, 40);

        // ✅ hanya kode (aman)
        $ownerCode    = (string) ($asset->assignment?->asset_owner ?? ''); // contoh: AKP
        $locationCode = (string) ($asset->kode_location ?? '');           // contoh: L001

        $ttf = 'SATO0.ttf';
        $h = '024';
        $w = '028';

        $ttfText = function (string $H, string $V, string $text) use ($esc, $ttf, $h, $w) {
            return $esc . "%0"
                . $esc . "H{$H}"
                . $esc . "V{$V}"
                . $esc . "P02"
                . $esc . "RH0,{$ttf},0,{$h},{$w}," . $text;
        };

        $cmd = $stx
            . $esc . "A"
            . $esc . "PS"
            . $esc . "WKLabel";

        // QR
        $cmd .= $esc . "%0"
            . $esc . "H0599"
            . $esc . "V00026"
            . $esc . "2D30,L,06,1,0"
            . $esc . "DN0036," . $uuid;

        // TEXT
        $cmd .= $ttfText('0036', '00113', $assetCode);
        $cmd .= $ttfText('0036', '00144', $desc);
        $cmd .= $ttfText('0036', '00177', 'Owner Asset: ' . $ownerCode);
        $cmd .= $ttfText('0036', '00206', 'Lokasi: ' . $locationCode);
        $cmd .= $ttfText('0036', '00237', 'RFID: ' . $epc);

        $cmd .= $esc . "Q1"
            . $esc . "Z"
            . $etx;

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
