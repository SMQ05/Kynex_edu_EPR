@php
    $user = auth()->guard('school_users')->user();
    $shouldShow = false;

    if ($user && tenancy()->initialized) {
        try {
            $hasInstituteHead = \App\Models\SchoolUser::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD']))
                ->exists();
            $shouldShow = ! $hasInstituteHead;
        } catch (\Throwable $e) {
            $shouldShow = false;
        }
    }
@endphp

@if($shouldShow)
    <div class="bg-amber-50 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-800">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="text-amber-600 dark:text-amber-400 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376C1.83 17.6 2.914 19.5 4.645 19.5h14.71c1.73 0 2.815-1.9 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="text-sm">
                    <div class="font-semibold text-amber-900 dark:text-amber-100">No Institute Head assigned yet</div>
                    <div class="text-amber-800 dark:text-amber-200 mt-0.5">
                        For governance compliance, an Institute Head or Multi-Institute Head must be assigned to your school.
                        Please <strong>contact <a href="https://kynexsolutions.com" target="_blank" rel="noopener" class="underline hover:text-amber-700 dark:hover:text-amber-300">Kynexsolutions.com</a></strong>
                        to assign an Institute Head or Multi-Institute Head for your tenant.
                    </div>
                </div>
            </div>
            <a href="mailto:hello@kynexsolutions.com?subject=Assign%20Institute%20Head%20for%20{{ urlencode(tenant()?->school_name ?? '') }}"
               class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold px-3 py-2">
                Email Kynex Solutions →
            </a>
        </div>
    </div>
@endif
