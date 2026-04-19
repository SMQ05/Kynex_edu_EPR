<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Models\Tenant\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * PART 7 — Notification Templates Seeder
 *
 * Seeds the default notification templates used by jobs:
 *   - NotifyHomeworkCreated    → homework.assigned
 *   - OverdueHomeworkReminder  → homework.overdue
 *   - NotifyHomeworkGraded     → homework.graded
 *   - NotifyLateArrival        → student.late
 *   - NotifyAbsentStudents...  → student.absent
 *
 * Variables use {variable_name} syntax (matched in NotificationTemplate::renderForChannel).
 *
 * Run inside a tenant context:
 *   tenancy()->initialize($tenant);
 *   (new NotificationTemplatesSeeder())->run();
 */
class NotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // ── Homework Assigned ───────────────────────────────────────
            [
                'name'               => 'Homework Assigned',
                'slug'               => 'homework.assigned',
                'event_trigger'      => 'homework.assigned',
                'channels'           => ['sms', 'whatsapp', 'in_app'],
                'send_to'            => ['parent', 'student'],
                'sms_template'       => 'Dear Parent, {student_name} has been assigned new homework in {subject_name}: "{title}". Due: {due_date}. — {school_name}',
                'whatsapp_template'  => "📚 *New Homework Assigned*\n\nDear Parent,\n*{student_name}* has been assigned new homework:\n\n*Subject:* {subject_name}\n*Title:* {title}\n*Due Date:* {due_date}\n\n_{school_name}_",
                'email_subject'      => 'New Homework: {title} — {subject_name}',
                'email_body'         => "<p>Dear Parent,</p><p><strong>{student_name}</strong> has been assigned new homework in <strong>{subject_name}</strong>.</p><p><strong>Title:</strong> {title}<br><strong>Description:</strong> {description}<br><strong>Due Date:</strong> {due_date}</p><p>Regards,<br>{school_name}</p>",
                'is_active'          => true,
                'variables'          => ['student_name', 'subject_name', 'title', 'description', 'due_date', 'school_name'],
            ],

            // ── Homework Overdue ────────────────────────────────────────
            [
                'name'               => 'Homework Overdue',
                'slug'               => 'homework.overdue',
                'event_trigger'      => 'homework.overdue',
                'channels'           => ['sms', 'whatsapp', 'in_app'],
                'send_to'            => ['parent', 'student'],
                'sms_template'       => 'REMINDER: {student_name}\'s homework "{title}" ({subject_name}) was due on {due_date} and has NOT been submitted. Please ensure completion. — {school_name}',
                'whatsapp_template'  => "⚠️ *Homework Overdue Reminder*\n\nDear Parent,\n*{student_name}*'s homework is overdue:\n\n*Subject:* {subject_name}\n*Title:* {title}\n*Due Date:* {due_date}\n\nPlease ensure the homework is submitted promptly.\n\n_{school_name}_",
                'email_subject'      => 'Overdue Homework Reminder: {title} — {subject_name}',
                'email_body'         => "<p>Dear Parent,</p><p>This is a reminder that <strong>{student_name}</strong>'s homework in <strong>{subject_name}</strong> is overdue.</p><p><strong>Title:</strong> {title}<br><strong>Due Date:</strong> {due_date}</p><p>Please ensure the assignment is submitted as soon as possible.</p><p>Regards,<br>{school_name}</p>",
                'is_active'          => true,
                'variables'          => ['student_name', 'subject_name', 'title', 'due_date', 'school_name'],
            ],

            // ── Homework Graded ─────────────────────────────────────────
            [
                'name'               => 'Homework Graded',
                'slug'               => 'homework.graded',
                'event_trigger'      => 'homework.graded',
                'channels'           => ['sms', 'whatsapp', 'in_app'],
                'send_to'            => ['parent', 'student'],
                'sms_template'       => 'Dear Parent, {student_name}\'s homework "{title}" ({subject_name}) has been graded: {marks_obtained}/{total_marks}. — {school_name}',
                'whatsapp_template'  => "✅ *Homework Graded*\n\nDear Parent,\n*{student_name}*'s homework has been reviewed by the teacher:\n\n*Subject:* {subject_name}\n*Title:* {title}\n*Score:* {marks_obtained} / {total_marks}\n\n_{school_name}_",
                'email_subject'      => 'Homework Graded: {title} — {marks_obtained}/{total_marks}',
                'email_body'         => "<p>Dear Parent,</p><p><strong>{student_name}</strong>'s homework in <strong>{subject_name}</strong> has been graded.</p><p><strong>Title:</strong> {title}<br><strong>Score:</strong> {marks_obtained} / {total_marks}<br><strong>Teacher Remarks:</strong> {remarks}</p><p>Regards,<br>{school_name}</p>",
                'is_active'          => true,
                'variables'          => ['student_name', 'subject_name', 'title', 'marks_obtained', 'total_marks', 'remarks', 'school_name'],
            ],

            // ── Student Late Arrival ────────────────────────────────────
            [
                'name'               => 'Student Late Arrival',
                'slug'               => 'student.late',
                'event_trigger'      => 'student.late_arrival',
                'channels'           => ['sms', 'whatsapp', 'in_app'],
                'send_to'            => ['parent'],
                'sms_template'       => 'Dear Parent, {student_name} arrived late to school today at {arrival_time}. Please ensure punctuality. — {school_name}',
                'whatsapp_template'  => "🕐 *Late Arrival Notice*\n\nDear Parent,\n*{student_name}* arrived at school late today.\n\n*Arrival Time:* {arrival_time}\n*Date:* {date}\n\nPlease ensure timely attendance.\n\n_{school_name}_",
                'email_subject'      => 'Late Arrival Notice — {student_name}',
                'email_body'         => "<p>Dear Parent,</p><p>We wish to inform you that <strong>{student_name}</strong> arrived at school late today.</p><p><strong>Arrival Time:</strong> {arrival_time}<br><strong>Date:</strong> {date}</p><p>Regular punctuality is important for your child's academic progress. Kindly ensure timely attendance.</p><p>Regards,<br>{school_name}</p>",
                'is_active'          => true,
                'variables'          => ['student_name', 'arrival_time', 'date', 'school_name'],
            ],

            // ── Student Absent ──────────────────────────────────────────
            [
                'name'               => 'Student Absent',
                'slug'               => 'student.absent',
                'event_trigger'      => 'student.absent',
                'channels'           => ['sms', 'whatsapp', 'in_app'],
                'send_to'            => ['parent'],
                'sms_template'       => 'Dear Parent, {student_name} was marked ABSENT on {date}. If this is an error, please contact the school. — {school_name}',
                'whatsapp_template'  => "📋 *Absence Notice*\n\nDear Parent,\n*{student_name}* was marked *absent* today.\n\n*Date:* {date}\n*Class:* {class_name}\n\nIf your child is unwell, please inform the school.\n\n_{school_name}_",
                'email_subject'      => 'Absence Notice — {student_name} on {date}',
                'email_body'         => "<p>Dear Parent,</p><p>We wish to inform you that <strong>{student_name}</strong> was marked <strong>absent</strong> today.</p><p><strong>Date:</strong> {date}<br><strong>Class:</strong> {class_name}</p><p>If your child is unwell or the absence was pre-approved, please inform the school office.</p><p>Regards,<br>{school_name}</p>",
                'is_active'          => true,
                'variables'          => ['student_name', 'date', 'class_name', 'school_name'],
            ],

            // ── Fee Reminder ────────────────────────────────────────────
            [
                'name'               => 'Fee Due Reminder',
                'slug'               => 'fee.reminder',
                'event_trigger'      => 'fee.due_reminder',
                'channels'           => ['sms', 'whatsapp', 'email'],
                'send_to'            => ['parent'],
                'sms_template'       => 'Dear Parent, fee of Rs {amount} for {student_name} ({fee_type}) is due on {due_date}. Please pay to avoid a late fine. — {school_name}',
                'whatsapp_template'  => "💳 *Fee Reminder*\n\nDear Parent,\n\nThis is a reminder that the following fee is due:\n\n*Student:* {student_name}\n*Fee Type:* {fee_type}\n*Amount:* Rs {amount}\n*Due Date:* {due_date}\n\nPlease pay at your earliest convenience.\n\n_{school_name}_",
                'email_subject'      => 'Fee Reminder: Rs {amount} due on {due_date}',
                'email_body'         => "<p>Dear Parent,</p><p>This is a reminder that the following fee payment is due:</p><ul><li><strong>Student:</strong> {student_name}</li><li><strong>Fee Type:</strong> {fee_type}</li><li><strong>Amount:</strong> Rs {amount}</li><li><strong>Due Date:</strong> {due_date}</li></ul><p>Please make the payment on time to avoid any late fee charges.</p><p>Regards,<br>{school_name}</p>",
                'is_active'          => true,
                'variables'          => ['student_name', 'fee_type', 'amount', 'due_date', 'school_name'],
            ],

        ];

        foreach ($templates as $data) {
            NotificationTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
