@extends('layouts.app')

@push('scripts')
    @push('scripts')
    @endpush

    <script>
        const SHOW_PROJECT_URL_TPL = @json(route('stockopname.asset_projects.show', '__UUID__'));

        const table = $('#projectsTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: "{{ route('stockopname.asset_projects.datatable') }}"
            },
            order: [
                [5, 'desc']
            ],
            columns:[{
                    data: 'name',
                    name: 'p.name',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        const url = SHOW_PROJECT_URL_TPL.replace('__UUID__', row.uuid);
                        return `<a class="text-primary fw-semibold" href="${url}">${data ?? ''}</a>`;
                    }
                },
                {
                    data: 'status',
                    name: 'p.status'
                },

                // {
                //     data: 'asset_count',
                //     name: 'asset_count',
                //     searchable: false
                // },

                {
                    data: null,
                    name: 'done_count',
                    searchable: false,
                    orderable: false,
                    render: function(_, type, row) {
                        const done = Number(row.done_count ?? 0);
                        const total = Number(row.asset_count ?? 0);
                        if (type !== 'display') return done;
                        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
                        return `<div class="fw-semibold">${done} / ${total} <span class="text-muted fs-8">(${pct}%)</span></div>`;
                    }
                },

                {
                    data: 'created_by_name',
                    name: 'created_by_name',
                    searchable: false
                },

                {
                    data: 'created_at',
                    name: 'p.created_at',
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
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });
        $(document).on('click', '.btn-close-project', function() {
            const uuid = $(this).data('uuid');

            Swal.fire({
                title: 'Close this project?',
                text: 'After closing, you cannot assign more assets to it.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, close',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#EA242A'
            }).then(async r => {
                if (!r.isConfirmed) return;

                const CLOSE_PROJECT_URL_TPL = @json(route('stockopname.asset_projects.close', '__UUID__'));
                const url = CLOSE_PROJECT_URL_TPL.replace('__UUID__', uuid);

                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Closed',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Could not close project.'
                    });
                }
            });
        });

        $(document).on('click', '.btn-re-open-project', function() {
            const uuid = $(this).data('uuid');

            Swal.fire({
                title: 'Re-open this project?',
                text: 'After re-opening, you can assign more assets to it.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, re-open',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#EA242A'
            }).then(async r => {
                if (!r.isConfirmed) return;

                const REOPEN_PROJECT_URL_TPL = @json(route('stockopname.asset_projects.reopen', '__UUID__'));
                const url = REOPEN_PROJECT_URL_TPL.replace('__UUID__', uuid);

                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Re-opened',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Could not re-open project.'
                    });
                }
            });
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
                    <li class="breadcrumb-item text-muted">List Projects</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title fw-bold">Projects List</h3>
                </div>
                <div class="card-body">
                    <table id="projectsTable"
                        class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                        <thead class="table-light">
                            <tr>
                                <th>Project Name</th>
                                <th>Status</th>
                                {{-- <th class="min-w-100px">Assets</th> --}}
                                <th class="min-w-140px">Checked / Total</th>
                                <th class="min-w-200px">Created By</th>
                                <th class="min-w-200px">Created At</th>
                                <th class="min-w-120px">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
