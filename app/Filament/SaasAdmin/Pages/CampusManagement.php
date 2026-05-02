<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Pages;

use App\Models\Tenant;
use App\Models\Tenant\Campus;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * SaaS-admin can pick a tenant and manage that tenant's Campus rows
 * cross-tenant. Tenancy initialises temporarily inside each action.
 */
class CampusManagement extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Campuses';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Cross-tenant Campus Management';

    protected string $view = 'filament.saas-admin.pages.campus-management';

    public ?string $tenant_id = null;
    public string $name = '';
    public ?string $address = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $editing_id = null;

    public function mount(): void
    {
        // No-op; tenant_id stays null until selected.
    }

    #[Computed]
    public function tenantOptions(): array
    {
        return Tenant::orderBy('school_name')
            ->get()
            ->mapWithKeys(fn (Tenant $t) => [$t->id => "{$t->school_name} ({$t->id})"])
            ->all();
    }

    #[Computed]
    public function campuses(): Collection
    {
        if (! $this->tenant_id) {
            return collect();
        }

        $tenant = Tenant::find($this->tenant_id);
        if (! $tenant) {
            return collect();
        }

        $rows = collect();
        $tenant->run(function () use (&$rows) {
            $rows = Campus::orderBy('name')->get(['id', 'name', 'address', 'phone', 'email']);
        });
        return $rows;
    }

    public function startEdit(string $id): void
    {
        if (! $this->tenant_id) return;
        Tenant::find($this->tenant_id)?->run(function () use ($id) {
            $c = Campus::find($id);
            if ($c) {
                $this->editing_id = $c->id;
                $this->name = $c->name;
                $this->address = $c->address;
                $this->phone = $c->phone;
                $this->email = $c->email;
            }
        });
    }

    public function resetForm(): void
    {
        $this->editing_id = null;
        $this->name = '';
        $this->address = null;
        $this->phone = null;
        $this->email = null;
    }

    public function saveCampus(): void
    {
        if (! $this->tenant_id) {
            Notification::make()->title('Pick a tenant first')->warning()->send();
            return;
        }
        if (! filled($this->name)) {
            Notification::make()->title('Name is required')->danger()->send();
            return;
        }

        $payload = [
            'name'    => $this->name,
            'address' => $this->address,
            'phone'   => $this->phone,
            'email'   => $this->email,
        ];

        $editingId = $this->editing_id;
        $tenant = Tenant::find($this->tenant_id);
        if (! $tenant) return;

        $tenant->run(function () use ($editingId, $payload) {
            if ($editingId) {
                Campus::where('id', $editingId)->update($payload);
            } else {
                Campus::create($payload);
            }
        });

        Notification::make()
            ->title($editingId ? 'Campus updated' : 'Campus created')
            ->success()
            ->send();

        $this->resetForm();
    }

    public function deleteCampus(string $id): void
    {
        if (! $this->tenant_id) return;
        Tenant::find($this->tenant_id)?->run(function () use ($id) {
            Campus::where('id', $id)->delete();
        });

        Notification::make()->title('Campus deleted')->success()->send();
    }
}
