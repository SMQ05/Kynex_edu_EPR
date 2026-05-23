<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\StudentApplicationResource\Pages;

use App\Enums\ApplicationStatus;
use App\Filament\SchoolAdmin\Resources\StudentApplicationResource;
use App\Models\Tenant\StudentApplication;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListStudentApplications extends ListRecords
{
    protected static string $resource = StudentApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // ── Bulk-reject pending applications past a due date ─────────
            // School clicks this once the cycle ends to close out anyone
            // who never completed test / interview / previous-marks.
            Action::make('bulkRejectPending')
                ->label('Reject all pending past due date')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reject all pending applications past a due date')
                ->modalDescription('This will reject every application created on or before the date you choose that is still in a pending state (submitted / scheduled / partially-scored). Already-admitted, already-rejected, or waitlisted applications will not be touched.')
                ->modalSubmitActionLabel('Reject pending applications')
                ->form([
                    DatePicker::make('cutoff_date')
                        ->label('Reject applications applied on or before')
                        ->required()
                        ->native(false)
                        ->default(now()),
                    Textarea::make('reason')
                        ->label('Reason (added to each application’s decision notes)')
                        ->required()
                        ->minLength(5)
                        ->rows(3)
                        ->placeholder('e.g. Application cycle closed; criteria not completed by the due date.'),
                ])
                ->action(function (array $data): void {
                    $cutoff = \Carbon\Carbon::parse($data['cutoff_date'])->endOfDay();
                    $reason = trim((string) ($data['reason'] ?? ''));

                    // Statuses we consider "pending" — anything not already
                    // admitted, rejected, waitlisted, or cancelled.
                    $pendingValues = collect(ApplicationStatus::cases())
                        ->reject(fn ($s) => in_array($s->value, [
                            ApplicationStatus::Admitted->value,
                            ApplicationStatus::Rejected->value,
                            ApplicationStatus::Waitlisted->value,
                        ], true))
                        ->map(fn ($s) => $s->value)
                        ->values()
                        ->all();

                    $rejected = 0;
                    $errors   = 0;

                    DB::transaction(function () use ($cutoff, $reason, $pendingValues, &$rejected, &$errors) {
                        $apps = StudentApplication::query()
                            ->where('created_at', '<=', $cutoff)
                            ->whereIn('status', $pendingValues)
                            ->get();

                        foreach ($apps as $app) {
                            try {
                                $app->forceFill([
                                    'status'             => ApplicationStatus::Rejected,
                                    'auto_rejected'      => true,
                                    'auto_reject_reason' => 'Bulk-rejected past cycle due date',
                                    'auto_decision_status' => ApplicationStatus::Rejected->value,
                                    'auto_decision_at'   => now(),
                                    'reviewed_at'        => now(),
                                    'decision_notes'     => trim(
                                        ($app->decision_notes ? $app->decision_notes . "\n" : '')
                                        . 'Bulk-rejected on ' . now()->format('d M Y, H:i') . ': ' . $reason
                                    ),
                                ])->save();
                                $rejected++;
                            } catch (\Throwable $e) {
                                $errors++;
                                \Illuminate\Support\Facades\Log::warning('bulkRejectPending failed for application', [
                                    'application_id' => $app->id,
                                    'error'          => $e->getMessage(),
                                ]);
                            }
                        }
                    });

                    Notification::make()
                        ->title("{$rejected} application(s) rejected")
                        ->body($errors > 0 ? "{$errors} could not be rejected (see logs)." : 'No errors.')
                        ->success()
                        ->duration(15000)
                        ->send();
                }),
        ];
    }
}
