<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MasterAction;
use App\Models\MasterMenu;
use App\Models\MasterRole;
use App\Models\MasterRoleMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MasterRoleController extends Controller
{
    public function index()
    {
        $roles = MasterRole::orderBy('kode')->get();

        return view('settings.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode'   => ['required', 'string', 'max:50', 'unique:master_role,kode'],
            'name'   => ['required', 'string', 'max:191'],
            'status' => ['nullable', 'boolean'],
        ]);

        MasterRole::create([
            'uuid'   => Str::uuid()->toString(),
            'kode'   => $data['kode'],
            'name'   => $data['name'],
            'status' => $data['status'] ?? true,
        ]);

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role created.');
    }
    
    public function edit(string $uuid)
    {
        $role = MasterRole::where('uuid', $uuid)->firstOrFail();

        $actions = MasterAction::where('status', true)
            // ->orderBy('kode')
            ->get();

        $menus = MasterMenu::where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $roleMenus = MasterRoleMenu::where('role_kode', $role->kode)->get();
        $permissions = [];
        foreach ($roleMenus as $rm) {
            $permissions[$rm->menu_kode] = $rm->actions ?: [];
        }

        return view('settings.roles.edit', [
            'role'         => $role,
            'actions'      => $actions,
            'menus'        => $menus,
            'permissions'  => $permissions,
        ]);
    }

    public function update(Request $request, string $uuid)
    {
        $role = MasterRole::where('uuid', $uuid)->firstOrFail();

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'status'      => ['nullable', 'boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['array'],
        ]);

        $role->update([
            'name'   => $data['name'],
            'status' => $data['status'] ?? true,
        ]);

        $permissions = $data['permissions'] ?? [];

        $allMenus = MasterMenu::where('status', true)->pluck('kode')->all();

        foreach ($allMenus as $menuKode) {
            $actionsForMenu = $permissions[$menuKode] ?? [];

            if (empty($actionsForMenu)) {
                MasterRoleMenu::where('role_kode', $role->kode)
                    ->where('menu_kode', $menuKode)
                    ->delete();
            } else {
                MasterRoleMenu::updateOrCreate(
                    [
                        'role_kode' => $role->kode,
                        'menu_kode' => $menuKode,
                    ],
                    [
                        'uuid'    => Str::uuid()->toString(),
                        'actions' => array_values(array_unique($actionsForMenu)),
                        'status'  => true,
                    ]
                );
            }
        }

        return redirect()
            ->route('settings.roles.edit', $role->uuid)
            ->with('success', 'Role updated.');
    }

    public function destroy(string $uuid)
    {
        $role = MasterRole::where('uuid', $uuid)->firstOrFail();

        // optional: protect built-in roles
        if (in_array($role->kode, ['SYSADMIN', 'AM_HEAD', 'AM_ADMIN', 'DEPT_HEAD', 'DEPT_USER', 'AUDITOR'])) {
            return back()->withErrors('Built-in role cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('settings.roles.index')
            ->with('success', 'Role deleted.');
    }
}
