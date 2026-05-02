@php
    // Only mount the launcher if (a) a user is logged in, (b) tenancy is
    // initialized, and (c) the tenant has AI enabled. Otherwise we'd
    // render a button that always errors when clicked.
    $shouldShow = false;
    try {
        $user   = auth('school_users')->user();
        $tenant = tenant();
        $shouldShow = (bool) ($user && $tenant && $tenant->ai_enabled);
    } catch (\Throwable $e) {
        $shouldShow = false;
    }
@endphp

@if($shouldShow)
    @livewire('ai-assistant-panel')
@endif
