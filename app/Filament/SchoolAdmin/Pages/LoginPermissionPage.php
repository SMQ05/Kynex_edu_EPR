<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\DueFeesLoginSetting;
use App\Models\Tenant\LoginPermission;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

/**
 * Login Permission + Due-Fees Login Permission — config UI + storage only.
 * Enforcement is documented in the build report, NOT wired into auth here.
 */
class LoginPermissionPage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'manage_login_permissions';

    protected static string $rbacWritePermission = 'manage_school_roles';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Login Permission';

    protected static ?int $navigationSort = 31;

    protected static ?string $title = 'Login Permission';

    protected string $view = 'filament.school-admin.pages.login-permission';

    public ?array $data = [];

    public function mount(): void
    {
        $roles = $this->roleNames();

        $existing = LoginPermission::query()->pluck('can_login', 'role')->all();

        $rolesState = [];
        foreach ($roles as $role) {
            $rolesState['role__' . $this->key($role)] = array_key_exists($role, $existing)
                ? (bool) $existing[$role]
                : true;
        }

        $due = DueFeesLoginSetting::current();

        $this->form->fill(array_merge($rolesState, [
            'due_enabled'        => (bool) $due->enabled,
            'due_grace_days'     => (int) $due->grace_days,
            'due_applies_to'     => (string) $due->applies_to,
            'due_min_due_pkr'    => $due->min_due_paisas / 100,
            'due_block_message'  => $due->block_message,
        ]));
    }

    public function form(Schema $schema): Schema
    {
        $roleToggles = [];
        foreach ($this->roleNames() as $role) {
            $roleToggles[] = Toggle::make('role__' . $this->key($role))
                ->label($role)
                ->inline(false);
        }

        if ($roleToggles === []) {
            $roleToggles[] = Placeholder::make('no_roles')
                ->label('')
                ->content('No roles found for this school yet.');
        }

        return $schema
            ->components([
                Section::make('Role Login Permission')
                    ->description('Turn a role off to prevent its users from logging in. Enforcement is applied at sign-in (see deployment notes). Defaults to allowed.')
                    ->schema($roleToggles)
                    ->columns(2),

                Section::make('Due-Fees Login Block')
                    ->description('Optionally block students/guardians with overdue fees from logging in to the portal.')
                    ->schema([
                        Toggle::make('due_enabled')
                            ->label('Block login when fees are overdue')
                            ->live(),
                        Select::make('due_applies_to')
                            ->label('Applies to')
                            ->options(DueFeesLoginSetting::APPLIES_TO)
                            ->default('students')
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('due_enabled')),
                        TextInput::make('due_grace_days')
                            ->label('Grace days after due date')
                            ->numeric()->minValue(0)->default(0)
                            ->visible(fn (Get $get): bool => (bool) $get('due_enabled')),
                        TextInput::make('due_min_due_pkr')
                            ->label('Only block if amount due ≥ (PKR)')
                            ->numeric()->minValue(0)->default(0)
                            ->visible(fn (Get $get): bool => (bool) $get('due_enabled')),
                        Textarea::make('due_block_message')
                            ->label('Block message shown to the user')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('due_enabled')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->roleNames() as $role) {
            $field = 'role__' . $this->key($role);
            LoginPermission::updateOrCreate(
                ['role' => $role],
                ['can_login' => (bool) ($state[$field] ?? true)],
            );
        }

        $due = DueFeesLoginSetting::current();
        $due->update([
            'enabled'        => (bool) ($state['due_enabled'] ?? false),
            'grace_days'     => (int) ($state['due_grace_days'] ?? 0),
            'applies_to'     => (string) ($state['due_applies_to'] ?? 'students'),
            'min_due_paisas' => (int) round(((float) ($state['due_min_due_pkr'] ?? 0)) * 100),
            'block_message'  => $state['due_block_message'] ?? null,
        ]);

        Notification::make()->title('Login permissions saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }

    /** @return array<int,string> Role names for the school_users guard. */
    protected function roleNames(): array
    {
        try {
            return Role::query()
                ->where('guard_name', 'school_users')
                ->orderBy('name')
                ->pluck('name')
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /** Make a role name safe to use as a form field key. */
    protected function key(string $role): string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '_', $role) ?? $role;
    }
}
