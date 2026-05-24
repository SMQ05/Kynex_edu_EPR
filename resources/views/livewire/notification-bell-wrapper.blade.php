{{-- Only mount the bell inside an initialized tenant context. The component
     itself also guards every query, but skipping the mount entirely avoids
     an unnecessary Livewire component on central/non-tenant renders. --}}
@if (function_exists('tenancy') && tenancy()->initialized)
    @livewire('notification-bell')
@endif
