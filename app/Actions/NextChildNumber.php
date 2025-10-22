<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class NextChildNumber
{
    public function get(string $parent): string
    {
        $max = DB::table('assets')
            ->where('asset_number_parent', $parent)
            ->max(DB::raw("LPAD(asset_number_child, 2, '0')"));

        if (!$max) return '00';
        $n = (int)$max + 1;
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }
}
