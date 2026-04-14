<?php

namespace App\Services;

use App\Models\Assets;
use App\Models\AssetsRfid;
use Illuminate\Support\Facades\Log;

class SatoRfidPrinter
{
    public function printByAssetUuids(array $assetUuids, string $labelSize = '100x40'): void
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

            $layout = $this->labelLayout($labelSize);
            $sbplAll = $this->buildMediaSizeCommand($layout);

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

                $sbplAll .= $this->buildLabelCommand($asset, $rfid, $layout);
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
    protected function buildLabelCommand(Assets $asset, AssetsRfid $rfid, array $layout): string
    {
        $esc = "\x1B";
        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', $rfid->epc));
        $assetCode = $this->cleanPrintableText($asset->asset_code ?? '');
        $desc = $this->cleanPrintableText($asset->description ?? '');
        $desc = mb_substr($desc, 0, $layout['description_length']);

        $cmd  = $esc . "A";

        // Asset code
        $cmd .= $esc . $this->h($layout['asset_code_h'])
            . $esc . $this->v($layout['asset_code_v'])
            . $esc . "L0202";
        $cmd .= $esc . "XM" . $assetCode . "\r\n";

        // Description
        $cmd .= $esc . $this->h($layout['description_h'])
            . $esc . $this->v($layout['description_v'])
            . $esc . "L0101";
        $cmd .= $esc . "XM" . $desc . "\r\n";

        // EPC WRITE
        $cmd .= $esc . "IP0" . $epc . "\r\n";

        $cmd .= $esc . "Q1";
        $cmd .= $esc . "Z";

        return $cmd;
    }

    protected function buildMediaSizeCommand(array $layout): string
    {
        $esc = "\x1B";

        return $esc . "A"
            . $esc . "A1" . $this->dots($layout['height_dots']) . $this->dots($layout['width_dots'])
            . $esc . "Z";
    }

    protected function labelLayout(string $labelSize): array
    {
        $dotsPerMm = (int) config('printing.sato_rfid.dots_per_mm', 8);
        $dotsPerMm = $dotsPerMm > 0 ? $dotsPerMm : 8;

        $sizes = [
            '100x40' => [
                'width_mm' => 100,
                'height_mm' => 40,
                'asset_code_h_mm' => 4,
                'asset_code_v_mm' => 8,
                'description_h_mm' => 4,
                'description_v_mm' => 18,
                'description_length' => 34,
            ],
            '60x25' => [
                'width_mm' => 60,
                'height_mm' => 25,
                'asset_code_h_mm' => 3,
                'asset_code_v_mm' => 5,
                'description_h_mm' => 3,
                'description_v_mm' => 14,
                'description_length' => 24,
            ],
        ];

        $layout = $sizes[$labelSize] ?? $sizes['100x40'];

        return [
            'width_dots' => $layout['width_mm'] * $dotsPerMm,
            'height_dots' => $layout['height_mm'] * $dotsPerMm,
            'asset_code_h' => $layout['asset_code_h_mm'] * $dotsPerMm,
            'asset_code_v' => $layout['asset_code_v_mm'] * $dotsPerMm,
            'description_h' => $layout['description_h_mm'] * $dotsPerMm,
            'description_v' => $layout['description_v_mm'] * $dotsPerMm,
            'description_length' => $layout['description_length'],
        ];
    }

    protected function cleanPrintableText(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    protected function h(int $dots): string
    {
        return 'H' . $this->dots($dots);
    }

    protected function v(int $dots): string
    {
        return 'V' . $this->dots($dots);
    }

    protected function dots(int $dots): string
    {
        return str_pad((string) max(1, $dots), 4, '0', STR_PAD_LEFT);
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
