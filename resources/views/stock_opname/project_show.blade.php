@extends('layouts.app')

@push('scripts')
    <script>
        const PROJECT_UUID = @json($project->uuid);
        const SHOW_ASSET_URL_TPL = @json(route('assets.detail', '__UUID__'));
        const REMOVE_URL_TPL = @json(route('stockopname.asset_projects.assets.remove', ['project' => '__P__', 'assetUuid' => '__A__']));
        let PROJECT_STATUS = @json($project->status); // IMPORTANT: must be let (dynamic)
        const DONE_URL_TPL = @json(route('stockopname.asset_projects.assets.stockopname_done', ['project' => '__P__', 'assetUuid' => '__A__']));
        const BULK_URL = @json(route('stockopname.asset_projects.assets.stockopname_bulk', $project->uuid));

        // urls used for re-rendering action buttons
        const EXPORT_URL = @json(route('stockopname.asset_projects.assets.export_excel', $project->uuid));
        const BACK_URL = @json(route('stockopname.asset_projects.index'));

        function getVisibleAssetUuids(table) {
            const rows = table.rows({
                page: 'current'
            }).data().toArray();
            return rows.map(r => r.uuid);
        }

        function syncHeaderCheckbox(table) {
            const $all = $('#cb-stockopname-all');

            if (PROJECT_STATUS === 'CLOSED') {
                $all.prop('checked', false).prop('indeterminate', false).prop('disabled', true);
                return;
            }

            const nodes = table.rows({
                page: 'current'
            }).nodes();
            const cbs = $(nodes).find('.cb-stockopname');

            if (cbs.length === 0) {
                $all.prop('checked', false).prop('indeterminate', false);
                return;
            }

            const checkedCount = cbs.filter(':checked').length;

            if (checkedCount === 0) {
                $all.prop('checked', false).prop('indeterminate', false);
            } else if (checkedCount === cbs.length) {
                $all.prop('checked', true).prop('indeterminate', false);
            } else {
                $all.prop('checked', false).prop('indeterminate', true);
            }
        }

        function renderActionButtons(status) {
            const uuid = PROJECT_UUID;

            const statusBtn = (status === 'CLOSED') ?
                `<button class="btn btn-sm btn-warning btn-re-open-project" data-uuid="${uuid}" id="btnReopenProject">
                    Re-open
                 </button>` :
                `<button class="btn btn-sm btn-danger btn-close-project" data-uuid="${uuid}" id="btnCloseProject">
                    Close
                 </button>`;

            $('#actionButtons').html(`
                ${statusBtn}
                <a href="${EXPORT_URL}" class="btn btn-sm btn-success me-2">Export Excel</a>
                <a href="${BACK_URL}" class="btn btn-sm btn-light me-2">Back</a>
            `);
        }

        function applyStatusToCheckboxes(status) {
            const closed = (status === 'CLOSED');

            // header checkbox
            $('#cb-stockopname-all')
                .prop('disabled', closed)
                .prop('checked', false)
                .prop('indeterminate', false);

            // visible row checkboxes
            if (window.table) {
                window.table.rows({
                        page: 'current'
                    }).nodes().to$()
                    .find('.cb-stockopname')
                    .prop('disabled', closed);
            }
        }

        function refreshProjectUI(newStatus) {
            PROJECT_STATUS = newStatus; // IMPORTANT
            renderActionButtons(newStatus);
            applyStatusToCheckboxes(newStatus);
            syncHeaderCheckbox(window.table);
        }

        // ===================== DATATABLE =====================
        window.table = $('#projectAssetsTable').DataTable({
            serverSide: true,
            processing: true,
            scrollX: true,
            ajax: {
                url: "{{ route('stockopname.asset_projects.assets.datatable', $project->uuid) }}"
            },
            order: [
                [6, 'desc']
            ], // assigned_at column index (0-based)
            columns: [{
                    data: 'asset_code',
                    name: 'a.asset_code',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        const url = SHOW_ASSET_URL_TPL.replace('__UUID__', encodeURIComponent(row.uuid));
                        return `<a class="text-primary fw-semibold" href="${url}">${data ?? ''}</a>`;
                    }
                },
                {
                    data: 'kode_asset_class_label',
                    name: 'kode_asset_class_label'
                },
                {
                    data: 'description',
                    name: 'a.description'
                },
                {
                    data: 'kode_location_label',
                    name: 'kode_location_label'
                },
                {
                    data: 'kode_status_label',
                    name: 'kode_status_label'
                },
                {
                    data: 'total',
                    name: 'v.total',
                    render: d => d != null ? Number(d).toLocaleString() : ''
                },
                {
                    data: 'assigned_at',
                    name: 'pa.created_at',
                    render: function(iso, type) {
                        if (!iso) return '';
                        if (type === 'sort' || type === 'type') return iso;
                        const d = new Date(iso);
                        return new Intl.DateTimeFormat('en-GB', {
                            timeZone: 'Asia/Jakarta',
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        }).format(d);
                    }
                },
                {
                    data: 'stock_opname_done',
                    name: 'pa.stock_opname_done',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function(v, type, row) {
                        if (type !== 'display') return v;
                        const checked = (Number(v) === 1 || v === true) ? 'checked' : '';
                        const disabled = (PROJECT_STATUS === 'CLOSED') ? 'disabled' : '';
                        return `
                            <div class="form-check form-check-sm form-check-custom form-check-solid d-inline-flex justify-content-center">
                                <input class="form-check-input cb-stockopname"
                                    type="checkbox"
                                    data-uuid="${row.uuid}"
                                    ${checked} ${disabled}/>
                            </div>
                        `;
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

        window.table.on('draw', function() {
            syncHeaderCheckbox(window.table);
            applyStatusToCheckboxes(PROJECT_STATUS);
        });

        // initial render (so action buttons + checkbox state reflect current status)
        $(document).ready(function() {
            renderActionButtons(PROJECT_STATUS);
            applyStatusToCheckboxes(PROJECT_STATUS);
        });

        // ===================== RE-OPEN =====================
        $(document).on('click', '#btnReopenProject', function(e) {
            const uuid = $(this).data('uuid');

            Swal.fire({
                icon: 'question',
                title: 'Re-open project?',
                text: 'Project will be opened again and stock opname checklist can be edited.',
                showCancelButton: true,
                confirmButtonText: 'Yes, re-open',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#EA242A',
            }).then(async r => {
                if (!r.isConfirmed) return;

                const REOPEN_PROJECT_URL_TPL = @json(route('stockopname.asset_projects.reopen', '__UUID__'));
                const url = REOPEN_PROJECT_URL_TPL.replace('__UUID__', encodeURIComponent(uuid));

                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    // prefer json status if available, fallback OPEN
                    let nextStatus = 'OPEN';
                    try {
                        const j = await res.json();
                        if (j?.status) nextStatus = j.status;
                    } catch (e) {}

                    Swal.fire({
                        icon: 'success',
                        title: 'Re-opened',
                        timer: 1200,
                        showConfirmButton: false
                    });

                    refreshProjectUI(nextStatus);
                    window.table.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Could not re-open project.'
                    });
                }
            });
        });

        // ===================== CLOSE =====================
        $(document).on('click', '#btnCloseProject', function(e) {
            const uuid = $(this).data('uuid');

            Swal.fire({
                icon: 'question',
                title: 'Close project?',
                text: 'Project will be closed and stock opname checklist can no longer be edited.',
                showCancelButton: true,
                confirmButtonText: 'Yes, close',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#EA242A',
            }).then(async r => {
                if (!r.isConfirmed) return;

                const CLOSE_PROJECT_URL_TPL = @json(route('stockopname.asset_projects.close', '__UUID__'));
                const url = CLOSE_PROJECT_URL_TPL.replace('__UUID__', encodeURIComponent(uuid));

                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    // prefer json status if available, fallback CLOSED
                    let nextStatus = 'CLOSED';
                    try {
                        const j = await res.json();
                        if (j?.status) nextStatus = j.status;
                    } catch (e) {}

                    Swal.fire({
                        icon: 'success',
                        title: 'Closed',
                        timer: 1200,
                        showConfirmButton: false
                    });

                    refreshProjectUI(nextStatus);
                    window.table.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Could not close project.'
                    });
                }
            });
        });

        // ===================== REMOVE ASSET =====================
        $(document).on('click', '.btn-remove-asset', function() {
            const assetUuid = $(this).data('uuid');

            Swal.fire({
                title: 'Remove asset from project?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#EA242A'
            }).then(async (r) => {
                if (!r.isConfirmed) return;

                const url = REMOVE_URL_TPL
                    .replace('__P__', encodeURIComponent(PROJECT_UUID))
                    .replace('__A__', encodeURIComponent(assetUuid));

                const res = await fetch(url, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    window.table.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Could not remove asset.'
                    });
                }
            });
        });

        // ===================== SINGLE CHECKBOX =====================
        $(document).on('change', '.cb-stockopname', async function() {
            const assetUuid = $(this).data('uuid');
            const done = this.checked;

            // UX: disable to prevent double click
            $(this).prop('disabled', true);

            const url = DONE_URL_TPL
                .replace('__P__', encodeURIComponent(PROJECT_UUID))
                .replace('__A__', encodeURIComponent(assetUuid));

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        done
                    })
                });

                if (!res.ok) {
                    // revert if failed
                    this.checked = !done;

                    let msg = 'Update failed';
                    try {
                        const j = await res.json();
                        msg = j.message || msg;
                    } catch (e) {}

                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: msg
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        timer: 900,
                        showConfirmButton: false
                    });
                    syncHeaderCheckbox(window.table);
                }
            } catch (e) {
                this.checked = !done;
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: 'Network error'
                });
            } finally {
                // enable again unless CLOSED
                $(this).prop('disabled', PROJECT_STATUS === 'CLOSED');
            }
        });

        // ===================== HEADER CHECKBOX (BULK CURRENT PAGE) =====================
        $(document).on('change', '#cb-stockopname-all', async function() {
            if (PROJECT_STATUS === 'CLOSED') return;

            const done = this.checked;
            const assetUuids = getVisibleAssetUuids(window.table);

            if (!assetUuids.length) {
                this.checked = !done;
                return;
            }

            const actionText = done ? 'Check ALL assets on this page?' : 'Uncheck ALL assets on this page?';

            const ok = await Swal.fire({
                icon: 'question',
                title: 'Confirm',
                text: actionText,
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
            });

            if (!ok.isConfirmed) {
                this.checked = !done;
                syncHeaderCheckbox(window.table);
                return;
            }

            // disable all checkboxes while processing
            $('#cb-stockopname-all').prop('disabled', true);
            window.table.rows({
                page: 'current'
            }).nodes().to$().find('.cb-stockopname').prop('disabled', true);

            try {
                const res = await fetch(BULK_URL, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        done,
                        asset_uuids: assetUuids
                    })
                });

                if (!res.ok) {
                    let msg = 'Bulk update failed';
                    try {
                        msg = (await res.json()).message || msg;
                    } catch (e) {}

                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: msg
                    });
                    syncHeaderCheckbox(window.table);
                    return;
                }

                // update current page UI without reload
                window.table.rows({
                    page: 'current'
                }).nodes().to$().find('.cb-stockopname').prop('checked', done);

                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    timer: 900,
                    showConfirmButton: false
                });

            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: 'Network error'
                });
                syncHeaderCheckbox(window.table);
            } finally {
                // enable back unless CLOSED
                $('#cb-stockopname-all').prop('disabled', PROJECT_STATUS === 'CLOSED');
                window.table.rows({
                        page: 'current'
                    }).nodes().to$().find('.cb-stockopname')
                    .prop('disabled', PROJECT_STATUS === 'CLOSED');

                syncHeaderCheckbox(window.table);
            }
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
                    <li class="breadcrumb-item text-muted">{{ $project->name }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card mb-5">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="row w-100">
                            <div class="col-md-9">
                                <h2 class="fw-bold mb-1">{{ $project->name }}</h2>
                                <div class="text-muted">
                                    Assigned Assets: <span class="fw-semibold">{{ $assetCount }}</span>
                                </div>
                            </div>

                            {{-- IMPORTANT: buttons will be rendered by JS so they can refresh after close/reopen --}}
                            <div class="col-md-3" id="actionButtons"></div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Assigned Assets</h3>
                </div>
                <div class="card-body">
                    <table id="projectAssetsTable"
                        class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                        <thead class="table-light">
                            <tr>
                                <th class="min-w-180px">Code</th>
                                <th class="min-w-150px">Asset Class</th>
                                <th class="min-w-250px">Description</th>
                                <th class="min-w-150px">Location</th>
                                <th class="min-w-150px">Status</th>
                                <th class="min-w-150px">Total</th>
                                <th class="min-w-250px">Assigned At</th>
                                <th class="min-w-200px text-center align-middle">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <span>Stock Opname</span>
                                        <div class="form-check form-check-sm form-check-custom form-check-solid mt-2">
                                            <input class="form-check-input" style="margin-left:-15%;" type="checkbox"
                                                id="cb-stockopname-all" />
                                        </div>
                                    </div>
                                </th>
                                <th class="min-w-200px">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
