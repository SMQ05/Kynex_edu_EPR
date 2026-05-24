<?php

namespace App\Filament\SchoolAdmin\Widgets\Counselor;

use Filament\Widgets\Widget;

class CounselorStubWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.school-admin.widgets.counselor-stub';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }
}
