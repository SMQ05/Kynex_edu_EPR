<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

/**
 * Gives a Demo seeder access to the active locale / school profile.
 *
 * Seeders call $this->profile() instead of the old static Pak:: helpers so
 * the same seeding logic can produce either the Pakistani AQM school or the
 * US Lincoln Heights school. See {@see DemoProfile}.
 */
trait UsesDemoProfile
{
    protected function profile(): DemoProfile
    {
        return DemoProfile::current();
    }
}
