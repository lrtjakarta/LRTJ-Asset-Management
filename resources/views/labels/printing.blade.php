@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Printing Label</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">Printing Label</li>
                </ul>
            </div>

        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-fluid">

            {{-- Filter sederhana, re-use parameter asset_q --}}
            <div class="card mb-5">
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
                    </div>
                </div>
                <div class="card-footer">
                    <button id="btn-apply-filter" class="btn btn-sm btn-danger me-2">
                        Apply Filter
                    </button>
                    <button id="btn-reset-filter" class="btn btn-sm btn-light-danger me-2">
                        Reset
                    </button>
                </div>
            </div>

            {{-- Tabel Asset --}}
            <div class="card">
                <div class="card-body">
                    <table id="printAssetsTable" class="table table-row-bordered table-striped gy-4 align-middle">
                        <thead>
                            <tr>
                                <th style="width:40px;">
                                    <input type="checkbox" id="chk-all">
                                </th>
                                <th>Asset Code</th>
                                <th>Description</th>
                                <th>Location</th>
                                <th>Owner</th>
                                <th>User</th>
                                <th>Maintenance</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <button id="btn-print-selected" class="btn btn-primary">
                            Print QR/Barcode
                        </button>
                        <button id="btn-print-selected-rfid" class="btn btn-danger">
                            Print RFID (LAN)
                        </button>
                    </div>
                </div>
            </div>

            {{-- Form hidden untuk POST ke /label-printing/print --}}
            <form id="print-form" method="POST" action="{{ route('label.print') }}" target="_blank" style="display:none;">
                @csrf
                <input type="hidden" name="asset_uuids" id="input-asset-uuids">
            </form>
            {{-- Form hidden untuk POST ke /label-printing/print-rfid-lan (raw SBPL ke printer LAN) --}}
            <form id="print-rfid-form" method="POST" action="{{ route('label.print-rfid-lan') }}" style="display:none;">
                @csrf
                <input type="hidden" name="asset_uuids" id="input-asset-uuids-rfid">
                <input type="hidden" name="label_size" id="input-label-size-rfid" value="100x40">
            </form>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function($) {
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
            const PRINT_URL = @json(route('label.print'));
            const SHOW_URL_TPL = @json(route('assets.detail', '__UUID__'));

            const table = $('#printAssetsTable').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: '{{ route('assets.datatable') }}',
                    data: function(d) {
                        d.asset_class = $('#flt-asset-class').val() || '';
                        d.transaction = $('#flt-transaction').val() || '';
                        d.location = $('#flt-location').val() || '';
                        d.status = $('#flt-status').val() || '';
                        d.owner = $('#flt-owner').val() || '';
                        d.user = $('#flt-user').val() || '';
                        d.maintenance = $('#flt-maintenance').val() || '';
                        d.sumber = $('#flt-sumber').val() || '';
                        d.asset_q = $('#flt-asset-q').val() || '';
                    }
                },
                scrollX: true,
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
                    "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
                    "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                order: [
                    [1, 'asc']
                ],
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            if (type !== 'display') return '';
                            return `<input type="checkbox" class="row-select" value="${row.uuid}">`;
                        }
                    }, {
                        data: 'asset_code',
                        name: 'a.asset_code',
                        "render": function(data, type, row, meta) {
                            if (type !== 'display') return data;

                            const url = SHOW_URL_TPL.replace('__UUID__', encodeURIComponent(row.uuid));
                            return `<a href="${url}" class="text-primary fw-semibold">${data ?? ''}</a>`;
                        }
                    },
                    {
                        data: 'description',
                        name: 'a.description'
                    },
                    {
                        data: 'kode_location_label',
                        name: 'kode_location_label'
                    },
                    {
                        data: 'asset_owner_label',
                        name: 'asset_owner_label'
                    },
                    {
                        data: 'asset_user_label',
                        name: 'asset_user_label'
                    },
                    {
                        data: 'asset_maintenance_label',
                        name: 'asset_maintenance_label'
                    },
                ]
            });
            $('#btn-apply-filter').on('click', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Reset filter: kosongkan semua dan reload
            $('#btn-reset-filter').on('click', function(e) {
                e.preventDefault();
                $('.asset-filter').val(null).trigger('change');
                $('#flt-asset-q').val('');
                table.ajax.reload();
            });



            // Select All checkbox
            $('#chk-all').on('change', function() {
                const checked = this.checked;
                $('#printAssetsTable').find('input.row-select').prop('checked', checked);
            });

            table.on('draw', function() {
                $('#chk-all').prop('checked', false);
            });

            // Tombol Print Selected
            function collectSelectedIds() {
                const ids = [];
                $('#printAssetsTable').find('input.row-select:checked').each(function() {
                    ids.push(this.value);
                });
                return ids;
            }

            // Tombol Print Selected (HTML)
            $('#btn-print-selected').on('click', function() {
                const ids = collectSelectedIds();

                if (!ids.length) {
                    Swal.fire('Tidak ada asset', 'Pilih minimal satu asset untuk dicetak label.', 'warning');
                    return;
                }

                $('#input-asset-uuids').val(JSON.stringify(ids));
                $('#print-form').submit(); // 
            });

            // Tombol Print RFID (LAN)
            // Tombol Print RFID (LAN) + pilih ukuran
            $('#btn-print-selected-rfid').on('click', async function() {
                const ids = collectSelectedIds();

                if (!ids.length) {
                    Swal.fire('Tidak ada asset', 'Pilih minimal satu asset untuk print RFID.', 'warning');
                    return;
                }

                const {
                    value: size
                } = await Swal.fire({
                    title: 'Pilih ukuran label RFID',
                    input: 'radio',
                    inputOptions: {
                        '100x40': '100 x 40 mm (default)',
                        '60x25': '60 x 25 mm'
                    },
                    inputValue: '100x40',
                    showCancelButton: true,
                    confirmButtonText: 'Print',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    inputValidator: (v) => !v ? 'Pilih salah satu ukuran dulu.' : undefined
                });

                if (!size) return;

                $('#input-asset-uuids-rfid').val(JSON.stringify(ids));
                $('#input-label-size-rfid').val(size);
                $('#print-rfid-form').submit();
            });


            initMasterSelect2(
                $('#flt-asset-class'),
                "{{ route('master.asset_class.options') }}",
                'All Asset Class'
            );

            initMasterSelect2(
                $('#flt-transaction'),
                "{{ route('master.transaction.options') }}",
                'All Company'
            );

            initMasterSelect2(
                $('#flt-location'),
                "{{ route('master.location.options') }}",
                'All Location'
            );

            initMasterSelect2(
                $('#flt-status'),
                "{{ route('master.status.options') }}",
                'All Status'
            );

            initMasterSelect2(
                $('#flt-sumber'),
                "{{ route('master.sumber.options') }}",
                'All Sumber'
            );

            initMasterSelect2(
                $('#flt-owner'),
                "{{ route('master.user_code.options') }}",
                'All Owner'
            );

            initMasterSelect2(
                $('#flt-user'),
                "{{ route('master.user_code.options') }}",
                'All User'
            );

            initMasterSelect2(
                $('#flt-maintenance'),
                "{{ route('master.user_code.options') }}",
                'All Maintenance'
            );

        })(jQuery);
    </script>
@endpush
