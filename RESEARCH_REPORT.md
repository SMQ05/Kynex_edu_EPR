# KynexEdu ERP System - Comprehensive Research Report

**Research Date:** April 7, 2026  
**Repository:** /home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP  
**Focus:** Homework, Exam, and Attendance Systems

---

## TABLE OF CONTENTS

1. [System Architecture Overview](#system-architecture-overview)
2. [Homework Management System](#homework-management-system)
3. [Examination System](#examination-system)
4. [Attendance System](#attendance-system)
5. [Database Migrations](#database-migrations)
6. [Key Integration Points](#key-integration-points)
7. [Findings & Observations](#findings--observations)

---

## SYSTEM ARCHITECTURE OVERVIEW

The KynexEdu ERP is a **Laravel-based multi-tenant education management system** utilizing:
- **Filament Admin Panel** for school administration interfaces
- **PostgreSQL** for data persistence with advisory locks
- **Livewire** for real-time UI interactions
- **Laravel Queue** for asynchronous job processing
- **ZKTeco Biometric Integration** for attendance tracking

### Technology Stack
- **Framework:** Laravel with Filament (PHP 8.x)
- **Database:** PostgreSQL (tenant databases)
- **Frontend:** Livewire + Blade templates
- **Authentication:** Spatie/Roles & Permissions
- **Tenancy:** Multi-tenant architecture (tenant-per-database)

---

## HOMEWORK MANAGEMENT SYSTEM

### 1. Database Schema

#### **homework_assignments Table**
```sql
CREATE TABLE homework_assignments (
  id ULID PRIMARY KEY,
  class_id ULID NOT NULL,
  section_id ULID NOT NULL,
  subject_id ULID NOT NULL,
  teacher_id ULID NOT NULL (school_users.id),
  title VARCHAR(255),
  description TEXT,
  due_date DATE,
  attachment_path VARCHAR(255) NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP (soft-deletes enabled),
  
  -- Indexes
  INDEX(class_id, section_id, due_date),
  FOREIGN KEY(class_id) REFERENCES classes(id) CASCADE,
  FOREIGN KEY(section_id) REFERENCES sections(id) CASCADE,
  FOREIGN KEY(subject_id) REFERENCES subjects(id) CASCADE,
  FOREIGN KEY(teacher_id) REFERENCES school_users(id) CASCADE
);
```

#### **homework_submissions Table**
```sql
CREATE TABLE homework_submissions (
  id ULID PRIMARY KEY,
  homework_id ULID NOT NULL,
  student_id ULID NOT NULL,
  submission_text TEXT NULLABLE,
  attachment_path VARCHAR(255) NULLABLE,
  submitted_at TIMESTAMP,
  grade VARCHAR(255) NULLABLE,
  feedback TEXT NULLABLE,
  graded_by ULID NULLABLE (school_users.id),
  graded_at TIMESTAMP NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Constraints
  UNIQUE(homework_id, student_id),
  FOREIGN KEY(homework_id) REFERENCES homework_assignments(id) CASCADE,
  FOREIGN KEY(student_id) REFERENCES students(id) CASCADE,
  FOREIGN KEY(graded_by) REFERENCES school_users(id) NULL DELETE
);
```

#### Migration File: `2026_04_03_060_create_homework_and_notice_tables.php`
- Creates three tables: `homework_assignments`, `homework_submissions`, `notices`
- Uses ULIDs for primary keys
- Soft-delete support on homework_assignments
- Composite uniqueness constraint on (homework_id, student_id)
- Max file upload: 10 MB (PDF, Word, JPEG, PNG, WebP)

### 2. Eloquent Models

#### **HomeworkAssignment Model**
- **File:** `/app/Models/Tenant/HomeworkAssignment.php`
- **Key Features:**
  - Uses `HasUlids`, `SoftDeletes`, `RequiresApproval` traits
  - Casts `due_date` as carbon date
  - **Relationships:**
    - `schoolClass()` - BelongsTo SchoolClass
    - `section()` - BelongsTo Section
    - `subject()` - BelongsTo Subject
    - `teacher()` - BelongsTo SchoolUser (via teacher_id)
    - `submissions()` - HasMany HomeworkSubmission
  - **Scopes:**
    - `overdue()` - where due_date < today
    - `upcoming()` - where due_date >= today
    - `dueToday()` - where due_date = today
  - **Attributes:**
    - `getPendingSubmissionsCountAttribute()` - counts submissions without grades
    - `isOverdue()` - boolean helper

#### **HomeworkSubmission Model**
- **File:** `/app/Models/Tenant/HomeworkSubmission.php`
- **Key Features:**
  - Uses `HasUlids` trait
  - Casts: `submitted_at`, `graded_at` as datetime
  - **Relationships:**
    - `homework()` - BelongsTo HomeworkAssignment
    - `student()` - BelongsTo Student
    - `gradedBy()` - BelongsTo SchoolUser
  - **Helper Methods:**
    - `isGraded()` - checks if grade is not null
    - `isPending()` - checks if grade is null

### 3. Filament Admin Resource

#### **HomeworkAssignmentResource**
- **File:** `/app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource.php`
- **Navigation:**
  - Icon: `heroicon-o-clipboard-document-list`
  - Group: "Academics"
  - Sort: 8

#### **Form Schema**
1. **Homework Details Section** (2 columns)
   - `title` - Required text input (max 255 chars)
   - `class_id` - Dropdown (reactive, resets section_id)
   - `section_id` - Dropdown (dynamic based on class_id, reactive)
   - `subject_id` - Dropdown searchable
   - `teacher_id` - Defaults to current authenticated user
   - `due_date` - Date picker (min: yesterday, default: 1 week from now)
   - `description` - Rich editor with formatting toolbar

2. **Attachment Section** (collapsible)
   - `attachment_path` - File upload
   - Max size: 10 MB
   - Allowed types: PDF, Word, JPEG, PNG, WebP
   - Directory: `homework-attachments/`

#### **Table Display**
- **Columns:**
  - `title` - searchable, sortable, limited to 40 chars
  - `schoolClass.name` - sorted
  - `section.name` - sorted
  - `subject.name` - sorted
  - `teacher.name` - sorted, toggleable
  - `due_date` - formatted M d, Y, color-coded (past = red, future = green)
  - `submissions_count` - badge, counted from relation
  - `created_at` - datetime, toggleable hidden by default

#### **Filters**
- By class_id (dropdown)
- By subject_id (dropdown)
- By status (upcoming/overdue with custom query logic)

#### **Actions**
- Edit (inline)
- Delete (bulk capable)
- Bulk delete action group

#### **Pages**
1. `ListHomeworkAssignments` - extends ListRecords
2. `CreateHomeworkAssignment` - extends CreateRecord
3. `EditHomeworkAssignment` - extends EditRecord with DeleteAction

---

## EXAMINATION SYSTEM

### 1. Database Schema

#### **exams Table**
```sql
CREATE TABLE exams (
  id ULID PRIMARY KEY,
  academic_year_id ULID NOT NULL,
  name VARCHAR(255),
  description TEXT NULLABLE,
  start_date DATE NULLABLE,
  end_date DATE NULLABLE,
  status VARCHAR(255) DEFAULT 'draft', -- ExamStatus enum
  publish_results BOOLEAN DEFAULT false,
  created_by ULID NULLABLE,
  weightage_percent TINYINT UNSIGNED DEFAULT 100,
  weightage_label VARCHAR(255) NULLABLE,
  include_in_annual_result BOOLEAN DEFAULT true,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  FOREIGN KEY(academic_year_id) REFERENCES academic_years(id) CASCADE,
  FOREIGN KEY(created_by) REFERENCES school_users(id) NULL DELETE
);
```

#### **exam_schedules Table**
```sql
CREATE TABLE exam_schedules (
  id ULID PRIMARY KEY,
  exam_id ULID NOT NULL,
  class_id ULID NOT NULL,
  section_id ULID NULLABLE,
  subject_id ULID NOT NULL,
  exam_date DATE,
  start_time TIME NULLABLE,
  end_time TIME NULLABLE,
  room VARCHAR(255) NULLABLE,
  full_marks UNSIGNED INT,
  pass_marks UNSIGNED INT,
  theory_weight TINYINT UNSIGNED DEFAULT 70,
  practical_weight TINYINT UNSIGNED DEFAULT 30,
  practical_full_marks UNSIGNED INT NULLABLE,
  practical_pass_marks UNSIGNED INT NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  UNIQUE(exam_id, class_id, section_id, subject_id),
  FOREIGN KEY(exam_id) REFERENCES exams(id) CASCADE,
  FOREIGN KEY(class_id) REFERENCES classes(id) CASCADE,
  FOREIGN KEY(section_id) REFERENCES sections(id) NULL DELETE,
  FOREIGN KEY(subject_id) REFERENCES subjects(id) CASCADE
);
```

#### **exam_marks Table**
```sql
CREATE TABLE exam_marks (
  id ULID PRIMARY KEY,
  exam_schedule_id ULID NOT NULL,
  student_id ULID NOT NULL,
  marks_obtained DECIMAL(6,2) NULLABLE,
  practical_marks_obtained DECIMAL(6,2) NULLABLE,
  is_absent BOOLEAN DEFAULT false,
  remarks TEXT NULLABLE,
  entered_by ULID NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  UNIQUE(exam_schedule_id, student_id),
  FOREIGN KEY(exam_schedule_id) REFERENCES exam_schedules(id) CASCADE,
  FOREIGN KEY(student_id) REFERENCES students(id) CASCADE,
  FOREIGN KEY(entered_by) REFERENCES school_users(id) NULL DELETE
);
```

#### **exam_results Table** (Aggregated Results)
```sql
CREATE TABLE exam_results (
  id ULID PRIMARY KEY,
  exam_id ULID NOT NULL,
  student_id ULID NOT NULL,
  class_id ULID NOT NULL,
  total_marks DECIMAL(8,2) DEFAULT 0,
  marks_obtained DECIMAL(8,2) DEFAULT 0,
  percentage DECIMAL(5,2) DEFAULT 0,
  weighted_percentage DECIMAL(6,2) NULLABLE,
  grade VARCHAR(255) NULLABLE,
  grade_point DECIMAL(3,1) NULLABLE,
  rank UNSIGNED INT NULLABLE,
  status VARCHAR(255) DEFAULT 'pass', -- ExamResultStatus enum
  remarks TEXT NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  UNIQUE(exam_id, student_id),
  FOREIGN KEY(exam_id) REFERENCES exams(id) CASCADE,
  FOREIGN KEY(student_id) REFERENCES students(id) CASCADE,
  FOREIGN KEY(class_id) REFERENCES classes(id) CASCADE
);
```

#### **grade_rules Table**
```sql
CREATE TABLE grade_rules (
  id ULID PRIMARY KEY,
  name VARCHAR(255), -- "Standard Grading"
  grade VARCHAR(255), -- A+, A, B+, B, C, D, F
  min_percentage DECIMAL(5,2),
  max_percentage DECIMAL(5,2),
  grade_point DECIMAL(3,1) NULLABLE,
  description TEXT NULLABLE,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  INDEX(name, min_percentage)
);
```

### 2. Eloquent Models

#### **Exam Model**
- **File:** `/app/Models/Tenant/Exam.php`
- **Key Features:**
  - Uses `HasUlids` trait
  - Fillable: academic_year_id, name, description, start_date, end_date, status, publish_results, created_by
  - Casts: dates to carbon, status to ExamStatus enum, publish_results to boolean
  - **Relationships:**
    - `academicYear()` - BelongsTo AcademicYear
    - `creator()` - BelongsTo SchoolUser (via created_by)
    - `schedules()` - HasMany ExamSchedule
    - `results()` - HasMany ExamResult
  - **Scopes:**
    - `active()` - excludes Cancelled status
    - `published()` - where publish_results = true
    - `forAcademicYear(yearId)` - filters by academic year

#### **ExamMark Model**
- **File:** `/app/Models/Tenant/ExamMark.php`
- **Key Features:**
  - Uses `HasUlids`, `RequiresApproval` traits
  - Fillable: exam_schedule_id, student_id, marks_obtained, is_absent, remarks, entered_by
  - Casts: marks_obtained to decimal:2, is_absent to boolean
  - **Relationships:**
    - `schedule()` - BelongsTo ExamSchedule
    - `student()` - BelongsTo Student
    - `enteredBy()` - BelongsTo SchoolUser
  - **Accessors:**
    - `getPercentageAttribute()` - calculated from marks/full_marks
    - `getIsPassAttribute()` - boolean pass check

#### **ExamResult Model**
- **File:** `/app/Models/Tenant/ExamResult.php`
- **Key Features:**
  - Uses `HasUlids` trait
  - Fillable: all columns including weighted_percentage
  - Casts: decimals (2-2), rank to integer, status to ExamResultStatus enum
  - **Relationships:**
    - `exam()` - BelongsTo Exam
    - `student()` - BelongsTo Student
    - `schoolClass()` - BelongsTo SchoolClass
  - **Scopes:**
    - `passed()` - status = Pass
    - `failed()` - status = Fail
    - `forClass(classId)` - class-specific results
    - `ranked()` - ordered by rank ASC

### 3. Filament Exam Resource

#### **ExamResource**
- **File:** `/app/Filament/SchoolAdmin/Resources/ExamResource.php`
- **Navigation:**
  - Icon: `heroicon-o-clipboard-document-list`
  - Group: "Examinations"
  - Sort: 1

#### **Form Schema**
1. **Exam Details Section** (2 columns)
   - `academic_year_id` - Relationship dropdown (required, searchable, preload)
   - `name` - Text input (required, max 255)
   - `description` - Textarea (3 rows)
   - `start_date` - Date picker
   - `end_date` - Date picker (must be >= start_date)
   - `status` - Dropdown (ExamStatus enum, default: Draft)
   - `publish_results` - Toggle (default: false)

2. **Annual Result Weightage Section** (2 columns, collapsed)
   - `weightage_percent` - Numeric (0-100, default: 100, suffix: %)
   - `weightage_label` - Text input (nullable, e.g., "Term 1 (30%)")
   - `include_in_annual_result` - Toggle (default: true)

#### **Table Display**
- Columns: name, academicYear.name, start_date, end_date, status (badge), publish_results (icon), schedules_count, created_at
- Filters: by status, by academic_year_id
- Default sort: created_at DESC
- Actions: Edit

### 4. ExamService (Core Logic)

#### **File:** `/app/Services/ExamService.php`

**Key Methods:**

1. **calculateResults(examId, classId?, gradingSystem)**
   - Processes exam results using PostgreSQL advisory locks to prevent concurrent execution
   - Groups exam_schedules by class
   - Calculates per-student aggregated results including:
     - Total marks & obtained marks across all subjects
     - Percentage: (obtained / total) × 100
     - Pass/Fail/Absent status determination
     - Grade resolution via GradeRule::resolveGrade()
   - **Part 5f: Weighted Percentage Calculation**
     - Applies `exam.weightage_percent` to raw percentage
     - Incorporates `DailyActivityLog` average scores (max 30 points → 30%)
     - Formula: `examContribution + (activityPct × activityWeight / 100)`
     - Only includes in annual if `exam.include_in_annual_result = true`
   - Creates/updates ExamResult records
   - Calls `assignRanks()` for class-level ranking
   - Returns count of calculated results

2. **assignRanks(examId, classId)**
   - Uses PostgreSQL RANK() OVER window function
   - Ranks passing students by marks_obtained DESC (NULLS LAST)
   - Sets rank=null for non-passing students
   - Handles ties correctly (same score = same rank)

3. **getMeritList(examId, classId, limit=10)**
   - Returns top N students using ROW_NUMBER() OVER
   - Includes student info: first_name, last_name, admission_number
   - Only passing students

4. **getStudentReportCard(examId, studentId)**
   - Returns complete report card data including:
     - Student info
     - Overall result (marks, percentage, grade, rank, status)
     - Subject-wise breakdown with pass/fail indicators
   - Validates student's class matches exam schedules

5. **getClassResultSummary(examId, classId)**
   - Returns class-level statistics:
     - Total students, pass/fail/absent counts
     - Pass rate percentage
     - Highest/lowest percentage
     - Average percentage
   - Returns empty stats if no results

6. **saveMarks(examScheduleId, marksData, enteredBy)**
   - Bulk saves exam marks in a transaction
   - Handles absent marking (nullifies marks_obtained)
   - Updates existing marks
   - Validates marks are within 0 to full_marks range

---

## ATTENDANCE SYSTEM

### 1. Database Schema

#### **attendance_settings Table** (Part 6a)
```sql
CREATE TABLE attendance_settings (
  id ULID PRIMARY KEY,
  campus_id ULID NULLABLE UNIQUE, -- NULL = school-wide default
  school_start_time TIME DEFAULT '07:30:00',
  school_end_time TIME DEFAULT '14:00:00',
  late_arrival_cutoff TIME DEFAULT '08:00:00',
  grace_period_minutes UNSIGNED SMALLINT DEFAULT 0,
  notify_on_late_arrival BOOLEAN DEFAULT true,
  half_day_cutoff TIME NULLABLE,
  early_departure_cutoff TIME NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  FOREIGN KEY(campus_id) REFERENCES campuses(id) NULL DELETE
);
```

#### **daily_activity_logs Table** (Part 5b)
```sql
CREATE TABLE daily_activity_logs (
  id ULID PRIMARY KEY,
  student_id ULID NOT NULL,
  class_id ULID NOT NULL,
  section_id ULID NULLABLE,
  subject_id ULID NULLABLE,
  academic_year_id ULID NOT NULL,
  recorded_by ULID NOT NULL (school_users.id),
  log_date DATE,
  participation_score TINYINT UNSIGNED DEFAULT 0, -- 0-10
  homework_score TINYINT UNSIGNED DEFAULT 0, -- 0-10
  behaviour_score TINYINT UNSIGNED DEFAULT 10, -- 0-10
  total_score TINYINT UNSIGNED STORED AS (participation_score + homework_score + behaviour_score), -- 0-30
  notes TEXT NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  UNIQUE(student_id, subject_id, log_date),
  FOREIGN KEY(student_id) REFERENCES students(id) CASCADE,
  FOREIGN KEY(class_id) REFERENCES classes(id) CASCADE,
  FOREIGN KEY(section_id) REFERENCES sections(id) NULL DELETE,
  FOREIGN KEY(subject_id) REFERENCES subjects(id) NULL DELETE,
  FOREIGN KEY(academic_year_id) REFERENCES academic_years(id) CASCADE,
  FOREIGN KEY(recorded_by) REFERENCES school_users(id) CASCADE,
  INDEX(student_id, academic_year_id, log_date),
  INDEX(class_id, section_id, log_date)
);
```

### 2. Eloquent Models

#### **AttendanceSetting Model**
- **File:** `/app/Models/Tenant/AttendanceSetting.php`
- **Key Features:**
  - Uses `HasUlids` trait
  - Fillable: campus_id, school_start_time, school_end_time, late_arrival_cutoff, grace_period_minutes, notify_on_late_arrival, half_day_cutoff, early_departure_cutoff
  - Casts: notify_on_late_arrival to boolean, grace_period_minutes to integer
  - **Relationship:**
    - `campus()` - BelongsTo Campus
  - **Helper Methods:**
    - `forCampus(campusId)` - static method returns campus-specific settings or school-wide default
    - `isLateArrival(timeStr)` - checks if arrival time exceeds cutoff + grace period

#### **DailyActivityLog Model**
- **File:** `/app/Models/Tenant/DailyActivityLog.php`
- **Key Features:**
  - Uses `HasUlids` trait
  - Fillable: all except total_score (which is database-computed)
  - Casts: log_date to date, scores to integer, total_score to integer
  - **Relationships:**
    - `student()`, `schoolClass()`, `section()`, `subject()`, `academicYear()`, `recordedBy()` - all BelongsTo
  - **Scopes:**
    - `forStudent(studentId)` - filters by student_id
    - `forAcademicYear(yearId)` - filters by academic_year_id
    - `forSubject(subjectId)` - filters by subject_id
  - **Accessor:**
    - `getTotalPercentageAttribute()` - converts 0-30 to 0-100 percentage

### 3. Filament Pages

#### **MarkAttendance Page** (Part 5e Integration)
- **File:** `/app/Filament/SchoolAdmin/Pages/MarkAttendance.php`
- **Navigation:**
  - Icon: `heroicon-o-clipboard-document-check`
  - Label: "Mark Attendance"
  - Group: "Students"
  - Sort: 2

**Key Properties:**
- `class_id`, `section_id`, `date` - filter properties
- `students[]`, `attendance[]` - student attendance data
- `activityScores[]` - keyed by student_id with participation/homework/behaviour scores (PART 5e)
- `showActivityScores` - toggle for activity UI display

**Key Methods:**

1. `loadStudents()`
   - Validates class, section, date selection
   - Checks if attendance already marked via AttendanceService
   - Loads student list for the class/section
   - Populates existing attendance statuses
   - **Part 5e:** Loads existing DailyActivityLog records for the date

2. `markAllPresent()` / `markAllAbsent()`
   - Bulk toggles attendance status

3. `saveAttendance()`
   - Calls AttendanceService::markClassAttendance()
   - Passes markedBy = auth()->id()
   - Sends success notification with count

4. **saveActivityScores()** (PART 5e)
   - Validates all scores (0-10 range with min/max constraints)
   - Creates/updates DailyActivityLog records per student
   - Records participant (recorded_by), log_date, class_id, section_id
   - Sets participation_score, homework_score, behaviour_score
   - Database auto-computes total_score

5. `toggleActivityScores()`
   - Shows/hides activity score input section

#### **AttendanceSettingsResource**
- **File:** `/app/Filament/SchoolAdmin/Resources/AttendanceSettingsResource.php`
- **Navigation:**
  - Icon: `heroicon-o-clock`
  - Group: "Settings"
  - Sort: 25

**Form Schema:**

1. **Campus & Timing Section** (2 columns)
   - `campus_id` - Dropdown (nullable, searchable, "leave blank for school-wide default")
   - `grace_period_minutes` - Numeric (0-60, default 0)
   - `school_start_time` - Time picker (HH:MM format)
   - `school_end_time` - Time picker
   - `late_arrival_cutoff` - Time picker (required, "students arriving after this time are marked as late")
   - `half_day_cutoff` - Time picker (nullable)
   - `early_departure_cutoff` - Time picker (nullable)

2. **Notifications Section**
   - `notify_on_late_arrival` - Toggle (default: true)

**Table Display:**
- Columns: campus.name (default "School-Wide Default"), school_start_time, late_arrival_cutoff, grace_period_minutes, notify_on_late_arrival (icon)
- Actions: Edit, Delete

---

## DATABASE MIGRATIONS

### Migration Timeline (Chronological)

1. **2026_04_03_051_create_attendance_records_table.php**
   - Base attendance records table

2. **2026_04_03_052_enhance_attendance_and_add_staff_attendance_tables.php**
   - Enhanced attendance records with biometric integration
   - Adds staff attendance records

3. **2026_04_03_054_create_examination_tables.php**
   - Creates: exams, exam_schedules, exam_marks, grade_rules, exam_results

4. **2026_04_03_060_create_homework_and_notice_tables.php**
   - Creates: homework_assignments, homework_submissions, notices
   - Includes soft-deletes for homework

5. **2026_04_03_065_add_weightage_to_exams_table.php**
   - **CRITICAL:** Adds weightage support for weighted annual results
   - Adds to exams: weightage_percent, weightage_label, include_in_annual_result
   - Adds to exam_schedules: theory_weight, practical_weight, practical_full_marks, practical_pass_marks
   - Adds to exam_marks: practical_marks_obtained
   - Adds to exam_results: weighted_percentage

6. **2026_04_03_066_create_daily_activity_logs_table.php**
   - Creates daily_activity_logs (Part 5b)
   - Includes database-computed total_score column
   - Unique constraint on (student_id, subject_id, log_date)

7. **2026_04_03_067_create_attendance_settings_table.php**
   - Creates attendance_settings (Part 6a)
   - Supports per-campus and school-wide default configurations

---

## KEY INTEGRATION POINTS

### 1. Homework → Submission Flow
```
HomeworkAssignment (teacher creates)
    ↓
HomeworkSubmission (student submits)
    ↓
Teacher grades in FilamentUI (grade, feedback, graded_by, graded_at)
    ↓
Student views feedback (API endpoint or web UI)
```

**Key Entry Point:**
- Filament Resource: HomeworkAssignmentResource
- Pages: ListHomeworkAssignments, CreateHomeworkAssignment, EditHomeworkAssignment

### 2. Exam → Result → Report Card Flow
```
Exam created (with weightage_percent, include_in_annual_result)
    ↓
ExamSchedule created per subject/class
    ↓
Marks entered via MarksEntry page (ExamService::saveMarks)
    ↓
Results calculated (ExamService::calculateResults)
    ├─ Aggregate marks across subjects
    ├─ Calculate percentage & grade
    ├─ Apply weightage: (exam_contribution) + (daily_activity_contribution)
    ├─ Determine Pass/Fail/Absent status
    └─ Assign ranks within class
    ↓
ExamResult stored with weighted_percentage
    ↓
Report card displayed: ExamService::getStudentReportCard()
    ├─ Student info
    ├─ Overall result (percentage, grade, rank, status)
    └─ Subject-wise breakdown
```

**Key Components:**
- MarksEntry page (Livewire/Filament)
- ExamService (business logic)
- ExamResult model (storage)

### 3. Attendance → Activity Score → Exam Weightage
```
Student arrives (biometric punch)
    ↓
ProcessBiometricLogs job
    ├─ Creates/updates AttendanceRecord
    └─ Checks late arrival via AttendanceSetting::isLateArrival()
         └─ Dispatches NotifyLateArrival if enabled
    ↓
Teacher marks daily activity scores (MarkAttendance page, PART 5e)
    ├─ participation_score (0-10)
    ├─ homework_score (0-10)
    └─ behaviour_score (0-10)
         └─ Database computes total_score (0-30)
    ↓
DailyActivityLog stored
    ↓
Exam result calculation uses activity logs
    ├─ Average total_score per student
    ├─ Scale to 0-100 percentage
    └─ Apply activity_weight to final result
```

**Key Integration:**
- ProcessBiometricLogs job (Part 6d adds late arrival checking)
- AttendanceSetting (Part 6a stores attendance rules)
- MarkAttendance page (Part 5e adds activity score input)
- ExamService::calculateResults() (Part 5f incorporates activity scores)

### 4. Permission & Authorization
All Filament resources use Spatie Roles & Permissions:
- TEACHER role: Can create HomeworkAssignments, enter marks
- SCHOOL_ADMIN: Full access to all resources
- PARENT/STUDENT: Limited API access (read-only)

---

## FINDINGS & OBSERVATIONS

### Architecture Patterns

1. **Multi-Tenant Design**
   - Each school has its own database (tenant_per_database)
   - Shared app code uses tenant context via Filament

2. **ULID Identifiers**
   - All primary keys are ULIDs (32-char base32-encoded)
   - Better than auto-increment for distributed systems
   - Sortable & URL-safe

3. **Soft Deletes**
   - HomeworkAssignment uses soft-delete
   - Allows recovery of deleted assignments without losing submission history

4. **Event-Driven Processing**
   - ProcessBiometricLogs job (queued, scheduled every 15 min)
   - Handles async biometric log processing
   - Uses advisory locks to prevent duplicate calculations

5. **Weighted Calculations**
   - **Part 5a-5f:** Comprehensive weightage system for annual results
   - Exam weightage (e.g., Term 1 = 30%, Term 2 = 30%, Finals = 40%)
   - Activity score contribution (daily participation + homework + behaviour)
   - Formula: `(exam_percentage × exam_weight) + (activity_percentage × activity_weight)`

### Data Integrity

1. **Unique Constraints**
   - `homework_submissions(homework_id, student_id)` - one submission per student per homework
   - `exam_results(exam_id, student_id)` - one result per student per exam
   - `daily_activity_logs(student_id, subject_id, log_date)` - one log per student-subject per day

2. **Foreign Key Cascades**
   - Most foreign keys cascade on delete
   - Exception: graded_by in homework_submissions is "NULL ON DELETE" (preserves history)

3. **Advisory Locks**
   - PostgreSQL pg_try_advisory_lock() prevents concurrent exam result calculations
   - Throws RuntimeException if lock cannot be acquired

### Field Validation

1. **Marks Entry (MarksEntry Page)**
   - Validates marks are within 0 to full_marks range
   - Marks nullified for absent students
   - Handles decimal precision (6,2)

2. **Activity Scores (MarkAttendance Page)**
   - Participation, homework, behaviour: 0-10 range
   - Min/max constraints applied before save
   - Total auto-computed by database (0-30)

3. **Attendance Status**
   - Status enum: present, absent, late, half-day, sick, leave
   - Converted from biometric detection

### Performance Considerations

1. **Indexes**
   - homework_assignments(class_id, section_id, due_date)
   - daily_activity_logs(student_id, academic_year_id, log_date)
   - daily_activity_logs(class_id, section_id, log_date)
   - grade_rules(name, min_percentage)

2. **Lazy Loading**
   - `with()` relationships used in ExamService for batch loading
   - Avoids N+1 queries

3. **Window Functions**
   - PostgreSQL RANK() OVER for ranking
   - ROW_NUMBER() OVER for merit list
   - Deterministic, efficient sorting

### Security

1. **Approval Workflow**
   - ExamMark, HomeworkAssignment use `RequiresApproval` trait
   - Can be reviewed before finalization

2. **Role-Based Access**
   - Filament middleware ensures role-based resource access
   - Teachers can only manage their own assignments

3. **Audit Trail**
   - entered_by, recorded_by, graded_by fields track who modified records
   - Timestamps (created_at, updated_at, graded_at) for audit

### Extensibility

1. **Enums**
   - ExamStatus (Draft, Scheduled, Ongoing, Completed, Cancelled, etc.)
   - ExamResultStatus (Pass, Fail, Absent)
   - AttendanceStatus (Present, Absent, Late, Half-Day, Sick, Leave)

2. **Scopes**
   - Allow flexible querying: overdue(), upcoming(), passed(), failed(), ranked()

3. **Accessors**
   - Computed attributes (getPercentageAttribute, getIsPassAttribute)
   - Virtual fields without database storage

---

## CRITICAL FEATURES SUMMARY

### Homework System ✓
- Assignment creation with attachments
- Student submission tracking
- Teacher grading & feedback
- Overdue alerts (color-coded UI)

### Exam System ✓
- Multi-stage exam scheduling
- Bulk marks entry
- Automatic result calculation
- **Weighted percentages** (Part 5a-5f)
- **Activity score integration** (Part 5b, 5e-5f)
- Merit list generation
- Individual report cards

### Attendance System ✓
- Biometric integration (ZKTeco)
- Daily activity scoring (Part 5e)
- Late arrival notifications (Part 6d)
- **Per-campus attendance settings** (Part 6a)
- Configurable late cutoff & grace period

### Integration ✓
- Daily activity scores factored into exam weightage
- Attendance records auto-created from biometric logs
- Teachers can manually override/edit records

---

## RECOMMENDATIONS FOR FURTHER EXPLORATION

1. Check NotifyLateArrival job (referenced but not provided)
2. Review AttendanceService::markClassAttendance() method
3. Examine GradeRule::resolveGrade() logic
4. Check API endpoints (HomeworkController, AttendanceController, ResultController)
5. Review Filament resource pages for exam schedules and grade rules
6. Check how weighted_percentage is displayed on report cards

---

**END OF REPORT**
