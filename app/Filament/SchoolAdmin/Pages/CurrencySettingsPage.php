<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Support\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CurrencySettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 62;

    protected static ?string $navigationLabel = 'Manage Currency';

    protected static ?string $title = 'Manage Currency';

    protected string $view = 'filament.school-admin.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'code'              => SchoolSettings::get('currency.code', 'PKR'),
            'symbol'            => SchoolSettings::get('currency.symbol', 'Rs'),
            'symbol_position'   => SchoolSettings::get('currency.symbol_position', 'before'),
            'decimal_places'    => SchoolSettings::get('currency.decimal_places', 2),
            'thousand_separator' => SchoolSettings::get('currency.thousand_separator', ','),
            'decimal_separator' => SchoolSettings::get('currency.decimal_separator', '.'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Currency')
                    ->description('Controls how money amounts are formatted across the school.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Currency code')
                            ->required()
                            ->maxLength(8)
                            ->placeholder('PKR'),
                        TextInput::make('symbol')
                            ->label('Symbol')
                            ->required()
                            ->maxLength(8)
                            ->placeholder('Rs'),
                        Select::make('symbol_position')
                            ->options(['before' => 'Before amount (Rs 100)', 'after' => 'After amount (100 Rs)'])
                            ->default('before')
                            ->native(false),
                        TextInput::make('decimal_places')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(4)
                            ->default(2),
                        TextInput::make('thousand_separator')->maxLength(1)->default(','),
                        TextInput::make('decimal_separator')->maxLength(1)->default('.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        SchoolSettings::setMany([
            'currency.code'               => $state['code'] ?? 'PKR',
            'currency.symbol'             => $state['symbol'] ?? 'Rs',
            'currency.symbol_position'    => $state['symbol_position'] ?? 'before',
            'currency.decimal_places'     => (int) ($state['decimal_places'] ?? 2),
            'currency.thousand_separator' => $state['thousand_separator'] ?? ',',
            'currency.decimal_separator'  => $state['decimal_separator'] ?? '.',
        ], group: 'currency');

        Notification::make()->title('Currency settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
