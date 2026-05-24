<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Models\SchoolUser;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\Section;
use App\Services\NotificationService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Schemas\Components\Section as FormSection;

class NotificationComposer extends Page implements HasForms
{
    use HasPermissionCheck;

    protected static string $rbacPermission = 'send_notifications_all';

    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string | \UnitEnum | null $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Send Notification';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.school-admin.pages.notification-composer';

    public ?string $recipient_type = null;

    public ?string $class_id = null;

    public ?string $section_id = null;

    public ?string $user_id = null;

    public array $channels = [];

    public ?string $notificationTitle = null;

    public ?string $message = null;

    public ?string $schedule_at = null;

    // ── PART 2d: Resolve which channels the current tenant allows ────
    protected function allowedChannelOptions(): array
    {
        $tenant = tenant();

        // If no tenancy context, expose all channels
        if (! $tenant) {
            return [
                'sms'      => 'SMS',
                'whatsapp' => 'WhatsApp',
                'email'    => 'Email',
                'in_app'   => 'In-App',
            ];
        }

        $isTeacher            = auth('school_users')->user()?->hasRole('TEACHER');
        $teachersCanSend      = (bool) ($tenant->teachers_can_send_notifications ?? true);
        $teachersOwnWhatsApp  = (bool) ($tenant->teachers_can_use_own_whatsapp   ?? false);

        // Teachers who are not permitted get an empty list
        if ($isTeacher && ! $teachersCanSend) {
            return [];
        }

        $options = [];

        if ((bool) ($tenant->allow_sms ?? true)) {
            $options['sms'] = 'SMS';
        }

        if ((bool) ($tenant->allow_whatsapp ?? true)) {
            // Teachers can only use WhatsApp if they are allowed OR the school
            // permits teachers to use their own WhatsApp gateway
            if (! $isTeacher || $teachersOwnWhatsApp) {
                $options['whatsapp'] = 'WhatsApp';
            }
        }

        if ((bool) ($tenant->allow_email ?? true)) {
            $options['email'] = 'Email';
        }

        if ((bool) ($tenant->allow_app_notifications ?? true)) {
            $options['in_app'] = 'In-App';
        }

        return $options;
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            FormSection::make('Recipients')->schema([
                Select::make('recipient_type')
                    ->label('Send To')
                    ->options([
                        'all_students'    => 'All Students',
                        'all_parents'     => 'All Parents',
                        'all_teachers'    => 'All Teachers',
                        'all_staff'       => 'All Staff',
                        'specific_class'  => 'Specific Class',
                        'specific_section'=> 'Specific Section',
                        'individual'      => 'Individual User',
                    ])
                    ->required()
                    ->live(),

                Select::make('class_id')
                    ->label('Class')
                    ->options(fn () => SchoolClass::pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn ($get) => in_array($get('recipient_type'), ['specific_class', 'specific_section'])),

                Select::make('section_id')
                    ->label('Section')
                    ->options(fn ($get) => Section::where('class_id', $get('class_id'))->pluck('name', 'id'))
                    ->searchable()
                    ->visible(fn ($get) => $get('recipient_type') === 'specific_section'),

                Select::make('user_id')
                    ->label('User')
                    ->options(fn () => SchoolUser::all()->mapWithKeys(fn ($u) => [$u->id => $u->name . ' (' . $u->email . ')']))
                    ->searchable()
                    ->visible(fn ($get) => $get('recipient_type') === 'individual'),
            ]),

            FormSection::make('Message')->schema([
                CheckboxList::make('channels')
                    ->label('Channels')
                    ->options(fn () => $this->allowedChannelOptions())
                    ->helperText(fn () => empty($this->allowedChannelOptions())
                        ? 'You do not have permission to send notifications via any channel.'
                        : null
                    )
                    ->columns(4)
                    ->required(),

                TextInput::make('notificationTitle')
                    ->label('Title')
                    ->visible(fn ($get) => ! empty(array_intersect($get('channels') ?? [], ['in_app', 'email']))),

                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),

                DateTimePicker::make('schedule_at')
                    ->label('Schedule At')
                    ->helperText('Leave empty to send immediately')
                    ->nullable(),
            ]),
        ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $recipients = $this->resolveRecipients($data['recipient_type'], $data);
        $recipientCount = count($recipients);

        if ($recipientCount === 0) {
            Notification::make()
                ->title('No recipients found')
                ->warning()
                ->send();
            return;
        }

        $service = app(NotificationService::class);

        foreach ($data['channels'] as $channel) {
            foreach ($recipients as $recipient) {
                $service->sendRaw(
                    channel: $channel,
                    recipient: $recipient,
                    message: $data['message'],
                    subject: $data['notificationTitle'] ?? null,
                );
            }
        }

        Notification::make()
            ->title("Notification sent to {$recipientCount} recipient(s)")
            ->success()
            ->send();

        $this->form->fill();
    }

    protected function resolveRecipients(string $type, array $data): array
    {
        return match ($type) {
            'all_students' => SchoolUser::role('STUDENT')->pluck('id')->toArray(),
            'all_parents' => SchoolUser::role('PARENT')->pluck('id')->toArray(),
            'all_teachers' => SchoolUser::role('TEACHER')->pluck('id')->toArray(),
            'all_staff' => SchoolUser::whereHas('staffProfile')->pluck('id')->toArray(),
            'specific_class' => SchoolUser::role('STUDENT')
                ->whereHas('student', fn ($q) => $q->where('class_id', $data['class_id']))
                ->pluck('id')->toArray(),
            'specific_section' => SchoolUser::role('STUDENT')
                ->whereHas('student', fn ($q) => $q->where('section_id', $data['section_id']))
                ->pluck('id')->toArray(),
            'individual' => [$data['user_id']],
            default => [],
        };
    }
}
