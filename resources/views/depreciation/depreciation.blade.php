@php
    use Carbon\Carbon;
    $currentMonth = Carbon::now()->startOfMonth()->toDateString();
    $currentMonthText = Carbon::now()->isoFormat('MMMM YYYY');
    $prevYearLabel = Carbon::now()->year - 1;
    $currentYear = Carbon::parse($currentMonth)->year;
@endphp

@extends('layouts.app')

@section('title', 'Depreciation')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Depreciation
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Depreciation</li>
                </ul>
            </div>
        </div>
    </div>


    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card mb-6">
                <div class="card-body">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-4">
                            <label class="form-label">Asset Status</label>
                            <select id="f-asset-status" class="form-select"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Masuk From</label>
                            <input type="date" id="f-cap-from" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Masuk To</label>
                            <input type="date" id="f-cap-to" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Period</label>
                            <input type="month" id="f-period" class="form-control"
                                value="{{ \Carbon\Carbon::parse($currentMonth)->format('Y-m') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Asset (code / description)</label>
                            <input type="text" id="f-asset" class="form-control"
                                placeholder="e.g. A1101000002-00 / Laptop Dell">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button id="btnFilter" class="btn btn-danger btn-sm me-2">Apply Filter</button>
                    <button id="btnReset" class="btn btn-light-danger btn-sm me-2">Reset</button>
                    <button id="btnExport" class="btn btn-light-danger btn-sm">Export Excel</button>
                </div>
            </div>
            <div class="card">
                <div class="card-header align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1" id="currentMonthText">{{ $currentMonthText }}</span>
                        </h3>
                    </div>

                    @canAction('DEPRECIATION','C')
                    <div class="card-toolbar">
                        {{-- Process Depreciation Month --}}
                        <form id="form-process-month" method="POST" action="{{ route('depreciation.run.month') }}"
                            class="d-inline-flex align-items-center gap-2 me-2">
                            @csrf
                            <input type="hidden" name="period" id="period" value="{{ $currentMonth }}">
                            <button type="button" id="btn-open-process-month" class="btn btn-danger btn-sm">
                                <span class="indicator-label">Process Depreciation Month</span>
                                <span class="indicator-progress">
                                    Processing… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </form>

                        {{-- Build Year --}}
                        <form id="form-build-year" method="POST" action="{{ route('depreciation.build.year') }}"
                            class="d-inline-flex align-items-center gap-2 me-2">
                            @csrf
                            <input type="hidden" name="year" id="year" value="{{ $currentYear }}">
                            <button type="button" id="btn-open-build-year" class="btn btn-danger btn-sm">
                                <span id="btn-build-year-label" class="indicator-label">Build Year</span>
                                <span class="indicator-progress">
                                    Building… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </form>

                        {{-- Adjustment Depreciation --}}
                        <button type="button" id="btn-open-adj-depr" class="btn btn-danger btn-sm">
                            Adjustment Depreciation
                        </button>
                    </div>
                    @endcanAction

                </div>

                <div class="card-body">
                    <table id="tbl-monthly"
                        class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded ">
                        <thead class="table-light">
                            <tr>
                                <th class="min-w-200px">Asset Code</th>
                                <th class="min-w-200px">Asset Description</th>
                                {{-- <th class="min-w-150px">Transaction Number</th> --}}
                                <th class="min-w-150px">Asset Status</th>
                                <th class="min-w-200px">Tanggal Masuk</th>
                                <th class="min-w-200px">Depreciation Date</th>
                                <th class="min-w-200px">Awal</th>
                                <th class="min-w-200px">Total (Assets Value)</th>
                                <th class="min-w-200px">Useful Life (Month)</th>
                                <th class="min-w-200px">Ending Balance {{ $prevYearLabel }}</th>
                                <th class="min-w-200px">Remaining Useful Life</th>
                                <th class="min-w-200px">Transfer In</th>
                                <th class="min-w-200px">Transfer Out</th>
                                <th class="min-w-200px">Adjusment Depreciation</th>
                                <th class="min-w-200px">Depreciation</th>
                                <th class="min-w-200px">Total Addition</th>
                                <th class="min-w-200px">Accumulated Depreciation</th>
                                <th class="min-w-200px">Net Book Value</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total Depreciation (This Month)</th>
                                <th id="depr-sum" class="text-danger"></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {{-- Process Depreciation Month - Month Picker --}}
    <div class="modal fade" id="modal-process-month" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Process Depreciation Month</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-process-month-modal" autocomplete="off">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Period</label>
                            <input type="month" id="proc-period" class="form-control"
                                value="{{ \Carbon\Carbon::parse($currentMonth)->format('Y-m') }}">
                            <small class="text-muted">Pick any month to process depreciation.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-run-process-month" class="btn btn-danger">
                            <span class="indicator-label">Process</span>
                            <span class="indicator-progress">
                                Processing… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Build Year - Year Picker --}}
    <div class="modal fade" id="modal-build-year" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Build Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-build-year-modal" autocomplete="off">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Year</label>
                            <select id="build-year-select" class="form-select">
                                @for ($y = $currentYear - 5; $y <= $currentYear + 1; $y++)
                                    <option value="{{ $y }}" @selected($y == $currentYear)>{{ $y }}
                                    </option>
                                @endfor
                            </select>
                            <small class="text-muted">Pick the year to build depreciation summary.</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-run-build-year" class="btn btn-danger">
                            <span class="indicator-label">Build</span>
                            <span class="indicator-progress">
                                Building… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Adjustment Depreciation Modal --}}
    <div class="modal fade" id="modal-adj-depr" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Adjustment Depreciation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-adj-depr" autocomplete="off">
                    @csrf
                    <div class="modal-body py-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label required">Asset</label>
                                <select id="adj-asset" class="form-select" style="width:100%"></select>
                                <input type="hidden" id="adj-asset-uuid" name="asset_uuid">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Amount</label>
                                <input type="number" step="0.01" class="form-control" id="adj-amount"
                                    name="amount" placeholder="e.g. -1500000 or 1500000">
                                <small class="text-muted d-block mt-1">
                                    Positive value = increase NBV, negative value = decrease NBV.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Actual Date</label>
                                <input type="date" class="form-control" id="adj-date" name="actual_date"
                                    value="{{ Carbon::now()->toDateString() }}">
                            </div>

                            {{-- <div class="col-md-6">
                                <label class="form-label">Note (Optional)</label>
                                <textarea class="form-control" id="adj-note" name="note" placeholder="Optional note"></textarea>
                            </div> --}}
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-submit-adj" class="btn btn-primary">
                            <span class="indicator-label">Save Adjustment</span>
                            <span class="indicator-progress">
                                Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        (function() {
            const PERIOD = "{{ $currentMonth }}"; // YYYY-MM-01
            const CURRENT_YEAR = {{ $currentYear }};
            const DEFAULT_PERIOD_MONTH = "{{ \Carbon\Carbon::parse($currentMonth)->format('Y-m') }}";

            const $formProc = $('#form-process-month');
            const $formYear = $('#form-build-year');

            const $btnProcOpen = $('#btn-open-process-month');
            const $btnYearOpen = $('#btn-open-build-year');
            const $btnAdjOpen = $('#btn-open-adj-depr');

            const $procMonthInp = $('#proc-period');
            const $yearSelect = $('#build-year-select');

            const monthModal = new bootstrap.Modal(document.getElementById('modal-process-month'));
            const yearModal = new bootstrap.Modal(document.getElementById('modal-build-year'));
            const $modalAdj = new bootstrap.Modal(document.getElementById('modal-adj-depr'));

            const money = v => Intl.NumberFormat().format(Number(v ?? 0));
            const money2 = v => new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(v ?? 0));

            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));

            const setBusyProc = busy => {
                if (busy) $btnProcOpen.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnProcOpen.removeAttr('data-kt-indicator').prop('disabled', false);
            };

            const setBusyYear = busy => {
                if (busy) $btnYearOpen.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnYearOpen.removeAttr('data-kt-indicator').prop('disabled', false);
            };

            function buildFilters() {
                const periodMonth = $('#f-period').val();
                const period = periodMonth ? (periodMonth + '-01') : PERIOD;

                return {
                    period: period,
                    asset_status: $('#f-asset-status').val() || '',
                    cap_from: $('#f-cap-from').val() || '',
                    cap_to: $('#f-cap-to').val() || '',
                    asset_q: $('#f-asset').val() || '',
                };
            }

            // ===== Adjustment Depreciation modal open =====
            $btnAdjOpen.on('click', () => {
                $('#form-adj-depr')[0].reset();
                $('#adj-asset-uuid').val('');
                $('#adj-asset').val(null).trigger('change');
                $('#adj-date').val("{{ Carbon::now()->toDateString() }}");
                $modalAdj.show();
            });

            $('#f-asset-status').select2({
                placeholder: 'All status',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('master.status.options') }}",
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        type: 'Asset',
                        page: params.page || 1
                    }),
                    processResults: d => d
                }
            });

            $('#adj-asset').select2({
                dropdownParent: $('#modal-adj-depr'),
                placeholder: 'Search asset code/name…',
                allowClear: true,
                ajax: {
                    url: "{{ route('depreciation.assets.search') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: (data?.data || data || []).map(it => ({
                            id: it.uuid || it.asset_uuid,
                            text: (it.asset_code ? it.asset_code : '') +
                                (it.description ? ' - ' + it.description : '')
                        }))
                    })
                }
            }).on('select2:select select2:clear', function() {
                $('#adj-asset-uuid').val($(this).val() || '');
            });

            // ===== DataTable =====
            const tbl = $('#tbl-monthly').DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 400,
                scrollX: true,
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                searching: true,
                order: [
                    [0, 'asc']
                ],
                ajax: {
                    url: "{{ route('depreciation.dt.monthly') }}",
                    data: d => Object.assign(d, buildFilters())
                },
                columns: [{
                        data: 'asset_code',
                        name: 'asset_code',
                        render: function(data, type, row) {
                            if (type !== 'display') return data;
                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row
                                .asset_uuid));
                            return `<a href="${url}" class="text-primary fw-semibold">${data ?? ''}</a>`;
                        }
                    },
                    {
                        data: 'asset_name',
                        name: 'asset_name'
                    },
                    // {
                    //     data: 'depr_code',
                    //     name: 'depr_code'
                    // },
                    {
                        data: 'asset_status_label',
                        name: 'asset_status_label'
                    },
                    {
                        data: 'cap_date',
                        name: 'av.capitalization_date',
                        defaultContent: '',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;
                            const d = new Date(iso);
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
                        data: 'opening_balance',
                        render: money,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'total_value',
                        render: money2,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'useful_life_months',
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'ending_balance_prev_year',
                        render: money2,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'remaining_useful_life_months',
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'transfers_in',
                        render: money,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'transfers_out',
                        render: money,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'adjustment_depreciation',
                        render: money,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'depr_expense',
                        render: money,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: null,
                        className: 'text-end',
                        searchable: false,
                        render: (_, __, row) => {
                            const a = Number(row.additions || 0);
                            const ti = Number(row.transfers_in || 0);
                            const to = Number(row.transfers_out || 0);
                            const av = Number(row.adjustment_value || 0);
                            const ds = Number(row.disposals || 0);
                            return money(a + ti - to + av - ds);
                        }
                    },
                    {
                        data: 'accumulated_depr_end',
                        render: money,
                        className: 'text-end',
                        searchable: false
                    },
                    {
                        data: 'ending_balance',
                        render: money,
                        className: 'text-end',
                        searchable: false
                    },
                ]
            });
            tbl.on('xhr', function() {
                const json = tbl.ajax.json() || {};
                const total = json.total_depr ?? 0;
                $('#depr-sum').text(money2(total));
            });

            function formatPeriodToText(periodMonth) {
                if (!periodMonth) return '';
                const [year, month] = periodMonth.split('-');
                const date = new Date(Number(year), Number(month) - 1, 1);
                return date.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });
            }

            function formatPeriodDeprCode(periodMonth) {
                if (!periodMonth) return '';
                const [year, month] = periodMonth.split('-');
                const yy = String(year).slice(-2);
                const mm = String(month).padStart(2, '0');
                return yy + mm;
            }

            function updatePeriodText() {
                const periodVal = $('#f-period').val() || DEFAULT_PERIOD_MONTH;
                $('#currentMonthText').text(formatPeriodToText(periodVal) + ' - DEP' + formatPeriodDeprCode(periodVal) +
                    '0001');
            }

            // initial text
            updatePeriodText();

            // ===== Filter & Reset & Export =====
            $('#btnFilter').on('click', function() {
                tbl.ajax.reload();
                updatePeriodText();
            });

            $('#btnReset').on('click', function() {
                $('#f-period').val(DEFAULT_PERIOD_MONTH);
                $('#f-asset-status').val('').trigger('change');
                $('#f-cap-from').val('');
                $('#f-cap-to').val('');
                $('#f-asset').val('');
                tbl.ajax.reload();
                updatePeriodText();
                console.log('Filters reset');
            });

            $('#btnExport').on('click', function(e) {
                e.preventDefault();
                const params = $.param(buildFilters());

                Swal.fire({
                    title: 'Export depreciation data?',
                    text: 'Export current filtered depreciation data to Excel.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    window.location = "{{ route('export.depreciation.monthly') }}" + '?' + params;
                });
            });

            // ===== Open Process Month modal (any year) =====
            $btnProcOpen.on('click', function() {
                const period = $('#f-period').val() || DEFAULT_PERIOD_MONTH;
                $procMonthInp.val(period);
                monthModal.show();
            });

            // ===== Submit Process Month (from modal) =====
            $('#form-process-month-modal').on('submit', async function(e) {
                e.preventDefault();

                const periodMonth = $procMonthInp.val();
                if (!periodMonth) {
                    toastr?.error('Please pick a month');
                    return;
                }

                const period = periodMonth + '-01';
                $('#period').val(period); // hidden input in main form

                Swal.fire({
                    title: `Process depreciation for ${formatPeriodToText(periodMonth)}?`,
                    text: 'This will (re)calculate depreciation for the selected month.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(result => {
                    if (!result.isConfirmed) return;

                    setBusyProc(true);
                    monthModal.hide();

                    $.ajax({
                            url: $formProc.attr('action'),
                            type: 'POST',
                            data: $formProc.serialize(),
                            headers: {
                                'X-CSRF-TOKEN': $('input[name="_token"]', $formProc).val()
                            }
                        })
                        .done(function(resp) {
                            // kalau backend balikin { ok:false } tapi status 200, anggap error juga
                            if (resp && resp.ok === false) {
                                const msg = resp.message || 'Failed to process depreciation';
                                return Swal.fire({
                                    icon: 'error',
                                    title: 'Process failed',
                                    text: msg,
                                    confirmButtonColor: '#EA242A'
                                });
                            }

                            function prevMonthStr(ym) {
                                const [y, m] = ym.split('-').map(Number);
                                const d = new Date(y, m - 1, 1);
                                d.setMonth(d.getMonth() - 1);
                                return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2,'0')}`;
                            }

                            const shownMonth = prevMonthStr(periodMonth);
                            toastr?.success(`Processed ${formatPeriodToText(shownMonth)}`);
                            tbl.ajax.reload(null, false);
                        })
                        .fail(function(xhr) {
                            const rj = xhr.responseJSON;
                            let msg = rj?.message || 'Failed to process depreciation';

                            if (rj?.need_period_text) {
                                msg += `\nPlease process: ${rj.need_period_text} first.`;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Process failed',
                                text: msg,
                                confirmButtonColor: '#EA242A'
                            });
                        })
                        .always(function() {
                            setBusyProc(false);
                        });
                });

            });


            // ===== Open Build Year modal =====
            $btnYearOpen.on('click', function() {
                // Suggest year from filter if same as currentYear range
                const filterPeriod = $('#f-period').val();
                if (filterPeriod && filterPeriod.includes('-')) {
                    const fy = Number(filterPeriod.split('-')[0]);
                    if (fy && fy >= CURRENT_YEAR - 5 && fy <= CURRENT_YEAR + 1) {
                        $yearSelect.val(String(fy));
                    }
                }
                yearModal.show();
            });

            // ===== Submit Build Year (from modal) =====
            $('#form-build-year-modal').on('submit', async function(e) {
                e.preventDefault();

                const yearVal = $yearSelect.val();
                const yearNum = Number(yearVal);
                if (!yearNum) {
                    toastr?.error('Please pick a year');
                    return;
                }

                $('#year').val(yearNum);
                $('#btn-build-year-label').text(`Build Year`);

                Swal.fire({
                    title: `Build depreciation summary for ${yearNum}?`,
                    text: 'This will (re)build yearly depreciation summary for the selected year.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(async result => {
                    if (!result.isConfirmed) return;

                    try {
                        setBusyYear(true);
                        yearModal.hide();
                        await $.ajax({
                            url: $formYear.attr('action'),
                            type: 'POST',
                            data: $formYear.serialize(),
                            headers: {
                                'X-CSRF-TOKEN': $('input[name="_token"]', $formYear)
                                    .val()
                            }
                        });
                        toastr?.success(`Yearly summary built for ${yearNum}`);
                    } catch (err) {
                        console.error(err);
                        const msg = err?.responseJSON?.message ||
                            'Failed to build yearly depreciation';
                        toastr?.error(msg);
                    } finally {
                        setBusyYear(false);
                    }
                });
            });


            // ===== Adjustment Depreciation submit =====
            const $btnAdj = $('#btn-submit-adj');
            const setBusyAdj = b => {
                if (b) $btnAdj.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnAdj.removeAttr('data-kt-indicator').prop('disabled', false);
            };


            $('#form-adj-depr').on('submit', async function(e) {
                e.preventDefault();

                const assetUuid = $('#adj-asset-uuid').val();
                const amount = Number($('#adj-amount').val() || 0);
                const date = $('#adj-date').val();
                const note = $('#adj-note').val() || '';

                if (!assetUuid) {
                    toastr?.error('Please pick an asset');
                    return;
                }
                if (!date) {
                    toastr?.error('Actual Date is required');
                    return;
                }
                if (!amount || amount === 0) {
                    toastr?.error('Amount must be non-zero (positive or negative)');
                    return;
                }

                Swal.fire({
                    title: 'Save this adjustment?',
                    text: 'This will change the net book value for the selected asset.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(async result => {
                    if (!result.isConfirmed) return;

                    try {
                        setBusyAdj(true);
                        await $.ajax({
                            url: "{{ route('depreciation.mv.adj.depr') }}",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            data: {
                                asset_uuid: assetUuid,
                                amount: amount,
                                actual_date: date,
                                note: note
                            }
                        });

                        // best-effort re-run month
                        try {
                            await $.ajax({
                                url: "{{ route('depreciation.run.month') }}",
                                type: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                                },
                                data: {
                                    period: date
                                }
                            });
                        } catch (e2) {
                            console.error('runMonth after adjustment failed', e2);
                            toastr?.warning(
                                'Adjustment saved, but monthly recompute failed. Please press "Process Depreciation Month".'
                            );
                        }

                        toastr?.success('Adjustment depreciation saved');
                        tbl.ajax.reload(null, false);
                        $modalAdj.hide();
                    } catch (err) {
                        console.error(err);
                        const msg = err?.responseJSON?.message || 'Failed to save adjustment';
                        toastr?.error(msg);
                    } finally {
                        setBusyAdj(false);
                    }
                });
            });

        })();
    </script>
@endpush
