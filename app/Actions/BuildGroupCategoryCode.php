<?php

namespace App\Actions;

class BuildGroupCategoryCode
{
    public function handle(
        string $trans,
        string $type,
        string $cat,
        string $cat2,
        string $sub
    ): string {
        $trans = strtoupper(trim($trans));
        $type  = strtoupper(trim($type));
        $cat   = strtoupper(trim($cat));
        $sub   = strtoupper(trim($sub));
        $cat2  = strtoupper(trim($cat2));

        return $trans . $type . $cat . $cat2 . $sub;
    }
}
