<?php

return [

    // Product photo → 3D model for "see it in your room" (furniture). Off until a key is set.
    // Natural voices for the assistants (Azure neural text to speech). Without a key the apps use the phone's own voice.
    'azure_speech' => [
        'key' => env('AZURE_SPEECH_KEY'),
        'region' => env('AZURE_SPEECH_REGION', 'westeurope'),
        'voices' => [
            'ar' => env('AZURE_SPEECH_VOICE_AR', 'ar-JO-SanaNeural'),
            'he' => env('AZURE_SPEECH_VOICE_HE', 'he-IL-HilaNeural'),
            'en' => env('AZURE_SPEECH_VOICE_EN', 'en-US-AvaNeural'),
        ],
    ],

    'meshy' => [
        'key' => env('MESHY_API_KEY'),
        'shop_types' => ['furniture_appliances'],
    ],

    'fashn' => [
        'key' => env('FASHN_API_KEY'),
        'shops' => array_filter(explode(',', env('FASHN_ENABLED_SHOPS', ''))),
    ],

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

    // The Android app: website links open in it once Android checks /.well-known/assetlinks.json.
    // ANDROID_APP_SHA256 = the signing certificates' SHA-256 fingerprints, comma separated (public values).
    'android_app' => [
        'package' => env('ANDROID_APP_PACKAGE', 'com.ozman.ozman_app'),
        'sha256' => array_values(array_filter(array_map('trim', explode(',', (string) env('ANDROID_APP_SHA256', ''))))),
    ],

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

    'whatsapp_cloud' => [
        'token' => env('WHATSAPP_CLOUD_TOKEN'),
        'phone_number_id' => env('WHATSAPP_CLOUD_PHONE_NUMBER_ID'),
        'graph_version' => env('WHATSAPP_CLOUD_GRAPH_VERSION', 'v23.0'),
        'property_alert_template' => env('WHATSAPP_PROPERTY_ALERT_TEMPLATE'),
        'template_language' => env('WHATSAPP_PROPERTY_ALERT_LANGUAGE', 'ar'),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '972'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'ozman-app'),
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/private/firebase-service-account.json')),
    ],

    'web_push' => [
        'subject' => env('WEB_PUSH_SUBJECT', env('APP_URL', 'https://ozman.online')),
        'public_key' => env('WEB_PUSH_PUBLIC_KEY'),
        'private_key' => env('WEB_PUSH_PRIVATE_KEY'),
        'key_file' => env('WEB_PUSH_KEY_FILE', storage_path('app/private/web-push-vapid.json')),
    ],

    'zernio' => [
        'key' => env('ZERNIO_API_KEY'),
        'base_url' => env('ZERNIO_BASE_URL', 'https://zernio.com/api'),
        'timezone' => env('SOCIAL_TIMEZONE', 'Asia/Jerusalem'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-5-5'),
        // The shop owner's assistant in the dashboard and the app.
        'shop_assistant' => [
            'enabled' => env('SHOP_ASSISTANT_ENABLED', true),
            'model' => env('SHOP_ASSISTANT_MODEL'),
            'monthly_limit' => (int) env('SHOP_ASSISTANT_MONTHLY_LIMIT', 300),
        ],
        // The customers' shopping assistant on the website and in the app.
        'customer_assistant' => [
            'enabled' => env('CUSTOMER_ASSISTANT_ENABLED', true),
            'model' => env('CUSTOMER_ASSISTANT_MODEL'),
            'daily_limit' => (int) env('CUSTOMER_ASSISTANT_DAILY_LIMIT', 40),
            'site_daily_limit' => (int) env('CUSTOMER_ASSISTANT_SITE_DAILY_LIMIT', 3000),
        ],
    ],

];
