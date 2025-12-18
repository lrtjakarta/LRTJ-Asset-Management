<?php

namespace App\Observers;

use App\Models\AssetDeprMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetDeprMovementObserver
{
    private function signedDelta(string $category, $amount): float
    {
        $amt = (float) $amount;

        // normalize
        if ($amt === 0.0) return 0.0;

        // INCREASE gross
        if (in_array($category, [
            AssetDeprMovement::ADDITION,
            AssetDeprMovement::TRANSFER_IN,
            AssetDeprMovement::ADJUSTMENT_VALUE,
        ], true)) {
            return abs($amt);
        }

        // DECREASE gross
        if (in_array($category, [
            AssetDeprMovement::TRANSFER_OUT,
            AssetDeprMovement::DISPOSAL,
        ], true)) {
            return -abs($amt);
        }

        // does not change gross (depr only)
        return 0.0;
    }

    private function applyDelta(string $assetUuid, float $delta): void
    {
        if (abs($delta) < 0.0000001) return;

        $row = DB::table('assets_value')
            ->where('asset_uuid', $assetUuid)
            ->lockForUpdate()
            ->first();

        if (!$row) {
            throw ValidationException::withMessages([
                'asset_uuid' => "assets_value not found for asset_uuid {$assetUuid}",
            ]);
        }

        $newTotal = (float) $row->total + $delta;

        if ($newTotal < 0) {
            throw ValidationException::withMessages([
                'total' => "assets_value.total cannot go below 0 (asset_uuid {$assetUuid}).",
            ]);
        }

        DB::table('assets_value')
            ->where('asset_uuid', $assetUuid)
            ->update([
                'total'      => $newTotal,
                'updated_at' => now(),
            ]);
    }

    public function created(AssetDeprMovement $m): void
    {
        $delta = $this->signedDelta($m->category, $m->amount);
        $this->applyDelta($m->asset_uuid, $delta);
    }

    public function updated(AssetDeprMovement $m): void
    {
        // handle edits safely (diff old vs new)
        $oldDelta = $this->signedDelta($m->getOriginal('category'), $m->getOriginal('amount'));
        $newDelta = $this->signedDelta($m->category, $m->amount);

        // if asset_uuid changed, revert old asset and apply to new asset
        $oldAsset = (string) $m->getOriginal('asset_uuid');
        $newAsset = (string) $m->asset_uuid;

        if ($oldAsset !== $newAsset) {
            $this->applyDelta($oldAsset, -$oldDelta);
            $this->applyDelta($newAsset, $newDelta);
            return;
        }

        $diff = $newDelta - $oldDelta;
        $this->applyDelta($newAsset, $diff);
    }

    public function deleted(AssetDeprMovement $m): void
    {
        // works for soft delete too: revert the delta
        $delta = $this->signedDelta($m->category, $m->amount);
        $this->applyDelta($m->asset_uuid, -$delta);
    }

    public function restored(AssetDeprMovement $m): void
    {
        // re-apply after restore
        $delta = $this->signedDelta($m->category, $m->amount);
        $this->applyDelta($m->asset_uuid, $delta);
    }
}
