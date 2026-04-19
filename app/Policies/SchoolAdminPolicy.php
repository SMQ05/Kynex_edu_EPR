<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolUser;

/**
 * SchoolAdminPolicy — Central logic for School Admin boundaries.
 * 
 * This prevents School Admins from managing academic tasks (homework/exams/marks)
 * and strictly confidential health records, while allowing Teachers or other
 * appropriate roles to do so.
 */
class SchoolAdminPolicy
{
    /**
     * Determine if user can manage homework.
     * School Admins CANNOT manage homework (Teacher responsibility).
     */
    public static function canManageHomework(SchoolUser $user): bool
    {
        return $user->hasPermissionTo('manage_homework');
    }

    /**
     * Determine if user can manage exams.
     * School Admins CANNOT schedule/manage exams (Exam Admin responsibility).
     */
    public static function canManageExams(SchoolUser $user): bool
    {
        return $user->hasPermissionTo('manage_exam_schedules');
    }

    /**
     * Determine if user can enter marks.
     * School Admins CANNOT enter/edit marks (Teacher / Exam Admin responsibility).
     */
    public static function canEnterMarks(SchoolUser $user): bool
    {
        return $user->hasPermissionTo('enter_marks');
    }

    /**
     * Determine if user can publish exam results.
     * School Admins CANNOT publish results.
     */
    public static function canPublishResults(SchoolUser $user): bool
    {
        return $user->hasPermissionTo('publish_results');
    }

    /**
     * Determine if user can manage online classes.
     * School Admins CANNOT manage online classes (Teacher responsibility).
     */
    public static function canManageOnlineClasses(SchoolUser $user): bool
    {
        return $user->hasPermissionTo('manage_classes') && !$user->hasRole('school_admin');
        // Assuming teacher has specific permission or we gate by role.
        // If there's an 'manage_homework' or similar, we can reuse.
        // Actually, we'll just check if they have manage_homework which acts as a proxy for teacher academic duties if there's no manage_online_classes.
    }

    /**
     * Determine if user can write to health records.
     * School Admins CANNOT create or edit health records (Health Officer responsibility).
     */
    public static function canWriteHealthRecords(SchoolUser $user): bool
    {
        return $user->hasPermissionTo('create_health_records');
    }
}
