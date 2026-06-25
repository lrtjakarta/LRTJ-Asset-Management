<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MasterRole;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $query = User::with(['roles', 'department'])
            ->orderBy('username');

        return datatables()->eloquent($query)
            ->addColumn('kode_department', function (User $user) {
                if (!$user->kode_department) {
                    return '<span class="text-muted">-</span>';
                }
                $dept = $user->department;
                if ($dept) {
                    return e($dept->kode . ' - ' . $dept->department);
                }
                return e($user->kode_department);
            })
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
                $deptLabel = '';
                if ($user->kode_department && $user->department) {
                    $deptLabel = $user->department->kode . ' - ' . $user->department->department;
                }

                return sprintf(
                    '<button type="button" class="btn btn-sm btn-light-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#user-role-modal"
                        data-user-id="%d"
                        data-user-username="%s"
                        data-user-name="%s"
                        data-user-email="%s"
                        data-user-kode-department="%s"
                        data-user-department-label="%s"
                        data-user-roles="%s">
                        Edit
                    </button>',
                    $user->id,
                    e($user->username),
                    e($user->name),
                    e($user->email),
                    e($user->kode_department ?? ''),
                    e($deptLabel),
                    e($roleCodes)
                );
            })
            ->rawColumns(['kode_department', 'roles', 'action'])
            ->make(true);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:191'],
            'email'            => ['nullable', 'email', 'max:191'],
            'kode_department'  => ['nullable', 'string', 'max:50'],
            'role_kode'        => ['array'],
            'role_kode.*'      => ['string', 'exists:master_role,kode'],

            // optional password:
            'password'         => ['nullable', 'string', 'min:5', 'confirmed'],
            // confirmed => butuh field password_confirmation
        ]);

        $user->name            = $data['name'];
        $user->email           = $data['email'] ?? null;
        $user->kode_department = $data['kode_department'] ?? null;

        // update password hanya jika diisi
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

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
    public function profile_update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:191'],
            'email'    => ['nullable', 'email', 'max:191'],
            'password' => ['nullable', 'string', 'min:5', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'] ?? null;

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        // update session ldap_user biar header langsung ikut berubah
        $roles = $user->roles()->pluck('kode')->toArray();
        $request->session()->put('ldap_user', [
            'username' => $user->username ?? null,
            'name'     => $user->name ?? null,
            'email'    => $user->email ?? null,
            'ou'       => $user->ou ?? null,
            'kode_department' => $user->kode_department ?? null,
            'roles'    => $roles,
        ]);

        return back()->with('success', 'Profile updated.');
    }
    public function select_users(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $builder = User::query()->orderBy('id');

        if ($q !== '') {
            $builder->where(function ($w) use ($q) {
                $ilike = '%' . Str::of($q)->lower() . '%';
                $w->whereRaw('LOWER(username) LIKE ?', [$ilike])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$ilike]);
            });
        }

        $total = (clone $builder)->count();
        $rows  = $builder->forPage($page, $perPage)->get(['id', 'username', 'name']);

        $results = $rows->map(fn($r) => [
            'id'   => $r->name,
            'text' => "{$r->username} - {$r->name}",
        ]);

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => ($page * $perPage) < $total],
        ]);
    }
}
