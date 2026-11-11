<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for these services to locate credentials.
    |
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

    'opensign' => [
        'base_url' => env('OPENSIGN_BASE_URL', 'https://sandbox.opensignlabs.com/api/v1.2'),
        'api_token' => env('OPENSIGN_API_TOKEN'),
        'webhook_secret' => env('OPENSIGN_WEBHOOK_SECRET'),
        'sender_name' => env('OPENSIGN_SENDER_NAME', env('APP_NAME', 'InsulaCRM')),
        'sender_email' => env('OPENSIGN_SENDER_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

];
