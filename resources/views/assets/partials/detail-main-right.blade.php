{{-- Value --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title fw-bold">Value</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Price</dt>
            <dd class="col-sm-7"> :
                {{ is_null($asset->value?->price) ? '' : number_format($asset->value->price, 2) }}
            </dd>
            
            <dt class="col-sm-5">Depreciation</dt>
            <dd class="col-sm-7"> :
                {{ is_null($depreciation) ? '' : number_format($depreciation, 2)  }}
            </dd>


            <dt class="col-sm-5">Ending Balance</dt>
            <dd class="col-sm-7"> :
                {{ is_null($endingBalance) ? '' : number_format($endingBalance, 2)  }}
            </dd>


            <dt class="col-sm-5">Quantity</dt>
            <dd class="col-sm-7"> :
                {{ is_null($asset->value?->quantity) ? '' : number_format($asset->value->quantity) }}
            </dd>

            <dt class="col-sm-5">VAT In</dt>
            <dd class="col-sm-7"> :
                {{ is_null($asset->value?->vat_in) ? '' : number_format($asset->value->vat_in, 2) }}
            </dd>

            <dt class="col-sm-5">UOM</dt>
            <dd class="col-sm-7"> :
                {{ $asset->value?->kode_uom }}
                @if ($asset->value?->uom?->name)
                    - {{ $asset->value->uom->name }}
                @endif
            </dd>

            <dt class="col-sm-5">Acquisition</dt>
            <dd class="col-sm-7"> :
                {{ is_null($asset->value?->total) ? '' : number_format($asset->value->total, 2) }}
            </dd>

            <dt class="col-sm-5">Acquisition Date</dt>
            <dd class="col-sm-7"> :
                {{ is_null($asset->value?->actual_date) ? '' : $asset->value->actual_date->format('d F Y') }}
            </dd>

            <dt class="col-sm-5">Capitalization Date</dt>
            <dd class="col-sm-7"> :
                {{ is_null($asset->value?->capitalization_date) ? '' : $asset->value->capitalization_date->format('d F Y') }}
            </dd>

            <dt class="col-sm-5">Useful Life</dt>
            <dd class="col-sm-7"> :
                {{ $asset->value?->useful_life_month }} Month(s)
                @if (!is_null($asset->value?->useful_life_year))
                    &nbsp; / &nbsp; {{ $asset->value->useful_life_year }} Year(s)
                @endif
            </dd>
        </dl>
    </div>
</div>

{{-- Notes --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title fw-bold">Notes</h3>
    </div>
    <div class="card-body">
        <div class="col-sm-12">{{ $asset->notes ?? '-' }}</div>
    </div>
</div>

{{-- QR --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title fw-bold">QR</h3>
    </div>
    <div class="card-body">
        <img src="{{ asset('storage/qrcodes/' . $asset->uuid . '.svg') }}" alt="QR Code"
             width="300" height="300">
    </div>
</div>
{{-- End Value + Notes + QR --}}