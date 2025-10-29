@php
    use Carbon\Carbon;
    $currentMonth = Carbon::now()->startOfMonth()->toDateString();
    $currentMonthText = Carbon::now()->isoFormat('MMMM YYYY');
    $prevYearLabel = Carbon::now()->year - 1;
@endphp

@extends('layouts.app')

@section('title', 'Depreciation')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Depreciation
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        Transaction
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Depreciation</li>
                </ul>
            </div>

        </div>
        <!--end::Toolbar container-->
    </div>

    <div class="container-xxl" id="kt_content_container">
        <div class="card">
            <div class="card-header align-items-center justify-content-between">
                <div class="d-flex flex-column">
                    <h3 class="card-title mb-1"><span class="fw-semibold">{{ $currentMonthText }}</span></h3>
                    {{-- <span class="text-muted fs-7">Only showing current month. Use the button to process this month’s
                        depreciation.</span> --}}
                </div>
                <div class="card-toolbar">
                    <form id="form-process-month" method="POST" action="{{ route('depreciation.run.month') }}"
                        class="d-inline-flex align-items-center gap-2">
                        @csrf
                        <input type="hidden" name="period" id="period" value="{{ $currentMonth }}">
                        <button type="submit" id="btn-process-month" class="btn btn-danger btn-sm">
                            <span class="indicator-label">
                                <i class="ki-duotone ki-refresh fs-4 me-2"></i>Process Current Month
                            </span>
                            <span class="indicator-progress">
                                Processing… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-body">
                <table id="tbl-monthly"
                    class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded ">
                    <thead class="table-light">
                        <tr>
                            <th class="min-w-200px">Asset Code</th>
                            <th class="min-w-200px">Asset Name</th>
                            <th class="min-w-200px">Tanggal Masuk</th> {{-- capitalization_date dari assets_value --}}
                            <th class="min-w-200px">Depreciation Date</th> {{-- bulan berjalan (period) --}}
                            <th class="min-w-200px">Awal</th> {{-- opening_balance --}}
                            <th class="min-w-200px">Total (Assets Value)</th>
                            <th class="min-w-200px">Useful Life (Month)</th>
                            <th class="min-w-200px">Ending Balance {{ $prevYearLabel }}</th>
                            <th class="min-w-200px">Remaining Useful Life</th>
                            {{-- <th class="min-w-200px">Transfer In</th>
                            <th class="min-w-200px">Transfer Out</th> --}}
                            <th class="min-w-200px">Adjusment Depreciation</th> {{-- adjustment_depreciation --}}
                            <th class="min-w-200px">Depreciation</th> {{-- depr_expense --}}
                            <th class="min-w-200px">Total Addition</th> {{-- Addition + In - Out + Adj Nilai - Disposal --}}
                            <th class="min-w-200px">Ending Balance</th>
                            {{-- <th class="min-w-200px">Addition</th>
                            <th class="min-w-200px">Adjustment Nilai</th>
                            <th class="min-w-200px">Accumulated (End)</th> --}}
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const PERIOD = "{{ $currentMonth }}";
            const $btn = $('#btn-process-month');
            const $form = $('#form-process-month');
            const money = v => Intl.NumberFormat().format(Number(v ?? 0));
            const money2 = v => new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(v ?? 0));

            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
            const setBusy = (busy) => {
                if (busy) $btn.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btn.removeAttr('data-kt-indicator').prop('disabled', false);
            };

            const tbl = $('#tbl-monthly').DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 400,
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
                    [0, 'asc']
                ],
                ajax: {
                    url: "{{ route('depreciation.dt.monthly') }}",
                    data: d => {
                        d.period = PERIOD;
                    }
                },
                columns: [{
                        data: 'asset_code',
                        name: 'asset_code',
                        "render": function(data, type, row, meta) {
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
                    {
                        data: 'cap_date',
                        name: 'cap_date',
                        defaultContent: '',
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
                            return `${dateStr}`;
                        }
                    },
                    {
                        data: 'period',
                        name: 'period',
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
                            return `${dateStr}`;
                        }
                    },
                    {
                        data: 'opening_balance',
                        render: money,
                        className: 'text-end'
                    },
                    {
                        data: 'total_value',
                        render: money2,
                        className: 'text-end'
                    },
                    {
                        data: 'useful_life_months',
                        className: 'text-end'
                    },
                    {
                        data: 'ending_balance_prev_year',
                        render: money2,
                        className: 'text-end'
                    },
                    {
                        data: 'remaining_useful_life_months',
                        className: 'text-end'
                    },
                    // {
                    //     data: 'transfers_in',
                    //     render: money,
                    //     className: 'text-end'
                    // },
                    // {
                    //     data: 'transfers_out',
                    //     render: money,
                    //     className: 'text-end'
                    // },
                    {
                        data: 'adjustment_depreciation',
                        render: money,
                        className: 'text-end'
                    },
                    {
                        data: 'depr_expense',
                        render: money,
                        className: 'text-end'
                    },
                    // {
                    //     data: 'additions',
                    //     render: money,
                    //     className: 'text-end'
                    // },
                    // {
                    //     data: 'adjustment_value',
                    //     render: money,
                    //     className: 'text-end'
                    // },
                    {
                        data: null,
                        className: 'text-end',
                        render: (_, __, row) => {
                            const a = Number(row.additions || 0);
                            const ti = Number(row.transfers_in || 0);
                            const to = Number(row.transfers_out || 0);
                            const av = Number(row.adjustment_value || 0);
                            const ds = Number(row.disposals || 0);
                            return money(a + ti - to + av - ds);
                        }
                    },
                    // {
                    //     data: 'accumulated_depr_end',
                    //     render: money,
                    //     className: 'text-end'
                    // },
                    {
                        data: 'ending_balance',
                        render: money,
                        className: 'text-end'
                    },
                ]
            });

            $form.on('submit', async function(e) {
                e
                    .preventDefault();
                try {
                    setBusy(true);
                    await $.ajax({
                        url: this.action,
                        type: 'POST',
                        data: $(this).serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]', this).val()
                        }
                    });
                    if (window.toastr) toastr.success('Processed {{ $currentMonthText }}');
                    tbl.ajax.reload(null, false);
                } catch (err) {
                    console.error(err);
                    if (window.toastr) toastr.error('Failed to process depreciation');
                    else alert('Failed to process depreciation');
                } finally {
                    setBusy(false);
                }
            });

        })();
    </script>
@endpush
