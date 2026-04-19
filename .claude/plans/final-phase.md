# Final Phase Implementation Plan

## PART 0: Fix Login/Logout Menu

### Current State
- Both `SaasAdmin` and `SchoolUser` already implement `FilamentUser` with `canAccessPanel()`
- Both panel providers have `->login()` and correct `->authGuard()`
- **Missing:** `->authModel()` on both providers, no `userMenuItems` configured

### Changes

**`app/Providers/Filament/SaasAdminPanelProvider.php`**
- Add `->authModel(\App\Models\SaasAdmin::class)` to panel chain
- Add `->userMenuItems([...])` with a Logout menu item

**`app/Providers/Filament/SchoolAdminPanelProvider.php`**
- Add `->authModel(\App\Models\SchoolUser::class)` to panel chain
- Add `->userMenuItems([...])` with:
  - Logout menu item
  - Switch Role menu item (visible when user has 2+ roles) — this will be a custom Livewire page `/admin/switch-role`

**New file: `app/Filament/SchoolAdmin/Pages/SwitchRole.php`**
- Livewire page listing user's assigned roles
- Calls `$user->switchRole($roleName)` (method already exists on SchoolUser)
- Redirects back to dashboard after switch

**Verify:** `php artisan optimize:clear`, then login to both panels, confirm avatar dropdown with Logout visible.

---

## PART 1: Role Fixes & Wiring

### Current State
- `TenantDefaultRolesSeeder` already defines all 19 roles including INSTITUTE_OWNER, REGISTRAR, BURSAR, HR_MANAGER, EXAM_ADMIN with permissions
- Dashboard.php match block has 14 roles wired, missing 5

### Changes

**`app/Filament/SchoolAdmin/Pages/Dashboard.php`**
Add to match block:
- `'INSTITUTE_OWNER'` → `[InstituteOwnerStatsWidget::class]`
- `'REGISTRAR'` → `[RegistrarStatsWidget::class]`
- `'BURSAR'` → same as ACCOUNTANT: `[AccountantStatsWidget::class, RecentFeeCollectionsWidget::class, RecentExpensesWidget::class]`
- `'HR_MANAGER'` → `[HrManagerStatsWidget::class]`
- `'EXAM_ADMIN'` → `[ExamAdminStatsWidget::class]`

Add imports for the 3 new widget classes (InstituteOwner, Registrar, HrManager, ExamAdmin — Bursar reuses Accountant widgets which are already imported).

ATTENDANCE_CLERK is already wired with 3 widgets. No role-assignment restrictions exist in the seeder — any SchoolUser can be assigned any role including ATTENDANCE_CLERK on top of TEACHER.

---

## PART 2: Notification Flows

### Current State
- `NotifyAbsentStudentsAndParents` job exists but only notifies **primary guardian** and creates in-app for student
- Does NOT send WhatsApp/SMS to student directly
- `NotifyResultPublished` notifies guardians + creates in-app for student, but no direct WhatsApp/SMS to student
- `SendFeeReminderWithFallback` has push-first with 24h fallback — already implemented
- `NotifyHomeworkCreated` and `NotifyHomeworkGraded` exist — notify guardians only
- Rate limiting exists in `ProcessNotificationQueue` and `AndroidGatewayDriver` (8-18s delay)
- `SendPkSmsDriver` and `SendPkWhatsAppDriver` already exist — no rate limiting (official)
- `NotificationSettingsPage` already has all 6 toggle fields

### Changes

**`app/Jobs/NotifyAbsentStudentsAndParents.php`**
- After notifying guardian, also send WhatsApp/SMS directly to **student** if `student->phone` or `student->email` exists
- Use `NotificationService->sendRaw()` for student channel

**`app/Jobs/NotifyResultPublished.php`**
- After guardian notification, also send WhatsApp/SMS to student (if phone exists)

**`app/Jobs/NotifyHomeworkCreated.php`**
- Also notify student directly (in-app + WhatsApp/SMS if phone exists)

**`app/Jobs/NotifyHomeworkGraded.php`**
- Also notify student directly

**`app/Services/Sms/SendPkSmsDriver.php`**
- Already has no rate limiting — confirm URL matches `sendpk.com/api/sms.php` (it does)

**`app/Services/WhatsApp/SendPkWhatsAppDriver.php`**
- Already has no rate limiting — confirm URL matches `sendpk.com/api/whatsapp.php` (it does)

**`app/Services/NotificationService.php`**
- Add helper method `sendToStudentDirectly(Student $student, string $title, string $body, array $channels)` that resolves student phone/whatsapp and sends

**`app/Filament/SchoolAdmin/Pages/NotificationSettingsPage.php`**
- Already complete with all 6 fields — no changes needed

No new .env keys needed — SendPK credentials are stored per-tenant in `sms_config` / `whatsapp_config` JSON columns.

---

## PART 3: Homework/Assignment Module + Teacher Features

### Current State
- `HomeworkAssignmentResource` exists with Create/Edit/List pages
- `HomeworkAssignment` and `HomeworkSubmission` models exist
- Migration `2026_04_03_060` creates both tables
- `MarkAttendance` page exists with daily activity scoring (0-10 participation, homework, behaviour = 0-30 total)
- `DailyActivityLog` model and migration exist
- `ExamService` already calculates weighted percentage using `exam->weightage_percent` and `DailyActivityLog` scores
- `Exam` model has `weightage_percent` and `include_in_annual_result` columns

### Changes

**`app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource.php`**
- Verify the resource has a relation manager for submissions. If missing, add `HomeworkSubmissionsRelationManager`

**New file: `app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource/RelationManagers/HomeworkSubmissionsRelationManager.php`**
- Table columns: student name, submitted_at, file_attachment, marks_obtained, total_marks, feedback, graded_at
- Actions: View, Grade (modal with marks + feedback fields)
- Grade action dispatches `NotifyHomeworkGraded` job

**`app/Filament/SchoolAdmin/Resources/ExamResource.php`**
- Verify `weightage_percent` field exists on the create/edit form
- If missing, add a numeric field (1-100, default 100) with helper text explaining: "Percentage weight of this exam in the annual result. Remaining weight comes from daily activity scores."
- Add `include_in_annual_result` toggle

**`app/Filament/SchoolAdmin/Pages/MarkAttendance.php`**
- Already has daily activity scoring (0-10 per category) — verify it saves to `DailyActivityLog`
- No changes expected

**`app/Services/ExamService.php`**
- Already implements weighted calculation — no changes needed

---

## PART 4: Biometric Late Arrival

### Current State
- `AttendanceSetting` model exists with `late_entry_time`, `absent_entry_time`, `grace_period_minutes` columns per campus
- `AttendanceSettingsResource` exists in Filament
- `ProcessBiometricLogs` job exists and already checks late arrival cutoff
- Job already sends notification via `NotifyLateArrival` job

### Changes

**`app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource.php`**
- Verify form has fields for `late_entry_time` and `absent_entry_time` per class/section
- If currently only per-campus, extend: add optional `class_id` and `section_id` columns so cutoff can be per-class/section
- This requires a migration to add nullable `class_id` and `section_id` to `attendance_settings` table

**New migration: `database/migrations/tenant/2026_04_07_083_add_class_section_to_attendance_settings.php`**
- Add `class_id` ULID nullable FK
- Add `section_id` ULID nullable FK
- Drop old unique constraint on campus_id, add new unique on (campus_id, class_id, section_id)

**`app/Jobs/ProcessBiometricLogs.php`**
- Update setting lookup: first try to find setting for specific class/section, then fall back to campus-level setting
- Already sends `NotifyLateArrival` — ensure it sends to BOTH parent and student

**`app/Jobs/NotifyLateArrival.php`**
- Verify it sends to parent — also add student notification (WhatsApp/SMS if phone exists)

---

## PART 5: Other Final Items

### Bursar = Accountant Dashboard
- Handled in Part 1 — BURSAR mapped to same widgets as ACCOUNTANT

### Attendance Clerk Assignable to Anyone
- Already the case — no role-assignment restrictions exist. Any SchoolUser (including one with TEACHER role) can also be assigned ATTENDANCE_CLERK

### SaaS Super Admin Creates Institute Owners
- The current flow: SaaS admin creates Tenant → `ProvisionNewTenant` action creates tenant DB + seeds roles + creates initial SchoolUser with SCHOOL_ADMIN role
- To support multiple Institute Owners: the SaaS admin (or the SCHOOL_ADMIN within the tenant) assigns the INSTITUTE_OWNER role to users
- **No code change needed** — INSTITUTE_OWNER role already exists in seeder, any SchoolUser can be assigned it via the Staff management resource

---

## Files to Create/Modify (Summary)

### New Files
1. `app/Filament/SchoolAdmin/Pages/SwitchRole.php` — role switching page
2. `app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource/RelationManagers/HomeworkSubmissionsRelationManager.php` — submission grading
3. `database/migrations/tenant/2026_04_07_083_add_class_section_to_attendance_settings.php` — per-class attendance cutoff

### Modified Files
**Part 0:**
4. `app/Providers/Filament/SaasAdminPanelProvider.php` — add authModel + userMenuItems
5. `app/Providers/Filament/SchoolAdminPanelProvider.php` — add authModel + userMenuItems

**Part 1:**
6. `app/Filament/SchoolAdmin/Pages/Dashboard.php` — wire 5 missing roles

**Part 2:**
7. `app/Jobs/NotifyAbsentStudentsAndParents.php` — also notify student directly
8. `app/Jobs/NotifyResultPublished.php` — also notify student directly
9. `app/Jobs/NotifyHomeworkCreated.php` — also notify student directly
10. `app/Jobs/NotifyHomeworkGraded.php` — also notify student directly
11. `app/Jobs/NotifyLateArrival.php` — also notify student directly
12. `app/Services/NotificationService.php` — add sendToStudentDirectly helper

**Part 3:**
13. `app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource.php` — add relation manager reference
14. `app/Filament/SchoolAdmin/Resources/ExamResource.php` — verify/add weightage fields

**Part 4:**
15. `app/Models/Tenant/AttendanceSetting.php` — add class_id/section_id fillable
16. `app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource.php` — add class/section fields
17. `app/Jobs/ProcessBiometricLogs.php` — class/section-level cutoff lookup

---

## New .env Keys
**None required.** SendPK credentials are stored per-tenant in JSON config columns, not in .env.

---

## Test Checklist
1. Login to `/saas/login` — avatar dropdown shows with Logout
2. Login to `/admin/login` — avatar dropdown shows with Logout + Switch Role (if 2+ roles)
3. INSTITUTE_OWNER, REGISTRAR, BURSAR, HR_MANAGER, EXAM_ADMIN roles show correct dashboards
4. BURSAR sees same dashboard as ACCOUNTANT
5. Absent notification sends to both parent AND student
6. Fee reminder: push first → 24h fallback WhatsApp/SMS (already working)
7. Homework submission grading works in relation manager
8. Teacher can mark daily activity (0-10 per category, saves to DailyActivityLog)
9. Exam weightage_percent field on exam form, weighted calculation in results
10. Biometric late cutoff per class/section, notification to parent + student
11. Rate limiting: unofficial gateways have 8-18s delay, SendPK has no delay
12. Switch Role page works — user switches role, dashboard changes
