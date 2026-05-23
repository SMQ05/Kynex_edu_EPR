<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | In production (Coolify) VIEW_COMPILED_PATH points OUTSIDE the shared
    | storage volume (e.g. bootstrap/cache/views), so each container compiles
    | its own views. Combined with pre-compiling on boot (docker/bootstrap.sh),
    | this prevents concurrent Apache workers / containers from race-writing a
    | half-finished compiled view — the cause of intermittent Blade 500s.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
