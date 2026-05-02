<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Resources\CampusResource\Pages;

use App\Filament\SchoolAdmin\Resources\CampusResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCampuses extends ListRecords
{
    protected static string $resource = CampusResource::class;

    public function getSubheading(): ?string
    {
        return 'Campuses are managed at the institute level. Only Multi-Institute Head or Kynex Solutions (SaaS admin) may add new campuses. School admins operate within their assigned campus only.';
    }

    protected function getHeaderActions(): array
    {
        // If the actor is allowed to create, show the normal CreateAction.
        // Otherwise show a disabled-looking button that, when clicked,
        // surfaces a clear explanation instead of a silent 403.
        if (CampusResource::canCreate()) {
            return [CreateAction::make()];
        }

        return [
            Action::make('whoCanAddCampus')
                ->label('New campus')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->modalIcon('heroicon-o-information-circle')
                ->modalHeading('Campus creation is restricted')
                ->modalDescription('A campus can only be added by a Multi-Institute Head or by Kynex Solutions (SaaS admin). To request a new campus, contact your Multi-Institute Head, or email hello@kynexsolutions.com to request one through Kynex Solutions.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Got it')
                ->action(fn () => Notification::make()
                    ->title('Campus creation is restricted')
                    ->body('Only Multi-Institute Head or SaaS admin may add campuses. Contact hello@kynexsolutions.com.')
                    ->warning()
                    ->send()),
        ];
    }
}
