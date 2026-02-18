<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PalmPay Configuration (NO HARDCODED VALUES)
    |--------------------------------------------------------------------------
    */
    'palmpay' => [
        'base_url' => env('PALMPAY_BASE_URL', 'https://open-gw-prod.palmpay-inc.com'),
        'merchant_id' => env('PALMPAY_MERCHANT_ID'),
        'app_id' => env('PALMPAY_APP_ID'),
        'public_key' => env('PALMPAY_PUBLIC_KEY'),
        'private_key' => env('PALMPAY_PRIVATE_KEY'),
        'palmpay_public_key' => env('PALMPAY_PALMPAY_PUBLIC_KEY'),
        'bank_code' => env('PALMPAY_BANK_CODE', '100033'), // Dynamic from env
        'bank_name' => env('PALMPAY_BANK_NAME', 'PalmPay'), // Dynamic from env
    ],

    /*
    |--------------------------------------------------------------------------
    | EaseID KYC Configuration
    |--------------------------------------------------------------------------
    */
    'easeid' => [
        'app_id' => env('EASEID_APP_ID'),
        'merchant_id' => env('EASEID_MERCHANT_ID'),
        'private_key' => env('EASEID_PRIVATE_KEY'),
        'public_key' => env('EASEID_PUBLIC_KEY'),
        'platform_public_key' => env('EASEID_PLATFORM_PUBLIC_KEY'),
        'base_url' => env('EASEID_BASE_URL', 'https://open-api.easeid.ai'),
    ],

    /*
    |--------------------------------------------------------------------------
    | VTPass Configuration
    |--------------------------------------------------------------------------
    */
    'vtpass' => [
        'api_key' => env('VTPASS_API_KEY'),
        'public_key' => env('VTPASS_PUBLIC_KEY'),
        'secret_key' => env('VTPASS_SECRET_KEY'),
        'base_url' => env('VTPASS_BASE_URL', 'https://vtpass.com/api'),
        'sandbox_mode' => env('VTPASS_SANDBOX', false),
    ],

];
