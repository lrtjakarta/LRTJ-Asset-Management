@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Trash
                </h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">
                        Trash
                    </li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->

            </div>
            <!--end::Page title-->
            {{-- <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="#" class="btn btn-sm fw-bold btn-danger" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_new_target">Add Sumber</a>
            </div> --}}

        </div>
        <!--end::Toolbar container-->
    </div>

    <div class="container-xxl" id="kt_content_container">
        <div class="card">
            <!--begin::Header-->
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">Trash Data</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Soft-deleted items from all modules</span>
                </h3>
            </div>
            <!--end::Header-->
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tableTrash" class="table table-row-dashed gy-5 align-middle">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Deleted</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const tbl = $('#tableTrash').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('trash.data') }}",
                order: [
                    [3, 'desc']
                ],
                columns: [{
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'label',
                        name: 'label'
                    },
                    {
                        data: 'deleted_at',
                        name: 'deleted_at'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ]
            });

            // Restore
            $(document).on('click', '.btn-restore', function() {
                const type = $(this).data('type');
                const id = $(this).data('id');

                $.post("{{ route('trash.restore', [':type', ':id']) }}".replace(':type', type).replace(':id',
                        id))
                    .done(function(res) {
                        tbl.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Restored',
                            text: res.message || 'Restored'
                        });
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops',
                            text: xhr.responseJSON?.message || 'Failed to restore'
                        });
                    });
            });

            // Force delete
            $(document).on('click', '.btn-force', function() {
                const type = $(this).data('type');
                const id = $(this).data('id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Permanently delete?',
                    text: 'This cannot be undone.',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Delete'
                }).then(function(r) {
                    if (!r.isConfirmed) return;
                    $.ajax({
                            url: "{{ route('trash.force', [':type', ':id']) }}".replace(':type',
                                type).replace(':id', id),
                            type: 'DELETE',
                            data: {
                                _token: document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .done(function(res) {
                            tbl.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: res.message || 'Deleted'
                            });
                        })
                        .fail(function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops',
                                text: xhr.responseJSON?.message || 'Failed to delete'
                            });
                        });
                });
            });
        })();
    </script>
@endpush
