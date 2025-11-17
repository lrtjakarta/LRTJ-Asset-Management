@extends('layouts.app')

@section('content')
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">Asset Detail</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('assets.index') }}" class="text-muted text-hover-primary">Assets</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        Detail {{ $asset->asset_code }} ({{ $asset->description }})
                    </li>
                </ul>
            </div>

            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @canAction('MOVEMENT','C')
                <a href="#" class="btn btn-sm fw-bold btn-secondary" id="btnOpenTransfer">Movement</a>
                @endcanAction
                @canAction('DISPOSAL','C')
                <a href="#"
                    class="btn btn-sm fw-bold btn-secondary {{ $asset->kode_status == 'DIS' ? 'disabled' : '' }} "
                    id="btnOpenDisposal">Disposal</a>
                @endcanAction
                @canAction('RETURN','C')
                <a href="#" class="btn btn-sm fw-bold btn-secondary" id="btnOpenReturn">Return</a>
                @endcanAction
                @canAction('ACQUISITION','C')
                <a href="#" class="btn btn-sm fw-bold btn-secondary" id="btnOpenAcq">Acquisition</a>
                @endcanAction
                @canAction('ASSETS','U')
                <a href="{{ route('assets.edit', $asset->uuid) }}" class="btn btn-sm fw-bold btn-danger">Edit</a>
                @endcanAction
                @canAction('ASSETS','D')
                <form id="assetDeleteForm" action="{{ route('assets.destroy', $asset->uuid) }}" method="POST"
                    class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" id="btnDeleteAsset" class="btn btn-sm fw-bold btn-danger">Delete</button>
                </form>
                @endcanAction
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

            {{-- Two columns --}}
            <div class="row g-6">

                {{-- LEFT: Identification + Classification + Assignment --}}
                <div class="col-xl-6">

                    {{-- Identifiers --}}
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">Identifiers</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                {{-- <dt class="col-sm-5">Group Category</dt>
                                <dd class="col-sm-7"> : {{ $asset->kode_group_category }}</dd> --}}

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

                                <dt class="col-sm-5">Alias</dt>
                                <dd class="col-sm-7"> : {{ $asset->identifiers?->alias }}</dd>

                                <dt class="col-sm-5">Asset Status</dt>
                                <dd class="col-sm-7" id="lbl-asset-status"> :
                                    @if ($asset->status?->name)
                                        <span
                                            class="badge badge-light-{{ $asset->status->name === 'Operation' ? 'success' : 'danger' }}">
                                            {{ $asset->kode_status }} - {{ $asset->status->name }}
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
                                <dt class="col-sm-5">Asset Company</dt>
                                <dd class="col-sm-7"> :
                                    @php
                                        $tx = $asset->classification?->kode_asset_transaction;
                                        $txName = $asset->classification?->transaction?->name ?? null;
                                    @endphp
                                    {{ $tx }} @if ($txName)
                                        - {{ $txName }}
                                    @endif
                                </dd>
                                <dt class="col-sm-5">Asset Class</dt>
                                <dd class="col-sm-7"> :
                                    {{ $asset->kode_asset_class }}
                                    @if ($asset->assetClass?->name)
                                        - {{ $asset->assetClass->name }}
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
                            </dl>
                        </div>
                    </div>

                </div>

                {{-- RIGHT: Value + Document + QR --}}
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

                                <dt class="col-sm-5">Acquisition</dt>
                                <dd class="col-sm-7"> :
                                    {{ is_null($asset->value?->total) ? '' : number_format($asset->value->total, 2) }}
                                </dd>

                                <dt class="col-sm-5">Acquisition Date</dt>
                                <dd class="col-sm-7"> :
                                    {{ is_null($asset->value?->actual_date) ? '' : $asset->value->actual_date->format('d F Y') }}
                                </dd>

                                <dt class="col-sm-5">Capitalization Date</dt>
                                <dd class="col-sm-7"> :
                                    {{ is_null($asset->value?->capitalization_date) ? '' : $asset->value->capitalization_date->format('d F Y') }}
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


                    {{-- Notes --}}
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">Notes</h3>
                        </div>
                        <div class="card-body">
                            <div class="col-sm-12">{{ $asset->notes ?? '-' }}</div>
                        </div>
                    </div>

                    {{-- QR --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title fw-bold">QR</h3>
                        </div>
                        <div class="card-body">
                            <img src="{{ asset('storage/qrcodes/' . $asset->uuid . '.svg') }}" alt="QR Code"
                                width="300" height="300">
                        </div>
                    </div>
                </div>
            </div>

            {{-- History Tabs --}}
            <div class="card py-3 mt-6">
                <div class="card-header card-header-tabs-line">
                    <div class="card-title">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Logbook / History</span>
                        </h3>
                    </div>
                    <div class="card-toolbar">
                        <ul class="nav nav-tabs nav-bold nav-tabs-line" id="historyTabs" role="tablist">
                            @canAction('ACQUISITION','R')
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active fw-bold" id="tab-acq-link" data-bs-toggle="tab"
                                    href="#tab_acquisition" role="tab" aria-controls="tab_acquisition"
                                    aria-selected="false">Acquisition</a>
                            </li>
                            @endcanAction
                            @canAction('MOVEMENT','R')
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-transfer-link" data-bs-toggle="tab"
                                    href="#tab_transfer" role="tab" aria-controls="tab_transfer"
                                    aria-selected="true">Movement</a>
                            </li>
                            @endcanAction
                            @canAction('RETURN','R')
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-return-link" data-bs-toggle="tab" href="#tab_return"
                                    role="tab" aria-controls="tab_return" aria-selected="false">Return</a>
                            </li>
                            @endcanAction
                            @canAction('DISPOSAL','R')
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-disposal-link" data-bs-toggle="tab"
                                    href="#tab_disposal" role="tab" aria-controls="tab_disposal"
                                    aria-selected="false">Disposal</a>
                            </li>
                            @endcanAction
                            @canAction('STOCK_OPN','R')
                            <li class="nav-item" role="presentation">
                                <a class="nav-link fw-bold" id="tab-so-link" data-bs-toggle="tab"
                                    href="#tab_stock_opname" role="tab" aria-controls="tab_stock_opname"
                                    aria-selected="false">Stock Opname</a>
                            </li>
                            @endcanAction
                        </ul>
                    </div>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="historyTabsContent">
                        @canAction('ACQUISITION','R')
                        <div class="tab-pane fade show active" id="tab_acquisition" role="tabpanel"
                            aria-labelledby="tab-acq-link">
                            <table
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                                id="tbl-acq">
                                <thead>
                                    <tr class="table-light">
                                        <th class="min-w-160px">Transaction Number</th>
                                        <th class="min-w-380px">Details</th>
                                        <th class="min-w-220px">Note</th>
                                        <th class="min-w-160px">Requester</th>
                                        <th class="min-w-180px">Created</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        @endcanAction
                        @canAction('MOVEMENT','R')
                        <div class="tab-pane fade" id="tab_transfer" role="tabpanel"
                            aria-labelledby="tab-transfer-link">
                            <table
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                                id="tbl-transfers">
                                <thead>
                                    <tr class="table-light">
                                        <th class="min-w-200px">Transaction Number</th>
                                        <th class="min-w-100px">Type</th>
                                        <th class="min-w-150px">Before</th>
                                        <th class="min-w-150px">After</th>
                                        <th class="min-w-100px">Requester</th>
                                        <th class="min-w-100px">Approver</th>
                                        <th class="min-w-250px">Note</th>
                                        <th class="min-w-100px">Status Movement</th>
                                        <th class="min-w-160px">Flow</th>
                                        <th class="min-w-200px">File</th>
                                        <th class="min-w-200px">Updated</th>
                                        <th class="min-w-200px">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        @endcanAction
                        @canAction('RETURN','R')
                        <div class="tab-pane fade" id="tab_return" role="tabpanel" aria-labelledby="tab-return-link">
                            <table
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                                id="tbl-returns">
                                <thead>
                                    <tr class="table-light">
                                        <th class="min-w-180px">Transaction Number</th>
                                        <th class="min-w-180px">MOV/DSP Code</th>
                                        <th class="min-w-220px">Type</th>
                                        <th class="min-w-300px">Details</th>
                                        <th class="min-w-220px">Note</th>
                                        <th class="min-w-140px">Requester</th>
                                        <th class="min-w-180px">Created</th>
                                        <th class="min-w-140px">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        @endcanAction
                        @canAction('DISPOSAL','R')
                        <div class="tab-pane fade" id="tab_disposal" role="tabpanel"
                            aria-labelledby="tab-disposal-link">
                            <table
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                                id="tbl-disposal">
                                <thead>
                                    <tr class="table-light">
                                        <th class="min-w-200px">Transaction Number</th>
                                        <th class="min-w-100px">Type</th>
                                        <th class="min-w-100px">Requester</th>
                                        <th class="min-w-100px">Approver</th>
                                        <th class="min-w-250px">Note</th>
                                        <th class="min-w-100px">Status Transfer</th>
                                        <th class="min-w-200px">File</th>
                                        <th class="min-w-200px">Updated</th>
                                        <th class="min-w-200px">Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        @endcanAction
                        @canAction('STOCK_OPN','R')
                        <div class="tab-pane fade" id="tab_stock_opname" role="tabpanel" aria-labelledby="tab-so-link">
                            <table
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                                id="tbl-so">
                                <thead>
                                    <tr class="table-light">
                                        <th class="min-w-170px">Transaction Number</th>
                                        <th class="min-w-120px">Source</th>
                                        <th class="min-w-140px">Type</th>
                                        <th class="min-w-300px">Detail</th>
                                        <th class="min-w-220px">Note</th>
                                        <th class="min-w-140px">Requester</th>
                                        <th class="min-w-140px">Approver</th>
                                        <th class="min-w-160px">File</th>
                                        <th class="min-w-180px">Updated</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        @endcanAction
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ===== Transfer Modal ===== --}}
    <div class="modal fade" id="modal-transfer" tabindex="-1" aria-labelledby="modalTransferLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formTransfer" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="asset_uuid" value="{{ $asset->uuid }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTransferLabel">Request Movement — {{ $asset->asset_code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-4">
                                <label class="form-label required">Transfer Type</label>
                                <select name="type" id="tf-type" class="form-select" required>
                                    <option value="owner">Owner</option>
                                    <option value="user">User</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="status">Status</option>
                                    <option value="location">Location</option>
                                </select>
                                <div class="form-text">Select Movement Type</div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label required">Move To</label>
                                <select id="tf-target" class="form-select" required></select>
                                <input type="hidden" name="after[value]" id="tf-target-hidden">
                                <div class="form-text" id="tf-target-help">Select the new target.</div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Attachment (optional)</label>
                                <input type="file" name="file" id="tf-file" class="form-control"
                                    accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv,.txt">
                                <div class="form-text">PDF / image / office docs (max 20MB).</div>

                                {{-- visible only when editing and a file exists --}}
                                <div id="tf-current-file" class="mt-2 d-none">
                                    <a id="tf-current-file-link" href="#" target="_blank"></a>
                                    <button type="button" class="btn btn-sm btn-light-danger ms-2"
                                        id="btn-remove-file">Remove</button>
                                    <input type="hidden" name="remove_file" id="tf-remove-file" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- ===== Disposal Modal ===== --}}
    <div class="modal fade" id="modal-disposal" tabindex="-1" aria-labelledby="modalDisposalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formDisposal" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="ds-edit-id">
                    <input type="hidden" name="asset_uuid" value="{{ $asset->uuid }}">
                    <input type="hidden" name="remove_file" id="ds-remove-file" value="0">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDisposalLabel">Request Disposal — {{ $asset->asset_code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" id="ds-note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Attachment (optional)</label>
                                <input type="file" name="file" id="ds-file" class="form-control"
                                    accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv,.txt">
                                <div class="form-text">PDF / image / office docs (max 20MB).</div>

                                {{-- visible only when editing and a file exists --}}
                                <div id="ds-current-file" class="mt-2 d-none">
                                    <a id="ds-current-file-link" href="#" target="_blank"></a>
                                    <button type="button" class="btn btn-sm btn-light-danger ms-2"
                                        id="ds-btn-remove-file">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- ===== Return Modal ===== --}}
    <div class="modal fade" id="modal-return" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formReturn">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Return</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-12">
                                <label class="form-label required">Select Transfer/Disposal</label>
                                <select id="ret-source" class="form-select" required></select>
                                <div class="form-text">Shows only latest accepted per asset (transfer-by-type, disposal)
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="p-3 border rounded bg-light" id="ret-preview" style="display:none">
                                    <div><strong>Asset:</strong> <span id="ret-asset"></span></div>
                                    <div><strong>Type:</strong> <span id="ret-type"></span></div>
                                    <div id="ret-ba-wrap" style="display:none;">
                                        <strong>Before → After:</strong>
                                        <span id="ret-before"></span>
                                        <span class="mx-1">→</span>
                                        <span id="ret-after" class="fw-bold"></span>
                                    </div>
                                    <div class="mt-1"><strong>Code:</strong> <span id="ret-code"></span></div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Create Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- ===== Acquisition Modal ===== --}}
    <div class="modal fade" id="modal-acq" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formAcq">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Acquisition — {{ $asset->asset_code }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label required">Quantity</label>
                                <input type="number" step="0.0001" min="0" class="form-control"
                                    name="quantity" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">UOM</label>
                                <select name="kode_uom" id="acq-uom" class="form-select" required></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Price</label>
                                <input type="number" step="0.0001" min="0" class="form-control" name="price"
                                    required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Useful Life (Months)</label>
                                <input type="number" step="1" min="0" class="form-control"
                                    name="useful_life_month" required>
                                <div class="form-text">Year will be auto-calculated</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Include Tax (VAT-IN)?</label>
                                <select name="is_pajak" class="form-select" required>
                                    <option value="1" selected>Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Nilai Pajak (%)</label>
                                <input type="number" class="form-control" id="nilai_pajak" name="nilai_pajak"
                                    value = "{{ ENV('NILAI_PAJAK') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Actual Date</label>
                                <input type="date" class="form-control" name="actual_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Capitalization Date</label>
                                <input type="date" class="form-control" name="capitalization_date" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea class="form-control" rows="3" name="note" placeholder="Optional note…"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- ===== Approve Movement Location Modal ===== --}}
    <div class="modal fade" id="modal-transfer-approve" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <input type="hidden" id="tf-approve-id">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Movement Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="tf-flow-steps">
                        {{-- steps will be rendered by JS --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-disposal-flow" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formDisposalFlow" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="flow-disposal-id" name="uuid" value="">

                    <div class="modal-header">
                        <h5 class="modal-title">Disposal Approval Flow</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-4">
                            <div><strong>Asset:</strong> <span id="flow-asset"></span></div>
                            <div><strong>Transaction:</strong> <span id="flow-code"></span></div>
                            <div><strong>Status:</strong> <span id="flow-status"></span></div>
                            <div class="mt-2">
                                <strong>Form Disposal:</strong>
                                <span id="flow-form-file"></span>
                            </div>
                            <div class="mt-2">
                                <strong>Berita Acara:</strong>
                                <span id="flow-ba-file"></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Flow Steps</label>
                            <ul class="list-group" id="flow-steps"></ul>
                        </div>

                        <div class="mb-4 d-none" id="flow-wrap-ba-upload">
                            <label class="form-label">Upload Berita Acara Disposal</label>
                            <input type="file" name="ba_file" id="flow-ba-file-input" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.bmp">
                            <div class="form-text">Required on final step by Asset Management.</div>
                        </div>
                        <div class="mb-4 d-none" id="flow-wrap-form-upload">
                            <label class="form-label">Upload Form Disposal</label>
                            <input type="file" name="flow_file" id="flow-form-file-input" class="form-control"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.bmp">
                            <div class="form-text">Optional: upload signed / scanned form disposal.</div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger" id="btn-flow-approve">
                            Approve Next Step
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        (function() {
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
                    if (res.isConfirmed) $('#assetDeleteForm').trigger('submit');
                });
            });

            const R = {
                usercode: '{{ route('master.user_code.options') }}',
                status: '{{ route('master.status.options') }}',
                location: '{{ route('master.location.options') }}',
                transfers: '{{ route('transfer.data', $asset->uuid) }}',
                create: '{{ route('transfer.store') }}',
                show: id => '{{ route('transfer.show', ':id') }}'.replace(':id', id),
                approveLocationStep: id => '{{ route('transfer.approve-step', ':id') }}'.replace(':id',
                    id),
            };
            const ROUTE = {
                update: id => '{{ route('transfer.update', ':id') }}'.replace(':id', id),
                destroy: id => '{{ route('transfer.destroy', ':id') }}'.replace(':id', id),
                approve: id => '{{ route('transfer.approve', ':id') }}'.replace(':id', id),
                reject: id => '{{ route('transfer.reject', ':id') }}'.replace(':id', id),
            };

            $('#btnOpenTransfer').on('click', function(e) {
                e.preventDefault();
                $('#formTransfer')[0].reset();
                $('#tf-type').val('owner');
                initTargetSelect('owner');
                $('#modal-transfer').modal('show');
            });

            function initSelect2(el, url, extra = {}) {
                $(el).empty().select2({
                    dropdownParent: $('#modal-transfer'),
                    placeholder: 'Select...',
                    width: '100%',
                    allowClear: true,
                    ajax: {
                        url,
                        dataType: 'json',
                        delay: 150,
                        data: function(params) {
                            const base = {
                                q: params.term || '',
                                page: params.page || 1
                            };
                            if (typeof extra === 'function') return Object.assign(base, extra());
                            if (extra && typeof extra === 'object') return Object.assign(base, extra);
                            return base;
                        },
                        processResults: data => data
                    }
                }).on('select2:select', function(e) {
                    const id = e.params.data.id;
                    $('#tf-target-hidden').val(id);
                });
            }

            function initTargetSelect(type) {
                $('#tf-target').off('select2:select').val(null).trigger('change');
                $('#tf-target-hidden').val('');
                if (type === 'owner' || type === 'user' || type === 'maintenance') {
                    initSelect2('#tf-target', R.usercode);
                    $('#tf-target-help').text('Pick a User Code (department) as new ' + type + '.');
                } else if (type === 'status') {
                    initSelect2('#tf-target', R.status, {
                        type: 'Asset'
                    });
                    $('#tf-target-help').text('Pick the new Asset Status (type = Asset).');
                } else if (type === 'location') {
                    initSelect2('#tf-target', R.location);
                    $('#tf-target-help').text('Pick the new Location.');
                }
            }

            $('#tf-type').on('change', function() {
                initTargetSelect(this.value);
            });


            // Transfer history DataTable
            $('#tbl-transfers').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: R.transfers,
                    type: 'GET'
                },
                order: [
                    [8, 'desc']
                ],
                "dom": "<'row mb-2'" +
                    "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
                    ">" +

                    "<'table-responsive'tr>" +

                    "<'row'" +
                    "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
                    ">",
                searching: true,
                columns: [{
                        data: 'transfer_code',
                        name: 'transfer_code'
                    },
                    {
                        data: 'type',
                        name: 'type',
                        render: d => (d || '').toUpperCase()
                    },
                    {
                        data: 'before_display',
                        name: 'before_display'
                    },
                    {
                        data: 'after_display',
                        name: 'after_display'
                    },
                    {
                        data: 'pic_request_uid',
                        name: 'pic_request_uid'
                    },
                    {
                        data: 'pic_approve_uid',
                        name: 'pic_approve_uid',
                        defaultContent: ''
                    },
                    {
                        data: 'note',
                        name: 'note',
                        render: d => d ? $('<div>').text(d).html() : ''
                    },
                    {
                        data: 'workflow_label',
                        name: 'workflow_label',
                        "render": function(data, type, row, meta) {
                            if (row.kode_status == 'APR') {
                                return `<span class="badge badge-light-primary">${data||''}</span>`;
                            } else if (row.kode_status == 'ACC') {
                                return `<span class="badge badge-light-success">${data||''}</span>`;
                            } else {
                                return `<span class="badge badge-light-danger">${data||''}</span>`;
                            }
                        }
                    },
                    {
                        data: 'flow_label',
                        name: 'flow_label',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            if (type !== 'display') return data;
                            return data || '';
                        }
                    },
                    {
                        data: 'file',
                        name: 'file',
                        defaultContent: ''
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;
                            const d = new Date(iso);
                            const dateStr = new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            }).format(d);
                            const timeStr = new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            }).format(d);
                            return `${dateStr} ${timeStr}`;
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#tbl-transfers').on('click', '.btn-tf-edit', function() {
                const row = $('#tbl-transfers').DataTable().row($(this).closest('tr')).data();
                $('#modal-transfer').modal('show');

                $('#formTransfer').data('edit-id', row.uuid);
                $('#tf-type').val(row.type).trigger('change');

                if (row.file_url) {
                    $('#tf-current-file').removeClass('d-none');
                    $('#tf-current-file-link').attr('href', row.file_url).text(row.file_name || 'Current file');
                    $('#tf-remove-file').val('0');
                } else {
                    $('#tf-current-file').addClass('d-none');
                    $('#tf-remove-file').val('0');
                }

                $('#btn-remove-file').off('click').on('click', function() {
                    $('#tf-remove-file').val('1');
                    $('#tf-current-file').addClass('d-none');
                });
                setTimeout(() => {
                    const code = row.after_code || '';
                    const opt = new Option(code, code, true, true);
                    $('#tf-target').append(opt).trigger('change');
                    $('#tf-target-hidden').val(code);
                }, 300);

                $('textarea[name="note"]').val(row.note || '');
            });

            $('#formTransfer').off('submit').on('submit', function(e) {
                e.preventDefault();

                const isEdit = !!$('#formTransfer').data('edit-id');
                const id = $('#formTransfer').data('edit-id');

                const baseFields = {
                    asset_uuid: '{{ $asset->uuid }}',
                    type: $('#tf-type').val(),
                    note: $('textarea[name="note"]').val() || '',
                    'after[value]': $('#tf-target-hidden').val() || '',
                    remove_file: $('#tf-remove-file').length ? ($('#tf-remove-file').val() || 0) : 0
                };

                const fileInput = document.getElementById('tf-file');
                const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

                let req;

                if (hasFile) {
                    const fd = new FormData();
                    Object.entries(baseFields).forEach(([k, v]) => fd.append(k, v));
                    fd.append('file', fileInput.files[0]);

                    const url = isEdit ? ROUTE.update(id) : '{{ route('transfer.store', $asset->uuid) }}';
                    const type = isEdit ? 'POST' : 'POST';
                    if (isEdit) fd.append('_method', 'PUT');

                    req = $.ajax({
                        url,
                        type,
                        data: fd,
                        processData: false,
                        contentType: false
                    });

                } else {
                    const payload = baseFields;

                    req = isEdit ?
                        $.ajax({
                            url: ROUTE.update(id),
                            type: 'PUT',
                            data: payload
                        }) :
                        $.post('{{ route('transfer.store', $asset->uuid) }}', payload);
                }

                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                req.done(() => {
                        $('#modal-transfer').modal('hide');
                        Swal.fire('Success', isEdit ? 'Transfer updated.' : 'Transfer created.', 'success');
                        $('#tbl-transfers').DataTable().ajax.reload(null, false);
                        if (fileInput) fileInput.value = '';
                        if ($('#tf-remove-file').length) $('#tf-remove-file').val('0');
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });

            $('#btn-remove-file').off('click').on('click', function() {
                $('#tf-remove-file').val('1');
                $('#tf-current-file').addClass('d-none');
            });

            $('#tbl-transfers').on('click', '.btn-tf-approve', function() {
                const id = $(this).data('id');
                const row = $('#tbl-transfers').DataTable().row($(this).closest('tr')).data();

                if ((row.type || '').toLowerCase() === 'location') {
                    // Use multi-step flow modal for location
                    $('#tf-approve-id').val(id);

                    $.get(R.show(id))
                        .done(d => {
                            renderApproveFlowModal(d);
                            $('#modal-transfer-approve').modal('show');
                        })
                        .fail(() => {
                            Swal.fire('Error', 'Cannot load transfer', 'error');
                        });
                } else {
                    // Old simple approve for other types
                    Swal.fire({
                            title: 'Approve this transfer?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Approve',
                            confirmButtonColor: '#EA242A',
                            cancelButtonColor: '#B5B5B6'
                        })
                        .then(res => {
                            if (!res.isConfirmed) return;
                            Swal.fire({
                                title: 'Applying…',
                                didOpen: () => Swal.showLoading(),
                                allowOutsideClick: false
                            });
                            $.post(ROUTE.approve(id)).done(() => {
                                Swal.fire({
                                    title: 'Approved',
                                    text: 'Transfer applied to asset.',
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 500,
                                    timerProgressBar: true,
                                }).then(() => {
                                    location.reload();
                                });
                            }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed',
                                'error'));
                        });
                }
            });

            $('#tbl-transfers').on('click', '.btn-tf-reject', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Reject this transfer?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Reject',
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6'
                    })
                    .then(res => {
                        if (!res.isConfirmed) return;
                        Swal.fire({
                            title: 'Updating…',
                            didOpen: () => Swal.showLoading(),
                            allowOutsideClick: false
                        });
                        $.post(ROUTE.reject(id)).done(() => {
                            Swal.fire('Rejected', 'Transfer rejected.', 'success');
                            $('#tbl-transfers').DataTable().ajax.reload(null, false);
                        }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

            $('#tbl-transfers').on('click', '.btn-tf-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Delete this request?',
                        text: 'It will go to trash.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Delete',
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6'
                    })
                    .then(res => {
                        if (!res.isConfirmed) return;
                        Swal.fire({
                            title: 'Deleting…',
                            didOpen: () => Swal.showLoading(),
                            allowOutsideClick: false
                        });
                        $.ajax({
                                url: ROUTE.destroy(id),
                                type: 'DELETE'
                            })
                            .done(() => {
                                Swal.fire('Deleted', 'Request moved to trash.', 'success');
                                $('#tbl-transfers').DataTable().ajax.reload(null, false);
                            })
                            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

            function renderApproveFlowModal(data) {
                const container = $('#tf-flow-steps').empty();

                let flow = data && data.flow ? data.flow : data;

                if (typeof flow === 'string') {
                    try {
                        flow = JSON.parse(flow);
                    } catch (e) {
                        flow = {};
                    }
                }

                const steps = Array.isArray(flow?.steps) ? flow.steps : [];

                if (!steps.length) {
                    container.append('<div class="text-muted">No flow defined.</div>');
                    return;
                }

                steps.forEach((step, idx) => {
                    const approvedAt = step.approved_at;
                    const approvedBy = step.approved_by;
                    const isPending = !approvedAt;

                    const statusBadge = approvedAt ?
                        `<span class="badge badge-light-success">Approved</span>` :
                        `<span class="badge badge-light-warning">Pending</span>`;

                    const approverInfo = approvedAt ?
                        `<div class="small text-muted">by ${escapeHtml(approvedBy || '-')} at ${escapeHtml(approvedAt)}</div>` :
                        '';

                    const btn = isPending ?
                        `<button type="button"
                      class="btn btn-sm btn-primary mt-2 btn-flow-approve-step"
                      data-step-index="${idx}">
                    Approved by ${escapeHtml(step.role || step.label || 'Role')}
               </button>` :
                        '';

                    container.append(`
            <div class="border rounded p-3 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">${escapeHtml(step.label || 'Step')}</div>
                        <div class="small text-muted">${escapeHtml(step.role || '')}</div>
                        ${approverInfo}
                    </div>
                    <div>${statusBadge}</div>
                </div>
                ${btn}
            </div>
        `);
                });

                // Only allow clicking the first pending step
                let firstPending = true;
                container.find('.btn-flow-approve-step').each(function() {
                    if (firstPending) {
                        firstPending = false;
                    } else {
                        $(this).prop('disabled', true);
                    }
                });
            }

            function escapeHtml(text) {
                return $('<div/>').text(text || '').html();
            }

            // Approve a single flow step
            $('#tf-flow-steps').on('click', '.btn-flow-approve-step', function() {
                const id = $('#tf-approve-id').val();
                if (!id) return;

                const $btn = $(this).prop('disabled', true);

                $.post(R.approveLocationStep(id))
                    .done(res => {
                        if (res.flow) {
                            renderApproveFlowModal({
                                flow: res.flow
                            });
                        }

                        if (res.completed) {
                            $('#modal-transfer-approve').modal('hide');
                            Swal.fire('Approved', 'Movement location completed.', 'success');
                        } else {
                            Swal.fire('Approved', 'Step approved.', 'success');
                        }

                        $('#tbl-transfers').DataTable().ajax.reload(null, false);
                    })
                    .fail(x => {
                        $btn.prop('disabled', false);
                        Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                    });
            });

        })();
    </script>@push('scripts')
<script>
    (function () {
        const DS = {
            data:    '{{ route('disposal.data', $asset->uuid) }}',
            create:  '{{ route('disposal.store') }}',
            show:    '{{ route('disposal.show', ':id') }}',
            update:  '{{ route('disposal.update', ':id') }}',
            destroy: '{{ route('disposal.destroy', ':id') }}',
            approve: '{{ route('disposal.approve', ':id') }}', // simple fallback (not used by buttons now)
            reject:  '{{ route('disposal.reject', ':id') }}',

            approveStep: id => '{{ route('disposal.approve-step', ':id') }}'.replace(':id', id),
            formRoute:   id => '{{ route('disposal.form', ':id') }}'.replace(':id', id),
            baRoute:     id => '{{ route('disposal.ba', ':id') }}'.replace(':id', id),
        };

        const FORM_URL_TPL = @json(route('disposal.form', '__UUID__'));
        const BA_URL_TPL   = @json(route('disposal.ba', '__UUID__'));

        // ----------------------------
        // Basic create / edit modal
        // ----------------------------
        $('#btnOpenDisposal').on('click', function (e) {
            e.preventDefault();
            resetDisposalForm();
            $('#modal-disposal').modal('show');
        });

        function resetDisposalForm() {
            $('#ds-edit-id').val('');
            $('#ds-note').val('');
            $('#ds-file').val('');
            $('#ds-remove-file').val('0');
            $('#ds-current-file').addClass('d-none');
            $('#ds-current-file-link').attr('href', '#').text('');
        }

        $('#ds-btn-remove-file').off('click').on('click', function () {
            $('#ds-remove-file').val('1');
            $('#ds-current-file').addClass('d-none');
        });

        $('#formDisposal').off('submit').on('submit', function (e) {
            e.preventDefault();

            const isEdit = !!$('#ds-edit-id').val();
            const id     = $('#ds-edit-id').val();
            const fileEl = document.getElementById('ds-file');
            const hasFile = fileEl && fileEl.files && fileEl.files.length > 0;

            const base = {
                asset_uuid: '{{ $asset->uuid }}',
                note: $('#ds-note').val() || '',
                remove_file: $('#ds-remove-file').val() || 0,
            };

            Swal.fire({
                title: 'Saving…',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            let req;
            if (hasFile) {
                const fd = new FormData();
                Object.entries(base).forEach(([k, v]) => fd.append(k, v));
                fd.append('file', fileEl.files[0]);
                if (isEdit) fd.append('_method', 'PUT');

                req = $.ajax({
                    url:  isEdit ? DS.update.replace(':id', id) : DS.create,
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                });
            } else {
                req = isEdit
                    ? $.ajax({ url: DS.update.replace(':id', id), type: 'PUT', data: base })
                    : $.post(DS.create, base);
            }

            req.done(() => {
                $('#modal-disposal').modal('hide');
                Swal.fire('Success', isEdit ? 'Disposal updated.' : 'Disposal created.', 'success');
                $('#tbl-disposal').DataTable().ajax.reload(null, false);
            }).fail(x => {
                Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
            });
        });

        // ----------------------------
        // DataTable (asset history)
        // ----------------------------
        const dsTable = $('#tbl-disposal').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: DS.data,
                type: 'GET'
            },
            order: [[7, 'desc']],
            dom:
                "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                "<'table-responsive'tr>" +
                "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
            columns: [
                { data: 'disposal_code', name: 'disposal_code' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: () => 'DISPOSAL'
                },
                { data: 'pic_request_uid', name: 'pic_request_uid' },
                {
                    data: 'pic_approve_uid',
                    name: 'pic_approve_uid',
                    defaultContent: ''
                },
                {
                    data: 'note',
                    name: 'note',
                    render: d => d ? $('<div>').text(d).html() : ''
                },
                {
                    data: 'workflow_label',
                    name: 'workflow_label',
                    render: (d, t, row) => {
                        if (row.kode_status === 'APR') {
                            return `<span class="badge badge-light-primary">${d || ''}</span>`;
                        } else if (row.kode_status === 'ACC') {
                            return `<span class="badge badge-light-success">${d || ''}</span>`;
                        }
                        return `<span class="badge badge-light-danger">${d || ''}</span>`;
                    }
                },
                {
                    data: 'file',
                    name: 'file',
                    orderable: false,
                    searchable: false,
                    defaultContent: ''
                },
                {
                    data: 'updated_at',
                    name: 'updated_at',
                    render: (iso, type) => {
                        if (!iso) return '';
                        if (type === 'sort' || type === 'type') return iso;
                        const d = new Date(iso);
                        const dateStr = new Intl.DateTimeFormat('en-GB', {
                            timeZone: 'Asia/Jakarta',
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric'
                        }).format(d);
                        const timeStr = new Intl.DateTimeFormat('en-GB', {
                            timeZone: 'Asia/Jakarta',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d);
                        return `${dateStr} ${timeStr}`;
                    }
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            rowCallback: function (row, data) {
                const $actions = $('td:last', row);
                const $group   = $actions.find('.btn-group');
                if (!$group.length) return;

                const formUrl = data.form_file_url && data.form_file_url.length
                    ? data.form_file_url
                    : FORM_URL_TPL.replace('__UUID__', encodeURIComponent(data.uuid));

                const baUrl = data.ba_file_url && data.ba_file_url.length
                    ? data.ba_file_url
                    : BA_URL_TPL.replace('__UUID__', encodeURIComponent(data.uuid));

                if (!$group.find('.btn-ds-form').length) {
                    const label = data.flow_file_name || 'Form';
                    const btn = `
                        <button type="button"
                                class="btn btn-light-info btn-sm btn-ds-form"
                                onclick="window.open('${formUrl}','_blank')">
                            ${escapeHtml(label)}
                        </button>`;
                    $group.prepend(btn);
                }

                if (!$group.find('.btn-ds-ba').length) {
                    const label = data.ba_file_name || 'BA';
                    const btn = `
                        <button type="button"
                                class="btn btn-light-warning btn-sm btn-ds-ba"
                                onclick="window.open('${baUrl}','_blank')">
                            ${escapeHtml(label)}
                        </button>`;
                    $group.prepend(btn);
                }
            }
        });

        // ----------------------------
        // Row actions (edit / reject / delete)
        // ----------------------------
        $(document).on('click', '.btn-ds-edit', function () {
            const id = $(this).data('id');

            $.getJSON(DS.show.replace(':id', id))
                .done(d => {
                    resetDisposalForm();
                    $('#ds-edit-id').val(d.uuid);
                    $('#ds-note').val(d.note || '');

                    if (d.file_url) {
                        $('#ds-current-file-link')
                            .attr('href', d.file_url)
                            .text(d.file_name || 'Current file');
                        $('#ds-current-file').removeClass('d-none');
                        $('#ds-remove-file').val('0');
                    } else {
                        $('#ds-current-file').addClass('d-none');
                        $('#ds-remove-file').val('0');
                    }

                    $('#modal-disposal').modal('show');
                })
                .fail(() => {
                    Swal.fire('Error', 'Cannot load disposal.', 'error');
                });
        });

        $(document).on('click', '.btn-ds-reject', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Reject?',
                icon: 'warning',
                showCancelButton: true
            }).then(res => {
                if (!res.isConfirmed) return;

                Swal.fire({
                    title: 'Updating…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.post(DS.reject.replace(':id', id))
                    .done(() => {
                        Swal.fire('Rejected', 'Disposal rejected.', 'success');
                        dsTable.ajax.reload(null, false);
                    })
                    .fail(x => {
                        Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                    });
            });
        });

        $(document).on('click', '.btn-ds-delete', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Delete?',
                text: 'Move to trash.',
                icon: 'warning',
                showCancelButton: true
            }).then(res => {
                if (!res.isConfirmed) return;

                Swal.fire({
                    title: 'Deleting…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.ajax({
                    url: DS.destroy.replace(':id', id),
                    type: 'DELETE'
                }).done(() => {
                    Swal.fire('Deleted', 'Disposal deleted.', 'success');
                    dsTable.ajax.reload(null, false);
                }).fail(x => {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                });
            });
        });

        // ----------------------------
        // NEW: Approval Flow Modal
        // ----------------------------
        const $flowModal       = $('#modal-disposal-flow');
        const $flowSteps       = $('#flow-steps');
        const $flowBaInput     = $('#flow-ba-file-input');
        const $flowFormInput   = $('#flow-form-file-input');
        const $flowBaWrapper   = $('#flow-wrap-ba-upload');
        const $flowFormWrapper = $('#flow-wrap-form-upload');
        const $flowAsset       = $('#flow-asset');
        const $flowCode        = $('#flow-code');
        const $flowStatus      = $('#flow-status');
        const $flowFormOut     = $('#flow-form-file');
        const $flowBaOut       = $('#flow-ba-file');

        function resetFlowModal() {
            const form = document.getElementById('formDisposalFlow');
            if (form) form.reset();

            $('#flow-disposal-id').val('');

            $flowSteps.empty();
            $flowAsset.text('');
            $flowCode.text('');
            $flowStatus.text('');
            $flowFormOut.html('—');
            $flowBaOut.html('—');

            if ($flowBaWrapper.length)   $flowBaWrapper.removeClass('d-none');
            if ($flowFormWrapper.length) $flowFormWrapper.removeClass('d-none');

            $flowModal.data('require-ba', false);
        }

        function openDisposalFlow(id) {
            if (!id) return;

            resetFlowModal();
            $('#flow-disposal-id').val(id);

            Swal.fire({
                title: 'Loading flow…',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            $.getJSON(DS.show.replace(':id', id))
                .done(d => {
                    Swal.close();

                    const assetLabel =
                        ((d.asset_code || '') +
                            (d.asset_name ? ' - ' + d.asset_name : '')) || d.asset_uuid || '';
                    $flowAsset.text(assetLabel);
                    $flowCode.text(d.disposal_code || '');
                    $flowStatus.text(d.kode_status || '');

                    // Form summary section
                    if (d.flow_file_url) {
                        $flowFormOut.html(
                            `<a href="${d.flow_file_url}" target="_blank">${escapeHtml(d.flow_file_name || 'Download Form')}</a>`
                        );
                    } else {
                        const url = FORM_URL_TPL.replace('__UUID__', encodeURIComponent(d.uuid));
                        $flowFormOut.html(
                            `<a href="${url}" target="_blank">Download auto-filled form</a>`
                        );
                    }

                    // BA summary section
                    if (d.ba_file_url) {
                        $flowBaOut.html(
                            `<a href="${d.ba_file_url}" target="_blank">${escapeHtml(d.ba_file_name || 'Download BA')}</a>`
                        );
                    } else {
                        const url = BA_URL_TPL.replace('__UUID__', encodeURIComponent(d.uuid));
                        $flowBaOut.html(
                            `<a href="${url}" target="_blank">Download auto-filled BA</a>`
                        );
                    }

                    let flow = d.flow;
                    if (typeof flow === 'string') {
                        try { flow = JSON.parse(flow); } catch (e) { flow = null; }
                    }
                    renderFlowSteps(flow || {});
                    $flowModal.modal('show');
                })
                .fail(x => {
                    Swal.fire('Error', x.responseJSON?.message || 'Cannot load flow', 'error');
                });
        }

        function renderFlowSteps(flow) {
            $flowSteps.empty();

            const steps = Array.isArray(flow.steps) ? flow.steps : [];
            if (!steps.length) {
                $flowSteps.append('<div class="text-muted">No flow defined.</div>');
                $flowModal.data('require-ba', false);
                return;
            }

            const firstPendingIndex = steps.findIndex(s => !s.approved_at);
            const lastIndex         = steps.length - 1;
            const requireBa         = firstPendingIndex !== -1 && firstPendingIndex === lastIndex;

            // BA required only on last step; both upload blocks are always visible
            $flowModal.data('require-ba', requireBa);

            if ($flowBaWrapper.length)   $flowBaWrapper.removeClass('d-none');
            if ($flowFormWrapper.length) $flowFormWrapper.removeClass('d-none');

            steps.forEach((st, idx) => {
                const isApproved = !!st.approved_at;
                const isNext     = !isApproved && idx === firstPendingIndex;

                const statusBadge = isApproved
                    ? '<span class="badge badge-light-success">Approved</span>'
                    : (isNext
                        ? '<span class="badge badge-light-warning">Pending (Next)</span>'
                        : '<span class="badge badge-light-secondary">Waiting</span>');

                const info = isApproved
                    ? `<div class="small text-muted">by ${escapeHtml(st.approved_by || '-')} at ${escapeHtml(st.approved_at || '')}</div>`
                    : '';

                const li = `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">${escapeHtml(st.label || st.code || '')}</div>
                            <div class="text-muted small">${escapeHtml(st.role || '')}</div>
                            ${info}
                        </div>
                        <div>${statusBadge}</div>
                    </li>
                `;
                $flowSteps.append(li);
            });
        }

        // open flow from table action
        $(document).on('click', '.btn-ds-approve', function () {
            const id = $(this).data('id');
            openDisposalFlow(id);
        });

        // Approve next step (submit flow form)
        $('#formDisposalFlow').off('submit').on('submit', function (e) {
            e.preventDefault();

            const id = $('#flow-disposal-id').val();
            if (!id) return;

            const requireBa = !!$flowModal.data('require-ba');
            const baEl = $flowBaInput.length ? $flowBaInput[0] : null;
            const hasBa = baEl && baEl.files && baEl.files.length > 0;

            if (requireBa && !hasBa) {
                Swal.fire('Required', 'Berita Acara must be uploaded on the last step.', 'warning');
                return;
            }

            const fd = new FormData(this);

            Swal.fire({
                title: 'Approving…',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            $.ajax({
                url: DS.approveStep(id),
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false
            }).done(res => {
                Swal.close();

                if (res.flow) {
                    let flow = res.flow;
                    if (typeof flow === 'string') {
                        try { flow = JSON.parse(flow); } catch (e) { flow = null; }
                    }
                    renderFlowSteps(flow || {});
                }

                if (res.flow_file_url && $flowFormOut.length) {
                    $flowFormOut.html(
                        `<a href="${res.flow_file_url}" target="_blank">${escapeHtml(res.flow_file_name || 'Download Form')}</a>`
                    );
                }

                if (res.ba_file_url && $flowBaOut.length) {
                    $flowBaOut.html(
                        `<a href="${res.ba_file_url}" target="_blank">${escapeHtml(res.ba_file_name || 'Download BA')}</a>`
                    );
                }

                dsTable.ajax.reload(null, false);

                if (res.completed || res.kode_status === 'ACC') {
                    $('#modal-disposal-flow').modal('hide');
                    Swal.fire('Approved', 'Disposal flow completed.', 'success')
                        .then(() => { location.reload(); });
                } else {
                    Swal.fire('Approved', 'Step approved.', 'success');
                }
            }).fail(x => {
                Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
            });
        });

        function escapeHtml(text) {
            return $('<div/>').text(text || '').html();
        }
    })();
</script>
@endpush


    <script>
        (function() {
            const R_RET = {
                options: '{{ route('return.options') }}',
                store: '{{ route('return.store') }}',
                data: '{{ route('return.data.asset', $asset->uuid) }}',
                destroy: id => '{{ route('return.destroy', ':id') }}'.replace(':id', id),
            };
            const dtRet = $('#tbl-returns').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: R_RET.data,
                    type: 'GET'
                },
                order: [
                    [5, 'desc']
                ],
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                columns: [{
                        data: 'return_code',
                        name: 'return_history.return_code'
                    }, {
                        data: 'source_code',
                        name: 'return_history.source_code'
                    },
                    {
                        data: 'source_type_label',
                        name: 'return_history.source_type'
                    },
                    {
                        data: 'source_detail',
                        name: 'source_detail',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'note',
                        name: 'return_history.note',
                        render: d => d ? $('<div>').text(d).html() : ''
                    },
                    {
                        data: 'pic_request_uid',
                        name: 'return_history.pic_request_uid'
                    },
                    {
                        data: 'created_at',
                        name: 'return_history.created_at',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;
                            const d = new Date(iso);
                            const dateStr = new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            }).format(d);
                            const timeStr = new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            }).format(d);
                            return `${dateStr} ${timeStr}`;
                        }
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        defaultContent: ''
                    }
                ]
            });

            function initReturnSelect() {
                $('#ret-source').select2({
                    dropdownParent: $('#modal-return'),
                    placeholder: 'Search code / asset…',
                    width: '100%',
                    allowClear: true,
                    ajax: {
                        url: R_RET.options,
                        dataType: 'json',
                        delay: 150,
                        data: params => ({
                            q: params.term || '',
                            asset_uuid: '{{ $asset->uuid }}'
                        }),
                        processResults: d => d
                    }
                }).on('select2:select', function(e) {
                    const m = e.params.data || {};
                    $('#ret-preview').show();
                    $('#ret-asset').text(m.asset_label || '');
                    $('#ret-type').text((m.source_type === 'transfer' ? (m.type || '').toUpperCase() :
                        'DISPOSAL'));
                    $('#ret-code').text(m.code || '');

                    if (m.source_type === 'transfer') {
                        $('#ret-ba-wrap').show();
                        $('#ret-before').text(m.before_label || '');
                        $('#ret-after').text(m.after_label || '');
                    } else {
                        $('#ret-ba-wrap').hide();
                        $('#ret-before').text('');
                        $('#ret-after').text('');
                    }
                }).on('select2:clear', function() {
                    $('#ret-preview').hide();
                    $('#ret-asset,#ret-type,#ret-code,#ret-before,#ret-after').text('');
                });
            }

            $('#btnOpenReturn').on('click', function(e) {
                e.preventDefault();

                $('#formReturn')[0].reset();
                if ($('#ret-source').data('select2')) {
                    $('#ret-source').val(null).trigger('change');
                } else {
                    initReturnSelect();
                }
                $('#ret-preview').hide();
                $('#modal-return').modal('show');
            });
            // window.openReturnModal = function() {
            // };

            $('#formReturn').on('submit', function(e) {
                e.preventDefault();
                const id = $('#ret-source').val();
                if (!id) {
                    Swal.fire('Required', 'Please select an item.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.post(R_RET.store, {
                    source: id,
                    note: $('textarea[name="note"]').val() || ''
                }).done(() => {
                    $('#modal-return').modal('hide');
                    Swal.fire('Success', 'Return recorded.', 'success');
                    if ($.fn.DataTable.isDataTable('#tbl-returns')) {
                        $('#tbl-returns').DataTable().ajax.reload(null, false);
                    }
                    if ($.fn.DataTable.isDataTable('#tbl-transfers')) {
                        $('#tbl-transfers').DataTable().ajax.reload(null, false);
                    }
                    location.reload();
                }).fail(x => {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed to save', 'error');
                });
            });
            $('#tbl-returns').on('click', '.btn-ret-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete this return?',
                    text: 'It will go to trash.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6'
                }).then(res => {
                    if (!res.isConfirmed) return;
                    Swal.fire({
                        title: 'Deleting…',
                        didOpen: () => Swal.showLoading(),
                        allowOutsideClick: false
                    });
                    $.ajax({
                            url: R_RET.destroy(id),
                            type: 'DELETE'
                        })
                        .done(() => {
                            Swal.fire('Deleted', '', 'success');
                            dtRet.ajax.reload(null, false);
                        })
                        .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                });
            });

        })();
    </script>
    <script>
        (function() {
            const ACQ = {
                latest: '{{ route('acquisition.latest', $asset->uuid) }}',
                store: '{{ route('acquisition.store', $asset->uuid) }}',
                data: '{{ route('acquisition.data', $asset->uuid) }}',
            };

            // ==== Lock / unlock fields based on existing acquisition ====
            function setExistingMode(isExisting) {
                const lockInputs = [
                    'input[name="price"]',
                    'input[name="useful_life_month"]',
                    'input[name="actual_date"]',
                    'input[name="capitalization_date"]',
                    '#nilai_pajak'
                ];

                lockInputs.forEach(sel => {
                    const $el = $(sel);
                    $el.prop('readonly', isExisting);
                    $el.toggleClass('bg-light', isExisting);
                });

                $('select[name="is_pajak"]')
                    .prop('disabled', isExisting)
                    .toggleClass('bg-light', isExisting);

                // quantity & note always editable
                $('input[name="quantity"]').prop('readonly', false).removeClass('bg-light');
                $('textarea[name="note"]').prop('readonly', false).removeClass('bg-light');
                $('#acq-uom').prop('disabled', false);
            }

            function clearAcqForm() {
                if ($('#formAcq')[0]) {
                    $('#formAcq')[0].reset();
                }
                if ($('#acq-uom').data('select2')) {
                    $('#acq-uom').val(null).trigger('change');
                }
                setExistingMode(false);
            }

            // ==== Open Modal & Prefill from latest acquisition ====
            $('#btnOpenAcq').on('click', function(e) {
                e.preventDefault();

                clearAcqForm();

                $.getJSON(ACQ.latest)
                    .done(res => {
                        const today = new Date().toISOString().slice(0, 10);

                        if (!res || !res.value) {
                            // no data at all → treat as new, fully editable
                            $('input[name="actual_date"]').val(today);
                            $('input[name="capitalization_date"]').val(today);
                            setExistingMode(false);
                            $('#modal-acq').modal('show');
                            return;
                        }

                        const v = res.value;

                        $('input[name="quantity"]').val(v.quantity ?? '');
                        $('input[name="price"]').val(v.price ?? '');
                        $('input[name="useful_life_month"]').val(v.useful_life_month ?? '');
                        $('select[name="is_pajak"]').val((v.is_pajak ?? 0) ? '1' : '0');

                        if (typeof v.nilai_pajak !== 'undefined' && v.nilai_pajak !== null) {
                            $('#nilai_pajak').val(v.nilai_pajak);
                        }

                        if (v.actual_date) {
                            $('input[name="actual_date"]').val(String(v.actual_date).substring(0, 10));
                        } else {
                            $('input[name="actual_date"]').val(today);
                        }

                        if (v.capitalization_date) {
                            $('input[name="capitalization_date"]').val(String(v.capitalization_date)
                                .substring(0, 10));
                        } else {
                            $('input[name="capitalization_date"]').val(today);
                        }

                        const kode = v.kode_uom;
                        const text = v.uom_text || kode;
                        if (kode) {
                            const opt = new Option(text, kode, true, true);
                            $('#acq-uom').append(opt).trigger('change');
                        }

                        // === NEW LOGIC:
                        // if total == 0 or null → editable
                        // if total != 0 → disabled (existing acquisition already applied)
                        const totalRaw = v.total;
                        const shouldLock =
                            totalRaw !== null &&
                            totalRaw !== undefined &&
                            Number(totalRaw) !== 0;

                        setExistingMode(shouldLock);

                        $('#modal-acq').modal('show');
                    })
                    .fail(() => {
                        const today = new Date().toISOString().slice(0, 10);
                        $('input[name="actual_date"]').val(today);
                        $('input[name="capitalization_date"]').val(today);
                        setExistingMode(false);
                        $('#modal-acq').modal('show');
                    });
            });

            // ==== UOM Select2 ====
            $('#acq-uom').select2({
                dropdownParent: $('#modal-acq'),
                placeholder: 'Select UOM…',
                width: '100%',
                allowClear: true,
                ajax: {
                    url: '{{ route('master.uom.options') }}',
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1
                    }),
                    processResults: d => d
                }
            });

            // ==== Submit Acquisition ====
            $('#formAcq').on('submit', function(e) {
                e.preventDefault();

                const payload = {
                    quantity: $('input[name="quantity"]').val(),
                    kode_uom: $('#acq-uom').val(),
                    price: $('input[name="price"]').val(),
                    useful_life_month: $('input[name="useful_life_month"]').val(),
                    is_pajak: $('select[name="is_pajak"]').val(),
                    actual_date: $('input[name="actual_date"]').val(),
                    capitalization_date: $('input[name="capitalization_date"]').val(),
                    nilai_pajak: $('input[name="nilai_pajak"]').val(),
                    note: $('textarea[name="note"]').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.post(ACQ.store, payload)
                    .done(() => {
                        $('#modal-acq').modal('hide');
                        Swal.fire('Saved', 'Acquisition saved.', 'success');

                        if ($.fn.DataTable.isDataTable('#tbl-acq')) {
                            $('#tbl-acq').DataTable().ajax.reload(null, false);
                        }

                        // refresh summary/value di atas
                        setTimeout(() => location.reload(), 400);
                    })
                    .fail(x => {
                        Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                    });
            });

            // ==== Acquisition History DataTable ====
            const tblAcq = $('#tbl-acq').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: ACQ.data,
                    type: 'GET'
                },
                order: [
                    [3, 'desc']
                ], // created_at desc
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                columns: [{
                        data: 'acq_code',
                        name: 'acq_code'
                    },
                    {
                        data: 'detail',
                        name: 'detail',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'note',
                        name: 'note',
                        render: d => d ? $('<div>').text(d).html() : ''
                    },
                    {
                        data: 'pic_request_uid',
                        name: 'pic_request_uid'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;

                            const d = new Date(iso);
                            const dateStr = new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            }).format(d);
                            const timeStr = new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            }).format(d);

                            return `${dateStr} ${timeStr}`;
                        }
                    }
                ]
            });

            // ==== Delete Acquisition Row ====
            $('#tbl-acq').on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                if (!id) return;

                Swal.fire({
                    title: 'Delete?',
                    text: 'Moved to trash',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6'
                }).then(r => {
                    if (!r.isConfirmed) return;

                    Swal.fire({
                        title: 'Deleting…',
                        didOpen: () => Swal.showLoading(),
                        allowOutsideClick: false
                    });

                    $.ajax({
                            url: '{{ route('acquisition.destroy', ':id') }}'.replace(':id', id),
                            type: 'DELETE'
                        })
                        .done(() => {
                            Swal.fire('Deleted', '', 'success');
                            tblAcq.ajax.reload(null, false);
                        })
                        .fail(x => {
                            Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                        });
                });
            });
        })();
    </script>
@endpush
