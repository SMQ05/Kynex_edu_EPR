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

    // Single array property so Livewire's ArraySynth handles FileUpload
    // temporary file transitions (scalar ?string properties cannot hold
    // TemporaryUploadedFile and cause "Property type not supported" errors).
    public array $data = [];

    // ── Lifecycle ───────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $settings = CmsSetting::getSettings();

        $asList = static fn (mixed $v): array => is_array($v) && array_is_list($v) ? $v : [];

        // FileUpload components expect a keyed array in $data (not a plain string).
        // $this->data is set directly, bypassing Filament's form fill() and thus
        // FileUploadStateCast::set(). Without the keyed-array format, Livewire
        // serialises the string path to the snapshot, and getUploadedFiles() later
        // does `foreach(string ...)` which throws a TypeError.
        $wrapFile = static function (mixed $v): ?array {
            if (blank($v)) {
                return null;
            }
            return [(string) \Illuminate\Support\Str::uuid() => (string) $v];
        };

        $this->data = [
            'school_name'          => $settings->school_name ?? '',
            'tagline'              => $settings->tagline ?? '',
            'logo_path'            => $wrapFile($settings->logo_path),
            'favicon_path'         => $wrapFile($settings->favicon_path),
            'primary_color'        => $settings->primary_color ?? '#1a56db',
            'address'              => $settings->address ?? '',
            'phone'                => $settings->phone ?? '',
            'email'                => $settings->email ?? '',
            'whatsapp'             => $settings->whatsapp ?? '',
            'facebook_url'         => $settings->facebook_url ?? '',
            'twitter_url'          => $settings->twitter_url ?? '',
            'instagram_url'        => $settings->instagram_url ?? '',
            'youtube_url'          => $settings->youtube_url ?? '',
            'about_text'           => $settings->about_text ?? '',
            'principal_name'       => $settings->principal_name ?? '',
            'principal_photo_path' => $wrapFile($settings->principal_photo_path),
            'principal_message'    => $settings->principal_message ?? '',
            'admission_open'       => (bool) $settings->admission_open,
            'admission_form_url'   => $settings->admission_form_url ?? '',
            'vision_text'          => $settings->vision_text ?? '',
            'mission_text'         => $settings->mission_text ?? '',
            'hero_image_path'      => $wrapFile($settings->hero_image_path),
            'hero_video_url'       => $settings->hero_video_url ?? '',
            'about_image_path'     => $wrapFile($settings->about_image_path),
            'address_map_iframe'   => $settings->address_map_iframe ?? '',
            'why_choose_us'        => $asList($settings->why_choose_us),
            // Normalise repeater items to exactly the schema keys so Livewire
            // entangle bindings always find the expected property paths.
            'facilities'           => array_map(
                static fn (array $f) => [
                    'name'        => $f['name'] ?? '',
                    'description' => $f['description'] ?? '',
                    'image_path'  => $wrapFile($f['image_path'] ?? null),
                ],
                $asList($settings->facilities)
            ),
            'testimonials'         => array_map(
                static fn (array $t) => [
                    'quote'      => $t['quote'] ?? '',
                    'author'     => $t['author'] ?? '',
                    'role'       => $t['role'] ?? null,
                    'photo_path' => $wrapFile($t['photo_path'] ?? null),
                ],
                $asList($settings->testimonials)
            ),
            'stats'                => $asList($settings->stats),
            'exam_highlights'      => $asList($settings->exam_highlights),
            'admission_steps'      => $asList($settings->admission_steps),
        ];
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
            ->statePath('data');
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
            ->statePath('data');
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
            ->statePath('data');
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
            ->statePath('data');
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
                            ->visible(fn () => $this->data['admission_open'] ?? false),
                    ]),
            ])
            ->statePath('data');
    }

    public function heroForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('🎯 Hero / Top of homepage')
                ->description('The big banner area at the top of your school website.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Components\FileUpload::make('hero_image_path')
                        ->label('Hero Background Image')
                        ->image()
                        ->directory('cms/hero')
                        ->maxSize(4096)
                        ->helperText('Recommended 1920x800. Used when no slider is configured.'),
                    Components\TextInput::make('hero_video_url')
                        ->label('Hero Video URL (YouTube embed)')
                        ->url()
                        ->placeholder('https://www.youtube.com/embed/…')
                        ->maxLength(500)
                        ->helperText('Optional. If set, displayed in the About section.'),
                ]),
        ])->statePath('data');
    }

    public function visionForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('🎯 Vision & Mission')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Components\Textarea::make('vision_text')->label('Vision')->rows(3)->maxLength(1000),
                    Components\Textarea::make('mission_text')->label('Mission')->rows(3)->maxLength(1000),
                    Components\FileUpload::make('about_image_path')
                        ->label('About-page Image')
                        ->image()->directory('cms/about')->maxSize(4096),
                ]),
        ])->statePath('data');
    }

    public function whyUsForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('⭐ Why Choose Us (homepage cards)')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Components\Repeater::make('why_choose_us')
                        ->label('Reasons')
                        ->schema([
                            Components\TextInput::make('icon')
                                ->label('Heroicon name')
                                ->placeholder('e.g. heroicon-o-academic-cap')
                                ->default('heroicon-o-star'),
                            Components\TextInput::make('title')->required()->maxLength(80),
                            Components\Textarea::make('description')->required()->rows(2)->maxLength(300),
                        ])
                        ->columns(3)
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->addActionLabel('Add reason'),
                ]),
        ])->statePath('data');
    }

    public function statsForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('📊 Stats / Achievements')
                ->description('Big numbers displayed on the homepage (students, teachers, programs, awards).')
                ->collapsible()->collapsed()
                ->schema([
                    Components\Repeater::make('stats')
                        ->schema([
                            Components\TextInput::make('value')->label('Value')->required()->placeholder('500+'),
                            Components\TextInput::make('label')->required()->placeholder('Students'),
                        ])
                        ->columns(2)->reorderable()->collapsible()
                        ->itemLabel(fn (array $state): ?string => trim(($state['value'] ?? '') . ' ' . ($state['label'] ?? '')))
                        ->addActionLabel('Add stat'),
                ]),
        ])->statePath('data');
    }

    public function facilitiesForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('🏫 Facilities / Campus features')
                ->collapsible()->collapsed()
                ->schema([
                    Components\Repeater::make('facilities')
                        ->schema([
                            Components\TextInput::make('name')->required()->maxLength(100),
                            Components\Textarea::make('description')->rows(2)->maxLength(300),
                            Components\FileUpload::make('image_path')
                                ->image()->directory('cms/facilities')->maxSize(2048),
                        ])
                        ->columns(2)->reorderable()->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->addActionLabel('Add facility'),
                ]),
        ])->statePath('data');
    }

    public function testimonialsForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('💬 Testimonials')
                ->collapsible()->collapsed()
                ->schema([
                    Components\Repeater::make('testimonials')
                        ->schema([
                            Components\Textarea::make('quote')->required()->rows(3)->maxLength(500),
                            Components\TextInput::make('author')->required()->placeholder('Parent of …'),
                            Components\TextInput::make('role')->placeholder('Parent / Alumnus / Teacher'),
                            Components\FileUpload::make('photo_path')
                                ->image()->directory('cms/testimonials')->maxSize(1024),
                        ])
                        ->columns(2)->reorderable()->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['author'] ?? null)
                        ->addActionLabel('Add testimonial'),
                ]),
        ])->statePath('data');
    }

    public function admissionStepsForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('📋 Admission process steps')
                ->description('Shown on the Admissions page.')
                ->collapsible()->collapsed()
                ->schema([
                    Components\Repeater::make('admission_steps')
                        ->schema([
                            Components\TextInput::make('title')->required()->placeholder('Step 1: Apply Online'),
                            Components\Textarea::make('description')->required()->rows(2)->maxLength(300),
                        ])
                        ->columns(1)->reorderable()->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->addActionLabel('Add step'),
                ]),
        ])->statePath('data');
    }

    public function examHighlightsForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('🏆 Exam result highlights')
                ->collapsible()->collapsed()
                ->schema([
                    Components\Repeater::make('exam_highlights')
                        ->schema([
                            Components\TextInput::make('exam')->required()->placeholder('Matric 2025'),
                            Components\TextInput::make('result')->required()->placeholder('98% pass · 12 A+'),
                        ])
                        ->columns(2)->reorderable()->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['exam'] ?? null)
                        ->addActionLabel('Add highlight'),
                ]),
        ])->statePath('data');
    }

    public function mapForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('🗺 Google Map embed')
                ->description('Paste the Google Maps embed iframe HTML. Shown on the Contact page.')
                ->collapsible()->collapsed()
                ->schema([
                    Components\Textarea::make('address_map_iframe')
                        ->rows(4)
                        ->maxLength(1500)
                        ->placeholder('<iframe src="https://www.google.com/maps/embed?…" …></iframe>'),
                ]),
        ])->statePath('data');
    }

    protected function getForms(): array
    {
        return [
            'generalForm',
            'heroForm',
            'visionForm',
            'whyUsForm',
            'statsForm',
            'facilitiesForm',
            'testimonialsForm',
            'admissionStepsForm',
            'examHighlightsForm',
            'contactForm',
            'mapForm',
            'socialForm',
            'aboutForm',
            'admissionForm',
        ];
    }

    // ── Save actions ─────────────────────────────────────────────

    public function saveGeneral(): void
    {
        CmsSetting::getSettings()->update($this->generalForm->getState());
        $this->notifySaved();
    }

    public function saveContact(): void
    {
        CmsSetting::getSettings()->update($this->contactForm->getState());
        $this->notifySaved();
    }

    public function saveSocial(): void
    {
        CmsSetting::getSettings()->update($this->socialForm->getState());
        $this->notifySaved();
    }

    public function saveAbout(): void
    {
        CmsSetting::getSettings()->update($this->aboutForm->getState());
        $this->notifySaved();
    }

    public function saveAdmission(): void
    {
        CmsSetting::getSettings()->update($this->admissionForm->getState());
        $this->notifySaved();
    }

    public function saveHero(): void           { CmsSetting::getSettings()->update($this->heroForm->getState());           $this->notifySaved(); }
    public function saveVision(): void         { CmsSetting::getSettings()->update($this->visionForm->getState());         $this->notifySaved(); }
    public function saveWhyUs(): void          { CmsSetting::getSettings()->update($this->whyUsForm->getState());          $this->notifySaved(); }
    public function saveStats(): void          { CmsSetting::getSettings()->update($this->statsForm->getState());          $this->notifySaved(); }
    public function saveFacilities(): void     { CmsSetting::getSettings()->update($this->facilitiesForm->getState());     $this->notifySaved(); }
    public function saveTestimonials(): void   { CmsSetting::getSettings()->update($this->testimonialsForm->getState());   $this->notifySaved(); }
    public function saveAdmissionSteps(): void { CmsSetting::getSettings()->update($this->admissionStepsForm->getState()); $this->notifySaved(); }
    public function saveExamHighlights(): void { CmsSetting::getSettings()->update($this->examHighlightsForm->getState()); $this->notifySaved(); }
    public function saveMap(): void            { CmsSetting::getSettings()->update($this->mapForm->getState());            $this->notifySaved(); }

    private function notifySaved(): void
    {
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
                ->url(fn () => $this->resolveSchoolWebsiteUrl())
                ->openUrlInNewTab(),
        ];
    }

    private function resolveSchoolWebsiteUrl(): string
    {
        if (! function_exists('tenant') || ! tenant()) {
            return '/';
        }

        $tenantId = tenant()->id;

        try {
            $domain = \Stancl\Tenancy\Database\Models\Domain::query()
                ->where('tenant_id', $tenantId)
                ->orderByDesc('verified_at')
                ->first();

            if ($domain && $domain->domain) {
                return 'https://' . $domain->domain;
            }
        } catch (\Throwable) {
            // Fall through to the path-based site URL.
        }

        return url('/site/' . $tenantId);
    }
}
