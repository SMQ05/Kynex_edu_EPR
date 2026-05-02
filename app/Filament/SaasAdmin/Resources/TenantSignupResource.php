<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources;

use App\Enums\SignupStatus;
use App\Enums\TenantStatus;
use App\Filament\SaasAdmin\Resources\TenantSignupResource\Pages;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSignup;
use Filament\Actions;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TenantSignupResource extends Resource
{
    protected static ?string $model = TenantSignup::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Signup Leads';

    protected static ?string $modelLabel = 'Signup Lead';

    protected static ?string $pluralModelLabel = 'Signup Leads';

    protected static string | \UnitEnum | null $navigationGroup = 'Schools';

    protected static ?int $navigationSort = 2;

    // ── Form ────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make('Contact Information')
                ->columns(2)
                ->schema([
                    Components\TextInput::make('school_name')
                        ->required()
                        ->maxLength(255),

                    Components\TextInput::make('contact_name')
                        ->required()
                        ->maxLength(255),

                    Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Components\TextInput::make('phone')
                        ->tel()
                        ->required()
                        ->maxLength(20),

                    Components\TextInput::make('whatsapp_number')
                        ->label('WhatsApp Number')
                        ->tel()
                        ->maxLength(20),

                    Components\TextInput::make('city')
                        ->maxLength(100),

                    Components\TextInput::make('country')
                        ->maxLength(100)
                        ->default('Pakistan'),

                    Components\Select::make('status')
                        ->options(SignupStatus::class)
                        ->required()
                        
                        ->default(SignupStatus::New),
                ]),

            Section::make('Additional Info')
                ->schema([
                    Components\Textarea::make('message')
                        ->label('Message / Requirements')
                        ->rows(4)
                        ->columnSpanFull(),

                    Components\Textarea::make('internal_notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Timeline')
                ->columns(2)
                ->visibleOn('edit')
                ->schema([
                    Components\DateTimePicker::make('invited_at')
                        ->label('Invited At')
                        ->disabled(),

                    Components\DateTimePicker::make('onboarded_at')
                        ->label('Onboarded At')
                        ->disabled(),
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

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->size('sm'),

                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->label('WhatsApp')
                    ->size('sm')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('city')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (SignupStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->date('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(SignupStatus::class),
            ])
            ->actions([
                Actions\EditAction::make(),

                // ── Send Invite ─────────────────────────────────
                Actions\Action::make('approveAndProvision')
                    ->label('Approve & Provision')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (TenantSignup $record): bool => ! in_array($record->status, [
                        SignupStatus::Onboarded,
                        SignupStatus::Rejected,
                    ]))
                    ->modalHeading('Approve school registration')
                    ->modalDescription(fn (TenantSignup $record) => "Provisioning '{$record->school_name}' will create their tenant database, install default roles + templates, and email login instructions to {$record->email}.")
                    ->form([
                        Components\Select::make('plan_id')
                            ->label('Plan')
                            ->options(SubscriptionPlan::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->default(fn () => SubscriptionPlan::where('is_featured', true)->where('is_active', true)->value('id')
                                ?? SubscriptionPlan::where('is_active', true)->value('id')),
                        Components\Select::make('status')
                            ->label('Initial status')
                            ->options([
                                'trial'  => 'Trial (with trial_days from the plan)',
                                'active' => 'Active (paid)',
                            ])
                            ->required()
                            ->default('trial'),
                    ])
                    ->action(function (TenantSignup $record, array $data) {
                        $plan = SubscriptionPlan::find($data['plan_id']);
                        if (! $plan) {
                            Notification::make()->title('Plan not found')->danger()->send();
                            return;
                        }

                        $tenantId = Str::slug($record->school_name) . '-' . Str::random(6);

                        $trialEnds = $data['status'] === 'trial'
                            ? now()->addDays($plan->trial_days ?? 14)
                            : null;

                        $tenant = Tenant::create([
                            'id'             => $tenantId,
                            'school_name'    => $record->school_name,
                            'admin_name'     => $record->contact_name,
                            'admin_email'    => $record->email,
                            'admin_whatsapp' => $record->whatsapp_number,
                            'plan_id'        => $plan->id,
                            'status'         => $data['status'],
                            'trial_ends_at'  => $trialEnds,
                        ]);

                        // Find the stashed password hash on the consumed
                        // SchoolInvitation (if the applicant set one before
                        // approval). Otherwise the admin will have to use
                        // the row's "Send Set-Password Link" later.
                        $invitation = \App\Models\SchoolInvitation::where('email', $record->email)
                            ->where('type', 'school_signup')
                            ->whereNotNull('consumed_at')
                            ->orderByDesc('id')
                            ->first();

                        $passwordHash = $invitation?->meta['password_hash'] ?? null;

                        try {
                            $provisioner = new \App\Actions\ProvisionNewTenant();
                            if ($passwordHash) {
                                $provisioner->invokeWithHashedPassword($tenant, $passwordHash);
                            } else {
                                $provisioner($tenant);
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Provisioning failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                            $tenant->delete();
                            return;
                        }

                        if ($invitation) {
                            $invitation->update(['tenant_id' => $tenant->id]);
                        }

                        $record->update([
                            'status'       => SignupStatus::Onboarded,
                            'invited_at'   => now(),
                            'onboarded_at' => now(),
                        ]);

                        Notification::make()
                            ->title('School approved & provisioned')
                            ->body("Tenant '{$record->school_name}' is live on plan {$plan->name}. The admin can log in at /login.")
                            ->success()
                            ->send();
                    }),

                // ── Mark as Contacted ───────────────────────────
                Actions\Action::make('markContacted')
                    ->label('Mark Contacted')
                    ->icon('heroicon-o-phone')
                    ->color('warning')
                    ->visible(fn (TenantSignup $record): bool => $record->status === SignupStatus::New)
                    ->requiresConfirmation()
                    ->action(function (TenantSignup $record) {
                        $record->update(['status' => SignupStatus::Contacted]);

                        Notification::make()
                            ->title('Marked as Contacted')
                            ->success()
                            ->send();
                    }),

                // ── Mark as Rejected ────────────────────────────
                Actions\Action::make('markRejected')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TenantSignup $record): bool => ! in_array($record->status, [
                        SignupStatus::Onboarded,
                        SignupStatus::Rejected,
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Reject Signup')
                    ->action(function (TenantSignup $record) {
                        $record->update(['status' => SignupStatus::Rejected]);

                        Notification::make()
                            ->title('Signup Rejected')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ───────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTenantSignups::route('/'),
            'create' => Pages\CreateTenantSignup::route('/create'),
            'edit'   => Pages\EditTenantSignup::route('/{record}/edit'),
        ];
    }
}
