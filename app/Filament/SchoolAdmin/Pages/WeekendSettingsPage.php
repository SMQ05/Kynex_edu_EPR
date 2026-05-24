<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Support\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WeekendSettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 63;

    protected static ?string $navigationLabel = 'Weekend';

    protected static ?string $title = 'Weekend Days';

    protected string $view = 'filament.school-admin.pages.settings-form';

    public const DAYS = [
        'monday'    => 'Monday',
        'tuesday'   => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday'  => 'Thursday',
        'friday'    => 'Friday',
        'saturday'  => 'Saturday',
        'sunday'    => 'Sunday',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'weekend_days' => SchoolSettings::get('calendar.weekend_days', ['sunday']),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Weekend')
                    ->description('Days marked as weekend are excluded from attendance and working-day calculations.')
                    ->schema([
                        CheckboxList::make('weekend_days')
                            ->label('Weekend days')
                            ->options(self::DAYS)
                            ->columns(2)
                            ->bulkToggleable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        SchoolSettings::set(
            'calendar.weekend_days',
            array_values($state['weekend_days'] ?? []),
            group: 'calendar',
        );

        Notification::make()->title('Weekend settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
