@extends('layouts.app')

@push('scripts')
    <script>
        (function() {
            const table = $('#tableMasterCategory2').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('master.category_2.data') }}",
                order: [
                    [5, 'desc']
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
                        data: 'category_label',
                        name: 'kode_category'
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
            get_cat_extra = () => {
                const p = {
                    kode_asset_type: $('#kodeAssetType').val() || '',
                    q: ''
                };
                console.log('cat->extra', p);
                return p;
            }
            if ($.fn.select2) {
                $('#kodeCategory').select2({
                    dropdownParent: $('#kt_modal_add'),
                    placeholder: 'Choose Asset Type First',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('master.category.options') }}",
                        dataType: 'json',
                        delay: 150,
                        cache: false,
                        data: function(params) {
                            const base = {
                                q: params.term || '',
                                page: params.page || 1
                            };
                            if (typeof get_cat_extra === 'function')
                                return Object.assign(base, get_cat_extra());
                            if (get_cat_extra && typeof get_cat_extra === 'object') return Object.assign(
                                base, get_cat_extra);
                            return base;
                        },
                        processResults: data => data
                    }
                });
                $('#kodeAssetType').select2({
                    dropdownParent: $('#kt_modal_add'),
                    placeholder: 'Choose asset type',
                    allowClear: true,
                    ajax: {
                        url: "{{ route('master.asset_type.options') }}",
                        data: params => ({
                            q: params.term || ''
                        }),
                        processResults: data => data,
                        delay: 150
                    }
                });

            } else {
                fetch("{{ route('master.category.options') }}")
                    .then(r => r.json())
                    .then(data => {
                        const sel = document.getElementById('kodeCategory');
                        data.results.forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.id;
                            opt.textContent = o.text;
                            sel.appendChild(opt);
                        });
                    });
                fetch("{{ route('master.asset_type.options') }}")
                    .then(r => r.json())
                    .then(data => {
                        const sel = document.getElementById('kodeAssetType');
                        data.results.forEach(o => {
                            const opt = document.createElement('option');
                            opt.value = o.id;
                            opt.textContent = o.text;
                            sel.appendChild(opt);
                        });
                    });
            }

            $(document).on('click', '.btn-edit', function() {
                const uuid = $(this).data('uuid');

                $.get("{{ route('master.category_2.show', ':uuid') }}".replace(':uuid', uuid))
                    .done(function(res) {
                        if (!res?.ok) return Swal.fire({
                            icon: 'error',
                            title: 'Oops',
                            text: 'Failed to load'
                        });
                        const d = res.data;
                        const $f = $('#formMasterCategory2');
                        $f.find('[name="uuid"]').val(d.uuid);
                        $f.find('[name="kode"]').val(d.kode);
                        $f.find('[name="name"]').val(d.name);
                        $f.find('[name="status"]').val(d.status).change?.
                            (); // set select (select2 or plain)
                        if ($.fn.select2) {
                            const opt = new Option(d.kode_category, d.kode_category, true, true);
                            $('#kodeCategory').append(opt).trigger('change');
                            const opt2 = new Option(d.kode_asset_type, d.kode_asset_type, true, true);
                            $('#kodeAssetType').append(opt2);

                            $('#kodeAssetType').on('change', function() {
                                $('#kodeCategory').val(null).trigger('change');
                            });
                        } else {
                            $('#kodeCategory').val(d.kode_category);
                            $('#kodeAssetType').val(d.kode_asset_type);
                        }
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

            $('#formMasterCategory2').on('submit', function(e) {
                e.preventDefault();
                const $f = $(this);
                const payload = $f.serialize();

                $.post("{{ route('master.category_2.save') }}", payload)
                    .done(function(res) {
                        $('#kt_modal_add').modal('hide');
                        table.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: res.message || 'Saved.'
                        });
                        $('#formMasterCategory2')[0].reset(); 
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
                        url: "{{ route('master.category_2.delete', ':uuid') }}".replace(':uuid',
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
                            text: res.message || 'Category 2 deleted.'
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
                    Master Category 2
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
                        Master Category 2
                    </li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->

            </div>

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
                                    <span class="card-label fw-bold fs-3 mb-1">Master Category 2 Data</span>
                                </h3>
                                <div class="card-toolbar">
                                    <button data-bs-toggle="modal" data-bs-target="#kt_modal_add"
                                        class="btn btn-sm btn-danger">
                                        <i class="ki-duotone ki-plus fs-2"></i>Add New</button>
                                </div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Body-->
                            <div class="card-body py-3">
                                <!--begin::Table container-->
                                <table id="tableMasterCategory2"
                                    class="table table-striped table-row-bordered gy-5 gs-7 border rounded ">
                                    <thead class="table-light">
                                        <tr>
                                            <th>UUID</th>
                                            <th>Kode</th>
                                            <th>Name</th>
                                            <th>Category</th>
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

                <form id="formMasterCategory2">
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
                            <label class="form-label required">Asset Type</label>
                            <select name="kode_asset_type" id="kodeAssetType" class="form-select" required></select>
                        </div>
                        <div class="mb-5">
                            <label class="form-label required">Category</label>
                            <select name="kode_category" id="kodeCategory" class="form-select" required></select>
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
