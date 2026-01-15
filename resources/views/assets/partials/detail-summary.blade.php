{{-- Summary Card --}}
<div class="card mb-8">
    <div class="card-body p-6">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h3 class="fw-bold mb-0">{{ $asset->description }}</h3>
                </div>
                <div class="text-gray-700 mt-3">{{ $asset->asset_code }}</div>
            </div>

            <div class="text-end">
                <div class="text-muted fs-8">
                    Updated:
                    {{ optional($asset->updated_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }}
                </div>

                {{-- Print Label (single asset) --}}
                <form action="{{ route('label.print') }}" method="POST" target="_blank" class="mt-2 d-inline">
                    @csrf
                    {{-- controller expect JSON array of uuids --}}
                    <input type="hidden" name="asset_uuids" value='["{{ $asset->uuid }}"]'>

                    <button type="submit" class="btn btn-sm btn-danger mt-2">
                        Print QR/Barcode
                    </button>
                </form>

                {{-- Print RFID via Sato LAN --}}
                <form id="print-rfid-single-form" action="{{ route('label.print-rfid-lan') }}" method="POST"
                    class="mt-2 d-inline">
                    @csrf
                    <input type="hidden" name="asset_uuids" value='["{{ $asset->uuid }}"]'>
                    <input type="hidden" name="label_size" id="input-label-size-rfid-single" value="100x40">

                    <button type="submit" id="btnPrintRfidSingle" class="btn btn-sm btn-danger mt-2">
                        Print RFID (LAN)
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
{{-- End Summary Card --}}
