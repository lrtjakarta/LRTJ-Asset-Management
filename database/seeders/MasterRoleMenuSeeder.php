<?php

namespace Database\Seeders;

use App\Models\MasterRoleMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterRoleMenuSeeder extends Seeder
{
    public function run(): void
    {

        $maps = [
            // TRANSFER
            ['SYSADMIN',  'TRANSFER', ['C', 'R', 'U', 'D', 'APR', 'REJ']],
            ['AM_HEAD',   'TRANSFER', ['C', 'R', 'U', 'D', 'APR', 'REJ']],
            ['AM_ADMIN',  'TRANSFER', ['C', 'R', 'U', 'D']],
            ['DEPT_HEAD', 'TRANSFER', ['R', 'APR', 'REJ']],
            ['DEPT_USER', 'TRANSFER', ['C', 'R']],
            ['AUDITOR',   'TRANSFER', ['R']],

            // DISPOSAL
            ['SYSADMIN',  'DISPOSAL', ['C', 'R', 'U', 'D', 'APR', 'REJ']],
            ['AM_HEAD',   'DISPOSAL', ['C', 'R', 'U', 'D', 'APR', 'REJ']],
            ['AM_ADMIN',  'DISPOSAL', ['C', 'R', 'U', 'D']],
            ['DEPT_HEAD', 'DISPOSAL', ['R', 'APR', 'REJ']],
            ['DEPT_USER', 'DISPOSAL', ['C', 'R']],
            ['AUDITOR',   'DISPOSAL', ['R']],


            ['SYSADMIN',  'USER_MGMT', ['C', 'R', 'U', 'D']],
            ['AM_HEAD',   'USER_MGMT', ['C', 'R', 'U', 'D']],
            ['AM_ADMIN',  'USER_MGMT', ['C', 'R', 'U', 'D']],


        ];

        foreach ($maps as [$roleKode, $menuKode, $actions]) {
            MasterRoleMenu::updateOrCreate(
                [
                    'role_kode' => $roleKode,
                    'menu_kode' => $menuKode,
                ],
                [
                    'uuid'    => Str::uuid()->toString(),
                    'actions' => $actions,
                    'status'  => true,
                ]
            );
        }
    }
}
