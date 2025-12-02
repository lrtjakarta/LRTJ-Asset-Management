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
        }

        .labels-container {
            display: flex;
            flex-wrap: wrap;
        }

        .label-item {
            width: 90mm;      /* sesuaikan dengan ukuran kertas label */
            height: 45mm;
            border: 1px solid #ddd;
            padding: 6px;
            margin: 4px;
            display: flex;
            align-items: center;
            page-break-inside: avoid;
        }

        .label-qr {
            flex: 0 0 40mm;
            text-align: center;
        }

        .label-qr img {
            max-width: 38mm;
            max-height: 38mm;
        }

        .label-info {
            flex: 1;
            padding-left: 6px;
            font-size: 11px;
            overflow: hidden;
        }

        .label-code {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .label-desc {
            font-size: 11px;
            margin-bottom: 2px;
            max-height: 3.2em;
            overflow: hidden;
        }

        .label-meta {
            font-size: 10px;
            color: #333;
        }

        .label-meta div {
            line-height: 1.3;
        }

        @page {
            margin: 8mm;
        }

        @media print {
            body {
                margin: 0;
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
            @endphp

            <div class="label-item">
                <div class="label-qr">
                    <img src="{{ $qrPath }}" alt="QR {{ $item->asset_code }}">
                </div>
                <div class="label-info">
                    <div class="label-code">{{ $item->asset_code }}</div>
                    <div class="label-desc">{{ $item->description }}</div>
                    <div class="label-meta">
                        <div>
                            Lokasi:
                            {{ $item->kode_location }}
                            @if(!empty($item->location_name))
                                - {{ $item->location_name }}
                            @endif
                        </div>
                        @if(!empty($item->rfid_epc))
                            <div>RFID: {{ $item->rfid_epc }}</div>
                        @endif
                        {{-- Kalau mau, bisa tambahkan Owner / User --}}
                        {{-- <div>Owner: {{ $item->asset_owner }}</div> --}}
                        {{-- <div>User: {{ $item->asset_user }}</div> --}}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
