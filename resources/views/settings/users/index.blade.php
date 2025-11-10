@extends('layouts.app')

@section('content')
    {{-- Toolbar --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mb-10">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">List User</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">User Settings</li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">List User</li>
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
                        <span class="card-label fw-bold fs-3 mb-1">Users Data</span>
                    </h3>
                </div>

                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table id="users-table"
                            class="table table-striped table-row-bordered table-column-bordered gy-5 gs-7 border rounded">
                            <thead>
                                <tr class="table-light">
                                    <th class="min-w-125px">Username/UID</th>
                                    <th class="min-w-175px">Name</th>
                                    <th class="min-w-200px">Email</th>
                                    <th class="min-w-200px">Roles</th>
                                    <th class="min-w-100px text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Edit User Modal --}}
    <div class="modal fade" id="user-role-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-600px">
            <div class="modal-content">
                <div class="modal-header pb-0 border-0">
                    <h3 class="fw-bold mb-0" id="user-role-modal-title">Edit User</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                        aria-label="Close">
                        <i class="ki-duotone ki-cross fs-2"></i>
                    </div>
                </div>
                <div class="modal-body px-10 pb-10">
                    <form id="user-role-form" method="post">
                        @csrf
                        @method('PUT')

                        <div class="mb-5 d-none">
                            <label class="form-label">ID</label>
                            <input type="text" class="form-control form-control-solid" id="user-id" disabled>
                        </div>

                        <div class="mb-5">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control form-control-solid" id="user-username" disabled>
                        </div>

                        <div class="mb-5">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name" class="form-control form-control-solid" id="user-name"
                                required>
                        </div>

                        <div class="mb-5">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control form-control-solid" id="user-email">
                        </div>

                        <div class="mb-7">
                            <label class="form-label required">Roles</label>
                            <select name="role_kode[]" id="user-role-select" class="form-select form-select-solid"
                                data-control="select2" data-placeholder="Select roles" multiple required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->kode }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                One user can have multiple roles.
                            </div>
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
        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: @json(session('success')),
                buttonsStyling: false,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: @json($errors->first()),
                buttonsStyling: false,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn btn-light btn-active-primary'
                }
            });
        @endif

        const USERS_DATATABLE_URL = @json(route('settings.users.datatable'));
        const USER_UPDATE_URL = (id) => @json(route('settings.users.update', 'ID_PLACEHOLDER')).replace('ID_PLACEHOLDER', id);

        let usersTable = null;

        document.addEventListener('DOMContentLoaded', function() {
            usersTable = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: USERS_DATATABLE_URL,
                    type: 'GET',
                },
                dom: "<'row mb-2'<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l><'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i><'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                searching: true,
                columns: [{
                        data: 'username',
                        name: 'username'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'roles',
                        name: 'roles',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            const searchInput = document.getElementById('users-search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    usersTable.search(this.value).draw();
                });
            }
        });

        const modalEl = document.getElementById('user-role-modal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const userId = button.getAttribute('data-user-id');
                const username = button.getAttribute('data-user-username');
                const name = button.getAttribute('data-user-name');
                const email = button.getAttribute('data-user-email');
                const rolesCsv = button.getAttribute('data-user-roles') || '';
                const roles = rolesCsv.split(',').filter(Boolean);

                document.getElementById('user-id').value = userId;
                document.getElementById('user-username').value = username;
                document.getElementById('user-name').value = name;
                document.getElementById('user-email').value = email || '';

                const form = document.getElementById('user-role-form');
                form.action = USER_UPDATE_URL(userId);

                const select = $('#user-role-select');
                select.val(roles).trigger('change');
            });
        }
    </script>
@endpush
