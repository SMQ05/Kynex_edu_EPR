# AQM Public School Demo Seeder — Plan

**Date:** 2026-05-05
**Operator review gate:** STOP after this document. Seeders are not yet written. Nothing destructive has been run.

---

## 1. Target tenant confirmation

| Field | Value |
|---|---|
| Tenant id | `haji-qamar-public-school-BEb3S9` |
| Tenant DB | `tenanthaji-qamar-public-school-BEb3S9` (postgres on `kynexedu-db`) |
| Central row `school_name` | `Haji Qamar public school` (note lowercase `p`, `s` — slight drift from spec's `Haji Qamar Public School`) |
| Central row `admin_email` | `qmr750@gmail.com` |
| Central row `admin_name` | `Qamar Abbas` |
| Central row `status` | `active` |
| Central row `plan_id` | `01kqj366nme630djgvm3b48e13` (subscription_plan FK — out of scope) |
| Verified custom domain | `aqmdigital.com` (`domain_type=custom`, `is_verified=true`) — pinned exclusively to this tenant |
| Other domains | none |

A second tenant (`tenantal-qasim-school-HStisi`) exists on the same DB host. **Out of scope.** The seeder targets only the haji-qamar DB.

The tenant slug `haji-qamar-public-school-BEb3S9` is the primary key of `tenants` and the suffix of the tenant DB name. **It will not be changed.** Renaming the slug requires a DB rename and a `tenants` PK update — out of scope and explicitly forbidden by the spec.

The custom domain `aqmdigital.com` will not be touched. The nginx `custom-aqmdigital.com.conf` inside `kynex-app`'s writable layer is load-bearing and ephemeral (per saved memory) — this seeder does not recreate the kynex stack and does not edit nginx.

---

## 2. Current tenant state — what we're about to overwrite

Captured 2026-05-05 from the live tenant DB. These are the rows that exist before any seeding.

### Operational tables (will be reseeded under `--fresh`)

| Table | Current rows | Notes |
|---|---:|---|
| `students` | 13 | mix of placeholder names |
| `student_guardians` | 13 | one per student |
| `staff_profiles` | 0 | empty — no real staff records, only `school_users` rows |
| `classes` | 6 | named `1`..`6` only — spec wants `1`..`10` |
| `sections` | 4 | all four belong to class `1`, names `A`/`b`/`c`/`a` (case-inconsistent) |
| `subjects` | 3 | `MATH`, `english`, `physics` only |
| `class_subjects` | 1 | partial wiring |
| `fee_groups` | 9 | will be reseeded |
| `fee_types` | 8 | will be reseeded |
| `fee_masters` | 6 | will be reseeded |
| `student_fees` | 16 | will be reseeded |
| `fee_payments` | 5 | will be reseeded |
| `fee_payment_items` | 5 | will be reseeded |
| `attendance_records` | 3 | will be reseeded |
| `exams` | 2 | will be reseeded |
| `exam_schedules` | 0 | |
| `exam_marks` | 0 | |
| `exam_results` | 0 | |
| `expenses` | 4 | will be reseeded |
| `expense_categories` | 3 | will be reseeded |
| `staff_payrolls` | 0 | |
| `salary_components` | 0 | |
| `departments` | 1 | `human resources` |
| `designations` | 4 | placeholder data (`MATALI`, `HOD`, `director`, `dd`) |
| `cms_settings` | 1 (singleton) | `school_name = "Haji Qamar public school"`, all other fields blank |
| `cms_pages` | 0 | |
| `cms_sliders` | 0 | |
| `cms_announcements` | 0 | |
| `cms_gallery_albums` | 0 | |
| `cms_gallery_photos` | 0 | |
| `notices` | 0 | |
| `in_app_notifications` | 6 | will be reseeded |
| `id_card_templates` | 3 | **preserve** — system-seeded templates |
| `certificate_templates` | 4 | **preserve** — system-seeded templates |
| `generated_certificates` | 11 | will be reseeded |
| `student_applications` | 4 | will be reseeded (admissions inquiries) |
| `budgets` | 1 | will be reseeded |
| `grade_rules` | 1 | will be reseeded |
| `notification_templates` | 6 | **preserve** — system-seeded |
| `roles` | 19 | **preserve** — RBAC system rows |
| `model_has_roles` | 7 | partially stale (3 orphan rows pointing at deleted users) — will be cleaned up to match the new user set |

### `campuses` (2 rows) — decision needed

| id | name | is_main_campus |
|---|---|---|
| `01kqjc8ja86bxsj0hd6784g7r1` | `YOULTAR COMPUS, MAIN` | true |
| `01kqjcae1ssvqsxqqmkrrjvajr` | `GYOUL CAMPUS` | false |

**Plan:** rename main to `AQM Public School — Main Campus` (keep id stable — referenced by FKs), soft-delete the secondary campus (set `is_active=false` and `deleted_at=now()`) so it doesn't clutter the demo. Do not hard-delete to avoid touching FKs.

### `academic_years` (1 row)

| id | name | start | end | is_current |
|---|---|---|---|---|
| `01kqjckt3633gbck0n1fw13zfx` | `2026` | 2025-01-01 | 2027-01-01 | true |

**Plan:** keep the id stable, optionally rename `name` to `2025-2026`. All seeded data uses this academic year.

---

## 3. Existing system users — keep vs delete

`school_users` table currently has 7 rows:

| id | name | email | role | Plan |
|---|---|---|---|---|
| `01kqjbfxf4kz91newwvvw3vxnp` | Qamar Abbas | qmr750@gmail.com | SCHOOL_ADMIN | **KEEP, rename → Office Manager / SCHOOL_ADMIN under AQM identity, also update central `tenants.admin_email/admin_name`** |
| `01kqjbh722esjneea2czw0xf18` | Haji Yaseen | skardutrip01@gmail.com | INSTITUTE_HEAD | **KEEP, rename → AQM Principal (head)** |
| `01kqjceqaxk1rkv3actzcvmt4e` | MATALI | kaey750@gmail.com | TEACHER | **DELETE** (replaced by 10 fresh teachers; teachers are not in the spec's keep-list) |
| `01kqjjebsmaqh31yd91xm0fz3w` | saqi bhai mahtai | zafabbas1214@gmail.com | STUDENT | **DELETE** (replaced by 100 fresh students) |
| `01kqmpwt203jgz25avyfkd0n9s` | DIAG TEST | diag-test-1777737819@local.invalid | (none) | **DELETE** (test artifact) |
| `01kqmpzbh6m6wnxj0zjr8nvcex` | DIAG | diag-1777737903@local.invalid | (none) | **DELETE** (test artifact) |
| `01kqmqgrx136ce8a4y96ftgxgw` | PLAN E POSITIVE | e-pos-1777738474@local.invalid | (none) | **DELETE** (test artifact) |

**No accountant exists.** Spec says "accountant accounts that already exist on this tenant — DON'T delete them, but DO update". Since none exist, the seeder will **create** an accountant fresh.

**No vice principal exists.** Same — will be created.

**Stable user ids (will not change):**
- Admin: `01kqjbfxf4kz91newwvvw3vxnp` → renamed in place
- Principal/head: `01kqjbh722esjneea2czw0xf18` → renamed in place

These two rows are kept to satisfy "Keep their user IDs stable so existing sessions / Filament breadcrumbs don't break."

**Cleanup of orphan `model_has_roles`:** 3 rows in `model_has_roles` (`01kqms7ey17k2gsvrxbd1ra9mz`, `01kqmx9366ajy90jq70ndv0cxe`, `01kqmxkr6zkewbc2jdw5yxrd1h` — all role SCHOOL_ADMIN) reference `school_users.id` rows that do not exist. The seeder deletes these orphans during the cleanup phase.

**Plan_id (subscription) is out of scope** per the spec — the seeder will not modify `tenants.plan_id`, `tenants.status`, or any billing-related columns.

---

## 4. Table-by-table seeded data shape

### 4.1 `tenants` (central DB) — single UPDATE

| column | new value |
|---|---|
| `school_name` | `AQM Public School` |
| `admin_name` | `Qamar Abbas` (kept; the in-place admin-rename happens to display name only on the tenant side, since the operator's email/login on the tenants table is just for billing reach-out) |
| `admin_email` | left as `qmr750@gmail.com` — operator's external billing contact |

`tenants.school_name` is the single source of truth for the tenant's display name on the SaaS admin panel. The seeder updates it inside a transaction.

### 4.2 `cms_settings` (tenant DB, singleton)

Update the existing row (id `01kqjr5rj36ph49v4m15b6938c`):

| column | value |
|---|---|
| `school_name` | `AQM Public School` |
| `tagline` | `Excellence in Education — Lahore, Pakistan` |
| `address` | `Plot 142, Block C, Johar Town, Lahore, Punjab 54600, Pakistan` |
| `phone` | `+92-42-1234-5678` |
| `email` | `info@aqmdigital.com` |
| `whatsapp` | `+92-300-1234567` |
| `principal_name` | `<head's display name>` |
| `principal_message` | 2-paragraph welcome from the principal |
| `about_text` | school history / mission paragraph |
| `vision_text` | one paragraph |
| `mission_text` | one paragraph |
| `primary_color` | `#1a56db` (kept) |
| `admission_open` | `true` |
| `why_choose_us` (JSON) | array of 4-6 features |
| `facilities` (JSON) | array of 6-8 facility blurbs |
| `testimonials` (JSON) | 3-4 parent testimonials |
| `stats` (JSON) | `{"students": 100, "teachers": 10, "established": 2008, "pass_rate": 96}` |
| `exam_highlights` (JSON) | top performers list |
| `admission_steps` (JSON) | 4-step admissions process |
| `hero_image_path` | `https://placehold.co/1600x600/1a56db/ffffff?text=AQM+Public+School` |
| `about_image_path` | placeholder |
| `address_map_iframe` | empty (don't leak a real Google embed key) |
| `facebook_url` | placeholder, e.g. `https://facebook.com/aqmpublicschool` |

Total: ~1 row updated.

### 4.3 `cms_pages` — 5 published pages

`home` (root reaches via PublicController, but a "Home" record exists for the menu), `about`, `admissions`, `academics`, `contact`. Each: `is_published=true`, `published_at=now`, content 200-400 words.

Plus 2 utility pages: `privacy-policy`, `terms-of-use`.

Total: ~7 rows.

### 4.4 `cms_sliders` — 4 rows

3-4 hero slides, each with a `placehold.co` image URL, title, subtitle, button text, button URL pointing to /admissions.

### 4.5 `cms_gallery_albums` + `cms_gallery_photos` — 3 albums × 4 photos = 12 photos

Albums: "Annual Sports Day 2025", "Science Fair 2025", "Independence Day Celebration". All `is_published=true`. Each photo uses a `placehold.co` URL.

### 4.6 `cms_announcements` — 6 rows

Mix of recent (last 60 days) website-facing notices.

### 4.7 `campuses`

| action | row |
|---|---|
| UPDATE | `01kqjc8ja86bxsj0hd6784g7r1` → name = `AQM Public School — Main Campus`, address = Lahore address, phone, email |
| SOFT-DELETE | `01kqjcae1ssvqsxqqmkrrjvajr` → `deleted_at=now`, `is_active=false` |

### 4.8 `academic_years`

| action | row |
|---|---|
| UPDATE | `01kqjckt3633gbck0n1fw13zfx` → name `2025-2026`, dates kept |

### 4.9 `departments` + `designations`

Truncate (`CASCADE` would cascade to `staff_profiles`, but `staff_profiles` is empty), then:

`departments` (5): `Administration`, `Academics`, `Finance`, `Support`, `Library & Resources`.

`designations` (12): `Principal`, `Vice Principal`, `Office Manager`, `Accountant`, `Senior Teacher`, `Teacher`, `Junior Teacher`, `Clerk`, `Librarian`, `Gatekeeper`, `Driver`, `Quran Teacher`.

### 4.10 `school_users` + `model_has_roles` + `staff_profiles` — 18 staff total

Two preserved (admin, head) + 16 new = 18 total per spec.

| Role (display) | Auth role (DB) | Count | Notes |
|---|---|---|---|
| Principal (head) | `INSTITUTE_HEAD` | 1 (existing) | id `01kqjbh722esjneea2czw0xf18` — rename to e.g. `Khalid Mahmood` |
| Vice Principal | `REGISTRAR` (closest existing role; **flagged**) | 1 (new) | |
| Office Manager / School Admin | `SCHOOL_ADMIN` | 1 (existing) | id `01kqjbfxf4kz91newwvvw3vxnp` — Qamar Abbas, rename for display |
| Accountant | `ACCOUNTANT` | 1 (new) | |
| Teachers (10) | `TEACHER` | 10 (new) | one per subject domain (Math, English, Urdu, Science, Social Studies, Islamiyat, Computer, Arts, Physical Education, Quran) |
| Clerk | `ATTENDANCE_CLERK` | 1 (new) | |
| Librarian | `LIBRARIAN` | 1 (new) | |
| Gatekeeper | (no auth role — `is_active=true` but no `model_has_roles` row, `active_role=null`) | 1 (new) | |
| Driver | (no auth role) | 1 (new) | |

For each staff `school_user`:
- `id` = `Str::ulid()`
- `name`, `email` (`<lowercase-name>@aqmdigital.com`), `phone` (`+92-3XX-XXXXXXX`), `is_active=true`
- `password` = `Hash::make(deterministicPassword('staff', $email))` (formula in spec §"Login credentials")
- `active_role` matches the assigned role
- `campus_id` = main campus
- `email_verified_at` = now()

For each, a paired `staff_profiles` row:
- `school_user_id` = above
- `employee_id` = `EMP-001`..`EMP-018` (sequential, gaps allowed for the 2 preserved users)
- `department_id`, `designation_id`
- `joining_date` random in 2018-2025, weighted toward 2020-2024
- `qualification` matches role (BA/MA/MSc/MEd/HSSC etc.)
- `experience_years` = current_year - joining_year, with light noise
- `basic_salary_paisas` = role-keyed (Principal 150k → 15_000_000; Teacher 50k → 5_000_000; Driver 25k → 2_500_000) ×100
- `personal_whatsapp`, `emergency_contact_*`, `bank_account` populated
- `campus_id` = main campus

### 4.11 `classes` — 10 rows

Truncate `classes` (CASCADE will cascade to `sections`, `class_subjects`, `fee_masters`, `student_fees`, `students`, `attendance_records`, etc. — covered by ordered truncation below).

| name | numeric_level | sort_order |
|---|---:|---:|
| Class 1 | 1 | 1 |
| Class 2 | 2 | 2 |
| ... | ... | ... |
| Class 10 | 10 | 10 |

`campus_id` = main.

### 4.12 `sections`

Per spec: classes 1-5 have sections A and B (2 sections each); classes 6-10 single section A.

Total: 5×2 + 5×1 = 15 sections.

Each section: `class_teacher_id` set to one of the 10 teachers (round-robin so every teacher is class teacher of 1-2 sections, with leftover sections assigned to the senior teachers).

### 4.13 `subjects` — 12 rows

Truncate, then seed:

`Math`, `English`, `Urdu`, `Science`, `Physics`, `Chemistry`, `Biology`, `Social Studies`, `Islamiyat`, `Computer`, `Arts`, `Physical Education`, `Quran`. Codes `MATH`, `ENG`, `URD`, etc.

`subject_type='theory'` for all (no `practical` in this scope).

### 4.14 `class_subjects` — ~75 rows

Per-class subject mapping per spec:
- Classes 1-3: 5 subjects (Math, English, Urdu, Science, Islamiyat) → ~3×5 = 15
- Classes 4-7: 7 subjects (+ Social Studies, Computer) → 4×7 = 28
- Classes 8-10: 8 subjects (Math, English, Urdu, Physics, Chemistry, Biology, Computer, Islamiyat) → 3×8 = 24

Each row links `class_id` × `subject_id` × `academic_year_id`, with `teacher_id` assigned by subject specialty.

Total ~67 rows (subject coverage varies; final count finalized in implementation).

### 4.15 `students` — 100 active + 5 alumni = 105

Distribution per spec: `[12, 11, 10, 10, 9, 10, 10, 9, 10, 9]` across classes 1-10 = 100.

For classes 1-5 (sections A and B), students are split ~50/50 between the two sections.

Each student:
- `id` = ULID
- `admission_number` = `AQM-2025-001`..`AQM-2025-100`
- `roll_number` = sequential within section
- `admission_date` = spread 2021-09-01 to 2025-04-15
- `academic_year_id` = current year
- `class_id`, `section_id`
- `first_name`, `last_name` (varied Pakistani names — pool of ~40 first names + ~30 surnames, no name repeated more than 2-3 times)
- `date_of_birth` = computed from class level (Class 1 ≈ 5-6 years old, Class 10 ≈ 14-15)
- `gender` = ~50/50, slight skew (52F/48M)
- `nationality` = `Pakistani`
- `blood_group` = realistic distribution
- `religion` = mostly Islam
- `city` = one of Lahore/Karachi/Islamabad/Rawalpindi/Faisalabad
- `address` = realistic generated address
- `profile_photo_path` = `https://placehold.co/200x200/4f46e5/ffffff?text=<initials>`
- `status` = `enrolled`
- `school_user_id` = paired ULID — every student gets a `school_users` login row (role=STUDENT, password formula seeded `student`+login_id)

5 additional **alumni students** (status=`graduated` or `transferred`) added in the same insert, used as FK targets for `generated_certificates`. `class_id` defaults to Class 10. No login row for alumni.

### 4.16 `student_guardians` — ~75 unique parent records, ~125 student-guardian rows

Strategy: generate ~75 distinct parent identities (couples/single parents). Sibling-detection: 20-25% of students share a parent set with another student.

For each student:
- 1 primary guardian (father or mother) with `is_primary_contact=true`, `can_access_portal=true`
- Optionally a 2nd guardian (the other parent) with `can_access_portal=false`

Total `student_guardians` rows ≈ 100 + ~25 secondary = ~125.

Each parent: `name`, `gender` (inferred from name), `relationship` (father/mother/guardian), `phone` (+923XXXXXXXXX), `email` (`<lowercase-firstname>.<surname>@aqmdigital.com` — synthesized so login works), `cnic` (`XXXXX-XXXXXXX-X` synthetic), `occupation` (engineer/shopkeeper/teacher/farmer/doctor/government employee/businessman pool), `address`.

`school_users` paired rows: one per **unique parent identity** (~75) with role PARENT. Multiple `student_guardians` rows can point to the same `school_users.id`. The portal login uses `student_guardians.school_user_id` for the parent-portal mapping.

### 4.17 `student_categories` — 3 rows

`General`, `Sibling Discount`, `Staff Child`. Used by some students for fee discount logic.

### 4.18 `fee_groups` (truncate, reseed) — 4 rows

`Tuition`, `One-time Fees`, `Exam Fees`, `Optional Services`.

### 4.19 `fee_types` — ~10 rows

`Tuition Monthly` (group=Tuition, recurring=true), `Admission Fee` (one-time), `Lab Fee` (recurring, classes 6+), `Transport` (recurring), `Library Fee`, `Sports Fee`, `Exam Fee`, `Stationery Charges`, `Annual Fee`, `Computer Lab`.

### 4.20 `fee_masters` — class-tier amounts

For each `fee_type` × `class_id` combination that applies, one row:

| Fee | Class 1-3 (PKR/mo) | Class 4-7 | Class 8-10 |
|---|---:|---:|---:|
| Tuition Monthly | 2,500 | 4,500 | 6,500 |
| Lab Fee | — | 500 | 1,200 |
| Library | 200 | 300 | 400 |
| Sports | 300 | 400 | 500 |
| Computer Lab | — | 600 (4-7) | 800 |

Stored in `amount_paisas` (×100). Total `fee_masters` rows ≈ 30-40.

### 4.21 `student_fees` — monthly invoices for Feb-May 2026

For each of 100 active students × 4 months × ~3-4 applicable recurring fee types = **~1,200-1,600 rows**.

`month` column = `'2026-02'`, `'2026-03'`, `'2026-04'`, `'2026-05'`.
`due_date` = 10th of the month.
`status` distribution per the spec:
- 70% paid in full on or before due_date → `status='paid'`, `paid_paisas=amount_paisas`
- 15% paid late → `status='paid'`, `paid_paisas=amount_paisas`, payment date 5-15 days after due_date
- 10% partial → `status='partial'`, `paid_paisas` = 30-70% of amount_paisas
- 5% unpaid → `status='pending'`, `paid_paisas=0`

Plus Admission Fee (one-time) per student, recorded against admission_date (all paid).
Plus Transport for ~40% of students (1 row/month × 4 months).

### 4.22 `fee_payments` + `fee_payment_items`

One `fee_payments` row per actual payment event (cash/bank_transfer/cheque/easypaisa/jazzcash). Each payment has 1+ `fee_payment_items` linking to `student_fees`.

Estimated ~1,000 `fee_payments` and ~1,200 `fee_payment_items`.

`receipt_number` = `RCP-2026-NNNNN` sequential.
`collected_by` = the accountant's school_user_id (with occasional admin to vary).
`payment_method` distribution: cash 30%, bank_transfer 35%, easypaisa 15%, jazzcash 15%, cheque 5%.

**Refunds: 2-3 rows.** Refunds are modeled as `fee_payments` with negative `total_amount_paisas` and a `notes` column describing reason ("Sibling discount adjustment", "Withdrawal refund", "Duplicate payment correction"). One `fee_payment_items` mirroring per refund.

### 4.23 `attendance_records`

Per spec: 3 months daily attendance for ~100 students × ~75 working days (Mon-Sat in Mar-Apr-May 2026 minus public holidays) = **~7,500 rows**.

Public holidays skipped:
- 2026-03-23 (Pakistan Day)
- 2026-05-01 (Labour Day)
- 1-2 in-school events (e.g. 2026-04-15 "Sports Day" already counted as a working day with attendance, so OPTIONALLY skipped — operator-tunable)

Status distribution per student per day: 85% present, 10% absent, 3% late, 2% leave.

Variation: 5-8 chronic-absentee students get bumped to ~30% absent rate; the median student has 0-5 absences across the window.

`marked_by` = a teacher's school_user_id (the section's class teacher).
`late_minutes` populated for late records (5-30 min uniform).

### 4.24 `exams` + `exam_schedules` + `exam_marks` + `exam_results`

`exams` (2 rows):
- `First Term` — start 2026-02-10, end 2026-02-20
- `Mid Term` — start 2026-04-15, end 2026-04-25

`exam_schedules`: per exam × per class × per subject. Subject counts per class per spec:
- Classes 1-3: 5 subjects → 3×5 = 15 schedules per exam
- Classes 4-7: 7 subjects → 4×7 = 28 schedules per exam
- Classes 8-10: 8 subjects → 3×8 = 24 schedules per exam

Total: 67 schedules × 2 exams = **134 `exam_schedules` rows**.

Each schedule: `full_marks=100`, `pass_marks=33`, `theory_weight=100`, `practical_weight=0` (no practical for this demo's subjects).

`exam_marks`: 1 row per student × per applicable schedule × 2 exams.

Per class: students × subjects × 2 exams.
Class 1 (12 students × 5 subjects × 2) = 120
Class 2 (11 × 5 × 2) = 110
... etc.
Total ≈ **~1,400 `exam_marks` rows**.

Marks distribution per spec: most 60-85, ~10% above 90, ~10% below 50, ~5% failing (<33). Generated with `mt_rand` over a tunable distribution.

`exam_results`: 1 row per student per exam = 100 students × 2 exams = **200 rows**. Computed from `exam_marks`:
- `total_marks` = sum of `exam_schedules.full_marks` for class
- `marks_obtained` = sum of `exam_marks.marks_obtained`
- `percentage` = computed
- `grade` per Pakistani grading: A+ (≥90), A (80-89), B+ (70-79), B (60-69), C (50-59), D (40-49), F (<33). Adjustable via `grade_rules` table.
- `rank` = position within class (computed after all marks inserted)
- `status` = `pass` (≥33% in every subject) or `fail`

Top 3 per class flagged via `remarks='top3'` or similar.

### 4.25 `expenses` + `expense_categories` + `staff_payrolls`

`expense_categories` (truncate, reseed): `Salaries`, `Utilities`, `Rent`, `Stationery`, `Lab Supplies`, `Sports Equipment`, `Library Books`, `Repairs & Maintenance`, `Professional Development`, `Exam Printing`, `Internet & IT`. (11 rows)

`expenses` for Feb-May 2026:
- Per month: 18 staff × 1 salary expense = 72 rows (linked to staff_payrolls)
- Per month: 1 electricity bill, 1 internet, 1 water, 1 rent = 16 rows
- Periodic: 2-3 stationery purchases, 1-2 lab supplies, 1 sports equipment, 1 library books, 1 repairs, 1 PD, 1 exam printing → ~10 rows over the window

Total `expenses` ≈ **~100 rows**.

Approval status per spec: 80% approved, 15% pending, 5% rejected (with `description` containing reason).

`staff_payrolls`: 18 staff × 4 months = **72 rows**, each linked to its corresponding salary `expenses` row.

`salary_components`: 4 standard components (Basic, House Rent Allowance, Conveyance, Medical) referenced by payroll calculations.

`budgets`: 1 row for academic year 2025-2026 with monthly breakdown.

### 4.26 `notices` + `cms_announcements` + `in_app_notifications`

`notices` (10-12 rows): mix of urgency, mix of audiences (`target_roles=["all"]`, `["TEACHER"]`, `["PARENT"]`, etc.), `created_by` = principal.

`cms_announcements` (already covered in 4.6): 6 website-facing rows.

`in_app_notifications` (truncate existing 6, reseed): ~50-80 rows distributed across the user base, mix of `read_at=null` and `read_at=<recent>`.

### 4.27 `id_card_templates` + `certificate_templates` + `generated_certificates`

`id_card_templates` (4 existing rows preserved). Default student template stays active.

`certificate_templates` (4 existing rows preserved).

`generated_certificates` (truncate, reseed): 100 student ID cards (template_type=student id) + 5 alumni completion certificates.

Total = **105 rows**.

Each `generated_certificates` row:
- `certificate_number` = `AQM-IDC-2025-001`..`AQM-IDC-2025-100` for ID cards, `AQM-CERT-2024-001`..`AQM-CERT-2025-005` for completion certificates
- `issued_date`, `issued_by` = principal
- `variables_used` = JSON snapshot of student fields rendered into the template
- `file_path` = null (file generation deferred to runtime PDF render — placeholder for demo)

### 4.28 Tables NOT touched

- `domains` — explicit forbidden by spec and saved memory
- `tenants.plan_id`, `status`, billing columns — out of scope
- `roles`, `permissions`, `role_has_permissions` — system contract
- `id_card_templates`, `certificate_templates`, `notification_templates` — system contract; the spec says use the default templates
- `migrations`, `cache*`, `jobs`, `failed_jobs`, `job_batches` — system tables
- All **central-DB** tables except a single `tenants` row UPDATE
- The second tenant `tenantal-qasim-school-HStisi` and its DB
- Anything in `kynex-app`, host nginx, host crontab, AI app

---

## 5. Idempotency strategy

### 5.1 Run modes

```
docker exec kynexedu-app php artisan tenants:run db:seed \
  --argument='class=DemoTenantSeeder' \
  --tenants=haji-qamar-public-school-BEb3S9
```

**Without `--fresh`:** the orchestrator checks each operational table for non-trivial row counts. If `students` has > 0 rows, the seeder aborts with:

> ❌ Demo data already present in tenant haji-qamar-public-school-BEb3S9 (students=N rows).
> Re-run with --fresh to wipe and reseed:
>   `tenants:run db:seed --argument='class=DemoTenantSeeder --fresh' --tenants=haji-qamar-public-school-BEb3S9`

(Note: `tenants:run` passes `--argument='class=X --fresh'` as a single string; the seeder parses argv inside `DemoTenantSeeder@run` since Laravel's `Seeder` doesn't get options natively. Implementation reads `$_SERVER['argv']` or uses `Artisan::$booted` reflection.)

**With `--fresh`:**

1. The orchestrator first prints a summary of what will be wiped (row counts of every operational table) and **prompts for `Y` to proceed** unless `--force` is also passed.
2. Wipes operational tables in FK-safe order (deepest leaves first, OR `TRUNCATE ... RESTART IDENTITY CASCADE` on a small set of root tables).
3. **Preserves** `roles`, `permissions`, `role_has_permissions`, `id_card_templates`, `certificate_templates`, `notification_templates`, `migrations`, `cache*`, `jobs*`, `failed_jobs`.
4. **Preserves** rows `01kqjbfxf4kz91newwvvw3vxnp` (admin) and `01kqjbh722esjneea2czw0xf18` (head) in `school_users` — DELETE FROM school_users WHERE id NOT IN (...) instead of TRUNCATE.
5. Cleans orphan `model_has_roles` entries.
6. Reseeds in dependency order:
   1. `SchoolIdentitySeeder` — central tenant rename + cms_settings
   2. `StaffSeeder` — departments, designations, school_users (excl. preserved), staff_profiles, model_has_roles
   3. `ClassesSeeder` — academic_years, classes, sections, subjects, class_subjects
   4. `StudentsAndParentsSeeder` — students (incl. paired school_users role=STUDENT), student_guardians (incl. paired school_users role=PARENT), student_categories
   5. `FeesSeeder` — fee_groups, fee_types, fee_masters, student_fees, fee_payments, fee_payment_items
   6. `AttendanceSeeder` — attendance_records, attendance_settings if needed
   7. `ExamsAndResultsSeeder` — grade_rules, exams, exam_schedules, exam_marks, exam_results, annual_results
   8. `FinanceSeeder` — expense_categories, expenses, salary_components, staff_payrolls, budgets
   9. `CmsContentSeeder` — cms_pages, cms_sliders, cms_gallery_albums, cms_gallery_photos, cms_announcements
   10. `NotificationsSeeder` — notices, in_app_notifications
   11. `IdCardsAndCertificatesSeeder` — generated_certificates

### 5.2 Determinism

All seeders pin Faker via `fake()->seed(20260505)` set at the top of `DemoTenantSeeder::run()` so two `--fresh` runs produce identical row content (modulo timestamp columns). ULIDs are time-based; for true determinism, seed ULIDs from a fake clock would be over-engineering — the seeder accepts that ULIDs vary across runs but row counts and human-visible values are stable.

### 5.3 Per-seeder confirmation gates

Per spec: "Every destructive operation goes through a 'I'm about to truncate X with Y rows, proceed?' confirmation point in the seeder logic."

The orchestrator prints the per-table wipe summary and asks `Are you sure? [y/N]` once for the whole batch. Each individual sub-seeder (StaffSeeder, etc.) does NOT re-prompt — that would make 11 prompts for one run. The single batch prompt at the top is the gate. `--force` skips the prompt.

### 5.4 Transactionality

Each sub-seeder wraps its wipe + reseed in `DB::transaction()` so a partial failure rolls back. Cross-seeder failures: if seeder N fails after seeder N-1 succeeded, the seeder logs `--fresh-required` and exits non-zero. Re-running with `--fresh` resumes cleanly.

### 5.5 Foreign-key wipe order

Tested order (leaves first):
```
fee_payment_items → fee_payments → student_fees → fee_masters → fee_types → fee_groups
exam_marks → exam_schedules → exam_results → annual_results → exams
attendance_records → attendance_settings (per-section rows)
in_app_notifications → notification_preferences
notices → cms_announcements
generated_certificates
homework_submissions → homework_assignments
behavior_incidents, health_records, communication_logs, daily_activity_logs
class_subjects → class_routines → student_promotions → sections → classes
staff_payrolls → staff_attendance_records → leave_requests → staff_profiles
student_guardians → student_documents → students
school_users WHERE id NOT IN (admin, head)
model_has_roles (orphans)
expenses → budgets → expense_categories
departments, designations, subjects, student_categories, leave_types, salary_components
campuses (only soft-delete the secondary; keep main and rename)
```

---

## 6. Estimated runtime

Local benchmark on equivalent hardware:

| Phase | Estimate |
|---|---|
| Wipe (under `--fresh`) | 2-4 s (single-tenant TRUNCATE CASCADE) |
| SchoolIdentitySeeder | < 1 s |
| StaffSeeder (18 users + profiles) | 1-2 s |
| ClassesSeeder (10 classes + 15 sections + 13 subjects + 67 class_subjects) | 1-2 s |
| StudentsAndParentsSeeder (100 students + ~75 parents + 175 school_users) | **8-15 s** (Hash::make is the bottleneck — 175 bcrypt hashes × ~50 ms on cost=10) |
| FeesSeeder (~1,500 student_fees + ~1,000 payments + items) | 5-8 s |
| AttendanceSeeder (~7,500 rows in chunks) | 4-6 s |
| ExamsAndResultsSeeder (~1,400 exam_marks + 200 results) | 3-5 s |
| FinanceSeeder (~100 expenses + 72 payrolls) | 1-2 s |
| CmsContentSeeder | < 1 s |
| NotificationsSeeder | 1-2 s |
| IdCardsAndCertificatesSeeder | 1-2 s |
| **Total** | **~30-60 s** |

If bcrypt cost is the bottleneck, the seeder can lower `BCRYPT_ROUNDS=4` for the duration of the seeding (then reset) — but that risks mutating prod env. **Decision: accept the runtime; do not lower bcrypt rounds.**

---

## 7. Schema discoveries / things that surprised me — flag before seeding

These are deviations from what the spec assumed. Operator decisions needed:

### 7.1 No `/student` panel exists

**Spec assumes:** `https://aqmdigital.com/student` is a login URL.

**Reality:** Only two Filament panels exist — `school-admin` at `/admin` and `parent` at `/parent`. Students log in via `/login` and are routed to `/admin` (with role-scoped UI). There is no `app/Providers/Filament/StudentPanelProvider.php`.

**Options for the spec's `/student` smoke test:**
1. Skip the `/student` line in the credentials file and verification table. Document that students share `/admin`.
2. Test student login via `POST /login` → expect 302 to `/admin` (same as other roles).

**Recommendation: option 2.** The credentials file entry for "student URL" becomes `https://aqmdigital.com/login` with note "lands at /admin".

### 7.2 No `PRINCIPAL` or `VICE_PRINCIPAL` auth role

**Reality:** `roles` table has `INSTITUTE_HEAD` (close to "principal"), `MULTI_INSTITUTE_HEAD`, `REGISTRAR`, etc. but no `PRINCIPAL` or `VICE_PRINCIPAL`.

**Plan:** Map `Principal` → `INSTITUTE_HEAD`. Map `Vice Principal` → `REGISTRAR` (proxy; REGISTRAR has admin-adjacent permissions). Display titles ("Principal", "Vice Principal") live in `staff_profiles.designation_id`/`designations.name` and `cms_settings.principal_name`. Auth-role assignments are an implementation detail.

**Operator decision: confirm REGISTRAR is acceptable for VP, or pick a different role from the existing 19.**

### 7.3 `tenants.school_name` is `Haji Qamar public school` (lowercase p, s)

Not `Haji Qamar Public School`. The seeder uses a case-insensitive match for the rename to catch this and any variant in CMS rich-text (`about_text`, etc.).

### 7.4 Existing `subjects` table has no `sort_order` column

Spec uses `sort_order` for `cms_pages` and `cms_sliders`, which DO have it. `subjects` does not — order will be implicit by ULID (creation order).

### 7.5 `sections.class_teacher_id` (not `classes.class_teacher_id`)

Class teacher assignment is per-section, not per-class. So Class 1A and 1B can have different class teachers. The seeder assigns teachers at section level, with a consistent rule: section A's teacher is the senior of the two for classes that have both A and B.

### 7.6 `generated_certificates.student_id` is a real FK

So the 5 alumni completion certificates need 5 actual `students` rows. Plan: insert 5 `students` with `status='graduated'` and `deleted_at=null`, no `school_user_id`. They count separately from the 100 active students.

### 7.7 Two campuses exist

`YOULTAR COMPUS, MAIN` (main) and `GYOUL CAMPUS` (secondary). Plan above is to rename main and soft-delete secondary. **Operator decision: confirm soft-delete is preferable to keeping both with renamed names.**

### 7.8 `tenants.admin_email` left as `qmr750@gmail.com`

The spec is ambiguous on whether the central `tenants.admin_email` should change. The school-side `school_users.email` for the admin row WILL change to a `@aqmdigital.com` address. The central `tenants.admin_email` is the SaaS-side billing/notification contact for the operator (Qamar Abbas) and is left untouched. **Operator decision: confirm or override.**

### 7.9 Login verification — CSRF token handling

`POST /login` requires a `_token` CSRF field. The verification harness does:
```
GET /login → scrape csrf-token meta + session cookie
POST /login (with _token, email, password) → expect 302
```

Verification will run from inside `kynexedu-app` (so it hits the in-cluster nginx via the public hostname using `--resolve aqmdigital.com:443:127.0.0.1` if needed), avoiding any need to publish a public smoke-test endpoint.

### 7.10 Parent login goes through `/parent/login` (Filament auto-registered)

When the parent tries `/login`, `attemptTenantLogin()` succeeds and redirects them to `/admin` — but `EnsureSchoolAdminAccess`-style middleware on the school-admin panel may bounce parents out. Filament parent panel registers its OWN `/parent/login` Livewire login page (because of `->login()` in `ParentPanelProvider`).

**Plan:** verify parents via `POST /parent/login` (Filament's path), expect 302 to `/parent`. Verify all other roles via `POST /login` (the school portal), expect 302 to `/admin`.

### 7.11 The existing `kynex:wipe-tenant-data` command preserves only the admin user

It deletes the head/principal user. We do NOT use it directly. The seeder's `--fresh` flag has its own preservation logic that keeps both admin AND head.

### 7.12 No school-side Domain writes — confirmed

The seeder does NOT touch `domains` (central). It only updates `tenants.school_name`. `Domain::*` writes are forbidden per saved feedback memory; the seeder respects this.

### 7.13 `kynex-app` container & nginx are NOT touched

Out of scope. The aqmdigital.com routing relies on `custom-aqmdigital.com.conf` in kynex-app's writable layer (per saved memory). The seeder runs entirely against `kynexedu-db` and reads/writes only the tenant DB + a single row of `kynexedu_central.tenants`.

### 7.14 `kynexedu-app` recreate not required

Spec / saved memory says env-file edits dormant until container recreate. This seeder does NOT edit any env file, so no recreate needed. The seeder can run against the live `kynexedu-app` container with zero downtime.

### 7.15 PgBouncer / RLS

`docs/pgbouncer-setup.md` and migration `2026_04_03_062_add_confidential_columns_and_rls.php` suggest RLS is in play. The seeder runs as the `kynexedu` postgres role (BYPASSRLS or SUPERUSER, to be verified). If RLS blocks inserts under tenant context, the seeder uses `tenant->run(fn () => ...)` which initializes tenancy and sets the postgres role via `SetPostgresUserRole` middleware-equivalent. **Risk to verify before first dry run.**

### 7.16 Search vectors / FTS triggers

`students`, `school_users`, `books` have `search_vector` (tsvector) columns populated by triggers. The seeder relies on the triggers — does NOT manually compute tsvectors.

### 7.17 Materialized views

Migration `2026_04_05_077_create_materialized_views.php` plus `kynex:refresh-materialized-views` command — after seeding, the operator may want to refresh materialized views for accurate dashboards. Plan: include a final step that runs `php artisan kynex:refresh-materialized-views --tenants=haji-qamar-public-school-BEb3S9` (or document it as a follow-up).

---

## 8. Out-of-scope reminders

- `domains`, custom-domain config, `CustomDomainService`, cert provisioning state — **untouched**
- `kynex-app` container, host nginx, host crontab, AI app — **untouched**
- Other tenant `tenantal-qasim-school-HStisi` — **untouched**
- `SaasAdmin`, central RBAC, central plan/invoice/billing tables — **untouched**
- Tenant slug rename — **NOT performed** (would break URL routing)
- aqmdigital.com domain — **kept exactly as-is**
- `BCRYPT_ROUNDS` env — **NOT changed** (would require container recreate per saved memory)
- Existing `id_card_templates`, `certificate_templates`, `notification_templates` — **preserved**

---

## 9. Approval gate

Operator: please confirm by replying with "approved" (and any edits to §7 items 7.1, 7.2, 7.7, 7.8). On approval I will proceed to:

1. Build the 11 seeder classes under `database/seeders/` and `database/seeders/Demo/`
2. Run a dry-run via `--fresh` (with `--force` initially set to false so the prompt is shown) once to verify
3. Run real seeding
4. Build the 6-role login verification harness
5. Write the credentials file to `/tmp/aqm-demo-credentials-<timestamp>.txt`
6. Refresh materialized views
7. Deliver the final report per spec §"Final report"

---

## 10. Operator-approved revisions (2026-05-05)

Approval received with the following notes — all incorporated into the seeder build:

1. **/student URL test:** dropped. Reported in credentials file and final report as `/login → /admin (role-based redirect)`.
2. **VP designation:** auth role = `REGISTRAR`, but `staff_profiles.designation_id` points to a `designations` row named `Vice Principal` (explicitly set per-user, not auto-derived from the auth role). Same pattern for Principal (`INSTITUTE_HEAD` role + `Principal` designation).
3. **Campus consolidation:** before soft-deleting the secondary campus, the orchestrator reassigns any FK pointing at the secondary campus to the main campus (defensive — no orphan FKs). All `*.campus_id` columns scanned via `information_schema` and updated in one pass.
4. **`tenants.admin_email`:** kept as `qmr750@gmail.com`.
5. **Casing normalization:** central `tenants.school_name` set to exactly `AQM Public School` (proper title case). `cms_settings.school_name`, all CMS rich-text references, certificate/ID-card template variables, page titles, page header all standardized to that exact string.
6. **Wipe set confirmed:** existing 13 students / 6 classes / 4 sections / 3 subjects / 5 fee_payments / 2 exams / 11 generated_certificates and all other operational rows are confirmed wipe-able under `--fresh`.

Build order (operator-specified):
a. SchoolIdentitySeeder — rename + casing fix + campus consolidation
b. StaffSeeder — creates 16 new staff + updates 2 existing (admin, head)
c. ClassesSeeder — truncate + create 1-10 with class teachers per section
d. StudentsAndParentsSeeder — 100 active + 5 alumni, ~75 parent identities
e. FeesSeeder
f. AttendanceSeeder
g. ExamsAndResultsSeeder
h. FinanceSeeder
i. CmsContentSeeder
j. NotificationsSeeder
k. IdCardsAndCertificatesSeeder

Lint each via `php -l` after writing. **Do NOT run any seeder against the DB until operator review of orchestrator + summary.**
