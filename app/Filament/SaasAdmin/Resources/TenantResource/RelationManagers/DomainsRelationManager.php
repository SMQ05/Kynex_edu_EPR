<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantResource\RelationManagers;

use App\Services\CustomDomainService;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * DomainsRelationManager — Phase 15C.4
 *
 * Manages subdomain + custom domain records for a tenant
 * in the SaaS admin panel.
 */
class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'School Domains';

    // ── Table ───────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('domain_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'subdomain' => 'primary',
                        'custom'    => 'info',
                        default     => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Verified At')
                    ->dateTime('M d, Y H:i')
                    ->placeholder('Not verified')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                Action::make('addCustomDomain')
                    ->label('Add Custom Domain')
                    ->icon('heroicon-o-globe-alt')
                    ->color('info')
                    ->form([
                        Components\TextInput::make('custom_domain')
                            ->label('Custom Domain')
                            ->placeholder('portal.yourschool.com')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                'regex:/^([a-z0-9]([a-z0-9\-]*[a-z0-9])?\.)+[a-z]{2,}$/i',
                            ])
                            ->helperText('Enter the full domain (e.g. portal.brightfuture.com). Do not include http:// or paths.'),
                    ])
                    ->action(function (array $data) {
                        $service = app(CustomDomainService::class);
                        $tenant  = $this->ownerRecord;

                        try {
                            $domain = $service->initiateVerification($tenant, $data['custom_domain']);
                            $instructions = $service->getVerificationInstructions($domain);

                            Notification::make()
                                ->title('Custom domain added')
                                ->body(
                                    "Add this DNS TXT record to verify:\n\n" .
                                    "**Name:** {$instructions['txt_record_name']}\n" .
                                    "**Value:** {$instructions['txt_record_value']}\n\n" .
                                    "Also add a **CNAME** pointing to **kynexedu.com**.\n\n" .
                                    "Then click *Verify Now* on the domain row."
                                )
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Cannot add domain')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('verifyNow')
                    ->label('Verify Now')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (Domain $record): bool => ! $record->is_verified && $record->domain_type === 'custom')
                    ->requiresConfirmation()
                    ->modalHeading('Verify Domain DNS')
                    ->modalDescription(fn (Domain $record): string =>
                        "This will check DNS TXT records for {$record->domain}.\n" .
                        "Make sure you've added the TXT record before verifying."
                    )
                    ->action(function (Domain $record) {
                        $service  = app(CustomDomainService::class);
                        $verified = $service->verifyDomain($record);

                        if ($verified) {
                            Notification::make()
                                ->title('Domain verified!')
                                ->body("{$record->domain} is now active and serving your school.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Verification failed')
                                ->body(
                                    "TXT record not found for {$record->domain}. " .
                                    "DNS changes can take up to 10 minutes to propagate. Please try again later."
                                )
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('showInstructions')
                    ->label('DNS Instructions')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->visible(fn (Domain $record): bool => ! $record->is_verified && $record->domain_type === 'custom')
                    ->modalHeading(fn (Domain $record): string => "DNS Setup for {$record->domain}")
                    ->modalContent(function (Domain $record) {
                        $service = app(CustomDomainService::class);
                        $info    = $service->getVerificationInstructions($record);

                        return view('filament.saas-admin.domain-instructions', [
                            'info' => $info,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('removeDomain')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (Domain $record): bool => ! $record->is_primary)
                    ->requiresConfirmation()
                    ->modalHeading('Remove Domain')
                    ->modalDescription(fn (Domain $record): string =>
                        "Are you sure you want to remove {$record->domain}? This cannot be undone."
                    )
                    ->action(function (Domain $record) {
                        $service = app(CustomDomainService::class);

                        try {
                            $service->removeDomain($record);

                            Notification::make()
                                ->title('Domain removed')
                                ->body("{$record->domain} has been removed.")
                                ->success()
                                ->send();
                        } catch (\LogicException $e) {
                            Notification::make()
                                ->title('Cannot remove domain')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No domains configured')
            ->emptyStateDescription('This school has no domains. Subdomains are automatically created during provisioning.')
            ->defaultSort('is_primary', 'desc');
    }
}
