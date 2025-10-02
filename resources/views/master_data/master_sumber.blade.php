@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Master Sumber
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
                        Master Sumber
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
                                    <span class="card-label fw-bold fs-3 mb-1">Master Sumber Data</span>
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
                                <table id="table_sumber"
                                    class="table table-striped table-row-bordered gy-5 gs-7 border rounded ">
                                    <thead class="table-light">
                                        <tr class="fw-bold fs-6 text-gray-800 px-7">
                                            <th>NO</th>
                                            <th>ID</th>
                                            <th>NAME</th>
                                            <th>LAST UPDATE</th>
                                            <th>ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody >
                                        <tr>
                                            <td>1</td>
                                            <td>UUID_0001</td>
                                            <td>MAXIMO</td>
                                            <td>2025-10-02 10:18:00</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
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

                <div class="modal-body">
                    <form>
                        <!--begin::Input group-->
                        <div class="fv-row mb-8">
                            <label class="form-label fs-6 fw-bold text-dark">Name : </label>
                            <input type="text" placeholder="Name" name="name" autocomplete="off"
                                class="form-control bg-transparent" />
                        </div>
                        <!--end::Input group-->
                    </form>
                </div>

                <div class="modal-footer">
                    <a type="button" class="btn btn-light" data-bs-dismiss="modal">Close</a>
                    <button type="button" class="btn btn-danger tombol-confirm">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $("#table_sumber").DataTable({
            "language": {
                "lengthMenu": "Show _MENU_",
            },
            "dom": "<'row mb-2'" +
                "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
                ">" +

                "<'table-responsive'tr>" +

                "<'row'" +
                "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
                ">"
        });
    </script>
@endpush
