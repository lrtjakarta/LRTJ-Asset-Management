@php
    use Carbon\Carbon;
    $currentMonth = Carbon::now()->startOfMonth()->toDateString();
    $currentMonthText = Carbon::now()->isoFormat('MMMM YYYY');
    $prevYearLabel = Carbon::now()->year - 1;
    $currentYear = Carbon::parse($currentMonth)->year;
@endphp

@extends('layouts.app')

@section('title', 'Depreciation Transfer Requests')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Transfer Requests
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Transaction</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Transfer Requests</li>
                </ul>
            </div>
        </div>
    </div>


    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card mb-6">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Transfer Type</label>
                            <select id="f-type" class="form-select">
                                <option value="">All</option>
                                <option value="tf-val">1. Partials/Full (Gross only)</option>
                                <option value="acq_fix">2. Acquisition Fix</option>
                                <option value="carry_over_gross_accum">3. Carry-Over (Gross + Accum)</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Requester</label>
                            <select id="f-requester" class="form-select"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status Approval</label>
                            <select id="f-status" class="form-select">
                                <option value="">All</option>
                                <option value="APR">APR - Waiting</option>
                                <option value="ACC">ACC - Accepted</option>
                                <option value="REJ">REJ - Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Request From</label>
                            <input type="date" id="f-req-from" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Request To</label>
                            <input type="date" id="f-req-to" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Approve From</label>
                            <input type="date" id="f-apr-from" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Approve To</label>
                            <input type="date" id="f-apr-to" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Asset (code / description)</label>
                            <input type="text" id="f-asset" class="form-control"
                                placeholder="e.g. A1101000002-00 / Laptop Dell">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="btnFilter" class="btn btn-danger btn-sm me-2">Apply Filter</button>
                    <button id="btnReset" class="btn btn-light-danger btn-sm me-2">Reset</button>
                    <button id="btnExport" class="btn btn-light-danger btn-sm">Export Excel</button>
                </div>
            </div>
            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <h3 class="card-title mb-1">
                            <span class="fw-semibold">Transfer Requests Data</span>
                        </h3>
                    </div>
                    @canAction('TRANSFER','C')
                    <div class="card-toolbar">
                        {{-- Transfer Value --}}
                        <button type="button" id="btn-open-transfer" class="btn btn-danger btn-sm me-2">
                            <i class="ki-duotone ki-plus fs-2"></i>
                            Add New
                        </button>
                    </div>
                    @endcanAction
                </div>

                <div class="card-body">
                    <table id="tbl-transfer-requests"
                        class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="min-w-150px">Transaction Number</th>
                                <th class="min-w-150px">From Asset</th>
                                <th class="min-w-150px">To Asset</th>
                                <th class="min-w-150px">Type</th>
                                <th class="min-w-150px">Amount</th>
                                <th class="min-w-150px">Actual Date</th>
                                <th class="min-w-150px">Status</th>
                                <th class="min-w-150px">Requested By</th>
                                <th class="min-w-150px">Approved By</th>
                                <th class="min-w-150px">Attachment</th>
                                <th class="min-w-200px">Note</th>
                                <th class="min-w-200px ">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Transfer Request Modal --}}
    <div class="modal fade" id="modal-edit-transfer-request" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Transfer Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-edit-transfer-request" autocomplete="off" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="edit-uuid" name="uuid">

                    <div class="modal-body py-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">From Asset</label>
                                <input type="text" class="form-control" id="edit-from-asset" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Asset</label>
                                <input type="text" class="form-control" id="edit-to-asset" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Transfer Type</label>
                                <select class="form-select" id="edit-transfer-type" name="transfer_type">
                                    <option value="tf-val">1. Partials/Full (Gross only)</option>
                                    <option value="acq_fix">2. Acquisition Fix</option>
                                    <option value="carry_over_gross_accum">3. Carry-Over (Gross + Accum)</option>
                                </select>
                                <small class="text-muted">
                                    Type changes are allowed only while status is APR (waiting for approval).
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Amount</label>
                                <input type="number" step="0.01" min="0.01" class="form-control"
                                    id="edit-amount" name="amount">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Actual Date</label>
                                <input type="date" class="form-control" id="edit-actual-date" name="actual_date">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <input type="text" class="form-control" id="edit-status-text" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea class="form-control" id="edit-note" name="note" rows="3" maxlength="300"></textarea>
                            </div>

                            <div class="col-12 mt-4">
                                <label class="form-label">Current Attachment</label>
                                <div id="edit-attachment-current" class="mb-2 text-muted">-</div>

                                <label class="form-label">Replace Attachment (optional)</label>
                                <input type="file" class="form-control" id="edit-attachment" name="attachment">
                                <small class="text-muted">Leave empty to keep the existing attachment.</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-update-transfer-request" class="btn btn-primary">
                            <span class="indicator-label">Save Changes</span>
                            <span class="indicator-progress">
                                Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- Transfer Value Modal --}}
    <div class="modal fade" id="modal-transfer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Transfer Value</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-transfer" autocomplete="off" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body py-4">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label"><b>Transfer Type :</b></label>
                                <div class="btn-group" role="group" aria-label="Transfer type"
                                    style="border: solid .5px black; width:100%;">
                                    {{-- 1. tf-val (existing case 1) --}}
                                    <input type="radio" class="btn-check" name="tr-type" id="tr-type-tf-val"
                                        value="tf-val" checked>
                                    <label class="btn btn-outline-danger btn-sm" for="tr-type-tf-val">
                                        1. Partials/Full (Gross only)
                                    </label>

                                    {{-- 2. Acquisition Fix --}}
                                    <input type="radio" class="btn-check" name="tr-type" id="tr-type-acq-fix"
                                        value="acq_fix">
                                    <label class="btn btn-outline-danger btn-sm" for="tr-type-acq-fix">
                                        2. Acquisition Fix
                                    </label>

                                    {{-- 3. Carry-Over (Gross + Accum) --}}
                                    <input type="radio" class="btn-check" name="tr-type" id="tr-type-carry"
                                        value="carry_over_gross_accum">
                                    <label class="btn btn-outline-danger btn-sm" for="tr-type-carry">
                                        3. Carry-Over (Gross + Accum)
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">From Asset</label>
                                <select id="tr-from-asset" class="form-select" style="width:100%"></select>
                                <input type="hidden" id="tr-from-uuid" name="from_asset_uuid">
                                <div id="tf-asset-snapshot-1" class="mt-4 d-none">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="fw-semibold mb-2">Current Asset Info</div>

                                        <div class="row gy-2">
                                            <div class="col-6">
                                                <div class="text-muted">Owner</div>
                                                <div id="snap-owner-1" class="fw-semibold"></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">User</div>
                                                <div id="snap-user-1" class="fw-semibold"></div>
                                            </div>

                                            <div class="col-6">
                                                <div class="text-muted">Maintenance</div>
                                                <div id="snap-maintenance-1" class="fw-semibold"></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">Status</div>
                                                <div id="snap-status-1" class="fw-semibold"></div>
                                            </div>

                                            <div class="col-12">
                                                <div class="text-muted">Location</div>
                                                <div id="snap-location-1" class="fw-semibold"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">To Asset</label>
                                <select id="tr-to-asset" class="form-select" style="width:100%"></select>
                                <input type="hidden" id="tr-to-uuid" name="to_asset_uuid">

                                <div id="tf-asset-snapshot-2" class="mt-4 d-none">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="fw-semibold mb-2">Current Asset Info</div>

                                        <div class="row gy-2">
                                            <div class="col-6">
                                                <div class="text-muted">Owner</div>
                                                <div id="snap-owner-2" class="fw-semibold"></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">User</div>
                                                <div id="snap-user-2" class="fw-semibold"></div>
                                            </div>

                                            <div class="col-6">
                                                <div class="text-muted">Maintenance</div>
                                                <div id="snap-maintenance-2" class="fw-semibold"></div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">Status</div>
                                                <div id="snap-status-2" class="fw-semibold"></div>
                                            </div>

                                            <div class="col-12">
                                                <div class="text-muted">Location</div>
                                                <div id="snap-location-2" class="fw-semibold"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Amount</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="tr-amount"
                                    name="amount" placeholder="e.g. 15000000">
                                <small id="tr-cap-help" class="text-muted d-block mt-1"></small>
                                <small id="tr-carry-help" class="text-warning d-block mt-1"></small>
                                <div id="tr-preview" class="alert d-none py-2 px-3 mt-2 small"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Actual Date</label>
                                <input type="date" class="form-control" id="tr-date" name="actual_date"
                                    value="{{ Carbon::now()->toDateString() }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Attachment (Optional)</label>
                                <input type="file" class="form-control" id="tr-attachment" name="attachment">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Note (Optional)</label>
                                <textarea class="form-control" id="tr-note" name="note" placeholder="Optional note"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-submit-transfer" class="btn btn-primary">
                            <span class="indicator-label">Submit Transfer</span>
                            <span class="indicator-progress">
                                Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        (function() {
            const money2 = v => new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(v ?? 0));

            const ATTACH_URL_TPL = @json(route('depreciation.transfer-requests.attachment', '__UUID__'));
            const APPROVE_URL_TPL = @json(route('depreciation.transfer-requests.approve', '__UUID__'));
            const REJECT_URL_TPL = @json(route('depreciation.transfer-requests.reject', '__UUID__'));
            const UPDATE_URL_TPL = @json(route('depreciation.transfer-requests.update', '__UUID__'));
            const DELETE_URL_TPL = @json(route('depreciation.transfer-requests.destroy', '__UUID__'));
            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));

            const $modalEdit = new bootstrap.Modal(document.getElementById('modal-edit-transfer-request'));
            const $formEdit = $('#form-edit-transfer-request');
            const $btnUpdate = $('#btn-update-transfer-request');
            const $modal = new bootstrap.Modal(document.getElementById('modal-transfer'));
            const PERIOD = "{{ $currentMonth }}";
            $('#f-requester').select2({
                placeholder: 'Select requester',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('users.options') }}",
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1
                    }),
                    processResults: d => d
                }
            });

            // Open Transfer modal
            $('#btn-open-transfer').on('click', () => {
                $('#form-transfer')[0].reset();
                $('#tr-from-uuid, #tr-to-uuid').val('');
                $('#tr-from-asset, #tr-to-asset').val(null).trigger('change');
                $('#tr-type-tf-val').prop('checked', true);
                $('#tr-cap-help').text('');
                $('#tr-carry-help').text('');
                $('#tr-preview').addClass('d-none').empty();
                $modal.show();
                syncTypeUI();
            });

            // select2 init (transfer modal)
            const initAssetSelect = ($el) => {
                $el.select2({
                    dropdownParent: $('#modal-transfer'),
                    placeholder: 'Search asset code/name…',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('assets.options') }}",
                        dataType: 'json',
                        delay: 250,
                        data: params => ({
                            q: params.term || '',
                            page: params.page || 1
                        }),
                        processResults: data => data
                    }
                }).on('select2:select', function(e) {
                    if (this.id === 'tr-from-asset') {
                        const assetUuid = e.params.data.id;
                        fetchAssetSnapshot1(assetUuid);
                    }
                    if (this.id === 'tr-to-asset') {
                        const assetUuid = e.params.data.id;
                        fetchAssetSnapshot2(assetUuid);
                    }
                }).on('select2:clear', function() {
                    if (this.id === 'tr-from-asset') {
                        clearSnapshot1();
                    }
                    if (this.id === 'tr-to-asset') {
                        clearSnapshot2();
                    }
                });
            };
            initAssetSelect($('#tr-from-asset'));
            initAssetSelect($('#tr-to-asset'));

            const setBusyUpdate = (b) => {
                if (b) $btnUpdate.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnUpdate.removeAttr('data-kt-indicator').prop('disabled', false);
            };

            const statusBadge = (kode) => {
                const k = (kode || '').toUpperCase();
                if (k === 'ACC') return '<span class="badge badge-light-success">Accepted</span>';
                if (k === 'REJ') return '<span class="badge badge-light-danger">Rejected</span>';
                return '<span class="badge badge-light-primary">Waiting for Approval</span>';
            };

            const typeLabel = (t) => {
                if (t === 'acq_fix') return '2. Acquisition Fix';
                if (t === 'carry_over_gross_accum') return '3. Carry-Over (Gross + Accum)';
                return '1. Partials/Full (Gross only)';
            };
            const TR_PERMS = {
                edit: @json(auth()->user()?->hasAction('TRANSFER', 'U') ?? false),
                approve: @json(auth()->user()?->hasAction('TRANSFER', 'APR') ?? false),
                delete: @json(auth()->user()?->hasAction('TRANSFER', 'D') ?? false),
            };
            const tbl = $('#tbl-transfer-requests').DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 400,
                scrollX: true,
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                ajax: {
                    url: "{{ route('depreciation.transfer-requests.dt') }}",
                    data: d => {
                        d.type = $('#f-type').val() || '';
                        d.requester = $('#f-requester').val() || '';
                        d.status = $('#f-status').val() || '';
                        d.req_from = $('#f-req-from').val() || '';
                        d.req_to = $('#f-req-to').val() || '';
                        d.apr_from = $('#f-apr-from').val() || '';
                        d.apr_to = $('#f-apr-to').val() || '';
                        d.asset_q = $('#f-asset').val() || '';
                    }
                },
                order: [
                    [4, 'desc']
                ], // actual_date
                columns: [{
                        data: 'transfer_code',
                        name: 'transfer_code'
                    },
                    // From asset
                    {
                        data: 'from_code',
                        name: 'from_asset',
                        render: function(data, type, row) {
                            const code = row.from_code || '';
                            const name = row.from_name || '';
                            if (type !== 'display') return code;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row
                                .from_asset_uuid));
                            const text = code || data || '';
                            return `
                        <div class="fw-semibold">
                            <a href="${url}" class="text-primary fw-semibold">${text}</a>
                        </div>
                        <div class="text-muted fs-7">${name}</div>
                    `;
                        }
                    },
                    // To asset
                    {
                        data: 'to_code',
                        name: 'to_asset',
                        render: function(data, type, row) {
                            const code = row.to_code || '';
                            const name = row.to_name || '';
                            if (type !== 'display') return code;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row
                                .to_asset_uuid));
                            const text = code || data || '';
                            return `
                        <div class="fw-semibold">
                            <a href="${url}" class="text-primary fw-semibold">${text}</a>
                        </div>
                        <div class="text-muted fs-7">${name}</div>
                    `;
                        }
                    },
                    // Type
                    {
                        data: 'transfer_type',
                        name: 'transfer_type',
                        render: function(data) {
                            return typeLabel(data);
                        }
                    },
                    // Amount
                    {
                        data: 'amount',
                        name: 'amount',
                        render: function(v) {
                            return money2(v);
                        }
                    },
                    // Actual Date
                    {
                        data: 'actual_date',
                        name: 'actual_date',
                        render: function(iso, type) {
                            if (!iso) return '';
                            // server sends full ISO with time; cut to date for value, but keep full for sort
                            if (type === 'sort' || type === 'type') return iso;
                            const d = new Date(iso);
                            return new Intl.DateTimeFormat('en-GB', {
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            }).format(d);
                        }
                    },
                    // Status
                    {
                        data: 'kode_status',
                        name: 'kode_status',
                        render: function(k) {
                            return statusBadge(k);
                        }
                    },
                    // Requested by
                    {
                        data: 'requested_by',
                        name: 'requested_by',
                        render: function(data, type, row) {
                            const by = row.requested_by_name || data || '-';
                            const at = row.created_at || '';
                            const ts = at ? new Date(at) : null;
                            const date = ts ?
                                new Intl.DateTimeFormat('en-GB', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }).format(ts) :
                                '';
                            return `<div class="fw-semibold">${by}</div><div class="text-muted small">${date}</div>`;
                        }
                    },
                    // Approved by / at
                    {
                        data: 'approved_by',
                        name: 'approved_by',
                        render: function(data, type, row) {
                            const by = row.approved_by_name || data || '-';
                            const at = row.approved_at || '';
                            const ts = at ? new Date(at) : null;
                            const date = ts ?
                                new Intl.DateTimeFormat('en-GB', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }).format(ts) :
                                '';
                            return `<div class="fw-semibold">${by}</div><div class="text-muted small">${date}</div>`;
                        }
                    },
                    // Attachment
                    {
                        data: 'attachment_path',
                        name: 'attachment_path',
                        render: function(path, type, row) {
                            if (!path) return '<span class="text-muted">-</span>';
                            const url = ATTACH_URL_TPL.replace('__UUID__', encodeURIComponent(row
                                .uuid));
                            return `<a href="${url}" target="_blank" class="btn btn-sm btn-light-primary">Download</a>`;
                        }
                    },
                    // Note
                    {
                        data: 'note',
                        name: 'note',
                        render: function(v) {
                            return v ? $('<div/>').text(v).html() : '<span class="text-muted">-</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: '',
                        render: function(row) {
                            const id = row.uuid;
                            const status = (row.kode_status || '').toUpperCase();
                            let buttons = '';

                            const btnEdit =
                                `<button type="button" class="btn btn-light-primary btn-edit" data-id="${id}">Edit</button>`;
                            const btnApprove =
                                `<button type="button" class="btn btn-light-success btn-approve" data-id="${id}">Approve</button>`;
                            const btnReject =
                                `<button type="button" class="btn btn-light-warning btn-reject" data-id="${id}">Reject</button>`;
                            const btnDelete =
                                `<button type="button" class="btn btn-light-danger btn-delete" data-id="${id}">Delete</button>`;

                            if (status === 'APR') {
                                if (TR_PERMS.edit) {
                                    buttons += btnEdit;
                                }
                                if (TR_PERMS.approve) {
                                    buttons += btnApprove + btnReject;
                                }
                            }

                            if (TR_PERMS.delete) {
                                buttons += btnDelete;
                            }

                            if (!buttons) {
                                return '';
                            }

                            return '<div class="btn-group btn-group-sm">' + buttons + '</div>';
                        }
                    }
                ]
            });


            function fetchAssetSnapshot1(assetUuid) {
                if (!assetUuid) {
                    clearSnapshot1();
                    return;
                }
                $.getJSON('{{ route('assets.brief', ':id') }}'.replace(':id', assetUuid))
                    .done(renderSnapshot1)
                    .fail(() => clearSnapshot1());
            }

            function renderSnapshot1(d) {
                const safe = (v) => v || '';
                $('#snap-owner-1').text(safe(d.owner_label));
                $('#snap-user-1').text(safe(d.user_label));
                $('#snap-maintenance-1').text(safe(d.maintenance_label));
                $('#snap-status-1').text(safe(d.status_label));
                $('#snap-location-1').text(safe(d.location_label));
                $('#tf-asset-snapshot-1').removeClass('d-none');
            }

            function clearSnapshot1() {
                $('#snap-owner-1,#snap-user-1,#snap-maintenance-1,#snap-status-1,#snap-location-1').text('');
                $('#tf-asset-snapshot-1').addClass('d-none');
            }

            function fetchAssetSnapshot2(assetUuid) {
                if (!assetUuid) {
                    clearSnapshot2();
                    return;
                }
                $.getJSON('{{ route('assets.brief', ':id') }}'.replace(':id', assetUuid))
                    .done(renderSnapshot2)
                    .fail(() => clearSnapshot2());
            }

            function renderSnapshot2(d) {
                const safe = (v) => v || '';
                $('#snap-owner-2').text(safe(d.owner_label));
                $('#snap-user-2').text(safe(d.user_label));
                $('#snap-maintenance-2').text(safe(d.maintenance_label));
                $('#snap-status-2').text(safe(d.status_label));
                $('#snap-location-2').text(safe(d.location_label));
                $('#tf-asset-snapshot-2').removeClass('d-none');
            }

            function clearSnapshot2() {
                $('#snap-owner-2,#snap-user-2,#snap-maintenance-2,#snap-status-2,#snap-location-2').text('');
                $('#tf-asset-snapshot-2').addClass('d-none');
            }

            $('#tr-from-asset').on('select2:select select2:clear', () => {
                $('#tr-from-uuid').val($('#tr-from-asset').val() || '');
                if (getTransferType() === 'tf-val') refreshTransferCap();
                else refreshCarryPreview();
            });
            $('#tr-to-asset').on('select2:select select2:clear', function() {
                $('#tr-to-uuid').val($(this).val() || '');
            });
            $('#tr-date').on('change', () => {
                if (getTransferType() === 'tf-val') refreshTransferCap();
                else refreshCarryPreview();
            });
            $('#tr-amount').on('keyup change', () => {
                if (getTransferType() === 'carry_over_gross_accum') refreshCarryPreview();
            });
            $('#btnFilter').on('click', function(e) {
                e.preventDefault();
                tbl.ajax.reload();
            });

            $('#btnReset').on('click', function(e) {
                e.preventDefault();
                $('#f-type').val('');
                $('#f-status').val('');
                $('#f-req-from').val('');
                $('#f-req-to').val('');
                $('#f-apr-from').val('');
                $('#f-apr-to').val('');
                $('#f-asset').val('');
                $('#f-requester').val(null).trigger('change');
                tbl.ajax.reload();
            });

            $('#btnExport').on('click', function(e) {
                e.preventDefault();

                const params = $.param({
                    type: $('#f-type').val() || '',
                    requester: $('#f-requester').val() || '',
                    status: $('#f-status').val() || '',
                    req_from: $('#f-req-from').val() || '',
                    req_to: $('#f-req-to').val() || '',
                    apr_from: $('#f-apr-from').val() || '',
                    apr_to: $('#f-apr-to').val() || '',
                    asset_q: $('#f-asset').val() || '',
                });

                window.location = "{{ route('export.transfer-requests') }}" + '?' + params;
            });

            // ===== Row actions =====

            $('#tbl-transfer-requests').on('click', '.btn-approve', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Approve?',
                        icon: 'question',
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6',
                        showCancelButton: true
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        Swal.fire({
                            title: 'Applying…',
                            didOpen: () => Swal.showLoading()
                        });
                        $.ajax({
                                url: APPROVE_URL_TPL.replace('__UUID__', encodeURIComponent(id)),
                                type: 'POST',
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    _method: 'POST'
                                }
                            }).done(res => {
                                Swal.fire('Approved', '', 'success');
                                tbl.ajax.reload(null, false);
                            })
                            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

            $('#tbl-transfer-requests').on('click', '.btn-reject', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Reject?',
                        icon: 'warning',
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6',
                        showCancelButton: true
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        Swal.fire({
                            title: 'Updating…',
                            didOpen: () => Swal.showLoading()
                        });
                        $.ajax({
                                url: REJECT_URL_TPL.replace('__UUID__', encodeURIComponent(id)),
                                type: 'POST',
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    _method: 'POST'
                                }
                            }).done(res => {
                                Swal.fire('Rejected', '', 'success');
                                tbl.ajax.reload(null, false);
                            })
                            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

            $('#tbl-transfer-requests').on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Delete?',
                        text: 'Moved to trash',
                        icon: 'warning',
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6',
                        showCancelButton: true
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        Swal.fire({
                            title: 'Deleting…',
                            didOpen: () => Swal.showLoading()
                        });
                        $.ajax({
                                url: DELETE_URL_TPL.replace('__UUID__', encodeURIComponent(id)),
                                type: 'POST',
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    _method: 'DELETE'
                                }
                            }).done(res => {
                                Swal.fire('Deleted', '', 'success');
                                tbl.ajax.reload(null, false);
                            })
                            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

            // ===== Edit =====

            $('#tbl-transfer-requests').on('click', '.btn-edit', function() {
                const rowData = tbl.row($(this).closest('tr')).data();
                if (!rowData) return;

                $('#edit-uuid').val(rowData.uuid);

                const fromText = (rowData.from_code || '') +
                    (rowData.from_name ? ' - ' + rowData.from_name : '');
                const toText = (rowData.to_code || '') +
                    (rowData.to_name ? ' - ' + rowData.to_name : '');

                $('#edit-from-asset').val(fromText);
                $('#edit-to-asset').val(toText);

                $('#edit-transfer-type').val(rowData.transfer_type || 'tf-val');
                $('#edit-amount').val(rowData.amount);

                const actualIso = (rowData.actual_date || '').substring(0, 10);
                $('#edit-actual-date').val(actualIso);

                $('#edit-note').val(rowData.note || '');

                const statusText = (rowData.kode_status || '').toUpperCase();
                $('#edit-status-text').val(statusText);

                // Current attachment
                if (rowData.attachment_path) {
                    const attUrl = ATTACH_URL_TPL.replace('__UUID__', encodeURIComponent(rowData.uuid));
                    $('#edit-attachment-current').html(
                        `<a href="${attUrl}" target="_blank">Download current attachment</a>`
                    );
                } else {
                    $('#edit-attachment-current').text('-');
                }

                const editable = (statusText === 'APR');
                $('#edit-transfer-type').prop('disabled', !editable);
                $('#edit-amount').prop('readonly', !editable);
                $('#edit-actual-date').prop('readonly', !editable);
                $('#edit-attachment').prop('disabled', !editable);

                $modalEdit.show();
            });

            $formEdit.on('submit', function(e) {
                e.preventDefault();

                const uuid = $('#edit-uuid').val();
                const amt = Number($('#edit-amount').val() || 0);
                const date = $('#edit-actual-date').val();

                if (!uuid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Missing request ID.'
                    });
                    return;
                }
                if (!(amt > 0)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation error',
                        text: 'Amount must be greater than 0'
                    });
                    return;
                }
                if (!date) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation error',
                        text: 'Actual Date is required'
                    });
                    return;
                }

                const formData = new FormData(this);
                formData.append('_method', 'PUT');

                setBusyUpdate(true);

                $.ajax({
                        url: UPDATE_URL_TPL.replace('__UUID__', encodeURIComponent(uuid)),
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                    }).done(res => {
                        Swal.fire('Updated', '', 'success');
                        tbl.ajax.reload(null, false);
                        $modalEdit.hide();
                    }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'))
                    .always(() => {
                        setBusyUpdate(false);
                    });
            });


            // ===== Transfer helpers =====
            async function refreshTransferCap() {
                const fromUUID = $('#tr-from-uuid').val();
                const date = $('#tr-date').val();
                if (!fromUUID || !date) {
                    $('#tr-cap-help').text('');
                    $('#tr-amount').data('capRemaining', 0);
                    return;
                }

                try {
                    const res = await $.get("{{ route('depreciation.mv.transfer.limit') }}", {
                        from_asset_uuid: fromUUID,
                        actual_date: date
                    });
                    const cap = res || {};
                    const lastP = cap.last_closed_period ? new Date(cap.last_closed_period).toISOString().slice(0,
                        10) : '-';
                    $('#tr-cap-help').text(
                        `Max: ${fmt2(cap.remaining)} (Begin: ${fmt2(cap.begin_total)} − Last NBV ${lastP}: ${fmt2(cap.last_nbv)} − This month OUT: ${fmt2(cap.already_out)})`
                    );
                    $('#tr-amount').data('capRemaining', Number(cap.remaining || 0));
                } catch {
                    $('#tr-cap-help').text('Max not available');
                    $('#tr-amount').data('capRemaining', 0);
                }
            }

            const getTransferType = () => $('input[name="tr-type"]:checked').val();

            function syncTypeUI() {
                const t = getTransferType();
                if (t === 'tf-val') {
                    $('#tr-carry-help').text('');
                    $('#tr-preview').addClass('d-none').empty();
                    refreshTransferCap();
                } else if (t === 'carry_over_gross_accum') {
                    $('#tr-cap-help').text('');
                    refreshCarryPreview();
                } else {
                    $('#tr-cap-help').text('');
                    $('#tr-carry-help').text('');
                    $('#tr-preview').addClass('d-none').empty();
                }
            }
            $('input[name="tr-type"]').on('change', syncTypeUI);

            async function refreshCarryPreview() {
                const fromUUID = $('#tr-from-uuid').val();
                const date = $('#tr-date').val();
                const amount = Number($('#tr-amount').val() || 0);

                $('#tr-carry-help').text('');
                $('#tr-preview').addClass('d-none').empty();
                $('#tr-amount').data('capGross', null);

                if (!fromUUID || !date || !(amount > 0)) return;

                try {
                    const res = await $.get("{{ route('depreciation.mv.carryover.preview') }}", {
                        from_asset_uuid: fromUUID,
                        actual_date: date,
                        amount: amount
                    });
                    const d = res || {};
                    const lc = d.last_closed_period ? new Date(d.last_closed_period).toISOString().slice(0, 10) :
                        '-';

                    $('#tr-preview').removeClass('d-none').html(
                        `Total Gross A: <b>${fmt2(d.total_gross)}</b> · Accum as of <b>${lc}</b>: <b>${fmt2(d.accum_as_of_last)}</b><br>
                 Accum to carry: <b>${fmt2(d.acc_move)}</b> · NBV that moves: <b>${fmt2(d.nbv_move)}</b><br>
                 Gross remaining cap: <b>${fmt2(d.cap_remaining)}</b>`
                    );
                    $('#tr-amount').data('capGross', Number(d.cap_remaining || 0));

                    if (amount > Number(d.cap_remaining || 0)) {
                        $('#tr-carry-help').text(
                            'Amount exceeds max carry-over (gross remaining). Please reduce the amount.');
                    } else {
                        $('#tr-carry-help').text('');
                    }
                } catch (e) {
                    console.error(e);
                }
            }


            const $btnSave = $('#btn-submit-transfer');
            const setBusyTransfer = (b) => {
                if (b) $btnSave.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnSave.removeAttr('data-kt-indicator').prop('disabled', false);
            };

            $('#form-transfer').on('submit', async function(e) {
                e.preventDefault();

                const fromUUID = $('#tr-from-uuid').val();
                const toUUID = $('#tr-to-uuid').val();
                const amount = Number($('#tr-amount').val() || 0);
                const date = $('#tr-date').val();
                const type = getTransferType();

                if (!fromUUID || !toUUID) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation error',
                        text: 'Please pick both assets'
                    });
                    return;
                }
                if (fromUUID === toUUID) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation error',
                        text: 'From/To asset cannot be the same'
                    });
                    return;
                }
                if (!(amount > 0)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation error',
                        text: 'Amount must be greater than 0'
                    });
                    return;
                }
                if (!date) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation error',
                        text: 'Actual Date is required'
                    });
                    return;
                }

                if (type === 'tf-val') {
                    const capRem = Number($('#tr-amount').data('capRemaining') || 0);
                    if (capRem && amount > capRem) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validation error',
                            text: 'Amount exceeds max allowed for that month.'
                        });
                        return;
                    }
                } else if (type === 'carry_over_gross_accum') {
                    const capGross = Number($('#tr-amount').data('capGross') || 0);
                    if (capGross && amount > capGross) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Validation error',
                            text: 'Amount exceeds max carry-over (gross remaining).'
                        });
                        return;
                    }
                }

                const fd = new FormData();
                fd.append('transfer_type', type);
                fd.append('from_asset_uuid', fromUUID);
                fd.append('to_asset_uuid', toUUID);
                fd.append('amount', amount);
                fd.append('actual_date', date);
                fd.append('note', $('#tr-note').val() || '');
                const fileInput = document.getElementById('tr-attachment');
                if (fileInput && fileInput.files && fileInput.files[0]) {
                    fd.append('attachment', fileInput.files[0]);
                }

                try {
                    setBusyTransfer(true);
                    await $.ajax({
                        url: "{{ route('depreciation.transfer-requests.store') }}",
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        }
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: 'Transfer request saved. Waiting for approval.'
                    });
                    $('#tbl-monthly').DataTable().ajax.reload(null, false);
                    $modal.hide();
                    tbl.ajax.reload(null, false);
                } catch (err) {
                    console.error(err);
                    const msg = err?.responseJSON?.message || 'Failed to save transfer request';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg
                    });
                } finally {
                    setBusyTransfer(false);
                }
            });

        })();
    </script>
@endpush
