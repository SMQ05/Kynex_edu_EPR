<?php

declare(strict_types=1);

namespace App\Filament\Shared\Pages;

use Filament\Pages\Page;

/**
 * Abstract base for per-panel AccessDenied pages.
 *
 * Registered on each panel via its own thin subclass in the panel's namespace
 * so Filament's discoverPages() picks it up automatically.
 *
 * The bootstrap/app.php exception handler redirects authenticated users who
 * trigger a 403 on a panel route to their panel's subclass of this page,
 * preserving the full sidebar/header chrome instead of showing a stripped
 * error view.
 */
abstract class AccessDeniedPage extends Page
{
    protected static ?string $title = 'Access Denied';

    protected string $view = 'filament.shared.pages.access-denied';

    /** Always reachable — must never 403 itself. */
    public static function canAccess(): bool
    {
        return auth()->check();
    }

    /** Not a navigation destination; hidden from every panel sidebar. */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /** The URL the user was trying to reach before being redirected here. */
    public function getDeniedUrl(): ?string
    {
        return session('denied_url');
    }
}
