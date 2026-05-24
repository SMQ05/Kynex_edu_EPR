<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\AiUsageLog;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * AiSettingsPage — school admin view of their AI usage and configuration.
 *
 * Policy (see INFIX_PORTING_ROADMAP.md → Locked AI policy):
 *  - SaaS admin enables AI on the platform key, capped by a monthly budget.
 *  - The school may bring its OWN provider key + model (they pay their
 *    provider; this self-enables AI for them).
 *  - The school may request a budget upgrade from the platform.
 */
class AiSettingsPage extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'manage_school_settings';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'AI Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 32;

    protected string $view = 'filament.school-admin.pages.ai-settings';

    // ── BYO-key form state ─────────────────────────────────────────
    public string $ai_provider = 'openrouter';
    public string $ai_model    = '';
    public string $ai_api_key  = '';

    // ── Read-only status (computed in mount) ───────────────────────
    public bool $statusEnabled    = false;
    public string $statusProvider = '';
    public string $statusModel    = '';
    public int $budgetPaisas      = 0;
    public int $usedPaisas        = 0;
    public int $callsThisMonth    = 0;
    public bool $usingOwnKey       = false;

    public function getTitle(): string|Htmlable
    {
        return 'AI Settings & Usage';
    }

    public function mount(): void
    {
        $tenant = tenant();
        if (! $tenant) {
            return;
        }

        $this->ai_provider = $tenant->ai_provider ?: 'openrouter';
        $this->ai_model    = (string) ($tenant->ai_model ?? '');
        $this->usingOwnKey = filled($tenant->ai_openrouter_api_key);

        $this->statusEnabled  = (bool) $tenant->ai_enabled;
        $this->statusProvider = $tenant->ai_provider ?: 'openrouter';
        $this->statusModel    = $tenant->ai_model ?: 'default';
        $this->budgetPaisas   = (int) ($tenant->ai_monthly_budget_paisas ?? 0);
        $this->usedPaisas     = (int) ($tenant->ai_used_this_month_paisas ?? 0);

        try {
            $this->callsThisMonth = AiUsageLog::on('central')
                ->where('tenant_id', $tenant->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();
        } catch (\Throwable) {
            $this->callsThisMonth = 0;
        }
    }

    // ── Derived display helpers (used by the blade) ────────────────

    public function budgetPkr(): string
    {
        return $this->budgetPaisas > 0 ? number_format($this->budgetPaisas / 100, 0) : 'Unlimited';
    }

    public function usedPkr(): string
    {
        return number_format($this->usedPaisas / 100, 2);
    }

    public function remainingPkr(): string
    {
        if ($this->budgetPaisas <= 0) {
            return 'Unlimited';
        }

        return number_format(max(0, $this->budgetPaisas - $this->usedPaisas) / 100, 2);
    }

    public function usedPercent(): int
    {
        if ($this->budgetPaisas <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->usedPaisas / $this->budgetPaisas) * 100));
    }

    // ── BYO-key form ───────────────────────────────────────────────

    public function settingsForm(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Use your own AI provider (optional)')
                ->description('Paste your own OpenRouter or Groq API key to run AI on your own account — your school is billed directly by the provider, with no platform budget limit. Leave blank to use the platform-provided AI (managed by KynexEdu, capped by your monthly budget).')
                ->schema([
                    Select::make('ai_provider')
                        ->label('Provider')
                        ->options([
                            'openrouter' => 'OpenRouter (access to GPT-4o, Gemini, Claude, Llama…)',
                            'groq'       => 'Groq (very fast, low cost — Llama models)',
                        ])
                        ->default('openrouter')
                        ->native(false),

                    TextInput::make('ai_model')
                        ->label('Model')
                        ->placeholder('e.g. openai/gpt-4o-mini  •  google/gemini-2.0-flash-001  •  llama-3.3-70b-versatile')
                        ->helperText('Leave blank to use a sensible default for the chosen provider.'),

                    TextInput::make('ai_api_key')
                        ->label('Your API key')
                        ->password()
                        ->revealable()
                        ->placeholder('sk-or-...  (OpenRouter)  or  gsk_...  (Groq)')
                        ->helperText('Stored encrypted. Saving a key turns AI on for your school immediately.'),
                ]),
        ])->statePath('');
    }

    protected function getForms(): array
    {
        return ['settingsForm'];
    }

    public function saveOwnKey(): void
    {
        $tenant = Tenant::find(tenant()?->id);
        if (! $tenant) {
            return;
        }

        $update = [
            'ai_provider' => $this->ai_provider ?: 'openrouter',
            'ai_model'    => $this->ai_model ?: null,
        ];

        $key = trim($this->ai_api_key);
        if ($key !== '') {
            $update['ai_openrouter_api_key'] = $key;
            $update['ai_enabled']            = true;
        }

        $tenant->update($update);

        Notification::make()
            ->title($key !== '' ? 'AI enabled with your own key' : 'AI provider/model updated')
            ->success()
            ->send();

        $this->ai_api_key = '';
        $this->mount();
    }

    // ── Request a budget upgrade from the platform ─────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('requestUpgrade')
                ->label('Request more AI budget')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('primary')
                ->form([
                    TextInput::make('requested_budget_pkr')
                        ->label('Requested monthly budget (PKR)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Textarea::make('reason')
                        ->label('Reason / how you plan to use it')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $tenant = tenant();
                    $msg = sprintf(
                        "AI budget upgrade request\nSchool: %s (tenant %s)\nCurrent cap: %s PKR\nRequested: %s PKR\nReason: %s",
                        $tenant?->school_name ?? 'unknown',
                        $tenant?->id ?? 'n/a',
                        $this->budgetPkr(),
                        number_format((float) ($data['requested_budget_pkr'] ?? 0)),
                        $data['reason'] ?? '—',
                    );

                    Log::info('AI budget upgrade requested', ['message' => $msg]);

                    $recipient = env('PLATFORM_SUPPORT_EMAIL') ?: config('mail.from.address');
                    if ($recipient) {
                        try {
                            Mail::raw($msg, fn ($m) => $m->to($recipient)->subject('AI budget upgrade request'));
                        } catch (\Throwable $e) {
                            Log::warning('AI upgrade request email failed', ['error' => $e->getMessage()]);
                        }
                    }

                    Notification::make()
                        ->title('Request sent')
                        ->body('Your platform admin will review your AI budget request.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
