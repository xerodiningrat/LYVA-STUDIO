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

    'discord' => [
        'application_id' => env('DISCORD_APPLICATION_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect_uri' => env('DISCORD_REDIRECT_URI'),
        'public_key' => env('DISCORD_PUBLIC_KEY'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        'internal_token' => env('DISCORD_INTERNAL_TOKEN'),
        'verified_role_id' => env('DISCORD_VERIFIED_ROLE_ID'),
    ],

    'roblox' => [
        'ingest_token' => env('ROBLOX_INGEST_TOKEN'),
    ],

    'duitku' => [
        'merchant_code' => env('DUITKU_MERCHANT_CODE'),
        'api_key' => env('DUITKU_API_KEY'),
        'sandbox' => env('DUITKU_SANDBOX', true),
        'customer_email_domain' => env('DUITKU_CUSTOMER_EMAIL_DOMAIN'),
        'default_phone_number' => env('DUITKU_DEFAULT_PHONE_NUMBER'),
        'payment_method' => env('DUITKU_PAYMENT_METHOD', 'VC'),
    ],

];
