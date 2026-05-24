<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\UserActivityLog;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiInsights;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * User Log — read-only view of user activity (logins, CRUD, etc.).
 * Entries are written via App\Support\UserActivity::log(). No create/edit.
 */
class UserActivityLogResource extends Resource
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'view_user_logs';

    protected static ?string $model = UserActivityLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'User Log';

    protected static ?string $modelLabel = 'Activity Log';

    protected static ?string $pluralModelLabel = 'User Activity Log';

    protected static ?int $navigationSort = 30;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, H:i')->since()
                    ->tooltip(fn (UserActivityLog $r): string => $r->created_at?->format('Y-m-d H:i:s') ?? '')
                    ->sortable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable()->placeholder('System'),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login'   => 'success',
                        'logout'  => 'gray',
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default   => 'primary',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject_type')->label('Subject')->toggleable()->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),
                TextColumn::make('description')->wrap()->toggleable()->placeholder('—'),
                TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(fn (): array => UserActivityLog::query()
                        ->distinct()->orderBy('action')->pluck('action', 'action')->all()),
                SelectFilter::make('school_user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->actions([
                Action::make('anomalyNote')
                    ->label('AI note')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->visible(fn (): bool => AiAvailability::enabled())
                    ->requiresConfirmation()
                    ->modalHeading('AI anomaly note')
                    ->modalDescription('AI reviews this user\'s recent activity and flags anything unusual. Advisory only.')
                    ->action(fn (UserActivityLog $record) => static::anomalyNote($record)),
            ])
            ->bulkActions([]);
    }

    /** Ask AiInsights to summarise/flag the user's recent activity. */
    protected static function anomalyNote(UserActivityLog $record): void
    {
        try {
            $recent = UserActivityLog::query()
                ->where('school_user_id', $record->school_user_id)
                ->orderByDesc('created_at')
                ->limit(40)
                ->get()
                ->map(fn (UserActivityLog $r): string => sprintf(
                    '%s — %s%s (IP %s)',
                    $r->created_at?->format('Y-m-d H:i') ?? '',
                    $r->action,
                    $r->subject_type ? ' ' . class_basename($r->subject_type) : '',
                    $r->ip ?? '—'
                ))
                ->implode("\n");

            $note = app(AiInsights::class)->summarize(
                "User activity log for {$record->user?->name}:\n{$recent}",
                'Flag any unusual sign-in patterns, off-hours activity, repeated failures, or suspicious sequences. If nothing stands out, say so plainly.',
                'user_log_anomaly',
            );

            Notification::make()->title('AI activity note')->body($note)->info()->persistent()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI note failed')->body($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\SchoolAdmin\Resources\UserActivityLogResource\Pages\ListUserActivityLogs::route('/'),
        ];
    }
}
