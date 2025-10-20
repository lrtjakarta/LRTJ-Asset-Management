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
        // Return "00" if no (non-deleted) asset currently uses this parent.
        // Otherwise bump counter and return "01","02",...
        return DB::transaction(function () use ($parentCode) {

            // ensure a counter row exists, then LOCK it so concurrent calls serialize
            DB::statement("
            INSERT INTO asset_parent_counters (parent_code, last_child_seq, created_at, updated_at)
            VALUES (?, 0, NOW(), NOW())
            ON CONFLICT (parent_code) DO NOTHING
        ", [$parentCode]);

            // lock the row before we check the assets table to avoid races
            $row = DB::table('asset_parent_counters')
                ->where('parent_code', $parentCode)
                ->lockForUpdate()
                ->first();

            // first child is "00" iff no active asset uses this parent
            // $exists = DB::table('assets')
            //     ->where('asset_number_parent', $parentCode)
            //     ->whereNull('deleted_at')
            //     ->exists();

            // if (! $exists) {
            //     return '00';
            // }

            // otherwise bump
            $seq = (int) ($row->last_child_seq ?? 0) + 1;

            DB::table('asset_parent_counters')
                ->where('parent_code', $parentCode)
                ->update([
                    'last_child_seq' => $seq,
                    'updated_at'     => now(),
                ]);

            return str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
        });
    }

    /** Extract numeric parent sequence from full code like AT123000001 -> 1 */
    public function extractParentSeq(string $parentCode): int
    {
        $last6 = substr($parentCode, -6);
        return (int) ltrim($last6, '0');
    }

    /**
     * If this asset was the last one issued for its old group, and no one else uses that parent,
     * roll the group's `last_parent_seq` back by 1.
     */
    public function rollbackParentIfLatest(string $oldGroup, string $oldParentCode, string $assetUuid): void
    {
        $oldSeq = $this->extractParentSeq($oldParentCode);

        DB::transaction(function () use ($oldGroup, $oldParentCode, $assetUuid, $oldSeq) {
            // Ensure row exists + lock it
            DB::statement("
                INSERT INTO asset_group_counters (group_code, last_parent_seq, created_at, updated_at)
                VALUES (?, 0, NOW(), NOW())
                ON CONFLICT (group_code) DO NOTHING
            ", [$oldGroup]);

            // Lock the counter row
            $row = DB::table('asset_group_counters')
                ->where('group_code', $oldGroup)
                ->lockForUpdate()
                ->first();

            if (!$row) return;

            // Only if our seq is exactly the latest
            if ((int)$row->last_parent_seq !== $oldSeq) return;

            // Verify no other asset still uses that parent (excluding this one)
            $stillUsed = DB::table('assets')
                ->where('asset_number_parent', $oldParentCode)
                ->where('uuid', '!=', $assetUuid)
                ->exists();

            if ($stillUsed) return;

            // Roll back by 1
            DB::table('asset_group_counters')
                ->where('group_code', $oldGroup)
                ->update([
                    'last_parent_seq' => DB::raw('GREATEST(last_parent_seq - 1, 0)'),
                    'updated_at'      => now(),
                ]);
        });
    }

    /**
     * increment child counters (when first child isn’t "00"),
     */ 
    public function rollbackChildIfLatest(string $oldParentCode, string $oldChildSeq2, string $assetUuid): void
    {
        DB::transaction(function () use ($oldParentCode, $oldChildSeq2, $assetUuid) {
            DB::statement("
            INSERT INTO asset_parent_counters (parent_code, last_child_seq, created_at, updated_at)
            VALUES (?, 0, NOW(), NOW())
            ON CONFLICT (parent_code) DO NOTHING
        ", [$oldParentCode]);

            $row = DB::table('asset_parent_counters')
                ->where('parent_code', $oldParentCode)
                ->lockForUpdate()
                ->first();

            if (! $row) return;

            $oldChildInt = (int) ltrim($oldChildSeq2, '0'); // '00' -> 0, '03' -> 3

            // if our row held the latest number AND no other asset still uses this exact child
            if ((int)$row->last_child_seq === $oldChildInt) {
                $stillUsesThisChild = DB::table('assets')
                    ->where('asset_number_parent', $oldParentCode)
                    ->where('asset_number_child',  $oldChildSeq2)
                    ->where('uuid', '!=', $assetUuid)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($stillUsesThisChild) return;

                // if parent now has NO assets left, reset to 0; else step back by 1
                $parentEmpty = ! DB::table('assets')
                    ->where('asset_number_parent', $oldParentCode)
                    ->whereNull('deleted_at')
                    ->exists();

                $newSeq = $parentEmpty ? 0 : max(0, $oldChildInt - 1);

                DB::table('asset_parent_counters')
                    ->where('parent_code', $oldParentCode)
                    ->update([
                        'last_child_seq' => $newSeq,
                        'updated_at'     => now(),
                    ]);
            }
        });
    }
}
