<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AssetReferenceGuard
{
    /** Return true if kode is referenced by any asset-related table/column */
    public static function isUsed(string $master, string $kode): bool
    {
        $map = [
            // assets
            'master_sumber'       => [ ['assets','kode_sumber'] ],
            'master_asset_class'  => [ ['assets','kode_asset_class'] ],
            'master_status'       => [ ['assets','kode_status'] ],
            'master_location'     => [ ['assets','kode_location'] ],

            // value
            'master_uom'          => [ ['assets_value','kode_uom'] ],

            // classification
            'master_transaction'  => [ ['assets_classification','kode_asset_transaction'] ],
            'master_asset_type'   => [ ['assets_classification','kode_asset_type'] ],
            'master_category'     => [ ['assets_classification','kode_category'] ],
            'master_category_2'   => [ ['assets_classification','kode_category_2'] ],

            // assignment (master_user_code)
            'master_user_code'    => [
                ['assets_assignment','asset_owner'],
                ['assets_assignment','asset_user'],
                ['assets_assignment','asset_maintenance'],
            ],
        ];

        $targets = $map[$master] ?? [];
        foreach ($targets as [$tbl, $col]) {
            // Count any references (including soft-deleted assets if you want to block those too)
            $exists = DB::table($tbl)->where($col, $kode)->limit(1)->exists();
            if ($exists) return true;
        }
        return false;
    }
}
