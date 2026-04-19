<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Models\Tenant\PiiAccessLog;
use Filament\Resources\Resource;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * PiiAccessLogResource — Read-only Filament resource for viewing PII audit logs.
 *
 * No create/edit/delete actions. This is a compliance tool.
 */
class PiiAccessLogResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_audit_logs';

    protected static ?string $model = PiiAccessLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'PII Audit Log';

    protected static ?string $modelLabel = 'PII Access Log';

    protected static ?string $pluralModelLabel = 'PII Access Logs';

    protected static string|\UnitEnum|null $navigationGroup = 'Compliance';

    protected static ?int $navigationSort = 99;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('accessed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('table_name')
                    ->label('Table')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('record_id')
                    ->label('Record ID')
                    ->searchable()
                    ->copyable()
                    ->size('sm')
                    ->limit(12),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'INSERT' => 'success',
                        'UPDATE' => 'warning',
                        'DELETE' => 'danger',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user_role')
                    ->label('Role')
                    ->badge()
                    ->color('primary')
                    ->default('—'),

                Tables\Columns\TextColumn::make('accessed_at')
                    ->label('When')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (PiiAccessLog $record): string => $record->accessed_at?->format('Y-m-d H:i:s') ?? ''),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('table_name')
                    ->label('Table')
                    ->options([
                        'health_records'      => 'Health Records',
                        'behavior_incidents'  => 'Behavior Incidents',
                        'student_fees'        => 'Student Fees',
                        'exam_marks'          => 'Exam Marks',
                        'students'            => 'Students',
                    ]),

                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'INSERT' => 'Insert',
                        'UPDATE' => 'Update',
                        'DELETE' => 'Delete',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SchoolAdmin\Resources\PiiAccessLogResource\Pages\ListPiiAccessLogs::route('/'),
        ];
    }
}
