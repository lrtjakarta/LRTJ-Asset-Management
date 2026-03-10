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
            background: #fff;

            /* Scroll INSIDE this card */
            overflow-y: auto !important;
            overflow-x: auto !important;
        }

        body.fs-lock {
            /* Lock background page when fullscreen card is open */
            overflow: hidden !important;
        }

        th,
        td {
            text-align: center !important;
        }
    </style>
@endpush

@section('title', 'Reporting')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Reporting
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Reporting</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

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
                                placeholder="e.g. A1101000002-00 / Laptop Dell">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Masuk From</label>
                            <input type="date" id="f-cap-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Masuk To</label>
                            <input type="date" id="f-cap-to" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Depreciation Date</label>
                            <input type="month" id="f-period" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="btn-apply-filter" class="btn btn-sm btn-danger me-2">
                        Apply Filter
                    </button>
                    <button id="btn-reset-filter" class="btn btn-sm btn-light-danger me-2">
                        Reset
                    </button>
                    <button id="btn-export-reporting" class="btn btn-sm btn-light-danger">
                        Export Excel
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="card mb-5 mb-xl-8">
                <div class="card-header align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Reporting Data</span>
                        </h3>
                    </div>
                    <div class="card-toolbar">
                        <button class="btn btn-sm btn-danger" data-card="fullscreen" title="Fullscreen">
                            <i class="ki-duotone ki-exit-right-corner fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i> Fullscreen
                        </button>
                    </div>
                </div>

                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table id="reportingTable"
                            class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                            <thead class="table-light">
                                <tr>
                                    <th class="min-w-150px">Asset Code</th>
                                    <th class="min-w-200px">Asset Description</th>
                                    <th class="min-w-150px">Asset Class</th>
                                    <th class="min-w-150px">Location</th>
                                    <th class="min-w-150px">Status</th>
                                    <th class="min-w-150px">Owner</th>
                                    <th class="min-w-150px">User</th>
                                    <th class="min-w-120px">Price</th>
                                    <th>Qty</th>
                                    <th class="min-w-120px">VAT In</th>
                                    <th>UOM</th>
                                    <th class="min-w-150px">Total</th>

                                    <th class="min-w-150px">Cap Date</th>
                                    <th class="min-w-150px">Depreciation Date</th>
                                    <th class="min-w-150px">Depreciation Code</th>
                                    <th class="min-w-150px">Opening Balance</th>
                                    <th class="min-w-150px">Additions</th>
                                    <th class="min-w-150px">Transfer In</th>
                                    <th class="min-w-150px">Transfer Out</th>
                                    <th class="min-w-150px">Disposals</th>
                                    <th class="min-w-150px">Adj. Value</th>
                                    <th class="min-w-150px">Adj. Depr</th>
                                    <th class="min-w-150px">Accumulated Depreciation</th>
                                    {{-- <th class="min-w-150px">Ending Balance</th> --}}
                                    <th class="min-w-150px">Net Book Value</th>
                                    <th class="min-w-200px">No PO/Perjanjian/SPK</th>
                                    <th class="min-w-200px">Note Reference</th>
                                    <th class="min-w-150px">Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="27"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function($) {
            // ===== PURE CSS FULLSCREEN (no native API) =====
            const FS_BTN = '[data-card="fullscreen"]';

            $(document).on('click', FS_BTN, function(e) {
                e.preventDefault();

                const $btn = $(this);
                const $card = $btn.closest('.card');
                if (!$card.length) return;

                const isOn = $card.hasClass('card-fullscreen');

                if (isOn) {
                    // Exit fullscreen
                    $card.removeClass('card-fullscreen');
                    $('body').removeClass('fs-lock');
                    $btn.attr('data-state', 'off');
                } else {
                    // Enter fullscreen
                    $card.addClass('card-fullscreen');
                    $('body').addClass('fs-lock');
                    $btn.attr('data-state', 'on');
                }
            });

            // ESC to exit fullscreen (CSS mode)
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    const $fs = $('.card.card-fullscreen');
                    if ($fs.length) {
                        $fs.removeClass('card-fullscreen');
                        $('body').removeClass('fs-lock');
                        $(FS_BTN).attr('data-state', 'off');
                    }
                }
            });

            // ===== Reporting logic =====
            const R = {
                datatable: "{{ route('reporting.asset_depr.datatable') }}",
                export: "{{ route('reporting.asset_depr.export') }}",
                assetClassOptions: "{{ route('master.asset_class.options') }}",
                transactionOptions: "{{ route('master.transaction.options') }}",
                locationOptions: "{{ route('master.location.options') }}",
                statusOptions: "{{ route('master.status.options') }}",
                sumberOptions: "{{ route('master.sumber.options') }}",
                userCodeOptions: "{{ route('master.user_code.options') }}",
            };

            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));

            function initMasterSelect2($el, url, placeholder) {
                $el.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: placeholder || '— All —',
                    ajax: {
                        url: url,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return data;
                        }
                    }
                });
            }

            // Init selects
            initMasterSelect2($('#flt-asset-class'), R.assetClassOptions, 'All Asset Class');
            initMasterSelect2($('#flt-transaction'), R.transactionOptions, 'All Company');
            initMasterSelect2($('#flt-location'), R.locationOptions, 'All Location');
            initMasterSelect2($('#flt-status'), R.statusOptions, 'All Status');
            initMasterSelect2($('#flt-sumber'), R.sumberOptions, 'All Sumber');
            initMasterSelect2($('#flt-owner'), R.userCodeOptions, 'All Owner');
            initMasterSelect2($('#flt-user'), R.userCodeOptions, 'All User');
            initMasterSelect2($('#flt-maintenance'), R.userCodeOptions, 'All Maintenance');

            function buildFilters() {
                const periodMonth = $('#f-period').val();
                const period = periodMonth ? (periodMonth + '-01') : '';

                return {
                    asset_class: $('#flt-asset-class').val() || '',
                    transaction: $('#flt-transaction').val() || '',
                    location: $('#flt-location').val() || '',
                    status: $('#flt-status').val() || '',
                    owner: $('#flt-owner').val() || '',
                    user: $('#flt-user').val() || '',
                    maintenance: $('#flt-maintenance').val() || '',
                    sumber: $('#flt-sumber').val() || '',
                    asset_q: $('#flt-asset-q').val() || '',
                    cap_from: $('#f-cap-from').val() || '',
                    cap_to: $('#f-cap-to').val() || '',
                    period: period,
                };
            }

            function formatNumber(d) {
                if (d == null || d === '') return '';
                return Number(d).toLocaleString('en-US');
            }

            const table = $('#reportingTable').DataTable({
                serverSide: true,
                processing: true,
                scrollX: true,
                searchDelay: 400,
                dom: "<'row mb-2'" +
                    "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
                    ">" +
                    "<'table-responsive'tr>" +
                    "<'row'" +
                    "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
                    ">",
                order: [
                    [0, 'asc']
                ],
                ajax: {
                    url: R.datatable,
                    type: 'POST',
                    data: function(d) {
                        d._token = '{{ csrf_token() }}';
                        return Object.assign(d, buildFilters());
                    }
                },
                columns: [{
                        data: 'asset_code',
                        name: 'a.asset_code',
                        defaultContent: '',
                        render: function(data, type, row) {
                            if (type !== 'display') return data;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row.uuid));
                            return `<a href="${url}" class="text-primary fw-semibold">${data ?? ''}</a>`;
                        }
                    },
                    {
                        data: 'description',
                        name: 'a.description',
                        defaultContent: ''
                    },
                    {
                        data: 'kode_asset_class_label',
                        name: 'a.kode_asset_class',
                        defaultContent: ''
                    },
                    {
                        data: 'kode_location_label',
                        name: 'a.kode_location',
                        defaultContent: ''
                    },
                    {
                        data: 'kode_status_label',
                        name: 'a.kode_status',
                        defaultContent: ''
                    },
                    {
                        data: 'asset_owner_label',
                        name: 'g.asset_owner',
                        defaultContent: ''
                    },
                    {
                        data: 'asset_user_label',
                        name: 'g.asset_user',
                        defaultContent: ''
                    },

                    {
                        data: 'price',
                        name: 'v.price',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'quantity',
                        name: 'v.quantity',
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'vat_in',
                        name: 'v.vat_in',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'kode_uom_label',
                        name: 'v.kode_uom',
                        defaultContent: ''
                    },
                    {
                        data: 'total',
                        name: 'v.total',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'cap_date',
                        name: 'v.capitalization_date',
                        defaultContent: '',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;
                            const d = new Date(iso);
                            if (isNaN(d.getTime())) return iso;
                            return new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                day: '2-digit',
                                month: 'long',
                                year: 'numeric'
                            }).format(d);
                        }
                    },

                    {
                        data: 'period',
                        name: 'period',
                        defaultContent: '',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;
                            const d = new Date(iso);
                            return new Intl.DateTimeFormat('en-GB', {
                                timeZone: 'Asia/Jakarta',
                                month: 'long',
                                year: 'numeric'
                            }).format(d);
                        }
                    },
                    {
                        data: 'depr_code',
                        name: 'depr_code',
                        defaultContent: ''
                    },
                    {
                        data: 'opening_balance',
                        name: 'opening_balance',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'additions',
                        name: 'additions',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'transfers_in',
                        name: 'transfers_in',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'transfers_out',
                        name: 'transfers_out',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'disposals',
                        name: 'disposals',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'adjustment_value',
                        name: 'adjustment_value',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'adjustment_depreciation',
                        name: 'adjustment_depreciation',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    {
                        data: 'accumulated_depr_end',
                        name: 'accumulated_depr_end',
                        render: formatNumber,
                        className: 'text-end',
                        defaultContent: ''
                    },
                    // {
                    //     data: 'ending_balance',
                    //     name: 'l.ending_balance',
                    //     render: formatNumber,
                    //     className: 'text-end'
                    // },
                    {
                        data: 'last_net_book_value',
                        name: 'last_net_book_value',
                        render: formatNumber,
                        className: 'text-end',
                        searchable: false,
                        defaultContent: ''
                    },
                    {
                        data: 'no_po_perjanjian_spk',
                        name: 'd.no_po_perjanjian_spk',
                        defaultContent: ''
                    },
                    {
                        data: 'nota_referensi',
                        name: 'd.nota_referensi',
                        defaultContent: ''
                    }, {
                        data: 'updated_at',
                        name: 'depr_updated_at',
                        defaultContent: '',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;

                            const d = new Date(iso);
                            if (isNaN(d.getTime()))
                                return iso;

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
                ]
            });

            $('#btn-apply-filter').on('click', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            $('#btn-reset-filter').on('click', function(e) {
                e.preventDefault();
                $('.asset-filter').val(null).trigger('change');
                $('#flt-asset-q').val('');
                $('#f-cap-from').val('');
                $('#f-cap-to').val('');
                $('#f-period').val('');
                table.ajax.reload();
            });

            $('#btn-export-reporting').on('click', function(e) {
                e.preventDefault();
                const params = $.param(buildFilters());


                Swal.fire({
                    title: 'Export Excel?',
                    text: 'Export reporting data with current filters.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    window.location = R.export+(params ? ('?' + params) : '');
                });
            });

        })(jQuery);
    </script>
@endpush
