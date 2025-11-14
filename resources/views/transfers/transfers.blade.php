@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Movement</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        Transaction
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Movement</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            {{-- FILTERS --}}
            <div class="card mb-6">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select id="mv-type" class="form-select">
                                <option value="">All</option>
                                <option value="owner">Owner</option>
                                <option value="user">User</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="status">Status</option>
                                <option value="location">Location</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status Approval</label>
                            <select id="mv-status" class="form-select">
                                <option value="">All</option>
                                @foreach ($workflows as $w)
                                    <option value="{{ $w->kode }}">{{ $w->kode }} - {{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Requester</label>
                            <select id="mv-requester" class="form-select"></select>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mt-1">
                        <div class="col-md-3">
                            <label class="form-label">Updated From</label>
                            <input type="date" id="mv-updated-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Updated To</label>
                            <input type="date" id="mv-updated-to" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Created From</label>
                            <input type="date" id="mv-created-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Created To</label>
                            <input type="date" id="mv-created-to" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Asset (code / description)</label>
                            <input type="text" id="mv-asset-q" class="form-control"
                                placeholder="e.g. A1101000002-00 / Laptop Dell">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="mv-btn-apply" class="btn btn-danger btn-sm me-2">
                        Apply Filter
                    </button>
                    <button id="mv-btn-reset" class="btn btn-light-danger btn-sm me-2">
                        Reset
                    </button>
                    <button id="mv-btn-export" class="btn btn-light-danger btn-sm">
                        Export Excel
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Movement Data</span>
                        </h3>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex align-items-center gap-3">
                            <a href="#" id="btnOpenCreate" class="btn btn-sm btn-danger">
                                <i class="ki-duotone ki-plus fs-2"></i>Add New
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                        id="tbl-transfers-all">
                        <thead>
                            <tr class="table-light">
                                <th class="min-w-200px">Transaction Number</th>
                                <th class="min-w-200px">Asset</th>
                                <th class="min-w-200px">Type</th>
                                <th class="min-w-200px">Before</th>
                                <th class="min-w-200px">After</th>
                                <th class="min-w-200px">Note</th>
                                <th class="min-w-200px">Requester</th>
                                <th class="min-w-200px">Approver</th>
                                <th class="min-w-200px">Status</th>
                                <th class="min-w-200px">Flow</th>
                                <th class="min-w-200px">File</th>
                                <th class="min-w-200px">Updated</th>
                                <th class="min-w-200px">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Create / Edit Transfer Modal --}}
    <div class="modal fade" id="modal-transfer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formTransfer" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="asset_uuid" id="tf-asset-uuid">
                    <input type="hidden" id="tf-edit-id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title">New Movement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-12">
                                <label class="form-label required">Asset</label>
                                <select id="tf-asset" class="form-select" required></select>
                                <div class="form-text">Search by code or description</div>
                            </div>
                            <div id="tf-asset-snapshot" class="mt-4 d-none">
                                <div class="p-3 border rounded bg-light">
                                    <div class="fw-semibold mb-2">Current Asset Info</div>

                                    <div class="row gy-2">
                                        <div class="col-6">
                                            <div class="text-muted">Owner</div>
                                            <div id="snap-owner" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">User</div>
                                            <div id="snap-user" class="fw-semibold"></div>
                                        </div>

                                        <div class="col-6">
                                            <div class="text-muted">Maintenance</div>
                                            <div id="snap-maintenance" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Status</div>
                                            <div id="snap-status" class="fw-semibold"></div>
                                        </div>

                                        <div class="col-12">
                                            <div class="text-muted">Location</div>
                                            <div id="snap-location" class="fw-semibold"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Type</label>
                                <select name="type" id="tf-type" class="form-select" required>
                                    <option value="owner">Owner</option>
                                    <option value="user">User</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="status">Status</option>
                                    <option value="location">Location</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Move To</label>
                                <select id="tf-target" class="form-select" required></select>
                                <input type="hidden" name="after[value]" id="tf-target-hidden">
                                <div class="form-text" id="tf-target-help">Pick the new value.</div>
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
                        <button type="submit" class="btn btn-danger">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Approve flow modal --}}
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
@endsection

@push('scripts')
    <script>
        (function() {
            const R = {
                data: '{{ route('transfer.data.all') }}',
                show: '{{ route('transfer.show', ':id') }}',
                update: '{{ route('transfer.update', ':id') }}',
                create: '{{ route('transfer.store') }}',
                assets: '{{ route('assets.options') }}',
                usercode: '{{ route('master.user_code.options') }}',
                status: '{{ route('master.status.options') }}',
                location: '{{ route('master.location.options') }}',
                assetBrief: id => '{{ route('assets.brief', ':id') }}'.replace(':id', id),
                approve: id => '{{ route('transfer.approve', ':id') }}'.replace(':id', id),
                reject: id => '{{ route('transfer.reject', ':id') }}'.replace(':id', id),
                destroy: id => '{{ route('transfer.destroy', ':id') }}'.replace(':id', id),
                export: '{{ route('export.movement') }}',
                usersOptions: '{{ route('users.options') }}',
                approveLocationStep: '{{ route('transfer.approve-location-step', ':id') }}',
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function preloadSelectValue($select, id, text) {
                if (!id) return;
                const opt = new Option(text ?? String(id), id, true, true);
                $select.append(opt).trigger('change');
            }

            function renderSnapshot(d) {
                const safe = (v) => v || '';
                $('#snap-owner').text(safe(d.owner_label));
                $('#snap-user').text(safe(d.user_label));
                $('#snap-maintenance').text(safe(d.maintenance_label));
                $('#snap-status').text(safe(d.status_label));
                $('#snap-location').text(safe(d.location_label));
                $('#tf-asset-snapshot').removeClass('d-none');
            }

            function clearSnapshot() {
                $('#snap-owner,#snap-user,#snap-maintenance,#snap-status,#snap-location').text('');
                $('#tf-asset-snapshot').addClass('d-none');
            }

            function fetchAssetSnapshot(assetUuid) {
                if (!assetUuid) {
                    clearSnapshot();
                    return;
                }
                $.getJSON(R.assetBrief(assetUuid))
                    .done(renderSnapshot)
                    .fail(() => clearSnapshot());
            }

            function resetTransferFormToCreate() {
                const $form = $('#formTransfer');

                $form.removeData('edit-id');
                $('#tf-asset').val(null).empty();

                $form[0].reset();
                $('textarea[name="note"]').val('');

                const fileInput = document.getElementById('tf-file');
                if (fileInput) fileInput.value = '';
                $('#tf-current-file').addClass('d-none');
                $('#tf-current-file-link').attr('href', '').text('');
                $('#tf-remove-file').val('0');

                $('#tf-type').val('owner');

                if ($('#tf-target').data('select2')) {
                    $('#tf-target').off('select2:select').select2('destroy');
                }
                $('#tf-target').val(null).empty();
                $('#tf-target-hidden').val('');

                initTargetSelect('owner');

                clearSnapshot();
            }

            function endpointForType(type) {
                switch (type) {
                    case 'owner':
                    case 'user':
                    case 'maintenance':
                        return R.usercode;
                    case 'status':
                        return R.status;
                    case 'location':
                        return R.location;
                    default:
                        return null;
                }
            }

            function initSelect2(el, url, extra = {}) {
                $(el).select2({
                    dropdownParent: $('#modal-transfer'),
                    placeholder: 'Select...',
                    width: '100%',
                    allowClear: true,
                    ajax: {
                        url,
                        dataType: 'json',
                        delay: 150,
                        data: params => Object.assign({
                            q: params.term || '',
                            page: params.page || 1
                        }, (typeof extra === 'function') ? extra() : (extra || {})),
                        processResults: d => d
                    }
                }).on('select2:select', function(e) {
                    if (this.id === 'tf-target') {
                        $('#tf-target-hidden').val(e.params.data.id);
                    }
                    if (this.id === 'tf-asset') {
                        const assetUuid = e.params.data.id;
                        $('#tf-asset-uuid').val(assetUuid);
                        fetchAssetSnapshot(assetUuid);
                    }
                }).on('select2:clear', function() {
                    if (this.id === 'tf-asset') {
                        $('#tf-asset-uuid').val('');
                        clearSnapshot();
                    }
                });
            }

            function initTargetSelect(type) {
                const $t = $('#tf-target');
                $t.off('select2:select').empty().trigger('change');
                $('#tf-target-hidden').val('');

                if (type === 'owner' || type === 'user' || type === 'maintenance') {
                    initSelect2('#tf-target', R.usercode);
                    $('#tf-target-help').text('Pick a User Code (department).');
                } else if (type === 'status') {
                    initSelect2('#tf-target', R.status, {
                        type: 'Asset'
                    });
                    $('#tf-target-help').text('Pick the new Asset Status.');
                } else if (type === 'location') {
                    initSelect2('#tf-target', R.location);
                    $('#tf-target-help').text('Pick the new Location.');
                }
            }

            $('#mv-requester').select2({
                placeholder: 'All',
                width: '100%',
                allowClear: true,
                ajax: {
                    url: R.usersOptions,
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1
                    }),
                    processResults: d => d
                }
            });

            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
            const urlParams = new URLSearchParams(window.location.search);
            const statusFromUrl = urlParams.get('status');
            if (statusFromUrl) {
                $('#mv-status').val(statusFromUrl);
            }

            const dt = $('#tbl-transfers-all').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: R.data,
                    data: d => {
                        d.type = $('#mv-type').val() || '';
                        d.status = $('#mv-status').val() || '';
                        d.requester = $('#mv-requester').val() || '';
                        d.updated_from = $('#mv-updated-from').val() || '';
                        d.updated_to = $('#mv-updated-to').val() || '';
                        d.created_from = $('#mv-created-from').val() || '';
                        d.created_to = $('#mv-created-to').val() || '';
                        d.asset_q = $('#mv-asset-q').val() || '';
                    }
                },
                order: [
                    [11, 'desc']
                ], // updated_at column index
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l><'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i><'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                searching: true,
                scrollX: true,
                columns: [{
                        data: 'transfer_code',
                        name: 'assets_transfers.transfer_code'
                    },
                    {
                        data: 'asset_label',
                        name: 'asset_label',
                        orderable: false,
                        render: function(data, type, row) {
                            if (type !== 'display') return data;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row
                                .asset_uuid));
                            const text = row.asset_code ?? data ?? '';
                            return `<a href="${url}" class="text-primary fw-semibold">${text}</a>`;
                        }
                    },
                    {
                        data: 'type',
                        name: 'assets_transfers.type',
                        render: d => (d || '').toUpperCase()
                    },
                    {
                        data: 'before_show',
                        name: 'before_show',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'after_show',
                        name: 'after_show',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'note',
                        name: 'assets_transfers.note'
                    },
                    {
                        data: 'pic_request_uid',
                        name: 'assets_transfers.pic_request_uid'
                    },
                    {
                        data: 'pic_approve_uid',
                        name: 'assets_transfers.pic_approve_uid',
                        defaultContent: ''
                    },
                    {
                        data: 'workflow_label',
                        name: 'workflow_label',
                        defaultContent: '',
                        render: function(data, type, row) {
                            if (row.kode_status == 'APR') {
                                return `<span class="badge badge-light-primary">${data || ''}</span>`;
                            } else if (row.kode_status == 'ACC') {
                                return `<span class="badge badge-light-success">${data || ''}</span>`;
                            } else {
                                return `<span class="badge badge-light-danger">${data || ''}</span>`;
                            }
                        }
                    },
                    {
                        data: 'flow_label',
                        name: 'flow_label',
                        orderable: false,
                        searchable: false,
                        render: function(data, type) {
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
                        name: 'assets_transfers.updated_at',
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

            // filter buttons
            $('#mv-btn-apply').on('click', function() {
                dt.ajax.reload();
            });

            $('#mv-btn-reset').on('click', function() {
                $('#mv-type').val('');
                $('#mv-status').val('');
                $('#mv-requester').val(null).trigger('change');
                $('#mv-updated-from').val('');
                $('#mv-updated-to').val('');
                $('#mv-created-from').val('');
                $('#mv-created-to').val('');
                $('#mv-asset-q').val('');
                dt.ajax.reload();
            });

            // export button
            $('#mv-btn-export').on('click', function(e) {
                e.preventDefault();
                const params = $.param({
                    type: $('#mv-type').val() || '',
                    status: $('#mv-status').val() || '',
                    requester: $('#mv-requester').val() || '',
                    updated_from: $('#mv-updated-from').val() || '',
                    updated_to: $('#mv-updated-to').val() || '',
                    created_from: $('#mv-created-from').val() || '',
                    created_to: $('#mv-created-to').val() || '',
                    asset_q: $('#mv-asset-q').val() || '',
                });
                window.location = R.export+'?' + params;
            });

            $('#btnOpenCreate').on('click', function(e) {
                e.preventDefault();
                resetTransferFormToCreate();
                $('#tf-asset-uuid').val('');
                $('#tf-target-hidden').val('');
                $('#tf-type').val('owner');
                $('#tf-edit-id').val('');
                $('.modal-title', '#modal-transfer').text('New Transfer');

                initSelect2('#tf-asset', R.assets);
                initTargetSelect('owner');

                $('#tf-asset').prop('disabled', false);

                $('#modal-transfer').modal('show');
            });

            $('#tf-type').on('change', function() {
                initTargetSelect(this.value);
            });

            $('#formTransfer').off('submit').on('submit', function(e) {
                e.preventDefault();

                const isEdit = !!$('#tf-edit-id').val();
                const id = $('#tf-edit-id').val();
                const fileEl = document.getElementById('tf-file');
                const hasFile = fileEl && fileEl.files && fileEl.files.length > 0;

                const base = {
                    type: $('#tf-type').val(),
                    'after[value]': $('#tf-target-hidden').val(),
                    note: $('textarea[name="note"]').val() || '',
                    asset_uuid: $('#tf-asset-uuid').val() || null,
                    remove_file: $('#tf-remove-file').val() || 0
                };

                if (!base['after[value]']) {
                    Swal.fire('Target required', 'Please select a transfer target.', 'warning');
                    return;
                }
                if (!base.asset_uuid) {
                    Swal.fire('Asset required', 'Please choose an asset.', 'warning');
                    return;
                }

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
                        url: isEdit ? R.update.replace(':id', id) : R.create,
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false
                    });

                } else {
                    const payload = base;

                    req = isEdit ?
                        $.ajax({
                            url: R.update.replace(':id', id),
                            type: 'PUT',
                            data: payload
                        }) :
                        $.post(R.create, payload);
                }

                req
                    .done(() => {
                        $('#modal-transfer').modal('hide');
                        Swal.fire(isEdit ? 'Updated' : 'Success', isEdit ? 'Transfer updated.' :
                            'Movement created.', 'success');
                        $('#tf-asset').prop('disabled', false);
                        $('#tbl-transfers-all').DataTable().ajax.reload(null, false);

                        if (fileEl) fileEl.value = '';
                        $('#tf-remove-file').val('0');
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });

            $('#tbl-transfers-all').on('click', '.btn-tf-edit', function() {
                const id = $(this).data('id');
                $('#modal-transfer').modal('show');
                $('.modal-title', '#modal-transfer').text('Edit Transfer');

                initSelect2('#tf-asset', R.assets);
                $('#tf-target').off().empty().trigger('change');
                $('#tf-target-hidden').val('');


                $.getJSON(R.show.replace(':id', id), function(d) {
                    $('#tf-edit-id').val(d.uuid || id);
                    $('#tf-asset-uuid').val(d.asset_uuid || '');
                    fetchAssetSnapshot(d.asset_uuid || '');

                    const $asset = $('#tf-asset');
                    const assetText = d.asset_label ??
                        (d.asset_code ? (d.asset_desc ? `${d.asset_code} - ${d.asset_desc}` : d
                                .asset_code) :
                            d.asset_uuid);

                    $asset.empty();
                    const opt = new Option(assetText ?? '', d.asset_uuid ?? '', true, true);
                    $asset.append(opt).trigger('change.select2');
                    $asset.prop('disabled', true);

                    const type = d?.type || 'owner';
                    $('#tf-type').val(type).trigger('change');
                    initTargetSelect(type);

                    if (d?.file_url) {
                        $('#tf-current-file').removeClass('d-none');
                        $('#tf-current-file-link').attr('href', d?.file_url).text(d?.file_name ||
                            'Current file');
                        $('#tf-remove-file').val('0');
                    } else {
                        $('#tf-current-file').addClass('d-none');
                        $('#tf-remove-file').val('0');
                    }

                    const afterVal = d?.after?.value ?? '';
                    if (afterVal) {
                        const url = endpointForType(type);
                        if (url) {
                            $.get(url, {
                                q: afterVal
                            }, function(resp) {
                                const found = (resp.results || []).find(x => String(x.id) ===
                                    String(afterVal));
                                const label = found ? found.text : afterVal;
                                const tOpt = new Option(label, afterVal, true, true);
                                $('#tf-target').append(tOpt).trigger('change.select2');
                                $('#tf-target-hidden').val(afterVal);
                            }).fail(() => {
                                const tOpt = new Option(afterVal, afterVal, true, true);
                                $('#tf-target').append(tOpt).trigger('change.select2');
                                $('#tf-target-hidden').val(afterVal);
                            });
                        } else {
                            const tOpt = new Option(afterVal, afterVal, true, true);
                            $('#tf-target').append(tOpt).trigger('change.select2');
                            $('#tf-target-hidden').val(afterVal);
                        }
                    } else {
                        $('#tf-target').val(null).trigger('change');
                        $('#tf-target-hidden').val('');
                    }

                    $('textarea[name="note"]').val(d.note || '');
                }).fail(() => {
                    Swal.fire('Error', 'Cannot load transfer', 'error');
                    $('#tf-edit-id').val('');
                    $('#modal-transfer').modal('hide');
                });
            });

            $('#tbl-transfers-all').on('click', '.btn-tf-approve', function() {
                const id = $(this).data('id');
                const row = $('#tbl-transfers-all').DataTable().row($(this).closest('tr')).data();

                if ((row.type || '').toLowerCase() === 'location') {
                    $('#tf-approve-id').val(id);

                    $.get(R.show.replace(':id', id))
                        .done(d => {
                            renderApproveFlowModal(d);
                            $('#modal-transfer-approve').modal('show');
                        })
                        .fail(() => {
                            Swal.fire('Error', 'Cannot load transfer', 'error');
                        });
                } else {
                    Swal.fire({
                        title: 'Approve?',
                        icon: 'question',
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6',
                        showCancelButton: true
                    }).then(r => {
                        if (!r.isConfirmed) return;
                        Swal.fire({
                            title: 'Applying…',
                            didOpen: () => Swal.showLoading()
                        });
                        $.post(R.approve(id)).done(() => {
                            Swal.fire('Approved', '', 'success');
                            $('#tbl-transfers-all').DataTable().ajax.reload(null, false);
                        }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed',
                            'error'));
                    });
                }
            });

            $('#tbl-transfers-all').on('click', '.btn-tf-reject', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Reject?',
                    icon: 'warning',
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    showCancelButton: true
                }).then(r => {
                    if (!r.isConfirmed) return;
                    Swal.fire({
                        title: 'Updating…',
                        didOpen: () => Swal.showLoading()
                    });
                    $.post(R.reject(id)).done(() => {
                        Swal.fire('Rejected', '', 'success');
                        dt.ajax.reload(null, false);
                    }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                });
            });

            $('#tbl-transfers-all').on('click', '.btn-tf-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete?',
                    text: 'Moved to trash',
                    icon: 'warning',
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    showCancelButton: true
                }).then(r => {
                    if (!r.isConfirmed) return;
                    Swal.fire({
                        title: 'Deleting…',
                        didOpen: () => Swal.showLoading()
                    });
                    $.ajax({
                        url: R.destroy(id),
                        type: 'DELETE'
                    }).done(() => {
                        Swal.fire('Deleted', '', 'success');
                        dt.ajax.reload(null, false);
                    }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
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
                        `<button type="button" class="btn btn-sm btn-primary mt-2 btn-flow-approve-step" data-step-index="${idx}">
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

            $('#tf-flow-steps').on('click', '.btn-flow-approve-step', function() {
                const id = $('#tf-approve-id').val();
                if (!id) return;

                const $btn = $(this).prop('disabled', true);

                $.post(R.approveLocationStep.replace(':id', id))
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

                        $('#tbl-transfers-all').DataTable().ajax.reload(null, false);
                    })
                    .fail(x => {
                        $btn.prop('disabled', false);
                        Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                    });
            });

        })();
    </script>
@endpush
