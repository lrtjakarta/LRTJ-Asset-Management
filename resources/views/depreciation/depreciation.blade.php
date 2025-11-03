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

                    {{-- Transfer Value --}}
                    <button type="button" id="btn-open-transfer" class="btn btn-primary btn-sm">
                        Transfer Value
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

    {{-- Transfer Value Modal --}}
    <div class="modal fade" id="modal-transfer" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Transfer Value</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-transfer" autocomplete="off">
                    @csrf
                    <div class="modal-body py-4">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label"><b>Transfer Type :</b></label>
                                <div class="btn-group" role="group" aria-label="Transfer type"
                                    style="border: solid .5px black; width:100%;">
                                    {{-- 1. tf-val (existing case 1) --}}
                                    <input type="radio" class="btn-check" name="tr-type" id="tr-type-tf-val"
                                        value="tf-val" checked>
                                    <label class="btn btn-outline-danger btn-sm" for="tr-type-tf-val">
                                        1. Partials/Full (Gross only)
                                    </label>

                                    {{-- 2. Acquisition Fix (placeholder for case 2) --}}
                                    <input type="radio" class="btn-check" name="tr-type" id="tr-type-acq-fix"
                                        value="acq_fix">
                                    <label class="btn btn-outline-danger btn-sm" for="tr-type-acq-fix">
                                        2. Acquisition Fix
                                    </label>

                                    {{-- 3. Carry-Over (Gross + Accum) --}}
                                    <input type="radio" class="btn-check" name="tr-type" id="tr-type-carry"
                                        value="carry_over_gross_accum">
                                    <label class="btn btn-outline-danger btn-sm" for="tr-type-carry">
                                        3. Carry-Over (Gross + Accum)
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">From Asset</label>
                                <select id="tr-from-asset" class="form-select" style="width:100%"></select>
                                <input type="hidden" id="tr-from-uuid" name="from_asset_uuid">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">To Asset</label>
                                <select id="tr-to-asset" class="form-select" style="width:100%"></select>
                                <input type="hidden" id="tr-to-uuid" name="to_asset_uuid">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Amount</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="tr-amount"
                                    name="amount" placeholder="e.g. 15000000">
                                <small id="tr-cap-help" class="text-muted d-block mt-1"></small>
                                <small id="tr-carry-help" class="text-warning d-block mt-1"></small>
                                <div id="tr-preview" class="alert d-none py-2 px-3 mt-2 small"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Actual Date</label>
                                <input type="date" class="form-control" id="tr-date" name="actual_date"
                                    value="{{ Carbon::now()->toDateString() }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <input type="text" class="form-control" id="tr-note" name="note"
                                    maxlength="300" placeholder="Optional note">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-submit-transfer" class="btn btn-primary">
                            <span class="indicator-label">Submit Transfer</span>
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
            const $modal = new bootstrap.Modal(document.getElementById('modal-transfer'));

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

            // Open Transfer modal
            $('#btn-open-transfer').on('click', () => {
                $('#form-transfer')[0].reset();
                $('#tr-from-uuid, #tr-to-uuid').val('');
                $('#tr-from-asset, #tr-to-asset').val(null).trigger('change');
                $('#tr-type-tf-val').prop('checked', true);
                $('#tr-cap-help').text('');
                $('#tr-carry-help').text('');
                $('#tr-preview').addClass('d-none').empty();
                $modal.show();
                syncTypeUI();
            });

            // select2 init
            const initAssetSelect = ($el) => {
                $el.select2({
                    dropdownParent: $('#modal-transfer'),
                    placeholder: 'Search asset code/name…',
                    allowClear: true,
                    minimumInputLength: 1,
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
                });
            };
            initAssetSelect($('#tr-from-asset'));
            initAssetSelect($('#tr-to-asset'));

            $('#tr-from-asset').on('select2:select select2:clear', () => {
                $('#tr-from-uuid').val($('#tr-from-asset').val() || '');
                if (getTransferType() === 'tf-val') refreshTransferCap();
                else refreshCarryPreview();
            });
            $('#tr-to-asset').on('select2:select select2:clear', function() {
                $('#tr-to-uuid').val($(this).val() || '');
            });
            $('#tr-date').on('change', () => {
                if (getTransferType() === 'tf-val') refreshTransferCap();
                else refreshCarryPreview();
            });
            $('#tr-amount').on('keyup change', () => {
                if (getTransferType() === 'carry_over_gross_accum') refreshCarryPreview();
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
                                day: '2-digit',
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

            // ===== Transfer helpers =====
            async function refreshTransferCap() {
                const fromUUID = $('#tr-from-uuid').val();
                const date = $('#tr-date').val();
                if (!fromUUID || !date) {
                    $('#tr-cap-help').text('');
                    $('#tr-amount').data('capRemaining', 0);
                    return;
                }

                try {
                    const res = await $.get("{{ route('depreciation.mv.transfer.limit') }}", {
                        from_asset_uuid: fromUUID,
                        actual_date: date
                    });
                    const cap = res || {};
                    const lastP = cap.last_closed_period ? new Date(cap.last_closed_period).toISOString().slice(0,
                        10) : '-';
                    $('#tr-cap-help').text(
                        `Max: ${fmt2(cap.remaining)} (Begin: ${fmt2(cap.begin_total)} − Last NBV ${lastP}: ${fmt2(cap.last_nbv)} − This month OUT: ${fmt2(cap.already_out)})`
                    );
                    $('#tr-amount').data('capRemaining', Number(cap.remaining || 0));
                } catch {
                    $('#tr-cap-help').text('Max not available');
                    $('#tr-amount').data('capRemaining', 0);
                }
            }

            const getTransferType = () => $('input[name="tr-type"]:checked').val();

            function syncTypeUI() {
                const t = getTransferType();
                if (t === 'tf-val') {
                    $('#tr-carry-help').text('');
                    $('#tr-preview').addClass('d-none').empty();
                    refreshTransferCap();
                } else if (t === 'carry_over_gross_accum') {
                    $('#tr-cap-help').text('');
                    refreshCarryPreview();
                } else { // acq_fix or others
                    $('#tr-cap-help').text('');
                    $('#tr-carry-help').text('');
                    $('#tr-preview').addClass('d-none').empty();
                }
            }
            $('input[name="tr-type"]').on('change', syncTypeUI);

            async function refreshCarryPreview() {
                const fromUUID = $('#tr-from-uuid').val();
                const date = $('#tr-date').val();
                const amount = Number($('#tr-amount').val() || 0);

                $('#tr-carry-help').text('');
                $('#tr-preview').addClass('d-none').empty();
                $('#tr-amount').data('capGross', null);

                if (!fromUUID || !date || !(amount > 0)) return;

                try {
                    const res = await $.get("{{ route('depreciation.mv.carryover.preview') }}", {
                        from_asset_uuid: fromUUID,
                        actual_date: date,
                        amount: amount
                    });
                    const d = res || {};
                    const lc = d.last_closed_period ? new Date(d.last_closed_period).toISOString().slice(0, 10) :
                        '-';

                    $('#tr-preview').removeClass('d-none').html(
                        `Total Gross A: <b>${fmt2(d.total_gross)}</b> · Accum as of <b>${lc}</b>: <b>${fmt2(d.accum_as_of_last)}</b><br>
                 Accum to carry: <b>${fmt2(d.acc_move)}</b> · NBV that moves: <b>${fmt2(d.nbv_move)}</b><br>
                 Gross remaining cap: <b>${fmt2(d.cap_remaining)}</b>`
                    );
                    $('#tr-amount').data('capGross', Number(d.cap_remaining || 0));

                    if (amount > Number(d.cap_remaining || 0)) {
                        $('#tr-carry-help').text(
                            'Amount exceeds max carry-over (gross remaining). Please reduce the amount.');
                    } else {
                        $('#tr-carry-help').text('');
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            const $btnSave = $('#btn-submit-transfer');
            const setBusyTransfer = (b) => {
                if (b) $btnSave.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnSave.removeAttr('data-kt-indicator').prop('disabled', false);
            };

            $('#form-transfer').on('submit', async function(e) {
                e.preventDefault();

                const fromUUID = $('#tr-from-uuid').val();
                const toUUID = $('#tr-to-uuid').val();
                const amount = Number($('#tr-amount').val() || 0);
                const date = $('#tr-date').val();
                const type = getTransferType();

                if (!fromUUID || !toUUID) {
                    toastr?.error('Please pick both assets');
                    return;
                }
                if (fromUUID === toUUID) {
                    toastr?.error('From/To asset cannot be the same');
                    return;
                }
                if (!(amount > 0)) {
                    toastr?.error('Amount must be greater than 0');
                    return;
                }
                if (!date) {
                    toastr?.error('Actual Date is required');
                    return;
                }

                if (type === 'tf-val') {
                    const capRem = Number($('#tr-amount').data('capRemaining') || 0);
                    if (capRem && amount > capRem) {
                        toastr?.error('Amount exceeds max allowed for that month.');
                        return;
                    }
                } else if (type === 'carry_over_gross_accum') {
                    const capGross = Number($('#tr-amount').data('capGross') || 0);
                    if (capGross && amount > capGross) {
                        toastr?.error('Amount exceeds max carry-over (gross remaining).');
                        return;
                    }
                }

                try {
                    setBusyTransfer(true);
                    await $.ajax({
                        url: "{{ route('depreciation.mv.transfer') }}",
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        data: {
                            transfer_type: type,
                            from_asset_uuid: fromUUID,
                            to_asset_uuid: toUUID,
                            amount: amount,
                            actual_date: date,
                            note: $('#tr-note').val(),
                            source_type: 'manual'
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
                        console.error('runMonth after transfer failed', e2);
                        toastr?.warning(
                            'Transfer saved, but monthly recompute failed. Please press "Process Current Month".'
                            );
                    }

                    toastr?.success('Transfer saved');
                    $('#tbl-monthly').DataTable().ajax.reload(null, false);
                    $modal.hide();
                } catch (err) {
                    console.error(err);
                    const msg = err?.responseJSON?.message || 'Failed to save transfer';
                    toastr?.error(msg);
                } finally {
                    setBusyTransfer(false);
                }
            });

        })();
    </script>
@endpush
