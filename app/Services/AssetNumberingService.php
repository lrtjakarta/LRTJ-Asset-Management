<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AssetNumberingService
{
    /** Next parent code for a group, e.g. AT123 -> AT123000001 (first = 000001) */
    public function nextParent(string $groupCode): string
    {
        return DB::transaction(function () use ($groupCode) {

            // Ensure counter row exists
            DB::statement("
                INSERT INTO asset_group_counters (group_code, last_parent_seq, created_at, updated_at)
                VALUES (?, 0, NOW(), NOW())
                ON CONFLICT (group_code) DO NOTHING
            ", [$groupCode]);

            // Lock and read current
            $seq = (int) DB::table('asset_group_counters')
                ->where('group_code', $groupCode)
                ->lockForUpdate()
                ->value('last_parent_seq');

            // Increment to next parent number (first will be 1 => 000001)
            $seqNext = $seq + 1;

            DB::table('asset_group_counters')
                ->where('group_code', $groupCode)
                ->update(['last_parent_seq' => $seqNext, 'updated_at' => now()]);

            if ($seqNext > 999999) {
                throw new \RuntimeException("Parent sequence overflow for group {$groupCode} (max 999999).");
            }

            return $groupCode . str_pad((string) $seqNext, 6, '0', STR_PAD_LEFT);
        });
    }

    /** Next child code for a parent, **2 digits starting at 00** (00..99) */
    public function nextChild(string $parentCode): string
    {
        return DB::transaction(function () use ($parentCode) {
            // Ensure counter row exists
            DB::statement("
                INSERT INTO asset_parent_counters (parent_code, last_child_seq, created_at, updated_at)
                VALUES (?, 0, NOW(), NOW())
                ON CONFLICT (parent_code) DO NOTHING
            ", [$parentCode]);

            // Lock and read current (first is 0 => "00")
            $seq = (int) DB::table('asset_parent_counters')
                ->where('parent_code', $parentCode)
                ->lockForUpdate()
                ->value('last_child_seq');

            if ($seq > 99) {
                throw new \RuntimeException("Child sequence overflow for parent {$parentCode} (max 99).");
            }

            // Current value is the code we return ("00" for first)
            $code = str_pad((string) $seq, 2, '0', STR_PAD_LEFT);

            // Bump to next
            DB::table('asset_parent_counters')
                ->where('parent_code', $parentCode)
                ->update(['last_child_seq' => $seq + 1, 'updated_at' => now()]);

            return $code;
        });
    }
}
