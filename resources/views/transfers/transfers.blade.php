@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Transfers</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Transfers</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            <div class="card">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Transfers Data</span>
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
                        id="tbl-transfers-all">
                        <thead>
                            <tr class="table-light">
                                <th class="min-w-200px">Transfer Code</th>
                                <th class="min-w-200px">Asset</th>
                                <th class="min-w-200px">Type</th>
                                <th class="min-w-200px">Before</th>
                                <th class="min-w-200px">After</th>
                                <th class="min-w-200px">Note</th>
                                <th class="min-w-200px">Requester</th>
                                <th class="min-w-200px">Approver</th>
                                <th class="min-w-200px">Status</th>
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
                <form id="formTransfer">
                    @csrf
                    <input type="hidden" name="asset_uuid" id="tf-asset-uuid">
                    <input type="hidden" id="tf-edit-id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title">New Transfer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-5">
                            <div class="col-md-12">
                                <label class="form-label required">Asset</label>
                                <select id="tf-asset" class="form-select" required></select>
                                <div class="form-text">Search by code or description</div>
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
                                <label class="form-label required">Transfer To</label>
                                <select id="tf-target" class="form-select" required></select>
                                <input type="hidden" name="after[value]" id="tf-target-hidden">
                                <div class="form-text" id="tf-target-help">Pick the new value.</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" class="form-control" rows="3" placeholder="Optional note…"></textarea>
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
                data: '{{ route('transfer.data.all') }}',
                show: '{{ route('transfer.show', ':id') }}',
                update: '{{ route('transfer.update', ':id') }}',
                create: '{{ route('transfer.store') }}',
                assets: '{{ route('assets.options') }}',
                usercode: '{{ route('master.user_code.options') }}',
                status: '{{ route('master.status.options') }}',
                location: '{{ route('master.location.options') }}',
                approve: id => '{{ route('transfer.approve', ':id') }}'.replace(':id', id),
                reject: id => '{{ route('transfer.reject', ':id') }}'.replace(':id', id),
                destroy: id => '{{ route('transfer.destroy', ':id') }}'.replace(':id', id),
            };

            // CSRF
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Helpers
            function preloadSelectValue($select, id, text) {
                if (!id) return;
                const opt = new Option(text ?? String(id), id, true, true);
                $select.append(opt).trigger('change');
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
                        $('#tf-asset-uuid').val(e.params.data.id);
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
            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
            // Table
            const dt = $('#tbl-transfers-all').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: R.data,
                    data: d => {
                        d.workflow = $('#filter-workflow').val() || '';
                    }
                },
                order: [
                    [9, 'desc']
                ],
                "dom": "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l><'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
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
                            // try to show the code if present, otherwise the label
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

            // Filter by workflow
            $('#filter-workflow').on('change', () => dt.ajax.reload());

            // Open create modal
            $('#btnOpenCreate').on('click', function(e) {
                e.preventDefault();
                $('#formTransfer')[0].reset();
                $('#tf-asset-uuid').val('');
                $('#tf-target-hidden').val('');
                $('#tf-type').val('owner');
                $('#tf-edit-id').val(''); // NEW: ensure create mode
                $('.modal-title', '#modal-transfer').text('New Transfer');

                // init selects
                initSelect2('#tf-asset', R.assets);
                initTargetSelect('owner');

                // enable asset selection in create
                $('#tf-asset').prop('disabled', false);

                $('#modal-transfer').modal('show');
            });

            // Change type in modal
            $('#tf-type').on('change', function() {
                initTargetSelect(this.value);
            });

            // Submit create
            $('#formTransfer').off('submit').on('submit', function(e) {
                e.preventDefault();

                const isEdit = !!$('#tf-edit-id').val();
                const payload = {
                    type: $('#tf-type').val(),
                    'after[value]': $('#tf-target-hidden').val(),
                    note: $('textarea[name="note"]').val() || '',
                    asset_uuid: $('#tf-asset-uuid').val() || null // 🔴 include for both create & edit
                };

                if (!payload['after[value]']) {
                    Swal.fire('Target required', 'Please select a transfer target.', 'warning');
                    return;
                }
                if (!payload.asset_uuid) {
                    Swal.fire('Asset required', 'Please choose an asset.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Saving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                if (isEdit) {
                    const id = $('#tf-edit-id').val();
                    $.ajax({
                            url: R.update.replace(':id', id),
                            type: 'PUT',
                            data: payload
                        })
                        .done(() => {
                            $('#modal-transfer').modal('hide');
                            Swal.fire('Updated', 'Transfer updated.', 'success');
                            $('#tf-asset').prop('disabled', false);
                            $('#tbl-transfers-all').DataTable().ajax.reload(null, false);
                        })
                        .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                } else {
                    $.post(R.create, payload)
                        .done(() => {
                            $('#modal-transfer').modal('hide');
                            Swal.fire('Success', 'Transfer created.', 'success');
                            $('#tbl-transfers-all').DataTable().ajax.reload(null, false);
                        })
                        .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                }
            });

            // Row actions
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

                    // preload asset label
                    const $asset = $('#tf-asset');
                    const assetText = d.asset_label ??
                        (d.asset_code ? (d.asset_desc ? `${d.asset_code} - ${d.asset_desc}` : d
                                .asset_code) :
                            d.asset_uuid);

                    $asset.empty();
                    const opt = new Option(assetText ?? '', d.asset_uuid ?? '', true, true);
                    $asset.append(opt).trigger(
                        'change.select2');
                    $asset.prop('disabled', true);

                    const type = d?.type || 'owner';
                    $('#tf-type').val(type).trigger('change');
                    initTargetSelect(type);

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
                                $('#tf-target-hidden').val(
                                    afterVal);
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
                        $.post(R.approve(id)).done(() => {
                                Swal.fire('Approved', '', 'success');
                                dt.ajax.reload(null, false);
                            })
                            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });
            $('#tbl-transfers-all').on('click', '.btn-tf-reject', function() {
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
                        $.post(R.reject(id)).done(() => {
                                Swal.fire('Rejected', '', 'success');
                                dt.ajax.reload(null, false);
                            })
                            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
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
                    })
                    .then(r => {
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
                            })
                            .fail(x => Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
                    });
            });

        })();
    </script>
@endpush
