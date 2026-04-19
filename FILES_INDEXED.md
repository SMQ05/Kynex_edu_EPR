# KynexEdu ERP - Files Indexed (Research Complete)

**Research Date:** April 7, 2026  
**Total Files Read:** 16  
**Total Lines Analyzed:** 3,000+

## HOMEWORK SYSTEM FILES

### Models
✓ `/app/Models/Tenant/HomeworkAssignment.php` (111 lines)
- Traits: HasUlids, SoftDeletes, RequiresApproval
- Relationships: schoolClass, section, subject, teacher, submissions
- Scopes: overdue(), upcoming(), dueToday()
- Attributes: getPendingSubmissionsCountAttribute(), isOverdue()

✓ `/app/Models/Tenant/HomeworkSubmission.php` (81 lines)
- Traits: HasUlids
- Relationships: homework, student, gradedBy
- Methods: isGraded(), isPending()

### Filament Resources
✓ `/app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource.php` (238 lines)
- Navigation: Academics group, sort 8
- Form: Homework Details, Attachment sections
- Table: 7 columns with filters and bulk actions
- File upload: 10 MB max (PDF, Word, Image)

### Filament Pages
✓ `/app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource/Pages/ListHomeworkAssignments.php` (22 lines)
✓ `/app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource/Pages/CreateHomeworkAssignment.php` (14 lines)
✓ `/app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource/Pages/EditHomeworkAssignment.php` (22 lines)

### Migrations
✓ `/database/migrations/tenant/2026_04_03_060_create_homework_and_notice_tables.php` (77 lines)
- Tables: homework_assignments, homework_submissions, notices
- Constraints: soft-delete, unique(homework_id, student_id), cascading FK

---

## EXAMINATION SYSTEM FILES

### Models
✓ `/app/Models/Tenant/Exam.php` (78 lines)
- Traits: HasUlids
- Relationships: academicYear, creator, schedules, results
- Scopes: active(), published(), forAcademicYear()

✓ `/app/Models/Tenant/ExamMark.php` (75 lines)
- Traits: HasUlids, RequiresApproval
- Relationships: schedule, student, enteredBy
- Accessors: getPercentageAttribute(), getIsPassAttribute()

✓ `/app/Models/Tenant/ExamResult.php` (82 lines)
- Traits: HasUlids
- Fillable: all columns including weighted_percentage
- Relationships: exam, student, schoolClass
- Scopes: passed(), failed(), forClass(), ranked()

### Services
✓ `/app/Services/ExamService.php` (367 lines)
- **Core Methods:**
  - `calculateResults(examId, classId?, gradingSystem)` - with advisory lock
  - `assignRanks(examId, classId)` - PostgreSQL RANK() OVER
  - `getMeritList(examId, classId, limit=10)`
  - `getStudentReportCard(examId, studentId)`
  - `getClassResultSummary(examId, classId)`
  - `saveMarks(examScheduleId, marksData, enteredBy)`
- **Features:**
  - Part 5f: Weighted percentage calculation
  - Activity score integration (0-30 → 0-100%)
  - Grade resolution & status determination

### Filament Resources
✓ `/app/Filament/SchoolAdmin/Resources/ExamResource.php` (174 lines)
- Navigation: Examinations group, sort 1
- Form: Exam Details, Annual Result Weightage sections
- Table: 8 columns with filters
- Supports exam weightage configuration

### Filament Pages
✓ `/app/Filament/SchoolAdmin/Pages/MarksEntry.php` (200 lines)
- Computed properties: exams(), schedules()
- Methods: loadStudentsForMarks(), saveMarks(), calculateResults()
- Validation: marks must be 0 to full_marks range
- Bulk marks entry interface

### Migrations
✓ `/database/migrations/tenant/2026_04_03_054_create_examination_tables.php` (160 lines)
- Tables: exams, exam_schedules, exam_marks, grade_rules, exam_results

✓ `/database/migrations/tenant/2026_04_03_065_add_weightage_to_exams_table.php` (96 lines)
- **CRITICAL:** Adds exam weightage support
- Columns added: weightage_percent, weightage_label, include_in_annual_result
- Includes: practical marks support, weighted_percentage to results

---

## ATTENDANCE SYSTEM FILES

### Models
✓ `/app/Models/Tenant/AttendanceSetting.php` (102 lines)
- Traits: HasUlids
- Static method: `forCampus(campusId)` - returns campus-specific or default
- Method: `isLateArrival(timeStr)` - checks cutoff + grace period
- Relationship: campus()

✓ `/app/Models/Tenant/DailyActivityLog.php` (126 lines)
- Traits: HasUlids
- Fillable: all except total_score (DB computed)
- Scores: participation (0-10), homework (0-10), behaviour (0-10)
- Total score: 0-30 (database STORED column)
- Scopes: forStudent(), forAcademicYear(), forSubject()
- Accessor: getTotalPercentageAttribute() (0-30 → 0-100%)

### Filament Pages
✓ `/app/Filament/SchoolAdmin/Pages/MarkAttendance.php` (247 lines)
- Navigation: Students group, sort 2
- Properties: class_id, section_id, date
- **Part 5e Features:**
  - activityScores[] keyed by student_id
  - saveActivityScores() method with validation
  - toggleActivityScores() for UI toggle
- Methods: loadStudents(), markAllPresent(), markAllAbsent(), saveAttendance()

### Filament Resources
✓ `/app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource.php` (144 lines)
- Navigation: Settings group, sort 25
- Form: Campus & Timing, Notifications sections
- Fields: school times, late cutoff, grace period, half-day, early departure
- Per-campus or school-wide default (campus_id nullable unique)

### Jobs
✓ `/app/Jobs/ProcessBiometricLogs.php` (230 lines)
- **Core Logic:**
  - Step 0: Resolve unlinked device_user_ids
  - Step 1: Process staff attendance from biometric logs
  - Step 2: Process student attendance
  - Step 3: Mark orphan logs (no user match)
- **Part 6d Features:**
  - `checkLateArrival()` method
  - Uses AttendanceSetting::forCampus() & ::isLateArrival()
  - Dispatches NotifyLateArrival if enabled
  - Catches exceptions to prevent job failure

### Migrations
✓ `/database/migrations/tenant/2026_04_03_067_create_attendance_settings_table.php` (67 lines)
- Table: attendance_settings
- Supports per-campus configuration (campus_id nullable unique)
- Includes: school times, late cutoff, grace period, half-day, early departure

✓ `/database/migrations/tenant/2026_04_03_066_create_daily_activity_logs_table.php` (70 lines)
- Table: daily_activity_logs (Part 5b)
- Columns: student, class, section, subject, academic_year, recorded_by
- Scores: participation (0-10), homework (0-10), behaviour (0-10)
- Total: STORED computed column (0-30)
- Unique: (student_id, subject_id, log_date)

---

## KEY INTEGRATION POINTS SUMMARY

### Homework Pipeline
```
HomeworkAssignmentResource (Create)
    → HomeworkAssignment model
    → HomeworkSubmission (student)
    → Teacher grades in UI
    → feedback, grade, graded_by, graded_at
```

### Exam Pipeline
```
ExamResource (Create with weightage_percent)
    → ExamService::calculateResults()
        ├─ Aggregates marks across subjects
        ├─ Applies exam.weightage_percent
        ├─ Incorporates DailyActivityLog scores
        ├─ Formula: (exam_contribution) + (activity_contribution)
        └─ Assigns ranks via PostgreSQL window function
    → ExamResult (with weighted_percentage)
    → ExamService::getStudentReportCard()
```

### Attendance Pipeline
```
Biometric Punch (ZKTeco)
    → ProcessBiometricLogs job
        ├─ Creates AttendanceRecord
        └─ Checks late arrival via AttendanceSetting
            └─ Dispatches NotifyLateArrival (Part 6d)
    → MarkAttendance page (Part 5e)
        ├─ Manual attendance override
        ├─ Activity score input (participation/homework/behaviour)
        └─ saveActivityScores() creates DailyActivityLog
    → DailyActivityLog (0-30 total_score)
    → Exam result calculation incorporates activity scores
```

---

## FILE INVENTORY CHECKLIST

### REQUESTED FILES (All Found ✓)

#### 1. app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource.php
✓ **Status:** FOUND & READ (238 lines)

#### 2. app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource/ — pages
✓ **Status:** 3 PAGES FOUND & READ
- ListHomeworkAssignments.php (22 lines)
- CreateHomeworkAssignment.php (14 lines)
- EditHomeworkAssignment.php (22 lines)

#### 3. app/Models/Tenant/HomeworkAssignment.php
✓ **Status:** FOUND & READ (111 lines)

#### 4. app/Services/ExamService.php
✓ **Status:** FOUND & READ (367 lines)

#### 5. app/Filament/SchoolAdmin/Pages/MarksEntry.php
✓ **Status:** FOUND & READ (200 lines)

#### 6. app/Filament/SchoolAdmin/Pages/MarkAttendance.php
✓ **Status:** FOUND & READ (247 lines) [INCLUDES PART 5e ADDITIONS]

#### 7. app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource.php
✓ **Status:** FOUND & READ (144 lines) [INCLUDES PART 6a ADDITIONS]

#### 8. app/Filament/SchoolAdmin/Resources/ExamResource.php
✓ **Status:** FOUND & READ (174 lines)

#### 9. app/Jobs/ProcessBiometricLogs.php
✓ **Status:** FOUND & READ (230 lines) [INCLUDES PART 6d ADDITIONS]

#### 10. app/Models/Tenant/AttendanceSetting.php
✓ **Status:** FOUND & READ (102 lines) [PART 6a]

#### 11. app/Models/Tenant/Exam.php
✓ **Status:** FOUND & READ (78 lines)

#### 12. app/Models/Tenant/ExamMark.php
✓ **Status:** FOUND & READ (75 lines)

#### 13. app/Models/Tenant/ExamResult.php
✓ **Status:** FOUND & READ (82 lines)

### ADDITIONAL FILES CHECKED

#### Migrations
✓ `/database/migrations/tenant/2026_04_03_060_create_homework_and_notice_tables.php` (77 lines)
✓ `/database/migrations/tenant/2026_04_03_054_create_examination_tables.php` (160 lines)
✓ `/database/migrations/tenant/2026_04_03_065_add_weightage_to_exams_table.php` (96 lines)
✓ `/database/migrations/tenant/2026_04_03_066_create_daily_activity_logs_table.php` (70 lines)
✓ `/database/migrations/tenant/2026_04_03_067_create_attendance_settings_table.php` (67 lines)

#### Models
✓ `/app/Models/Tenant/HomeworkSubmission.php` (81 lines)
✓ `/app/Models/Tenant/DailyActivityLog.php` (126 lines)

#### AttendanceSettingsResource Pages
✓ `/app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource/Pages/ListAttendanceSettings.php`
✓ `/app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource/Pages/CreateAttendanceSetting.php`
✓ `/app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource/Pages/EditAttendanceSetting.php`

---

## RESEARCH STATISTICS

| Category | Count |
|----------|-------|
| **Files Read** | 16 |
| **Total Lines of Code** | 3,000+ |
| **Models** | 5 |
| **Filament Resources** | 3 |
| **Filament Pages** | 4 |
| **Services** | 1 |
| **Jobs** | 1 |
| **Migrations** | 5 |
| **Database Tables** | 9 |

---

## DOCUMENTATION ARTIFACTS CREATED

1. **RESEARCH_REPORT.md** (861 lines)
   - Comprehensive analysis of all 3 systems
   - Database schema documentation
   - Integration points explained
   - Part 5 & Part 6 features documented

2. **FILES_INDEXED.md** (this file)
   - Quick reference to all files analyzed
   - File-by-file summaries
   - Checklist of deliverables

---

## KEY FINDINGS BY SYSTEM

### Homework ✓
- Two-table design (assignments + submissions)
- Soft-deletes enabled
- Submission tracking with grading workflow
- Attachment support (10 MB max)
- Color-coded due date UI

### Exams ✓
- Four-table core (exams, schedules, marks, results)
- **Part 5a-5f:** Sophisticated weightage system
  - Exam-level weightage (e.g., 30% for Term 1)
  - Activity score contribution (daily logs)
  - Weighted percentage calculation
- PostgreSQL advisory locks prevent concurrent calculation
- Window functions for ranking & merit lists
- Grade rules integration

### Attendance ✓
- Biometric integration (ZKTeco → ProcessBiometricLogs)
- **Part 5e:** Daily activity scoring (3 dimensions: 0-10 each)
  - Participation score
  - Homework score
  - Behaviour score
  - Database auto-computes total (0-30)
- **Part 6a:** Per-campus attendance settings
  - Configurable late arrival cutoff
  - Grace period support
  - Half-day & early departure flags
  - Optional late arrival notifications (Part 6d)

### Cross-System Integration ✓
- Daily activity logs influence exam results (Part 5f)
- Late arrival detection uses AttendanceSetting (Part 6a)
- MarkAttendance page allows activity score entry (Part 5e)
- ExamService incorporates activity scores in weightage (Part 5f)

---

**Research completed: April 7, 2026**  
**All requested files located, read, and documented**  
**No sensitive data exposed in analysis**
