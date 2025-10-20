@extends('layouts.app')

@section('content')
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <!--begin::Toolbar container-->
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <!--begin::Page title-->
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <!--begin::Title-->
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Assets
                </h1>
                <!--end::Title-->
                <!--begin::Breadcrumb-->
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <!--begin::Item-->
                    <a href="{{ route('assets.index') }}" class="text-muted text-hover-primary">Assets</a>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        Create Asset
                    </li>
                    <!--end::Item-->
                </ul>
                <!--end::Breadcrumb-->

            </div>

        </div>
        <!--end::Toolbar container-->
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <!--begin::Content container-->
        <div id="kt_app_content_container" class="app-container container-fluid">
            <!--begin::Row-->
            <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <div class="card mb-5 mb-xl-8">
                            <!--begin::Header-->
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">Create Assets</span>
                                </h3>

                            </div>
                            <!--end::Header-->
                            <!--begin::Body-->
                            <div class="card-body py-3">
                                <form method="POST" action="{{ route('assets.store') }}" class="card-body"
                                    id="assetCreateForm">
                                    @csrf

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $e)
                                                    <li>{{ $e }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif


                                    {{-- Classification (drives kode_group_category on server) --}}
                                    <div class="row g-5">
                                        <div class="col-12">
                                            <h5 class="fw-bold">Classification</h5>
                                        </div>

                                        {{-- Mode toggle --}}
                                        <div class="row g-5">
                                            <div class="col-12">
                                                <label class="form-label fw-bold required">Parent Mode</label>
                                                <div class="d-flex gap-6">
                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="radio" name="mode"
                                                            value="new" checked>
                                                        <span class="form-check-label">Create NEW parent</span>
                                                    </label>
                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="radio" name="mode"
                                                            value="existing">
                                                        <span class="form-check-label">Use EXISTING parent</span>
                                                    </label>
                                                </div>
                                                <br>
                                            </div>
                                            <div class="col-md-12" id="wrap-parent-picker" style="display:none">
                                                <label class="form-label required">Select Parent</label>
                                                <select name="parent_uuid" id="sel-parent" class="form-select"></select>
                                                <div class="form-text">Search by parent asset code / description</div>
                                            </div>

                                            <div class="col-md-12" id="wrap-classification">
                                                <div class="row g-5">
                                                    <div class="col-md-3">
                                                        <label class="form-label required">Asset Company</label>
                                                        <select name="kode_asset_transaction" id="sel-transaction"
                                                            class="form-select" required></select>
                                                    </div>

                                                    <div class="col-md-3" >
                                                        <label class="form-label required">Asset Class</label>
                                                        <select name="kode_asset_class" id="sel-asset-class"
                                                            class="form-select" required></select>
                                                    </div>

                                                    <div class="col-md-3" style="display:none;">
                                                        <label class="form-label required">Asset Type</label>
                                                        <select name="kode_asset_type" id="sel-asset-type"
                                                            class="form-select" ></select>
                                                    </div>

                                                    <div class="col-md-3" style="display:none;">
                                                        <label class="form-label required">Category</label>
                                                        <select name="kode_category" id="sel-category" class="form-select"
                                                            data-placeholder="Select Asset Type First" ></select>
                                                    </div>

                                                    <div class="col-md-3" style="display:none;">
                                                        <label class="form-label required">Category 2</label>
                                                        <select name="kode_category_2" id="sel-category-2"
                                                            class="form-select" data-placeholder="Select Category First"></select>
                                                    </div>

                                                    <div class="col-md-3" style="display:none;">
                                                        <label class="form-label required">Sub Category</label>
                                                        <select name="kode_sub_category" id="sel-sub-category"
                                                            class="form-select"></select>
                                                    </div>
                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                    <hr class="my-6" />

                                    {{-- Asset core --}}
                                    <div class="row g-5">
                                        <div class="col-12">
                                            <h5 class="fw-bold">Asset</h5>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label required">Description</label>
                                            <input name="description" value="{{ old('description') }}" class="form-control"
                                                required>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required">Location</label>
                                            <select name="kode_location" id="sel-location" class="form-select"
                                                required></select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required">Sumber</label>
                                            <select name="kode_sumber" id="sel-sumber" class="form-select"
                                                required></select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required">Status</label>
                                            <select name="kode_status" id="sel-status" class="form-select" required>
                                            </select>
                                        </div>
                                    </div>

                                    <hr class="my-6" />

                                    {{-- Identifiers (1:1) --}}
                                    <div class="row g-5">
                                        <div class="col-12">
                                            <h5 class="fw-bold">Identifiers</h5>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Maximo</label>
                                            <input name="asset_number_maximo" value="{{ old('asset_number_maximo') }}"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">D365</label>
                                            <input name="asset_number_dynamic_365"
                                                value="{{ old('asset_number_dynamic_365') }}" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Internal</label>
                                            <input name="asset_number_internal" value="{{ old('asset_number_internal') }}"
                                                class="form-control">
                                        </div>
                                    </div>

                                    <hr class="my-6" />

                                    {{-- Assignment (1:1) --}}
                                    <div class="row g-5">
                                        <div class="col-12">
                                            <h5 class="fw-bold required">Assignment</h5>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Owner</label>
                                            <select name="asset_owner" id="sel-owner" class="form-select"
                                                required></select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">User</label>
                                            <select name="asset_user" id="sel-user" class="form-select"
                                                required></select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Maintenance</label>
                                            <select name="asset_maintenance" id="sel-maintenance" class="form-select"
                                                required></select>
                                        </div>
                                    </div>

                                    <hr class="my-6" />

                                    {{-- Value (1:1) --}}
                                    <div class="row g-5">
                                        <div class="col-12">
                                            <h5 class="fw-bold">Value</h5>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">Price</label>
                                            <input type="number" step="0.01" name="price"
                                                value="{{ old('price') }}" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">Quantity</label>
                                            <input type="number" name="quantity" value="{{ old('quantity') }}"
                                                class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">UOM</label>
                                            <select name="kode_uom" id="sel-uom" class="form-select" required></select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Include Tax (VAT-IN)?</label>
                                            <select name="is_pajak" class="form-select">
                                                <option value="0" @selected(old('is_pajak') === '0')>No</option>
                                                <option selected value="1" @selected(old('is_pajak') === '1')>Yes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">Useful Life (Month)</label>
                                            <input type="number" name="useful_life_month"
                                                value="{{ old('useful_life_month') }}" class="form-control" required>
                                        </div>
                                    </div>

                                    <hr class="my-6" />

                                    {{-- Document (1:1) --}}
                                    <div class="row g-5">
                                        <div class="col-12">
                                            <h5 class="fw-bold">Document</h5>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">No PO/Perjanjian/SPK</label>
                                            <input name="no_po_perjanjian_spk" value="{{ old('no_po_perjanjian_spk') }}"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Nota Referensi</label>
                                            <input name="nota_referensi" value="{{ old('nota_referensi') }}"
                                                class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">No Document</label>
                                            <input name="no_document" value="{{ old('no_document') }}"
                                                class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="mt-6 d-flex gap-3">
                                        <button type="submit" class="btn btn-primary">Save</button>
                                        <a href="{{ route('assets.index') }}" class="btn btn-light">Cancel</a>
                                    </div>
                                </form>
                            </div>
                            <!--begin::Body-->
                        </div>
                    </div>
                </div>


            </div>
            <!--end::Row-->
        </div>
        <!--end::Content container-->
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const $form = $('#assetCreateForm');

            $form.on('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Save this asset?',
                    text: 'Please make sure before submitting.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, save',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                }).then((res) => {
                    if (!res.isConfirmed) return;

                    // optional: lock the UI while submitting
                    const $btn = $form.find('button[type="submit"]');
                    $btn.prop('disabled', true).addClass('disabled');

                    Swal.fire({
                        title: 'Saving…',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    // now submit for real
                    e.target.submit();
                });
            });
        });

        function initSelect2(el, url, extra = {}) {
            $(el).select2({
                placeholder: 'Select...',
                width: '100%',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url,
                    dataType: 'json',
                    delay: 150,
                    cache: false,
                    data: function(params) {
                        const base = {
                            q: params.term || '',
                            page: params.page || 1
                        };
                        if (typeof extra === 'function') return Object.assign(base, extra());
                        if (extra && typeof extra === 'object') return Object.assign(base, extra);
                        return base;
                    },
                    processResults: data => data
                }
            });
        }

        // base endpoints (you already defined routes)
        const R = {
            parent: '{{ route('assets.parent.options') }}',
            parentMeta: (uuid) => '{{ route('assets.parent.meta', ':id') }}'.replace(':id', uuid),
            sumber: '{{ route('master.sumber.options') }}',
            trx: '{{ route('master.transaction.options') }}',
            type: '{{ route('master.asset_type.options') }}',
            cat: '{{ route('master.category.options') }}',
            cat2: '{{ route('master.category_2.options') }}',
            subcat: '{{ route('master.sub_category.options') }}',
            location: '{{ route('master.location.options') }}',
            uom: '{{ route('master.uom.options') }}',
            status: '{{ route('master.status.options') }}',
            aclass: '{{ route('master.asset_class.options') }}',
            usercode: '{{ route('master.user_code.options') }}',
        };


        $(function() {
            // Independent dropdowns
            initSelect2('#sel-parent', R.parent);
            initSelect2('#sel-sumber', R.sumber);
            initSelect2('#sel-transaction', R.trx);
            initSelect2('#sel-asset-type', R.type);
            initSelect2('#sel-location', R.location);
            initSelect2('#sel-uom', R.uom);
            initSelect2('#sel-status', R.status, {
                type: 'Asset'
            });
            initSelect2('#sel-asset-class', R.aclass);
            initSelect2('#sel-owner', R.usercode);
            initSelect2('#sel-user', R.usercode);
            initSelect2('#sel-maintenance', R.usercode);
            initSelect2('#sel-sub-category', R.subcat);
            initSelect2('#sel-asset-type', R.type);
            initSelect2('#sel-category', R.cat, () => {
                const p = {
                    kode_asset_type: $('#sel-asset-type').val() || '',
                    q: ''
                };
                console.log('cat->extra', p);
                return p;
            });
            initSelect2('#sel-category-2', R.cat2, () => {
                const p = {
                    kode_category: $('#sel-category').val() || '',
                    q: ''
                };
                console.log('cat2->extra', p);
                return p;
            });

            $('#sel-category').on('select2:opening', function(e) {
                if (!$('#sel-asset-type').val()) {
                    e.preventDefault();
                }
            });
            $('#sel-category-2').on('select2:opening', function(e) {
                if (!$('#sel-category').val()) {
                    e.preventDefault();
                }
            });

            // clear children on parent change
            $('#sel-asset-type').on('change', function() {
                $('#sel-category').val(null).trigger('change');
                $('#sel-category-2').val(null).trigger('change');
            });
            $('#sel-category').on('change', function() {
                $('#sel-category-2').val(null).trigger('change');
            });

            preloadOld('#sel-sumber', '{{ old('kode_sumber') }}', R.sumber);
            preloadOld('#sel-transaction', '{{ old('kode_asset_transaction') }}', R.trx);
            preloadOld('#sel-asset-type', '{{ old('kode_asset_type') }}', R.type);
            preloadOld('#sel-category', '{{ old('kode_category') }}', R.cat);
            preloadOld('#sel-category-2', '{{ old('kode_category_2') }}', R.cat2);
            preloadOld('#sel-sub-category', '{{ old('kode_sub_category') }}', R.subcat);
            preloadOld('#sel-status', '{{ old('kode_status') }}', R.status);
            preloadOld('#sel-location', '{{ old('kode_location') }}', R.location);
            preloadOld('#sel-uom', '{{ old('kode_uom') }}', R.uom);
            preloadOld('#sel-asset-class', '{{ old('kode_asset_class') }}', R.aclass);
            preloadOld('#sel-owner', '{{ old('asset_owner') }}', R.usercode);
            preloadOld('#sel-user', '{{ old('asset_user') }}', R.usercode);
            preloadOld('#sel-maintenance', '{{ old('asset_maintenance') }}', R.usercode);
            // Mode toggle
            $('input[name="mode"]').on('change', function() {
                const mode = this.value;
                if (mode === 'existing') {
                    $('#wrap-parent-picker').show();
                    $('#wrap-classification').hide();

                    // Optional: clear classification inputs so backend won’t validate them
                    $('#sel-transaction, #sel-asset-type, #sel-category, #sel-category-2, #sel-sub-category, #sel-asset-class')
                        .val(null).trigger('change');
                } else {
                    $('#wrap-parent-picker').hide();
                    $('#wrap-classification').show();
                    $('#sel-parent').val(null).trigger('change');
                }
            });

            // When user picks a parent → autofill classification
            $('#sel-parent').on('select2:select', function(e) {
                const id = e.params.data.id;
                $.getJSON(R.parentMeta(id), function(meta) {
                    const cl = meta.classification || {};
                    setSelectValue('#sel-transaction', cl.kode_asset_transaction);
                    setSelectValue('#sel-asset-type', cl.kode_asset_type);
                    setSelectValue('#sel-category', cl.kode_category);
                    setSelectValue('#sel-category-2', cl.kode_category_2);
                    setSelectValue('#sel-sub-category', cl.kode_sub_category);
                });
            });

            function setSelectValue(sel, code) {
                if (!code) return;
                const $el = $(sel);
                const opt = new Option(code, code, true, true);
                $el.append(opt).trigger('change');
            }
        });

        function preloadOld(selector, id, url, extraFn) {
            if (!id) return;
            const params = {
                q: id
            };
            if (typeof extraFn === 'function') Object.assign(params, extraFn());
            $.get(url, params, function(resp) {
                const item = (resp.results || []).find(x => String(x.id) === String(id)) || {
                    id,
                    text: id
                };
                const opt = new Option(item.text, item.id, true, true);
                $(selector).append(opt).trigger('change');
            });
        }
    </script>
@endpush
