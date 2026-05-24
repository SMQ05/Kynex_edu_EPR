<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\Event;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Calendar — read-only month + agenda view of published events.
 * Events are managed via EventResource; this page only reads them.
 */
class CalendarPage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_events';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Calendar';

    protected string $view = 'filament.school-admin.pages.calendar';

    /** Anchor month, format Y-m (defaults to the current month). */
    public string $month;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
    }

    public function today(): void
    {
        $this->month = now()->format('Y-m');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prev')->label('‹ Prev')->color('gray')->action('previousMonth'),
            Action::make('today')->label('Today')->color('gray')->action('today'),
            Action::make('next')->label('Next ›')->color('gray')->action('nextMonth'),
            Action::make('manage')
                ->label('Manage Events')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => \App\Filament\SchoolAdmin\Resources\EventResource::getUrl('index')),
        ];
    }

    /** The Carbon for the first day of the displayed month. */
    public function getMonthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
    }

    public function getMonthLabel(): string
    {
        return $this->getMonthStart()->format('F Y');
    }

    /**
     * 6x7 grid of days (each: ['date' => Carbon, 'inMonth' => bool, 'events' => Collection]).
     *
     * @return array<int, array<int, array{date: Carbon, inMonth: bool, events: Collection}>>
     */
    public function getWeeks(): array
    {
        $start    = $this->getMonthStart();
        $end      = $start->copy()->endOfMonth();
        $byDay    = $this->eventsForRange($start->copy()->startOfWeek(Carbon::SUNDAY), $end->copy()->endOfWeek(Carbon::SATURDAY));

        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd   = $end->copy()->endOfWeek(Carbon::SATURDAY);

        $weeks  = [];
        $week   = [];
        foreach (CarbonPeriod::create($gridStart, $gridEnd) as $day) {
            /** @var Carbon $day */
            $key   = $day->format('Y-m-d');
            $week[] = [
                'date'    => $day->copy(),
                'inMonth' => $day->month === $start->month,
                'events'  => $byDay->get($key, collect()),
            ];

            if (count($week) === 7) {
                $weeks[] = $week;
                $week    = [];
            }
        }

        return $weeks;
    }

    /** Upcoming published events (next 60 days) for the agenda list. */
    public function getUpcoming(): Collection
    {
        return Event::query()
            ->published()
            ->whereBetween('start_at', [now()->startOfDay(), now()->addDays(60)->endOfDay()])
            ->orderBy('start_at')
            ->limit(50)
            ->get();
    }

    /**
     * Index published events by day (Y-m-d) for the given range.
     *
     * @return Collection<string, Collection>
     */
    protected function eventsForRange(Carbon $from, Carbon $to): Collection
    {
        return Event::query()
            ->published()
            ->whereBetween('start_at', [$from, $to])
            ->orderBy('start_at')
            ->get()
            ->groupBy(fn (Event $e): string => $e->start_at->format('Y-m-d'));
    }
}
