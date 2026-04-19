<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * NotificationSettingsPage — School admin controls which notification
 * channels are enabled and whether teachers can send notifications.
 *
 * Part 2b — Settings are stored on the Tenant model.
 * Updates go through tenancy's central DB (tenants table).
 */
class NotificationSettingsPage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_communication_settings';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected string $view = 'filament.school-admin.pages.notification-settings';

    protected static ?string $navigationLabel = 'Notification Settings';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 30;

    // Form state
    public bool $allow_app_notifications       = true;
    public bool $allow_whatsapp                = true;
    public bool $allow_sms                     = true;
    public bool $allow_email                   = true;
    public bool $teachers_can_use_own_whatsapp = false;
    public bool $teachers_can_send_notifications = true;

    public function getTitle(): string | Htmlable
    {
        return 'Notification Settings';
    }

    public function mount(): void
    {
        $tenant = tenant();

        if ($tenant) {
            $this->allow_app_notifications         = (bool) ($tenant->allow_app_notifications ?? true);
            $this->allow_whatsapp                  = (bool) ($tenant->allow_whatsapp ?? true);
            $this->allow_sms                       = (bool) ($tenant->allow_sms ?? true);
            $this->allow_email                     = (bool) ($tenant->allow_email ?? true);
            $this->teachers_can_use_own_whatsapp   = (bool) ($tenant->teachers_can_use_own_whatsapp ?? false);
            $this->teachers_can_send_notifications = (bool) ($tenant->teachers_can_send_notifications ?? true);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([

            Section::make('Enabled Channels')
                ->description('Control which notification channels are available for this school.')
                ->columns(2)
                ->schema([
                    Toggle::make('allow_app_notifications')
                        ->label('In-App Notifications')
                        ->helperText('Show notifications inside the school admin panel'),

                    Toggle::make('allow_whatsapp')
                        ->label('WhatsApp Notifications')
                        ->helperText('Send WhatsApp messages to parents/guardians'),

                    Toggle::make('allow_sms')
                        ->label('SMS Notifications')
                        ->helperText('Send SMS to parents/guardians'),

                    Toggle::make('allow_email')
                        ->label('Email Notifications')
                        ->helperText('Send emails to parents/guardians'),
                ]),

            Section::make('Teacher Permissions')
                ->description('Control what teachers are allowed to do with notifications.')
                ->columns(2)
                ->schema([
                    Toggle::make('teachers_can_send_notifications')
                        ->label('Teachers Can Send Notifications')
                        ->helperText('Allow teachers to use the Notification Composer'),

                    Toggle::make('teachers_can_use_own_whatsapp')
                        ->label('Teachers Can Use Own WhatsApp')
                        ->helperText('Allow teachers to send via their personal WhatsApp number (unofficial — rate limiting applies)')
                        ->visible(fn (): bool => (bool) ($this->teachers_can_send_notifications)),
                ]),

        ])->statePath('');
    }

    public function save(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            Notification::make()
                ->title('Error')
                ->body('Could not determine current school.')
                ->danger()
                ->send();
            return;
        }

        // Update notification settings on the tenant record (central DB)
        \App\Models\Tenant::find($tenant->id)?->update([
            'allow_app_notifications'         => $this->allow_app_notifications,
            'allow_whatsapp'                  => $this->allow_whatsapp,
            'allow_sms'                       => $this->allow_sms,
            'allow_email'                     => $this->allow_email,
            'teachers_can_use_own_whatsapp'   => $this->teachers_can_use_own_whatsapp,
            'teachers_can_send_notifications' => $this->teachers_can_send_notifications,
        ]);

        Notification::make()
            ->title('Settings saved')
            ->body('Notification settings have been updated.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->icon('heroicon-o-check')
                ->color('primary'),
        ];
    }
}
