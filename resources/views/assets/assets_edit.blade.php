@extends('layouts.app')

@section('content')
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">Assets</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('assets.index') }}" class="text-muted text-hover-primary">Assets</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Edit {{ $asset->asset_code }} ({{ $asset->description }})</li>
                </ul>
            </div>

            {{-- <div class="d-flex align-items-center gap-3">
                <a href="{{ route('assets.detail', $asset->uuid) }}" class="btn btn-light">Back to Detail</a>
            </div> --}}
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="row g-5 gx-xl-10 mb-5 mb-xl-10">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <div class="card mb-5 mb-xl-8">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-3 mb-1">
                                        Edit Asset — <span class="text-primary">{{ $asset->asset_code }}</span>
                                    </span>
                                    <span class="fs-7 text-muted">Group: {{ $asset->kode_group_category }} &middot; Parent:
                                        {{ $asset->asset_number_parent }} &middot; Child:
                                        {{ $asset->asset_number_child }}</span>
                                </h3>
                            </div>

                            <div class="card-body py-3">
                                <form method="POST" action="{{ route('assets.update', $asset->uuid) }}" class="card-body"
                                    id="assetEditForm">
                                    @csrf
                                    @method('PUT')

                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $e)
                                                    <li>{{ $e }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    {{-- Classification --}}
                                    @php
                                        // If this asset is a child (child != '00'), default mode = existing
                                        $defaultMode = $asset->asset_number_child !== '00' ? 'existing' : 'new';
                                    @endphp
                                    <input type="hidden" value="{{ old('uuid', $asset->uuid) }}" name="uuid">
                                    <div class="row g-5">
                                        <div class="col-12">
                                            <h5 class="fw-bold">Classification</h5>
                                        </div>
                                        {{-- Mode toggle --}}
                                        <div class="col-12">
                                            <label class="form-label fw-bold required">Parent Mode</label>
                                            <div class="d-flex gap-6">
                                                <label class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="radio" name="mode"
                                                        value="new" {{ $defaultMode === 'new' ? 'checked' : '' }}>
                                                    <span class="form-check-label">Create NEW parent</span>
                                                </label>
                                                <label class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="radio" name="mode"
                                                        value="existing"
                                                        {{ $defaultMode === 'existing' ? 'checked' : '' }}>
                                                    <span class="form-check-label">Use EXISTING parent</span>
                                                </label>
                                            </div>
                                            <br>
                                        </div>

                                        <div class="col-md-12" id="wrap-parent-picker" style="display:none">
                                            <label
                                                class="form-label  {{ $defaultMode === 'existing' ? 'required' : '' }}">Select
                                                Parent</label>
                                            <select name="parent_uuid" id="sel-parent" class="form-select"></select>
                                            <div class="form-text">Search by parent asset code / description</div>
                                        </div>
                                        <div class="col-md-12" id="wrap-classification">
                                            <div class="row g-5">
                                                <div class="col-md-3">
                                                    <label class="form-label required">Asset Transaction</label>
                                                    <select name="kode_asset_transaction" id="sel-transaction"
                                                        class="form-select" required></select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label required">Asset Type</label>
                                                    <select name="kode_asset_type" id="sel-asset-type" class="form-select"
                                                        required></select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label required">Category</label>
                                                    <select name="kode_category" id="sel-category" class="form-select"
                                                        data-placeholder="Select Asset Type First" required></select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label required">Category 2</label>
                                                    <select name="kode_category_2" id="sel-category-2" class="form-select"
                                                        data-placeholder="Select Category First" required></select>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label required">Sub Category</label>
                                                    <select name="kode_sub_category" id="sel-sub-category"
                                                        class="form-select" required></select>
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
                                            <input name="description" value="{{ old('description', $asset->description) }}"
                                                class="form-control" required>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required">Asset Class</label>
                                            <select name="kode_asset_class" id="sel-asset-class" class="form-select"
                                                required></select>
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
                                            <select name="kode_status" id="sel-status" class="form-select"
                                                required></select>
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
                                            <input name="asset_number_maximo"
                                                value="{{ old('asset_number_maximo', $asset->identifiers?->asset_number_maximo) }}"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">D365</label>
                                            <input name="asset_number_dynamic_365"
                                                value="{{ old('asset_number_dynamic_365', $asset->identifiers?->asset_number_dynamic_365) }}"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Internal</label>
                                            <input name="asset_number_internal"
                                                value="{{ old('asset_number_internal', $asset->identifiers?->asset_number_internal) }}"
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
                                                value="{{ old('price', $asset->value?->price) }}" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">Quantity</label>
                                            <input type="number" name="quantity"
                                                value="{{ old('quantity', $asset->value?->quantity) }}"
                                                class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">UOM</label>
                                            <select name="kode_uom" id="sel-uom" class="form-select" required></select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Include Tax (VAT-IN)?</label>
                                            @php $isPajak = old('is_pajak', (int)($asset->value?->is_pajak ?? 1)); @endphp
                                            <select name="is_pajak" class="form-select">
                                                <option value="0" @selected($isPajak === 0)>No</option>
                                                <option value="1" @selected($isPajak === 1)>Yes</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required">Useful Life (Month)</label>
                                            <input type="number" name="useful_life_month"
                                                value="{{ old('useful_life_month', $asset->value?->useful_life_month) }}"
                                                class="form-control" required>
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
                                            <input name="no_po_perjanjian_spk"
                                                value="{{ old('no_po_perjanjian_spk', $asset->documents?->no_po_perjanjian_spk) }}"
                                                class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">Nota Referensi</label>
                                            <input name="nota_referensi"
                                                value="{{ old('nota_referensi', $asset->documents?->nota_referensi) }}"
                                                class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required">No Document</label>
                                            <input name="no_document"
                                                value="{{ old('no_document', $asset->documents?->no_document) }}"
                                                class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="mt-6 d-flex gap-3">
                                        <button type="submit" class="btn btn-primary">Update</button>
                                        <a href="{{ route('assets.detail', $asset->uuid) }}"
                                            class="btn btn-light">Cancel</a>
                                    </div>
                                </form>
                            </div>
                            {{-- /Body --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    @php
        $current = [
            'mode' => $defaultMode,
            'parent_uuid' => $parentUuid,
            'trx' => optional($asset->classification)->kode_asset_transaction,
            'type' => optional($asset->classification)->kode_asset_type,
            'cat' => optional($asset->classification)->kode_category,
            'cat2' => optional($asset->classification)->kode_category_2,
            'subcat' => optional($asset->classification)->kode_sub_category,
            'aclass' => $asset->kode_asset_class,
            'loc' => $asset->kode_location,
            'sumber' => $asset->kode_sumber,
            'status' => $asset->kode_status,
            'uom' => optional($asset->value)->kode_uom,
            'owner' => optional($asset->assignment)->asset_owner,
            'user' => optional($asset->assignment)->asset_user,
            'maintenance' => optional($asset->assignment)->asset_maintenance,
        ];
    @endphp
    <script>
        /* confirm before submit */
        $(function() {
            const $form = $('#assetEditForm');
            $form.on('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Update this asset?',
                    text: 'Please review your changes.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#EA242A',
                    cancelButtonColor: '#B5B5B6',
                }).then((res) => {
                    if (!res.isConfirmed) return;
                    Swal.fire({
                        title: 'Saving…',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    e.target.submit();
                });
            });
        });

        /* select2 helpers */
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

        function preloadValue(selector, id, url, extraFn) {
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

        function setSelectValue(selector, code) {
            if (!code) return;
            const opt = new Option(code, code, true, true);
            $(selector).append(opt).trigger('change');
        }

        /* endpoints */
        const R = {
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
            parent: '{{ route('assets.parent.options') }}',
            parentMeta: (uuid) => '{{ route('assets.parent.meta', ':id') }}'.replace(':id', uuid),
        };

        /* current values from server */
        const CURRENT = @json($current);

        $(function() {
            // independent
            initSelect2('#sel-sumber', R.sumber);
            initSelect2('#sel-transaction', R.trx);
            initSelect2('#sel-asset-type', R.type);
            initSelect2('#sel-location', R.location);
            initSelect2('#sel-uom', R.uom);
            initSelect2('#sel-status', R.status);
            initSelect2('#sel-asset-class', R.aclass);
            initSelect2('#sel-owner', R.usercode);
            initSelect2('#sel-user', R.usercode);
            initSelect2('#sel-maintenance', R.usercode);
            initSelect2('#sel-sub-category', R.subcat);
            initSelect2('#sel-parent', R.parent);

            // dependent
            initSelect2('#sel-category', R.cat, () => ({
                kode_asset_type: $('#sel-asset-type').val() || ''
            }));
            initSelect2('#sel-category-2', R.cat2, () => ({
                kode_category: $('#sel-category').val() || ''
            }));

            $('#sel-category').on('select2:opening', function(e) {
                if (!$('#sel-asset-type').val()) e.preventDefault();
            });
            $('#sel-category-2').on('select2:opening', function(e) {
                if (!$('#sel-category').val()) e.preventDefault();
            });
            $('#sel-asset-type').on('change', function() {
                $('#sel-category').val(null).trigger('change');
                $('#sel-category-2').val(null).trigger('change');
            });
            $('#sel-category').on('change', function() {
                $('#sel-category-2').val(null).trigger('change');
            });

            // preload current values (in correct dependency order)
            preloadValue('#sel-transaction', CURRENT['trx'], R.trx);
            preloadValue('#sel-asset-type', CURRENT['type'], R.type, () => ({}));

            // after type is loaded, preload category & cat2
            setTimeout(() => {
                preloadValue('#sel-category', CURRENT['cat'], R.cat, () => ({
                    kode_asset_type: $('#sel-asset-type').val() || ''
                }));
                setTimeout(() => {
                    preloadValue('#sel-category-2', CURRENT['cat2'], R.cat2, () => ({
                        kode_category: $('#sel-category').val() || ''
                    }));
                }, 250);
            }, 250);

            // Mode toggle display
            function applyModeUI(mode) {
                if (mode === 'existing') {
                    $('#wrap-parent-picker').show();
                    $('#wrap-classification').hide();

                    $('#sel-parent').prop('required', true);
                    $('#sel-transaction,#sel-asset-type,#sel-category,#sel-category-2,#sel-sub-category')
                        .prop('required', false);
                } else {
                    $('#wrap-parent-picker').hide();
                    $('#wrap-classification').show();

                    $('#sel-parent').prop('required', false);
                    $('#sel-transaction,#sel-asset-type,#sel-category,#sel-category-2,#sel-sub-category')
                        .prop('required', true);
                }
            }
            applyModeUI(CURRENT.mode || 'new');
            if (CURRENT['parent_uuid']) {
                $.getJSON(R.parentMeta(CURRENT['parent_uuid']), function(meta) {
                    const opt = new Option(meta.label, meta.id, true, true);
                    $('#sel-parent').append(opt).trigger('change');
                });
            }
            $('input[name="mode"]').on('change', function() {
                const mode = this.value;
                applyModeUI(mode);
                if (mode === 'existing') {
                    // clear classification so validation won’t trip
                    $('#sel-transaction,#sel-asset-type,#sel-category,#sel-category-2,#sel-sub-category')
                        .val(null).trigger('change');
                } else {
                    $('#sel-parent').val(null).trigger('change');
                }
            });
            // When a parent is selected, autofill classification
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
            preloadValue('#sel-sub-category', CURRENT['subcat'], R.subcat);
            preloadValue('#sel-asset-class', CURRENT['aclass'], R.aclass);
            preloadValue('#sel-location', CURRENT['loc'], R.location);
            preloadValue('#sel-sumber', CURRENT['sumber'], R.sumber);
            preloadValue('#sel-status', CURRENT['status'], R.status);
            preloadValue('#sel-uom', CURRENT['uom'], R.uom);
            preloadValue('#sel-owner', CURRENT['owner'], R.usercode);
            preloadValue('#sel-user', CURRENT['user'], R.usercode);
            preloadValue('#sel-maintenance', CURRENT['maintenance'], R.usercode);
        });
    </script>
@endpush
