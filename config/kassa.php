<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kassa PIN Codes
    |--------------------------------------------------------------------------
    |
    | Default PIN codes for POS cash register unlock and Admin management screens.
    |
    */
    'pos_pin' => env('KASSA_POS_PIN', '1234'),
    'admin_pin' => env('KASSA_ADMIN_PIN', '1234'),

    /*
    | Default register/kassa ID (e.g. "1", "2")
    */
    'default_kassa_id' => env('KASSA_DEVICE_ID', '1'),
];
