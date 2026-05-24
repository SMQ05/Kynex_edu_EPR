<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Support\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Misc. preferences that are single toggles in InfixEdu: Preloader
 * Settings and Two-Factor Setting. Combined into one page to keep the
 * Settings navigation lean; each lives in its own clearly-labelled
 * section and is stored independently via SchoolSettings.
 */
class GeneralPreferencesPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 66;

    protected static ?string $navigationLabel = 'Preferences';

    protected static ?string $title = 'General Preferences';

    protected string $view = 'filament.school-admin.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'preloader_enabled' => (bool) SchoolSettings::get('preloader.enabled', false),
            'preloader_style'   => SchoolSettings::get('preloader.style', 'spinner'),
            'two_factor_enabled' => (bool) SchoolSettings::get('security.two_factor_enabled', false),
            'two_factor_channel' => SchoolSettings::get('security.two_factor_channel', 'email'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Preloader')
                    ->description('Show a loading animation while the public website loads.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('preloader_enabled')->label('Enable preloader')->columnSpanFull(),
                        Select::make('preloader_style')
                            ->options(['spinner' => 'Spinner', 'dots' => 'Dots', 'bar' => 'Progress bar', 'logo' => 'Logo pulse'])
                            ->default('spinner')
                            ->native(false),
                    ]),

                Section::make('Two-Factor Authentication')
                    ->description('Require a second verification step when staff log in.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('two_factor_enabled')->label('Require two-factor authentication')->columnSpanFull(),
                        Select::make('two_factor_channel')
                            ->label('Delivery channel')
                            ->options(['email' => 'Email', 'sms' => 'SMS', 'authenticator' => 'Authenticator app'])
                            ->default('email')
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        SchoolSettings::setMany([
            'preloader.enabled' => (bool) ($state['preloader_enabled'] ?? false),
            'preloader.style'   => $state['preloader_style'] ?? 'spinner',
        ], group: 'preloader');

        SchoolSettings::setMany([
            'security.two_factor_enabled' => (bool) ($state['two_factor_enabled'] ?? false),
            'security.two_factor_channel' => $state['two_factor_channel'] ?? 'email',
        ], group: 'security');

        Notification::make()->title('Preferences saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
