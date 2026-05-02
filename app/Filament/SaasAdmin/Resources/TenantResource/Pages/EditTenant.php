<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantResource\Pages;

use App\Filament\SaasAdmin\Resources\TenantResource;
use App\Models\SchoolInvitation;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Resend\Laravel\Facades\Resend;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return TenantResource::mutateFormDataBeforeSave($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Send set-password invite ──────────────────────────
            Actions\Action::make('sendSetPasswordLink')
                ->label('Send Set-Password Link')
                ->icon('heroicon-o-key')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Send Set-Password Link')
                ->modalDescription(fn (): string => "This will email a set-password link (valid 3 hours) to {$this->record->admin_email}.")
                ->action(function () {
                    /** @var Tenant $record */
                    $record = $this->record;

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
                            ->body("Emailed to {$record->admin_email}.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::error('Failed to send set-password link from edit page', [
                            'tenant_id' => $record->id,
                            'error'    => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Email delivery failed')
                            ->body('Link created but email could not be sent. Check logs.')
                            ->danger()
                            ->send();
                    }
                }),

            // ── Send password reset link ──────────────────────────
            Actions\Action::make('sendPasswordResetLink')
                ->label('Send Password Reset')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Send Password Reset Link')
                ->modalDescription(fn (): string => "This will email a password reset link (valid 3 hours) to {$this->record->admin_email}.")
                ->action(function () {
                    /** @var Tenant $record */
                    $record = $this->record;

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
                            ->body("Reset link emailed to {$record->admin_email}.")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Log::error('Failed to send password reset from edit page', [
                            'tenant_id' => $record->id,
                            'error'    => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Email delivery failed')
                            ->body('Link created but email could not be sent. Check logs.')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

