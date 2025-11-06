@extends('layouts.app')

@push('head')
    <style>
        .card.card-fullscreen {
            position: fixed !important;
            inset: 0 !important;
            z-index: 1080 !important;
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }

        body.fs-lock {
            overflow: hidden;
        }

        th,
        td {
            text-align: center !important;
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
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Validation failed',
                html: `{!! implode('<br>', $errors->all()) !!}`
            });
        </script>
    @endif
    <script>
        const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
        (function($) {
            const FS_BTN = '[data-card="fullscreen"]';

            $(document).on('click', FS_BTN, function(e) {
                e.preventDefault();

                const $btn = $(this);
                const $card = $btn.closest('.card')[0];

                if (!$card) return;

                const canFullscreen = !!($card.requestFullscreen || $card.webkitRequestFullscreen || $card
                    .msRequestFullscreen);

                const fallbackEnter = function() {
                    $($card).addClass('card-fullscreen');
                    $('body').addClass('fs-lock');
                    $btn.attr('data-state', 'on');
                };

                const fallbackExit = function() {
                    $($card).removeClass('card-fullscreen');
                    $('body').removeClass('fs-lock');
                    $btn.attr('data-state', 'off');
                };

                const enterFS = async function() {
                    if (document.fullscreenElement || document.webkitFullscreenElement || document
                        .msFullscreenElement) return;
                    if ($card.requestFullscreen) await $card.requestFullscreen();
                    else if ($card.webkitRequestFullscreen) await $card.webkitRequestFullscreen();
                    else if ($card.msRequestFullscreen) await $card.msRequestFullscreen();
                    else fallbackEnter();
                };

                const exitFS = async function() {
                    if (document.exitFullscreen) await document.exitFullscreen();
                    else if (document.webkitExitFullscreen) await document.webkitExitFullscreen();
                    else if (document.msExitFullscreen) await document.msExitFullscreen();
                    else fallbackExit();
                };

                const isNativeOn =
                    document.fullscreenElement === $card ||
                    document.webkitFullscreenElement === $card ||
                    document.msFullscreenElement === $card;

                const isFallbackOn = $($card).hasClass('card-fullscreen');

                if (canFullscreen) {
                    (isNativeOn ? exitFS() : enterFS()).catch(fallbackEnter);
                } else {
                    isFallbackOn ? fallbackExit() : fallbackEnter();
                }
            });

            const syncButtons = function() {
                const active = document.fullscreenElement || document.webkitFullscreenElement || document
                    .msFullscreenElement;
                $(FS_BTN).each(function() {
                    const $btn = $(this);
                    const card = $btn.closest('.card')[0];
                    const inThis = !!(active && card === active);
                    $btn.attr('data-state', inThis ? 'on' : 'off');
                });
            };

            $(document).on('fullscreenchange webkitfullscreenchange MSFullscreenChange', syncButtons);

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    const $fs = $('.card.card-fullscreen');
                    if ($fs.length) {
                        $fs.removeClass('card-fullscreen');
                        $('body').removeClass('fs-lock');
                    }
                }
            });
        })(jQuery);

        const table = $('#assetsTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: '{{ route('assets.datatable') }}',
            scrollX: true,
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
            order: [
                [24, 'desc']
            ],
            columns: [{
                    data: 'asset_code',
                    name: 'a.asset_code',
                    "render": function(data, type, row, meta) {
                        if (type !== 'display') return data;

                        const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row.uuid));
                        return `<a href="${url}" class="text-primary fw-semibold">${data ?? ''}</a>`;
                    }
                },
                // {
                //     data: 'kode_group_category',
                //     name: 'a.kode_group_category'
                // },
                {
                    data: 'kode_asset_class_label',
                    name: 'a.kode_asset_class'
                },
                {
                    data: 'asset_number_parent',
                    name: 'a.asset_number_parent'
                },
                {
                    data: 'asset_number_child',
                    name: 'a.asset_number_child'
                },
                {
                    data: 'description',
                    name: 'a.description'
                },
                {
                    data: 'asset_number_maximo',
                    name: 'i.asset_number_maximo'
                },
                {
                    data: 'asset_number_dynamic_365',
                    name: 'i.asset_number_dynamic_365'
                },
                {
                    data: 'asset_number_internal',
                    name: 'i.asset_number_internal'
                },
                {
                    data: 'alias',
                    name: 'i.alias'
                },
                {
                    data: 'asset_owner_label',
                    name: 'g.asset_owner'
                },
                {
                    data: 'asset_user_label',
                    name: 'g.asset_user'
                },
                {
                    data: 'asset_maintenance_label',
                    name: 'g.asset_maintenance'
                },
                {
                    data: 'kode_location_label',
                    name: 'a.kode_location'
                },
                {
                    data: 'kode_status_label',
                    name: 'a.kode_status'
                },
                {
                    data: 'price',
                    name: 'v.price',
                    render: d => d != null ? Number(d).toLocaleString() : ''
                },
                {
                    data: 'quantity',
                    name: 'v.quantity'
                },
                {
                    data: 'vat_in',
                    name: 'v.vat_in',
                    render: d => d != null ? Number(d).toLocaleString() : ''
                },
                {
                    data: 'kode_uom',
                    name: 'v.kode_uom'
                },
                {
                    data: 'total',
                    name: 'v.total',
                    render: d => d != null ? Number(d).toLocaleString() : ''
                },
                {
                    data: 'useful_life_month',
                    name: 'v.useful_life_month'
                },
                {
                    data: 'useful_life_year',
                    name: 'v.useful_life_year'
                },

                {
                    data: 'no_po_perjanjian_spk',
                    name: 'd.no_po_perjanjian_spk'
                },
                {
                    data: 'nota_referensi',
                    name: 'd.nota_referensi'
                },
                // {
                //     data: 'no_document',
                //     name: 'd.no_document'
                // },
                // {
                //     data: 'kode_sumber_label'
                // },

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

                //             {
                //                 title: 'Actions',
                //                 data: null,
                //                 orderable: false,
                //                 searchable: false,
                //                 render: d => `
            //   <div class="btn-group">
            //     <button class="btn btn-sm btn-light-primary" onclick="editAsset('${d.uuid}')">Edit</button>
            //     <button class="btn btn-sm btn-light-danger"  onclick="delAsset('${d.uuid}')">Delete</button>
            //   </div>`
                //             }
            ]
        });

        async function editAsset(uuid) {
            const res = await fetch(`/assets/${uuid}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
        }

        async function delAsset(uuid) {
            if (!confirm('Move this asset to trash?')) return;
            const res = await fetch(`/assets/${uuid}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            if (res.ok) table.ajax.reload(null, false);
        }
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
                    Assets
                </h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <!--begin::Item-->
                    <li class="breadcrumb-item text-muted">
                        Assets
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
                                    <span class="card-label fw-bold fs-3 mb-1">Assets Data</span>
                                </h3>
                                <div class="card-toolbar">
                                    <a href="{{ route('assets.create') }}" class="btn btn-sm btn-danger">
                                        <i class="ki-duotone ki-plus fs-2"></i>Add New
                                    </a>
                                    &nbsp;
                                    <button class="btn btn-sm btn-danger" data-card="fullscreen" title="Fullscreen">
                                        <i class="ki-duotone ki-exit-right-corner fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i> Fullscreen
                                    </button>
                                </div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Body-->
                            <div class="card-body py-3">
                                <table id="assetsTable"
                                    class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded ">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="min-w-200px">Code (Parent + Child)</th>
                                            {{-- <th class="min-w-100px">Group Category</th> --}}
                                            <th class="min-w-150px">Asset Class</th>
                                            <th class="min-w-150px">Parent</th>
                                            <th>Child</th>
                                            <th class="min-w-250px">Description</th>
                                            <th class="min-w-150px">Asset Number Maximo</th>
                                            <th class="min-w-150px">Asset Number D365</th>
                                            <th class="min-w-150px">Asset Number Internal</th>
                                            <th class="min-w-200px">Alias</th>
                                            <th class="min-w-150px">Asset Owner</th>
                                            <th class="min-w-150px">Asset User</th>
                                            <th class="min-w-150px">Asset Maintenance</th>
                                            <th class="min-w-150px">Location</th>
                                            <th class="min-w-150px">Status</th>
                                            <th class="min-w-100px">Price</th>
                                            <th>Quantity</th>
                                            <th class="min-w-150px">VAT In</th>
                                            <th>UOM</th>
                                            <th class="min-w-150px">Total</th>
                                            <th>Useful Life (Month)</th>
                                            <th>Useful Life (Year)</th>
                                            <th class="min-w-200px">No PO/Perjanjian/SPK</th>
                                            <th class="min-w-200px">Note Reference</th>
                                            {{-- <th class="min-w-200px">No Document</th> --}}
                                            {{-- <th class="min-w-120px">Sumber</th> --}}
                                            <th class="min-w-150px">Updated</th>
                                            {{-- <th class="text-center min-w-120px">Actions</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td></td>
                                            {{-- <td></td> --}}
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            {{-- <td></td> --}}
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
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
@endsection
