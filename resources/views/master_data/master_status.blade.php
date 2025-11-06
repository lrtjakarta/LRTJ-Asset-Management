@extends('layouts.app')

@push('scripts')
    <script>
        (function() {
            const table = $('#tableMasterStatus').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('master.status.data') }}",
                order: [
                    [4, 'desc']
                ],
                "dom": "<'row mb-2'" +
                    "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
                    ">" +

                    "<'table-responsive'tr>" +

                    "<'row'" +
                    "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
                    ">",
                searching: true,
                columns: [{
                        data: 'uuid',
                        name: 'uuid',
                        visible: false
                    },
                    {
                        data: 'kode',
                        name: 'kode'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        orderable: false,
                        searchable: false
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
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                ]
            });

            $(document).on('click', '#btn-export-excel', function() {
                window.location = "{{ route('export.master_status') }}";
            });

            $(document).on('click', '#btn-add', function() {
                const $f = $('#formMasterStatus');
                $('#formMasterStatus')[0].reset();
                $f.find('[name="uuid"]').val(null);
                $f.find('[name="type"]').val('Asset').trigger('change');
            });

            $(document).on('click', '.btn-edit', function() {
                        $('#formMasterStatus')[0].reset(); 
                const uuid = $(this).data('uuid');
                $.get("{{ route('master.status.show', ':uuid') }}".replace(':uuid', uuid))
                    .done(function(res) {
                        if (!res?.ok) return Swal.fire({
                            icon: 'error',
                            title: 'Oops',
                            text: 'Failed to load'
                        });
                        const d = res.data;
                        const $f = $('#formMasterStatus');
                        $f.find('[name="uuid"]').val(d.uuid);
                        $f.find('[name="kode"]').val(d.kode);
                        $f.find('[name="name"]').val(d.name);
                        $f.find('[name="type"]').val(d.type);
                        $f.find('[name="status"]').val(d.status).change?.();
                        $('#kt_modal_add').modal('show');
                    })
                    .fail(function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops',
                            text: xhr.responseJSON?.message || 'Failed to load'
                        });
                    });
            });

            $('#formMasterStatus').on('submit', function(e) {
                e.preventDefault();
                const $f = $(this);
                const payload = $f.serialize();

                $.post("{{ route('master.status.save') }}", payload)
                    .done(function(res) {
                        $('#kt_modal_add').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: res.message || 'Saved.'
                        });
                        $('#formMasterStatus')[0].reset(); 
                        $f.find('[name="uuid"]').val(null);
                    })
                    .fail(function(xhr) {
                        let msg = xhr.responseJSON?.message || 'Failed to save';
                        const errs = xhr.responseJSON?.errors;
                        if (errs) {
                            const firstKey = Object.keys(errs)[0];
                            if (firstKey) msg = errs[firstKey][0] || msg;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops',
                            text: msg
                        });
                    });
            });

            $(document).on('click', '.btn-delete', function() {
                const uuid = $(this).data('uuid');
                Swal.fire({
                    icon: 'question',
                    title: 'Delete?',
                    text: 'This item will be moved to trash.',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes, delete'
                }).then(function(r) {
                    if (!r.isConfirmed) return;
                    $.ajax({
                        url: "{{ route('master.status.delete', ':uuid') }}".replace(':uuid',
                            uuid),
                        type: 'DELETE',
                        data: {
                            _token: document.querySelector('meta[name="csrf-token"]').content
                        }
                    }).done(function(res) {
                        table.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: res.message || 'Status deleted.'
                        });
                    }).fail(function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops',
                            text: xhr.responseJSON?.message || 'Delete failed'
                        });
                    });
                });
            });
        })();
    </script>
@endpush



@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Master Status
                </h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">
                        Master Data
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <!--end::Item-->
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">
                        Master Status
                    </li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->

            </div>
            <!--end::Page title-->

        </div>
        <!--end::Toolbar container-->
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-fluid">
            <!--begin::Row-->
            <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <div class="card mb-5 mb-xl-8">
                            <!--begin::Header-->
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">Master Status Data</span>
                                </h3>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-danger me-2" id="btn-export-excel">
                                        Export Excel
                                    </button>
                                    <button data-bs-toggle="modal" data-bs-target="#kt_modal_add"
                                        class="btn btn-sm btn-danger" id="btn-add">
                                        <i class="ki-duotone ki-plus fs-2"></i>Add New</button>
                                </div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Body-->
                            <div class="card-body py-3">
                                <!--begin::Table container-->
                                <table id="tableMasterStatus"
                                    class="table table-striped table-row-bordered gy-5 gs-7 border rounded ">
                                    <thead class="table-light">
                                        <tr>
                                            <th>UUID</th>
                                            <th>Kode</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Updated</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                                <!--end::Table container-->
                            </div>
                            <!--begin::Body-->
                        </div>
                    </div>
                </div>


            </div>
            <!--end::Row-->
        </div>
        <!--end::Content container-->
    </div>

    <div class="modal fade" tabindex="-1" id="kt_modal_add">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add New</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <form id="formMasterStatus">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="uuid">
                        <div class="mb-5">
                            <label class="form-label required">Kode</label>
                            <input type="text" name="kode" class="form-control" required maxlength="50"
                                placeholder="e.g. KD-1">
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control" required maxlength="191">
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Status</label>
                            <select name="type" data-control="select2" data-placeholder="Select Type"
                                class="form-select">
                                <option>Asset</option>
                                <option>Transfer</option>
                                <option>Disposal</option>
                                <option>Return</option>
                                <option>Acquisition</option>
                                <option>Stock Opname</option>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <div class="text-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Save</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
