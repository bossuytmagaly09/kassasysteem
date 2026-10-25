<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Beheer API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to beheer.tjeute.be
    |
    */

    'base_url' => env('BEHEER_API_BASE_URL', 'https://beheer.tjeute.be/api/kassa/v1'),

    'api_token' => env('BEHEER_API_TOKEN', ''),

    'card_hash_secret' => env('CARD_HASH_SECRET', ''),

    'device_name' => env('KASSA_DEVICE_NAME', 'kassa-1'),

    'timeout' => (int) env('BEHEER_API_TIMEOUT', 10),
];
