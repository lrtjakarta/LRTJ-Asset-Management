@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Dashboard
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Dashboard</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            {{-- Summary Stats Row --}}
            <div class="row g-5 g-xl-8 mb-5 mb-xl-8">

                {{-- Movement Card --}}
                <div class="col-xl-4">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Movement</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">Asset movement requests</span>
                            </h3>
                            <div class="card-toolbar">
                                <i class="ki-outline ki-delivery fs-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex flex-column gap-7">

                                {{-- Waiting --}}
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="movement" data-status="APR">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-outline ki-time fs-2x text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Waiting for Approval</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Pending requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-primary" id="mov-waiting">0</div>
                                </div>

                                {{-- Rejected --}}
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="movement" data-status="REJ">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-danger">
                                            <i class="ki-outline ki-cross-circle fs-2x text-danger"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Rejected</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Declined requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="mov-rejected">0</div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transfer Value Card --}}
                <div class="col-xl-4">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Transfer Value</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">Value transfer requests</span>
                            </h3>
                            <div class="card-toolbar">
                                <i class="ki-outline ki-arrow-right-left fs-2x text-success"></i>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex flex-column gap-7">

                                {{-- Waiting --}}
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="transfer-value" data-status="APR">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-outline ki-time fs-2x text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Waiting for Approval</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Pending requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-primary" id="trv-waiting">0</div>
                                </div>

                                {{-- Rejected --}}
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="transfer-value" data-status="REJ">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-danger">
                                            <i class="ki-outline ki-cross-circle fs-2x text-danger"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Rejected</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Declined requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="trv-rejected">0</div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- Disposal Card --}}
                <div class="col-xl-4">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Disposal</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">Asset disposal requests</span>
                            </h3>
                            <div class="card-toolbar">
                                <i class="ki-outline ki-trash fs-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex flex-column gap-7">

                                {{-- Waiting --}}
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="disposal" data-status="APR">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-outline ki-time fs-2x text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Waiting for Approval</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Pending requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-primary" id="dsp-waiting">0</div>
                                </div>

                                {{-- Rejected --}}
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="disposal" data-status="REJ">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-danger">
                                            <i class="ki-outline ki-cross-circle fs-2x text-danger"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Rejected</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Declined requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="dsp-rejected">0</div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const R = {
                data: "{{ route('dashboard.data') }}",
                movementIndex: "{{ route('transaction.transfer.index') }}",
                transferValueIndex: "{{ route('depreciation.transfer-requests.index') }}",
                disposalIndex: "{{ route('transaction.disposal.index') }}",
            };

            $.getJSON(R.data)
                .done(function(res) {
                    const mv = res.movement || {};
                    const trv = res.transfer_value || {};
                    const dsp = res.disposal || {};

                    // Movement
                    $('#mov-waiting').text(mv.waiting ?? 0);
                    $('#mov-rejected').text(mv.rejected ?? 0);

                    // Transfer value
                    $('#trv-waiting').text(trv.waiting ?? 0);
                    $('#trv-rejected').text(trv.rejected ?? 0);

                    // Disposal
                    $('#dsp-waiting').text(dsp.waiting ?? 0);
                    $('#dsp-rejected').text(dsp.rejected ?? 0);
                })
                .fail(function(err) {
                    console.error('Failed to load dashboard data', err);
                });

            $(document).on('click', '.dash-link', function() {
                const target = $(this).data('target');
                const status = $(this).data('status');

                let base = '';
                if (target === 'movement') base = R.movementIndex;
                if (target === 'transfer-value') base = R.transferValueIndex;
                if (target === 'disposal') base = R.disposalIndex;

                if (!base || !status) return;

                window.location.href = base + '?status=' + encodeURIComponent(status);
            });
        })();
    </script>
@endpush
