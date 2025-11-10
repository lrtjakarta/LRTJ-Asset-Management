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
                    <li class="breadcrumb-item text-muted">Transaction</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Depreciation</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="container-xxl" id="kt_content_container">
        <div class="card">
            <div class="card-header align-items-center justify-content-between">
                <div class="d-flex flex-column">
                    <h3 class="card-title mb-1">
                        <span class="fw-semibold">{{ $currentMonthText }}</span>
                    </h3>
                </div>

                <div class="card-toolbar">
                    {{-- Process Current Month --}}
                    <form id="form-process-month" method="POST" action="{{ route('depreciation.run.month') }}"
                        class="d-inline-flex align-items-center gap-2 me-2">
                        @csrf
                        <input type="hidden" name="period" id="period" value="{{ $currentMonth }}">
                        <button type="submit" id="btn-process-month" class="btn btn-danger btn-sm">
                            <span class="indicator-label">Process Current Month</span>
                            <span class="indicator-progress">
                                Processing… <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </form>

                    {{-- Build Year button (current year) --}}
                    <form id="form-build-year" method="POST" action="{{ route('depreciation.build.year') }}"
                        class="d-inline-flex align-items-center gap-2 me-2">
                        @csrf
                        <input type="hidden" name="year" value="{{ $currentYear }}">
                        <button type="submit" id="btn-build-year" class="btn btn-danger btn-sm">
                            <span class="indicator-label">Build Year {{ $currentYear }}</span>
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
            </div>

            <div class="card-body">
                <table id="tbl-monthly"
                    class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded ">
                    <thead class="table-light">
                        <tr>
                            <th class="min-w-200px">Asset Code</th>
                            <th class="min-w-200px">Asset Name</th>
                            <th class="min-w-150px">Transaction Number</th>
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
                            <th class="min-w-200px">Ending Balance</th>
                        </tr>
                    </thead>
                </table>
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
            const PERIOD = "{{ $currentMonth }}";
            const $btn = $('#btn-process-month');
            const $form = $('#form-process-month');
            const $btnYear = $('#btn-build-year');
            const $formYear = $('#form-build-year');
            const $modalAdj = new bootstrap.Modal(document.getElementById('modal-adj-depr'));

            const money = v => Intl.NumberFormat().format(Number(v ?? 0));
            const money2 = v => new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(v ?? 0));
            const fmt2 = v => new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(v || 0));

            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));

            const setBusy = (busy) => {
                if (busy) $btn.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btn.removeAttr('data-kt-indicator').prop('disabled', false);
            };
            const setBusyYear = (busy) => {
                if (busy) $btnYear.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnYear.removeAttr('data-kt-indicator').prop('disabled', false);
            };

            // Open Adjustment Depreciation modal
            $('#btn-open-adj-depr').on('click', () => {
                $('#form-adj-depr')[0].reset();
                $('#adj-asset-uuid').val('');
                $('#adj-asset').val(null).trigger('change');
                $('#adj-date').val("{{ Carbon::now()->toDateString() }}");
                $modalAdj.show();
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

            // DataTable
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
                    data: d => {
                        d.period = PERIOD;
                    }
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
                    {
                        data: 'depr_code',
                        name: 'depr_code'
                    },
                    {
                        data: 'asset_status_label',
                        name: 'asset_status_label'
                    },
                    {
                        data: 'cap_date',
                        name: 'cap_date',
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
                    {
                        data: 'transfers_in',
                        render: money,
                        className: 'text-end'
                    },
                    {
                        data: 'transfers_out',
                        render: money,
                        className: 'text-end'
                    },
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
                    {
                        data: 'ending_balance',
                        render: money,
                        className: 'text-end'
                    },
                ]
            });

            // Process Current Month
            $form.on('submit', async function(e) {
                e.preventDefault();
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
                    toastr?.success('Processed {{ $currentMonthText }}');
                    tbl.ajax.reload(null, false);
                } catch (err) {
                    console.error(err);
                    toastr?.error('Failed to process depreciation');
                } finally {
                    setBusy(false);
                }
            });

            // Build Year (current year)
            $formYear.on('submit', async function(e) {
                e.preventDefault();
                try {
                    setBusyYear(true);
                    await $.ajax({
                        url: this.action,
                        type: 'POST',
                        data: $(this).serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]', this).val()
                        }
                    });
                    toastr?.success('Yearly summary built for {{ $currentYear }}');
                } catch (err) {
                    console.error(err);
                    const msg = err?.responseJSON?.message || 'Failed to build yearly depreciation';
                    toastr?.error(msg);
                } finally {
                    setBusyYear(false);
                }
            });

            // ===== Adjustment Depreciation submit =====
            const $btnAdj = $('#btn-submit-adj');
            const setBusyAdj = (b) => {
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
                            'Adjustment saved, but monthly recompute failed. Please press "Process Current Month".'
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

        })();
    </script>
@endpush
