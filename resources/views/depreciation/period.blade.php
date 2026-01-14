@php
    use Carbon\Carbon;
@endphp

@extends('layouts.app')
@section('title', 'Depreciation Period')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Depreciation Period
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Depreciation</li>
                    <li class="breadcrumb-item text-muted">Period</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-columnfluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            <div class="card mb-6">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Year</label>
                            <select id="f-year" class="form-select">
                                @for ($y = $minYear; $y <= $maxYear; $y++)
                                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}
                                    </option>
                                @endfor
                            </select>
                            @if ($minYear === $maxYear)
                                <small class="text-muted">Only year available: {{ $minYear }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    @php
                        $isDecember = $isDecember ?? now()->month === 12;
                        $nowYear = $nowYear ?? now()->year;
                    @endphp

                    <div class="d-flex gap-2">
                        <button id="btnApply" class="btn btn-danger btn-sm">Apply</button>
                        <a href="{{ route('depreciation.period.index') }}" class="btn btn-light-danger btn-sm">Reset</a>
                        @if (!empty($isLedgerEmpty) && $isLedgerEmpty)
                            @canAction('DEPRECIATION','C')
                            <button type="button" id="btn-init-first-run" class="btn btn-danger btn-sm me-2">
                                Initialize / First Run
                            </button>
                            @endcanAction
                        @endif
                        <button type="button" id="btn-open-build-year" class="btn btn-danger btn-sm"
                            @disabled(!$isDecember)
                            title="{{ $isDecember ? '' : 'Build Year hanya bisa dilakukan di bulan Desember' }}">
                            Build Year
                        </button>

                        <button type="button" id="btn-open-rollback-year" class="btn btn-light-danger btn-sm d-none">
                            Rollback Build Year
                        </button>

                        <span id="year-lock-badge" class="d-none badge badge-light">-</span>
                    </div>

                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table id="tbl-period"
                        class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                        <thead class="table-light">
                            <tr>
                                <th class="min-w-200px">Month</th>
                                <th class="min-w-200px">Depr Code</th>
                                <th class="min-w-150px text-end">Assets</th>
                                <th class="min-w-200px text-end">Total Depreciation</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total (Year)</th>
                                <th id="year-total" class="text-end text-danger"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
@push('scripts')
    <script>
        (function() {
            const DT_URL = @json(route('depreciation.dt.period'));
            const MONTHLY_URL = @json(route('depreciation.index'));
            const INIT_URL = @json(route('depreciation.init.first-run'));

            const ROUTES = {
                yearStatus: @json(route('depreciation.year.status')),
                buildYear: @json(route('depreciation.build.year')),
                rollbackYear: @json(route('depreciation.rollback.year')),
            };

            const IS_DECEMBER = Boolean(@json($isDecember ?? now()->month === 12));

            // CSRF token
            const csrf = () => $('meta[name="csrf-token"]').attr('content') || @json(csrf_token());

            const money2 = v => new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(v ?? 0));

            function currentYear() {
                return Number($('#f-year').val() || 0);
            }

            // biar kalau ada error langsung keliatan
            $.fn.dataTable.ext.errMode = 'none';

            // ===== DataTable =====
            const tbl = $('#tbl-period').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                lengthChange: false,
                pageLength: 12,
                order: [
                    [0, 'asc']
                ],
                ajax: {
                    url: DT_URL,
                    data: d => {
                        d.year = currentYear();
                    },
                    error: function(xhr) {
                        console.error('DT period error:', xhr);
                        const msg = xhr?.responseJSON?.message || xhr?.responseText ||
                            'Failed to load period data';
                        toastr?.error(msg);
                    }
                },
                columns: [{
                        data: 'period',
                        name: 'period',
                        render: function(iso, type) {
                            if (!iso) return '';
                            if (type === 'sort' || type === 'type') return iso;
                            const d = new Date(iso);
                            return d.toLocaleDateString('en-US', {
                                month: 'long',
                                year: 'numeric'
                            });
                        }
                    },
                    {
                        data: 'depr_code_display',
                        name: 'depr_code_display',
                        render: function(data, type, row) {
                            if (type !== 'display') return data;

                            const badge = row.is_processed ?
                                `<span class="badge badge-light-success ms-2">Processed</span>` :
                                `<span class="badge badge-light-warning ms-2">Not processed</span>`;

                            if (!row.can_click) {
                                return `<span class="text-muted">${data ?? ''}</span>${badge}`;
                            }

                            const ym = row.period_ym; // YYYY-MM
                            const url = MONTHLY_URL +
                                `?period_from=${encodeURIComponent(ym)}&period_to=${encodeURIComponent(ym)}`;

                            return `<a href="${url}" class="text-primary fw-semibold">${data ?? ''}</a>${badge}`;
                        }
                    },
                    {
                        data: 'asset_count',
                        name: 'asset_count',
                        className: 'text-end'
                    },
                    {
                        data: 'total_depr',
                        name: 'total_depr',
                        className: 'text-end',
                        render: money2
                    },
                ]
            });

            tbl.on('xhr', function() {
                const json = tbl.ajax.json() || {};
                const rows = json.data || [];
                const sum = rows.reduce((acc, r) => acc + Number(r.total_depr || 0), 0);
                $('#year-total').text(money2(sum));
            });

            // ===== Year lock UI =====
            function refreshYearLockUI() {
                const y = currentYear();
                if (!y) return;

                $.get(ROUTES.yearStatus, {
                        year: y
                    })
                    .done((resp) => {
                        const locked = !!resp.locked;

                        $('#year-lock-badge')
                            .toggleClass('badge-light-success', !locked)
                            .toggleClass('badge-light-danger', locked)
                            .text(locked ? `LOCKED ${y}` : `UNLOCKED ${y}`);

                        // build: hanya desember dan year belum locked
                        $('#btn-open-build-year').prop('disabled', !IS_DECEMBER || locked);

                        // rollback: hanya tampil kalau locked
                        $('#btn-open-rollback-year').toggleClass('d-none', !locked);
                    })
                    .fail(() => {
                        $('#year-lock-badge').addClass('d-none');
                        $('#btn-open-rollback-year').addClass('d-none');
                        $('#btn-open-build-year').prop('disabled', !IS_DECEMBER);
                    });
            }

            // ===== Events =====
            $('#btnApply').on('click', function() {
                tbl.ajax.reload();
                refreshYearLockUI();
            });

            $('#f-year').on('change', function() {
                tbl.ajax.reload();
                refreshYearLockUI();
            });

            // ===== Build Year =====
            $('#btn-open-build-year').on('click', function() {
                const y = currentYear();

                Swal.fire({
                    title: `Build Year ${y}?`,
                    text: 'Syarat: hanya boleh bulan Desember dan periode Desember tahun tsb harus sudah diproses. Setelah build, year akan LOCKED.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes, build',
                    cancelButtonText: 'Cancel',
                }).then((r) => {
                    if (!r.isConfirmed) return;

                    const $btn = $('#btn-open-build-year');
                    $btn.prop('disabled', true).attr('data-kt-indicator', 'on');

                    $.ajax({
                        url: ROUTES.buildYear,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf()
                        },
                        data: {
                            year: y
                        },
                    }).done((resp) => {
                        toastr?.success(resp?.message || `Year ${y} built & locked.`);
                        tbl.ajax.reload(null, false);
                        refreshYearLockUI();
                    }).fail((xhr) => {
                        const resp = xhr.responseJSON || {};
                        if (resp.code === 'BUILD_YEAR_ONLY_DECEMBER') {
                            Swal.fire('Tidak bisa', resp.message ||
                                'Build Year hanya boleh dilakukan di bulan Desember.',
                                'warning');
                            return;
                        }
                        if (resp.code === 'DECEMBER_NOT_PROCESSED') {
                            const extra = resp.need_period_text ?
                                `\nNeed: ${resp.need_period_text}` : '';
                            Swal.fire('Tidak bisa', (resp.message ||
                                'Desember harus diproses dulu.') + extra, 'warning');
                            return;
                        }
                        if (resp.code === 'YEAR_LOCKED') {
                            Swal.fire('Terkunci', resp.message || 'Year sudah locked.',
                                'warning');
                            return;
                        }
                        Swal.fire('Failed', resp.message || 'Build year failed', 'error');
                    }).always(() => {
                        $btn.prop('disabled', false).removeAttr('data-kt-indicator');
                    });
                });
            });

            // ===== Rollback Year =====
            $('#btn-open-rollback-year').on('click', function() {
                const y = currentYear();

                Swal.fire({
                    title: `Rollback Build Year ${y}?`,
                    text: 'Year lock akan dibuka (UNLOCK) dan yearly summary tahun ini akan dihapus.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonText: 'Cancel',
                    confirmButtonText: 'Yes, rollback',
                }).then((r) => {
                    if (!r.isConfirmed) return;

                    $.ajax({
                        url: ROUTES.rollbackYear,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf()
                        },
                        data: {
                            year: y
                        },
                    }).done((resp) => {
                        toastr?.success(resp?.message || `Year ${y} unlocked.`);
                        tbl.ajax.reload(null, false);
                        refreshYearLockUI();
                    }).fail((xhr) => {
                        Swal.fire('Failed', xhr.responseJSON?.message || 'Rollback failed',
                            'error');
                    });
                });
            });

            // ===== Init First Run =====
            $('#btn-init-first-run').on('click', function() {
                Swal.fire({
                    title: 'Initialize depreciation?',
                    text: 'System will process the first eligible period automatically (first run).',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then(async (res) => {
                    if (!res.isConfirmed) return;

                    const $btn = $('#btn-init-first-run');
                    try {
                        $btn.prop('disabled', true).attr('data-kt-indicator', 'on');

                        const resp = await $.ajax({
                            url: INIT_URL,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf()
                            },
                            data: {}
                        });

                        if (resp?.ok === false) {
                            toastr?.error(resp?.message || 'Initialize failed');
                            return;
                        }

                        toastr?.success(`Initialized: ${resp?.period_month || ''}`);

                        const ym = resp.period_month;
                        window.location = MONTHLY_URL +
                            `?period_from=${encodeURIComponent(ym)}&period_to=${encodeURIComponent(ym)}`;
                    } catch (e) {
                        const msg = e?.responseJSON?.message || 'Initialize failed';
                        toastr?.error(msg);
                        console.error(e);
                    } finally {
                        $btn.prop('disabled', false).removeAttr('data-kt-indicator');
                    }
                });
            });

            // initial load
            refreshYearLockUI();
        })();
    </script>
@endpush
