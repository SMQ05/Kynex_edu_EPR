<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources;

use App\Filament\SaasAdmin\Resources\AuditRunResource\Pages;
use App\Models\AuditRun;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditRunResource extends Resource
{
    protected static ?string $model = AuditRun::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Audit Runs';

    protected static ?string $modelLabel = 'Audit Run';

    protected static ?string $pluralModelLabel = 'Audit Runs';

    protected static string|\UnitEnum|null $navigationGroup = 'Audit';

    protected static ?int $navigationSort = 1;

    // Read-only resource — runs are created only by the audit:run command.
    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Run ID')
                    ->formatStateUsing(fn ($state) => substr($state, 0, 8) . '…')
                    ->copyable()
                    ->tooltip(fn ($state) => $state),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'complete',
                        'warning' => ['running', 'incomplete'],
                        'danger'  => ['timeout', 'error'],
                    ]),

                Tables\Columns\TextColumn::make('scope')
                    ->badge(),

                Tables\Columns\TextColumn::make('tenant_slug')
                    ->label('Tenant')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('findings_critical')
                    ->label('Crit')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'danger' : null),

                Tables\Columns\TextColumn::make('findings_high')
                    ->label('High')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'warning' : null),

                Tables\Columns\TextColumn::make('findings_medium')
                    ->label('Med')
                    ->numeric(),

                Tables\Columns\TextColumn::make('findings_low')
                    ->label('Low')
                    ->numeric(),

                Tables\Columns\TextColumn::make('total_pages')
                    ->label('Pages')
                    ->numeric(),

                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime('M j, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Duration')
                    ->formatStateUsing(function ($record) {
                        if (! $record->finished_at) return '—';
                        $secs = abs($record->finished_at->diffInSeconds($record->started_at));
                        return $secs < 60
                            ? "{$secs}s"
                            : floor($secs / 60) . 'm ' . ($secs % 60) . 's';
                    }),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'running'    => 'Running',
                        'complete'   => 'Complete',
                        'timeout'    => 'Timeout',
                        'incomplete' => 'Incomplete',
                        'error'      => 'Error',
                    ]),

                Tables\Filters\SelectFilter::make('scope')
                    ->options([
                        'all'    => 'All',
                        'tenant' => 'Tenant',
                        'role'   => 'Role',
                        'url'    => 'URL',
                    ]),

                Tables\Filters\TernaryFilter::make('saas_skipped')
                    ->label('SaaS skipped'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditRuns::route('/'),
            'view'  => Pages\ViewAuditRun::route('/{record}'),
        ];
    }
}
