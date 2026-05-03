<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\FeeMasterResource\Pages;

use App\Filament\SchoolAdmin\Resources\FeeMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeMaster extends EditRecord
{
    protected static string $resource = FeeMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($this->data['amount_pkr'])) {
            $rupees = (float) $this->data['amount_pkr'];
            $data['amount_paisas'] = (int) round($rupees * 100);
        }
        unset($data['amount_pkr']);
        return $data;
    }
}
