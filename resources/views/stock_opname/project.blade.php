@extends('layouts.app')

@push('head')
    <style>
        th,
        td {
            text-align: center !important;
        }

        /* === FIX select2 in bootstrap5/metronic modal === */
        #assignProjectModal .modal-dialog {
            pointer-events: auto !important;
        }

        #assignProjectModal .modal-content {
            pointer-events: auto !important;
        }

        /* make select2 always above modal/backdrop */
        #assignProjectModal .select2-container--open,
        #assignProjectModal .select2-dropdown {
            z-index: 2000 !important;
        }

        /* if metronic adds overlay/transform weirdness */
        .select2-container {
            width: 100% !important;
        }
    </style>
@endpush

@push('scripts')
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: @json(session('success')),
                timer: 2000,
                showConfirmButton: false
            });
            localStorage.removeItem('assets_select_uuids_v1');
        </script>
    @endif

    <script>
        const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
        const STORAGE_KEY = 'assets_select_uuids_v1';

        let assignModal = null;
        let table = null;

        // -------------------------
        // LocalStorage selection
        // -------------------------
        function getSelectedSet() {
            try {
                const arr = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
                return new Set(Array.isArray(arr) ? arr : []);
            } catch (e) {
                return new Set();
            }
        }

        function saveSelectedSet(set) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(set)));
            updateSelectedBadge(set.size);
        }

        function updateSelectedBadge(n) {
            $('#selectedCount').text(n);
            $('#btnAssignProject').prop('disabled', n <= 0);
        }

        function syncCheckboxesFromStorage() {
            const set = getSelectedSet();
            $('.row-select').each(function() {
                const uuid = $(this).data('uuid');
                this.checked = set.has(uuid);
            });
            updateSelectedBadge(set.size);
            syncHeaderCheckbox();
        }

        function syncHeaderCheckbox() {
            const $rows = $('.row-select');
            if ($rows.length === 0) {
                $('#cbAll').prop('checked', false).prop('indeterminate', false);
                return;
            }
            const total = $rows.length;
            const checked = $rows.filter(':checked').length;

            if (checked === 0) {
                $('#cbAll').prop('checked', false).prop('indeterminate', false);
            } else if (checked === total) {
                $('#cbAll').prop('checked', true).prop('indeterminate', false);
            } else {
                $('#cbAll').prop('checked', false).prop('indeterminate', true);
            }
        }

        function formatNumber(d) {
            if (d == null || d === '') return '';
            return Number(d).toLocaleString('en-US');
        }

        // -------------------------
        // Select2 helpers
        // -------------------------
        function initMasterSelect2($el, url, placeholder) {
            $el.select2({
                width: '100%',
                allowClear: true,
                placeholder: placeholder || '— All —',
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term || ''
                    }),
                    processResults: data => data
                }
            });
        }

        function destroyProjectSelect2IfAny() {
            const $sel = $('#projectSelect');
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.off('select2:select select2:clear change');
                $sel.select2('destroy');
                $sel.empty(); // optional: reset options
            }
        }

        function initProjectSelect2() {
            const $modal = $('#assignProjectModal');
            const $sel = $('#projectSelect');

            // prevent double init
            if ($sel.hasClass('select2-hidden-accessible')) return;

            $sel.select2({
                width: '100%',
                allowClear: true,
                placeholder: '— Select project —',
                dropdownParent: $modal.find('.modal-content'), // ✅ must be inside modal-content
                ajax: {
                    url: "{{ route('stockopname.asset_projects.options') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1
                    }),
                    processResults: (data, params) => {
                        params.page = params.page || 1;

                        // IMPORTANT: ensure items have {id,text}
                        const items = (data.results || []).map(x => ({
                            id: x.id ?? x.uuid, // tolerate old response
                            text: x.text ?? x.name
                        }));

                        return {
                            results: items,
                            pagination: data.pagination || {
                                more: false
                            }
                        };
                    }
                }
            });

            // ✅ reliable fill project_uuid
            $sel.on('select2:select', function(e) {
                const id = e.params?.data?.id || '';
                $('#projectUuid').val(id);
                $('#projectName').val(''); // optional: clear manual name when picking existing
            });

            $sel.on('select2:clear', function() {
                $('#projectUuid').val('');
            });

            // fallback (rare)
            $sel.on('change', function() {
                $('#projectUuid').val($(this).val() || '');
            });
        }

        // -------------------------
        // Modal prepare + guards
        // -------------------------
        function openAssignProjectModal() {
            const set = getSelectedSet();
            const uuids = Array.from(set);

            $('#modalSelectedCount').text(uuids.length);

            // reset inputs
            $('#projectUuid').val('');
            $('#projectName').val('');

            // reset select2 cleanly (important)
            destroyProjectSelect2IfAny();
            $('#projectSelect').val(null);

            // rebuild hidden uuids[]
            $('#assignProjectForm input[name="uuids[]"]').remove();
            uuids.forEach(u => {
                $('#assignProjectForm').append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'uuids[]',
                        value: u
                    })
                );
            });

            assignModal.show();
        }

        function bindAssignFormGuards() {
            $('#assignProjectForm').on('submit', function(e) {
                const pu = ($('#projectUuid').val() || '').trim();
                const pn = ($('#projectName').val() || '').trim();
                const n = $('#assignProjectForm input[name="uuids[]"]').length;

                if (n === 0) {
                    e.preventDefault();
                    Swal.fire('Oops', 'No assets selected.', 'error');
                    return;
                }

                if (!pu && !pn) {
                    e.preventDefault();
                    Swal.fire('Oops', 'Select existing project OR input new project name.', 'warning');
                    return;
                }
            });
        }

        // -------------------------
        // DataTable init
        // -------------------------
        function initDataTable() {
            table = $('#assetsSelectTable').DataTable({
                serverSide: true,
                processing: true,
                scrollX: true,
                ajax: {
                    url: '{{ route('stockopname.assets.select.datatable') }}',
                    data: function(d) {
                        d.asset_class = $('#flt-asset-class').val() || '';
                        d.transaction = $('#flt-transaction').val() || '';
                        d.location = $('#flt-location').val() || '';
                        d.status = $('#flt-status').val() || '';
                        d.owner = $('#flt-owner').val() || '';
                        d.user = $('#flt-user').val() || '';
                        d.maintenance = $('#flt-maintenance').val() || '';
                        d.sumber = $('#flt-sumber').val() || '';
                        d.asset_q = $('#flt-asset-q').val() || '';
                    }
                },
                order: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'select_cb',
                        name: 'select_cb',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'asset_code',
                        name: 'a.asset_code',
                        render: function(data, type, row) {
                            if (type !== 'display') return data;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row.uuid));
                            return `<a href="${url}" class="text-primary fw-semibold">${data ?? ''}</a>`;
                        }
                    },
                    {
                        data: 'kode_asset_class_label',
                        name: 'a.kode_asset_class'
                    },
                    {
                        data: 'description',
                        name: 'a.description'
                    },
                    {
                        data: 'kode_location_label',
                        name: 'a.kode_location'
                    },
                    {
                        data: 'asset_owner_label',
                        name: 'a.asset_owner'
                    },
                    {
                        data: 'asset_user_label',
                        name: 'a.asset_user'
                    },
                    {
                        data: 'asset_maintenance_label',
                        name: 'a.asset_maintenance'
                    },
                    {
                        data: 'kode_status_label',
                        name: 'a.kode_status'
                    },
                    {
                        data: 'total',
                        name: 'v.total',
                        render: d => d != null ? Number(d).toLocaleString() : ''
                    },
                    {
                        data: 'last_accumulated_depr',
                        name: 'last_accumulated_depr',
                        render: formatNumber,
                        searchable: false
                    },
                    {
                        data: 'last_net_book_value',
                        name: 'last_net_book_value',
                        render: formatNumber,
                        searchable: false
                    },
                ],
                drawCallback: function() {
                    syncCheckboxesFromStorage();
                }
            });
        }

        // -------------------------
        // Document ready
        // -------------------------
        $(function() {
            // master filters select2
            initMasterSelect2($('#flt-asset-class'), "{{ route('master.asset_class.options') }}",
                'All Asset Class');
            initMasterSelect2($('#flt-transaction'), "{{ route('master.transaction.options') }}", 'All Company');
            initMasterSelect2($('#flt-location'), "{{ route('master.location.options') }}", 'All Location');
            initMasterSelect2($('#flt-status'), "{{ route('master.status.options') }}", 'All Status');
            initMasterSelect2($('#flt-sumber'), "{{ route('master.sumber.options') }}", 'All Sumber');
            initMasterSelect2($('#flt-owner'), "{{ route('master.user_code.options') }}", 'All Owner');
            initMasterSelect2($('#flt-user'), "{{ route('master.user_code.options') }}", 'All User');
            initMasterSelect2($('#flt-maintenance'), "{{ route('master.user_code.options') }}", 'All Maintenance');

            initDataTable();

            // modal
            assignModal = new bootstrap.Modal(document.getElementById('assignProjectModal'), {
                focus: false
            });

            // init project select2 when modal shown (after DOM + modal is visible)
            $('#assignProjectModal').on('shown.bs.modal', function() {
                initProjectSelect2();
            });

            // badge init
            updateSelectedBadge(getSelectedSet().size);

            // events: row checkbox
            $(document).on('change', '.row-select', function() {
                const uuid = $(this).data('uuid');
                const set = getSelectedSet();
                if (this.checked) set.add(uuid);
                else set.delete(uuid);
                saveSelectedSet(set);
                syncHeaderCheckbox();
            });

            // select all current page
            $(document).on('change', '#cbAll', function() {
                const set = getSelectedSet();
                const checked = this.checked;
                $('.row-select').each(function() {
                    const uuid = $(this).data('uuid');
                    this.checked = checked;
                    if (checked) set.add(uuid);
                    else set.delete(uuid);
                });
                saveSelectedSet(set);
                syncHeaderCheckbox();
            });

            // filters
            $('#btn-apply-filter').on('click', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });
            $('#btn-reset-filter').on('click', function(e) {
                e.preventDefault();
                $('.asset-filter').val(null).trigger('change');
                $('#flt-asset-q').val('');
                table.ajax.reload();
            });

            $('#btnExportExcel').on('click', function(e) {
                e.preventDefault();

                const params = new URLSearchParams({
                    asset_class: $('#flt-asset-class').val() || '',
                    transaction: $('#flt-transaction').val() || '',
                    location: $('#flt-location').val() || '',
                    status: $('#flt-status').val() || '',
                    owner: $('#flt-owner').val() || '',
                    user: $('#flt-user').val() || '',
                    maintenance: $('#flt-maintenance').val() || '',
                    sumber: $('#flt-sumber').val() || '',
                    asset_q: $('#flt-asset-q').val() || '',
                });

                const url = "{{ route('stockopname.assets.select.export') }}" + '?' + params.toString();
                window.open(url, '_blank');
            });
            // clear selected
            $('#btnClearSelected').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Clear selection?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, clear',
                    cancelButtonText: 'No'
                }).then(r => {
                    if (!r.isConfirmed) return;
                    localStorage.removeItem(STORAGE_KEY);
                    syncCheckboxesFromStorage();
                });
            });

            // open modal
            $('#btnAssignProject').on('click', function(e) {
                e.preventDefault();
                openAssignProjectModal();
            });

            // guard submit
            bindAssignFormGuards();
        });
    </script>
@endpush

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Stock Opname
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Stock Opname</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Create Projects</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
                <div class="col-md-12">

                    {{-- Filters --}}
                    <div class="card mb-6">
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Asset Class</label>
                                    <select id="flt-asset-class" class="form-select asset-filter"></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Transaction / Company</label>
                                    <select id="flt-transaction" class="form-select asset-filter"></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Location</label>
                                    <select id="flt-location" class="form-select asset-filter"></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select id="flt-status" class="form-select asset-filter"></select>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end mt-1">
                                <div class="col-md-3">
                                    <label class="form-label">Owner</label>
                                    <select id="flt-owner" class="form-select asset-filter"></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">User</label>
                                    <select id="flt-user" class="form-select asset-filter"></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Maintenance</label>
                                    <select id="flt-maintenance" class="form-select asset-filter"></select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Source (Sumber)</label>
                                    <select id="flt-sumber" class="form-select asset-filter"></select>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end mt-1">
                                <div class="col-md-4">
                                    <label class="form-label">Asset (code / description)</label>
                                    <input type="text" id="flt-asset-q" class="form-control"
                                        placeholder="e.g. A1101... / Laptop">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button id="btn-apply-filter" class="btn btn-sm btn-danger me-2">Apply Filter</button>
                            <button id="btn-reset-filter" class="btn btn-sm btn-light-danger me-2">Reset</button>
                            <a id="btnExportExcel" class="btn btn-sm btn-light-danger me-2" href="#">
                                Export Excel
                            </a>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="card mb-5 mb-xl-8">
                        <div class="card-header align-items-center justify-content-between">
                            <div class="d-flex flex-column">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">Pick Assets</span>
                                    <span class="text-muted mt-1 fw-semibold fs-7">
                                        Selected: <span class="badge badge-light-danger" id="selectedCount">0</span>
                                    </span>
                                </h3>
                            </div>

                            <div class="card-toolbar">
                                <button id="btnClearSelected" class="btn btn-sm btn-light-danger me-2">
                                    Clear Selected
                                </button>

                                <form id="selectForm" method="POST"
                                    action="{{ route('stockopname.assets.select.submit') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="uuids" id="uuidsInput" value="[]">
                                    <button id="btnAssignProject" class="btn btn-sm btn-danger" type="button" disabled>
                                        Assign to Project
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card-body py-3">
                            <table id="assetsSelectTable"
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                                <thead class="table-light">
                                    <tr>
                                        <th class="min-w-50px">
                                            <input type="checkbox" id="cbAll" style="margin-left: -25% !important"
                                                class="form-check-input" />
                                        </th>
                                        <th class="min-w-200px">Asset Code</th>
                                        <th class="min-w-150px">Asset Class</th>
                                        <th class="min-w-250px">Description</th>
                                        <th class="min-w-150px">Location</th>
                                        <th class="min-w-250px">Owner</th>
                                        <th class="min-w-250px">User</th>
                                        <th class="min-w-250px">Maintenance</th>
                                        <th class="min-w-150px">Status</th>
                                        <th class="min-w-150px">Total</th>
                                        <th class="min-w-200px">Accum Depr</th>
                                        <th class="min-w-150px">NBV</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="assignProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('stockopname.assets.select.assign_project') }}"
                    id="assignProjectForm">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Assign Selected Assets to Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Existing Project (optional)</label>
                            <select id="projectSelect" class="form-select"></select>
                            <div class="text-muted mt-1">Choose a project OR create a new one below.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Or Create New Project</label>
                            <input type="text" name="project_name" id="projectName" class="form-control"
                                placeholder="e.g. Depot Upgrade Phase 1" maxlength="150">
                        </div>

                        <input type="hidden" name="project_uuid" id="projectUuid">

                        <div class="text-muted">
                            Selected assets: <span class="fw-semibold" id="modalSelectedCount">0</span>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="btnConfirmAssign">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
