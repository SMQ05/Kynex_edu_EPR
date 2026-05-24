<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\SchoolUser;
use App\Models\Tenant\ChatBlockedUser;
use App\Models\Tenant\Conversation;
use App\Models\Tenant\ConversationParticipant;
use App\Models\Tenant\Message;
use App\Services\Ai\AiAvailability;
use App\Services\Ai\AiDraftService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Chat Box — user-to-user messaging UI (distinct from the AI assistant
 * and WhatsApp inbox). Lists the current user's conversations and the
 * selected thread's messages; lets them send a reply. AI smart-reply
 * suggestion is gated by AiAvailability.
 */
class ChatBox extends Page
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'use_chat';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Chat Box';

    protected static ?int $navigationSort = 21;

    protected static ?string $title = 'Chat';

    protected string $view = 'filament.school-admin.pages.chat-box';

    public ?string $activeConversationId = null;

    public string $draft = '';

    public function mount(): void
    {
        $first = $this->conversations()->first();
        $this->activeConversationId = $first?->id;
    }

    public function aiEnabled(): bool
    {
        return AiAvailability::enabled();
    }

    protected function currentUserId(): ?string
    {
        return auth()->guard('school_users')->id();
    }

    /** Conversations the current user participates in, most-recent first. */
    public function conversations(): Collection
    {
        $userId = $this->currentUserId();
        if (! $userId) {
            return collect();
        }

        return Conversation::query()
            ->forUser($userId)
            ->with(['members'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getActiveConversation(): ?Conversation
    {
        if (! $this->activeConversationId) {
            return null;
        }

        return Conversation::query()
            ->forUser((string) $this->currentUserId())
            ->with('members')
            ->find($this->activeConversationId);
    }

    /** Messages in the active conversation, oldest first. */
    public function messages(): Collection
    {
        if (! $this->activeConversationId) {
            return collect();
        }

        return Message::query()
            ->where('conversation_id', $this->activeConversationId)
            ->with('sender')
            ->orderBy('created_at')
            ->limit(200)
            ->get();
    }

    /** Display title for a conversation (group title, or the other member's name for 1:1). */
    public function conversationTitle(Conversation $conversation): string
    {
        if ($conversation->title) {
            return $conversation->title;
        }

        $userId = $this->currentUserId();
        $other  = $conversation->members->firstWhere('id', '!=', $userId);

        return $other?->name ?? 'Conversation';
    }

    public function selectConversation(string $conversationId): void
    {
        $this->activeConversationId = $conversationId;
        $this->draft = '';
        $this->markRead();
    }

    public function sendMessage(): void
    {
        $body   = trim($this->draft);
        $userId = $this->currentUserId();

        if ($body === '' || ! $this->activeConversationId || ! $userId) {
            return;
        }

        // Respect block list: don't deliver into a 1:1 with a blocked partner.
        if ($this->isBlockedInActiveConversation()) {
            Notification::make()->title('You cannot message this user')->danger()->send();

            return;
        }

        $message = Message::create([
            'conversation_id' => $this->activeConversationId,
            'sender_id'       => $userId,
            'body'            => $body,
        ]);

        Conversation::whereKey($this->activeConversationId)->update([
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
        ]);

        $this->draft = '';
        $this->markRead();
    }

    /** Mark the active conversation read for the current user. */
    protected function markRead(): void
    {
        $userId = $this->currentUserId();
        if (! $userId || ! $this->activeConversationId) {
            return;
        }

        ConversationParticipant::query()
            ->where('conversation_id', $this->activeConversationId)
            ->where('school_user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    /** True if this is a 1:1 with someone the current user has blocked (or who blocked them). */
    protected function isBlockedInActiveConversation(): bool
    {
        $conversation = $this->getActiveConversation();
        if (! $conversation || $conversation->type !== 'direct') {
            return false;
        }

        $userId = $this->currentUserId();
        $other  = $conversation->members->firstWhere('id', '!=', $userId);
        if (! $other) {
            return false;
        }

        return ChatBlockedUser::query()
            ->where(function ($q) use ($userId, $other) {
                $q->where('blocker_id', $userId)->where('blocked_id', $other->id);
            })
            ->orWhere(function ($q) use ($userId, $other) {
                $q->where('blocker_id', $other->id)->where('blocked_id', $userId);
            })
            ->exists();
    }

    /** AI smart-reply: suggest a reply based on the last inbound message. */
    public function suggestReply(): void
    {
        if (! AiAvailability::enabled()) {
            Notification::make()->title(AiAvailability::reason() ?? 'AI is unavailable')->warning()->send();

            return;
        }

        $lastInbound = $this->messages()
            ->reverse()
            ->firstWhere('sender_id', '!=', $this->currentUserId());

        if (! $lastInbound) {
            Notification::make()->title('Nothing to reply to yet')->warning()->send();

            return;
        }

        try {
            $text = app(AiDraftService::class)->draft(
                instruction: 'a brief, friendly reply to this chat message from a colleague',
                context: ['Message received' => (string) $lastInbound->body],
                feature: 'chat_smart_reply',
                options: ['tone' => 'friendly and casual', 'length' => 'very short', 'channel' => 'whatsapp'],
            );

            $this->draft = $text;

            Notification::make()->title('Suggested reply inserted')->body('Edit before sending.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('AI suggestion failed')->body($e->getMessage())->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newChat')
                ->label('New chat')
                ->icon('heroicon-o-plus')
                ->form([
                    Select::make('with')
                        ->label('Start a chat with')
                        ->options(fn (): array => $this->startableUsers())
                        ->searchable()
                        ->required(),
                ])
                ->action(fn (array $data) => $this->startConversation($data['with'])),
        ];
    }

    /** School users the current user can start a chat with (excludes self + blocked). */
    protected function startableUsers(): array
    {
        $userId = $this->currentUserId();

        $blockedIds = ChatBlockedUser::query()
            ->where('blocker_id', $userId)->pluck('blocked_id')
            ->merge(ChatBlockedUser::query()->where('blocked_id', $userId)->pluck('blocker_id'))
            ->all();

        return SchoolUser::query()
            ->whereKeyNot($userId)
            ->when($blockedIds !== [], fn ($q) => $q->whereKeyNot($blockedIds))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** Find-or-create a 1:1 conversation with the given user and select it. */
    public function startConversation(string $otherUserId): void
    {
        $userId = $this->currentUserId();
        if (! $userId || $otherUserId === $userId) {
            return;
        }

        // Find an existing direct conversation that includes exactly these two.
        $existing = Conversation::query()
            ->where('type', 'direct')
            ->forUser($userId)
            ->forUser($otherUserId)
            ->first();

        if ($existing) {
            $this->selectConversation($existing->id);

            return;
        }

        $conversation = Conversation::create([
            'type'       => 'direct',
            'created_by' => $userId,
        ]);

        foreach ([$userId, $otherUserId] as $participantId) {
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'school_user_id'  => $participantId,
                'role'            => $participantId === $userId ? 'owner' : 'member',
            ]);
        }

        $this->selectConversation($conversation->id);

        Notification::make()->title('Conversation started')->success()->send();
    }
}
