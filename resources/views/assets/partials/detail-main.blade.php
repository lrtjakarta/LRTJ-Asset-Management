{{-- Two columns --}}
<div class="row g-6">

    {{-- LEFT: Identification + Classification + Assignment + Document --}}
    <div class="col-xl-6">
        @include('assets.partials.detail-main-left', ['asset' => $asset])
    </div>

    {{-- RIGHT: Value + Notes + QR --}}
    <div class="col-xl-6">
        @include('assets.partials.detail-main-right', ['asset' => $asset])
    </div>

</div>
{{-- End Two columns --}}