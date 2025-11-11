@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Return</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        Transaction
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Return</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card mb-6">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Source</label>
                            <select id="f-source" class="form-select">
                                <option value="">All</option>
                                <option value="transfer">Movement</option>
                                <option value="disposal">Disposal</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Movement Type</label>
                            <select id="f-tf-type" class="form-select">
                                <option value="">All</option>
                                <option value="owner">Owner</option>
                                <option value="user">User</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="status">Status</option>
                                <option value="location">Location</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Created From</label>
                            <input type="date" id="f-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Created To</label>
                            <input type="date" id="f-to" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Requester</label>
                            <select id="f-users" class="form-select">
                            </select>
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
                    <button id="btnReset" class="btn btn-light-danger btn-sm">Reset</button>
                    <button id="btnExport" class="btn btn-light-danger btn-sm">Export Excel</button>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Return History</span>
                    </h3>
                    @canAction('RETURN','C')
                    <div class="card-toolbar">
                        <a href="#" id="btnOpenCreate" class="btn btn-sm btn-danger">
                            <i class="ki-duotone ki-plus fs-2"></i>Add New
                        </a>
                    </div>
                    @endcanAction
                </div>
                <div class="card-body">
                    <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                        id="tbl-returns-all">
                        <thead>
                            <tr class="table-light">
                                <th class="min-w-150px">Transaction Number</th>
                                <th class="min-w-200px">MOV/DSP Tr. No.</th>
                                <th class="min-w-200px">Asset</th>
                                <th class="min-w-200px">Type</th>
                                <th class="min-w-300px">Details</th>
                                <th class="min-w-300px">Note</th>
                                <th class="min-w-200px">Requester</th>
                                <th class="min-w-200px">Created</th>
                                <th class="min-w-150px">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Create Return Modal --}}
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
                                <label class="form-label required">Select Movement/Disposal</label>
                                <select id="ret-source" class="form-select" required></select>
                                <div class="form-text">Shows latest accepted only (movement-by-type per asset, disposal per
                                    asset)</div>
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
@endsection

@push('scripts')
    <script>
        (function() {
            const R = {
                options: '{{ route('return.options') }}',
                store: '{{ route('return.store') }}',
                data: '{{ route('return.data') }}',
                destroy: id => '{{ route('return.destroy', ':id') }}'.replace(':id', id),
                showAsset: uuid => '{{ route('assets.detail', ':uuid') }}'.replace(':uuid', encodeURIComponent(
                    uuid)),
                export: '{{ route('export.return') }}',
            };
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $('#f-users').select2({
                placeholder: 'Select requester',
                width: '100%',
                allowClear: true,
                ajax: {
                    url: '{{ route('users.options') }}',
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1
                    }),
                    processResults: d => d
                }
            });

            const dt = $('#tbl-returns-all').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: R.data,
                    type: 'GET',
                    data: function(d) {
                        d.source = $('#f-source').val() || '';
                        d.tf_type = $('#f-tf-type').val() || '';
                        d.date_from = $('#f-from').val() || '';
                        d.date_to = $('#f-to').val() || '';
                        d.users = $('#f-users').val() || '';
                        d.asset = $('#f-asset').val() || '';
                    }
                },
                order: [
                    [7, 'desc']
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
                        data: null,
                        name: 'a.asset_code',
                        orderable: false,
                        render: function(row, type, full) {
                            if (type !== 'display') return (row.asset_label || '');
                            const url = R.showAsset(row.asset_uuid);
                            const label = row.asset_code ? (row.description ? (row.asset_code + ' - ' +
                                row.description) : row.asset_code) : row.asset_uuid;
                            return `<a href="${url}" class="text-primary fw-semibold">${label}</a>`;
                        }
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
                    },
                ]
            });
            $('#btnFilter').on('click', function(e) {
                e.preventDefault();
                dt.ajax.reload();
            });

            $('#btnReset').on('click', function(e) {
                e.preventDefault();
                $('#f-source').val('');
                $('#f-tf-type').val('');
                $('#f-from').val('');
                $('#f-to').val('');
                $('#f-asset').val('');
                $('#f-users').val(null).trigger('change');
                dt.ajax.reload();
            });

            $('#btnExport').on('click', function(e) {
                e.preventDefault();

                const params = $.param({
                    source: $('#f-source').val() || '',
                    tf_type: $('#f-tf-type').val() || '',
                    date_from: $('#f-from').val() || '',
                    date_to: $('#f-to').val() || '',
                    users: $('#f-users').val() || '',
                    asset: $('#f-asset').val() || '',
                });

                window.location = R.export+'?' + params;
            });
            $('#btnOpenCreate').on('click', function(e) {
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

            function initReturnSelect() {
                $('#ret-source').select2({
                    dropdownParent: $('#modal-return'),
                    placeholder: 'Search code / asset…',
                    width: '100%',
                    allowClear: true,
                    ajax: {
                        url: R.options,
                        dataType: 'json',
                        delay: 150,
                        data: params => ({
                            q: params.term || ''
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

            $('#formReturn').on('submit', function(e) {
                e.preventDefault();
                const id = $('#ret-source').val();
                if (!id) return Swal.fire('Required', 'Please select an item.', 'warning');

                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });
                $.post(R.store, {
                        source: id,
                        note: $('textarea[name="note"]').val() || ''
                    })
                    .done(() => {
                        $('#modal-return').modal('hide');
                        Swal.fire('Success', 'Return recorded.', 'success');
                        dt.ajax.reload(null, false);
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed to save', 'error'));
            });

            $('#tbl-returns-all').on('click', '.btn-ret-delete', function() {
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
                            url: R.destroy(id),
                            type: 'DELETE'
                        })
                        .done(() => {
                            Swal.fire('Deleted', '', 'success');
                            dt.ajax.reload(null, false);
                        })
                        .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                });
            });
        })();
    </script>
@endpush
