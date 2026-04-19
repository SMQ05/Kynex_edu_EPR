<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Approval Action Handlers
    |--------------------------------------------------------------------------
    |
    | Map each action_type string to an invokable handler class.
    | The ExecuteApprovedAction job will resolve and call these handlers
    | when an approval request is approved.
    |
    */
    'handlers' => [
        'fee_refund'             => \App\Actions\Approvals\HandleFeeRefund::class,
        'student_status_change'  => \App\Actions\Approvals\HandleStudentStatusChange::class,
        'staff_status_change'    => \App\Actions\Approvals\HandleStaffStatusChange::class,
        'role_revocation'        => \App\Actions\Approvals\HandleRoleRevocation::class,
        'expense_approval'       => \App\Actions\Approvals\HandleExpenseApproval::class,
        'student_delete'         => \App\Actions\Approvals\HandleStudentDelete::class,
        'staff_delete'           => \App\Actions\Approvals\HandleStaffDelete::class,
        'exam_mark_change'       => \App\Actions\Approvals\HandleExamMarkChange::class,
        'assignment_change'      => \App\Actions\Approvals\HandleAssignmentChange::class,
        'sensitive_role_assignment' => \App\Actions\Approvals\HandleSensitiveRoleAssignment::class,
        'bulk_fee_waiver'        => \App\Actions\Approvals\HandleBulkFeeWaiver::class,
    ],

];
