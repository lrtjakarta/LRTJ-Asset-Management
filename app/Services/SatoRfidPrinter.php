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
            $assets = Assets::with([
                'rfids',
                'activeQr',
                'assignment.owner',
                'location',
            ])
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
        $qrData = $this->cleanQrData($rfid->epc ?: $asset->uuid);
        $lines = $this->labelLines($asset, $rfid, $layout);

        $cmd  = $esc . "A";

        foreach ($lines as $line) {
            $cmd .= $esc . $this->h($line['h'])
                . $esc . $this->v($line['v'])
                . $esc . "L" . $line['scale']
                . $esc . $line['font'] . $line['text'];
        }

        if ($qrData !== '') {
            $cmd .= $esc . $this->h($layout['qr_h'])
                . $esc . $this->v($layout['qr_v'])
                . $this->qrCommand($qrData, $layout);
        }

        // EPC WRITE
        $cmd .= $esc . "IP0e:h,epc:" . $epc . ";";

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
                'text_h_mm' => 5,
                'text_v_mm' => 5,
                'line_gap_mm' => 5,
                'qr_h_mm' => 68,
                'qr_v_mm' => 6,
                'qr_cell_size' => 6,
                'description_length' => 28,
                'text_font' => 'XS',
                'title_font' => 'XM',
                'title_scale' => '0101',
                'text_scale' => '0101',
            ],
            '60x25' => [
                'width_mm' => 60,
                'height_mm' => 25,
                'text_h_mm' => 4,
                'text_v_mm' => 3,
                'line_gap_mm' => 3,
                'qr_h_mm' => 40,
                'qr_v_mm' => 3,
                'qr_cell_size' => 3,
                'description_length' => 18,
                'text_font' => 'M',
                'title_font' => 'M',
                'title_scale' => '0101',
                'text_scale' => '0101',
            ],
        ];

        $layout = $sizes[$labelSize] ?? $sizes['100x40'];

        return [
            'width_dots' => $layout['width_mm'] * $dotsPerMm,
            'height_dots' => $layout['height_mm'] * $dotsPerMm,
            'text_h' => $layout['text_h_mm'] * $dotsPerMm,
            'text_v' => $layout['text_v_mm'] * $dotsPerMm,
            'line_gap' => $layout['line_gap_mm'] * $dotsPerMm,
            'qr_h' => $layout['qr_h_mm'] * $dotsPerMm,
            'qr_v' => $layout['qr_v_mm'] * $dotsPerMm,
            'qr_cell_size' => $layout['qr_cell_size'],
            'description_length' => $layout['description_length'],
            'text_font' => $layout['text_font'],
            'title_font' => $layout['title_font'],
            'title_scale' => $layout['title_scale'],
            'text_scale' => $layout['text_scale'],
        ];
    }

    protected function labelLines(Assets $asset, AssetsRfid $rfid, array $layout): array
    {
        $owner = $asset->assignment?->asset_owner
            ?: $asset->assignment?->owner?->department
            ?: $asset->assignment?->owner?->description
            ?: '-';
        $location = $asset->kode_location ?: $asset->location?->name ?: '-';
        $epc = $this->shortRfidText($rfid->epc ?? '');

        $texts = [
            ['text' => 'PT. LRT JAKARTA', 'font' => $layout['title_font'], 'scale' => $layout['title_scale']],
            ['text' => $asset->asset_code ?? '-', 'font' => $layout['text_font'], 'scale' => $layout['text_scale']],
            ['text' => mb_substr($asset->description ?? '-', 0, $layout['description_length']), 'font' => $layout['text_font'], 'scale' => $layout['text_scale']],
            ['text' => 'Owner: ' . $owner, 'font' => $layout['text_font'], 'scale' => $layout['text_scale']],
            ['text' => 'Lok: ' . $location, 'font' => $layout['text_font'], 'scale' => $layout['text_scale']],
            ['text' => 'RFID: ' . $epc, 'font' => $layout['text_font'], 'scale' => $layout['text_scale']],
        ];

        $lines = [];
        foreach ($texts as $index => $item) {
            $lines[] = [
                'h' => $layout['text_h'],
                'v' => $layout['text_v'] + ($layout['line_gap'] * $index),
                'font' => $item['font'],
                'scale' => $item['scale'],
                'text' => $this->cleanPrintableText($item['text']),
            ];
        }

        return $lines;
    }

    protected function shortRfidText(string $epc): string
    {
        $epc = strtoupper(preg_replace('/[^0-9A-F]/', '', $epc) ?? '');

        if (strlen($epc) <= 18) {
            return $epc !== '' ? $epc : '-';
        }

        return substr($epc, 0, 6) . '...' . substr($epc, -6);
    }

    protected function cleanQrData(string $text): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $text) ?? '';
    }

    protected function qrCommand(string $data, array $layout): string
    {
        $cellSize = str_pad((string) $layout['qr_cell_size'], 2, '0', STR_PAD_LEFT);
        $length = str_pad((string) strlen($data), 4, '0', STR_PAD_LEFT);

        return "\x1B" . "BQ20" . $cellSize . ",3" . $length . $data;
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
