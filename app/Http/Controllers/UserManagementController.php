<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MasterRole;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index()
    {
        $roles = MasterRole::where('status', true)
            ->orderBy('name')
            ->get();

        return view('settings.users.index', [
            'roles' => $roles,
        ]);
    }

    public function datatable(Request $request)
    {
        $query = User::with('roles')
            ->orderBy('username');

        return datatables()->eloquent($query)
            ->addColumn('roles', function (User $user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="badge badge-light-warning">No Role</span>';
                }

                return $user->roles->map(function ($role) {
                    return '<span class="badge badge-light-primary mb-1">' . $role->name . '</span>';
                })->implode(' ');
            })
            ->addColumn('action', function (User $user) {
                if (! auth()->user() || ! auth()->user()->hasAction('USER_MGMT', 'U')) {
                    return '';
                }

                $roleCodes = $user->roles->pluck('kode')->implode(',');

                return sprintf(
                    '<button type="button" class="btn btn-sm btn-light-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#user-role-modal"
                        data-user-id="%d"
                        data-user-username="%s"
                        data-user-name="%s"
                        data-user-email="%s"
                        data-user-roles="%s">
                        Edit
                    </button>',
                    $user->id,
                    e($user->username),
                    e($user->name),
                    e($user->email),
                    e($roleCodes)
                );
            })
            ->rawColumns(['roles', 'action'])
            ->make(true);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'email'       => ['nullable', 'email', 'max:191'],
            'role_kode'   => ['array'],
            'role_kode.*' => ['string', 'exists:master_role,kode'],
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'] ?? null;
        $user->save();

        $user->roles()->sync($data['role_kode'] ?? []);

        if (!empty($data['role_kode'])) {
            $user->role_kode = $data['role_kode'][0];
        } else {
            $user->role_kode = null;
        }
        $user->save();

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User updated.');
    }
}
