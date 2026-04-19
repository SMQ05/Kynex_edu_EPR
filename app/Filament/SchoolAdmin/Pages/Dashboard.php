<?php

namespace App\Filament\SchoolAdmin\Pages;

use App\Filament\SchoolAdmin\Widgets\Accountant\AccountantStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Accountant\RecentExpensesWidget;
use App\Filament\SchoolAdmin\Widgets\Accountant\RecentFeeCollectionsWidget;
use App\Filament\SchoolAdmin\Widgets\AttendanceClerk\AttendanceClerkClassListWidget;
use App\Filament\SchoolAdmin\Widgets\AttendanceClerk\AttendanceClerkRecentAbsentsWidget;
use App\Filament\SchoolAdmin\Widgets\AttendanceClerk\AttendanceClerkStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Cafeteria\CafeteriaLowStockWidget;
use App\Filament\SchoolAdmin\Widgets\Cafeteria\CafeteriaStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Counselor\CounselorStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Counselor\CounselorStubWidget;
use App\Filament\SchoolAdmin\Widgets\ExamAdmin\ExamAdminStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Hostel\HostelCheckedOutTodayWidget;
use App\Filament\SchoolAdmin\Widgets\Hostel\HostelPendingGatePassesWidget;
use App\Filament\SchoolAdmin\Widgets\Hostel\HostelWardenStatsWidget;
use App\Filament\SchoolAdmin\Widgets\HrManager\HrManagerStatsWidget;
use App\Filament\SchoolAdmin\Widgets\InstituteOwner\InstituteOwnerStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Librarian\LibrarianBookSearchWidget;
use App\Filament\SchoolAdmin\Widgets\Librarian\LibrarianStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Nurse\NurseHealthStubWidget;
use App\Filament\SchoolAdmin\Widgets\Nurse\NurseStatsWidget;
use App\Filament\SchoolAdmin\Widgets\ParentRole\ParentChildrenWidget;
use App\Filament\SchoolAdmin\Widgets\ParentRole\ParentStatsWidget;
use App\Filament\SchoolAdmin\Widgets\PendingLeaveRequestsWidget;
use App\Filament\SchoolAdmin\Widgets\RecentStudentsWidget;
use App\Filament\SchoolAdmin\Widgets\Receptionist\ReceptionistCurrentVisitorsWidget;
use App\Filament\SchoolAdmin\Widgets\Receptionist\ReceptionistStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Registrar\RegistrarStatsWidget;
use App\Filament\SchoolAdmin\Widgets\SchoolStatsOverview;
use App\Filament\SchoolAdmin\Widgets\StudentRole\StudentStatsWidget;
use App\Filament\SchoolAdmin\Widgets\StudentRole\StudentTimetableWidget;
use App\Filament\SchoolAdmin\Widgets\Teacher\TeacherStatsWidget;
use App\Filament\SchoolAdmin\Widgets\Teacher\TeacherTimetableWidget;
use App\Filament\SchoolAdmin\Widgets\Transport\TransportGpsPlaceholderWidget;
use App\Filament\SchoolAdmin\Widgets\Transport\TransportStatsWidget;
use App\Filament\SchoolAdmin\Widgets\UpcomingOnlineClassesWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        $user = auth()->user();
        $role = $user?->active_role
            ?? $user?->roles?->first()?->name;

        return match ($role) {
            'SCHOOL_ADMIN' => [
                SchoolStatsOverview::class,
                RecentStudentsWidget::class,
                PendingLeaveRequestsWidget::class,
                UpcomingOnlineClassesWidget::class,
            ],

            'INSTITUTE_HEAD' => [
                InstituteOwnerStatsWidget::class,
            ],

            'TEACHER' => [
                TeacherStatsWidget::class,
                TeacherTimetableWidget::class,
            ],

            'PARENT' => [
                ParentStatsWidget::class,
                ParentChildrenWidget::class,
            ],

            'STUDENT' => [
                StudentStatsWidget::class,
                StudentTimetableWidget::class,
            ],

            'ACCOUNTANT' => [
                AccountantStatsWidget::class,
                RecentFeeCollectionsWidget::class,
                RecentExpensesWidget::class,
            ],

            'LIBRARIAN' => [
                LibrarianStatsWidget::class,
                LibrarianBookSearchWidget::class,
            ],

            'TRANSPORT_MANAGER' => [
                TransportStatsWidget::class,
                TransportGpsPlaceholderWidget::class,
            ],

            'HOSTEL_WARDEN' => [
                HostelWardenStatsWidget::class,
                HostelPendingGatePassesWidget::class,
                HostelCheckedOutTodayWidget::class,
            ],

            'NURSE' => [
                NurseStatsWidget::class,
                NurseHealthStubWidget::class,
            ],

            'COUNSELOR' => [
                CounselorStatsWidget::class,
                CounselorStubWidget::class,
            ],

            'CAFETERIA_MANAGER' => [
                CafeteriaStatsWidget::class,
                CafeteriaLowStockWidget::class,
            ],

            'RECEPTIONIST' => [
                ReceptionistStatsWidget::class,
                ReceptionistCurrentVisitorsWidget::class,
            ],

            'ATTENDANCE_CLERK' => [
                AttendanceClerkStatsWidget::class,
                AttendanceClerkClassListWidget::class,
                AttendanceClerkRecentAbsentsWidget::class,
            ],

            'MULTI_INSTITUTE_HEAD' => [
                InstituteOwnerStatsWidget::class,
            ],

            'REGISTRAR' => [
                RegistrarStatsWidget::class,
            ],

            'BURSAR' => [
                AccountantStatsWidget::class,
                RecentFeeCollectionsWidget::class,
                RecentExpensesWidget::class,
            ],

            'HR_MANAGER' => [
                HrManagerStatsWidget::class,
            ],

            'EXAM_ADMIN' => [
                ExamAdminStatsWidget::class,
            ],

            default => [
                SchoolStatsOverview::class,
            ],
        };
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
