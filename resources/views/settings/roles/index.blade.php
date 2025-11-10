@extends('layouts.app')

@section('content')
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Master Role</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">User Settings</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Master Role</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Role/Group Data</span>
                    </h3>
                </div>

                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table
                            class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                            <thead>
                                <tr class="table-light">
                                    <th class="min-w-125px">Kode</th>
                                    <th class="min-w-225px">Role Name</th>
                                    <th class="min-w-100px text-center">Status</th>
                                    <th class="min-w-125px text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                    <tr data-role-row data-role-uuid="{{ $role->uuid }}"
                                        data-role-kode="{{ $role->kode }}" data-role-name="{{ $role->name }}"
                                        data-role-status="{{ $role->status ? 1 : 0 }}">
                                        <td>{{ $role->kode }}</td>
                                        <td>{{ $role->name }}</td>
                                        <td class="text-center">
                                            @if ($role->status)
                                                <span class="badge badge-light-success">Active</span>
                                            @else
                                                <span class="badge badge-light-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @canAction('USER_MGMT','U')
                                            <a href="{{ route('settings.roles.edit', $role->uuid) }}"
                                                class="btn btn-sm btn-light-primary me-2">
                                                Edit
                                            </a>
                                            @endcanAction

                                            @canAction('USER_MGMT','D')
                                            @if (!in_array($role->kode, ['SYSADMIN', 'AM_HEAD', 'AM_ADMIN', 'DEPT_HEAD', 'DEPT_USER', 'AUDITOR']))
                                                <form action="{{ route('settings.roles.destroy', $role->uuid) }}"
                                                    method="post" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger"
                                                        onclick="return confirm('Delete this role?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                            @endcanAction
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-10">
                                            No roles found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Create / Edit Role Modal --}}
    <div class="modal fade" id="role-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header pb-0 border-0">
                    <h3 class="fw-bold mb-0" id="role-modal-title">Add Role</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ki-duotone ki-cross fs-2"></i>
                    </div>
                </div>
                <div class="modal-body px-10 pb-10">
                    <form id="role-modal-form" method="post">
                        @csrf
                        <input type="hidden" name="_method" id="role-modal-method" value="POST">

                        <div class="mb-5">
                            <label class="form-label required">Kode</label>
                            <input type="text" name="kode" id="role-kode" class="form-control form-control-solid"
                                placeholder="e.g. SYSADMIN, AM_HEAD" required>
                        </div>

                        <div class="mb-5">
                            <label class="form-label required">Role Name</label>
                            <input type="text" name="name" id="role-name" class="form-control form-control-solid"
                                placeholder="Role name" required>
                        </div>

                        <div class="mb-7">
                            <label class="form-label">Status</label>
                            <select name="status" id="role-status" class="form-select form-select-solid">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Save</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const ROLE_STORE_URL = @json(route('settings.roles.store'));
        const ROLE_UPDATE_URL = (uuid) => @json(route('settings.roles.update', 'UUID_PLACEHOLDER')).replace('UUID_PLACEHOLDER', uuid);

        const roleModalEl = document.getElementById('role-modal');
        if (roleModalEl) {
            roleModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const mode = button.getAttribute('data-mode') || 'create';
                const titleEl = document.getElementById('role-modal-title');
                const form = document.getElementById('role-modal-form');
                const methodEl = document.getElementById('role-modal-method');

                const kodeEl = document.getElementById('role-kode');
                const nameEl = document.getElementById('role-name');
                const statusEl = document.getElementById('role-status');

                if (mode === 'edit') {
                    const uuid = button.getAttribute('data-role-uuid');
                    const row = document.querySelector(`tr[data-role-row][data-role-uuid="${uuid}"]`);
                    const kode = row.getAttribute('data-role-kode');
                    const name = row.getAttribute('data-role-name');
                    const stat = row.getAttribute('data-role-status');

                    titleEl.innerText = 'Edit Role';
                    form.action = ROLE_UPDATE_URL(uuid);
                    methodEl.value = 'PUT';

                    kodeEl.value = kode;
                    nameEl.value = name;
                    statusEl.value = stat;
                } else {
                    titleEl.innerText = 'Add Role';
                    form.action = ROLE_STORE_URL;
                    methodEl.value = 'POST';

                    kodeEl.value = '';
                    nameEl.value = '';
                    statusEl.value = '1';
                }
            });
        }
    </script>
@endpush
