<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | JazzCash Payment Gateway
    |--------------------------------------------------------------------------
    */
    'jazzcash' => [
        'merchant_id'    => env('JAZZCASH_MERCHANT_ID'),
        'password'       => env('JAZZCASH_PASSWORD'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
        'env'            => env('JAZZCASH_ENV', 'sandbox'),
        'return_url'     => env('JAZZCASH_RETURN_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | EasyPaisa Payment Gateway
    |--------------------------------------------------------------------------
    */
    'easypaisa' => [
        'store_id'       => env('EASYPAISA_STORE_ID'),
        'store_password' => env('EASYPAISA_STORE_PASSWORD'),
        'env'            => env('EASYPAISA_ENV', 'sandbox'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Payment Gateway (International Schools)
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM) — Push Notifications
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
        'project_id' => env('FCM_PROJECT_ID'),
    ],

];
