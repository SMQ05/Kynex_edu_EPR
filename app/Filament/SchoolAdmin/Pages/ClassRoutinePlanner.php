<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Enums\DayOfWeek;
use App\Models\Tenant\ClassRoutine;
use App\Models\Tenant\Section;
use App\Models\Tenant\Subject;
use App\Models\SchoolUser;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

class ClassRoutinePlanner extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_timetable';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Routine Planner';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.school-admin.pages.class-routine-planner';

    // ── Step 1: class/year picker ────────────────────────────────────

    public ?string $academic_year_id = null;
    public ?string $class_id = null;
    public ?string $section_id = null;

    // ── Step 2: generator inputs ─────────────────────────────────────

    public string $gen_start_time   = '08:00';
    public int    $gen_period_count = 6;
    public int    $gen_duration_min = 45;   // minutes per regular period

    // ── Step 3: slot list + grid ─────────────────────────────────────

    public bool $isLoaded = false;

    /**
     * Ordered list of slots. Each item:
     *   [
     *     'type'        => 'period' | 'break',
     *     'label'       => string,          // "Period 1" or break label
     *     'duration'    => int,             // minutes
     *     'start_time'  => 'HH:MM',        // auto-calculated
     *     'end_time'    => 'HH:MM',        // auto-calculated
     *   ]
     */
    public array $periodSlots = [];

    /**
     * Grid cells keyed by "{day}.{slotIndex}" (0-based slot index).
     *   ['subject_id' => '', 'teacher_id' => '', 'room_number' => '']
     */
    public array $grid = [];

    // ── Modal state ──────────────────────────────────────────────────

    public bool    $showModal      = false;
    public ?string $modalDay       = null;
    public ?int    $modalSlotIndex = null;
    public string  $modalSubjectId  = '';
    public string  $modalTeacherId  = '';
    public string  $modalRoomNumber = '';

    // ── Computed ─────────────────────────────────────────────────────

    #[Computed]
    public function sections(): Collection
    {
        if (! $this->class_id) {
            return collect();
        }
        return Section::where('class_id', $this->class_id)->orderBy('name')->pluck('name', 'id');
    }

    #[Computed]
    public function subjectOptions(): array
    {
        return Subject::orderBy('name')->pluck('name', 'id')->all();
    }

    #[Computed]
    public function teacherOptions(): array
    {
        return SchoolUser::orderBy('name')->pluck('name', 'id')->all();
    }

    #[Computed]
    public function days(): array
    {
        return DayOfWeek::cases();
    }

    // ── Livewire watchers ────────────────────────────────────────────

    public function updatedClassId(): void
    {
        $this->section_id = null;
        $this->isLoaded   = false;
        $this->periodSlots      = [];
        $this->grid       = [];
    }

    // ── Load existing or start fresh ─────────────────────────────────

    public function loadRoutine(): void
    {
        if (! $this->academic_year_id || ! $this->class_id) {
            Notification::make()->title('Please select Academic Year and Class')->warning()->send();
            return;
        }

        $existing = ClassRoutine::where('academic_year_id', $this->academic_year_id)
            ->where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->orderBy('period_number')
            ->get();

        if ($existing->isEmpty()) {
            // Nothing saved yet — start blank, user will generate
            $this->periodSlots  = [];
            $this->grid   = [];
            $this->isLoaded = true;
            return;
        }

        // Rebuild slots from the Monday row-set (canonical period definition)
        $byDay   = $existing->groupBy(fn ($r) => $r->day_of_week->value);
        $dayRows = ($byDay->get('monday') ?? $byDay->first())->sortBy('period_number')->values();

        $this->periodSlots = $dayRows->map(fn ($r) => [
            'type'       => $r->is_break ? 'break' : 'period',
            'label'      => $r->is_break ? ($r->break_label ?? 'Break') : ('Period ' . $r->period_number),
            'duration'   => $this->minutesBetween(
                $r->start_time?->format('H:i') ?? '00:00',
                $r->end_time?->format('H:i')   ?? '00:00',
            ),
            'start_time' => $r->start_time?->format('H:i') ?? '',
            'end_time'   => $r->end_time?->format('H:i')   ?? '',
        ])->toArray();

        // Rebuild grid (index = period_number - 1, same order as slots)
        $this->grid = [];
        foreach ($existing as $row) {
            if (! $row->is_break) {
                $slotIdx = $row->period_number - 1;
                $key = $row->day_of_week->value . '.' . $slotIdx;
                $this->grid[$key] = [
                    'subject_id'  => $row->subject_id  ?? '',
                    'teacher_id'  => $row->teacher_id  ?? '',
                    'room_number' => $row->room_number ?? '',
                ];
            }
        }

        $this->isLoaded = true;
    }

    // ── Generator ────────────────────────────────────────────────────

    public function generateSlots(): void
    {
        if ($this->gen_period_count < 1 || $this->gen_period_count > 20) {
            Notification::make()->title('Period count must be between 1 and 20')->danger()->send();
            return;
        }
        if ($this->gen_duration_min < 5 || $this->gen_duration_min > 180) {
            Notification::make()->title('Duration must be between 5 and 180 minutes')->danger()->send();
            return;
        }

        $this->grid  = [];
        $this->periodSlots = [];

        $cursor = $this->gen_start_time;

        for ($i = 1; $i <= $this->gen_period_count; $i++) {
            $end = $this->addMinutes($cursor, $this->gen_duration_min);
            $this->periodSlots[] = [
                'type'       => 'period',
                'label'      => 'Period ' . $i,
                'duration'   => $this->gen_duration_min,
                'start_time' => $cursor,
                'end_time'   => $end,
            ];
            $cursor = $end;
        }

        $this->isLoaded = true;
    }

    // ── Slot editing ─────────────────────────────────────────────────

    /**
     * Called when user edits duration or start_time of any slot.
     * Recalculate end_time for that slot, then cascade all subsequent starts/ends.
     */
    public function recalcTimes(): void
    {
        $cursor = null;

        foreach ($this->periodSlots as $i => &$slot) {
            if ($cursor !== null) {
                $slot['start_time'] = $cursor;
            }

            // Recalculate end from start + duration
            $duration = max(1, (int) ($slot['duration'] ?? 1));
            $slot['end_time'] = $this->addMinutes($slot['start_time'], $duration);

            $cursor = $slot['end_time'];
        }
        unset($slot);
    }

    /**
     * Insert a break slot after position $afterIndex.
     */
    public function addBreakAfter(int $afterIndex): void
    {
        $prev = $this->periodSlots[$afterIndex] ?? null;
        $breakStart = $prev ? $prev['end_time'] : $this->gen_start_time;

        $breakSlot = [
            'type'       => 'break',
            'label'      => 'Break',
            'duration'   => 10,
            'start_time' => $breakStart,
            'end_time'   => $this->addMinutes($breakStart, 10),
        ];

        // Splice in at afterIndex + 1
        array_splice($this->periodSlots, $afterIndex + 1, 0, [$breakSlot]);

        // Re-map grid keys: any grid entry at slot index >= afterIndex+1 shifts up by 1
        $newGrid = [];
        foreach ($this->grid as $key => $cell) {
            [$day, $idx] = explode('.', $key, 2);
            $idx = (int) $idx;
            if ($idx > $afterIndex) {
                $idx++;
            }
            $newGrid[$day . '.' . $idx] = $cell;
        }
        $this->grid = $newGrid;

        $this->recalcTimes();
    }

    public function removeSlot(int $index): void
    {
        if (! isset($this->periodSlots[$index])) {
            return;
        }

        $wasBreak = $this->periodSlots[$index]['type'] === 'break';
        array_splice($this->periodSlots, $index, 1);

        // Remove grid cells at removed index and shift keys down
        $newGrid = [];
        foreach ($this->grid as $key => $cell) {
            [$day, $idx] = explode('.', $key, 2);
            $idx = (int) $idx;
            if ($idx === $index) {
                continue; // drop
            }
            if ($idx > $index) {
                $idx--;
            }
            $newGrid[$day . '.' . $idx] = $cell;
        }
        $this->grid = $newGrid;

        $this->recalcTimes();
    }

    // ── Cell modal ───────────────────────────────────────────────────

    public function openCell(string $day, int $slotIndex): void
    {
        if (! isset($this->periodSlots[$slotIndex]) || $this->periodSlots[$slotIndex]['type'] === 'break') {
            return;
        }

        $key = $day . '.' . $slotIndex;
        $cell = $this->grid[$key] ?? [];

        $this->modalDay        = $day;
        $this->modalSlotIndex  = $slotIndex;
        $this->modalSubjectId  = $cell['subject_id']  ?? '';
        $this->modalTeacherId  = $cell['teacher_id']  ?? '';
        $this->modalRoomNumber = $cell['room_number'] ?? '';
        $this->showModal       = true;
    }

    public function saveCell(): void
    {
        if ($this->modalDay === null || $this->modalSlotIndex === null) {
            return;
        }

        $key = $this->modalDay . '.' . $this->modalSlotIndex;
        $this->grid[$key] = [
            'subject_id'  => $this->modalSubjectId,
            'teacher_id'  => $this->modalTeacherId,
            'room_number' => $this->modalRoomNumber,
        ];

        $this->closeModal();
    }

    public function clearCell(): void
    {
        if ($this->modalDay !== null && $this->modalSlotIndex !== null) {
            unset($this->grid[$this->modalDay . '.' . $this->modalSlotIndex]);
        }
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showModal       = false;
        $this->modalDay        = null;
        $this->modalSlotIndex  = null;
        $this->modalSubjectId  = '';
        $this->modalTeacherId  = '';
        $this->modalRoomNumber = '';
    }

    // ── Copy day ─────────────────────────────────────────────────────

    public function copyDay(string $fromDay, string $toDay): void
    {
        if ($fromDay === $toDay) {
            return;
        }

        foreach (array_keys($this->periodSlots) as $i) {
            $fromKey = $fromDay . '.' . $i;
            $toKey   = $toDay   . '.' . $i;
            if (isset($this->grid[$fromKey])) {
                $this->grid[$toKey] = $this->grid[$fromKey];
            } else {
                unset($this->grid[$toKey]);
            }
        }

        Notification::make()
            ->title(ucfirst($fromDay) . ' copied to ' . ucfirst($toDay))
            ->success()
            ->send();
    }

    // ── Persist ──────────────────────────────────────────────────────

    public function saveRoutine(): void
    {
        if (! $this->academic_year_id || ! $this->class_id) {
            Notification::make()->title('Select Academic Year and Class first')->warning()->send();
            return;
        }
        if (empty($this->periodSlots)) {
            Notification::make()->title('Generate or add at least one slot')->warning()->send();
            return;
        }

        // Validate slot times
        foreach ($this->periodSlots as $i => $slot) {
            if (empty($slot['start_time']) || empty($slot['end_time'])) {
                Notification::make()->title("Slot " . ($i + 1) . " is missing times")->danger()->send();
                return;
            }
            if ($slot['start_time'] >= $slot['end_time']) {
                Notification::make()->title("Slot " . ($i + 1) . ": end must be after start")->danger()->send();
                return;
            }
        }

        ClassRoutine::where('academic_year_id', $this->academic_year_id)
            ->where('class_id', $this->class_id)
            ->when($this->section_id, fn ($q) => $q->where('section_id', $this->section_id))
            ->delete();

        $inserts = [];

        foreach (DayOfWeek::cases() as $day) {
            foreach ($this->periodSlots as $i => $slot) {
                $isBreak = $slot['type'] === 'break';
                $key     = $day->value . '.' . $i;
                $cell    = $isBreak ? [] : ($this->grid[$key] ?? []);

                $inserts[] = [
                    'id'               => \Illuminate\Support\Str::ulid(),
                    'academic_year_id' => $this->academic_year_id,
                    'class_id'         => $this->class_id,
                    'section_id'       => $this->section_id ?: null,
                    'day_of_week'      => $day->value,
                    'period_number'    => $i + 1,  // slot position; unique per day
                    'start_time'       => $slot['start_time'],
                    'end_time'         => $slot['end_time'],
                    'is_break'         => $isBreak,
                    'break_label'      => $isBreak ? ($slot['label'] ?? 'Break') : null,
                    'subject_id'       => (! $isBreak && ! empty($cell['subject_id'])) ? $cell['subject_id'] : null,
                    'teacher_id'       => (! $isBreak && ! empty($cell['teacher_id'])) ? $cell['teacher_id'] : null,
                    'room_number'      => ! empty($cell['room_number']) ? $cell['room_number'] : null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }
        }

        ClassRoutine::insert($inserts);

        Notification::make()
            ->title('Routine saved successfully')
            ->body('Saved ' . count($inserts) . ' slots across all days.')
            ->success()
            ->send();
    }

    // ── Label helpers (used in Blade) ────────────────────────────────

    public function subjectName(string $id): string
    {
        return $this->subjectOptions[$id] ?? '';
    }

    public function teacherName(string $id): string
    {
        return $this->teacherOptions[$id] ?? '';
    }

    // ── Time utilities ───────────────────────────────────────────────

    private function addMinutes(string $time, int $minutes): string
    {
        [$h, $m] = array_map('intval', explode(':', $time));
        $total   = $h * 60 + $m + $minutes;
        return sprintf('%02d:%02d', intdiv($total, 60) % 24, $total % 60);
    }

    private function minutesBetween(string $start, string $end): int
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        return max(1, ($eh * 60 + $em) - ($sh * 60 + $sm));
    }
}
