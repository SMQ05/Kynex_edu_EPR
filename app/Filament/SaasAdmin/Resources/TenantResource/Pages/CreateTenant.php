<?php

declare(strict_types=1);

namespace App\Filament\SaasAdmin\Resources\TenantResource\Pages;

use App\Actions\ProvisionNewTenant;
use App\Filament\SaasAdmin\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = TenantResource::mutateFormDataBeforeCreate($data);

        // Auto-generate tenant ID from school name if not set
        if (empty($data['id'])) {
            $data['id'] = Str::slug($data['school_name'] ?? 'school').'-'.Str::random(6);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Provision the tenant DB, create admin user, send welcome email
        try {
            (new ProvisionNewTenant())->__invoke($this->record);
        } catch (\Throwable $e) {
            Log::error('Tenant provisioning failed', [
                'tenant_id' => $this->record->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
