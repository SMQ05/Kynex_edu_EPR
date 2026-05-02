<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Widgets;

use App\Models\Tenant\HomeworkSubmission;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * HomeworkPendingReviewWidget — Part 4h.
 *
 * Dashboard widget for teachers and school admins showing homework
 * submissions that have been submitted but not yet graded.
 * Sorted by submission date (oldest first) to prioritise review.
 */
class HomeworkPendingReviewWidget extends BaseWidget
{
    protected static ?string $heading = 'Homework Pending Review';

    protected static ?int $sort = 15;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HomeworkSubmission::query()
                    ->with(['homework.subject', 'homework.schoolClass', 'student'])
                    ->whereNull('marks_obtained')
                    ->whereNull('graded_at')
                    ->whereNotNull('submitted_at')
                    ->orderBy('submitted_at')
            )
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                TextColumn::make('homework.title')
                    ->label('Homework')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('homework.subject.name')
                    ->label('Subject')
                    ->sortable(),

                TextColumn::make('homework.schoolClass.name')
                    ->label('Class')
                    ->sortable(),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('homework.due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->homework?->isOverdue() ? 'danger' : 'gray'),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->emptyStateHeading('No submissions pending review')
            ->emptyStateDescription('All submitted homework has been graded.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([10, 25])
            ->striped();
    }

    /**
     * Only show this widget to roles that review homework.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'getActiveRoleName')) {
            $role = $user->getActiveRoleName();
            return in_array($role, ['SCHOOL_ADMIN', 'PRINCIPAL', 'TEACHER']);
        }

        return false;
    }
}
