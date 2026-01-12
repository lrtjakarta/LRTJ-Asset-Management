@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Dashboard
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Dashboard</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            {{-- Filters (YEARLY) --}}
            <div class="card mb-6">
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">Year</label>
                            @php
                                $year = (int) (request('year') ?: now()->year);
                            @endphp
                            <input id="f-year" type="number" class="form-control" min="2000" max="2100"
                                value="{{ $year }}">
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">Owner</label>
                            <select id="f-owner" class="form-select" data-init="{{ request('owner') ?? '' }}"></select>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">Location</label>
                            <select id="f-location" class="form-select"
                                data-init="{{ request('location') ?? '' }}"></select>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">Asset Class</label>
                            <select id="f-asset-class" class="form-select"
                                data-init="{{ request('asset_class') ?? '' }}"></select>
                        </div>

                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button id="btn-apply" class="btn btn-danger btn-sm">Apply</button>
                    <button id="btn-reset" class="btn btn-light-danger btn-sm">Reset</button>
                </div>
            </div>

            {{-- Summary Stats Row --}}
            <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                {{-- Movement Card --}}
                <div class="col-xl-4">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Movement</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">Asset movement requests</span>
                            </h3>
                            <div class="card-toolbar">
                                <i class="ki-outline ki-delivery fs-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex flex-column gap-7">
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="movement" data-status="APR">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-outline ki-time fs-2x text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Waiting for Approval</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Pending requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-primary" id="mov-waiting">0</div>
                                </div>

                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="movement" data-status="REJ">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-danger">
                                            <i class="ki-outline ki-cross-circle fs-2x text-danger"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Rejected</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Declined requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="mov-rejected">0</div>
                                </div>

                                <div class="d-flex align-items-center mt-2">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-success">
                                            <i class="ki-outline ki-chart-line fs-2x text-gray-600"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Accepted</span>
                                        <span class="text-muted fw-semibold d-block fs-7">All accepted requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="mov-total">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transfer Value Card --}}
                <div class="col-xl-4">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Transfer Value</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">Value transfer requests</span>
                            </h3>
                            <div class="card-toolbar">
                                <i class="ki-outline ki-arrow-right-left fs-2x text-success"></i>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex flex-column gap-7">
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="transfer-value" data-status="APR">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-outline ki-time fs-2x text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Waiting for Approval</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Pending requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-primary" id="trv-waiting">0</div>
                                </div>

                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="transfer-value" data-status="REJ">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-danger">
                                            <i class="ki-outline ki-cross-circle fs-2x text-danger"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Rejected</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Declined requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="trv-rejected">0</div>
                                </div>

                                <div class="d-flex align-items-center dash-link">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-success">
                                            <i class="ki-outline ki-chart-line fs-2x text-gray-600"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Accepted</span>
                                        <span class="text-muted fw-semibold d-block fs-7">All accepted requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="trv-total">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Disposal Card --}}
                <div class="col-xl-4">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Disposal</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">Asset disposal requests</span>
                            </h3>
                            <div class="card-toolbar">
                                <i class="ki-outline ki-trash fs-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="card-body pt-5">
                            <div class="d-flex flex-column gap-7">
                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="disposal" data-status="APR">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-primary">
                                            <i class="ki-outline ki-time fs-2x text-primary"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Waiting for Approval</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Pending requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-primary" id="dsp-waiting">0</div>
                                </div>

                                <div class="d-flex align-items-center dash-link" style="cursor:pointer"
                                    data-target="disposal" data-status="REJ">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-danger">
                                            <i class="ki-outline ki-cross-circle fs-2x text-danger"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Rejected</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Declined requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="dsp-rejected">0</div>
                                </div>

                                <div class="d-flex align-items-center dash-link">
                                    <div class="symbol symbol-50px me-5">
                                        <span class="symbol-label bg-light-success">
                                            <i class="ki-outline ki-chart-line fs-2x text-gray-600"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-800 fw-bold d-block fs-6">Accepted</span>
                                        <span class="text-muted fw-semibold d-block fs-7">All accepted requests</span>
                                    </div>
                                    <div class="fs-1 fw-bolder text-danger" id="dsp-total">0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Acquisition overview --}}
            <div class="row g-5 g-xl-8 mb-5 mb-xl-8">

                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Asset Quantity</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total asset <br>count per year <br>(capitalization date)
                                </span>
                            </h3>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total All Asset</span>
                                    <span id="acq-total-qty" class="fs-2 fw-bolder text-gray-900">0</span>
                                </div>
                            </div>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total Selected Year</span>
                                    <span id="acq-total-qty-year" class="fs-2 fw-bolder text-gray-900">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-3 pb-7">
                            <div id="chart-acq-qty" style="width: 100%; height: 320px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Acquisition Value</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total acquisition <br>value per year <br>(capitalization date)
                                </span>
                            </h3>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total All Acquisition</span>
                                    <span id="acq-total-amount" class="fs-2 fw-bolder text-success">Rp0</span>
                                </div>
                            </div>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total Selected Year</span>
                                    <span id="acq-total-amount-year" class="fs-2 fw-bolder text-success">Rp0</span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-3 pb-7">
                            <div id="chart-acq-amount" style="width: 100%; height: 360px;"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Depreciation & NBV overview --}}
            <div class="row g-5 g-xl-8 mb-5 mb-xl-8">

                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Accumulated Depreciation</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total depreciation <br> per year (snapshot end of year)
                                </span>
                            </h3>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total Depreciation <br>(All)</span>
                                    <span id="depr-total-amount-all" class="fs-2 fw-bolder text-danger">Rp0</span>
                                </div>
                            </div>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total Depreciation <br>(Selected Year)</span>
                                    <span id="depr-total-amount" class="fs-2 fw-bolder text-danger">Rp0</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-3 pb-7">
                            <div id="chart-depr-total" style="width: 100%; height: 340px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Asset Net Book Value</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total NBV <br>per year (snapshot end of year)
                                </span>
                            </h3>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total NBV <br>(All)</span>
                                    <span id="depr-total-nbv-all" class="fs-2 fw-bolder text-success">Rp0</span>
                                </div>
                            </div>

                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total NBV <br>(Selected Year)</span>
                                    <span id="depr-total-nbv" class="fs-2 fw-bolder text-success">Rp0</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-3 pb-7">
                            <div id="chart-depr-nbv" style="width: 100%; height: 340px;"></div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Owner per assets --}}
            <div class="row g-5 g-xl-8 mb-5 mb-xl-8">
                <div class="col-xl-12">
                    <div class="card card-flush h-xl-100 shadow-sm mb-7">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Assets Status per Owner</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">DIS, IDL, OPE, RPR per Owner</span>
                            </h3>
                        </div>
                        <div class="card-body pt-3 pb-7">
                            <div id="chart-owner-status" style="width:100%;height:420px;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const R = {
                data: "{{ route('dashboard.data.yearly') }}",
                movementIndex: "{{ route('transaction.transfer.index') }}",
                transferValueIndex: "{{ route('depreciation.transfer-requests.index') }}",
                disposalIndex: "{{ route('transaction.disposal.index') }}",
                acqYearly: "{{ route('dashboard.acquisition.yearly') }}", // now returns YEARLY
                deprYearly: "{{ route('dashboard.depr.yearly') }}", // now returns YEARLY
                ownerStatus: "{{ route('dashboard.owner.status') }}",
                usercodeOptions: "{{ route('master.user_code.options') }}",
                locationOptions: "{{ route('master.location.options') }}",
                assetClassOptions: "{{ route('master.asset_class.options') }}",
            };

            // —— URL filters (NO from/to)
            function getFilters() {
                const p = new URLSearchParams(window.location.search);
                const pick = k => (p.get(k) ?? '').trim();

                const f = {
                    year: pick('year'),
                    owner: pick('owner'),
                    location: pick('location'),
                    asset_class: pick('asset_class'),
                };

                Object.keys(f).forEach(k => {
                    if (f[k] === '') delete f[k];
                });
                return f;
            }
            const F = getFilters();

            // —— Select2 helpers
            function initAjaxSelect($el, url, placeholder = 'All') {
                $el.select2({
                    placeholder,
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url,
                        dataType: 'json',
                        delay: 150,
                        data: params => ({
                            q: params.term || '',
                            page: params.page || 1
                        }),
                        processResults: d => d
                    }
                });
            }

            function preloadSelectValue($el, id, text = null) {
                if (!id) return;
                const opt = new Option(text ?? id, id, true, true);
                $el.append(opt).trigger('change');
            }

            // —— Init filter selects
            initAjaxSelect($('#f-owner'), R.usercodeOptions, 'All Owner');
            initAjaxSelect($('#f-location'), R.locationOptions, 'All Location');
            initAjaxSelect($('#f-asset-class'), R.assetClassOptions, 'All Asset Class');

            preloadSelectValue($('#f-owner'), F.owner || '');
            preloadSelectValue($('#f-location'), F.location || '');
            preloadSelectValue($('#f-asset-class'), F.asset_class || '');

            // —— Apply & Reset (NO from/to)
            $('#btn-apply').on('click', function() {
                const params = new URLSearchParams();

                const year = $('#f-year').val();
                const owner = $('#f-owner').val();
                const loc = $('#f-location').val();
                const cls = $('#f-asset-class').val();

                if (year) params.set('year', year);
                if (owner) params.set('owner', owner);
                if (loc) params.set('location', loc);
                if (cls) params.set('asset_class', cls);

                const base = "{{ route('dashboard.yearly') }}";
                const url = params.toString() ? `${base}?${params.toString()}` : base;
                window.location.href = url;
            });

            $('#btn-reset').on('click', function() {
                window.location.href = "{{ route('dashboard.yearly') }}";
            });

            // —— Top cards (filtered by YEAR)
            $.getJSON(R.data, F).done(function(res) {
                const mv = res.movement || {};
                const trv = res.transfer_value || {};
                const dsp = res.disposal || {};

                $('#mov-waiting').text(mv.waiting ?? 0);
                $('#mov-rejected').text(mv.rejected ?? 0);
                $('#trv-waiting').text(trv.waiting ?? 0);
                $('#trv-rejected').text(trv.rejected ?? 0);
                $('#dsp-waiting').text(dsp.waiting ?? 0);
                $('#dsp-rejected').text(dsp.rejected ?? 0);

                $('#mov-total').text(mv.total ?? 0);
                $('#trv-total').text(trv.total ?? 0);
                $('#dsp-total').text(dsp.total ?? 0);
            });

            // —— Card links keep status but preserve current filters
            $(document).on('click', '.dash-link', function() {
                const target = $(this).data('target');
                const status = $(this).data('status');
                let base = '';
                if (target === 'movement') base = R.movementIndex;
                if (target === 'transfer-value') base = R.transferValueIndex;
                if (target === 'disposal') base = R.disposalIndex;
                if (!base) return;

                const p = new URLSearchParams(window.location.search);
                p.set('status', status);
                window.location.href = `${base}?${p.toString()}`;
            });

            // —— Format
            function formatMoneyIDR(v) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(Number(v || 0));
            }

            // —— Acquisition charts (YEARLY)
            $.getJSON(R.acqYearly, F).done(function(res) {
                const rows = res.years || [];
                $('#acq-total-qty').text(res.total_qty ?? 0);
                $('#acq-total-qty-year').text(res.total_qty_year ?? 0);
                $('#acq-total-amount').text(formatMoneyIDR(res.total_amount ?? 0));
                $('#acq-total-amount-year').text(formatMoneyIDR(res.total_amount_year ?? 0));

                const data = rows.map(r => ({
                    year: String(r.year),
                    qty: Number(r.qty || 0),
                    amount: Number(r.amount || 0)
                }));

                renderQtyChart(data);
                renderAmountChart(data);
            });

            function renderQtyChart(data) {
                const root = am5.Root.new("chart-acq-qty");
                root.setThemes([am5themes_Animated.new(root)]);
                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    layout: root.verticalLayout
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    maxDeviation: 0.3,
                    categoryField: "year",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));
                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: "Quantity",
                    xAxis,
                    yAxis,
                    valueYField: "qty",
                    categoryXField: "year",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{categoryX}: {valueY}"
                    })
                }));

                xAxis.data.setAll(data);
                series.data.setAll(data);

                series.columns.template.setAll({
                    cornerRadiusTL: 4,
                    cornerRadiusTR: 4
                });
                chart.set("cursor", am5xy.XYCursor.new(root, {}));
                series.appear(800);
                chart.appear(800, 80);

                am5plugins_exporting.Exporting.new(root, {
                    menu: am5plugins_exporting.ExportingMenu.new(root, {}),
                    dataSource: data
                });
            }

            function renderAmountChart(data) {
                const root = am5.Root.new("chart-acq-amount");
                root.setThemes([am5themes_Animated.new(root)]);
                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    layout: root.verticalLayout
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "year",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));
                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.LineSeries.new(root, {
                    name: "Acquisition Value",
                    xAxis,
                    yAxis,
                    valueYField: "amount",
                    categoryXField: "year",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{categoryX}: {valueY.formatNumber('#,###.')}"
                    })
                }));

                series.strokes.template.setAll({
                    strokeWidth: 2
                });
                series.bullets.push(() => am5.Bullet.new(root, {
                    sprite: am5.Circle.new(root, {
                        radius: 4
                    })
                }));

                xAxis.data.setAll(data);
                series.data.setAll(data);

                chart.set("cursor", am5xy.XYCursor.new(root, {}));
                series.appear(800);
                chart.appear(800, 80);

                am5plugins_exporting.Exporting.new(root, {
                    menu: am5plugins_exporting.ExportingMenu.new(root, {}),
                    dataSource: data
                });
            }

            // —— Depreciation & NBV charts (YEARLY)
            $.getJSON(R.deprYearly, F).done(function(res) {
                const rows = res.years || [];

                $('#depr-total-amount-all').text(formatMoneyIDR(res.total_depr_all ?? 0));
                $('#depr-total-nbv-all').text(formatMoneyIDR(res.total_nbv_all ?? 0));
                $('#depr-total-amount').text(formatMoneyIDR(res.total_depr_year ?? 0));
                $('#depr-total-nbv').text(formatMoneyIDR(res.total_nbv_year ?? 0));

                const dataDepr = rows.map(r => ({
                    year: String(r.year),
                    depr: Number(r.depr || 0)
                }));
                const dataNbv = rows.map(r => ({
                    year: String(r.year),
                    nbv: Number(r.nbv || 0)
                }));

                renderDeprChart(dataDepr);
                renderNbvChart(dataNbv);
            });

            function renderDeprChart(data) {
                const root = am5.Root.new("chart-depr-total");
                root.setThemes([am5themes_Animated.new(root)]);
                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    layout: root.verticalLayout
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "year",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));
                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.LineSeries.new(root, {
                    name: "Depreciation",
                    xAxis,
                    yAxis,
                    valueYField: "depr",
                    categoryXField: "year",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{categoryX}: {valueY.formatNumber('#,###.')}"
                    })
                }));

                series.strokes.template.setAll({
                    strokeWidth: 2
                });
                series.bullets.push(() => am5.Bullet.new(root, {
                    sprite: am5.Circle.new(root, {
                        radius: 4
                    })
                }));

                xAxis.data.setAll(data);
                series.data.setAll(data);

                chart.set("cursor", am5xy.XYCursor.new(root, {}));
                series.appear(800);
                chart.appear(800, 80);

                am5plugins_exporting.Exporting.new(root, {
                    menu: am5plugins_exporting.ExportingMenu.new(root, {}),
                    dataSource: data
                });
            }

            function renderNbvChart(data) {
                const root = am5.Root.new("chart-depr-nbv");
                root.setThemes([am5themes_Animated.new(root)]);
                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    layout: root.verticalLayout
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "year",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));
                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.LineSeries.new(root, {
                    name: "Net Book Value",
                    xAxis,
                    yAxis,
                    valueYField: "nbv",
                    categoryXField: "year",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{categoryX}: {valueY.formatNumber('#,###.')}"
                    })
                }));

                series.strokes.template.setAll({
                    strokeWidth: 2
                });
                series.bullets.push(() => am5.Bullet.new(root, {
                    sprite: am5.Circle.new(root, {
                        radius: 4
                    })
                }));

                xAxis.data.setAll(data);
                series.data.setAll(data);

                chart.set("cursor", am5xy.XYCursor.new(root, {}));
                series.appear(800);
                chart.appear(800, 80);

                am5plugins_exporting.Exporting.new(root, {
                    menu: am5plugins_exporting.ExportingMenu.new(root, {}),
                    dataSource: data
                });
            }

            // —— Owner × Status stacked (unchanged)
            $.getJSON(R.ownerStatus, F).done(res => {
                renderOwnerStatus(res.data || []);
            });

            function renderOwnerStatus(rows) {
                const root = am5.Root.new("chart-owner-status");
                root.setThemes([am5themes_Animated.new(root)]);
                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    layout: root.verticalLayout,
                    wheelX: "panX",
                    panX: true
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "owner",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 20,
                        cellStartLocation: 0.1,
                        cellEndLocation: 0.9
                    })
                }));
                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    min: 0,
                    strictMinMax: false,
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                xAxis.data.setAll(rows);

                const legend = chart.children.push(am5.Legend.new(root, {
                    useDefaultMarker: true,
                    centerX: am5.p50,
                    x: am5.p50
                }));
                const seriesList = [];

                const STATUS_COLORS = {
                    ope: am5.color(0x50CD89),
                    idl: am5.color(0xFFC700),
                    rpr: am5.color(0xF88645),
                    dis: am5.color(0xF1416C),
                };
                const STATUS_SERIES = [{
                        key: 'ope',
                        name: 'OPE - Operation'
                    },
                    {
                        key: 'idl',
                        name: 'IDL - Idle'
                    },
                    {
                        key: 'rpr',
                        name: 'RPR - Repair'
                    },
                    {
                        key: 'dis',
                        name: 'DIS - Disposal'
                    },
                ];

                STATUS_SERIES.forEach(def => {
                    const s = chart.series.push(am5xy.ColumnSeries.new(root, {
                        name: def.name,
                        stacked: true,
                        xAxis,
                        yAxis,
                        categoryXField: "owner",
                        valueYField: def.key,
                        tooltip: am5.Tooltip.new(root, {
                            labelText: "{name}\n{categoryX}: {valueY.formatNumber('#,###')}"
                        })
                    }));
                    s.columns.template.setAll({
                        cornerRadiusTL: 3,
                        cornerRadiusTR: 3,
                        fill: STATUS_COLORS[def.key],
                        stroke: STATUS_COLORS[def.key]
                    });
                    s.data.setAll(rows);
                    seriesList.push(s);
                });

                legend.data.setAll(seriesList);
                chart.set("cursor", am5xy.XYCursor.new(root, {}));

                am5plugins_exporting.Exporting.new(root, {
                    menu: am5plugins_exporting.ExportingMenu.new(root, {}),
                    dataSource: rows,
                    filePrefix: "owner_status_stacked"
                });

                seriesList.forEach(s => s.appear(800));
                chart.appear(800, 80);
            }
        })();
    </script>
@endpush
