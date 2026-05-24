<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\AppearanceSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Style / Theme — admin-panel + login-screen appearance settings (Infix
 * "Style"). Stored in the single-row appearance_settings table. Kept
 * separate from CmsSetting (public website) and Phase 8 generic settings.
 */
class AppearanceSettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_appearance_settings';

    protected static string $rbacWritePermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Style & Theme';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Style & Theme';

    protected string $view = 'filament.school-admin.pages.appearance-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $s = AppearanceSetting::current();

        $this->form->fill([
            'primary_color'          => $s->primary_color,
            'secondary_color'        => $s->secondary_color,
            'sidebar_style'          => $s->sidebar_style,
            'login_background_path'  => $s->login_background_path,
            'login_background_color' => $s->login_background_color,
            'panel_background_path'  => $s->panel_background_path,
            'panel_background_color' => $s->panel_background_color,
            'font_family'            => $s->font_family,
            'dark_mode_default'      => (bool) $s->dark_mode_default,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Colours')
                    ->schema([
                        ColorPicker::make('primary_color')->label('Primary colour')->default('#1a56db'),
                        ColorPicker::make('secondary_color')->label('Secondary colour')->default('#7e3af2'),
                        Toggle::make('dark_mode_default')->label('Default to dark mode'),
                    ])->columns(2),

                Section::make('Sidebar & Typography')
                    ->schema([
                        Select::make('sidebar_style')
                            ->label('Sidebar style')
                            ->options(AppearanceSetting::SIDEBAR_STYLES)
                            ->default('default')
                            ->native(false),
                        TextInput::make('font_family')
                            ->label('Font family')
                            ->placeholder('e.g. Inter, system-ui, sans-serif')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Login Screen')
                    ->schema([
                        FileUpload::make('login_background_path')
                            ->label('Login background image')
                            ->image()
                            ->directory('appearance/login')
                            ->maxSize(4096),
                        ColorPicker::make('login_background_color')->label('Login background colour'),
                    ])->columns(2),

                Section::make('Panel Background')
                    ->schema([
                        FileUpload::make('panel_background_path')
                            ->label('Panel background image')
                            ->image()
                            ->directory('appearance/panel')
                            ->maxSize(4096),
                        ColorPicker::make('panel_background_color')->label('Panel background colour'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        AppearanceSetting::current()->update($this->form->getState());

        Notification::make()->title('Appearance settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
