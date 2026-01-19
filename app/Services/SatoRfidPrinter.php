<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Support\Facades\Log;

class SatoRfidPrinter
{
    /**
     * DPI printer.
     * Template kamu sebelumnya: 100mm => H0799 (800 dots) sangat cocok 203dpi.
     * Kalau printer kamu 305dpi, ganti ke 305.
     */
    private int $dpi = 203;

    /**
     * Print RFID label via LAN (raw SBPL) untuk daftar UUID asset.
     * $size: '100x40' (default) atau '60x40'
     */
    public function printByAssetUuids(array $assetUuids, string $size = '100x40'): void
    {
        $assets = Assets::query()
            ->with(['rfids', 'assignment'])
            ->whereIn('uuid', $assetUuids)
            ->get();

        if ($assets->isEmpty()) {
            throw new \RuntimeException('No assets found.');
        }

        // ukuran label dalam dots
        [$wDots, $hDots] = match ($size) {
            '60x40' => [$this->mmToDots(60), $this->mmToDots(40)],
            default => [$this->mmToDots(100), $this->mmToDots(40)],
        };

        $sbplAll = $this->buildInitBlock($wDots, $hDots);

        foreach ($assets as $asset) {
            $rfid = $asset->rfids instanceof \Illuminate\Support\Collection
                ? $asset->rfids->first()
                : $asset->rfids;

            // auto-create EPC jika belum ada
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

            $sbplAll .= $this->buildPrintBlock($asset, $rfid, $wDots, $hDots, $size);
        }

        $this->sendToPrinter($sbplAll);
    }

    private function mmToDots(float $mm): int
    {
        return (int) round($mm * $this->dpi / 25.4);
    }

    /**
     * Buat asset description:
     * - tanpa special karakter/simbol (sisakan huruf, angka, spasi)
     * - rapihin spasi
     * - max karakter = $maxLen (default 20)
     */
    private function sanitizeDescForLabel(?string $text, int $maxLen = 20): string
    {
        $text = (string) ($text ?? '');

        // Buang karakter non-printable (biar aman ke printer)
        $text = preg_replace('/[^\P{C}\n]+/u', '', $text) ?? '';

        // Hapus semua special char/simbol: sisakan huruf, angka, spasi saja
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text) ?? '';

        // Rapihin spasi (tab/newline jadi spasi, spasi dobel diringkas)
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);

        // Max 20 karakter (multibyte-safe)
        return mb_substr($text, 0, $maxLen);
    }

    protected function buildInitBlock(int $wDots, int $hDots): string
    {
        $esc = "\x1B";
        $stx = "\x02";
        $etx = "\x03";

        // SBPL pakai max coordinate (0..max)
        // width 800 => H0799, height 320 => V00320 (template kamu)
        $maxH = max(0, $wDots - 1);
        $maxV = max(0, $hDots); // ikut gaya template awal kamu (V00320)

        return $stx
            . $esc . "A"
            . $esc . "A3V+00000H+0000"
            . $esc . "CS6"
            . $esc . "#F5"
            . $esc . "A1V" . str_pad((string) $maxV, 5, '0', STR_PAD_LEFT)
            . "H" . str_pad((string) $maxH, 4, '0', STR_PAD_LEFT)
            . $esc . "Z"
            . $etx;
    }

    protected function buildPrintBlock(Assets $asset, AssetsRfid $rfid, int $wDots, int $hDots, string $size): string
    {
        $esc = "\x1B";
        $stx = "\x02";
        $etx = "\x03";

        // EPC harus 32 hex chars
        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', (string) $rfid->epc));
        $epc = substr(str_pad($epc, 32, '0', STR_PAD_LEFT), -32);

        $uuid      = strtolower((string) $asset->uuid);
        $assetCode = (string) ($asset->asset_code ?? '');

        // ✅ DESC: tanpa simbol + max 20 char
        $desc = $this->sanitizeDescForLabel($asset->description, 20);

        $ownerCode    = (string) ($asset->assignment?->asset_owner ?? '');
        $locationCode = (string) ($asset->kode_location ?? '');

        $ttf = 'SATO0.ttf';

        // helper text (TTF)
        $makeTtfText = function (string $H, string $V, string $text, string $h = '024', string $w = '028') use ($esc, $ttf) {
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

        // RFID encode (UHF)
        $cmd .= $esc . "IP0" . "e:h,epc:" . $epc . ";";

        $header = "PT. LRT JAKARTA";

        if ($size !== '60x40') {
            // =========================
            // TEMPLATE 100 x 40 (AS-IS)
            // =========================

            // Header
            $cmd .= $esc . "%0"
                .  $esc . "H0036"
                .  $esc . "V00060"
                .  $esc . "P02"
                .  $esc . "RH0,SATO0.ttf,0,040,040," . $header;

            // QR kanan (pakai layout lama kamu)
            $cmd .= $esc . "%0"
                . $esc . "H0599"
                . $esc . "V00026"
                . $esc . "2D30,L,06,1,0"
                . $esc . "DN0036," . $uuid;

            // Text
            $cmd .= $makeTtfText('0036', '00113', $assetCode);
            $cmd .= $makeTtfText('0036', '00144', $desc);
            $cmd .= $makeTtfText('0036', '00177', 'Owner Asset: ' . $ownerCode);
            $cmd .= $makeTtfText('0036', '00206', 'Lokasi: ' . $locationCode);
            $cmd .= $makeTtfText('0036', '00237', 'RFID: ' . $epc);
        } else {
            // =========================
            // TEMPLATE 60 x 40
            // - QR diperkecil & diposisikan kanan
            // - Text diperkecil supaya muat
            // =========================

            // Header kecil
            $cmd .= $esc . "%0"
                .  $esc . "H0020"
                .  $esc . "V00045"
                .  $esc . "P02"
                .  $esc . "RH0,SATO0.ttf,0,032,032," . $header;

            // QR kanan (lebih kecil)
            $qrModule = 4;

            // posisi QR: kira-kira butuh area ~170 dots
            $qrEstimateSize = 170;
            $qrH = max(20, $wDots - $qrEstimateSize);
            $qrV = 20;

            $cmd .= $esc . "%0"
                . $esc . "H" . str_pad((string) $qrH, 4, '0', STR_PAD_LEFT)
                . $esc . "V" . str_pad((string) $qrV, 5, '0', STR_PAD_LEFT)
                . $esc . "2D30,L," . str_pad((string) $qrModule, 2, '0', STR_PAD_LEFT) . ",1,0"
                . $esc . "DN0036," . $uuid;

            // Text area kiri (lebih kecil)
            $cmd .= $makeTtfText('0020', '00105', $assetCode, '022', '024');
            $cmd .= $makeTtfText('0020', '00135', $desc, '022', '024');
            $cmd .= $makeTtfText('0020', '00168', 'Owner: ' . $ownerCode, '022', '024');
            $cmd .= $makeTtfText('0020', '00196', 'Lokasi: ' . $locationCode, '022', '024');

            // EPC dipendekin biar gak kepanjangan
            $shortEpc = substr($epc, 0, 8) . '...' . substr($epc, -8);
            $cmd .= $makeTtfText('0020', '00228', 'RFID: ' . $shortEpc, '022', '024');
        }

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

        // write semua bytes (biar gak kepotong)
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

        fclose($fp);
    }
}
