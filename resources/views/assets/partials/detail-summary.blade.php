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
                <a href="#" class="btn btn-sm btn-danger mt-2">Print Page</a>
            </div>
        </div>
    </div>
</div>
{{-- End Summary Card --}}  