<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\ExamSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamSettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_exam_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Examinations';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Exam Settings';

    protected static ?string $title = 'Exam Settings';

    protected string $view = 'filament.school-admin.pages.exam-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $format    = (array) ExamSetting::get('format', []);
        $admitCard = (array) ExamSetting::get('admit_card', []);
        $seatPlan  = (array) ExamSetting::get('seat_plan', []);

        $this->form->fill([
            'result_format'        => $format['result_format'] ?? 'marks_grade',
            'position_basis'       => $format['position_basis'] ?? 'total_marks',
            'show_rank'            => $format['show_rank'] ?? true,
            'show_percentage'      => $format['show_percentage'] ?? true,
            'admit_show_dob'       => $admitCard['show_dob'] ?? false,
            'admit_instructions'   => $admitCard['instructions'] ?? 'Bring this admit card and a valid ID to every exam. Arrive 15 minutes early.',
            'seat_default_capacity' => $seatPlan['default_capacity'] ?? 30,
            'seat_seats_per_row'   => $seatPlan['seats_per_row'] ?? 5,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Result Format & Position')
                    ->description('Controls how results and merit positions are presented across exam reports.')
                    ->schema([
                        Select::make('result_format')
                            ->label('Result format')
                            ->options([
                                'marks_only'  => 'Marks only',
                                'grade_only'  => 'Grade only',
                                'marks_grade' => 'Marks + Grade',
                                'gpa'         => 'GPA / Grade points',
                            ])
                            ->default('marks_grade')
                            ->required(),

                        Select::make('position_basis')
                            ->label('Position (rank) basis')
                            ->options([
                                'total_marks' => 'Total marks',
                                'percentage'  => 'Percentage',
                                'gpa'         => 'GPA',
                            ])
                            ->default('total_marks')
                            ->required(),

                        Toggle::make('show_rank')->label('Show rank/position')->default(true),
                        Toggle::make('show_percentage')->label('Show percentage')->default(true),
                    ])->columns(2),

                Section::make('Admit Card')
                    ->schema([
                        Toggle::make('admit_show_dob')->label('Show date of birth on admit card'),
                        Textarea::make('admit_instructions')
                            ->label('Admit card instructions / footer')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make('Seat Plan Defaults')
                    ->schema([
                        TextInput::make('seat_default_capacity')
                            ->label('Default room capacity')
                            ->numeric()->minValue(1)->default(30),
                        TextInput::make('seat_seats_per_row')
                            ->label('Seats per row')
                            ->numeric()->minValue(1)->default(5),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        ExamSetting::put('format', [
            'result_format'   => $state['result_format'] ?? 'marks_grade',
            'position_basis'  => $state['position_basis'] ?? 'total_marks',
            'show_rank'       => (bool) ($state['show_rank'] ?? true),
            'show_percentage' => (bool) ($state['show_percentage'] ?? true),
        ]);

        ExamSetting::put('admit_card', [
            'show_dob'     => (bool) ($state['admit_show_dob'] ?? false),
            'instructions' => $state['admit_instructions'] ?? '',
        ]);

        ExamSetting::put('seat_plan', [
            'default_capacity' => (int) ($state['seat_default_capacity'] ?? 30),
            'seats_per_row'    => (int) ($state['seat_seats_per_row'] ?? 5),
        ]);

        Notification::make()->title('Exam settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }
}
