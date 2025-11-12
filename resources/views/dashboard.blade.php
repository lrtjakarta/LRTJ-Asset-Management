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

                                {{-- Waiting --}}
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

                                {{-- Rejected --}}
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

                                {{-- Waiting --}}
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

                                {{-- Rejected --}}
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

                                {{-- Waiting --}}
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

                                {{-- Rejected --}}
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

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Acquisition overview --}}
            <div class="row g-5 g-xl-8 mb-5 mb-xl-8">

                {{-- Quantity (chart + total in header) --}}
                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Asset Quantity</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total asset count per month (capitalization date)
                                </span>
                            </h3>

                            {{-- Total box on the right, like depr section --}}
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total Asset</span>
                                    <span id="acq-total-qty" class="fs-2 fw-bolder text-gray-900">0</span>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-3 pb-7">
                            <div id="chart-acq-qty" style="width: 100%; height: 320px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Acquisition value (chart + total in header) --}}
                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Acquisition Value</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total acquisition value per month (capitalization date)
                                </span>
                            </h3>

                            {{-- Total box on the right, like NBV total --}}
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total Acquisition</span>
                                    <span id="acq-total-amount" class="fs-2 fw-bolder text-success">Rp0</span>
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

                {{-- Total Depreciation (card + chart) --}}
                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Accumulated Depreciation</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total depreciation per month
                                </span>
                            </h3>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total Depreciation</span>
                                    <span id="depr-total-amount" class="fs-2 fw-bolder text-danger">Rp0</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-3 pb-7">
                            <div id="chart-depr-total" style="width: 100%; height: 340px;"></div>
                        </div>
                    </div>
                </div>

                {{-- Total Net Book Value (card + chart) --}}
                <div class="col-xl-6">
                    <div class="card card-flush h-xl-100 shadow-sm">
                        <div class="card-header pt-7 pb-3 border-0">
                            <h3 class="card-title align-items-start flex-column">
                                <span class="card-label fw-bold text-gray-800 fs-3">Asset Net Book Value</span>
                                <span class="text-gray-500 mt-1 fw-semibold fs-7">
                                    Total NBV per month
                                </span>
                            </h3>
                            <div class="card-toolbar text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="text-gray-500 fw-semibold fs-8">Total NBV</span>
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


        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const R = {
                data: "{{ route('dashboard.data') }}",
                movementIndex: "{{ route('transaction.transfer.index') }}",
                transferValueIndex: "{{ route('depreciation.transfer-requests.index') }}",
                disposalIndex: "{{ route('transaction.disposal.index') }}",
                acqMonthly: "{{ route('dashboard.acquisition.monthly') }}",
                deprMonthly: "{{ route('dashboard.depr.monthly') }}",
            };

            $.getJSON(R.data)
                .done(function(res) {
                    const mv = res.movement || {};
                    const trv = res.transfer_value || {};
                    const dsp = res.disposal || {};

                    // Movement
                    $('#mov-waiting').text(mv.waiting ?? 0);
                    $('#mov-rejected').text(mv.rejected ?? 0);

                    // Transfer value
                    $('#trv-waiting').text(trv.waiting ?? 0);
                    $('#trv-rejected').text(trv.rejected ?? 0);

                    // Disposal
                    $('#dsp-waiting').text(dsp.waiting ?? 0);
                    $('#dsp-rejected').text(dsp.rejected ?? 0);
                })
                .fail(function(err) {
                    console.error('Failed to load dashboard data', err);
                });

            $(document).on('click', '.dash-link', function() {
                const target = $(this).data('target');
                const status = $(this).data('status');

                let base = '';
                if (target === 'movement') base = R.movementIndex;
                if (target === 'transfer-value') base = R.transferValueIndex;
                if (target === 'disposal') base = R.disposalIndex;

                if (!base || !status) return;

                window.location.href = base + '?status=' + encodeURIComponent(status);
            });

            // === Acquisition charts ===
            function formatMoneyIDR(v) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(Number(v || 0));
            }

            $.getJSON(R.acqMonthly)
                .done(function(res) {
                    const rows = res.months || [];

                    const totalQty = res.total_qty ?? rows.reduce((s, r) => s + Number(r.qty || 0), 0);
                    const totalAmount = res.total_amount ?? rows.reduce((s, r) => s + Number(r.amount || 0), 0);

                    $('#acq-total-qty').text(totalQty);
                    $('#acq-total-amount').text(formatMoneyIDR(totalAmount));

                    const data = rows.map(r => {
                        const d = new Date(r.month);
                        const monthLabel = d.toLocaleDateString('id-ID', {
                            month: 'short'
                        });
                        return {
                            month: monthLabel,
                            qty: Number(r.qty || 0),
                            amount: Number(r.amount || 0)
                        };
                    });

                    renderQtyChart(data);
                    renderAmountChart(data);
                })
                .fail(err => console.error('Failed to load acquisition monthly data', err));

            function renderQtyChart(data) {
                const root = am5.Root.new("chart-acq-qty");
                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    layout: root.verticalLayout
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    maxDeviation: 0.3,
                    categoryField: "month",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));

                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    name: "Quantity",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "qty",
                    categoryXField: "month",
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
                series.appear(1000);
                chart.appear(1000, 100);


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
                    categoryField: "month",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));

                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.LineSeries.new(root, {
                    name: "Acquisition Value",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "amount",
                    categoryXField: "month",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{categoryX}: {valueY.formatNumber('#,###.')}"
                    })
                }));

                series.strokes.template.setAll({
                    strokeWidth: 2
                });

                series.bullets.push(function() {
                    return am5.Bullet.new(root, {
                        sprite: am5.Circle.new(root, {
                            radius: 4
                        })
                    });
                });

                xAxis.data.setAll(data);
                series.data.setAll(data);

                chart.set("cursor", am5xy.XYCursor.new(root, {}));
                series.appear(1000);
                chart.appear(1000, 100);

                am5plugins_exporting.Exporting.new(root, {
                    menu: am5plugins_exporting.ExportingMenu.new(root, {}),
                    dataSource: data
                });
            }

            // === Depreciation & NBV charts ===
            $.getJSON(R.deprMonthly)
                .done(function(res) {
                    const rows = res.months || [];

                    const totalDepr = res.total_depr ??
                        rows.reduce((s, r) => s + Number(r.depr || 0), 0);

                    const latestNbv = res.total_nbv ??
                        (rows.length ? Number(rows[rows.length - 1].nbv || 0) : 0);

                    $('#depr-total-amount').text(formatMoneyIDR(totalDepr));
                    $('#depr-total-nbv').text(formatMoneyIDR(latestNbv));

                    const data = rows.map(r => {
                        const d = new Date(r.month);
                        const monthLabel = d.toLocaleDateString('id-ID', {
                            month: 'short'
                        });
                        return {
                            month: monthLabel,
                            depr: Number(r.depr || 0),
                            nbv: Number(r.nbv || 0)
                        };
                    });

                    renderDeprChart(data);
                    renderNbvChart(data);
                })
                .fail(err => console.error('Failed to load depreciation monthly data', err));

            function renderDeprChart(data) {
                const root = am5.Root.new("chart-depr-total");
                root.setThemes([am5themes_Animated.new(root)]);

                const chart = root.container.children.push(am5xy.XYChart.new(root, {
                    layout: root.verticalLayout
                }));

                const xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: "month",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));

                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.LineSeries.new(root, {
                    name: "Depreciation",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "depr",
                    categoryXField: "month",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{categoryX}: {valueY.formatNumber('#,###.')}"
                    })
                }));

                series.strokes.template.setAll({
                    strokeWidth: 2
                });

                series.bullets.push(function() {
                    return am5.Bullet.new(root, {
                        sprite: am5.Circle.new(root, {
                            radius: 4
                        })
                    });
                });

                xAxis.data.setAll(data);
                series.data.setAll(data);

                chart.set("cursor", am5xy.XYCursor.new(root, {}));
                series.appear(1000);
                chart.appear(1000, 100);

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
                    categoryField: "month",
                    renderer: am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    })
                }));

                const yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));

                const series = chart.series.push(am5xy.LineSeries.new(root, {
                    name: "Net Book Value",
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: "nbv",
                    categoryXField: "month",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{categoryX}: {valueY.formatNumber('#,###.')}"
                    })
                }));

                series.strokes.template.setAll({
                    strokeWidth: 2
                });

                series.bullets.push(function() {
                    return am5.Bullet.new(root, {
                        sprite: am5.Circle.new(root, {
                            radius: 4
                        })
                    });
                });

                xAxis.data.setAll(data);
                series.data.setAll(data);

                chart.set("cursor", am5xy.XYCursor.new(root, {}));
                series.appear(1000);
                chart.appear(1000, 100);

                am5plugins_exporting.Exporting.new(root, {
                    menu: am5plugins_exporting.ExportingMenu.new(root, {}),
                    dataSource: data
                });
            }
        })();
    </script>
@endpush
