<?php

declare(strict_types=1);

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * Renders a user's initials as an inline SVG data URI.
 *
 * WHY: Filament's default provider points every avatar at ui-avatars.com, so
 * each panel page load blocks on a third-party request. Measured at ~200ms per
 * page on a normal connection, and far worse behind a VPN — it was one of two
 * external fetches making the app feel slow (the other was the fonts.bunny.net
 * stylesheet, removed from the blade views).
 *
 * A data URI costs nothing, works offline, and keeps user names from being sent
 * to a third party on every page view — which matters more than usual here,
 * because the names belong to schoolchildren.
 */
class LocalInitialsAvatarProvider implements AvatarProvider
{
    /** Muted, accessible background palette; picked deterministically per name. */
    private const COLOURS = [
        '#0f766e', '#1d4ed8', '#7e22ce', '#b91c1c',
        '#a16207', '#15803d', '#0e7490', '#9d174d',
    ];

    public function get(Model $record): string
    {
        $name = trim((string) ($record->getAttribute('name') ?? ''));
        $initials = $this->initials($name);

        // Deterministic colour so a given person keeps the same avatar.
        $colour = self::COLOURS[abs(crc32($name ?: 'user')) % count(self::COLOURS)];

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
        <rect width="64" height="64" rx="32" fill="{$colour}"/>
        <text x="32" y="33" fill="#ffffff" font-family="ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
              font-size="26" font-weight="600" text-anchor="middle" dominant-baseline="central">{$initials}</text>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,' . base64_encode(preg_replace('/\s+/', ' ', trim($svg)));
    }

    /** First letter of the first and last word, uppercased. */
    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}
