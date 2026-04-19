<?php

namespace App\Filament\SchoolAdmin\Widgets\Nurse;

use Filament\Widgets\Widget;

class NurseHealthStubWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.school-admin.widgets.nurse-health-stub';
}
