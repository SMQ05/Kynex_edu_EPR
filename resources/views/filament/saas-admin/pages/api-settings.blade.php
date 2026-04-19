<x-filament-panels::page>
    <style>
        .api-settings-header { border-radius: 0.75rem; padding: 1rem; border: 1px solid; display: flex; align-items: flex-start; gap: 0.75rem; }
        .api-settings-header.light-bg { background-color: var(--primary-50, #eff6ff); border-color: var(--primary-200, #bfdbfe); }
        :is(.dark .api-settings-header.light-bg) { background-color: color-mix(in oklab, var(--primary-950, #1e1b4b) 50%, transparent); border-color: var(--primary-800, #1e40af); }
        .api-settings-header svg.header-icon { width: 1.5rem; height: 1.5rem; flex-shrink: 0; margin-top: 0.125rem; color: var(--primary-600, #2563eb); }
        :is(.dark .api-settings-header svg.header-icon) { color: var(--primary-400, #60a5fa); }
        .api-settings-header h3 { font-weight: 600; color: var(--primary-900, #1e3a5f); }
        :is(.dark .api-settings-header h3) { color: var(--primary-100, #dbeafe); }
        .api-settings-header p { font-size: 0.875rem; margin-top: 0.25rem; color: var(--primary-700, #1d4ed8); }
        :is(.dark .api-settings-header p) { color: var(--primary-300, #93c5fd); }
        .api-docs-box { border-radius: 0.75rem; padding: 1rem; border: 1px solid var(--gray-200, #e5e7eb); background-color: var(--gray-50, #f9fafb); }
        :is(.dark .api-docs-box) { border-color: var(--gray-700, #374151); background-color: var(--gray-900, #111827); }
        .api-docs-box h4 { font-weight: 600; margin-bottom: 0.75rem; color: var(--gray-900, #111827); }
        :is(.dark .api-docs-box h4) { color: var(--gray-100, #f3f4f6); }
        .api-docs-links { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        @media (min-width: 768px) { .api-docs-links { grid-template-columns: repeat(3, 1fr); } }
        .api-docs-links a { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; text-decoration: none; }
        .api-docs-links a:hover { text-decoration: underline; }
        .api-docs-links a svg { flex-shrink: 0; }
        .api-docs-links a .link-icon { width: 1rem; height: 1rem; }
        .api-docs-links a .ext-icon { width: 0.75rem; height: 0.75rem; }
        .api-docs-links a.sms-link { color: var(--primary-600, #2563eb); }
        :is(.dark .api-docs-links a.sms-link) { color: var(--primary-400, #60a5fa); }
        .api-docs-links a.wa-link { color: #16a34a; }
        :is(.dark .api-docs-links a.wa-link) { color: #4ade80; }
        .api-docs-links a.ai-link { color: #d97706; }
        :is(.dark .api-docs-links a.ai-link) { color: #fbbf24; }
        .api-save-all-section { border-top: 1px solid var(--gray-200, #e5e7eb); padding-top: 1.5rem; }
        :is(.dark .api-save-all-section) { border-color: var(--gray-700, #374151); }
        .api-form-actions { margin-top: 1rem; display: flex; align-items: center; gap: 0.75rem; }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Header Info --}}
        <div class="api-settings-header light-bg">
            <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            <div>
                <h3>Platform API Configuration</h3>
                <p>
                    Configure platform-level API credentials for SMS, WhatsApp, and AI services.
                    These settings apply globally and are used by all tenant schools.
                    Sensitive values (API keys, passwords) are encrypted at rest.
                </p>
            </div>
        </div>

        {{-- SMS Gateway Section --}}
        <form wire:submit="saveSmsSettings">
            {{ $this->smsForm }}

            <div class="api-form-actions">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Save SMS Settings
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-signal"
                    wire:click="testSmsConnection"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="testSmsConnection">Test Connection</span>
                    <span wire:loading wire:target="testSmsConnection">Testing…</span>
                </x-filament::button>
            </div>
        </form>

        {{-- WhatsApp Section --}}
        <form wire:submit="saveWhatsappSettings">
            {{ $this->whatsappForm }}

            <div class="api-form-actions">
                <x-filament::button type="submit" color="success" icon="heroicon-o-check">
                    Save WhatsApp Settings
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-signal"
                    wire:click="testWhatsappConnection"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="testWhatsappConnection">Test Connection</span>
                    <span wire:loading wire:target="testWhatsappConnection">Testing…</span>
                </x-filament::button>
            </div>
        </form>

        {{-- AI Section --}}
        <form wire:submit="saveAiSettings">
            {{ $this->aiForm }}

            <div class="api-form-actions">
                <x-filament::button type="submit" color="warning" icon="heroicon-o-check">
                    Save AI Settings
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-signal"
                    wire:click="testAiConnection"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="testAiConnection">Test Connection</span>
                    <span wire:loading wire:target="testAiConnection">Testing…</span>
                </x-filament::button>
            </div>
        </form>

        {{-- Save All --}}
        <div class="api-save-all-section">
            <x-filament::button
                size="lg"
                icon="heroicon-o-arrow-down-tray"
                wire:click="saveAllSettings"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="saveAllSettings">Save All Settings</span>
                <span wire:loading wire:target="saveAllSettings">Saving…</span>
            </x-filament::button>
        </div>

        {{-- API Reference Links --}}
        <div class="api-docs-box">
            <h4>📚 API Documentation Links</h4>
            <div class="api-docs-links">
                <a href="https://github.com/capcom6/android-sms-gateway" target="_blank" rel="noopener" class="sms-link">
                    <svg class="link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                    Android SMS Gateway Docs
                    <svg class="ext-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
                <a href="https://github.com/EvolutionAPI/evolution-api" target="_blank" rel="noopener" class="wa-link">
                    <svg class="link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                    </svg>
                    Evolution API Docs
                    <svg class="ext-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
                <a href="https://openrouter.ai/docs" target="_blank" rel="noopener" class="ai-link">
                    <svg class="link-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
                    </svg>
                    OpenRouter AI Docs
                    <svg class="ext-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                </a>
            </div>
        </div>

    </div>
</x-filament-panels::page>
