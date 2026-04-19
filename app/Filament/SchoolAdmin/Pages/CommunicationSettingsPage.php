<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * CommunicationSettingsPage — School admin configures their WhatsApp & SMS channels.
 *
 * Each WhatsApp/SMS driver shows only the fields that driver actually needs,
 * based on the official API documentation for each provider.
 */
class CommunicationSettingsPage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_communication_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Communication Setup';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 31;

    protected string $view = 'filament.school-admin.pages.communication-settings';

    // ── WhatsApp state ─────────────────────────────────────────────
    public string $whatsapp_channel        = 'none';

    // Evolution API
    public string $evo_base_url            = '';
    public string $evo_api_key             = '';
    public string $evo_instance_name       = '';

    // SendPK WhatsApp (wa.sendpk.com)
    public string $sendpk_wa_api_key       = '';
    public string $sendpk_wa_whatsapp_id   = '';

    // Meta Official Cloud API
    public string $meta_phone_number_id    = '';
    public string $meta_access_token       = '';
    public string $meta_waba_id            = '';
    public string $meta_verify_token       = '';

    // Twilio WhatsApp
    public string $twilio_wa_account_sid   = '';
    public string $twilio_wa_auth_token    = '';
    public string $twilio_wa_from_number   = '';

    // ── SMS state ──────────────────────────────────────────────────
    public string $sms_channel             = 'none';

    // Android SMS Gateway
    public string $android_mode            = 'cloud';
    public string $android_server_url      = '';
    public string $android_login           = '';
    public string $android_password        = '';

    // SendPK SMS (sendpk.com)
    public string $sendpk_sms_username     = '';
    public string $sendpk_sms_password     = '';
    public string $sendpk_sms_sender       = '';

    // Jazz SMS
    public string $jazz_username           = '';
    public string $jazz_password           = '';
    public string $jazz_mask               = '';

    // Telenor SMS
    public string $telenor_api_key         = '';
    public string $telenor_sender_id       = '';

    // Twilio SMS
    public string $twilio_sms_account_sid  = '';
    public string $twilio_sms_auth_token   = '';
    public string $twilio_sms_from_number  = '';

    // ── Lifecycle ──────────────────────────────────────────────────

    public function getTitle(): string|Htmlable
    {
        return 'Communication Setup';
    }

    public function mount(): void
    {
        $tenant = tenant();
        if (! $tenant) {
            return;
        }

        // WhatsApp
        $this->whatsapp_channel = $tenant->whatsapp_channel ?? 'none';
        $wa = $tenant->whatsapp_config ?? [];

        $this->evo_base_url          = $wa['base_url'] ?? '';
        $this->evo_api_key           = $wa['api_key'] ?? '';
        $this->evo_instance_name     = $wa['instance_name'] ?? '';
        $this->sendpk_wa_api_key     = $wa['api_key'] ?? '';
        $this->sendpk_wa_whatsapp_id = $wa['whatsapp_id'] ?? '';
        $this->meta_phone_number_id  = $wa['phone_number_id'] ?? '';
        $this->meta_access_token     = $wa['access_token'] ?? '';
        $this->meta_waba_id          = $wa['waba_id'] ?? '';
        $this->meta_verify_token     = $wa['verify_token'] ?? '';
        $this->twilio_wa_account_sid = $wa['account_sid'] ?? '';
        $this->twilio_wa_auth_token  = $wa['auth_token'] ?? '';
        $this->twilio_wa_from_number = $wa['from_number'] ?? '';

        // SMS
        $this->sms_channel = $tenant->sms_channel ?? 'none';
        $sms = $tenant->sms_config ?? [];

        $this->android_mode           = $sms['mode'] ?? 'cloud';
        $this->android_server_url     = $sms['server_url'] ?? '';
        $this->android_login          = $sms['login'] ?? '';
        $this->android_password       = $sms['password'] ?? '';
        $this->sendpk_sms_username    = $sms['username'] ?? '';
        $this->sendpk_sms_password    = $sms['password'] ?? '';
        $this->sendpk_sms_sender      = $sms['sender'] ?? '';
        $this->jazz_username          = $sms['username'] ?? '';
        $this->jazz_password          = $sms['password'] ?? '';
        $this->jazz_mask              = $sms['mask'] ?? '';
        $this->telenor_api_key        = $sms['api_key'] ?? '';
        $this->telenor_sender_id      = $sms['sender_id'] ?? '';
        $this->twilio_sms_account_sid = $sms['account_sid'] ?? '';
        $this->twilio_sms_auth_token  = $sms['auth_token'] ?? '';
        $this->twilio_sms_from_number = $sms['from_number'] ?? '';
    }

    // ── Forms ──────────────────────────────────────────────────────

    public function whatsappForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('💬 WhatsApp Channel')
                ->description('Choose your WhatsApp provider and enter the required credentials.')
                ->schema([
                    Select::make('whatsapp_channel')
                        ->label('WhatsApp Provider')
                        ->options([
                            'none'            => '— Disabled —',
                            'evolution'       => 'Evolution API (Free, Self-Hosted)',
                            'sendpk_whatsapp' => 'SendPK WhatsApp (wa.sendpk.com) — Pakistan Official',
                            'meta_official'   => 'Meta Official Cloud API (Facebook)',
                            'twilio_whatsapp' => 'Twilio WhatsApp',
                        ])
                        ->native(false)
                        ->live()
                        ->helperText('Evolution API is free and recommended for most schools.'),

                    // ── Evolution API fields ───────────────────────
                    Section::make('Evolution API Settings')
                        ->description('Self-hosted WhatsApp gateway. Your KynexEdu platform runs a shared Evolution server — contact your platform admin for the URL and API key.')
                        ->schema([
                            TextInput::make('evo_base_url')
                                ->label('Evolution API Server URL')
                                ->placeholder('https://evo.kynexedu.com')
                                ->url()
                                ->helperText('Provided by your KynexEdu platform admin'),

                            TextInput::make('evo_api_key')
                                ->label('API Key')
                                ->password()
                                ->revealable()
                                ->placeholder('Your Evolution API key')
                                ->helperText('The global API key for the Evolution server'),

                            TextInput::make('evo_instance_name')
                                ->label('Instance Name')
                                ->placeholder('your-school-id')
                                ->helperText('Your school\'s unique WhatsApp instance name — provided by platform admin. Each school gets one instance connected to their own WhatsApp number.'),
                        ])
                        ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'evolution')
                        ->compact(),

                    // ── SendPK WhatsApp fields ─────────────────────
                    Section::make('SendPK WhatsApp Settings')
                        ->description('Official Pakistan WhatsApp provider. Get your API key from [wa.sendpk.com](https://wa.sendpk.com) after registering.')
                        ->schema([
                            TextInput::make('sendpk_wa_api_key')
                                ->label('API Key')
                                ->password()
                                ->revealable()
                                ->placeholder('Your SendPK API key')
                                ->helperText('Found in your wa.sendpk.com account dashboard')
                                ->required(),

                            TextInput::make('sendpk_wa_whatsapp_id')
                                ->label('WhatsApp ID (Optional)')
                                ->placeholder('e.g. 923001234567')
                                ->helperText('Send from a specific WhatsApp number if your account has multiple. Leave blank to use default.'),
                        ])
                        ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'sendpk_whatsapp')
                        ->compact(),

                    // ── Meta Official API fields ───────────────────
                    Section::make('Meta Official Cloud API Settings')
                        ->description('Direct integration with Meta\'s WhatsApp Business Cloud API. Requires a verified Meta Business account. [Setup guide →](https://developers.facebook.com/docs/whatsapp/cloud-api/get-started)')
                        ->schema([
                            TextInput::make('meta_phone_number_id')
                                ->label('Phone Number ID')
                                ->placeholder('123456789012345')
                                ->helperText('Found in Meta Developer Console → WhatsApp → API Setup → Phone Number ID')
                                ->required(),

                            TextInput::make('meta_access_token')
                                ->label('Permanent Access Token')
                                ->password()
                                ->revealable()
                                ->placeholder('EAAxxxxxxxxxxxxx...')
                                ->helperText('Generate a permanent system user token in Meta Business Manager → System Users')
                                ->required(),

                            TextInput::make('meta_waba_id')
                                ->label('WhatsApp Business Account ID (WABA ID)')
                                ->placeholder('123456789012345')
                                ->helperText('Found in Meta Developer Console → WhatsApp Business Account ID'),

                            TextInput::make('meta_verify_token')
                                ->label('Webhook Verify Token')
                                ->placeholder('any-secret-string-you-choose')
                                ->helperText('Enter any secret string here AND paste it in Meta Developer Console → WhatsApp → Configuration → Webhook → Verify Token. Must match exactly so Meta can verify your webhook URL.')
                                ->required(),
                        ])
                        ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'meta_official')
                        ->compact(),

                    // ── Twilio WhatsApp fields ─────────────────────
                    Section::make('Twilio WhatsApp Settings')
                        ->description('Send WhatsApp via Twilio. Requires a Twilio account with a WhatsApp-enabled number. [Twilio Console →](https://console.twilio.com)')
                        ->schema([
                            TextInput::make('twilio_wa_account_sid')
                                ->label('Account SID')
                                ->placeholder('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                ->helperText('Found on your Twilio Console dashboard')
                                ->required(),

                            TextInput::make('twilio_wa_auth_token')
                                ->label('Auth Token')
                                ->password()
                                ->revealable()
                                ->placeholder('Your Twilio auth token')
                                ->helperText('Found on your Twilio Console dashboard (keep this secret)')
                                ->required(),

                            TextInput::make('twilio_wa_from_number')
                                ->label('Twilio WhatsApp Number')
                                ->placeholder('whatsapp:+14155238886')
                                ->helperText('Your Twilio WhatsApp sender in format: whatsapp:+1XXXXXXXXXX')
                                ->required(),
                        ])
                        ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'twilio_whatsapp')
                        ->compact(),
                ]),
        ])->statePath('');
    }

    public function smsForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('📱 SMS Channel')
                ->description('Choose your SMS provider and enter the required credentials.')
                ->schema([
                    Select::make('sms_channel')
                        ->label('SMS Provider')
                        ->options([
                            'none'                => '— Disabled —',
                            'android_sms_gateway' => 'Android SMS Gateway (Free — uses your Android phone)',
                            'sendpk'              => 'SendPK SMS (sendpk.com) — Pakistan Official',
                            'jazz_sms'            => 'Jazz SMS (Pakistan)',
                            'telenor_sms'         => 'Telenor SMS (Pakistan)',
                            'twilio_sms'          => 'Twilio SMS',
                        ])
                        ->native(false)
                        ->live()
                        ->helperText('Android SMS Gateway is free — just install the app on any Android phone.'),

                    // ── Android SMS Gateway fields ─────────────────
                    Section::make('Android SMS Gateway Settings')
                        ->description('Free SMS via your own Android phone. Install the [Android SMS Gateway app](https://github.com/capcom6/android-sms-gateway) on any Android device.')
                        ->schema([
                            Select::make('android_mode')
                                ->label('Mode')
                                ->options([
                                    'cloud'   => 'Cloud Mode (api.sms-gate.app) — phone sends via cloud relay',
                                    'private' => 'Private Mode — phone on your local/VPN network',
                                ])
                                ->native(false)
                                ->live()
                                ->helperText('Cloud mode works anywhere. Private mode requires the phone and server on the same network.'),

                            TextInput::make('android_server_url')
                                ->label('Private Server URL')
                                ->placeholder('http://192.168.1.x:8080')
                                ->url()
                                ->helperText('Your Android phone\'s local IP + port from the app settings')
                                ->visible(fn (Get $get): bool => $get('android_mode') === 'private'),

                            TextInput::make('android_login')
                                ->label('Login / Username')
                                ->placeholder('Your gateway username')
                                ->helperText('Username set in the Android SMS Gateway app')
                                ->required(),

                            TextInput::make('android_password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->placeholder('Your gateway password')
                                ->helperText('Password set in the Android SMS Gateway app')
                                ->required(),
                        ])
                        ->visible(fn (Get $get): bool => $get('sms_channel') === 'android_sms_gateway')
                        ->compact(),

                    // ── SendPK SMS fields ──────────────────────────
                    Section::make('SendPK SMS Settings')
                        ->description('Official Pakistan bulk SMS provider. Register at [sendpk.com](https://sendpk.com) and apply for a sender ID (brand name).')
                        ->schema([
                            TextInput::make('sendpk_sms_username')
                                ->label('Username')
                                ->placeholder('Your sendpk.com login username')
                                ->helperText('Your login username on sendpk.com')
                                ->required(),

                            TextInput::make('sendpk_sms_password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->placeholder('Your sendpk.com password')
                                ->helperText('Your login password on sendpk.com')
                                ->required(),

                            TextInput::make('sendpk_sms_sender')
                                ->label('Sender ID (Brand Name)')
                                ->placeholder('YourSchool')
                                ->helperText('Your approved sender ID / company name shown to recipients. Must be applied for on sendpk.com.')
                                ->required(),
                        ])
                        ->visible(fn (Get $get): bool => $get('sms_channel') === 'sendpk')
                        ->compact(),

                    // ── Jazz SMS fields ────────────────────────────
                    Section::make('Jazz SMS Settings')
                        ->description('Jazz bulk SMS API for Pakistan. Contact Jazz Business to get API access.')
                        ->schema([
                            TextInput::make('jazz_username')
                                ->label('API Username')
                                ->placeholder('Your Jazz SMS username')
                                ->required(),

                            TextInput::make('jazz_password')
                                ->label('API Password')
                                ->password()
                                ->revealable()
                                ->placeholder('Your Jazz SMS password')
                                ->required(),

                            TextInput::make('jazz_mask')
                                ->label('Sender Mask (Brand Name)')
                                ->placeholder('YourSchool')
                                ->helperText('Your approved sender ID registered with Jazz')
                                ->required(),
                        ])
                        ->visible(fn (Get $get): bool => $get('sms_channel') === 'jazz_sms')
                        ->compact(),

                    // ── Telenor SMS fields ─────────────────────────
                    Section::make('Telenor SMS Settings')
                        ->description('Telenor bulk SMS API for Pakistan. Contact Telenor Business to get API access and a registered sender ID.')
                        ->schema([
                            TextInput::make('telenor_api_key')
                                ->label('API Key')
                                ->password()
                                ->revealable()
                                ->placeholder('Your Telenor SMS API key')
                                ->helperText('Provided by Telenor after business account setup')
                                ->required(),

                            TextInput::make('telenor_sender_id')
                                ->label('Sender ID')
                                ->placeholder('YourSchool')
                                ->helperText('Your registered sender ID approved by Telenor')
                                ->required(),
                        ])
                        ->visible(fn (Get $get): bool => $get('sms_channel') === 'telenor_sms')
                        ->compact(),

                    // ── Twilio SMS fields ──────────────────────────
                    Section::make('Twilio SMS Settings')
                        ->description('Send SMS via Twilio. Requires a Twilio account with a purchased phone number. [Twilio Console →](https://console.twilio.com)')
                        ->schema([
                            TextInput::make('twilio_sms_account_sid')
                                ->label('Account SID')
                                ->placeholder('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                ->helperText('Found on your Twilio Console dashboard')
                                ->required(),

                            TextInput::make('twilio_sms_auth_token')
                                ->label('Auth Token')
                                ->password()
                                ->revealable()
                                ->placeholder('Your Twilio auth token')
                                ->required(),

                            TextInput::make('twilio_sms_from_number')
                                ->label('Twilio Phone Number')
                                ->placeholder('+14155238886')
                                ->helperText('Your Twilio purchased number in E.164 format: +1XXXXXXXXXX')
                                ->required(),
                        ])
                        ->visible(fn (Get $get): bool => $get('sms_channel') === 'twilio_sms')
                        ->compact(),
                ]),
        ])->statePath('');
    }

    protected function getForms(): array
    {
        return ['whatsappForm', 'smsForm'];
    }

    // ── Save ───────────────────────────────────────────────────────

    public function saveWhatsapp(): void
    {
        $tenant = \App\Models\Tenant::find(tenant()?->id);
        if (! $tenant) {
            return;
        }

        $config = match ($this->whatsapp_channel) {
            'evolution'       => [
                'base_url'      => rtrim($this->evo_base_url, '/'),
                'api_key'       => $this->evo_api_key,
                'instance_name' => $this->evo_instance_name,
            ],
            'sendpk_whatsapp' => [
                'api_key'      => $this->sendpk_wa_api_key,
                'whatsapp_id'  => $this->sendpk_wa_whatsapp_id,
            ],
            'meta_official'   => [
                'phone_number_id' => $this->meta_phone_number_id,
                'access_token'    => $this->meta_access_token,
                'waba_id'         => $this->meta_waba_id,
                'verify_token'    => $this->meta_verify_token,
            ],
            'twilio_whatsapp' => [
                'account_sid' => $this->twilio_wa_account_sid,
                'auth_token'  => $this->twilio_wa_auth_token,
                'from_number' => $this->twilio_wa_from_number,
            ],
            default => [],
        };

        $tenant->update([
            'whatsapp_channel' => $this->whatsapp_channel,
            'whatsapp_config'  => $config,
        ]);

        Notification::make()
            ->title('WhatsApp settings saved')
            ->success()
            ->send();
    }

    public function saveSms(): void
    {
        $tenant = \App\Models\Tenant::find(tenant()?->id);
        if (! $tenant) {
            return;
        }

        $config = match ($this->sms_channel) {
            'android_sms_gateway' => [
                'mode'       => $this->android_mode,
                'server_url' => $this->android_mode === 'private' ? $this->android_server_url : 'https://api.sms-gate.app/3rdparty/v1',
                'login'      => $this->android_login,
                'password'   => $this->android_password,
            ],
            'sendpk'              => [
                'username' => $this->sendpk_sms_username,
                'password' => $this->sendpk_sms_password,
                'sender'   => $this->sendpk_sms_sender,
            ],
            'jazz_sms'            => [
                'username' => $this->jazz_username,
                'password' => $this->jazz_password,
                'mask'     => $this->jazz_mask,
            ],
            'telenor_sms'         => [
                'api_key'   => $this->telenor_api_key,
                'sender_id' => $this->telenor_sender_id,
            ],
            'twilio_sms'          => [
                'account_sid' => $this->twilio_sms_account_sid,
                'auth_token'  => $this->twilio_sms_auth_token,
                'from_number' => $this->twilio_sms_from_number,
            ],
            default => [],
        };

        $tenant->update([
            'sms_channel' => $this->sms_channel,
            'sms_config'  => $config,
        ]);

        Notification::make()
            ->title('SMS settings saved')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveWhatsapp')
                ->label('Save WhatsApp')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('saveWhatsapp'),

            Action::make('saveSms')
                ->label('Save SMS')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('saveSms'),
        ];
    }
}
