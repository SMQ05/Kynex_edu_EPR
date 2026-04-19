<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Pages;

use App\Models\PlatformApiSetting;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * API Settings Page — Manage platform-level API credentials.
 *
 * Configures:
 *   - SMS Gateway (Android SMS Gateway by capcom6)
 *   - WhatsApp API (Evolution API)
 *   - AI Model (OpenRouter.ai)
 */
class ApiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'API Settings';

    protected static ?string $title = 'API & Integration Settings';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.saas-admin.pages.api-settings';

    // ── Form State ──────────────────────────────────────────────

    // SMS fields
    public bool $sms_enabled = false;
    public string $sms_api_url = '';
    public string $sms_login = '';
    public string $sms_password = '';

    // WhatsApp fields
    public bool $whatsapp_enabled = false;
    public string $whatsapp_api_url = '';
    public string $whatsapp_api_key = '';
    public string $whatsapp_instance_name = '';
    public string $whatsapp_webhook_url = '';

    // AI fields
    public bool $ai_enabled = false;
    public string $ai_api_key = '';
    public string $ai_default_model = 'openai/gpt-4o-mini';
    public string $ai_default_model_custom = '';
    public string $ai_site_url = '';
    public string $ai_site_name = 'KynexEdu ERP';
    public string $ai_max_tokens = '4096';
    public string $ai_monthly_budget = '50';

    // ── Lifecycle ───────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        // SMS
        $sms = PlatformApiSetting::getGroup('sms');
        $this->sms_enabled  = ($sms['enabled'] ?? '0') === '1';
        $this->sms_api_url  = $sms['api_url'] ?? '';
        $this->sms_login    = $sms['login'] ?? '';
        $this->sms_password = $sms['password'] ?? '';

        // WhatsApp
        $wa = PlatformApiSetting::getGroup('whatsapp');
        $this->whatsapp_enabled       = ($wa['enabled'] ?? '0') === '1';
        $this->whatsapp_api_url       = $wa['api_url'] ?? '';
        $this->whatsapp_api_key       = $wa['api_key'] ?? '';
        $this->whatsapp_instance_name = $wa['instance_name'] ?? '';
        $this->whatsapp_webhook_url   = $wa['webhook_url'] ?? '';

        // AI
        $ai = PlatformApiSetting::getGroup('ai');
        $this->ai_enabled        = ($ai['enabled'] ?? '0') === '1';
        $this->ai_api_key        = $ai['api_key'] ?? '';
        $knownModels = ['openai/gpt-4o-mini','openai/gpt-4o','openai/gpt-4.1','anthropic/claude-sonnet-4',
                        'anthropic/claude-haiku','google/gemini-2.5-flash','google/gemini-2.5-pro',
                        'meta-llama/llama-4-scout','deepseek/deepseek-chat-v3'];
        $savedModel = $ai['default_model'] ?? 'openai/gpt-4o-mini';
        if (in_array($savedModel, $knownModels, true)) {
            $this->ai_default_model = $savedModel;
        } else {
            $this->ai_default_model = 'custom';
            $this->ai_default_model_custom = $savedModel;
        }
        $this->ai_site_url       = $ai['site_url'] ?? '';
        $this->ai_site_name      = $ai['site_name'] ?? 'KynexEdu ERP';
        $this->ai_max_tokens     = $ai['max_tokens'] ?? '4096';
        $this->ai_monthly_budget = $ai['monthly_budget'] ?? '50';
    }

    // ── Named Forms (Filament v5) ───────────────────────────────

    public function smsForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('📱 SMS — Android SMS Gateway')
                    ->description('Send SMS via your Android phone. Install the [Android SMS Gateway](https://github.com/capcom6/android-sms-gateway) app on any Android device, enable the server, and configure credentials below.')
                    ->collapsible()
                    ->schema([
                        Components\Toggle::make('sms_enabled')
                            ->label('Enable SMS Gateway')
                            ->live(),

                        Components\TextInput::make('sms_api_url')
                            ->label('Gateway API URL')
                            ->placeholder('https://sms.example.com or http://192.168.1.x:8080')
                            ->url()
                            ->helperText('The URL of your Android SMS Gateway server (local or cloud)')
                            ->visible(fn () => $this->sms_enabled),

                        Components\TextInput::make('sms_login')
                            ->label('Login / Username')
                            ->placeholder('Your gateway login')
                            ->helperText('Basic auth username configured in the Android app')
                            ->visible(fn () => $this->sms_enabled),

                        Components\TextInput::make('sms_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->placeholder('••••••••')
                            ->helperText('Basic auth password configured in the Android app')
                            ->visible(fn () => $this->sms_enabled),
                    ]),
            ])
            ->statePath('');
    }

    public function whatsappForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('💬 WhatsApp — Evolution API')
                    ->description('Self-hosted WhatsApp API gateway. Deploy [Evolution API](https://github.com/EvolutionAPI/evolution-api) and configure credentials below. Supports multi-device, webhooks, groups, and media.')
                    ->collapsible()
                    ->schema([
                        Components\Toggle::make('whatsapp_enabled')
                            ->label('Enable WhatsApp API')
                            ->live(),

                        Components\TextInput::make('whatsapp_api_url')
                            ->label('Evolution API URL')
                            ->placeholder('https://evolution.yourdomain.com')
                            ->url()
                            ->helperText('Base URL of your Evolution API instance')
                            ->visible(fn () => $this->whatsapp_enabled),

                        Components\TextInput::make('whatsapp_api_key')
                            ->label('Global API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('Your Evolution API key')
                            ->helperText('The global API key from your Evolution API configuration')
                            ->visible(fn () => $this->whatsapp_enabled),

                        Components\TextInput::make('whatsapp_instance_name')
                            ->label('Default Instance Name')
                            ->placeholder('kynexedu-main')
                            ->helperText('The WhatsApp instance name to use for sending messages')
                            ->visible(fn () => $this->whatsapp_enabled),

                        Components\TextInput::make('whatsapp_webhook_url')
                            ->label('Webhook URL (incoming)')
                            ->placeholder('https://yourdomain.com/webhook/whatsapp')
                            ->url()
                            ->helperText('URL where Evolution API will send incoming message webhooks')
                            ->visible(fn () => $this->whatsapp_enabled),
                    ]),
            ])
            ->statePath('');
    }

    public function aiForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('🤖 AI — OpenRouter')
                    ->description('OpenRouter provides unified access to 100+ AI models (GPT-4, Claude, Gemini, etc). Get your API key at [openrouter.ai](https://openrouter.ai/). **Without a valid API key, AI features will not work.**')
                    ->collapsible()
                    ->schema([
                        Components\Toggle::make('ai_enabled')
                            ->label('Enable AI Features')
                            ->live()
                            ->helperText('AI features require a valid OpenRouter API key'),

                        Components\TextInput::make('ai_api_key')
                            ->label('OpenRouter API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('sk-or-v1-xxxxxxxxxxxx')
                            ->helperText('Get your key at https://openrouter.ai/keys — Required for all AI features')
                            ->visible(fn () => $this->ai_enabled),

                        Components\Select::make('ai_default_model')
                            ->label('Default AI Model')
                            ->options([
                                'openai/gpt-4o-mini'        => 'GPT-4o Mini — Fast & Cheap ($0.15/1M input)',
                                'openai/gpt-4o'             => 'GPT-4o — Balanced ($2.50/1M input)',
                                'openai/gpt-4.1'            => 'GPT-4.1 — Latest ($2.00/1M input)',
                                'anthropic/claude-sonnet-4' => 'Claude Sonnet 4 ($3.00/1M input)',
                                'anthropic/claude-haiku'    => 'Claude Haiku — Fast ($0.25/1M input)',
                                'google/gemini-2.5-flash'   => 'Gemini 2.5 Flash ($0.15/1M input)',
                                'google/gemini-2.5-pro'     => 'Gemini 2.5 Pro ($1.25/1M input)',
                                'meta-llama/llama-4-scout'  => 'Llama 4 Scout — Free Tier',
                                'deepseek/deepseek-chat-v3' => 'DeepSeek V3 — Budget ($0.14/1M input)',
                                'custom'                    => '— Enter custom model ID —',
                            ])
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->helperText('This model will be used by default for AI-powered features across the platform. Browse all models at openrouter.ai/models')
                            ->visible(fn () => $this->ai_enabled),

                        Components\TextInput::make('ai_default_model_custom')
                            ->label('Custom Model ID')
                            ->placeholder('e.g. mistralai/mistral-7b-instruct or qwen/qwen-2.5-72b-instruct')
                            ->helperText('Enter the exact model ID from openrouter.ai/models — format: provider/model-name')
                            ->visible(fn () => $this->ai_enabled && $this->ai_default_model === 'custom'),

                        Components\TextInput::make('ai_site_url')
                            ->label('Site URL')
                            ->placeholder('https://kynexedu.com')
                            ->url()
                            ->helperText('Sent as HTTP-Referer to OpenRouter for ranking/analytics')
                            ->visible(fn () => $this->ai_enabled),

                        Components\TextInput::make('ai_site_name')
                            ->label('Site Name')
                            ->placeholder('KynexEdu ERP')
                            ->helperText('Sent as X-OpenRouter-Title header to OpenRouter for leaderboard attribution')
                            ->visible(fn () => $this->ai_enabled),

                        Components\TextInput::make('ai_max_tokens')
                            ->label('Default Max Tokens')
                            ->numeric()
                            ->placeholder('4096')
                            ->helperText('Maximum tokens per AI response (higher = more detailed but costlier)')
                            ->visible(fn () => $this->ai_enabled),

                        Components\TextInput::make('ai_monthly_budget')
                            ->label('Monthly Budget Limit (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('50')
                            ->helperText('Platform-wide monthly spending cap on OpenRouter. Set to 0 for unlimited.')
                            ->visible(fn () => $this->ai_enabled),
                    ]),
            ])
            ->statePath('');
    }

    /**
     * Register all named forms for Filament v5.
     */
    protected function getForms(): array
    {
        return [
            'smsForm',
            'whatsappForm',
            'aiForm',
        ];
    }

    // ── Save Action ─────────────────────────────────────────────

    public function saveSmsSettings(): void
    {
        PlatformApiSetting::set('sms', 'enabled', $this->sms_enabled ? '1' : '0');
        PlatformApiSetting::set('sms', 'api_url', $this->sms_api_url);
        PlatformApiSetting::set('sms', 'login', $this->sms_login);
        PlatformApiSetting::set('sms', 'password', $this->sms_password, encrypted: true);

        Notification::make()
            ->title('SMS Gateway settings saved')
            ->success()
            ->send();
    }

    public function saveWhatsappSettings(): void
    {
        PlatformApiSetting::set('whatsapp', 'enabled', $this->whatsapp_enabled ? '1' : '0');
        PlatformApiSetting::set('whatsapp', 'api_url', $this->whatsapp_api_url);
        PlatformApiSetting::set('whatsapp', 'api_key', $this->whatsapp_api_key, encrypted: true);
        PlatformApiSetting::set('whatsapp', 'instance_name', $this->whatsapp_instance_name);
        PlatformApiSetting::set('whatsapp', 'webhook_url', $this->whatsapp_webhook_url);

        Notification::make()
            ->title('WhatsApp Evolution API settings saved')
            ->success()
            ->send();
    }

    public function saveAiSettings(): void
    {
        if ($this->ai_enabled && empty($this->ai_api_key)) {
            Notification::make()
                ->title('OpenRouter API key is required')
                ->body('You must provide a valid API key from openrouter.ai to enable AI features.')
                ->danger()
                ->send();
            return;
        }

        PlatformApiSetting::set('ai', 'enabled', $this->ai_enabled ? '1' : '0');
        PlatformApiSetting::set('ai', 'api_key', $this->ai_api_key, encrypted: true);
        $modelToSave = $this->ai_default_model === 'custom'
            ? trim($this->ai_default_model_custom)
            : $this->ai_default_model;
        PlatformApiSetting::set('ai', 'default_model', $modelToSave ?: 'openai/gpt-4o-mini');
        PlatformApiSetting::set('ai', 'site_url', $this->ai_site_url);
        PlatformApiSetting::set('ai', 'site_name', $this->ai_site_name);
        PlatformApiSetting::set('ai', 'max_tokens', $this->ai_max_tokens);
        PlatformApiSetting::set('ai', 'monthly_budget', $this->ai_monthly_budget);

        Notification::make()
            ->title('AI OpenRouter settings saved')
            ->success()
            ->send();
    }

    public function saveAllSettings(): void
    {
        $this->saveSmsSettings();
        $this->saveWhatsappSettings();
        $this->saveAiSettings();

        Notification::make()
            ->title('All API settings saved successfully')
            ->success()
            ->send();
    }

    // ── Test Connection Actions ──────────────────────────────────

    public function testSmsConnection(): void
    {
        if (empty($this->sms_api_url)) {
            Notification::make()->title('SMS Gateway URL is required')->danger()->send();
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                $this->sms_login,
                $this->sms_password,
            )->timeout(10)->get(rtrim($this->sms_api_url, '/') . '/api/v1/health');

            if ($response->successful()) {
                Notification::make()
                    ->title('SMS Gateway connection successful ✅')
                    ->body('Android SMS Gateway is reachable and responding.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('SMS Gateway returned error')
                    ->body('HTTP ' . $response->status() . ': ' . $response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('SMS Gateway connection failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testWhatsappConnection(): void
    {
        if (empty($this->whatsapp_api_url)) {
            Notification::make()->title('Evolution API URL is required')->danger()->send();
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'apikey' => $this->whatsapp_api_key,
            ])->timeout(10)->get(rtrim($this->whatsapp_api_url, '/') . '/instance/fetchInstances');

            if ($response->successful()) {
                $instances = $response->json();
                $count = is_array($instances) ? count($instances) : 0;
                Notification::make()
                    ->title('Evolution API connection successful ✅')
                    ->body("Found {$count} instance(s) on the server.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Evolution API returned error')
                    ->body('HTTP ' . $response->status() . ': ' . $response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Evolution API connection failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testAiConnection(): void
    {
        if (empty($this->ai_api_key)) {
            Notification::make()->title('OpenRouter API key is required')->danger()->send();
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization'      => 'Bearer ' . $this->ai_api_key,
                'HTTP-Referer'       => $this->ai_site_url ?: config('app.url'),
                'X-OpenRouter-Title' => $this->ai_site_name ?: config('app.name'),
            ])->timeout(15)->get('https://openrouter.ai/api/v1/models');

            if ($response->successful()) {
                $models = $response->json('data', []);
                $count = is_array($models) ? count($models) : 0;
                Notification::make()
                    ->title('OpenRouter connection successful ✅')
                    ->body("API key is valid. {$count} models available.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('OpenRouter returned error')
                    ->body('HTTP ' . $response->status() . ': ' . ($response->json('error.message') ?? $response->body()))
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('OpenRouter connection failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
