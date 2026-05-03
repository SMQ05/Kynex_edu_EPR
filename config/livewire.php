<?php

return [
    'class_namespace' => 'App\\Livewire',
    'view_path'       => resource_path('views/livewire'),
    'layout'          => 'components.layouts.app',

    'lazy_placeholder' => null,
    'pagination_theme' => 'tailwind',

    'legacy_model_binding' => false,

    'inject_assets'      => true,
    'inject_morph_markers' => true,

    /**
     * Temporary file uploads.
     *
     * Pins Livewire's temporary upload disk to `public` so it writes to
     * `storage/app/public/livewire-tmp/...` — the same disk Filament's
     * FileUpload reads back from for validation. With Laravel 11 the
     * default `local` disk's root moved to `storage/app/private`, while
     * `FILAMENT_FILESYSTEM_DISK=public` made Filament look elsewhere,
     * which caused "Unable to retrieve the file_size" 500s when the
     * validation tried to read a path that didn't exist on its disk.
     */
    'temporary_file_upload' => [
        'disk'                  => 'public',
        'rules'                 => null,
        'directory'             => 'livewire-tmp',
        'middleware'            => null,
        'preview_mimes'         => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time'       => 5,
        'cleanup'               => true,
    ],

    'render_on_redirect' => false,
    'enable_morph'       => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#2299dd',
    ],
];
