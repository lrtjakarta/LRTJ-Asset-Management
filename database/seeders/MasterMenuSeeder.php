<?php

namespace Database\Seeders;

use App\Models\MasterMenu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterMenuSeeder extends Seeder
{
    public function run(): void
    {
        // kode,           name,                          route,                 url,                sort_order
        $menus = [
            ['MASTER_DATA',  'Master Data',                null,                  null,              30],
        ];

        foreach ($menus as [$kode, $name, $route, $url, $sort]) {
            MasterMenu::updateOrCreate(
                ['kode' => $kode],
                [
                    'uuid'       => Str::uuid()->toString(),
                    'name'       => $name,
                    'route'      => $route,
                    'url'        => $url,
                    'parent_uuid'=> null,
                    'sort_order' => $sort,
                    'status'     => true,
                ]
            );
        }
    }
}
