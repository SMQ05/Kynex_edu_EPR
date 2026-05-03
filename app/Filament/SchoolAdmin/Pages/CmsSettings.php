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

    // Extended fields
    public string $vision_text = '';
    public string $mission_text = '';
    public ?string $hero_image_path = '';
    public string $hero_video_url = '';
    public ?string $about_image_path = '';
    public string $address_map_iframe = '';
    /** @var array<int,array<string,mixed>> */ public array $why_choose_us = [];
    /** @var array<int,array<string,mixed>> */ public array $facilities    = [];
    /** @var array<int,array<string,mixed>> */ public array $testimonials  = [];
    /** @var array<int,array<string,mixed>> */ public array $stats         = [];
    /** @var array<int,array<string,mixed>> */ public array $exam_highlights = [];
    /** @var array<int,array<string,mixed>> */ public array $admission_steps = [];

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

        // Extended
        $this->vision_text          = $settings->vision_text ?? '';
        $this->mission_text         = $settings->mission_text ?? '';
        $this->hero_image_path      = $settings->hero_image_path ?? '';
        $this->hero_video_url       = $settings->hero_video_url ?? '';
        $this->about_image_path     = $settings->about_image_path ?? '';
        $this->address_map_iframe   = $settings->address_map_iframe ?? '';
        $this->why_choose_us        = is_array($settings->why_choose_us) ? $settings->why_choose_us : [];
        $this->facilities           = is_array($settings->facilities) ? $settings->facilities : [];
        $this->testimonials         = is_array($settings->testimonials) ? $settings->testimonials : [];
        $this->stats                = is_array($settings->stats) ? $settings->stats : [];
        $this->exam_highlights      = is_array($settings->exam_highlights) ? $settings->exam_highlights : [];
        $this->admission_steps      = is_array($settings->admission_steps) ? $settings->admission_steps : [];
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
        ])->statePath('');
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
        ])->statePath('');
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
        ])->statePath('');
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
                        ->itemLabel(fn (array $s): ?string => trim(($s['value'] ?? '') . ' ' . ($s['label'] ?? '')))
                        ->addActionLabel('Add stat'),
                ]),
        ])->statePath('');
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
                        ->itemLabel(fn (array $s): ?string => $s['name'] ?? null)
                        ->addActionLabel('Add facility'),
                ]),
        ])->statePath('');
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
                        ->itemLabel(fn (array $s): ?string => $s['author'] ?? null)
                        ->addActionLabel('Add testimonial'),
                ]),
        ])->statePath('');
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
                        ->itemLabel(fn (array $s): ?string => $s['title'] ?? null)
                        ->addActionLabel('Add step'),
                ]),
        ])->statePath('');
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
                        ->itemLabel(fn (array $s): ?string => $s['exam'] ?? null)
                        ->addActionLabel('Add highlight'),
                ]),
        ])->statePath('');
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
        ])->statePath('');
    }

    /**
     * Register all named forms for Filament v5.
     */
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

    public function saveHero(): void           { $this->saveFields(['hero_image_path', 'hero_video_url']); }
    public function saveVision(): void         { $this->saveFields(['vision_text', 'mission_text', 'about_image_path']); }
    public function saveWhyUs(): void          { $this->saveFields(['why_choose_us']); }
    public function saveStats(): void          { $this->saveFields(['stats']); }
    public function saveFacilities(): void     { $this->saveFields(['facilities']); }
    public function saveTestimonials(): void   { $this->saveFields(['testimonials']); }
    public function saveAdmissionSteps(): void { $this->saveFields(['admission_steps']); }
    public function saveExamHighlights(): void { $this->saveFields(['exam_highlights']); }
    public function saveMap(): void            { $this->saveFields(['address_map_iframe']); }

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
                ->url(fn () => $this->resolveSchoolWebsiteUrl())
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Resolve the public-facing URL for this school's website.
     *
     * Priority:
     *   1. A verified custom domain (e.g. "school.example.edu") if the
     *      school has one on the central `domains` table.
     *   2. The tenant's subdomain on the central host (currently
     *      "{slug}.kynexedu.com" / equivalent).
     *   3. Fallback: /site/{tenantId} on the active host (the route
     *      group in routes/web.php that initialises tenancy from a
     *      ?tenant=… query string or the path parameter).
     */
    private function resolveSchoolWebsiteUrl(): string
    {
        if (! function_exists('tenant') || ! tenant()) {
            return '/';
        }

        $tenantId = tenant()->id;

        // 1. Custom domain or verified subdomain on the central domains table.
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

        // 2. Path-based fallback: works on the same host you're on now.
        return url('/site/' . $tenantId);
    }
}
