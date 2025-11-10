<?php

namespace Database\Seeders;

use App\Models\MasterAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterActionSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            ['C',   'Create'],
            ['R',   'Read'],
            ['U',   'Update'],
            ['D',   'Delete'],
            ['APR', 'Approve'],
            ['REJ', 'Reject'],
        ];

        foreach ($actions as [$kode, $name]) {
            MasterAction::updateOrCreate(
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
