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
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('transaction.depreciation.index') }}" class="text-muted text-hover-primary">
                            Depreciation
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Transfer Requests</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container-xxl" id="kt_content_container">
        <div class="card">
            <div class="card-header align-items-center justify-content-between">
                <div class="d-flex flex-column">
                    <h3 class="card-title mb-1">
                        <span class="fw-semibold">Transfer Requests</span>
                    </h3>
                    <span class="text-muted fs-7">
                        Requests created from Depreciation
                    </span>
                </div>
            </div>

            <div class="card-body">
                <table id="tbl-transfer-requests"
                    class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="min-w-150px">Transfer Code</th>
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
                            <th class="min-w-200px text-center">Actions</th>
                        </tr>
                    </thead>
                </table>
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
                                <input type="number" step="0.01" min="0.01" class="form-control" id="edit-amount"
                                    name="amount">
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
                    data: d => {}
                },
                order: [
                    [4, 'desc']
                ], // actual_date
                columns: [
                    {
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
                    // Actions
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
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
                                buttons = btnEdit + btnApprove + btnReject + btnDelete;
                            } else {
                                buttons = btnDelete;
                            }

                            return '<div class="btn-group btn-group-sm">' + buttons + '</div>';
                        }
                    }
                ]
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
                    toastr?.error('Missing request ID.');
                    return;
                }
                if (!(amt > 0)) {
                    toastr?.error('Amount must be greater than 0');
                    return;
                }
                if (!date) {
                    toastr?.error('Actual Date is required');
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

        })();
    </script>
@endpush
