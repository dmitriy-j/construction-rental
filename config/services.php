<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'yandex_maps' => [
        'api_key' => env('YANDEX_MAPS_API_KEY'),
        'coefficient' => env('DISTANCE_COEFFICIENT', 1.3),
    ],

    '1c' => [
        'base_url' => env('1C_BASE_URL', 'http://localhost:8080/construction_rental/ws'),
        'login' => env('1C_LOGIN', 'admin'),
        'password' => env('1C_PASSWORD', 'password'),
        'timeout' => env('1C_TIMEOUT', 30),
        'version' => env('1C_VERSION', '8.3'),
        'export_format' => env('1C_EXPORT_FORMAT', 'xml'),
    ],

];
