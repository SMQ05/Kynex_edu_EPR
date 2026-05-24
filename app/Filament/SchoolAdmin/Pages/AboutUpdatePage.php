<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;

/**
 * Read-only application / version information. Mirrors InfixEdu's
 * "About & Update" page (without the self-update workflow — KynexEdu
 * is updated centrally via the SaaS platform / deployment pipeline).
 */
class AboutUpdatePage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-information-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 68;

    protected static ?string $navigationLabel = 'About & Update';

    protected static ?string $title = 'About & Update';

    protected string $view = 'filament.school-admin.pages.about-update';

    /** @return array<string,string> */
    public function info(): array
    {
        return [
            'Application'   => (string) config('app.name', 'KynexEdu'),
            'Version'       => (string) config('app.version', 'managed by kynexsolutions.com'),
            'Environment'   => (string) App::environment(),
            'Laravel'       => App::version(),
            'PHP'           => PHP_VERSION,
            'Locale'        => (string) App::getLocale(),
            'School (tenant)' => function_exists('tenant') && tenant() ? (string) tenant()->id : 'central',
        ];
    }
}
