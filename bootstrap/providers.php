<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\ParentPanelProvider;
use App\Providers\Filament\SaasAdminPanelProvider;
use App\Providers\Filament\SchoolAdminPanelProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    SaasAdminPanelProvider::class,
    SchoolAdminPanelProvider::class,
    ParentPanelProvider::class,
];
