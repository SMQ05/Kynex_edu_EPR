<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway — Android SMS Gateway (capcom6/android-sms-gateway)
    |--------------------------------------------------------------------------
    |
    | Self-hosted SMS gateway using an Android device. Install the Android app,
    | enable the local/cloud server, then configure the URL and credentials here.
    |
    | GitHub: https://github.com/capcom6/android-sms-gateway
    | Docs:   https://docs.sms-gate.app/
    |
    */

    'sms' => [
        'driver'   => 'android_sms_gateway',
        'enabled'  => env('SMS_GATEWAY_ENABLED', false),
        'api_url'  => env('SMS_GATEWAY_URL', 'https://sms.example.com'),
        'login'    => env('SMS_GATEWAY_LOGIN', ''),
        'password' => env('SMS_GATEWAY_PASSWORD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp — Evolution API (EvolutionAPI/evolution-api)
    |--------------------------------------------------------------------------
    |
    | Self-hosted WhatsApp API. Supports multi-device, webhooks, and group
    | messaging. Requires a running Evolution API instance.
    |
    | GitHub: https://github.com/EvolutionAPI/evolution-api
    | Docs:   https://doc.evolution-api.com/
    |
    */

    'whatsapp' => [
        'driver'        => 'evolution_api',
        'enabled'       => env('WHATSAPP_ENABLED', false),
        'api_url'       => env('WHATSAPP_EVOLUTION_URL', 'https://evolution.yourdomain.com'),
        'api_key'       => env('WHATSAPP_EVOLUTION_TOKEN', ''),
        'instance_name' => env('WHATSAPP_EVOLUTION_INSTANCE', 'kynexedu-main'),
        'webhook_url'   => env('WHATSAPP_WEBHOOK_URL', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI — OpenRouter (openrouter.ai)
    |--------------------------------------------------------------------------
    |
    | OpenRouter provides a unified API for 100+ AI models including GPT-4,
    | Claude, Gemini, Llama, and more. You MUST have a valid API key from
    | https://openrouter.ai/ to use any AI features in the platform.
    |
    | All AI requests are routed through OpenRouter, giving tenants access
    | to the model selected by the SaaS admin without needing individual keys.
    |
    */

    'ai' => [
        'driver'         => 'openrouter',
        'enabled'        => env('AI_ENABLED', false),
        'api_key'        => env('OPENROUTER_API_KEY', ''),
        'base_url'       => 'https://openrouter.ai/api/v1',
        'default_model'  => env('OPENROUTER_DEFAULT_MODEL', 'openai/gpt-4o-mini'),
        'site_url'       => env('OPENROUTER_SITE_URL', ''),
        'site_name'      => env('OPENROUTER_SITE_NAME', 'KynexEdu ERP'),
        'max_tokens'     => (int) env('OPENROUTER_MAX_TOKENS', 4096),
        'monthly_budget' => (float) env('OPENROUTER_MONTHLY_BUDGET', 50),
    ],

];
