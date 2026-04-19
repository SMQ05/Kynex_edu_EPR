<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources;

use App\Enums\TenantStatus;
use App\Filament\SaasAdmin\Resources\TenantResource\Pages;
use App\Filament\SaasAdmin\Resources\TenantResource\RelationManagers\DomainsRelationManager;
use App\Filament\SaasAdmin\Resources\TenantResource\Widgets\TenantStatsOverview;
use App\Models\SchoolInvitation;
use App\Models\SchoolUser;
use App\Models\Tenant;
use App\Support\RoleHierarchy;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Laravel\Facades\Resend;
use Spatie\Permission\Models\Role;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Schools';

    protected static ?string $modelLabel = 'School';

    protected static ?string $pluralModelLabel = 'Schools';

    protected static string | \UnitEnum | null $navigationGroup = 'Schools';

    protected static ?int $navigationSort = 1;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            // ── School Information ──────────────────────────────
            Section::make('School Information')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('school_name')
                        ->required()
                        ->maxLength(255),

                    Components\TextInput::make('admin_name')
                        ->required()
                        ->maxLength(255),

                    Components\TextInput::make('admin_email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Components\TextInput::make('admin_whatsapp')
                        ->label('Admin WhatsApp')
                        ->tel()
                        ->maxLength(20),

                    Components\TextInput::make('owner_whatsapp')
                        ->label('Owner WhatsApp')
                        ->tel()
                        ->maxLength(20),

                    Components\TextInput::make('city')
                        ->maxLength(100),

                    Components\TextInput::make('country')
                        ->maxLength(100)
                        ->default('Pakistan'),
                ]),

            // ── Subscription ────────────────────────────────────
            Section::make('Subscription')
                ->columns(2)
                ->schema([
                    Components\Select::make('plan_id')
                        ->label('Subscription Plan')
                        ->relationship('plan', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Components\Select::make('status')
                        ->options(TenantStatus::class)
                        ->required()
                        ->native(false)
                        ->default(TenantStatus::Trial),

                    Components\DateTimePicker::make('trial_ends_at')
                        ->label('Trial Ends At')
                        ->visible(fn (Get $get): bool => $get('status') === TenantStatus::Trial->value || $get('status') === TenantStatus::Trial),

                    Components\DatePicker::make('current_period_start')
                        ->label('Current Period Start'),

                    Components\DatePicker::make('current_period_end')
                        ->label('Current Period End'),

                    Components\Textarea::make('internal_notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            // ── Communication Channels ──────────────────────────
            Section::make('Communication Channels')
                ->collapsible()
                ->collapsed()
                ->schema([

                    // ── WhatsApp ─────────────────────────────────
                    Section::make('WhatsApp')
                        ->columns(2)
                        ->schema([
                            Components\Select::make('whatsapp_channel')
                                ->label('WhatsApp Provider')
                                ->options([
                                    'none'            => '— Disabled —',
                                    'evolution'       => 'Evolution API (Free, Self-Hosted)',
                                    'sendpk_whatsapp' => 'SendPK WhatsApp (wa.sendpk.com) — Pakistan',
                                    'meta_official'   => 'Meta Official Cloud API',
                                    'twilio_whatsapp' => 'Twilio WhatsApp',
                                ])
                                ->default('none')
                                ->native(false)
                                ->live()
                                ->columnSpanFull(),

                            // Evolution API
                            Components\TextInput::make('whatsapp_config.base_url')
                                ->label('Evolution: Server URL')
                                ->placeholder('https://evo.kynexedu.com')
                                ->url()
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'evolution'),

                            Components\TextInput::make('whatsapp_config.api_key')
                                ->label('Evolution: API Key')
                                ->password()
                                ->revealable()
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'evolution'),

                            Components\TextInput::make('whatsapp_config.instance_name')
                                ->label('Evolution: Instance Name')
                                ->placeholder('school-id')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'evolution'),

                            // SendPK WhatsApp
                            Components\TextInput::make('whatsapp_config.api_key')
                                ->label('SendPK: API Key')
                                ->password()
                                ->revealable()
                                ->helperText('From wa.sendpk.com dashboard')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'sendpk_whatsapp'),

                            Components\TextInput::make('whatsapp_config.whatsapp_id')
                                ->label('SendPK: WhatsApp ID (Optional)')
                                ->placeholder('923001234567')
                                ->helperText('Leave blank to use default sender')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'sendpk_whatsapp'),

                            // Meta Official
                            Components\TextInput::make('whatsapp_config.phone_number_id')
                                ->label('Meta: Phone Number ID')
                                ->placeholder('123456789012345')
                                ->helperText('Found in Meta Developer Console → WhatsApp → API Setup')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'meta_official'),

                            Components\TextInput::make('whatsapp_config.access_token')
                                ->label('Meta: Permanent Access Token')
                                ->password()
                                ->revealable()
                                ->helperText('System user token from Meta Business Manager → System Users')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'meta_official'),

                            Components\TextInput::make('whatsapp_config.waba_id')
                                ->label('Meta: WABA ID')
                                ->placeholder('123456789012345')
                                ->helperText('WhatsApp Business Account ID from Meta Developer Console')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'meta_official'),

                            Components\TextInput::make('whatsapp_config.verify_token')
                                ->label('Meta: Webhook Verify Token')
                                ->placeholder('any-secret-string-you-choose')
                                ->helperText('Enter any secret string here AND in Meta Developer Console → WhatsApp → Configuration → Webhook → Verify Token. Must match exactly.')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'meta_official'),

                            // Twilio WhatsApp
                            Components\TextInput::make('whatsapp_config.account_sid')
                                ->label('Twilio: Account SID')
                                ->placeholder('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'twilio_whatsapp'),

                            Components\TextInput::make('whatsapp_config.auth_token')
                                ->label('Twilio: Auth Token')
                                ->password()
                                ->revealable()
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'twilio_whatsapp'),

                            Components\TextInput::make('whatsapp_config.from_number')
                                ->label('Twilio: WhatsApp Number')
                                ->placeholder('whatsapp:+14155238886')
                                ->visible(fn (Get $get): bool => $get('whatsapp_channel') === 'twilio_whatsapp'),
                        ]),

                    // ── SMS ───────────────────────────────────────
                    Section::make('SMS')
                        ->columns(2)
                        ->schema([
                            Components\Select::make('sms_channel')
                                ->label('SMS Provider')
                                ->options([
                                    'none'                => '— Disabled —',
                                    'android_sms_gateway' => 'Android SMS Gateway (Free)',
                                    'sendpk'              => 'SendPK SMS (sendpk.com) — Pakistan',
                                    'jazz_sms'            => 'Jazz SMS',
                                    'telenor_sms'         => 'Telenor SMS',
                                    'twilio_sms'          => 'Twilio SMS',
                                ])
                                ->default('none')
                                ->native(false)
                                ->live()
                                ->columnSpanFull(),

                            // Android SMS Gateway
                            Components\Select::make('sms_config.mode')
                                ->label('Android: Mode')
                                ->options([
                                    'cloud'   => 'Cloud (api.sms-gate.app)',
                                    'private' => 'Private (local/VPN)',
                                ])
                                ->native(false)
                                ->live()
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'android_sms_gateway'),

                            Components\TextInput::make('sms_config.server_url')
                                ->label('Android: Private Server URL')
                                ->placeholder('http://192.168.1.x:8080')
                                ->url()
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'android_sms_gateway' && $get('sms_config.mode') === 'private'),

                            Components\TextInput::make('sms_config.login')
                                ->label('Android: Login')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'android_sms_gateway'),

                            Components\TextInput::make('sms_config.password')
                                ->label('Android: Password')
                                ->password()
                                ->revealable()
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'android_sms_gateway'),

                            // SendPK SMS
                            Components\TextInput::make('sms_config.username')
                                ->label('SendPK: Username')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'sendpk'),

                            Components\TextInput::make('sms_config.password')
                                ->label('SendPK: Password')
                                ->password()
                                ->revealable()
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'sendpk'),

                            Components\TextInput::make('sms_config.sender')
                                ->label('SendPK: Sender ID')
                                ->placeholder('YourSchool')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'sendpk'),

                            // Jazz SMS
                            Components\TextInput::make('sms_config.username')
                                ->label('Jazz: Username')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'jazz_sms'),

                            Components\TextInput::make('sms_config.password')
                                ->label('Jazz: Password')
                                ->password()
                                ->revealable()
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'jazz_sms'),

                            Components\TextInput::make('sms_config.mask')
                                ->label('Jazz: Sender Mask')
                                ->placeholder('YourSchool')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'jazz_sms'),

                            // Telenor SMS
                            Components\TextInput::make('sms_config.api_key')
                                ->label('Telenor: API Key')
                                ->password()
                                ->revealable()
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'telenor_sms'),

                            Components\TextInput::make('sms_config.sender_id')
                                ->label('Telenor: Sender ID')
                                ->placeholder('YourSchool')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'telenor_sms'),

                            // Twilio SMS
                            Components\TextInput::make('sms_config.account_sid')
                                ->label('Twilio: Account SID')
                                ->placeholder('ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'twilio_sms'),

                            Components\TextInput::make('sms_config.auth_token')
                                ->label('Twilio: Auth Token')
                                ->password()
                                ->revealable()
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'twilio_sms'),

                            Components\TextInput::make('sms_config.from_number')
                                ->label('Twilio: From Number')
                                ->placeholder('+14155238886')
                                ->visible(fn (Get $get): bool => $get('sms_channel') === 'twilio_sms'),
                        ]),
                ]),

            // ── AI Settings ─────────────────────────────────────
            Section::make('AI Settings')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Components\Toggle::make('ai_enabled')
                        ->label('AI Enabled')
                        ->live()
                        ->default(false)
                        ->columnSpanFull(),

                    Components\TextInput::make('ai_openrouter_api_key')
                        ->label('OpenRouter API Key (per-school override)')
                        ->password()
                        ->revealable()
                        ->placeholder('sk-or-v1-xxxxxxxxxxxx')
                        ->helperText('Optional — leave blank to use the platform-wide key from API Settings. Set this to give the school their own API key and separate billing.')
                        ->visible(fn (Get $get): bool => (bool) $get('ai_enabled')),

                    Components\Select::make('ai_model')
                        ->label('AI Model (via OpenRouter)')
                        ->options([
                            // Budget-friendly
                            'openai/gpt-4o-mini'                  => 'GPT-4o Mini — $0.15/1M tokens',
                            'google/gemini-2.0-flash-001'         => 'Gemini 2.0 Flash — $0.075/1M tokens',
                            'deepseek/deepseek-chat'              => 'DeepSeek V3 — $0.14/1M tokens',
                            // Mid-tier
                            'openai/gpt-4o'                       => 'GPT-4o — $2.50/1M tokens',
                            'anthropic/claude-sonnet-4'           => 'Claude Sonnet 4 — $3.00/1M tokens',
                            'google/gemini-2.5-pro-preview-03-25' => 'Gemini 2.5 Pro — $1.25/1M tokens',
                            // Premium
                            'openai/gpt-4.1'                      => 'GPT-4.1 — $2.00/1M tokens',
                            'anthropic/claude-opus-4'             => 'Claude Opus 4 — $15.00/1M tokens',
                            'meta-llama/llama-4-maverick'         => 'Llama 4 Maverick — Free tier',
                            // Custom
                            'custom'                              => '— Enter custom model ID —',
                        ])
                        ->default('openai/gpt-4o-mini')
                        ->native(false)
                        ->live()
                        ->helperText('All models via OpenRouter.ai. Browse all models at openrouter.ai/models')
                        ->visible(fn (Get $get): bool => (bool) $get('ai_enabled')),

                    Components\TextInput::make('ai_model_custom')
                        ->label('Custom Model ID')
                        ->placeholder('e.g. mistralai/mistral-7b-instruct or qwen/qwen-2.5-72b-instruct')
                        ->helperText('Enter the exact model ID from openrouter.ai/models — format: provider/model-name')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            $known = ['openai/gpt-4o-mini','google/gemini-2.0-flash-001','deepseek/deepseek-chat',
                                      'openai/gpt-4o','anthropic/claude-sonnet-4','google/gemini-2.5-pro-preview-03-25',
                                      'openai/gpt-4.1','anthropic/claude-opus-4','meta-llama/llama-4-maverick'];
                            if ($record && $record->ai_model && ! in_array($record->ai_model, $known, true)) {
                                $component->state($record->ai_model);
                            }
                        })
                        ->visible(fn (Get $get): bool => (bool) $get('ai_enabled') && $get('ai_model') === 'custom'),

                    Components\TextInput::make('ai_monthly_budget_pkr')
                        ->label('AI Monthly Budget (PKR)')
                        ->numeric()
                        ->prefix('PKR')
                        ->default(0)
                        ->helperText('Monthly spending cap for this school\'s AI usage. 0 = unlimited.')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Components\TextInput $component, $state, $record) {
                            if ($record) {
                                $component->state(number_format($record->ai_monthly_budget_paisas / 100, 2, '.', ''));
                            }
                        })
                        ->visible(fn (Get $get): bool => (bool) $get('ai_enabled')),
                ]),
        ]);
    }

    // ── Table ───────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('school_name')
                    ->label('School')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('admin_email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('primary')
                    ->default('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (TenantStatus $state): string => match ($state) {
                        TenantStatus::Trial     => 'warning',
                        TenantStatus::Active    => 'success',
                        TenantStatus::Suspended => 'danger',
                        TenantStatus::Expired   => 'gray',
                    }),

                Tables\Columns\TextColumn::make('active_student_count')
                    ->label('Students')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('whatsapp_channel')
                    ->label('WhatsApp')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'none' ? 'gray' : 'success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->date('M d, Y')
                    ->sortable()
                    ->visible(fn ($livewire): bool => true)
                    ->description(fn (Tenant $record): ?string => $record->status === TenantStatus::Trial && $record->trial_ends_at?->isPast()
                        ? 'Expired!'
                        : null)
                    ->color(fn (Tenant $record): ?string => $record->status === TenantStatus::Trial && $record->trial_ends_at?->isPast()
                        ? 'danger'
                        : null)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(TenantStatus::class),

                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->relationship('plan', 'name'),

                Tables\Filters\Filter::make('trials_expiring_soon')
                    ->label('Trials Expiring Soon')
                    ->query(fn ($query) => $query
                        ->where('status', TenantStatus::Trial)
                        ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])),

                Tables\Filters\Filter::make('created_this_month')
                    ->label('Created This Month')
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)),
            ])
            ->actions([
                Actions\EditAction::make(),

                Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->status !== TenantStatus::Suspended)
                    ->form([
                        Components\Textarea::make('suspended_reason')
                            ->label('Reason for Suspension')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        $record->update([
                            'status'           => TenantStatus::Suspended,
                            'suspended_reason'  => $data['suspended_reason'],
                        ]);
                    }),

                Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tenant $record): bool => $record->status !== TenantStatus::Active)
                    ->action(function (Tenant $record) {
                        $record->update([
                            'status'           => TenantStatus::Active,
                            'suspended_reason'  => null,
                        ]);
                    }),

                Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-finger-print')
                    ->color('warning')
                    ->tooltip('Login as school admin (coming soon)')
                    ->disabled(),

                // ── Manage Owner Accounts ────────────────────────────────
                // SaaS-only: assign/revoke INSTITUTE_HEAD and MULTI_INSTITUTE_HEAD.
                // These roles cannot be touched by any school-level user.
                Actions\Action::make('manageOwnerAccounts')
                    ->label('Owner Accounts')
                    ->icon('heroicon-o-building-office-2')
                    ->color('primary')
                    ->tooltip('Assign or revoke INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD accounts for this school')
                    ->modalHeading(fn (Tenant $record) => "Owner Accounts — {$record->school_name}")
                    ->modalDescription('Only KynexEdu platform staff can assign or revoke these roles. Changes take effect immediately.')
                    ->form(function (Tenant $record): array {
                        // Switch to the tenant DB to load school users
                        tenancy()->initialize($record);

                        $users = SchoolUser::orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (SchoolUser $u) => [
                                $u->id => "{$u->name} ({$u->email})",
                            ])
                            ->toArray();

                        tenancy()->end();

                        return [
                            Components\Select::make('action')
                                ->label('Action')
                                ->options([
                                    'assign' => 'Assign Role',
                                    'revoke' => 'Revoke Role',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),

                            Components\Select::make('role_name')
                                ->label('Role')
                                ->options([
                                    'INSTITUTE_HEAD'       => 'INSTITUTE_HEAD — School Principal / Head',
                                    'MULTI_INSTITUTE_HEAD' => 'MULTI_INSTITUTE_HEAD — Owner of Multiple Institutes',
                                ])
                                ->required()
                                ->native(false),

                            Components\Select::make('school_user_id')
                                ->label('User')
                                ->options($users)
                                ->searchable()
                                ->required(),
                        ];
                    })
                    ->action(function (Tenant $record, array $data) {
                        tenancy()->initialize($record);

                        $user     = SchoolUser::findOrFail($data['school_user_id']);
                        $roleName = $data['role_name'];
                        $action   = $data['action'];

                        if ($action === 'assign') {
                            if ($user->hasRole($roleName)) {
                                tenancy()->end();
                                Notification::make()
                                    ->title('Already assigned')
                                    ->body("{$user->name} already has the {$roleName} role.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $user->assignRole($roleName);
                            if (! $user->active_role) {
                                $user->update(['active_role' => $roleName]);
                            }

                            tenancy()->end();

                            Notification::make()
                                ->title('Role Assigned')
                                ->body("{$roleName} has been assigned to {$user->name} at {$record->school_name}.")
                                ->success()
                                ->send();
                        } else {
                            if (! $user->hasRole($roleName)) {
                                tenancy()->end();
                                Notification::make()
                                    ->title('Role not found')
                                    ->body("{$user->name} does not have the {$roleName} role.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $user->removeRole($roleName);
                            if ($user->active_role === $roleName) {
                                $next = $user->roles->first()?->name ?? 'STUDENT';
                                $user->update(['active_role' => $next]);
                            }

                            tenancy()->end();

                            Notification::make()
                                ->title('Role Revoked')
                                ->body("{$roleName} has been removed from {$user->name} at {$record->school_name}.")
                                ->success()
                                ->send();
                        }
                    }),

                Actions\Action::make('sendSetPasswordLink')
                    ->label('Send Set-Password Link')
                    ->icon('heroicon-o-key')
                    ->color('info')
                    ->tooltip('Generate a new set-password invitation link and email it to the school admin')
                    ->requiresConfirmation()
                    ->modalHeading('Send Set-Password Link')
                    ->modalDescription(fn (Tenant $record) => "This will send a new set-password link (valid 3 hours) to {$record->admin_email}. Any previous unconsumed links for this email will be replaced.")
                    ->action(function (Tenant $record) {
                        // Expire old invite tokens for this email/tenant
                        SchoolInvitation::where('email', $record->admin_email)
                            ->where('type', 'admin_invite')
                            ->whereNull('consumed_at')
                            ->delete();

                        $token = Str::random(64);
                        SchoolInvitation::create([
                            'school_name'        => $record->school_name,
                            'contact_name'       => $record->admin_name,
                            'email'              => $record->admin_email,
                            'type'               => 'admin_invite',
                            'token'              => $token,
                            'expires_at'         => Carbon::now()->addHours(3),
                            'email_verified_at'  => now(),
                            'tenant_id'          => $record->id,
                        ]);

                        $setPasswordUrl = route('school.set-password', ['token' => $token]);

                        try {
                            Resend::emails()->send([
                                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                                'to'      => $record->admin_email,
                                'subject' => 'Set your KynexEdu password',
                                'html'    => view('emails.school-admin-invite', [
                                    'tenant'         => $record,
                                    'setPasswordUrl' => $setPasswordUrl,
                                    'expiresAt'      => Carbon::now()->addHours(3)->format('d M Y, h:i A'),
                                ])->render(),
                            ]);

                            Notification::make()
                                ->title('Set-password link sent')
                                ->body("A set-password link was emailed to {$record->admin_email}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('Failed to send set-password link from SaaS admin', [
                                'tenant_id' => $record->id,
                                'error'    => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Email delivery failed')
                                ->body('The link was created but the email could not be sent. Check logs.')
                                ->danger()
                                ->send();
                        }
                    }),

                Actions\Action::make('sendPasswordResetLink')
                    ->label('Send Password Reset')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->tooltip('Send a password reset link to the school admin email')
                    ->requiresConfirmation()
                    ->modalHeading('Send Password Reset Link')
                    ->modalDescription(fn (Tenant $record) => "This will send a password reset link (valid 3 hours) to {$record->admin_email}.")
                    ->action(function (Tenant $record) {
                        // Remove old reset tokens for this email
                        SchoolInvitation::where('email', $record->admin_email)
                            ->where('type', 'password_reset')
                            ->whereNull('consumed_at')
                            ->delete();

                        $token = Str::random(64);
                        SchoolInvitation::create([
                            'school_name'        => $record->school_name,
                            'contact_name'       => $record->admin_name,
                            'email'              => $record->admin_email,
                            'type'               => 'password_reset',
                            'token'              => $token,
                            'expires_at'         => Carbon::now()->addHours(3),
                            'email_verified_at'  => now(),
                            'tenant_id'          => $record->id,
                        ]);

                        $resetUrl = route('school.reset-password', ['token' => $token]);

                        try {
                            Resend::emails()->send([
                                'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                                'to'      => $record->admin_email,
                                'subject' => 'Reset your KynexEdu password',
                                'html'    => view('emails.school-reset-password', [
                                    'name'      => $record->admin_name,
                                    'resetUrl'  => $resetUrl,
                                    'expiresAt' => Carbon::now()->addHours(3)->format('d M Y, h:i A'),
                                ])->render(),
                            ]);

                            Notification::make()
                                ->title('Password reset link sent')
                                ->body("A reset link was emailed to {$record->admin_email}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('Failed to send password reset from SaaS admin', [
                                'tenant_id' => $record->id,
                                'error'    => $e->getMessage(),
                            ]);

                            Notification::make()
                                ->title('Email delivery failed')
                                ->body('The link was created but the email could not be sent. Check logs.')
                                ->danger()
                                ->send();
                        }
                    }),

                Actions\Action::make('viewInvoices')
                    ->label('Invoices')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (Tenant $record): string => InvoiceResource::getUrl('index', [
                        'tableFilters[tenant][value]' => $record->id,
                    ])),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Widgets ─────────────────────────────────────────────────

    public static function getWidgets(): array
    {
        return [
            TenantStatsOverview::class,
        ];
    }

    // ── Relations ────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            DomainsRelationManager::class,
        ];
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit'   => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    // ── Mutators (PKR → Paisas) ─────────────────────────────────

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return static::convertPkrToPaisas($data);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        return static::convertPkrToPaisas($data);
    }

    protected static function convertPkrToPaisas(array $data): array
    {
        // Convert PKR budget to paisas
        if (isset($data['ai_monthly_budget_pkr'])) {
            $data['ai_monthly_budget_paisas'] = (int) round((float) $data['ai_monthly_budget_pkr'] * 100);
            unset($data['ai_monthly_budget_pkr']);
        }

        // Resolve custom model ID
        if (($data['ai_model'] ?? '') === 'custom') {
            $customId = trim($data['ai_model_custom'] ?? '');
            $data['ai_model'] = $customId !== '' ? $customId : 'openai/gpt-4o-mini';
        }
        unset($data['ai_model_custom']);

        return $data;
    }
}
