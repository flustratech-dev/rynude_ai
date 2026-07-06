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

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1/messages'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'google' => [
        'key' => env('GOOGLE_API_KEY'),
        'base_url' => env('GOOGLE_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    'mistral' => [
        'key' => env('MISTRAL_API_KEY'),
        'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
    ],

    'search' => [
        'provider' => env('SEARCH_PROVIDER', 'duckduckgo'),
        'key' => env('SEARCH_API_KEY'),
    ],

    // Dedicated local GGUF inference engine (Model Hub models). Runs on its own
    // port, completely separate from Ollama (11434) and 9router (20128).
    'local_gguf' => [
        'port' => env('LOCAL_GGUF_PORT', 8091),
        'base_url' => env('LOCAL_GGUF_BASE_URL'),
        'node' => env('LOCAL_GGUF_NODE', 'node'),
    ],

];
