<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Support\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class LanguageSettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 65;

    protected static ?string $navigationLabel = 'Language Settings';

    protected static ?string $title = 'Language Settings';

    protected string $view = 'filament.school-admin.pages.settings-form';

    public const LOCALES = [
        'en'  => 'English',
        'ur'  => 'Urdu (اردو)',
        'ar'  => 'Arabic (العربية)',
        'fr'  => 'French (Français)',
        'es'  => 'Spanish (Español)',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'available_locales' => SchoolSettings::get('locale.available', ['en']),
            'default_locale'    => SchoolSettings::get('locale.default', 'en'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Languages')
                    ->description('Choose which languages the school offers and the default.')
                    ->schema([
                        CheckboxList::make('available_locales')
                            ->label('Available languages')
                            ->options(self::LOCALES)
                            ->columns(2)
                            ->live()
                            ->bulkToggleable(),
                        Select::make('default_locale')
                            ->label('Default language')
                            ->options(fn (Get $get): array => array_intersect_key(
                                self::LOCALES,
                                array_flip($get('available_locales') ?: array_keys(self::LOCALES)),
                            ))
                            ->required()
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $available = array_values($state['available_locales'] ?? ['en']);
        if ($available === []) {
            $available = ['en'];
        }

        $default = $state['default_locale'] ?? 'en';
        if (! in_array($default, $available, true)) {
            $default = $available[0];
        }

        SchoolSettings::setMany([
            'locale.available' => $available,
            'locale.default'   => $default,
        ], group: 'locale');

        Notification::make()->title('Language settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
