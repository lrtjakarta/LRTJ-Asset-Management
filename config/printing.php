<?php

return [
    'sato_rfid' => [
        'host'    => env('SATO_RFID_HOST', '192.168.1.50'),
        'port'    => env('SATO_RFID_PORT', 9100),
        'timeout' => 5,
    ],
];
