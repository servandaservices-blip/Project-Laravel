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

    'smartpresence' => [
        'base_url' => env('SMARTPRESENCE_BASE_URL', 'https://api.smartpresence.id/v1/customrequest'),
        'companies' => [
            'servanda' => [
                'company_id' => (int) env('SMARTPRESENCE_SERVANDA_COMPANY_ID', 1),
                'label' => 'Servanda',
                'api_key' => env('SMARTPRESENCE_SERVANDA_API_KEY'),
            ],
            'gabe' => [
                'company_id' => (int) env('SMARTPRESENCE_GABE_COMPANY_ID', 4),
                'label' => 'Gabe',
                'api_key' => env('SMARTPRESENCE_GABE_API_KEY'),
            ],
            'salus' => [
                'company_id' => (int) env('SMARTPRESENCE_SALUS_COMPANY_ID', 3),
                'label' => 'Salus',
                'api_key' => env('SMARTPRESENCE_SALUS_API_KEY'),
            ],
        ],
    ],

];
