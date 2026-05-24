<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Support\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Crypt;

/**
 * Online payment gateway configuration. Secret keys are stored
 * encrypted (via Crypt) inside the school_settings JSON value, and
 * are never re-displayed once saved (write-only "leave blank to keep").
 */
class PaymentSettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 64;

    protected static ?string $navigationLabel = 'Payment Settings';

    protected static ?string $title = 'Payment Gateway Settings';

    protected string $view = 'filament.school-admin.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'enabled'        => (bool) SchoolSettings::get('payment.enabled', false),
            'gateway'        => SchoolSettings::get('payment.gateway', 'stripe'),
            'mode'           => SchoolSettings::get('payment.mode', 'test'),
            'public_key'     => SchoolSettings::get('payment.public_key', ''),
            // Secrets are write-only: show a masked placeholder, never the value.
            'secret_key'     => '',
            'webhook_secret' => '',
            'has_secret_key'     => filled(SchoolSettings::get('payment.secret_key_enc')),
            'has_webhook_secret' => filled(SchoolSettings::get('payment.webhook_secret_enc')),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Online Payments')
                    ->description('Configure the gateway used to accept online fee payments. Secret keys are stored encrypted.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enabled')->label('Enable online payments')->columnSpanFull(),
                        Select::make('gateway')
                            ->options([
                                'stripe'    => 'Stripe',
                                'paypal'    => 'PayPal',
                                'razorpay'  => 'Razorpay',
                                'jazzcash'  => 'JazzCash',
                                'easypaisa' => 'Easypaisa',
                                'manual'    => 'Manual / Bank transfer',
                            ])
                            ->default('stripe')
                            ->native(false),
                        Select::make('mode')
                            ->options(['test' => 'Test / Sandbox', 'live' => 'Live'])
                            ->default('test')
                            ->native(false),
                        TextInput::make('public_key')
                            ->label('Public / Publishable key')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('secret_key')
                            ->label('Secret key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText(fn (): string => ($this->data['has_secret_key'] ?? false)
                                ? 'A secret key is already saved. Leave blank to keep it, or enter a new one to replace.'
                                : 'Stored encrypted.')
                            ->columnSpanFull(),
                        TextInput::make('webhook_secret')
                            ->label('Webhook signing secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText(fn (): string => ($this->data['has_webhook_secret'] ?? false)
                                ? 'Already saved. Leave blank to keep, or enter a new one.'
                                : 'Stored encrypted.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $values = [
            'payment.enabled'    => (bool) ($state['enabled'] ?? false),
            'payment.gateway'    => $state['gateway'] ?? 'stripe',
            'payment.mode'       => $state['mode'] ?? 'test',
            'payment.public_key' => $state['public_key'] ?? '',
        ];

        // Only overwrite secrets when a new value is entered.
        if (filled($state['secret_key'] ?? null)) {
            $values['payment.secret_key_enc'] = Crypt::encryptString((string) $state['secret_key']);
        }
        if (filled($state['webhook_secret'] ?? null)) {
            $values['payment.webhook_secret_enc'] = Crypt::encryptString((string) $state['webhook_secret']);
        }

        SchoolSettings::setMany($values, group: 'payment');

        // Reset write-only fields and refresh the "already saved" flags.
        $this->data['secret_key'] = '';
        $this->data['webhook_secret'] = '';
        $this->data['has_secret_key'] = filled(SchoolSettings::get('payment.secret_key_enc'));
        $this->data['has_webhook_secret'] = filled(SchoolSettings::get('payment.webhook_secret_enc'));

        Notification::make()->title('Payment settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
