@extends('layouts.app')

@section('content')
    <div class="app-toolbar py-3 py-lg-6 mb-10">
        <div class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Stock Opname</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        Transaction
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Stock Opname</li>
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
                    <button id="btnReset" class="btn btn-light-danger btn-sm me-2">Reset</button>
                    <button id="btnExport" class="btn btn-light-danger btn-sm">Export Excel</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Stock Opname Data</span>
                        </h3>
                    </div>
                    @canAction('STOCK_OPN','C')
                    <div class="card-toolbar">
                        <div class="d-flex align-items-center gap-3">
                            <a href="#" id="btnOpenTf" class="btn btn-sm btn-danger">
                                <i class="ki-duotone ki-plus fs-2"></i>Movement
                            </a>
                            <a href="#" id="btnOpenDis" class="btn btn-sm btn-danger">
                                <i class="ki-duotone ki-plus fs-2"></i>Disposal
                            </a>
                        </div>
                    </div>
                    @endcanAction
                </div>
                <div class="card-body">
                    <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                        id="tbl-so-global">
                        <thead>
                            <tr class="table-light">
                                <th class="min-w-200px">Asset</th>
                                <th class="min-w-200px">Transaction Number</th>
                                <th class="min-w-200px">Source</th>
                                <th class="min-w-200px">Type</th>
                                <th class="min-w-400px">Detail</th>
                                <th class="min-w-300px">Note</th>
                                <th class="min-w-200px">Requester</th>
                                <th class="min-w-200px">Approver</th>
                                <th class="min-w-200px">File</th>
                                <th class="min-w-200px">Updated</th>
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


    {{-- Create / Edit Disposal Modal --}}
    <div class="modal fade" id="modal-disposal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formDisposal" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="asset_uuid" id="ds-asset-uuid">
                    <input type="hidden" id="ds-edit-id" value="">
                    <input type="hidden" name="remove_file" id="ds-remove-file" value="0">

                    <div class="modal-header">
                        <h5 class="modal-title">New Disposal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-12">
                                <label class="form-label required">Asset</label>
                                <select id="ds-asset" class="form-select" required></select>
                                <div class="form-text">Search by code or description</div>
                            </div>

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
                        <button type="submit" class="btn btn-danger">Submit</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            $("#f-users").select2({
                placeholder: 'Select requester',
                width: '100%',
                allowClear: true,
                ajax: {
                    url: '{{ route('users.options') }}',
                    dataType: 'json',
                    delay: 150,
                    data: params => Object.assign({
                        q: params.term || '',
                        page: params.page || 1
                    }),

                    processResults: function(data) {
                        return data;
                    }
                }
            });

            const SO = {
                data: '{{ route('stockopname.data.all') }}',
                export: '{{ route('export.stockopname') }}',
            };
            $('#btnExport').on('click', function(e) {
                e.preventDefault();

                const params = $.param({
                    source: $('#f-source').val() || '',
                    tf_type: $('#f-tf-type').val() || '',
                    date_from: $('#f-from').val() || '',
                    date_to: $('#f-to').val() || '',
                    asset: $('#f-asset').val() || '',
                    users: $('#f-users').val() || ''
                });

                window.location = SO.export+'?' + params;
            });

            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
            const dt = $('#tbl-so-global').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: SO.data,
                    type: 'GET',
                    data: function(d) {
                        d.source = $('#f-source').val();
                        d.tf_type = $('#f-tf-type').val();
                        d.date_from = $('#f-from').val();
                        d.date_to = $('#f-to').val();
                        d.asset = $('#f-asset').val();
                        d.users = $('#f-users').val();
                    }
                },
                order: [
                    [9, 'desc']
                ],
                "dom": "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                columns: [{
                        data: 'asset_code',
                        name: 'asset_code',
                        render: function(data, type, row) {
                            if (type !== 'display') return data;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row
                                .asset_uuid));
                            const text = row.asset_code ?? data ?? '';
                            return `<a href="${url}" class="text-primary fw-semibold">${text}</a>`;
                        }
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'source',
                        name: 'source'
                    },
                    {
                        data: 'type',
                        name: 'type'
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
                        data: 'pic_approve_uid',
                        name: 'pic_approve_uid'
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

            $('#btnFilter').on('click', () => dt.ajax.reload());
            $('#btnReset').on('click', () => {
                $('#f-source').val('');
                $('#f-tf-type').val('');
                $('#f-from').val('');
                $('#f-to').val('');
                $('#f-asset').val('');
                $('#f-users').val(null).trigger('change');
                dt.ajax.reload();
            });
        })();
    </script>

    <script>
        (function() {
            const R_TF = {
                create: '{{ route('stockopname.transfer.store') }}',
                assets: '{{ route('assets.options') }}',
                usercode: '{{ route('master.user_code.options') }}',
                status: '{{ route('master.status.options') }}',
                location: '{{ route('master.location.options') }}',
                assetBrief: id => '{{ route('assets.brief', ':id') }}'.replace(':id', id),
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
                $.getJSON(R_TF.assetBrief(assetUuid))
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
                        return R_TF.usercode;
                    case 'status':
                        return R_TF.status;
                    case 'location':
                        return R_TF.location;
                    default:
                        return null;
                }
            }

            function initSelect2Tf(el, url, extra = {}) {
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
                    initSelect2Tf('#tf-target', R_TF.usercode);
                    $('#tf-target-help').text('Pick a User Code (department).');
                } else if (type === 'status') {
                    initSelect2Tf('#tf-target', R_TF.status, {
                        type: 'Asset'
                    });
                    $('#tf-target-help').text('Pick the new Asset Status.');
                } else if (type === 'location') {
                    initSelect2Tf('#tf-target', R_TF.location);
                    $('#tf-target-help').text('Pick the new Location.');
                }
            }

            $('#btnOpenTf').on('click', function(e) {
                e.preventDefault();
                resetTransferFormToCreate();
                $('#tf-asset-uuid').val('');
                $('#tf-target-hidden').val('');
                $('#tf-type').val('owner');
                $('#tf-edit-id').val('');
                $('.modal-title', '#modal-transfer').text('New Transfer');

                initSelect2Tf('#tf-asset', R_TF.assets);
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
                    Swal.fire('Target required', 'Please select a movement target.', 'warning');
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
                        url: isEdit ? R_TF.update.replace(':id', id) : R_TF.create,
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false
                    });

                } else {
                    const payload = base;

                    req = isEdit ?
                        $.ajax({
                            url: R_TF.update.replace(':id', id),
                            type: 'PUT',
                            data: payload
                        }) :
                        $.post(R_TF.create, payload);
                }

                req
                    .done(() => {
                        $('#modal-transfer').modal('hide');
                        Swal.fire(isEdit ? 'Updated' : 'Success', isEdit ? 'Movement updated.' :
                            'Movement created.', 'success');
                        $('#tf-asset').prop('disabled', false);
                        $('#tbl-so-global').DataTable().ajax.reload(null, false);

                        if (fileEl) fileEl.value = '';
                        $('#tf-remove-file').val('0');
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });
        })();
    </script>
    <script>
        (function() {
            const DIS = {
                create: '{{ route('stockopname.disposal.store') }}',
                // create: '{{ route('disposal.store') }}', 
                assets: '{{ route('assets.options') }}',
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function initSelect2Dis(el, url, extra = {}) {
                $(el).select2({
                    dropdownParent: $('#modal-disposal'),
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
                    if (this.id === 'ds-asset') {
                        $('#ds-asset-uuid').val(e.params.data.id);
                    }
                });
            }

            function preloadSelectValue($select, id, text) {
                if (!id) return;
                const opt = new Option(text ?? String(id), id, true, true);
                $select.append(opt).trigger('change');
            }

            function resetFormToCreate() {
                const $form = $('#formDisposal');
                $('#ds-edit-id').val('');
                $('#ds-asset-uuid').val('');
                $form[0].reset();
                const fileInput = document.getElementById('ds-file');
                if (fileInput) fileInput.value = '';
                $('#ds-remove-file').val('0');
                $('#ds-current-file').addClass('d-none');
                $('#ds-current-file-link').attr('href', '#').text('');
                if ($('#ds-asset').data('select2')) $('#ds-asset').select2('destroy');
                $('#ds-asset').empty();
                initSelect2Dis('#ds-asset', DIS.assets);
                $('#ds-asset').prop('disabled', false);
            }

            $('#btnOpenDis').on('click', function(e) {
                e.preventDefault();
                resetFormToCreate();
                $('.modal-title', '#modal-disposal').text('New Disposal');
                $('#modal-disposal').modal('show');
            });

            $('#formDisposal').off('submit').on('submit', function(e) {
                e.preventDefault();

                const isEdit = !!$('#ds-edit-id').val();
                const id = $('#ds-edit-id').val();
                const fileEl = document.getElementById('ds-file');
                const hasFile = fileEl && fileEl.files && fileEl.files.length > 0;

                const base = {
                    asset_uuid: $('#ds-asset-uuid').val() || null,
                    note: $('#ds-note').val() || '',
                    remove_file: $('#ds-remove-file').val() || 0
                };

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
                        url: isEdit ? DIS.update.replace(':id', id) : DIS.create,
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false
                    });
                } else {
                    req = isEdit ?
                        $.ajax({
                            url: DIS.update.replace(':id', id),
                            type: 'PUT',
                            data: base
                        }) :
                        $.post(DIS.create, base);
                }

                req.done(() => {
                        $('#modal-disposal').modal('hide');
                        Swal.fire(isEdit ? 'Updated' : 'Success', isEdit ? 'Disposal updated.' :
                            'Disposal created.', 'success');
                        $('#ds-asset').prop('disabled', false);
                        $('#tbl-so-global').DataTable().ajax.reload(null, false);
                        if (fileEl) fileEl.value = '';
                        $('#ds-remove-file').val('0');
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });

        })();
    </script>
@endpush
