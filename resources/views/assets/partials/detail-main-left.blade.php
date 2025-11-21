{{-- Identifiers --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title fw-bold">Identifiers</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Asset Number Parent</dt>
            <dd class="col-sm-7"> : {{ $asset->asset_number_parent }}</dd>

            <dt class="col-sm-5">Asset Number Child</dt>
            <dd class="col-sm-7"> : {{ $asset->asset_number_child }}</dd>

            <dt class="col-sm-5">Asset Number Maximo</dt>
            <dd class="col-sm-7"> : {{ $asset->identifiers?->asset_number_maximo }}</dd>

            <dt class="col-sm-5">Asset Number D365</dt>
            <dd class="col-sm-7"> : {{ $asset->identifiers?->asset_number_dynamic_365 }}</dd>

            <dt class="col-sm-5">Asset Number Internal</dt>
            <dd class="col-sm-7"> : {{ $asset->identifiers?->asset_number_internal }}</dd>

            <dt class="col-sm-5">Alias</dt>
            <dd class="col-sm-7"> : {{ $asset->identifiers?->alias }}</dd>

            <dt class="col-sm-5">Asset Status</dt>
            <dd class="col-sm-7" id="lbl-asset-status"> :
                @if ($asset->status?->name)
                    <span
                        class="badge badge-light-{{ $asset->status->name === 'Operation' ? 'success' : 'danger' }}">
                        {{ $asset->kode_status }} - {{ $asset->status->name }}
                    </span>
                @endif
            </dd>
        </dl>
    </div>
</div>

{{-- Classification --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title fw-bold">Classification</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Asset Company</dt>
            <dd class="col-sm-7"> :
                @php
                    $tx = $asset->classification?->kode_asset_transaction;
                    $txName = $asset->classification?->transaction?->name ?? null;
                @endphp
                {{ $tx }} @if ($txName)
                    - {{ $txName }}
                @endif
            </dd>

            <dt class="col-sm-5">Asset Class</dt>
            <dd class="col-sm-7"> :
                {{ $asset->kode_asset_class }}
                @if ($asset->assetClass?->name)
                    - {{ $asset->assetClass->name }}
                @endif
            </dd>

            <dt class="col-sm-5">Sumber Input</dt>
            <dd class="col-sm-7"> :
                {{ $asset->kode_sumber }}
                @if ($asset->sumber?->name)
                    - {{ $asset->sumber->name }}
                @endif
            </dd>
        </dl>
    </div>
</div>

{{-- Assignment --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title fw-bold">Assignment</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Asset Owner</dt>
            <dd class="col-sm-7"> :
                @php
                    $own = $asset->assignment?->asset_owner;
                    $ownName = $asset->assignment?->owner?->department ?? null;
                @endphp
                {{ $own }} @if ($ownName)
                    - {{ $ownName }}
                @endif
            </dd>

            <dt class="col-sm-5">Asset User</dt>
            <dd class="col-sm-7"> :
                @php
                    $usr = $asset->assignment?->asset_user;
                    $usrName = $asset->assignment?->user?->department ?? null;
                @endphp
                {{ $usr }} @if ($usrName)
                    - {{ $usrName }}
                @endif
            </dd>

            <dt class="col-sm-5">Asset Maintenance</dt>
            <dd class="col-sm-7"> :
                @php
                    $mnt = $asset->assignment?->asset_maintenance;
                    $mntName = $asset->assignment?->maintenance?->department ?? null;
                @endphp
                {{ $mnt }} @if ($mntName)
                    - {{ $mntName }}
                @endif
            </dd>

            <dt class="col-sm-5">Asset Location</dt>
            <dd class="col-sm-7"> :
                {{ $asset->kode_location }}
                @if ($asset->location?->name)
                    - {{ $asset->location->name }}
                @endif
            </dd>
        </dl>
    </div>
</div>


{{-- Document --}}
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title fw-bold">Document</h3>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">No PO/Perjanjian/SPK</dt>
            <dd class="col-sm-7"> : {{ $asset->documents?->no_po_perjanjian_spk }}</dd>

            <dt class="col-sm-5">Nota Referensi</dt>
            <dd class="col-sm-7"> : {{ $asset->documents?->nota_referensi }}</dd>
        </dl>
    </div>
</div>
{{-- End Identifiers + Classification + Assignment + Document --}}