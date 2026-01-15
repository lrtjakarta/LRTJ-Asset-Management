@extends('layouts.app')
@push('scripts')
    <script>
        const PROJECT_UUID = @json($project->uuid);
        const SHOW_ASSET_URL_TPL = @json(route('assets.detail', '__UUID__'));
        const REMOVE_URL_TPL = @json(route('stockopname.asset_projects.assets.remove', ['project' => '__P__', 'assetUuid' => '__A__']));

        const table = $('#projectAssetsTable').DataTable({
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
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

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
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Could not remove asset.'
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
                        <div>
                            <h2 class="fw-bold mb-1">{{ $project->name }}</h2>
                            <div class="text-muted">Assigned Assets: <span class="fw-semibold">{{ $assetCount }}</span>
                            </div>
                        </div>
                        <a href="{{ route('stockopname.asset_projects.index') }}" class="btn btn-sm btn-light">Back</a>
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
                                <th class="min-w-220px">Assigned At</th>
                                <th class="min-w-120px">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
