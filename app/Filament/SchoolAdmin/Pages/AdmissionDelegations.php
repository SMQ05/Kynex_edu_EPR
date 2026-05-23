<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\SchoolUser;
use App\Models\Tenant\SchoolClass;
use App\Services\AdmissionDelegationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;

/**
 * Class-scoped delegation page. School Admin / Institute Head picks a
 * staff member or teacher and grants any combination of the four
 * admission permissions, each scoped to all classes or to specific
 * classes.
 *
 * Parents and students are filtered out of the user picker — only
 * staff/teacher accounts can be delegated to.
 */
class AdmissionDelegations extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-plus';

    protected static string | \UnitEnum | null $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Delegations';

    protected static ?string $title = 'Admission Delegations';

    protected string $view = 'filament.school-admin.pages.admission-delegations';

    public ?string $userId = null;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth('school_users')->user();
        return $user?->hasRole(['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill($this->emptyState());
    }

    public function updatedUserId(): void
    {
        $user = $this->resolveUser();
        if (! $user) {
            $this->form->fill($this->emptyState());
            return;
        }

        $delegations = app(AdmissionDelegationService::class)->loadForUser($user);
        $this->form->fill(['perms' => $delegations]);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach ($this->permissionLabels() as $perm => $label) {
            $sections[] = FormSection::make($label)
                ->description($this->permissionDescription($perm))
                ->collapsible()
                ->collapsed(false)
                ->schema([
                    Toggle::make("perms.{$perm}.granted")
                        ->label('Grant this permission')
                        ->live(),

                    Toggle::make("perms.{$perm}.allClasses")
                        ->label('Apply to all classes')
                        ->helperText('When on, this user can act for every class. Turn off to limit to specific classes.')
                        ->visible(fn (callable $get) => (bool) $get("perms.{$perm}.granted"))
                        ->live(),

                    Select::make("perms.{$perm}.classIds")
                        ->label('Limit to these classes')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => SchoolClass::query()->orderBy('sort_order')->orderBy('name')->pluck('name', 'id'))
                        ->visible(fn (callable $get) => (bool) $get("perms.{$perm}.granted")
                            && ! (bool) $get("perms.{$perm}.allClasses"))
                        ->required(fn (callable $get) => (bool) $get("perms.{$perm}.granted")
                            && ! (bool) $get("perms.{$perm}.allClasses"))
                        ->columnSpanFull(),
                ]);
        }

        // Insert the role placeholder at the top.
        array_unshift($sections, FormSection::make('User')
            ->schema([
                Placeholder::make('user_role')
                    ->label('Current roles')
                    ->content(function () {
                        $user = $this->resolveUser();
                        if (! $user) return '— pick a user above —';
                        $roles = $user->roles->pluck('name')->all();
                        return $roles ? implode(', ', $roles) : 'No roles assigned';
                    }),
            ]));

        return $schema->statePath('data')->schema($sections);
    }

    public function save(): void
    {
        $user = $this->resolveUser();
        if (! $user) {
            Notification::make()->title('Pick a user first')->warning()->send();
            return;
        }

        $config = (array) ($this->form->getState()['perms'] ?? []);

        // Validate: if granted but neither all-classes nor any class IDs,
        // surface a clean error.
        foreach ($this->permissionLabels() as $perm => $label) {
            $row = $config[$perm] ?? [];
            if (! empty($row['granted'])
                && empty($row['allClasses'])
                && empty($row['classIds'])
            ) {
                Notification::make()
                    ->title("Pick at least one class for: {$label}")
                    ->body('Or turn on "Apply to all classes".')
                    ->danger()
                    ->send();
                return;
            }
        }

        app(AdmissionDelegationService::class)->syncForUser($user, $config);

        Notification::make()
            ->title('Delegation updated')
            ->body($this->summarise($config) ?: 'No admission permissions for ' . $user->name . '.')
            ->success()
            ->send();
    }

    /**
     * Active staff/teacher accounts only. Parents and students are
     * filtered out — they should never appear in this picker.
     */
    public function getUserOptions(): array
    {
        return SchoolUser::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['PARENT', 'STUDENT']))
            ->with('roles')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(function (SchoolUser $u) {
                $roles = $u->roles
                    ->whereNotIn('name', ['PARENT', 'STUDENT'])
                    ->pluck('name')
                    ->join(', ');
                $roleTag = $roles ? " [{$roles}]" : '';
                return [$u->id => "{$u->name}{$roleTag} · {$u->email}"];
            })
            ->all();
    }

    public function resolveUser(): ?SchoolUser
    {
        if (! $this->userId) {
            return null;
        }
        $user = SchoolUser::with('roles')->find($this->userId);

        // Belt-and-braces: refuse to operate on parents/students even if
        // someone hits the page with a hand-crafted ID.
        if ($user && $user->hasRole(['PARENT', 'STUDENT']) && ! $user->roles->whereNotIn('name', ['PARENT', 'STUDENT'])->count()) {
            return null;
        }
        return $user;
    }

    protected function emptyState(): array
    {
        $perms = [];
        foreach (array_keys($this->permissionLabels()) as $perm) {
            $perms[$perm] = ['granted' => false, 'allClasses' => false, 'classIds' => []];
        }
        return ['perms' => $perms];
    }

    protected function permissionLabels(): array
    {
        return [
            'enter_admission_marks'       => 'Enter Admission Test / Interview Marks',
            'conduct_admission_interview' => 'Conduct & Schedule Admission Interviews',
            'create_admission_tests'      => 'Create Admission Tests & Question Banks',
            'manage_student_admissions'   => 'Manage Student Admissions (umbrella — full lifecycle)',
        ];
    }

    protected function permissionDescription(string $perm): string
    {
        return match ($perm) {
            'enter_admission_marks'       => 'Allows recording entry-test and interview marks via the Marks & Interview page.',
            'conduct_admission_interview' => 'Allows scheduling interviews and recording interview scores from the Admissions list.',
            'create_admission_tests'      => 'Allows creating Admission Tests, building question banks, and AI-grading attempts.',
            'manage_student_admissions'   => 'Includes all three above plus the override workflow. Use sparingly.',
            default                       => '',
        };
    }

    protected function summarise(array $config): string
    {
        $lines = [];
        foreach ($this->permissionLabels() as $perm => $label) {
            $row = $config[$perm] ?? [];
            if (empty($row['granted'])) continue;
            if (! empty($row['allClasses'])) {
                $lines[] = "{$label}: all classes";
                continue;
            }
            $count = count((array) ($row['classIds'] ?? []));
            $lines[] = "{$label}: {$count} class(es)";
        }
        return $lines ? implode("\n", $lines) : '';
    }

    /**
     * Returns all users who currently hold at least one admission delegation,
     * grouped for the summary table. Each row: user info + per-permission scope.
     *
     * @return array<int, array{name:string, email:string, roles:string, perms:array}>
     */
    public function getDelegationSummary(): array
    {
        $labels = $this->permissionLabels();

        $rows = \App\Models\Tenant\AdmissionDelegation::query()
            ->whereIn('permission', AdmissionDelegationService::PERMISSIONS)
            ->get(['school_user_id', 'permission', 'class_id']);

        if ($rows->isEmpty()) {
            return [];
        }

        $userIds = $rows->pluck('school_user_id')->unique()->all();
        $users   = SchoolUser::with('roles')->whereIn('id', $userIds)->get()->keyBy('id');
        $classes = SchoolClass::pluck('name', 'id');

        $summary = [];
        foreach ($userIds as $uid) {
            $user = $users[$uid] ?? null;
            if (! $user) continue;

            $userRows = $rows->where('school_user_id', $uid);
            $perms    = [];
            foreach ($labels as $perm => $label) {
                $matching = $userRows->where('permission', $perm);
                if ($matching->isEmpty()) continue;

                $hasAll = $matching->contains(fn ($r) => $r->class_id === null);
                if ($hasAll) {
                    $scope = 'All classes';
                } else {
                    $names = $matching->pluck('class_id')
                        ->map(fn ($id) => $classes[$id] ?? "#{$id}")
                        ->join(', ');
                    $scope = $names ?: '—';
                }
                $perms[$perm] = ['label' => $label, 'scope' => $scope];
            }

            $roleNames = $user->roles
                ->whereNotIn('name', ['PARENT', 'STUDENT'])
                ->pluck('name')->join(', ');

            $summary[] = [
                '_uid'  => $uid,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $roleNames ?: '—',
                'perms' => $perms,
            ];
        }

        usort($summary, fn ($a, $b) => strcmp($a['name'], $b['name']));
        return $summary;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reload')
                ->label('Reload')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->updatedUserId()),
        ];
    }
}
