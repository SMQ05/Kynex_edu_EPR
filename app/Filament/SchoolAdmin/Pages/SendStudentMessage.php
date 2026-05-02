<?php

declare(strict_types=1);

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use App\Models\Tenant\ClassSubject;
use App\Models\Tenant\CommunicationLog;
use App\Models\Tenant\InAppNotification;
use App\Models\Tenant\Student;
use App\Models\Tenant\StudentGuardian;
use App\Services\Sms\SmsServiceFactory;
use App\Services\WhatsApp\WhatsAppServiceFactory;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

/**
 * SendStudentMessage — Lets a teacher pick a student in one of their
 * classes and message that student's primary guardian(s) via in-app,
 * email, SMS, or WhatsApp. Each successful send is logged to
 * communication_logs for audit.
 */
class SendStudentMessage extends Page implements HasForms
{
    use HasPermissionCheck;
    use InteractsWithForms;

    protected static string $rbacPermission = 'communication.view';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'Message Parents';

    protected static string | \UnitEnum | null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.school-admin.pages.send-student-message';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'channels' => ['in_app'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Student')
                    ->options(fn () => $this->studentOptions())
                    ->searchable()
                    ->preload()
                    ->required(),

                CheckboxList::make('channels')
                    ->label('Channels')
                    ->options([
                        'in_app'   => 'In-app notification',
                        'email'    => 'Email',
                        'sms'      => 'SMS',
                        'whatsapp' => 'WhatsApp',
                    ])
                    ->columns(4)
                    ->required(),

                TextInput::make('subject')
                    ->label('Subject (used for email and in-app title)')
                    ->maxLength(255),

                Textarea::make('body')
                    ->label('Message')
                    ->rows(5)
                    ->required()
                    ->maxLength(2000),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        /** @var Student|null $student */
        $student = Student::with(['guardians' => fn ($q) => $q->orderByDesc('is_primary_contact')])
            ->find($data['student_id']);

        if (! $student) {
            Notification::make()->title('Student not found')->danger()->send();
            return;
        }

        $guardians = $student->guardians;
        if ($guardians->isEmpty()) {
            Notification::make()
                ->title('No guardians on record')
                ->body('Add a guardian to this student before sending a message.')
                ->warning()
                ->send();
            return;
        }

        $tenant   = function_exists('tenant') ? tenant() : null;
        $sender   = auth()->guard('school_users')->user();
        $sent     = ['in_app' => 0, 'email' => 0, 'sms' => 0, 'whatsapp' => 0];
        $failures = [];

        $channels = $data['channels'] ?? [];
        $subject  = $data['subject'] ?: 'Message from ' . ($sender?->name ?? 'school');
        $body     = $data['body'];

        foreach ($guardians as $g) {
            // In-app notification — addressed to the student record so the
            // parent portal picks it up.
            if (in_array('in_app', $channels, true)) {
                InAppNotification::create([
                    'user_id' => $student->school_user_id,
                    'title'   => $subject,
                    'body'    => $body,
                    'type'    => 'info',
                ]);
                $sent['in_app']++;
            }

            if (in_array('email', $channels, true) && $g->email) {
                try {
                    Resend::emails()->send([
                        'from'    => 'KynexEdu <noreply@kynexsolutions.com>',
                        'to'      => $g->email,
                        'subject' => $subject,
                        'html'    => nl2br(e($body)),
                    ]);
                    $sent['email']++;
                    $this->logComm($student, $g, 'email', $g->email, $subject, $body, 'sent');
                } catch (\Throwable $e) {
                    $failures[] = "email→{$g->email}: " . $e->getMessage();
                    $this->logComm($student, $g, 'email', $g->email, $subject, $body, 'failed', $e->getMessage());
                }
            }

            if (in_array('sms', $channels, true) && $g->phone && $tenant) {
                try {
                    SmsServiceFactory::make($tenant)->send($g->phone, $body);
                    $sent['sms']++;
                    $this->logComm($student, $g, 'sms', $g->phone, $subject, $body, 'sent');
                } catch (\Throwable $e) {
                    $failures[] = "sms→{$g->phone}: " . $e->getMessage();
                    $this->logComm($student, $g, 'sms', $g->phone, $subject, $body, 'failed', $e->getMessage());
                }
            }

            if (in_array('whatsapp', $channels, true) && ($g->whatsapp ?? $g->phone) && $tenant) {
                $to = $g->whatsapp ?: $g->phone;
                try {
                    WhatsAppServiceFactory::make($tenant)->sendText($to, $body);
                    $sent['whatsapp']++;
                    $this->logComm($student, $g, 'whatsapp', $to, $subject, $body, 'sent');
                } catch (\Throwable $e) {
                    $failures[] = "whatsapp→{$to}: " . $e->getMessage();
                    $this->logComm($student, $g, 'whatsapp', $to, $subject, $body, 'failed', $e->getMessage());
                }
            }
        }

        $summary = collect($sent)
            ->filter(fn ($n) => $n > 0)
            ->map(fn ($n, $c) => "{$c}: {$n}")
            ->join(', ');

        Notification::make()
            ->title($summary === '' ? 'No messages sent' : 'Messages dispatched')
            ->body($summary . (count($failures) ? ' — failures: ' . implode('; ', $failures) : ''))
            ->color(count($failures) > 0 ? 'warning' : 'success')
            ->send();

        $this->form->fill([
            'student_id' => null,
            'channels'   => ['in_app'],
            'subject'    => null,
            'body'       => null,
        ]);
    }

    /**
     * Student dropdown — scoped to the teacher's class_subjects rows when
     * acting as TEACHER.
     */
    private function studentOptions(): array
    {
        $user = auth()->guard('school_users')->user();
        if (! $user) {
            return [];
        }

        $active = $user->active_role ?? $user->roles->first()?->name;

        $query = Student::query()->orderBy('first_name');

        if ($active === 'TEACHER') {
            $tuples = ClassSubject::query()
                ->where('teacher_id', $user->id)
                ->get(['class_id', 'section_id']);

            $query->where(function ($q) use ($tuples) {
                foreach ($tuples as $t) {
                    $q->orWhere(function ($qq) use ($t) {
                        $qq->where('class_id', $t->class_id);
                        if ($t->section_id) {
                            $qq->where('section_id', $t->section_id);
                        }
                    });
                }
            });
        }

        return $query
            ->with(['schoolClass', 'section'])
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (Student $s) => [
                $s->id => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) .
                    ' — ' . ($s->schoolClass?->name ?? '?') .
                    ($s->section?->name ? ' / ' . $s->section->name : '') .
                    ' [' . ($s->admission_number ?? $s->roll_number ?? '—') . ']',
            ])
            ->all();
    }

    private function logComm(
        Student $student,
        StudentGuardian $guardian,
        string $channel,
        string $address,
        string $subject,
        string $body,
        string $status,
        ?string $error = null,
    ): void {
        try {
            CommunicationLog::create([
                'channel'         => $channel,
                'recipient_phone' => in_array($channel, ['sms', 'whatsapp'], true) ? $address : null,
                'recipient_email' => $channel === 'email' ? $address : null,
                'message_preview' => mb_substr($body, 0, 160),
                'status'          => $status,
                'sent_at'         => now(),
                'failed_reason'   => $error,
                'feature_context' => 'student_message:' . $student->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to write communication_log row', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
