@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Disposal</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        Transaction
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Disposal</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            <div class="card">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Disposal Data</span>
                    </h3>
                    <div class="card-toolbar">
                        <div class="d-flex align-items-center gap-3">
                            <select id="filter-workflow" class="form-select form-select-sm w-auto">
                                <option value="">All Status</option>
                                @foreach ($workflows as $w)
                                    <option value="{{ $w->kode }}">{{ $w->kode }} - {{ $w->name }}</option>
                                @endforeach
                            </select>
                            <a href="#" id="btnOpenCreate" class="btn btn-sm btn-danger">
                                <i class="ki-duotone ki-plus fs-2"></i>Add New
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded"
                        id="tbl-disposals-all">
                        <thead>
                            <tr class="table-light">
                                <th class="min-w-200px">Disposal Code</th>
                                <th class="min-w-200px">Asset</th>
                                <th class="min-w-200px">Note</th>
                                <th class="min-w-200px">Requester</th>
                                <th class="min-w-200px">Approver</th>
                                <th class="min-w-200px">Status</th>
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
                            <div id="ds-asset-snapshot" class="mt-4 d-none">
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
            const R = {
                data: '{{ route('disposal.data.all') }}', 
                show: '{{ route('disposal.show', ':id') }}', 
                update: '{{ route('disposal.update', ':id') }}',
                create: '{{ route('disposal.store') }}', 
                destroy: '{{ route('disposal.destroy', ':id') }}', 
                assetBrief: id => '{{ route('assets.brief', ':id') }}'.replace(':id', id),
                approve: id => '{{ route('disposal.approve', ':id') }}'.replace(':id', id),
                reject: id => '{{ route('disposal.reject', ':id') }}'.replace(':id', id),

                assets: '{{ route('assets.options') }}', 
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function initSelect2(el, url, extra = {}) {
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
                        fetchAssetSnapshot(e.params.data.id);
                    }
                }).on('select2:clear', function() {
                    if (this.id === 'ds-asset') {
                        $('#ds-asset-uuid').val('');
                        clearSnapshot();
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
                initSelect2('#ds-asset', R.assets);
                $('#ds-asset').prop('disabled', false);
            }

            $('#btnOpenCreate').on('click', function(e) {
                e.preventDefault();
                resetFormToCreate();
                $('.modal-title', '#modal-disposal').text('New Disposal');
                $('#modal-disposal').modal('show');
            });

            $('#ds-btn-remove-file').off('click').on('click', function() {
                $('#ds-remove-file').val('1');
                $('#ds-current-file').addClass('d-none');
            });
            

            function renderSnapshot(d) {
                const safe = (v) => v || '';
                $('#snap-owner').text(safe(d.owner_label));
                $('#snap-user').text(safe(d.user_label));
                $('#snap-maintenance').text(safe(d.maintenance_label));
                $('#snap-status').text(safe(d.status_label));
                $('#snap-location').text(safe(d.location_label));
                $('#ds-asset-snapshot').removeClass('d-none');
            }

            function clearSnapshot() {
                $('#snap-owner,#snap-user,#snap-maintenance,#snap-status,#snap-location').text('');
                $('#ds-asset-snapshot').addClass('d-none');
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

            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
            const dt = $('#tbl-disposals-all').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: R.data,
                    data: d => {
                        d.workflow = $('#filter-workflow').val() || '';
                    }
                },
                order: [
                    [7, 'desc']
                ],
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l><'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i><'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                searching: true,
                scrollX: true,
                columns: [{
                        data: 'disposal_code',
                        name: 'assets_disposals.disposal_code'
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
                        data: 'note',
                        name: 'assets_disposals.note',
                        render: d => d ? $('<div>').text(d).html() : ''
                    },
                    {
                        data: 'pic_request_uid',
                        name: 'assets_disposals.pic_request_uid'
                    },
                    {
                        data: 'pic_approve_uid',
                        name: 'assets_disposals.pic_approve_uid',
                        defaultContent: ''
                    },
                    {
                        data: 'workflow_label',
                        name: 'workflow_label',
                        defaultContent: '',
                        render: function(data, type, row) {
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
                        data: 'file',
                        name: 'file',
                        orderable: false,
                        searchable: false,
                        defaultContent: ''
                    },
                    {
                        data: 'updated_at',
                        name: 'assets_disposals.updated_at',
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

            $('#filter-workflow').on('change', () => dt.ajax.reload());

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
                        url: isEdit ? R.update.replace(':id', id) : R.create,
                        type: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false
                    });
                } else {
                    req = isEdit ?
                        $.ajax({
                            url: R.update.replace(':id', id),
                            type: 'PUT',
                            data: base
                        }) :
                        $.post(R.create, base);
                }

                req.done(() => {
                        $('#modal-disposal').modal('hide');
                        Swal.fire(isEdit ? 'Updated' : 'Success', isEdit ? 'Disposal updated.' :
                            'Disposal created.', 'success');
                        $('#ds-asset').prop('disabled', false);
                        dt.ajax.reload(null, false);
                        if (fileEl) fileEl.value = '';
                        $('#ds-remove-file').val('0');
                    })
                    .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
            });

            $('#tbl-disposals-all').on('click', '.btn-ds-edit', function() {
                const id = $(this).data('id');
                $('#modal-disposal').modal('show');
                $('.modal-title', '#modal-disposal').text('Edit Disposal');

                if ($('#ds-asset').data('select2')) $('#ds-asset').select2('destroy');
                $('#ds-asset').empty();
                initSelect2('#ds-asset', R.assets);

                $.getJSON(R.show.replace(':id', id), function(d) {
                    $('#ds-edit-id').val(d.uuid || id);
                    $('#ds-asset-uuid').val(d.asset_uuid || '');

                    const $asset = $('#ds-asset');
                    const assetText = d.asset_label ?? (d.asset_code ? (d.asset_desc ?
                        `${d.asset_code} - ${d.asset_desc}` : d.asset_code) : d.asset_uuid);
                    const opt = new Option(assetText ?? '', d.asset_uuid ?? '', true, true);
                    $asset.append(opt).trigger('change.select2');
                    $asset.prop('disabled', true);

                    $('#ds-note').val(d.note || '');

                    if (d.file_url) {
                        $('#ds-current-file-link').attr('href', d.file_url).text(d.file_name ||
                            'Current file');
                        $('#ds-current-file').removeClass('d-none');
                        $('#ds-remove-file').val('0');
                    } else {
                        $('#ds-current-file').addClass('d-none');
                        $('#ds-remove-file').val('0');
                    }
                }).fail(() => {
                    Swal.fire('Error', 'Cannot load disposal', 'error');
                    $('#ds-edit-id').val('');
                    $('#modal-disposal').modal('hide');
                });
            });

            $('#tbl-disposals-all').on('click', '.btn-ds-approve', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Approve?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6'
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        Swal.fire({
                            title: 'Applying…',
                            didOpen: () => Swal.showLoading()
                        });
                        $.post(R.approve(id)).done(() => {
                            Swal.fire('Approved', '', 'success');
                            dt.ajax.reload(null, false);
                        }).fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

            $('#tbl-disposals-all').on('click', '.btn-ds-reject', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Reject?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6'
                    })
                    .then(r => {
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

            $('#tbl-disposals-all').on('click', '.btn-ds-delete', function() {
                const id = $(this).data('id');
                Swal.fire({
                        title: 'Delete?',
                        text: 'Moved to trash',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#EA242A',
                        cancelButtonColor: '#B5B5B6'
                    })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        Swal.fire({
                            title: 'Deleting…',
                            didOpen: () => Swal.showLoading()
                        });
                        $.ajax({
                                url: R.destroy.replace(':id', id),
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
