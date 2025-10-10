<?php

namespace App\Services;

use App\Models\Assets;
use Illuminate\Support\Facades\DB;

class AssetChildSequencer
{
    /**
     * Re-sequence children under a parent to contiguous 2-digit codes
     * (00 is reserved for the parent row itself; children start at 01).
     *
     * - Renumbers child assets (asset_number_child & asset_code)
     * - Refreshes asset_parent_counters.last_child_seq to current max
     */
    public function normalizeChildren(string $parentCode): void
    {
        DB::transaction(function () use ($parentCode) {
            // Lock counter row (create if missing)
            DB::statement("
                INSERT INTO asset_parent_counters (parent_code, last_child_seq, created_at, updated_at)
                VALUES (?, 0, NOW(), NOW())
                ON CONFLICT (parent_code) DO NOTHING
            ", [$parentCode]);

            DB::table('asset_parent_counters')
                ->where('parent_code', $parentCode)
                ->lockForUpdate()
                ->first();

            // Lock all current (non-deleted) children under this parent, excluding the parent '00'
            $children = Assets::query()
                ->where('asset_number_parent', $parentCode)
                ->where('asset_number_child', '<>', '00')
                ->whereNull('deleted_at')
                ->orderBy('asset_number_child') // or created_at if you prefer
                ->lockForUpdate()
                ->get();

            $i = 1;
            foreach ($children as $child) {
                $newChild = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                if ($child->asset_number_child !== $newChild) {
                    $child->asset_number_child = $newChild;
                    $child->asset_code = $parentCode . '-' . $newChild;
                    $child->save();
                }
                $i++;
            }

            $max = $i - 1; // 0 if no children left
            DB::table('asset_parent_counters')
                ->where('parent_code', $parentCode)
                ->update([
                    'last_child_seq' => $max,
                    'updated_at'     => now(),
                ]);
        });
    }
}
