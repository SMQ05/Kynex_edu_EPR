<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\Tenant\CmsSetting;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CmsSettings extends Page implements HasForms
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Website Settings';

    protected static ?string $title = 'Website / CMS Settings';

    protected static string | \UnitEnum | null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.school-admin.pages.cms-settings';

    // ── Form State ──────────────────────────────────────────────
    public string $school_name = '';
    public string $tagline = '';
    public ?string $logo_path = '';
    public ?string $favicon_path = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $whatsapp = '';
    public string $facebook_url = '';
    public string $twitter_url = '';
    public string $instagram_url = '';
    public string $youtube_url = '';
    public string $primary_color = '#1a56db';
    public string $about_text = '';
    public string $principal_message = '';
    public string $principal_name = '';
    public ?string $principal_photo_path = '';
    public bool $admission_open = false;
    public string $admission_form_url = '';

    // ── Lifecycle ───────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $settings = CmsSetting::getSettings();

        $this->school_name          = $settings->school_name ?? '';
        $this->tagline              = $settings->tagline ?? '';
        $this->logo_path            = $settings->logo_path ?? '';
        $this->favicon_path         = $settings->favicon_path ?? '';
        $this->address              = $settings->address ?? '';
        $this->phone                = $settings->phone ?? '';
        $this->email                = $settings->email ?? '';
        $this->whatsapp             = $settings->whatsapp ?? '';
        $this->facebook_url         = $settings->facebook_url ?? '';
        $this->twitter_url          = $settings->twitter_url ?? '';
        $this->instagram_url        = $settings->instagram_url ?? '';
        $this->youtube_url          = $settings->youtube_url ?? '';
        $this->primary_color        = $settings->primary_color ?? '#1a56db';
        $this->about_text           = $settings->about_text ?? '';
        $this->principal_message    = $settings->principal_message ?? '';
        $this->principal_name       = $settings->principal_name ?? '';
        $this->principal_photo_path = $settings->principal_photo_path ?? '';
        $this->admission_open       = (bool) $settings->admission_open;
        $this->admission_form_url   = $settings->admission_form_url ?? '';
    }

    // ── Form Schemas ────────────────────────────────────────────

    public function generalForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('🏫 General Information')
                    ->description('Basic school information displayed on the website.')
                    ->collapsible()
                    ->schema([
                        Components\TextInput::make('school_name')
                            ->label('School Name')
                            ->required()
                            ->maxLength(255),

                        Components\TextInput::make('tagline')
                            ->label('Tagline / Motto')
                            ->maxLength(255)
                            ->helperText('A short tagline displayed below the school name.'),

                        Components\FileUpload::make('logo_path')
                            ->label('School Logo')
                            ->image()
                            ->directory('cms/logos')
                            ->maxSize(2048),

                        Components\FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->directory('cms/favicons')
                            ->maxSize(512),

                        Components\ColorPicker::make('primary_color')
                            ->label('Primary Color')
                            ->helperText('The primary brand color for the website theme.'),
                    ]),
            ])
            ->statePath('');
    }

    public function contactForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('📞 Contact Information')
                    ->description('Contact details displayed on the website.')
                    ->collapsible()
                    ->schema([
                        Components\Textarea::make('address')
                            ->label('School Address')
                            ->rows(2)
                            ->maxLength(500),

                        Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(20),

                        Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255),

                        Components\TextInput::make('whatsapp')
                            ->label('WhatsApp Number')
                            ->maxLength(20)
                            ->helperText('Include country code (e.g., 923001234567). Used for WhatsApp chat button.'),
                    ]),
            ])
            ->statePath('');
    }

    public function socialForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('🌐 Social Media Links')
                    ->description('Social media profile URLs.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->maxLength(500),

                        Components\TextInput::make('twitter_url')
                            ->label('Twitter / X URL')
                            ->url()
                            ->maxLength(500),

                        Components\TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url()
                            ->maxLength(500),

                        Components\TextInput::make('youtube_url')
                            ->label('YouTube URL')
                            ->url()
                            ->maxLength(500),
                    ]),
            ])
            ->statePath('');
    }

    public function aboutForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('📝 About & Principal')
                    ->description('About text and principal\'s message displayed on the website.')
                    ->collapsible()
                    ->schema([
                        Components\RichEditor::make('about_text')
                            ->label('About School')
                            ->helperText('Displayed on the Home and About pages.')
                            ->columnSpanFull(),

                        Components\TextInput::make('principal_name')
                            ->label('Principal Name')
                            ->maxLength(255),

                        Components\FileUpload::make('principal_photo_path')
                            ->label('Principal Photo')
                            ->image()
                            ->directory('cms/principal')
                            ->maxSize(2048),

                        Components\RichEditor::make('principal_message')
                            ->label('Principal\'s Message')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('');
    }

    public function admissionForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('🎓 Admissions')
                    ->description('Control the admission status and link on the website.')
                    ->collapsible()
                    ->schema([
                        Components\Toggle::make('admission_open')
                            ->label('Admissions Open')
                            ->helperText('When enabled, an admission banner appears on the website.')
                            ->live(),

                        Components\TextInput::make('admission_form_url')
                            ->label('Admission Form URL')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Link to online admission form (Google Form, external URL, etc.)')
                            ->visible(fn () => $this->admission_open),
                    ]),
            ])
            ->statePath('');
    }

    /**
     * Register all named forms for Filament v5.
     */
    protected function getForms(): array
    {
        return [
            'generalForm',
            'contactForm',
            'socialForm',
            'aboutForm',
            'admissionForm',
        ];
    }

    // ── Actions ─────────────────────────────────────────────────

    public function saveGeneral(): void
    {
        $this->saveFields([
            'school_name', 'tagline', 'logo_path', 'favicon_path', 'primary_color',
        ]);
    }

    public function saveContact(): void
    {
        $this->saveFields(['address', 'phone', 'email', 'whatsapp']);
    }

    public function saveSocial(): void
    {
        $this->saveFields(['facebook_url', 'twitter_url', 'instagram_url', 'youtube_url']);
    }

    public function saveAbout(): void
    {
        $this->saveFields(['about_text', 'principal_message', 'principal_name', 'principal_photo_path']);
    }

    public function saveAdmission(): void
    {
        $this->saveFields(['admission_open', 'admission_form_url']);
    }

    protected function saveFields(array $fields): void
    {
        $settings = CmsSetting::getSettings();

        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $this->{$field};
        }

        $settings->update($data);

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('visit_website')
                ->label('Visit Website')
                ->icon('heroicon-o-globe-alt')
                ->url('/')
                ->openUrlInNewTab(),
        ];
    }
}
