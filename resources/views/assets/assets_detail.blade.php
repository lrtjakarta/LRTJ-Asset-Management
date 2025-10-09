@extends('layouts.app')

@section('content')
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">
                    Asset Detail
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('assets.index') }}" class="text-muted text-hover-primary">Assets</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Detail {{ $asset->asset_code }} ({{ $asset->description }})</li>
                </ul>
            </div>

            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="#" class="btn btn-sm fw-bold btn-secondary">Transfer</a>
                <a href="#" class="btn btn-sm fw-bold btn-secondary">Disposal</a>
                <a href="{{ route('assets.edit', $asset->uuid) }}" class="btn btn-sm fw-bold btn-danger">Edit</a>
                <form id="assetDeleteForm" action="{{ route('assets.destroy', $asset->uuid) }}" method="POST"
                    class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" id="btnDeleteAsset" class="btn btn-sm fw-bold btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            {{-- Summary Card --}}
            <div class="card mb-8">
                <div class="card-body p-6">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <h3 class="fw-bold mb-0">
                                    {{ $asset->description }}
                                </h3>
                            </div>
                            <div class="text-gray-700 mt-3">
                                {{ $asset->asset_code }}
                            </div>
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

            {{-- Two columns --}}
            <div class="row g-6">

                {{-- LEFT: Classification + Identifiers + Assignment --}}
                <div class="col-xl-6">

                    {{-- Identifiers --}}
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">Identifiers</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Group Category</dt>
                                <dd class="col-sm-7"> : {{ $asset->kode_group_category }}</dd>
                                <dt class="col-sm-5">Asset Number Parent</dt>
                                <dd class="col-sm-7"> : {{ $asset->asset_number_parent }}</dd>
                                <dt class="col-sm-5">Asset Number Child</dt>
                                <dd class="col-sm-7"> : {{ $asset->asset_number_child }}</dd>
                                <dt class="col-sm-5">Asset Number Maximo</dt>
                                <dd class="col-sm-7"> : {{ $asset->identifiers?->asset_number_maximo }}</dd>
                                <dt class="col-sm-5">Asset Number D365</dt>
                                <dd class="col-sm-7"> : {{ $asset->identifiers?->asset_number_dynamic_365 }}</dd>
                                <dt class="col-sm-5">Asset Number Internal</dt>
                                <dd class="col-sm-7"> : {{ $asset->identifiers?->asset_number_internal }}</dd>
                                <dt class="col-sm-5">Asset Status</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $statusName = $asset->status?->name;
                                    @endphp
                                    @if ($statusName)
                                        <span
                                            class="badge badge-light-{{ $statusName === 'Active' ? 'success' : 'danger' }}">
                                            {{ $asset->kode_status }} - {{ $statusName }}
                                        </span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Classification --}}
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">Classification</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Asset Transaction</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $tx = $asset->classification?->kode_asset_transaction;
                                        $txName = $asset->classification?->transaction?->name ?? null;
                                    @endphp
                                    {{ $tx }} @if ($txName)
                                        - {{ $txName }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset Type</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $t = $asset->classification?->kode_asset_type;
                                        $tName = $asset->classification?->assetType?->name ?? null;
                                    @endphp
                                    {{ $t }} @if ($tName)
                                        - {{ $tName }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset Category</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $c = $asset->classification?->kode_category;
                                        $cName = $asset->classification?->category?->name ?? null;
                                    @endphp
                                    {{ $c }} @if ($cName)
                                        - {{ $cName }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset Category 2</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $c2 = $asset->classification?->kode_category_2;
                                        $c2Name = $asset->classification?->category2?->name ?? null;
                                    @endphp
                                    {{ $c2 }} @if ($c2Name)
                                        - {{ $c2Name }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset Sub Category</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $sc = $asset->classification?->kode_sub_category;
                                        $scName = $asset->classification?->subCategory?->name ?? null;
                                    @endphp
                                    {{ $sc }} @if ($scName)
                                        - {{ $scName }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Sumber Input</dt>
                                <dd class="col-sm-7"> :
                                    {{ $asset->kode_sumber }}
                                    @if ($asset->sumber?->name)
                                        - {{ $asset->sumber->name }}
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Assignment --}}
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">Assignment</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Asset Owner</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $own = $asset->assignment?->asset_owner;
                                        $ownName = $asset->assignment?->owner?->department ?? null;
                                    @endphp
                                    {{ $own }} @if ($ownName)
                                        - {{ $ownName }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset User</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $usr = $asset->assignment?->asset_user;
                                        $usrName = $asset->assignment?->user?->department ?? null;
                                    @endphp
                                    {{ $usr }} @if ($usrName)
                                        - {{ $usrName }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset Maintenance</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $mnt = $asset->assignment?->asset_maintenance;
                                        $mntName = $asset->assignment?->maintenance?->department ?? null;
                                    @endphp
                                    {{ $mnt }} @if ($mntName)
                                        - {{ $mntName }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset Location</dt>
                                <dd class="col-sm-7"> :
                                    {{ $asset->kode_location }}
                                    @if ($asset->location?->name)
                                        - {{ $asset->location->name }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Asset Class</dt>
                                <dd class="col-sm-7"> :
                                    {{ $asset->kode_asset_class }}
                                    @if ($asset->assetClass?->name)
                                        - {{ $asset->assetClass->name }}
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Value + Document + Meta --}}
                <div class="col-xl-6">

                    {{-- Value --}}
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">Value</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Price</dt>
                                <dd class="col-sm-7"> :
                                    {{ is_null($asset->value?->price) ? '' : number_format($asset->value->price, 2) }}
                                </dd>

                                <dt class="col-sm-5">Quantity</dt>
                                <dd class="col-sm-7"> :
                                    {{ is_null($asset->value?->quantity) ? '' : number_format($asset->value->quantity) }}
                                </dd>

                                <dt class="col-sm-5">VAT In</dt>
                                <dd class="col-sm-7"> :
                                    {{ is_null($asset->value?->vat_in) ? '' : number_format($asset->value->vat_in, 2) }}
                                </dd>

                                <dt class="col-sm-5">UOM</dt>
                                <dd class="col-sm-7"> :
                                    {{ $asset->value?->kode_uom }}
                                    @if ($asset->value?->uom?->name)
                                        - {{ $asset->value->uom->name }}
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Total</dt>
                                <dd class="col-sm-7"> :
                                    {{ is_null($asset->value?->total) ? '' : number_format($asset->value->total, 2) }}
                                </dd>

                                <dt class="col-sm-5">Useful Life</dt>
                                <dd class="col-sm-7"> :
                                    {{ $asset->value?->useful_life_month }} Month(s)
                                    @if (!is_null($asset->value?->useful_life_year))
                                        &nbsp; / &nbsp; {{ $asset->value->useful_life_year }} Year(s)
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Document --}}
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">Document</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">No PO/Perjanjian/SPK</dt>
                                <dd class="col-sm-7"> : {{ $asset->documents?->no_po_perjanjian_spk }}</dd>

                                <dt class="col-sm-5">Nota Referensi</dt>
                                <dd class="col-sm-7"> : {{ $asset->documents?->nota_referensi }}</dd>

                                <dt class="col-sm-5">No Document</dt>
                                <dd class="col-sm-7"> : {{ $asset->documents?->no_document }}</dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">QR</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <img src="{{ asset('storage/qrcodes/'.$asset->uuid.'.svg') }}" alt="QR Code" width="300" height="300">
                            </dl>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card py-3">
                <div class="card-header card-header-tabs-line">
                    <div class="card-title">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Logbook / History</span>
                        </h3>
                    </div>
                    <div class="card-toolbar">
                        <ul class="nav nav-tabs nav-bold nav-tabs-line" id="historyTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active fw-bold" id="tab-transfer-link" data-bs-toggle="tab"
                                    href="#tab_transfer" role="tab" aria-controls="tab_transfer"
                                    aria-selected="true">Transfer</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-return-link" data-bs-toggle="tab" href="#tab_return"
                                    role="tab" aria-controls="tab_return" aria-selected="false">Return</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-disposal-link" data-bs-toggle="tab"
                                    href="#tab_disposal" role="tab" aria-controls="tab_disposal"
                                    aria-selected="false">Disposal</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-acq-link" data-bs-toggle="tab"
                                    href="#tab_acquisition" role="tab" aria-controls="tab_acquisition"
                                    aria-selected="false">Acquisition</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-so-link" data-bs-toggle="tab"
                                    href="#tab_stock_opname" role="tab" aria-controls="tab_stock_opname"
                                    aria-selected="false">Stock
                                    Opname</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="historyTabsContent">
                        <div class="tab-pane fade show active" id="tab_transfer" role="tabpanel"
                            aria-labelledby="tab-transfer-link">
                            Transfer
                        </div>
                        <div class="tab-pane fade" id="tab_return" role="tabpanel" aria-labelledby="tab-return-link">
                            Return
                        </div>
                        <div class="tab-pane fade" id="tab_disposal" role="tabpanel"
                            aria-labelledby="tab-disposal-link">
                            Disposal
                        </div>
                        <div class="tab-pane fade" id="tab_acquisition" role="tabpanel" aria-labelledby="tab-acq-link">
                            Acquisition
                        </div>
                        <div class="tab-pane fade" id="tab_stock_opname" role="tabpanel" aria-labelledby="tab-so-link">
                            Stock Opname
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Delete confirmation
            $('#btnDeleteAsset').on('click', function() {
                Swal.fire({
                    title: 'Delete?',
                    text: 'This item will be moved to trash.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then(res => {
                    if (res.isConfirmed) {
                        $('#assetDeleteForm').trigger('submit');
                    }
                });
            });
        });
    </script>
@endpush
