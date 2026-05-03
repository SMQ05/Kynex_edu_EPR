<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tenant\AiConversation;
use App\Models\Tenant\AiMessage;
use App\Services\Ai\AiAssistant;
use App\Services\Ai\RolePrompts;
use App\Services\Ai\RoleScopedFacts;
use Livewire\Component;

/**
 * Floating AI assistant — opens from a button in the bottom-right of
 * every Filament page. Uses the logged-in user's active_role to pick
 * the system prompt + suggested starters.
 *
 * Conversation history is persisted per user in the tenant DB so it
 * survives page reloads and shows up next time the user opens the
 * panel.
 */
class AiAssistantPanel extends Component
{
    public bool $open = false;
    public string $input = '';
    public ?string $conversationId = null;
    public bool $busy = false;
    public ?string $error = null;

    /** @var array<int, array{role:string, content:string, ts:string}> */
    public array $messages = [];

    /** @var array<int,string> */
    public array $starters = [];

    public string $activeRole = 'TEACHER';
    public string $schoolName = '';

    public function mount(): void
    {
        $user = auth('school_users')->user();
        if (! $user) {
            return;
        }

        $this->activeRole = $user->active_role ?? $user->roles->first()?->name ?? 'TEACHER';
        $this->schoolName = (string) (tenant()?->school_name ?? '');
        $this->starters   = RolePrompts::startersFor($this->activeRole);

        // Load the user's most recent conversation so they can pick up
        // where they left off.
        $latest = AiConversation::where('school_user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if ($latest) {
            $this->loadConversation($latest);
        }
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->error = null;
    }

    public function startNewConversation(): void
    {
        $this->conversationId = null;
        $this->messages = [];
        $this->error = null;
        $this->input = '';
    }

    public function useStarter(string $prompt): void
    {
        $this->input = $prompt;
        $this->ask();
    }

    public function ask(): void
    {
        $user = auth('school_users')->user();
        if (! $user) {
            $this->error = 'Please log in.';
            return;
        }

        $text = trim($this->input);
        if ($text === '') {
            return;
        }

        if (! tenancy()->initialized) {
            $this->error = 'Tenant context unavailable. Please refresh the page.';
            return;
        }

        $this->busy = true;
        $this->error = null;
        $this->input = '';

        try {
            // Reuse or open a conversation.
            if ($this->conversationId) {
                $convo = AiConversation::find($this->conversationId);
            } else {
                $convo = AiConversation::create([
                    'school_user_id'    => $user->id,
                    'role_when_created' => $this->activeRole,
                    'title'             => mb_substr($text, 0, 60),
                ]);
                $this->conversationId = $convo->id;
            }

            // Append the user's message to history.
            AiMessage::create([
                'conversation_id' => $convo->id,
                'role'            => 'user',
                'content'         => $text,
                'created_at'      => now(),
            ]);

            // Build the message list for the model — last 20 messages
            // are plenty for a focused assistant.
            $messages = $convo->messages()
                ->orderBy('created_at')
                ->limit(40)
                ->get()
                ->map(fn (AiMessage $m) => [
                    'role'    => $m->role,
                    'content' => $m->content,
                ])
                ->all();

            $systemPrompt = RolePrompts::systemPromptFor($this->activeRole, $this->schoolName);

            // Prepend a role-scoped live-data snapshot so the model can
            // answer questions like "how many students" with real numbers,
            // bounded by the user's authority. Failures here are caught
            // inside RoleScopedFacts so chat never breaks because of a
            // single aggregate query.
            $facts = (new RoleScopedFacts())->for($user, (string) $this->activeRole);
            $systemPrompt .= (new RoleScopedFacts())->asSystemBlock($facts);

            $reply = AiAssistant::forCurrentTenant()->chat(
                systemPrompt: $systemPrompt,
                messages: $messages,
                feature: 'role_assistant_' . strtolower($this->activeRole),
            );

            AiMessage::create([
                'conversation_id' => $convo->id,
                'role'            => 'assistant',
                'content'         => $reply,
                'created_at'      => now(),
            ]);

            $this->loadConversation($convo);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->busy = false;
        }
    }

    protected function loadConversation(AiConversation $convo): void
    {
        $this->conversationId = $convo->id;
        $this->messages = $convo->messages
            ->map(fn (AiMessage $m) => [
                'role'    => $m->role,
                'content' => $m->content,
                'ts'      => $m->created_at?->format('H:i') ?? '',
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.ai-assistant-panel');
    }
}
