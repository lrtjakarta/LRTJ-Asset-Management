@extends('layouts.app')
@push('head')
    <style>
        .form-check-input,
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border: 2px solid #6b7280;
            border-radius: 4px;
            background-color: #ffffff;
            cursor: pointer;
        }

        .form-check-input:not(:checked),
        input[type="checkbox"]:not(:checked) {
            border: 2px solid #9ca3af !important;
            background-color: #f3f4f6 !important;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.03);
        }

        .form-check-input:not(:checked):hover,
        input[type="checkbox"]:not(:checked):hover {
            border-color: #6b7280 !important;
            background-color: #e5e7eb !important;
        }
    </style>
@endpush
@section('content')
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">
                    Edit Role - {{ $role->kode }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">User Settings</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('settings.roles.index') }}" class="text-muted text-hover-primary">Master Role</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Edit Role</li>
                </ul>
            </div>

        </div>
    </div>

    {{-- Content --}}
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">

            @if (session('success'))
                <div class="alert alert-success mb-5">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger mb-5">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('settings.roles.update', $role->uuid) }}" method="post">
                @csrf
                @method('PUT')

                {{-- Role Info --}}
                <div class="card mb-7">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Role / Group Detail</span>
                        </h3>
                    </div>
                    <div class="card-body pt-5">
                        <div class="row mb-5">
                            <div class="col-md-3">
                                <label class="form-label">Kode</label>
                                <input type="text" class="form-control form-control-solid" value="{{ $role->kode }}"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Role Name</label>
                                <input type="text" name="name"
                                    class="form-control form-control-solid @error('name') is-invalid @enderror"
                                    value="{{ old('name', $role->name) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select form-select-solid">
                                    <option value="1" @selected($role->status)>Active</option>
                                    <option value="0" @selected(!$role->status)>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Access Matrix --}}
                <div class="card">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-3 mb-1">Role Access</span>
                        </h3>
                    </div>
                    <div class="card-body pt-5">
                        <div class="table-responsive">
                            <table
                                class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                                <thead>
                                    <tr class="table-light text-center align-middle">
                                        <th class="text-start min-w-250px">Menu / Modul</th>
                                        @foreach ($actions as $action)
                                            <th class="min-w-70px">
                                                <div class="fw-bold">{{ $action->kode }}</div>
                                                <div class="text-muted fs-8">{{ $action->name }}</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($menus as $menu)
                                        @php
                                            $granted = $permissions[$menu->kode] ?? [];
                                            $available = $menuActions[$menu->kode] ?? $actions->pluck('kode')->all();
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">
                                                {{ $menu->name }}
                                                <div class="text-muted fs-8">{{ $menu->kode }}</div>
                                            </td>

                                            @foreach ($actions as $action)
                                                @if (in_array($action->kode, $available, true))
                                                    <td class="text-center align-middle">
                                                        <div
                                                            class="form-check form-check-sm form-check-custom form-check-solid d-flex justify-content-center align-items-center ps-0">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="permissions[{{ $menu->kode }}][]"
                                                                value="{{ $action->kode }}" @checked(in_array($action->kode, $granted, true)) />
                                                        </div>
                                                    </td>
                                                @else
                                                    {{-- This menu does NOT support this action -> empty cell --}}
                                                    <td></td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-7">
                            <a href="{{ route('settings.roles.index') }}" role="button" class="btn btn-light-primary me-2">Go Back</a>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Save Changes</span>
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
