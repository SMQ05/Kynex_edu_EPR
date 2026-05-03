<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FeeMasterResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeMasterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeeMaster extends CreateRecord
{
    protected static string $resource = FeeMasterResource::class;

    /**
     * Convert the user-facing PKR rupee input into integer paisas before
     * persisting. The form field is virtual (dehydrated:false) so we
     * have to read it from the raw form state.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rupees = (float) ($this->data['amount_pkr'] ?? 0);
        $data['amount_paisas'] = (int) round($rupees * 100);
        unset($data['amount_pkr']);
        return $data;
    }
}
