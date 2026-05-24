<?php

namespace App\Filament\SchoolAdmin\Widgets\Transport;

use Filament\Widgets\Widget;

class TransportGpsPlaceholderWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.school-admin.widgets.transport-gps-placeholder';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }
}
