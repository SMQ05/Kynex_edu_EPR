<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Filament\SchoolAdmin\Support\AiActions;
use App\Support\SchoolSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Header & footer content for the public website, stored via
 * SchoolSettings (group 'cms'). Complements the existing CmsSettings
 * page (which owns logo/colours/social) without overlapping it.
 */
class HeaderFooterSettingsPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 23;

    protected static ?string $navigationLabel = 'Header & Footer';

    protected static ?string $title = 'Header & Footer Content';

    protected string $view = 'filament.school-admin.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'topbar_enabled'   => (bool) SchoolSettings::get('cms.topbar_enabled', false),
            'topbar_text'      => SchoolSettings::get('cms.topbar_text', ''),
            'header_cta_label' => SchoolSettings::get('cms.header_cta_label', ''),
            'header_cta_url'   => SchoolSettings::get('cms.header_cta_url', ''),
            'footer_about'     => SchoolSettings::get('cms.footer_about', ''),
            'footer_copyright' => SchoolSettings::get('cms.footer_copyright', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Top Bar')
                    ->columns(2)
                    ->schema([
                        Toggle::make('topbar_enabled')->label('Show top bar')->columnSpanFull(),
                        TextInput::make('topbar_text')
                            ->label('Top bar text')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Admissions open for 2026 — Apply now!'),
                    ]),

                Section::make('Header Call-to-Action')
                    ->columns(2)
                    ->schema([
                        TextInput::make('header_cta_label')->label('Button label')->maxLength(60),
                        TextInput::make('header_cta_url')->label('Button URL')->url()->maxLength(500),
                    ]),

                Section::make('Footer')
                    ->schema([
                        Textarea::make('footer_about')
                            ->label('Footer about text')
                            ->rows(3)
                            ->maxLength(1000)
                            ->hintActions([
                                AiActions::draftInto('footer_about', [
                                    'instruction' => 'a short, warm footer "about" blurb for a school website',
                                    'feature'     => 'cms_footer_about_draft',
                                ]),
                                AiActions::refineInto('footer_about', ['feature' => 'cms_footer_about_refine']),
                            ]),
                        TextInput::make('footer_copyright')
                            ->label('Copyright line')
                            ->maxLength(255)
                            ->placeholder('© ' . date('Y') . ' Your School. All rights reserved.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        SchoolSettings::setMany([
            'cms.topbar_enabled'   => (bool) ($state['topbar_enabled'] ?? false),
            'cms.topbar_text'      => $state['topbar_text'] ?? '',
            'cms.header_cta_label' => $state['header_cta_label'] ?? '',
            'cms.header_cta_url'   => $state['header_cta_url'] ?? '',
            'cms.footer_about'     => $state['footer_about'] ?? '',
            'cms.footer_copyright' => $state['footer_copyright'] ?? '',
        ], group: 'cms');

        Notification::make()->title('Header & footer content saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save Settings')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }
}
