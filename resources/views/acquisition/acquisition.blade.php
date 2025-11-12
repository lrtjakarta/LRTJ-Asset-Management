@extends('layouts.app')

@section('title', 'Acquisition History')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Acquisition
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Transaction</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Acquisition</li>
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
                        <div class="col-md-3">
                            <label class="form-label">PIC</label>
                            <select id="f-pic" class="form-select"></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pajak</label>
                            <select id="f-pajak" class="form-select">
                                <option value="">All</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Capitalization From</label>
                            <input type="date" id="f-cap-from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Capitalization To</label>
                            <input type="date" id="f-cap-to" class="form-control">
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
                            <span class="card-label fw-bold fs-3 mb-1">Acquisition History</span>
                        </h3>
                    </div>
                    @canAction('ACQUISITION','C')
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <button id="btn-open-acq" class="btn btn-sm btn-danger">
                            <i class="ki-duotone ki-plus fs-2"></i>Create or Update
                        </button>
                    </div>
                    @endcanAction
                </div>

                <div class="card-body">
                    <table id="tbl-acq-global"
                        class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="min-w-150px">Transaction Number</th>
                                <th class="min-w-200px">Asset</th>
                                <th class="min-w-100px">Qty</th>
                                <th class="min-w-150px">UOM</th>
                                <th class="min-w-200px">Price</th>
                                <th class="min-w-200px">VAT In</th>
                                <th class="min-w-200px">Total</th>
                                <th class="min-w-150px">Actual Date</th>
                                <th class="min-w-200px">Capitalization Date</th>
                                <th class="min-w-200px">PIC</th>
                                <th class="min-w-250px">Note</th>
                                <th class="min-w-150px">Created At</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Global Acquisition Modal --}}
    <div class="modal fade" id="modal-acq-global" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Acquisition — Global</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="form-acq-global" autocomplete="off">
                    @csrf
                    <input type="hidden" name="asset_uuid" id="acq-asset-uuid">

                    <div class="modal-body py-4">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label required">Asset</label>
                                <select id="acq-asset-select" class="form-select" style="width:100%"></select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Quantity</label>
                                <input type="number" step="0.001" min="0" class="form-control"
                                    id="acq-quantity" name="quantity">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">UOM</label>
                                <select id="acq-uom" name="kode_uom" class="form-select" style="width:100%"></select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Price</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="acq-price"
                                    name="price">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Useful Life (Months)</label>
                                <input type="number" step="1" min="0" class="form-control"
                                    id="acq-life-month" name="useful_life_month">
                                <div class="form-text">Year will be auto-calculated</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Include Tax (VAT-IN)?</label>
                                <select class="form-select" id="acq-is-pajak" name="is_pajak">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Actual Date</label>
                                <input type="date" class="form-control" id="acq-actual-date" name="actual_date">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required">Capitalization Date</label>
                                <input type="date" class="form-control" id="acq-cap-date" name="capitalization_date">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea class="form-control" id="acq-note" name="note" rows="2" maxlength="1000"
                                    placeholder="Optional note"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btn-save-acq-global" class="btn btn-primary">
                            <span class="indicator-label">Save</span>
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
            const money = v => new Intl.NumberFormat().format(Number(v ?? 0));
            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));
            const $modal = new bootstrap.Modal(document.getElementById('modal-acq-global'));
            const $form = $('#form-acq-global');
            const $btnSave = $('#btn-save-acq-global');

            const setBusy = b => {
                if (b) $btnSave.attr('data-kt-indicator', 'on').prop('disabled', true);
                else $btnSave.removeAttr('data-kt-indicator').prop('disabled', false);
            };
            $('#f-pic').select2({
                placeholder: 'Select PIC',
                width: '100%',
                allowClear: true,
                ajax: {
                    url: "{{ route('users.options') }}",
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1
                    }),
                    processResults: d => d
                }
            });

            var tbl = $('#tbl-acq-global').DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 400,
                scrollX: true,
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                ajax: {
                    url: "{{ route('acquisition.dt') }}",
                    data: d => {
                        d.pic = $('#f-pic').val() || '';
                        d.pajak = $('#f-pajak').val() || '';
                        d.cap_from = $('#f-cap-from').val() || '';
                        d.cap_to = $('#f-cap-to').val() || '';
                        d.asset = $('#f-asset').val() || '';
                    }
                },
                order: [
                    [11, 'desc']
                ],
                columns: [{
                        data: 'acq_code',
                        name: 'h.acq_code'
                    },
                    {
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
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'kode_uom',
                        name: 'kode_uom'
                    },
                    {
                        data: 'price',
                        render: money
                    },
                    {
                        data: 'vat_in',
                        render: money
                    },
                    {
                        data: 'total',
                        render: money,
                        className: 'text-end'
                    },
                    {
                        data: 'actual_date',
                        name: 'actual_date'
                    },
                    {
                        data: 'capitalization_date',
                        name: 'capitalization_date'
                    },
                    {
                        data: 'pic_request_uid',
                        name: 'pic_request_uid'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'created_at_fmt',
                        name: 'h.created_at'
                    },
                ]
            });
            $('#btnFilter').on('click', function(e) {
                e.preventDefault();
                tbl.ajax.reload();
            });

            $('#btnReset').on('click', function(e) {
                e.preventDefault();
                $('#f-pic').val(null).trigger('change');
                $('#f-pajak').val('');
                $('#f-cap-from').val('');
                $('#f-cap-to').val('');
                $('#f-asset').val('');
                tbl.ajax.reload();
            });

            $('#btnExport').on('click', function(e) {
                e.preventDefault();

                const params = $.param({
                    pic: $('#f-pic').val() || '',
                    pajak: $('#f-pajak').val() || '',
                    cap_from: $('#f-cap-from').val() || '',
                    cap_to: $('#f-cap-to').val() || '',
                    asset: $('#f-asset').val() || '',
                });

                window.location = "{{ route('export.acquisition') }}" + '?' + params;
            });

            $('#acq-asset-select').select2({
                dropdownParent: $('#modal-acq-global'),
                placeholder: 'Search asset code/name…',
                allowClear: true,
                ajax: {
                    url: "{{ route('assets.options') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term || '',
                        page: params.page || 1
                    }),
                    processResults: data => data
                }
            }).on('select2:select', function(e) {
                const assetUuid = e.params.data.id;
                $('#acq-asset-uuid').val(assetUuid);
                loadLatest(assetUuid);
            }).on('select2:clear', function() {
                $('#acq-asset-uuid').val('');
                clearFormValues();
            });

            $('#acq-uom').select2({
                dropdownParent: $('#modal-acq-global'),
                placeholder: 'Choose UOM…',
                ajax: {
                    url: "{{ route('master.uom.options') }}",
                    dataType: 'json',
                    delay: 150,
                    data: params => ({
                        q: params.term || ''
                    }),
                    processResults: data => data
                }
            });

            function clearFormValues() {
                $('#acq-quantity').val('');
                $('#acq-price').val('');
                $('#acq-life-month').val('');
                $('#acq-is-pajak').val('0');
                $('#acq-actual-date').val('');
                $('#acq-cap-date').val('');
                $('#acq-note').val('');
                $('#acq-uom').val(null).trigger('change');
            }

            function loadLatest(assetUuid) {
                if (!assetUuid) {
                    clearFormValues();
                    return;
                }

                $.getJSON("{{ route('acquisition.latest', ':uuid') }}".replace(':uuid', assetUuid))
                    .done(res => {
                        clearFormValues();
                        if (!res || !res.exists || !res.value) return;

                        const v = res.value;

                        $('#acq-quantity').val(v.quantity ?? '');
                        $('#acq-price').val(v.price ?? '');
                        $('#acq-life-month').val(v.useful_life_month ?? '');
                        $('#acq-is-pajak').val(v.is_pajak ? '1' : '0');
                        if (v.actual_date) $('#acq-actual-date').val(String(v.actual_date).substring(0, 10));
                        if (v.capitalization_date) $('#acq-cap-date').val(String(v.capitalization_date).substring(0,
                            10));

                        if (v.kode_uom) {
                            const opt = new Option(v.kode_uom, v.kode_uom, true, true);
                            $('#acq-uom').append(opt).trigger('change');
                        }
                    })
                    .fail(() => {
                        clearFormValues();
                    });
            }

            $('#btn-open-acq').on('click', function() {
                $form[0].reset();
                $('#acq-asset-uuid').val('');
                $('#acq-asset-select').val(null).trigger('change');
                $('#acq-uom').val(null).trigger('change');
                clearFormValues();
                $modal.show();
            });

            // Submit
            $form.on('submit', function(e) {
                e.preventDefault();

                const assetUuid = $('#acq-asset-uuid').val();
                if (!assetUuid) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Asset required',
                        text: 'Please choose an asset first.'
                    });
                    return;
                }

                setBusy(true);
                $.ajax({
                    url: "{{ route('acquisition.global.save') }}",
                    type: 'POST',
                    data: $form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                }).done(res => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: res?.message || 'Acquisition saved.'
                    });
                    tbl.ajax.reload(null, false);
                    $modal.hide();
                }).fail(xhr => {
                    let msg = xhr.responseJSON?.message || 'Failed to save acquisition.';
                    const errs = xhr.responseJSON?.errors;
                    if (errs) {
                        const firstKey = Object.keys(errs)[0];
                        if (firstKey && errs[firstKey][0]) msg = errs[firstKey][0];
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg
                    });
                }).always(() => setBusy(false));
            });
        })();
    </script>
@endpush
