<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationTemplate extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'event_trigger',
        'channels',
        'send_to',
        'sms_template',
        'whatsapp_template',
        'email_subject',
        'email_body',
        'is_active',
        'variables',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'send_to' => 'array',
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Render template body for a given channel with variable replacements.
     */
    public function renderForChannel(string $channel, array $data = []): ?string
    {
        $template = match ($channel) {
            'sms' => $this->sms_template,
            'whatsapp' => $this->whatsapp_template,
            'email' => $this->email_body,
            'in_app' => $this->sms_template ?? $this->email_body,
            default => null,
        };

        if (! $template) {
            return null;
        }

        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", (string) $value, $template);
        }

        return $template;
    }

    /**
     * Render the email subject with variable replacements.
     */
    public function renderSubject(array $data = []): ?string
    {
        if (! $this->email_subject) {
            return null;
        }

        $subject = $this->email_subject;

        foreach ($data as $key => $value) {
            $subject = str_replace("{{$key}}", (string) $value, $subject);
        }

        return $subject;
    }

    /**
     * Legacy render method for backward compatibility.
     */
    public function render(array $data = []): string
    {
        return $this->renderForChannel('sms', $data) ?? '';
    }
}
