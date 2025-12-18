<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Asset Labels</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 10px;
            /* preview */
        }

        .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
        }

        .label-item {
            width: 90mm;
            height: 45mm;
            padding: 2.5mm;
            margin: 3mm;
            border: 1px dashed #ccc;
            /* preview only */
            background: #fff;

            /* important for print: keep as one block */
            display: inline-block;
            vertical-align: top;
            overflow: hidden;

            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* TABLE LAYOUT (most stable in print) */
        .label-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .cell-logo {
            height: 7mm;
            vertical-align: top;
            padding: 0;
        }

        .label-logo {
            height: 6mm;
            width: auto;
            max-width: 32mm;
            object-fit: contain;
            display: block;
        }

        .cell-info {
            vertical-align: top;
            padding: 0;
            overflow: hidden;
        }

        .cell-qr {
            width: 28mm;
            vertical-align: top;
            text-align: right;
            padding: 0;
        }

        .cell-qr img {
            width: 26mm;
            height: 26mm;
            object-fit: contain;
            display: inline-block;
        }

        /* Auto-fit-ish: clamp + ellipsis + line clamp */
        .label-code {
            font-weight: 800;
            font-size: clamp(8pt, 2.2vw, 11pt);
            line-height: 1.1;
            margin: 0 0 1mm 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-desc {
            font-size: clamp(7pt, 1.9vw, 9.5pt);
            line-height: 1.15;
            margin: 0 0 1mm 0;

            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .label-meta {
            font-size: clamp(6.5pt, 1.6vw, 8.5pt);
            color: #222;
        }

        .label-meta div {
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .meta-label {
            font-weight: 700;
        }

        @page {
            size: 90mm 45mm;
            margin: 0;
        }

        html,
        body {
            width: 90mm;
            height: 45mm;
            margin: 0;
            padding: 0;
        }

        @media print {
            .labels-container {
                display: block;
                margin: 0;
                padding: 0;
            }

            .label-item {
                width: 90mm;
                height: 45mm;
                margin: 0;
                border: none;
                overflow: hidden;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            /* ✅ page break hanya antar label, bukan setelah label terakhir */
            .label-item:not(:last-child) {
                break-after: page;
                page-break-after: always;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="labels-container">
        @foreach ($items as $item)
            @php
                $qrPath = $item->qr_image_path
                    ? asset('storage/' . $item->qr_image_path)
                    : asset('storage/qrcodes/' . $item->uuid . '.svg');

                $logoPath = asset('metronic/demo1/assets/media/logos/logo-lrtj.png');

                $ownerDept = $item->asset_owner ?? '-';

                $lokasiDesc = $item->location_name ?? ($item->kode_location ?? '-');
            @endphp

            <div class="label-item">
                <table class="label-table">
                    <tr>
                        <td class="cell-logo">
                            <img class="label-logo" src="{{ $logoPath }}" alt="LRT Jakarta"
                                onerror="this.style.display='none'">
                        </td>

                        <!-- QR spans 2 rows (logo+info) so it never drops -->
                        <td class="cell-qr" rowspan="2">
                            <img src="{{ $qrPath }}" alt="QR {{ $item->asset_code }}">
                        </td>
                    </tr>

                    <tr>
                        <td class="cell-info">
                            <div class="label-code">{{ $item->asset_code }}</div>
                            <div class="label-desc">{{ $item->description }}</div>

                            <div class="label-meta">
                                <div><span class="meta-label">Owner Asset:</span> {{ $ownerDept }}</div>
                                <div><span class="meta-label">Lokasi:</span> {{ $lokasiDesc }}</div>

                                @if (!empty($item->rfid_epc))
                                    <div><span class="meta-label">RFID:</span> {{ $item->rfid_epc }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>
</body>

</html>
