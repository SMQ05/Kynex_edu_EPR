<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Canonicalise legacy enum values that were seeded/imported before the PHP
 * enum definitions were locked down.
 *
 * Approved mappings (2026-05-07):
 *
 *   exams.exam_type
 *     first_term  → quarterly   (seeder used "First Term" paired with "Mid Term";
 *                                mapping to quarterly preserves the distinction)
 *
 *   attendance_records.status
 *     leave       → excused     (absent with an approved reason)
 *
 *   fee_payments.payment_method
 *     cheque        → cheque    (now a first-class FeePaymentMethod case — no remap needed)
 *     easypaisa     → online
 *     jazzcash      → online
 *     bank_transfer → bank
 *
 *   expenses.payment_method
 *     easypaisa     → online
 *     jazzcash      → online
 *     bank_transfer → bank
 *
 * Reversible: down() restores the original values.
 */
return new class extends Migration
{
    public function up(): void
    {
        // exams
        DB::table('exams')
            ->where('exam_type', 'first_term')
            ->update(['exam_type' => 'quarterly']);

        // attendance_records
        DB::table('attendance_records')
            ->where('status', 'leave')
            ->update(['status' => 'excused']);

        // fee_payments — easypaisa / jazzcash → online, bank_transfer → bank
        // (cheque is now valid after FeePaymentMethod enum was extended — no remap)
        DB::table('fee_payments')
            ->whereIn('payment_method', ['easypaisa', 'jazzcash'])
            ->update(['payment_method' => 'online']);

        DB::table('fee_payments')
            ->where('payment_method', 'bank_transfer')
            ->update(['payment_method' => 'bank']);

        // expenses
        DB::table('expenses')
            ->whereIn('payment_method', ['easypaisa', 'jazzcash'])
            ->update(['payment_method' => 'online']);

        DB::table('expenses')
            ->where('payment_method', 'bank_transfer')
            ->update(['payment_method' => 'bank']);
    }

    public function down(): void
    {
        DB::table('exams')
            ->where('exam_type', 'quarterly')
            ->update(['exam_type' => 'first_term']);

        DB::table('attendance_records')
            ->where('status', 'excused')
            ->update(['status' => 'leave']);

        DB::table('fee_payments')
            ->where('payment_method', 'online')
            ->update(['payment_method' => 'easypaisa']);

        DB::table('fee_payments')
            ->where('payment_method', 'bank')
            ->update(['payment_method' => 'bank_transfer']);

        DB::table('expenses')
            ->where('payment_method', 'online')
            ->update(['payment_method' => 'easypaisa']);

        DB::table('expenses')
            ->where('payment_method', 'bank')
            ->update(['payment_method' => 'bank_transfer']);
    }
};
