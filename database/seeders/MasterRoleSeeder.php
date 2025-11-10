<?php

namespace Database\Seeders;

use App\Models\MasterRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['SYSADMIN',  'System Administrator'],
            ['AM_HEAD',   'Asset Management Head'],
            ['AM_ADMIN',  'Asset Management Admin'],
            ['DEPT_HEAD', 'User - Department Head'],
            ['DEPT_USER', 'User Departemen'],
            ['AUDITOR',   'Auditor'],
        ];

        foreach ($roles as [$kode, $name]) {
            MasterRole::updateOrCreate(
                ['kode' => $kode],
                [
                    'uuid'   => Str::uuid()->toString(),
                    'name'   => $name,
                    'status' => true,
                ]
            );
        }
    }
}
