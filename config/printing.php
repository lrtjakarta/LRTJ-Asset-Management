<?php

return [
    'sato_rfid' => [
        'host'        => env('SATO_RFID_HOST', '192.168.18.51'),
        'port'        => env('SATO_RFID_PORT', 9100),
        'timeout'     => 5,
        'dots_per_mm' => env('SATO_RFID_DOTS_PER_MM', 8),
    ],
];
