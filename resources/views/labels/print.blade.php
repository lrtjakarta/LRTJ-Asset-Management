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
            margin: 10px; /* buat preview di layar, nanti di-print jadi 0 */
        }

        .labels-container {
            display: flex;
            flex-wrap: wrap;
        }

        .label-item {
            width: 90mm;      /* lebar label fisik */
            height: 45mm;     /* tinggi label fisik */
            padding: 3mm 3mm 3mm 3mm;
            margin: 3mm 3mm;
            display: flex;
            align-items: center;
            page-break-inside: avoid;
            border: 1px dashed #ccc; /* hanya untuk preview di layar */
            background: #fff;
        }

        .label-qr {
            flex: 0 0 32mm; /* sedikit lebih kecil biar muat teks */
            text-align: center;
        }

        .label-qr img {
            max-width: 30mm;
            max-height: 30mm;
        }

        .label-info {
            flex: 1;
            padding-left: 3mm;
            font-size: 9pt;
            overflow: hidden;
        }

        .label-code {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-desc {
            font-size: 9pt;
            margin-bottom: 2px;
            max-height: 3.2em;
            overflow: hidden;
        }

        .label-meta {
            font-size: 8pt;
            color: #333;
        }

        .label-meta div {
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Ukuran halaman untuk driver yang respect @page (beberapa driver thermal nurut) */
        @page {
            size: 90mm 45mm;
            margin: 0;
        }

        @media print {
            body {
                margin: 0;
            }

            .labels-container {
                margin: 0;
            }

            .label-item {
                margin: 0;          /* antar label tanpa gap, ikut setting gap di driver Sato */
                border: none;       /* border hanya buat preview layar */
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

                        {{-- Kalau mau diaktifkan:
                        @if (!empty($item->asset_owner))
                            <div>Owner: {{ $item->asset_owner }}</div>
                        @endif
                        @if (!empty($item->asset_user))
                            <div>User: {{ $item->asset_user }}</div>
                        @endif
                        --}}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
