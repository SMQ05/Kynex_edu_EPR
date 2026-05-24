<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Pages\Page;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only view of the application's scheduled tasks (the Laravel
 * scheduler, driven by the system cron). Shows each task's expression
 * and a human description; last-run timestamps are read from the cache
 * when the scheduler has recorded them.
 */
class CronJobPage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 69;

    protected static ?string $navigationLabel = 'Cron Jobs';

    protected static ?string $title = 'Scheduled Tasks (Cron)';

    protected string $view = 'filament.school-admin.pages.cron-jobs';

    /**
     * @return array<int,array{name:string,expression:string,description:string,last_run:?string}>
     */
    public function tasks(): array
    {
        try {
            /** @var Schedule $schedule */
            $schedule = app(Schedule::class);
            $events = $schedule->events();
        } catch (\Throwable) {
            return [];
        }

        $rows = [];
        foreach ($events as $event) {
            $name = $event->description ?: $this->summarise($event->command ?? '');
            $rows[] = [
                'name'        => $name ?: 'Closure task',
                'expression'  => (string) ($event->expression ?? '* * * * *'),
                'description' => (string) ($event->command ?? 'Closure'),
                'last_run'    => $this->lastRun($name),
            ];
        }

        return $rows;
    }

    private function summarise(string $command): string
    {
        $command = trim(str_replace(["'", '"'], '', $command));
        $command = preg_replace('/^.*artisan\s*/', '', $command) ?? $command;

        return $command !== '' ? $command : 'Closure task';
    }

    private function lastRun(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        $ts = Cache::get('schedule:last_run:' . md5($name));

        return $ts ? (string) $ts : null;
    }
}
