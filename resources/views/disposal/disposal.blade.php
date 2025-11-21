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
            <div class="card mb-6">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Status Approval</label>
                            <select id="ds-status" class="form-select">
                                <option value="">All</option>
                                @foreach ($workflows as $w)
                                    <option value="{{ $w->kode }}">{{ $w->kode }} - {{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Requester</label>
                            <select id="ds-requester" class="form-select"></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Updated From</label>
                            <input type="date" id="ds-updated-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Updated To</label>
                            <input type="date" id="ds-updated-to" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 align-items-end mt-1">
                        <div class="col-md-3">
                            <label class="form-label">Created From</label>
                            <input type="date" id="ds-created-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Created To</label>
                            <input type="date" id="ds-created-to" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Asset (code / description)</label>
                            <input type="text" id="ds-asset-q" class="form-control"
                                placeholder="e.g. A1101000002-00 / Laptop Dell">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="ds-btn-apply" class="btn btn-sm btn-danger me-2">
                        Apply Filter
                    </button>
                    <button id="ds-btn-reset" class="btn btn-sm btn-light-danger me-2">
                        Reset
                    </button>
                    <button id="ds-btn-export" class="btn btn-sm btn-light-danger">
                        Export Excel
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Disposal Data</span>
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
                        id="tbl-disposals-all">
                        <thead>
                            <tr class="table-light">
                                <th class="min-w-200px">Transaction Number</th>
                                <th class="min-w-200px">Asset</th>
                                <th class="min-w-200px">Reason</th>
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
                                <br>
                                <div class="p-3 border rounded bg-light">
                                    <div class="fw-semibold mb-2">Acquisition Info</div>
                                    <div class="row gy-2">
                                        {{-- NEW FIELDS --}}
                                        <div class="col-6">
                                            <div class="text-muted">Acquisition Date</div>
                                            <div id="snap-acq-date" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Commercial Acquisition Cost (IDR)</div>
                                            <div id="snap-commercial-acq" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Commercial Accumulated Depreciation (IDR)</div>
                                            <div id="snap-commercial-acc-depr" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Commercial Net Book Value (IDR)</div>
                                            <div id="snap-commercial-nbv" class="fw-semibold"></div>
                                        </div>

                                        {{-- Existing fields --}}
                                        <div class="col-6">
                                            <div class="text-muted">Price</div>
                                            <div id="snap-price" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Quantity</div>
                                            <div id="snap-quantity" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">VAT In</div>
                                            <div id="snap-vat-in" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">UOM</div>
                                            <div id="snap-uom" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Total</div>
                                            <div id="snap-total" class="fw-semibold"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted">Useful Life (Month)</div>
                                            <div id="snap-useful-life" class="fw-semibold"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-12">
                                <label for="ds-reason" class="form-label required">Reason</label>
                                <select name="reason" id="ds-reason" class="form-select" required>
                                    <option value="" selected disabled>-- Select Reason --</option>
                                    <option value="Sale">Sale</option>
                                    <option value="Waste">Waste</option>
                                    <option value="Donate">Donate</option>
                                    <option value="Held">Held</option>
                                </select>
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

    {{-- NEW: Flow / Approval / BA Upload Modal --}}
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
                            <div><strong>Transaction Number:</strong> <span id="flow-code"></span></div>
                            <div><strong>Reason:</strong> <span id="flow-reason"></span></div>
                            <div><strong>Acquisition Date:</strong> <span id="flow-acq-date"></span></div>
                            <div><strong>Commercial Acquisition Cost (IDR):</strong> <span id="flow-commercial-acq"></span>
                            </div>
                            <div><strong>Commercial Accumulated Depreciation (IDR):</strong> <span
                                    id="flow-commercial-acc-depr"></span></div>
                            <div><strong>Commercial Net Book Value (IDR):</strong> <span id="flow-commercial-nbv"></span>
                            </div>

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

                approveStep: id => '{{ route('disposal.approve-step', ':id') }}'.replace(':id', id),
                form: id => '{{ route('disposal.form', ':id') }}'.replace(':id', id),

                assets: '{{ route('assets.options') }}',
                exportUrl: '{{ route('export.disposal') }}',
            };

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $('#ds-requester').select2({
                placeholder: 'Select requester',
                width: '100%',
                allowClear: true,
                ajax: {
                    url: '{{ route('users.options') }}',
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1,
                    }),
                    processResults: d => d,
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
                            page: params.page || 1,
                            type: 'disposal',
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
                $('#ds-reason').val('');
                clearSnapshot();
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

                const formatIDR = (v) => {
                    if (v === null || v === undefined || v === '') return '';
                    const n = Number(v);
                    if (isNaN(n)) return v;
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(n);
                };

                $('#snap-owner').text(safe(d.owner_label));
                $('#snap-user').text(safe(d.user_label));
                $('#snap-maintenance').text(safe(d.maintenance_label));
                $('#snap-status').text(safe(d.status_label));
                $('#snap-location').text(safe(d.location_label));

                // NEW
                $('#snap-acq-date').text(safe(d.acquisition_date));
                $('#snap-commercial-acq').text(formatIDR(d.commercial_acq_cost));
                $('#snap-commercial-acc-depr').text(formatIDR(d.commercial_accum_depr));
                $('#snap-commercial-nbv').text(formatIDR(d.commercial_nbv));

                // Existing
                $('#snap-price').text(safe(d.price));
                $('#snap-quantity').text(safe(d.quantity));
                $('#snap-vat-in').text(safe(d.vat_in));
                $('#snap-uom').text(safe(d.kode_uom));
                $('#snap-total').text(safe(d.total));
                $('#snap-useful-life').text(safe(d.useful_life_month));

                $('#ds-asset-snapshot').removeClass('d-none');
            }

            function clearSnapshot() {
                $('#snap-owner,#snap-user,#snap-maintenance,#snap-status,#snap-location,' +
                    '#snap-acq-date,#snap-commercial-acq,#snap-commercial-acc-depr,#snap-commercial-nbv,' +
                    '#snap-price,#snap-quantity,#snap-vat-in,#snap-uom,#snap-total,#snap-useful-life'
                ).text('');

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
            const FORM_URL_TPL = @json(route('disposal.form', '__UUID__'));
            const BA_URL_TPL = @json(route('disposal.ba', '__UUID__'));


            const urlParams = new URLSearchParams(window.location.search);
            const statusFromUrl = urlParams.get('status');
            if (statusFromUrl) {
                $('#ds-status').val(statusFromUrl);
            }
            const dt = $('#tbl-disposals-all').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: R.data,
                    data: d => {
                        d.status = $('#ds-status').val() || '';
                        d.requester = $('#ds-requester').val() || '';
                        d.updated_from = $('#ds-updated-from').val() || '';
                        d.updated_to = $('#ds-updated-to').val() || '';
                        d.created_from = $('#ds-created-from').val() || '';
                        d.created_to = $('#ds-created-to').val() || '';
                        d.asset_q = $('#ds-asset-q').val() || '';
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
                        data: 'reason',
                        name: 'assets_disposals.reason'
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
                ],
                rowCallback: function(row, data) {
                    const $actions = $('td:last', row);
                    const $group = $actions.find('.btn-group');

                    if (!$group.length) return;

                    const formUrl = (data.form_file_url && data.form_file_url.length) ?
                        data.form_file_url :
                        FORM_URL_TPL.replace('__UUID__', encodeURIComponent(data.uuid));

                    const baUrl = (data.ba_file_url && data.ba_file_url.length) ?
                        data.ba_file_url :
                        BA_URL_TPL.replace('__UUID__', encodeURIComponent(data.uuid));

                    const formBtn = `
                            <button type="button"
                            class="btn btn-light-info btn-sm btn-ds-form"
                            onclick="window.open('${formUrl}','_blank')">
                            Form
                            </button>
                        `;

                    const baBtn = `
                            <button type="button"
                            class="btn btn-light-warning btn-sm btn-ds-ba"
                            onclick="window.open('${baUrl}','_blank')">
                            BA
                            </button>
                        `;

                    // Always allow Form button
                    if (!$group.find('.btn-ds-form').length) {
                        $group.prepend(formBtn);
                    }

                    // BA button only when last step (asset_mgt) is approved
                    if (isAssetMgtApproved(data.flow) && !$group.find('.btn-ds-ba').length) {
                        $group.prepend(baBtn);
                    }
                }



            });
            $('#ds-btn-apply').on('click', function(e) {
                e.preventDefault();
                dt.ajax.reload();
            });

            $('#ds-btn-reset').on('click', function(e) {
                e.preventDefault();
                $('#ds-status').val('');
                $('#ds-requester').val(null).trigger('change');
                $('#ds-updated-from').val('');
                $('#ds-updated-to').val('');
                $('#ds-created-from').val('');
                $('#ds-created-to').val('');
                $('#ds-asset-q').val('');
                dt.ajax.reload();
            });

            $('#ds-btn-export').on('click', function(e) {
                e.preventDefault();

                const params = $.param({
                    status: $('#ds-status').val() || '',
                    requester: $('#ds-requester').val() || '',
                    updated_from: $('#ds-updated-from').val() || '',
                    updated_to: $('#ds-updated-to').val() || '',
                    created_from: $('#ds-created-from').val() || '',
                    created_to: $('#ds-created-to').val() || '',
                    asset_q: $('#ds-asset-q').val() || '',
                });

                window.location = R.exportUrl + '?' + params;
            });

            function isAssetMgtApproved(flowRaw) {
                if (!flowRaw) return false;

                let flow = flowRaw;

                if (typeof flow === 'string') {
                    try {
                        flow = JSON.parse(flow);
                    } catch (e) {
                        return false;
                    }
                }

                if (!flow || !Array.isArray(flow.steps)) return false;

                for (const st of flow.steps) {
                    if (st.code === 'akp_head') {
                        return !!st.approved_at;
                    }
                }
                return false;
            }
            $('#formDisposal').off('submit').on('submit', function(e) {
                e.preventDefault();

                const isEdit = !!$('#ds-edit-id').val();
                const id = $('#ds-edit-id').val();
                const fileEl = document.getElementById('ds-file');
                const hasFile = fileEl && fileEl.files && fileEl.files.length > 0;

                const base = {
                    asset_uuid: $('#ds-asset-uuid').val() || null,
                    note: $('#ds-note').val() || '',
                    remove_file: $('#ds-remove-file').val() || 0,
                    reason: $('#ds-reason').val() || '',
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

                    // also fetch snapshot
                    if (d.asset_uuid) {
                        fetchAssetSnapshot(d.asset_uuid);
                    }
                }).fail(() => {
                    Swal.fire('Error', 'Cannot load disposal', 'error');
                    $('#ds-edit-id').val('');
                    $('#modal-disposal').modal('hide');
                });
            });

            // Replace old direct approve with FLOW-based approval
            $('#tbl-disposals-all').on('click', '.btn-ds-approve', function() {
                const id = $(this).data('id');
                openDisposalFlow(id);
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

            // ===============================
            // NEW: FLOW MODAL JS
            // ===============================
            function openDisposalFlow(id) {
                $('#formDisposalFlow')[0].reset();
                $('#flow-steps').empty();
                $('#flow-wrap-ba-upload').addClass('d-none');
                $('#flow-wrap-form-upload').addClass('d-none');
                $('#flow-disposal-id').val(id);
                $('#flow-form-file').html('');
                $('#flow-ba-file').html('');
                $('#flow-asset').text('');
                $('#flow-code').text('');
                $('#flow-reason').text('');

                // NEW: clear extra info
                $('#flow-acq-date').text('');
                $('#flow-commercial-acq').text('');
                $('#flow-commercial-acc-depr').text('');
                $('#flow-commercial-nbv').text('');

                const formatIDR = (v) => {
                    if (v === null || v === undefined || v === '') return '';
                    const n = Number(v);
                    if (isNaN(n)) return v;
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(n);
                };

                Swal.fire({
                    title: 'Loading flow…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.getJSON(R.show.replace(':id', id))
                    .done(function(d) {
                        Swal.close();
                        $('#flow-asset').text(((d.asset_code || '') + (d.asset_name ? (' - ' + d.asset_name) :
                            '')) || d.asset_uuid);
                        $('#flow-code').text(d.disposal_code || '');
                        $('#flow-reason').text(d.reason || '');

                        // NEW: fill extra info
                        $('#flow-acq-date').text(d.acquisition_date || '');
                        $('#flow-commercial-acq').text(formatIDR(d.commercial_acq_cost));
                        $('#flow-commercial-acc-depr').text(formatIDR(d.commercial_accum_depr));
                        $('#flow-commercial-nbv').text(formatIDR(d.commercial_nbv));

                        const formDownloadUrl = FORM_URL_TPL.replace('__UUID__', encodeURIComponent(d.uuid));
                        const baDownloadUrl = BA_URL_TPL.replace('__UUID__', encodeURIComponent(d.uuid));

                        if (d.flow_file_url) {
                            $('#flow-form-file').html(
                                `<a href="${d.flow_file_url}" target="_blank">${d.flow_file_name || 'Download Form'}</a>`
                            );
                        } else {
                            $('#flow-form-file').html(
                                `<a href="${formDownloadUrl}" target="_blank">Download auto-filled form</a>`
                            );
                        }
                        if (isAssetMgtApproved(d.flow)) {
                            if (d.ba_file_url) {
                                $('#flow-ba-file').html(
                                    `<a href="${d.ba_file_url}" target="_blank">${d.ba_file_name || 'Download BA'}</a>`
                                );
                            } else {
                                $('#flow-ba-file').html(
                                    `<a href="${baDownloadUrl}" target="_blank">Download auto-filled BA</a>`
                                );
                            }
                        } else {
                            $('#flow-ba-file').html('Not available yet');
                        }

                        renderFlowSteps(d.flow);
                        $('#modal-disposal-flow').modal('show');
                    })
                    .fail(function(x) {
                        Swal.fire('Error', x.responseJSON?.message || 'Cannot load flow', 'error');
                    });
            }

            function renderFlowSteps(flow) {
                $('#flow-steps').empty();
                $('#flow-wrap-ba-upload').addClass('d-none');
                if (!flow || !flow.steps) return;

                let pendingIndex = null;
                flow.steps.forEach(function(st, idx) {
                    if (!st.approved_at && pendingIndex === null) {
                        pendingIndex = idx;
                    }
                });

                flow.steps.forEach(function(st, idx) {
                    let li = $(
                        '<li class="list-group-item d-flex justify-content-between align-items-center"></li>'
                    );
                    let left = $('<div></div>');
                    let right = $('<div class="text-end"></div>');

                    left.append('<div class="fw-bold">' + (st.label || st.code || '') + '</div>');
                    left.append('<div class="text-muted small">' + (st.role || '') + '</div>');

                    if (st.approved_at) {
                        right.append('<span class="badge badge-light-success mb-1">Approved</span><br>');
                        right.append('<span class="small text-muted">' + (st.approved_by || '') +
                            '</span><br>');
                        right.append('<span class="small text-muted">' + st.approved_at + '</span>');
                    } else if (pendingIndex === idx) {
                        right.append('<span class="badge badge-light-warning">Pending (Next)</span>');
                        if (st.code === 'asset_mgt') {
                            $('#flow-wrap-ba-upload').removeClass('d-none');
                            $('#flow-wrap-form-upload').removeClass('d-none');
                        }
                    } else {
                        right.append('<span class="badge badge-light-secondary">Pending</span>');
                    }

                    li.append(left).append(right);
                    $('#flow-steps').append(li);
                });
            }

            $('#formDisposalFlow').on('submit', function(e) {
                e.preventDefault();

                const id = $('#flow-disposal-id').val();
                if (!id) return;

                const fd = new FormData(this);

                Swal.fire({
                    title: 'Approving…',
                    didOpen: () => Swal.showLoading(),
                    allowOutsideClick: false
                });

                $.ajax({
                    url: R.approveStep(id),
                    type: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false
                }).done(function(res) {
                    Swal.close();
                    if (res.flow) {
                        renderFlowSteps(res.flow);
                    }
                    dt.ajax.reload(null, false);
                    if (res.kode_status === 'ACC') {
                        Swal.fire('Approved', 'Disposal fully approved.', 'success');
                        $('#modal-disposal-flow').modal('hide');
                    } else {
                        Swal.fire('Approved', 'Step approved.', 'success');
                    }
                }).fail(function(x) {
                    Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error');
                });
            });

        })();
    </script>
@endpush
