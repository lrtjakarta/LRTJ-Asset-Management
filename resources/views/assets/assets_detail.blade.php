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
                <a href="#" class="btn btn-sm fw-bold btn-secondary" id="btnOpenTransfer">Transfer</a>
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
                                    @if ($asset->status?->name)
                                        <span
                                            class="badge badge-light-{{ $asset->status->name === 'Active' ? 'success' : 'danger' }}">
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
                                    aria-selected="false">Stock Opname</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="historyTabsContent">
                        <div class="tab-pane fade show active" id="tab_transfer" role="tabpanel"
                            aria-labelledby="tab-transfer-link">
                            <table
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                                id="tbl-transfers">
                                <thead>
                                    <tr class="table-light">
                                        <th class="min-w-200px">Code</th>
                                        <th class="min-w-100px">Type</th>
                                        <th class="min-w-150px">Before</th>
                                        <th class="min-w-150px">After</th>
                                        <th class="min-w-100px">Requester</th>
                                        <th class="min-w-100px">Approver</th>
                                        <th class="min-w-250px">Note</th>
                                        <th class="min-w-100px">Status Transfer</th>
                                        <th class="min-w-200px">Created</th>
                                        <th class="min-w-200px">Action</th>
                                    </tr>
                                </thead>
                            </table>
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

    {{-- ===== Transfer Modal ===== --}}
    <div class="modal fade" id="modal-transfer" tabindex="-1" aria-labelledby="modalTransferLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formTransfer" method="POST">
                    @csrf
                    <input type="hidden" name="asset_uuid" value="{{ $asset->uuid }}">

                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTransferLabel">Request Transfer — {{ $asset->asset_code }}</h5>
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
                                <div class="form-text">Select Transfer Type</div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label required">Transfer To</label>
                                <select id="tf-target" class="form-select" required></select>
                                <input type="hidden" name="after[value]" id="tf-target-hidden">
                                <div class="form-text" id="tf-target-help">Select the new target.</div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
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
@endsection

@push('scripts')
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        (function() {
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
                    if (res.isConfirmed) $('#assetDeleteForm').trigger('submit');
                });
            });

            // Endpoints
            const R = {
                usercode: '{{ route('master.user_code.options') }}',
                status: '{{ route('master.status.options') }}',
                location: '{{ route('master.location.options') }}',
                transfers: '{{ route('transfer.data', $asset->uuid) }}',
                create: '{{ route('transfer.store') }}'
            };
            const ROUTE = {
                update: id => '{{ route('transfer.update',  ':id') }}'.replace(':id', id),
                destroy: id => '{{ route('transfer.destroy', ':id') }}'.replace(':id', id),
                approve: id => '{{ route('transfer.approve', ':id') }}'.replace(':id', id),
                reject: id => '{{ route('transfer.reject', ':id') }}'.replace(':id', id),
            };

            // Open modal
            $('#btnOpenTransfer').on('click', function(e) {
                e.preventDefault();
                $('#formTransfer')[0].reset();
                $('#tf-type').val('owner');
                initTargetSelect('owner');
                $('#modal-transfer').modal('show');
            });

            // Select2 helper
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

            // Type change
            $('#tf-type').on('change', function() {
                initTargetSelect(this.value);
            });

            // Submit Transfer
            $('#formTransfer').on('submit', function(e) {
                e.preventDefault();

                const afterVal = $('#tf-target-hidden').val() || $('#tf-target').val();
                if (!afterVal) return Swal.fire('Target required', 'Please select a transfer target.',
                    'warning');

                Swal.fire({
                    title: 'Submit transfer request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, submit',
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                }).then(res => {
                    if (!res.isConfirmed) return;

                    Swal.fire({
                        title: 'Saving…',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    $.post(R.create, {
                            _token: $('input[name="_token"]').first().val(),
                            asset_uuid: '{{ $asset->uuid }}',
                            type: $('#tf-type').val(),
                            note: $('textarea[name="note"]').val() || '',
                            'after[value]': afterVal
                        })
                        .done(() => {
                            $('#modal-transfer').modal('hide');
                            Swal.fire('Success', 'Transfer request created.', 'success');
                            $('#tbl-transfers').DataTable().ajax.reload(null, false);
                        })
                        .fail(xhr => {
                            let msg = 'Failed to submit.';
                            if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                            }
                            Swal.fire('Error', msg, 'error');
                        });
                });
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
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(row) {
                            const pending = row.kode_status === 'APR';
                            let html = `<div class="btn-group btn-group-sm">`;
                            if (pending) {
                                html +=
                                    `<button class="btn btn-light-primary btn-tf-edit" data-id="${row.uuid}">Edit</button>`;
                                html +=
                                    `<button class="btn btn-light-success btn-tf-approve" data-id="${row.uuid}">Accept</button>`;
                                html +=
                                    `<button class="btn btn-light-warning btn-tf-reject" data-id="${row.uuid}">Reject</button>`;
                            }
                            html +=
                                `<button class="btn btn-light-danger btn-tf-delete" data-id="${row.uuid}">Delete</button>`;
                            html += `</div>`;
                            return html;
                        }
                    }
                ]
            });

            $('#tbl-transfers').on('click', '.btn-tf-edit', function() {
                const row = $('#tbl-transfers').DataTable().row($(this).closest('tr')).data();
                $('#modal-transfer').modal('show');

                // set form into EDIT mode
                $('#formTransfer').data('edit-id', row.uuid);
                $('#tf-type').val(row.type).trigger('change');

                // Delay to allow select2 to mount then set target
                setTimeout(() => {
                    const code = row.after_code || '';
                    // ensure the target select contains current code
                    const opt = new Option(code, code, true, true);
                    $('#tf-target').append(opt).trigger('change');
                    $('#tf-target-hidden').val(code);
                }, 300);

                $('textarea[name="note"]').val(row.note || '');
            });

            // On submit: if edit-id present, PUT update; otherwise POST create (your current behavior)
            $('#formTransfer').off('submit').on('submit', function(e) {
                e.preventDefault();

                const isEdit = !!$('#formTransfer').data('edit-id');
                const id = $('#formTransfer').data('edit-id');

                const payload = {
                    asset_uuid: '{{ $asset->uuid }}',
                    type: $('#tf-type').val(),
                    note: $('textarea[name="note"]').val() || '',
                    'after[value]': $('#tf-target-hidden').val()
                };

                const req = isEdit ?
                    $.ajax({
                        url: ROUTE.update(id),
                        type: 'PUT',
                        data: payload
                    }) :
                    $.post('{{ route('transfer.store', $asset->uuid) }}', payload);

                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });
                req.done(() => {
                        $('#modal-transfer').modal('hide');
                        Swal.fire('Success', isEdit ? 'Transfer updated.' : 'Transfer created.', 'success');
                        $('#tbl-transfers').DataTable().ajax.reload(null, false);
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });

            // Approve
            $('#tbl-transfers').on('click', '.btn-tf-approve', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Approve this transfer?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Approve',
                        confirmButtonColor: '#28a745'
                    })
                    .then(res => {
                        if (!res.isConfirmed) return;
                        Swal.fire({
                            title: 'Applying…',
                            didOpen: () => Swal.showLoading(),
                            allowOutsideClick: false
                        });
                        $.post(ROUTE.approve(id)).done(() => {
                            Swal.fire('Approved', 'Transfer applied to asset.', 'success');
                            // we changed asset data; safest is full refresh:
                            location.reload();
                        }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

            // Reject
            $('#tbl-transfers').on('click', '.btn-tf-reject', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Reject this transfer?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Reject',
                        confirmButtonColor: '#f0ad4e'
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

            // Delete (soft)
            $('#tbl-transfers').on('click', '.btn-tf-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Delete this request?',
                        text: 'It will go to trash.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Delete',
                        confirmButtonColor: '#dc3545'
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
        })();
    </script>
@endpush
