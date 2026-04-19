<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Console\Command;

/**
 * Send billing notifications for unpaid invoices.
 *
 * Sends reminders via WhatsApp and database notification
 * for invoices that are draft/sent/overdue.
 */
class SendBillingNotifications extends Command
{
    protected $signature = 'billing:send-notifications
                            {--type=reminder : Notification type: reminder|overdue|receipt}
                            {--days-overdue=7 : Days overdue threshold for overdue notices}';

    protected $description = 'Send billing notifications (reminders, overdue warnings, receipts) to tenants';

    public function handle(NotificationService $notificationService): int
    {
        $type = $this->option('type');
        $daysOverdue = (int) $this->option('days-overdue');

        $this->info("Sending billing notifications: {$type}");

        return match ($type) {
            'reminder' => $this->sendReminders($notificationService),
            'overdue'  => $this->sendOverdueNotices($notificationService, $daysOverdue),
            'receipt'  => $this->sendReceipts($notificationService),
            default    => $this->handleUnknownType($type),
        };
    }

    /**
     * Send reminders for draft/sent invoices.
     */
    protected function sendReminders(NotificationService $notificationService): int
    {
        $invoices = Invoice::with('tenant')
            ->whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Sent])
            ->whereNull('sent_via_whatsapp_at')
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No pending invoices to send reminders for.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->tenant;

            if (! $tenant) {
                continue;
            }

            try {
                // Mark as sent
                $invoice->update([
                    'status' => InvoiceStatus::Sent,
                    'sent_via_whatsapp_at' => now(),
                ]);

                // Send notification using template
                $notificationService->sendRaw(
                    notifiable: $tenant,
                    subject: "Invoice {$invoice->invoice_number} — PKR " . number_format($invoice->total_paisas / 100, 2),
                    body: $this->buildReminderMessage($invoice),
                    channels: ['database', 'whatsapp'],
                );

                $this->line("  📩 {$tenant->school_name} — {$invoice->invoice_number}");
                $sent++;

            } catch (\Throwable $e) {
                $this->error("  ❌ {$tenant->school_name} — {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} reminders.");
        return self::SUCCESS;
    }

    /**
     * Send overdue notices for invoices past due.
     */
    protected function sendOverdueNotices(NotificationService $notificationService, int $daysOverdue): int
    {
        $cutoff = now()->subDays($daysOverdue);

        $invoices = Invoice::with('tenant')
            ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
            ->where('period_end', '<', $cutoff)
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->tenant;

            if (! $tenant) {
                continue;
            }

            try {
                // Mark as overdue
                $invoice->update(['status' => InvoiceStatus::Overdue]);

                $notificationService->sendRaw(
                    notifiable: $tenant,
                    subject: "⚠️ OVERDUE: Invoice {$invoice->invoice_number}",
                    body: $this->buildOverdueMessage($invoice),
                    channels: ['database', 'whatsapp'],
                );

                $this->line("  ⚠️ {$tenant->school_name} — {$invoice->invoice_number} (overdue)");
                $sent++;

            } catch (\Throwable $e) {
                $this->error("  ❌ {$tenant->school_name} — {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} overdue notices.");
        return self::SUCCESS;
    }

    /**
     * Send payment receipts for recently paid invoices.
     */
    protected function sendReceipts(NotificationService $notificationService): int
    {
        $invoices = Invoice::with('tenant')
            ->where('status', InvoiceStatus::Paid)
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDay())
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No recent payments to send receipts for.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->tenant;

            if (! $tenant) {
                continue;
            }

            try {
                $notificationService->sendRaw(
                    notifiable: $tenant,
                    subject: "✅ Payment Received — {$invoice->invoice_number}",
                    body: $this->buildReceiptMessage($invoice),
                    channels: ['database', 'whatsapp'],
                );

                $this->line("  ✅ {$tenant->school_name} — receipt sent");
                $sent++;

            } catch (\Throwable $e) {
                $this->error("  ❌ {$tenant->school_name} — {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} receipts.");
        return self::SUCCESS;
    }

    // ── Message Builders ───────────────────────────────────────────

    protected function buildReminderMessage(Invoice $invoice): string
    {
        $total = number_format($invoice->total_paisas / 100, 2);
        $period = $invoice->period_start->format('M Y');

        return "Assalam-o-Alaikum,\n\n"
            . "Your KynexEdu invoice for {$period} is ready.\n\n"
            . "Invoice: {$invoice->invoice_number}\n"
            . "Amount: PKR {$total}\n"
            . "Students: {$invoice->active_student_count}\n"
            . "Period: {$invoice->period_start->format('d M')} – {$invoice->period_end->format('d M Y')}\n\n"
            . "Please make payment at your earliest convenience.\n"
            . "JazakAllah Khair — KynexEdu Team";
    }

    protected function buildOverdueMessage(Invoice $invoice): string
    {
        $total = number_format($invoice->total_paisas / 100, 2);
        $daysLate = $invoice->period_end->diffInDays(now());

        return "⚠️ OVERDUE NOTICE\n\n"
            . "Your invoice {$invoice->invoice_number} of PKR {$total} is {$daysLate} days overdue.\n\n"
            . "Please clear the payment immediately to avoid service interruption.\n\n"
            . "KynexEdu Team";
    }

    protected function buildReceiptMessage(Invoice $invoice): string
    {
        $total = number_format($invoice->total_paisas / 100, 2);

        return "✅ Payment Received!\n\n"
            . "Invoice: {$invoice->invoice_number}\n"
            . "Amount: PKR {$total}\n"
            . "Paid on: {$invoice->paid_at->format('d M Y')}\n\n"
            . "Thank you for your timely payment.\n"
            . "KynexEdu Team";
    }

    protected function handleUnknownType(string $type): int
    {
        $this->error("Unknown notification type: {$type}");
        return self::FAILURE;
    }
}
