<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\ModuleToggle;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Chat Settings — per-school chat preferences. Stores the on/off flag in
 * the `chat` ModuleToggle row and extra preferences in its JSON-encoded
 * description (no extra table needed; complements the Module Manager).
 */
class ChatSettings extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'use_chat';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Chat Settings';

    protected static ?int $navigationSort = 24;

    protected static ?string $title = 'Chat Settings';

    protected string $view = 'filament.school-admin.pages.chat-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $row   = $this->toggle();
        $prefs = $this->prefs($row);

        $this->form->fill([
            'enabled'              => (bool) $row->enabled,
            'invitation_required'  => (bool) ($prefs['invitation_required'] ?? false),
            'staff_only'           => (bool) ($prefs['staff_only'] ?? false),
            'allow_attachments'    => (bool) ($prefs['allow_attachments'] ?? true),
            'ai_smart_reply'       => (bool) ($prefs['ai_smart_reply'] ?? true),
            'retention'            => (string) ($prefs['retention'] ?? 'forever'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Availability')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable chat')
                            ->helperText('Turn the user-to-user chat module on or off for this school.'),
                        Toggle::make('staff_only')
                            ->label('Staff only')
                            ->helperText('Restrict chat to staff/teachers (exclude parents/students).'),
                        Toggle::make('invitation_required')
                            ->label('Require invitation before chatting')
                            ->helperText('Users must accept a chat invitation before a conversation can start.'),
                    ])->columns(1),

                Section::make('Messages')
                    ->schema([
                        Toggle::make('allow_attachments')->label('Allow file attachments')->default(true),
                        Toggle::make('ai_smart_reply')
                            ->label('AI smart-reply suggestions')
                            ->helperText('Show the ✨ suggest-reply button (only works while AI is enabled and in budget).')
                            ->default(true),
                        Select::make('retention')
                            ->label('Message retention')
                            ->options([
                                'forever' => 'Keep forever',
                                '365'     => '1 year',
                                '180'     => '6 months',
                                '90'      => '90 days',
                                '30'      => '30 days',
                            ])
                            ->default('forever')
                            ->native(false),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $row   = $this->toggle();

        $row->enabled = (bool) ($state['enabled'] ?? false);
        $row->description = json_encode([
            'invitation_required' => (bool) ($state['invitation_required'] ?? false),
            'staff_only'          => (bool) ($state['staff_only'] ?? false),
            'allow_attachments'   => (bool) ($state['allow_attachments'] ?? true),
            'ai_smart_reply'      => (bool) ($state['ai_smart_reply'] ?? true),
            'retention'           => (string) ($state['retention'] ?? 'forever'),
        ]);
        $row->save();

        Notification::make()->title('Chat settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label('Save')->icon('heroicon-o-check')->color('success')->action('save'),
        ];
    }

    protected function toggle(): ModuleToggle
    {
        return ModuleToggle::query()->firstOrCreate(
            ['module_key' => 'chat'],
            ['label' => ModuleToggle::KNOWN_MODULES['chat'] ?? 'Chat', 'enabled' => true]
        );
    }

    /** @return array<string,mixed> */
    protected function prefs(ModuleToggle $row): array
    {
        $decoded = json_decode((string) $row->description, true);

        return is_array($decoded) ? $decoded : [];
    }
}
