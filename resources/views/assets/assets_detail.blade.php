@extends('layouts.app')

@section('content')

    @include('assets.partials.detail-toolbar', ['asset' => $asset])

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

    @include('assets.partials.detail-summary', ['asset' => $asset])
            @include('assets.partials.detail-main', ['asset' => $asset])
            @include('assets.partials.detail-history', ['asset' => $asset])
            @include('assets.partials.detail-modals', ['asset' => $asset])

        </div>
    </div>

@endsection

@push('scripts')
    @include('assets.scripts.detail', ['asset' => $asset])
@endpush
