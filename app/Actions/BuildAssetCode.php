<?php

namespace App\Actions;

class BuildAssetCode
{
    public function handle(string $parent, string $child): string
    {
        return $parent.'-'.$child;
    }
}
