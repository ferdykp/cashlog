<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'gmail' => [
        'enabled' => (bool) env('GMAIL_INTEGRATION_ENABLED', false),
        'client_id' => env('GMAIL_CLIENT_ID') ?: env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GMAIL_CLIENT_SECRET') ?: env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GMAIL_REDIRECT_URI', rtrim(env('LEDGER_PUBLIC_URL', env('APP_URL')), '/').'/auth/gmail/callback'),
        'pubsub_topic' => env('GMAIL_PUBSUB_TOPIC'),
        'pubsub_audience' => env('GMAIL_PUBSUB_AUDIENCE'),
        'pubsub_service_account' => env('GMAIL_PUBSUB_SERVICE_ACCOUNT'),
        'bank_senders' => collect(explode(',', (string) env('GMAIL_BANK_SENDERS', '')))
            ->mapWithKeys(function ($entry) {
                [$domain, $bank] = array_pad(explode(':', trim($entry), 2), 2, null);
                $domain = strtolower(trim((string) $domain));
                $bank = strtolower(trim((string) $bank));

                return $domain !== '' && in_array($bank, ['bca', 'blu', 'mandiri', 'seabank'], true)
                    ? [$domain => $bank]
                    : [];
            })->all(),
    ],

    'ledger' => [
        'url' => rtrim(env('LEDGER_PUBLIC_URL', env('APP_URL', 'http://localhost')), '/'),
    ],

    'devflow_ai' => [
        'enabled' => env('DEVFLOW_AI_ENABLED', false),
        'endpoint' => env('DEVFLOW_AI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'),
        'key' => env('DEVFLOW_AI_API_KEY'),
        'model' => env('DEVFLOW_AI_MODEL', 'gemini-3.1-flash-lite'),
    ],

    'whatsapp' => [
        'timezone' => env('WHATSAPP_TIMEZONE', 'Asia/Jakarta'),
    ],

    'evolution' => [
        'url' => env('EVOLUTION_API_URL'),
        'key' => env('EVOLUTION_API_KEY'),
        'instance' => env('EVOLUTION_INSTANCE', 'ledger'),
        'webhook_secret' => env('EVOLUTION_WEBHOOK_SECRET'),
        'webhook_jwt_secret' => env('EVOLUTION_WEBHOOK_JWT_SECRET'),
        'webhook_allow_shared_secret' => (bool) env(
            'EVOLUTION_WEBHOOK_ALLOW_SHARED_SECRET',
            env('APP_ENV', 'production') === 'local'
        ),
    ],

    'ocr' => [
        'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),
        'timeout' => env('OCR_TIMEOUT', 15),
        'retention_days' => max(1, (int) env('OCR_RETENTION_DAYS', 30)),
    ],

];
