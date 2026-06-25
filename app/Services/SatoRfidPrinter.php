<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SatoRfidPrinter
{
    private int $dpi = 203;

    /**
     * Print RFID label via LAN (raw SBPL) for asset UUID list.
     *
     * @param array<int, string> $assetUuids
     * @param string $size '100x40' or '60x40'
     * @param bool $withRfid Whether to include RFID encode command
     */
    public function printByAssetUuids(array $assetUuids, string $size = '100x40', bool $withRfid = true): void
    {
        $assets = Assets::query()
            ->with(['rfids', 'assignment'])
            ->whereIn('uuid', $assetUuids)
            ->get();

        if ($assets->isEmpty()) {
            throw new \RuntimeException('No assets found.');
        }

        [$wDots, $hDots] = match ($size) {
            '60x40' => [$this->mmToDots(60), $this->mmToDots(40)],
            default => [$this->mmToDots(100), $this->mmToDots(40)],
        };

        $sbplAll = $this->buildInitBlock($wDots, $hDots);

        foreach ($assets as $asset) {
            $rfid = $this->resolveOrCreateRfid($asset);

            $sbplAll .= $this->buildPrintBlock(
                $asset,
                $rfid,
                $wDots,
                $hDots,
                $size,
                $withRfid
            );
        }

        $this->sendToPrinter($sbplAll);
    }

    private function resolveOrCreateRfid(Assets $asset): AssetsRfid
    {
        $rfid = $asset->rfids instanceof Collection
            ? $asset->rfids->first()
            : $asset->rfids;

        if ($rfid instanceof AssetsRfid) {
            return $rfid;
        }

        $epc = strtoupper(str_replace('-', '', (string) $asset->uuid));
        $epc = preg_replace('/[^0-9A-F]/', '', $epc) ?? '';
        $epc = substr(str_pad($epc, 32, '0', STR_PAD_LEFT), -32);

        return AssetsRfid::create([
            'asset_uuid' => $asset->uuid,
            'epc'        => $epc,
            'tag_type'   => 'UHF',
            'is_active'  => true,
        ]);
    }

    private function mmToDots(float $mm): int
    {
        return (int) round($mm * $this->dpi / 25.4);
    }

    /**
     * Build asset description:
     * - remove special characters/symbols
     * - normalize whitespace
     * - truncate safely
     */
    private function sanitizeDescForLabel(?string $text, int $maxLen = 20): string
    {
        $text = (string) ($text ?? '');

        $text = preg_replace('/[^\P{C}\n]+/u', '', $text) ?? '';
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);

        return mb_substr($text, 0, $maxLen);
    }

    protected function buildInitBlock(int $wDots, int $hDots): string
    {
        $esc = "\x1B";
        $stx = "\x02";
        $etx = "\x03";

        $maxH = max(0, $wDots - 1);
        $maxV = max(0, $hDots);

        return $stx
            . $esc . 'A'
            . $esc . 'A3V+00000H+0000'
            . $esc . 'CS6'
            . $esc . '#F5'
            . $esc . 'A1V' . str_pad((string) $maxV, 5, '0', STR_PAD_LEFT)
            . 'H' . str_pad((string) $maxH, 4, '0', STR_PAD_LEFT)
            . $esc . 'Z'
            . $etx;
    }

    protected function buildPrintBlock(
        Assets $asset,
        AssetsRfid $rfid,
        int $wDots,
        int $hDots,
        string $size,
        bool $withRfid = true
    ): string {
        $esc = "\x1B";
        $stx = "\x02";
        $etx = "\x03";

        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', (string) $rfid->epc) ?? '');
        $epc = substr(str_pad($epc, 32, '0', STR_PAD_LEFT), -32);

        $uuid         = strtolower((string) $asset->uuid);
        $assetCode    = (string) ($asset->asset_code ?? '');
        $ownerCode    = (string) ($asset->assignment?->asset_owner ?? '');
        $locationCode = (string) ($asset->kode_location ?? '');
        $header       = 'PT. LRT JAKARTA';
        $ttf          = 'SATO0.ttf';

        $desc = $size !== '60x40'
            ? $this->sanitizeDescForLabel($asset->description, 36)
            : $this->sanitizeDescForLabel($asset->description, 20);

        $makeTtfText = function (
            string $H,
            string $V,
            string $text,
            string $h = '024',
            string $w = '028'
        ) use ($esc, $ttf): string {
            return $esc . '%0'
                . $esc . "H{$H}"
                . $esc . "V{$V}"
                . $esc . 'P02'
                . $esc . "RH0,{$ttf},0,{$h},{$w}," . $text;
        };

        $cmd = $stx
            . $esc . 'A'
            . $esc . 'PS'
            . $esc . 'WKLabel';

        if ($withRfid) {
            $cmd .= $esc . 'IP0' . 'e:h,epc:' . $epc . ';';
        }

        if ($size !== '60x40') {
            $cmd .= $esc . '%0'
                . $esc . 'H0036'
                . $esc . 'V00060'
                . $esc . 'P02'
                . $esc . 'RH0,SATO0.ttf,0,040,040,' . $header;

            $cmd .= $esc . '%0'
                . $esc . 'H0599'
                . $esc . 'V00026'
                . $esc . '2D30,L,06,1,0'
                . $esc . 'DN0036,' . $uuid;

            $cmd .= $makeTtfText('0036', '00113', $assetCode);
            $cmd .= $makeTtfText('0036', '00144', $desc);
            $cmd .= $makeTtfText('0036', '00177', 'Owner Asset: ' . $ownerCode);
            $cmd .= $makeTtfText('0036', '00206', 'Lokasi: ' . $locationCode);
            $cmd .= $makeTtfText('0036', '00237', 'RFID: ' . $epc);
        } else {
            $cmd .= $esc . '%0'
                . $esc . 'H0020'
                . $esc . 'V00045'
                . $esc . 'P02'
                . $esc . 'RH0,SATO0.ttf,0,032,032,' . $header;

            $qrModule = 4;
            $qrEstimateSize = 170;
            $qrH = max(20, $wDots - $qrEstimateSize);
            $qrV = 20;

            $cmd .= $esc . '%0'
                . $esc . 'H' . str_pad((string) $qrH, 4, '0', STR_PAD_LEFT)
                . $esc . 'V' . str_pad((string) $qrV, 5, '0', STR_PAD_LEFT)
                . $esc . '2D30,L,' . str_pad((string) $qrModule, 2, '0', STR_PAD_LEFT) . ',1,0'
                . $esc . 'DN0036,' . $uuid;

            $cmd .= $makeTtfText('0020', '00105', $assetCode, '022', '024');
            $cmd .= $makeTtfText('0020', '00135', $desc, '022', '024');
            $cmd .= $makeTtfText('0020', '00168', 'Owner: ' . $ownerCode, '022', '024');
            $cmd .= $makeTtfText('0020', '00196', 'Lokasi: ' . $locationCode, '022', '024');

            $shortEpc = substr($epc, 0, 8) . '...' . substr($epc, -8);
            $cmd .= $makeTtfText('0020', '00228', 'RFID: ' . $shortEpc, '022', '024');
        }

        $cmd .= $esc . 'Q1'
            . $esc . 'Z'
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

        Log::info('Printing to SATO', [
            'host' => $host,
            'port' => $port,
            'bytes' => strlen($data),
        ]);

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (! $fp) {
            Log::error("SatoRfidPrinter: cannot connect to {$host}:{$port}", compact('errno', 'errstr'));
            throw new \RuntimeException("Cannot connect to RFID printer: {$errstr} ({$errno})");
        }

        stream_set_timeout($fp, $timeout);

        $len = strlen($data);
        $off = 0;

        while ($off < $len) {
            $chunk = substr($data, $off);
            $w = fwrite($fp, $chunk);

            if ($w === false) {
                fclose($fp);
                throw new \RuntimeException("Failed writing to printer socket.");
            }

            $off += $w;
        }

        fflush($fp);
        usleep(300000);

        Log::info('SATO print sent', [
            'host' => $host,
            'port' => $port,
            'bytes_sent' => $off,
            'bytes_total' => $len,
        ]);

        fclose($fp);
    }
}
