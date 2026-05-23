# KynexEdu — Complete Feature Inventory

> Generated 2026-05-19. Catalogues every Resource, Page, Widget, Service, Job, Command, Route, and cross-cutting system in the platform. Use this as the authoritative "what does the product do" reference.

---

## 1. Platform Architecture (at a glance)

KynexEdu is a multi-tenant school-management SaaS built on Laravel + Filament v3, using `stancl/tenancy` for hard tenant isolation (separate Postgres database per school).

- **Central database** holds `tenants`, `domains`, `subscription_plans`, `invoices`, `approval_requests`, `audit_runs`, `audit_findings`, `device_tokens`, `school_invitations`, `tenant_signups`.
- **Per-tenant databases** hold everything school-specific: students, staff, fees, attendance, exams, library, CMS, etc.
- Three Filament panels:
  - **SaaS Admin** (`/saas`, guard `saas_admin`) — platform owners.
  - **School Admin** (`/admin`, guard `school_users`) — per-school staff (24+ navigation groups, 60+ resources).
  - **Parent Portal** (guard `school_users`) — read-only views for parents.
- **Public surface**: marketing landing, school signup, school login, password set/reset, tenant CMS sites, public student applications, online admission tests, certificate verification, parent registration.
- **Tenant resolution** (`InitializeTenancyBySubdomainOrDomain` middleware): central host + session, subdomain (`school.kynexedu.com`), or verified custom domain — in that order.
- **SSL for custom domains** flows through `ProvisionCustomDomainCertificate` job → in-container cert listener (port 9090) → Caddy/Let's Encrypt.
- **Approval system**: 15 sensitive action types are gated by `ApprovalRequest` records, executed asynchronously by `ExecuteApprovedAction`.
- **RBAC**: Spatie permissions seeded per tenant; ~150 permissions across 9 categories; `RoleHierarchy` enforces level-based authority and dual-approval for protected roles.
- **Notification stack**: Push (FCM) → WhatsApp → SMS → Email cascade with 24h fallback.

---

## 2. SaaS Admin Panel — `/saas`

Path `/saas`. Guard `saas_admin`. Provider: `app/Providers/Filament/SaasAdminPanelProvider.php`. Dark-mode enabled, primary `#2563EB`, blocked from running on tenant hosts via `BlockCentralPortalOnTenantHost` middleware.

### 2.1 Schools group

#### Tenant Resource — `app/Filament/SaasAdmin/Resources/TenantResource.php`
Manages every school account.

- **Form sections**: School Information; Subscription (plan, status enum Trial/Active/Suspended/Expired, trial_ends_at, period dates, internal notes); Communication Channels (WhatsApp: none / evolution / sendpk_whatsapp / meta_official / twilio_whatsapp + per-driver config; SMS: none / android_sms_gateway / sendpk / jazz_sms / telenor_sms / twilio_sms + per-driver config); AI Settings (toggle, provider openrouter/groq, API key, model picker with 9+ known models + custom, monthly budget in PKR).
- **List columns**: school_name, admin_email (copyable), plan badge, status badge, active_student_count, whatsapp_channel, trial_ends_at (with "Expired!" overlay), created_at.
- **Filters**: status, plan, `trials_expiring_soon` (custom: status=Trial AND trial expires within 7 days), `created_this_month`.
- **Row actions**: Edit, Suspend (with reason), Activate, Impersonate (stub, "coming soon"), Owner Accounts (assigns/revokes INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD in tenant context), Send Set-Password Link (creates `SchoolInvitation` type `admin_invite`, 3h validity, emails via Resend), Send Password Reset, View Invoices (deep-link).
- **Internal**: monetary values stored as paisas; mutators convert PKR ↔ paisas on save.
- **Relation manager**: `DomainsRelationManager` (add custom domain, verify, DNS instructions modal, provision cert, reissue cert, remove non-primary). Tracks `cert_status` ∈ {issued, issuing, rate_limited, lock_timeout, dns_mismatch, failed} and `cert_expires_at`.
- **Resource widget**: `TenantStatsOverview` (5 KPI stat cards).

#### Tenant Signup Resource — Signup Leads
Captures self-service registrations from `/register`.
- Statuses: New, Contacted, Invited, Onboarded, Rejected.
- Row action **Approve & Provision**: pick plan + trial/active, calls `ProvisionNewTenant` action which spins up the tenant DB, seeds roles/templates, sends invite emails, sets status to Onboarded.
- Other row actions: Mark Contacted, Reject.

### 2.2 Billing group

#### Subscription Plan Resource
Pricing catalog.
- **Pricing**: base + per-student + setup fee, all PKR-input/paisa-stored.
- **Limits**: max_students, max_staff, max_campuses, storage_limit_gb (0 = unlimited).
- **Trial**: `trial_days` (default 14).
- **Enabled modules** (30+ flags, checkbox list 3-col): student_management, staff_management, campus_management, class_management, attendance, timetable, whatsapp_notifications, sms_notifications, email_notifications, parent_portal, exam_management, report_cards, homework, online_classes, online_classes_pro, library, transport, hostel, inventory, visitor_management, fee_management, payroll, expense_tracking, budgeting, ai_assistant, advanced_analytics, custom_reports, api_access.
- **Reorderable** via `sort_order`; toggleable active/featured/custom flags.
- **Row actions**: Edit, Duplicate ("(Copy)", deactivated), Toggle Active.

#### Invoice Resource
Monthly billing records.
- Line items (PKR inputs, paisa storage, live total): base_amount, per_student_amount, sms_usage, whatsapp_usage, ai_usage, storage_overage, discount.
- Invoice number format `KNX-YYYY-MM-slug`, unique.
- **Header action**: Generate Invoice — pick tenant + month/year, autopopulates student count and amounts from the tenant's plan.
- **Row actions**: Edit, Mark Paid, Send WhatsApp (placeholder marker), Download PDF (DomPDF).
- **Filters**: tenant, status (Draft/Sent/Paid/Overdue), period (month + year dropdowns).

### 2.3 Operations group

#### Approval Request Resource
Central queue across all tenants.
- Filters: status (Pending/Approved/Rejected/Cancelled), approver_level (institute_head, saas_admin, institute_head_or_saas, multi_head_or_saas, exam_admin).
- Row actions Approve / Reject call `ApprovalService::approve()` / `reject()` with optional/required admin notes; on approval the matching handler from `config/approval.php` dispatches via `ExecuteApprovedAction`.

### 2.4 Audit group (read-only)

#### Audit Run Resource
One record per `audit:run` execution. Columns: status, scope (all/tenant/role/url), tenant_slug, critical/high/medium/low counts, total_pages, duration.

#### Audit Finding Resource
Per-finding rows with severity (critical/high/medium/low/info/ok), `finding_type` (5xx, 4xx, js_error, network_failure, broken_image, etc.; 13+ types), URL, role, http_status, screenshot_path, detected_at, fixed_at, fixed_by_commit. Infolist view pretty-prints `details` JSON.

### 2.5 Settings page

#### API Settings — `app/Filament/SaasAdmin/Pages/ApiSettings.php`
Platform-level fallback credentials managed in `PlatformApiSetting`. Three named forms:
- **SMS** (Android SMS Gateway): URL, login, encrypted password.
- **WhatsApp** (Evolution API): URL, API key, instance name, webhook URL.
- **AI** (OpenRouter): API key, default model (with custom), site URL/name, max_tokens, monthly budget.
Each has a Test Connection button that hits a health/discovery endpoint.

### 2.6 Standalone pages

- **Dashboard** — 8 widgets:
  - `StatsOverviewWidget` (Total Schools, Active, On Trial, Suspended, Est. MRR)
  - `LatestSignupsWidget` (10 rows + inline Send Invite)
  - `InvoicesDueWidget` (sent/overdue, days overdue)
  - `PlanDistributionWidget` (tenants per plan)
  - `RevenueTrendWidget` (line, 12-month paid revenue PKR)
  - `TenantGrowthWidget` (mixed bar+line, cumulative + new)
  - `AiUsageAnalyticsWidget` (6-month AI cost PKR)
  - `TenantStatusPieWidget` (status distribution doughnut)
- **Campus Management** — cross-tenant Campus CRUD (pick tenant → manage their campuses without entering their panel).
- **Assign Institute Head** — cross-tenant role-grant tool. Creates new SchoolUser (with set-password link) or assigns to existing one; sends notification emails to new holder, existing same-role holders, and SaaS admins.
- **Access Denied** — shared 403 page.
- **Auth/Login** — custom SaaS login.

---

## 3. School Admin Panel — `/admin`

Path `/admin`. Guard `school_users`. Provider: `app/Providers/Filament/SchoolAdminPanelProvider.php`. Urdu/RTL aware, multi-role users get a Switch Role item.

**Navigation groups**: Home · Admissions · Students · Academic Setup · Examinations · Attendance · Staff & HR · Fees & Finance · Communication · Front Office · Library · Transport · Hostel · Inventory · Cafeteria · Health & Wellbeing · Online Classes · Certificates & ID Cards · Reports · Website CMS · Settings · Compliance · System · Help & Support.

### 3.1 Dashboard
`app/Filament/SchoolAdmin/Pages/Dashboard.php`. Surface is role-driven: each role sees a tailored widget set.

Per-role widget bundles (illustrative — full list in `app/Filament/SchoolAdmin/Widgets/`):
- **SCHOOL_ADMIN**: SchoolStatsOverview, RecentStudents, PendingLeaveRequests, UpcomingOnlineClasses, EnrolmentByClass, AttendanceTrend, FeeCollectionTrend, ExamPerformance.
- **INSTITUTE_HEAD / MULTI_INSTITUTE_HEAD**: InstituteOwnerStats (cross-campus rollups), CampusOwnerStats, CampusRecentAdmissions.
- **TEACHER**: TeacherStats, TeacherTimetable, TeacherClasses, HomeworkPendingReview.
- **PARENT** (in parent portal too): ParentStats, ParentChildren (per-child fees/attendance/marks summary).
- **STUDENT**: StudentStats, StudentTimetable.
- **ACCOUNTANT / BURSAR**: AccountantStats, BursarStats, RecentExpenses, RecentFeeCollections, FeeStatusDistribution.
- **ATTENDANCE_CLERK**: AttendanceClerkStats, AttendanceClerkClassList, AttendanceClerkRecentAbsents.
- **LIBRARIAN**: LibrarianStats, LibrarianBookSearch.
- **HOSTEL_WARDEN**: HostelWardenStats, HostelPendingGatePasses, HostelCheckedOutToday.
- **NURSE**: NurseStats, NurseHealthStub.
- **COUNSELOR**: CounselorStats, CounselorStub.
- **EXAM_ADMIN**: ExamAdminStats.
- **RECEPTIONIST**: ReceptionistStats, ReceptionistCurrentVisitors.
- **REGISTRAR**: RegistrarStats.
- **CAFETERIA_MANAGER**: CafeteriaStats, CafeteriaLowStock.
- **TRANSPORT_MANAGER**: TransportStats, TransportGpsPlaceholder.
- **HR_MANAGER**: HrStats.

### 3.2 Admissions

- **Student Application Resource** (`Admissions`): full applicant CRUD with documents, scoping by campus/class-delegation. Row actions: View, Edit, Admit (creates Student), Reject (reason), Hold/Waitlist, Generate Offer Letter, Send Login Credentials. Bulk Admit / Reject / Hold.
- **Admission Criteria Resource**: eligibility + quota per year × class.
- **Admission Test Resource**: name, duration, total marks, negative marking, passing score, eligible classes; nested questions via relation manager.
- **Admission Schedule (page)**: create/manage test dates/sessions.
- **Admission Exam Attendance (page)**: mark attendance for candidates on exam day.
- **Admission Marks Entry (page)**: enter written + interview marks.
- **Admission Delegations (page)**: assign staff (with approval level) to act on admissions for specific classes.
- **Public admission flow** (see §5.3): `/apply`, status check, parent registration, exam-login, online admission test, post-admit profile completion.
- **Services**: `StudentApplicationService`, `AdmissionScoringService` (weighted test + interview + criteria), `AdmissionDelegationService`, `AdmissionAiService` (AI-assisted scoring), `RunnerUpService` (merit waitlist), `DocumentExtractor`.
- **Approval overrides**: `HandleAdmissionDecisionOverride`, `HandleAdmissionMarksOverride`.

### 3.3 Students

- **Student Resource**: tabbed form (Admission, Academic Placement, Personal Info, Status & Enrollment, Guardians 1–2). Row actions: Profile (deep page), Send Login, Invite Parent, Edit, Change Status (Left/Expelled/Graduated/Suspended go through approval unless actor is INSTITUTE_HEAD), Delete (also approval-gated). Campus-scoped for non-INSTITUTE_HEAD.
- **Student Category Resource**: name + fee discount % + priority (e.g., Scholarship, Day Scholar, Boarder, Staff Child).
- **Student Bulk Import (page)**: CSV importer with template download, validation preview, error reporting, progress tracking. Backed by `StudentBulkImporter` service.
- **Infix Import (page)**: migrate students/staff/grades from Infix ERP. Backed by `InfixImportService` + `ProcessInfixImport` job.
- **Mark Attendance (page)**: class+section+date → student list with present/absent toggles, optional Activity Score (participation/homework/behavior), bulk actions. Teachers scoped to own classes.
- **Approval handlers**: `HandleStudentStatusChange`, `HandleStudentDelete`, `HandleStudentAdmission`.

### 3.4 Academic Setup

- **Class Resource** (`SchoolClass`): name, campus, numeric level, sort, description.
- **Section Resource**: name, class, campus, capacity.
- **Subject Resource**: name, code, type (Theory/Practical/Both), elective flag.
- **Class Subject Resource**: bridge linking subjects to class/section, with required/optional flag.
- **Syllabus Resource**: with chapter/topic relation manager.
- **Class Routine Resource**: weekly timetable per class+section, time periods.
- **Class Routine Planner (page)**: interactive drag-drop timetable builder.
- **Grade Resource**: grading scale (A=4.0 …) with min/max percentage and color.
- **Grading Weights (page)**: term weightages used by annual result calculation (Term 1: 20%, Term 2: 30%, Finals: 50%, etc.).

### 3.5 Examinations & Marks

- **Exam Resource**: academic year, type (Internal/Terminal), dates, status, publish toggle, annual-result weightage.
- **Annual Result Resource** (read-only list): student × academic year totals/percentage/grade/status.
- **Marks Entry (page)**: tabs for Exam Marks · Homework · Class Assignments · Class Tests. Drill-down by exam → class → section → subject; validates marks ≤ full marks; teachers see only their classes.
- **Results Page**: filter by exam/class/section/student, view marks, trigger annual calculation, publish results.
- **Approval handlers**: `HandleExamMarkChange` (post-publish edits), `HandleAssignmentChange`.

### 3.6 Attendance

- **Attendance Settings Resource**: per campus/class/section start/end times, late cutoff, grace period.
- **Attendance Device Resource**: register biometric/RFID devices (name, device ID, campus, location).
- **Mark Attendance (page)**: see §3.3.
- **Attendance Report (page)**: class/section/date-range summaries and per-student detail.
- **Biometric pipeline**: ZKTeco ADMS endpoints (`/iclock/cdata`, `/iclock/getrequest`, `/iclock/devicecmd`) + `ProcessBiometricLogs` job (every 15 min).
- **Notifications**: `NotifyAbsentStudentsAndParents`, `NotifyAbsentParents`, `NotifyLateArrival`.

### 3.7 Staff & HR

- **Staff Resource** (`StaffProfile`): Employment Details (user account, employee ID, department, designation filtered by dept, campus, employment type, joining date, qualification, experience, basic salary PKR), Emergency Contact, Bank Details.
- **Department Resource**: name, head, description.
- **Designation Resource**: name, department, grade/level.
- **Staff Attendance Resource**: per staff per day check-in/out + status.
- **Leave Type Resource**: name, annual quota, carryover, gender-specific flag (e.g., Maternity).
- **Leave Request Resource**: full lifecycle — submit, approve, reject, edit, delete, bulk approve/reject. Optional supervisor step.
- **Salary Component Resource**: earnings/deductions/tax, flat or %-based, applies to all or specific designations.
- **Staff Payroll Resource**: monthly slips with auto-populate from components, allowance/deduction repeaters, gross/net, status (Draft/Approved/Paid), payslip PDF.
- **Payroll Resource**: summary management / reconciliation page.
- **Approval handlers**: `HandleStaffStatusChange`, `HandleStaffDelete`, `HandleStaffHire`.
- **Payslip download** via tenant-aware web route `/payslip/{payroll}/download`.

### 3.8 Fees & Finance

Fee Structure setup:
- **Fee Group Resource** (Tuition, Hostel, …).
- **Fee Type Resource**: name + group + is_recurring.
- **Fee Master Resource** (Fee Structure): the price table — class × section (optional) × fee_type × academic_year × amount × active.

Fee operations:
- **Generate Fees (page)**: header actions Roll out monthly fees, Add one-time fee, Apply late fees. Reads from FeeMaster to issue StudentFee invoices.
- **Fee Collection Page**: row-per-invoice with Collect (modal with method, partial/full, receipt), Receipt reprint, Send Reminder, Waive, Edit. Summary tiles: today's collection, this-month, total outstanding, defaulter count.
- **Fee Catalog Page**: read-only structure view (parent/student-facing).
- **Fee Defaulters Page**: aged defaulter list with single + bulk reminders, dunning letter generation.
- **Fee Reports Page**: tabs for Collection summary, Outstanding, Payment history, Cash/Bank reconciliation; CSV/Excel export.
- **Financial Report Page**: school-wide income vs expense.
- **Fee Reports widgets**: FeeKpi, CollectionTrend, StatusDistribution, DefaulterAging, ClassWiseOutstanding.

Expenses & budgeting:
- **Expense Category Resource**: category + budget allocation.
- **Expense Resource**: full record with attachment (receipt) + status (Draft/Approved/Paid).
- **Budget Resource**: year × category × allocated; auto-calculated Spent / Remaining / % used.

Payments & receipts:
- **Payment gateways**: JazzCash, EasyPaisa, Stripe drivers in `app/Services/Payment/Drivers/`, with callback + return routes per gateway (`/payment/jazzcash/callback`, `/payment/easypaisa/callback`, etc.).
- **Fee receipt** public download route: `/fee-receipt/{payment}`.
- **Approval handlers**: `HandleFeeRefund`, `HandleBulkFeeWaiver`, `HandleExpenseApproval` (above-threshold).
- **Late fees**: scheduled daily 01:00 via `FeesService::applyLateFees` (5000 paisas/day, 500000 max).

### 3.9 Communication

- **CMS Announcement Resource** (school-wide announcements with target audience filter).
- **Notice Resource** (official notices with attachment + priority).
- **Notification Composer (page)**: type (SMS/Email/In-app/Push) × recipients (all/class/section/role/individual) × template/free-form × schedule (immediate or deferred). Delivery status tracking.
- **Notification Template Resource**: per-channel templates with variables like `{student_name}`, `{class}`, `{fee_due}`.
- **Notification Settings Page**: gateway keys, frequency caps per parent, retry policy.
- **Communication Settings Page**: SMS/email/WhatsApp business config, default language.
- **Send Student Message Page**: per-student parent messaging.
- **AI Assistant Panel** (`app/Livewire/AiAssistantPanel.php`): sidebar chat, role-scoped prompts/facts (see `app/Services/Ai/`).
- **Notification Bell** (`app/Livewire/NotificationBell.php`): header dropdown.

### 3.10 Front Office

- **Visitor Resource**: name, purpose, time-in/out, contact person, ID scan, status. Check-in / Check-out / Issue Pass actions, bulk end-of-day checkout.
- **Receptionist widgets**: CurrentVisitors, ReceptionistStats.

### 3.11 Library

- **Book Resource**: title, author, ISBN, category, publisher, edition, rack #, total/available copies, price (paisas), description.
- **Book Issue Resource**: track checkouts; Issue, Return (auto-fine on overdue), Renew, Mark Lost. Validates available copies before issuing.
- **Service**: `LibraryService`.

### 3.12 Transport

- **Transport Route Resource**: stops repeater (name, sequence, time), vehicle, driver, schedule, capacity, campus.
- **Vehicle Resource**: license plate, type, capacity, driver, insurance expiry, fitness certificate expiry, last maintenance.
- **Transport widgets**: TransportStats, TransportGpsPlaceholder (third-party hook).

### 3.13 Hostel

- **Hostel Building Resource**: name, address, capacity, warden, amenities.
- **Hostel Room Type Resource**: name, capacity, base rent (PKR), amenities.
- **Hostel Room Resource**: room #, building, type, floor, occupancy, condition.
- **Hostel Allocation Resource**: student × room × dates × rent × meal plan; Check-in / Check-out / Transfer / Create Gate Pass actions.
- **Hostel Gate Pass Resource**: student × destination × expected return × status (Approved / In-Progress / Returned / Overdue); Approve / Check-out / Check-in / Extend / Cancel.

### 3.14 Inventory

- **Inventory Category Resource**.
- **Inventory Store Resource** (Main Store, Lab Store, …).
- **Inventory Item Resource**: with unit, quantity-on-hand (computed), reorder level, supplier, unit cost.
- **Inventory Transaction Resource**: Receipt / Issue / Transfer / Adjustment with from-store, to-store, reference, reason.
- **Inventory Supplier Resource**: doubles as vendor for expenses.

### 3.15 Cafeteria

- **Cafeteria Menu Item Resource**: category (Breakfast/Lunch/Snack), days-of-week toggle, price (PKR), photo, nutritional info.
- **Cafeteria widgets**: CafeteriaStats, CafeteriaLowStock (ingredient-stock cross-check).

### 3.16 Health & Wellbeing

- **Health Record Resource**: per-visit clinical record (complaint, diagnosis, treatment, follow-up, prescribed meds).
- **Behavior Incident Resource**: type (Bullying/Misbehavior/Aggression/Other), severity, description, involved parties + witnesses, action taken, follow-up required.
- **Counselor widgets**: CounselorStats, CounselorStub.

### 3.17 Online Classes

- **Online Class Resource**: class, subject, instructor, platform (Zoom/Meet/custom), join URL, schedule, recording. Actions: Start Class, Join, End Class, Upload Recording, View Attendance.
- **Manage Online Class Attendance (custom page)**: mark per-session attendance.
- **Upcoming Online Classes Widget**: dashboard-level.

### 3.18 Certificates & ID Cards

- **ID Card Template Resource**: design + included fields (Name, Photo, Roll #, Class, Barcode/QR), card size, orientation.
- **Certificate Template Resource**: type (Completion/Merit/Promotion), design, fields, fonts/colors.
- **Generate ID Cards (page)**: pick template, filter students, preview, batch PDF, print, barcode/QR generation.
- **Issue Certificate (page)**: pick template, pick students/graduates, generate PDF, track issued, email out.
- **Generated Certificate** model tracks every issuance for verification.
- **Public verification page**: `/certificate-verify` (template) — QR scan lands here to confirm authenticity.
- **Service**: `CertificateService`.

### 3.19 Reports & Analytics

- **Report Builder Page**: drag-drop query builder; choose data source (Students/Staff/Fees/Marks…), filters, columns, sort, group; export CSV/Excel; save as Custom Report.
- **Analytics Dashboard**: enrollment, attendance %, pass rate, collection rate tiles + charts (enrollment trend, attendance trend, marks distribution, pass/fail ratio) + attendance heatmap.
- **Attendance Report Page**, **Fee Reports Page**, **Financial Report Page**: see §3.6 / §3.8.
- **Custom Report** model supports scheduled execution via `RunScheduledReport` job.
- **Service**: `ReportBuilderService`.

### 3.20 Website CMS

- **CMS Page Resource**: title, slug, RichEditor content, meta, featured image, parent (hierarchy), SEO fields.
- **CMS Gallery Album Resource** (with photo relation).
- **CMS Slider Resource**: hero carousel images.
- **CMS Settings Page**: school name/logo/tagline, contact, social links, homepage block config, footer, custom CSS/JS.
- Public renderer: `/site/{tenant}/...` routes via `PublicSiteController` / `Cms/PublicController`.

### 3.21 Settings

- **Academic Year Resource**: name, dates, is_current; actions Activate, Create Next Year (prefill).
- **Campus Resource**: per-campus profile (head, phone, infra notes).
- **School User Resource**: name, email, roles (multi), campus, active, last login; actions Reset Password, Force Logout, Disable/Enable.
- **Role Management Resource**: Spatie Role CRUD with permission checkboxes.
- **Switch Role (page)**: visible only to multi-role users; toggles active role.
- **Platform Settings Resource**: school identity & branding (name, logo, address, principal, currency PKR default, date format, timezone, theme color).
- **Attendance Settings Resource** (also surfaces here): per campus/class/section.

### 3.22 Compliance

- **PII Access Log Resource** (`PiiAccessLog`, read-only): user × timestamp × action (view/export/edit) × entity × record × IP. Pruned by `PrunePiiAccessLog` command.

### 3.23 System

- **Approval Queue Page**: tenant-side view of pending approvals. Approve / Reject / View. Used by INSTITUTE_HEAD and other approver roles.
- **Help Center Page**: in-app docs, FAQ, support contact.
- **Access Denied** page in-layout.

---

## 4. Parent Portal

Same guard (`school_users`), separate Filament panel under `app/Filament/ParentPortal/`. Read-only views: child fee statement, attendance, results, homework, notices. Dashboard widgets: ParentStats, ParentChildren.

---

## 5. Public-Facing Surface

### 5.1 Central host (`kynexedu.com` / `kynexsolutions.com` etc.)

Routes in `routes/web.php`, controller `SchoolPortalController`:
- `/` — marketing/landing (`resources/views/portal/landing.blade.php`).
- `/register` — school self-registration → creates `TenantSignup`, sends verification email.
- `/verify-email/{token}` — confirms email, advances signup status.
- `/login`, `/logout` — school portal auth (post-login routes go to either the SaaS panel, school panel, or parent portal based on guard).
- `/forgot-password`, `/reset-password/{token}` — password reset flow.
- `/set-password/{token}` — used by both admin invites and self-signups for initial password.
- `/dashboard` — post-login dispatcher.
- `/CL0/{path}` — Resend email click-tracking passthrough (whitelisted to verified domains/subdomains).
- `/caddy/check-domain` — pre-flight check for the cert listener.
- `/admin/login` redirects to `/login` (so old links keep working).

### 5.2 Tenant CMS (subdomain or verified custom domain)

`PublicSiteController` + `Cms/PublicController`:
- `/` home, `/about`, `/admissions`, `/gallery`, `/news`, `/news/{slug}`, `/contact`, `/contact-form`, `/results`, `/pages/{slug}`.
- Driven by `CmsSetting`, `CmsPage`, `CmsAnnouncement`, `CmsSlider`, `CmsGalleryAlbum`, `CmsGalleryPhoto`.

### 5.3 Public admissions workflow

Tenant-aware routes (resolve via subdomain/custom domain), controllers `PublicAdmissionController`, `PublicAdmissionTestController`, `PublicAdmissionCompleteController`, `ExamLoginController`:

- `/apply` — application form; rejected with "applications closed" view (`apply-closed.blade.php`) if no open window.
- `/apply/status/{token}` — applicant looks up their own status (token-based, no login).
- `/parent/register` → `/parent/register-sent` — parent self-signup; lands a verification email.
- `/exam-login` — temporary credentials for exam day.
- `/admission-test/{token}` (intro) → `/begin` → take view → `/submit` → finished view; `/violation` records browser misbehavior; `/exam-waiting/{token}` polls until staff opens the room.
- `/admission/complete/{token}` — admit fills the detailed profile post-acceptance.

### 5.4 Other tenant-aware public/auth routes

- `/payslip/{payroll}/download` — staff payslip PDF.
- `/result-card/{result}` — student report card PDF.
- `/financial-report/print` — printable financial report.
- `/fee-receipt/{payment}` — payment receipt PDF.
- `/admin/bulk-import-run` — internal POST endpoint for the bulk student importer.

### 5.5 Verification pages

- `/certificate-verify` template — QR scan lands here.
- `/student-verify` template — staff/parent verification of a student record.

---

## 6. Tenancy & Custom Domains

- **Config**: `config/tenancy.php`. Central domains include `127.0.0.1`, `localhost`, `kynexedu.com`, `kynexsolutions.com`, `sms.kynexsolutions.com`. UUID tenant IDs. Postgres per-tenant DB (prefix `tenant`), tenant-suffixed filesystem disks, tagged cache (tag base `tenant`).
- **Resolution middleware** (`InitializeTenancyBySubdomainOrDomain`): central host + session/`?tenant=` → subdomain → verified custom domain → no tenancy. Variant `InitializeTenancyBySubdomain` for subdomain-only contexts.
- **Tenancy middleware family**:
  - `EnsureTenantIsActive` — blocks suspended/expired tenants.
  - `ClearStaleTenantSession` — wipes session tenant_id on logout.
  - `EnsureCentralHost` — central-only routes.
  - `BlockCentralPortalOnTenantHost` — keeps SaaS panel off school hosts.
  - `SetTenantLocale` — per-tenant locale config.
  - `SetPostgresUserRole` — Postgres RLS / row-level security role switching.
- **Custom domain lifecycle** (`CustomDomainService`):
  1. `initiateVerification` — creates unverified `Domain` row + verification token; user adds DNS TXT.
  2. `verifyDomain` — DNS TXT lookup; marks verified.
  3. `provisionCert` — dispatches `ProvisionCustomDomainCertificate` to in-container cert-listener on port 9090 (`/provision`, idempotent).
  4. `reissueCert` — forces re-issue via `/reissue`.
  5. `removeDomain` — only on non-primary domains.
- **Cert config** (`config/cert.php`): listener URL, shared secret, timeouts, renewal sweep at 14 days, verification token TTL 7 days. Stub mode (`CERT_STUB_MODE=true`) logs instead of executing.
- **Daily sweep**: `VerifyPendingCustomDomains` command auto-expires stale tokens and retries verification.

⚠️ Memory: aqmdigital.com nginx conf and similar live in the `kynex-app` container's writable layer; recreating that container wipes them. This is being redesigned in the Phase 1.5 cert workstream.

---

## 7. RBAC & Roles

`config/permission.php`. Guard `school_users`. Seeded per tenant by `RbacPermissionSeeder` (runs inside tenant DB after provisioning).

### 7.1 Permission catalog (~150)

Categories:
- **Student Management** (15): view, create, edit, delete, status changes, documents, admissions.
- **Academic Management** (18): classes, sections, subjects, timetable, homework, assignments, daily activity.
- **Attendance** (6): manual, biometric, fallback, view, edit, process.
- **Examination** (13): schedules, grading, marks entry/edit/publish, results, report cards.
- **Fee & Finance** (26): structures, payments, refunds, expenses, budget, payroll, invoices, scholarships, reports.
- **Staff & HR** (16): view, create, edit, delete, documents, contracts, onboarding, performance, leave, payroll.
- **Role Management** (4): assign, remove, succession, sensitive roles.
- **Communication** (14): notifications, SMS, email, push, announcements, messaging.
- **Admin/System** (9): school settings, audits, AI features, API access, custom reports.

### 7.2 Role hierarchy (`app/Support/RoleHierarchy.php`)

| Level | Roles |
| --- | --- |
| 110 | MULTI_INSTITUTE_HEAD |
| 100 | INSTITUTE_HEAD |
| 90 | SCHOOL_ADMIN |
| 70 | HR_MANAGER, REGISTRAR, BURSAR, EXAM_ADMIN |
| 60 | ACCOUNTANT |
| 50 | TEACHER |
| 40 | TRANSPORT_MANAGER, HOSTEL_WARDEN, LIBRARIAN, ATTENDANCE_CLERK, NURSE, COUNSELOR, CAFETERIA_MANAGER |
| 30 | RECEPTIONIST |
| 20 | PARENT |
| 10 | STUDENT |

Rules:
- Actor must be **strictly higher** level than target.
- Equal level allowed **only** with `bypass_approvals` permission.
- Actor cannot "punch below" — SCHOOL_ADMIN cannot act on INSTITUTE_HEAD even with bypass.
- Protected roles (level ≥ 70) require `bypass_approvals` for direct assignment.
- **SaaS-only roles**: MULTI_INSTITUTE_HEAD, INSTITUTE_HEAD (first-time only by SaaS admin).
- **Dual-approval roles**: INSTITUTE_HEAD, MULTI_INSTITUTE_HEAD (reassignment from school panel needs institute_head_or_saas / multi_head_or_saas).

### 7.3 Policies

- `SchoolUserPolicy`, `SchoolAdminPolicy`, `RoleSuccessionPolicy`.
- `RoleSuccessionService` orchestrates admin handover.

---

## 8. Approval System

`config/approval.php` maps action_type → invokable handler. `ApprovalService` provides `submit / approve / reject / cancel / pendingForTenant / pendingForInstituteHead / pendingForSaasAdmin / expireOverdue`. `ApprovalRequest` lives in the **central** database (not tenant-scoped) so SaaS admins can see everything.

15 handler actions:
1. `HandleFeeRefund`
2. `HandleStudentStatusChange`
3. `HandleStaffStatusChange`
4. `HandleRoleRevocation`
5. `HandleExpenseApproval`
6. `HandleStudentDelete`
7. `HandleStaffDelete`
8. `HandleExamMarkChange`
9. `HandleAssignmentChange`
10. `HandleSensitiveRoleAssignment`
11. `HandleBulkFeeWaiver`
12. `HandleStudentAdmission`
13. `HandleStaffHire`
14. `HandleAdmissionDecisionOverride`
15. `HandleAdmissionMarksOverride`

Approver levels: `institute_head`, `saas_admin`, `institute_head_or_saas`, `multi_head_or_saas`, `exam_admin`.

Execution: `ExecuteApprovedAction` job (tries 3, backoff 60s) initializes tenancy if scoped, resolves handler, runs it. Pending requests can be expired by `expireOverdue`.

---

## 9. Notifications

`NotificationService` orchestrates the cascade Push (FCM) → WhatsApp → SMS → Email. If push delivery isn't confirmed in 24h (`CheckPushDelivery`), falls back. Per-tenant `allow_*` flags gate channels.

### 9.1 Channels & drivers

- **Push**: `Push/Drivers/FcmDriver` (Firebase Cloud Messaging). `DeviceToken` model in central DB. `app/Http/Controllers/Api/V1/PushTokenController` for register/unregister.
- **WhatsApp** drivers (`Services/WhatsApp/Drivers/`): `MetaOfficialDriver`, `EvolutionDriver`, `TwilioDriver`. Factory: `WhatsAppServiceFactory`. Webhook routes `/whatsapp/webhook` (GET verify, POST receive). `BotController` + `ProcessWhatsAppBotMessage` + `ProcessWhatsAppMessage` jobs. Event `WhatsAppMessageReceived` → listener `SendWhatsAppInboxNotification`.
- **SMS** drivers (`Services/Sms/Drivers/`): `JazzSmsDriver`, `TelenorSmsDriver`, `TwilioSmsDriver`, `SendPkSmsDriver`, `AndroidGatewayDriver`, `NullSmsDriver`. Factory: `SmsServiceFactory`.
- **Email**: Laravel mail + Resend transport for transactional invites/resets.

### 9.2 Templates & logs

- `NotificationTemplate` (per-school customizable, Blade syntax).
- `NotificationLog` (delivery tracking), `InAppNotification`, `CommunicationLog`, `WhatsAppMessage`, `WhatsAppConversation`, `AiConversation`, `AiMessage`.

### 9.3 Notification jobs

- `ProcessNotificationQueue` (main pipeline).
- `NotifyAbsentStudentsAndParents`, `NotifyAbsentParents`, `NotifyLateArrival`.
- `NotifyHomeworkCreated`, `NotifyHomeworkGraded`, `OverdueHomeworkReminder`.
- `NotifyNoticePublished`, `NotifyResultPublished`.
- `SendFeeReminderWithFallback`, `SendPaymentReceiptNotification`.
- `SendExamCredentialsBatch` (admission test logins).
- `GradeAdmissionAnswersBatch` (async grading).

---

## 10. AI Integration

`Services/Ai/AiAssistant.php` is the chat interface. `OpenRouterService` is the client (per-school API keys with platform fallback). `RolePrompts` defines system prompts per role; `RoleScopedFacts` injects context the user is allowed to see.

- Storage: `AiConversation`, `AiMessage`, `AiUsageLog`.
- Budgets: tenant.`ai_monthly_budget_paisas`, `ai_used_this_month_paisas`; SaaS view via `AiUsageAnalyticsWidget`.
- UI: `app/Livewire/AiAssistantPanel.php` (sidebar) plus `AdmissionAiService` for admission scoring assistance.

---

## 11. REST API (`/api/v1`)

Sanctum-authenticated. Rate limits: 120/min auth, 10/min for `/auth/*`. Gated by `EnsureApiAccess` (plan must have `api_access`).

| Method · Path | Purpose |
| --- | --- |
| POST `/auth/login` · `/auth/logout` · `/auth/refresh` | Token lifecycle |
| GET / POST `/students` · GET `/students/{id}` | Student data |
| GET / POST `/attendance` · GET `/attendance/summary` | Attendance |
| GET `/results` · GET `/results/{id}` | Exam results |
| GET `/fees/{studentId}` | Fee statement |
| GET `/timetable` | Class schedule |
| GET `/homework` · GET `/homework/{id}` · POST `/homework/{id}/submit` | Assignments |
| GET `/notices` | Notices |
| POST `/push/register` · `/push/unregister` | Push tokens |

Controllers in `app/Http/Controllers/Api/V1/`. Deep linking handled via `DeepLinkService`.

---

## 12. Background Jobs & Schedule

Queue connection: database (default), Redis available. Retry-after 90s.

### 12.1 Jobs (22)

`ExecuteApprovedAction`, `ProvisionCustomDomainCertificate`, `SendExamCredentialsBatch`, `GradeAdmissionAnswersBatch`, `SyncTenantActiveStudentCount`, `CheckPushDelivery`, `NotifyAbsentStudentsAndParents`, `NotifyAbsentParents`, `NotifyHomeworkCreated`, `NotifyHomeworkGraded`, `NotifyLateArrival`, `NotifyNoticePublished`, `NotifyResultPublished`, `OverdueHomeworkReminder`, `ProcessBiometricLogs`, `ProcessInfixImport`, `ProcessNotificationQueue`, `ProcessWhatsAppBotMessage`, `ProcessWhatsAppMessage`, `SendFeeReminderWithFallback`, `SendPaymentReceiptNotification`, `RunScheduledReport`.

### 12.2 Console commands (16)

- `audit:run` (`AuditRunCommand`) — full system audit (~39 KB; covers RBAC, students, fees, etc.).
- `audit:prune` — archive old findings.
- `audit:report` — generate summary.
- `CheckEnvironment` — pre-flight.
- `ConsolidateCampuses` — multi-campus merge.
- `EnsureDevDemoTenant` / `ResetDemoTenant` — demo tenant lifecycle.
- `GenerateMonthlyInvoices` — monthly billing.
- `PrunePiiAccessLog` — retention sweep.
- `RefreshMaterializedViews` — report view refresh.
- `RepairSchoolUserRoles` — fix orphaned role assignments.
- `ResetAllTenants` — destructive: wipe every production tenant.
- `SeedDefaultTemplates` — notification templates.
- `SendBillingNotifications` — billing comms.
- `VerifyPendingCustomDomains` — DNS verification sweep.
- `WipeTenantData` — selective deletion.

### 12.3 Scheduled tasks (`routes/console.php`)

Central:
- 02:00 daily — `SyncTenantActiveStudentCount`.
- 03:00 1st of month — `billing:generate-invoices`.
- 09:00 daily — `billing:send-notifications --type=reminder`.
- 10:00 daily — `billing:send-notifications --type=overdue`.

Per tenant:
- Every 15 min — `ProcessBiometricLogs`.
- 01:00 daily — `FeesService::applyLateFees` (5000 paisas/day, 500000 max).
- Custom report schedules (`CustomReport.scheduled_at`).

---

## 13. Audit & Compliance

- **Audit pipeline**: `audit:run` crawls authorized URLs per role per tenant, records `AuditRun` + findings (5xx, 4xx, js_error, network_failure, broken_image, etc.). Findings include screenshots. Browse and triage in SaaS Admin → Audit group.
- **PII access logging**: `PiiAccessLog` records any read/export/edit of sensitive student/staff data with user + IP + entity. Surfaced in School Admin → Compliance, pruned by `PrunePiiAccessLog`.

---

## 14. Authentication & Identity

| Guard | Model | Where |
| --- | --- | --- |
| `saas_admin` | `SaasAdmin` | central DB |
| `school_users` | `SchoolUser` | tenant DB (multi-role via Spatie) |

- `SchoolInvitation` model (central) tracks invite-token flows: `admin_invite`, `password_reset`, parent invites, student logins. Tokens valid for 3 hours; old tokens auto-invalidated on reissue.
- `User` legacy model exists but is essentially unused.
- Events: `StudentEnrolled` → `SyncStudentCountOnEnroll`; `StudentDeactivated` → `SyncStudentCountOnDeactivate`; `WhatsAppMessageReceived` → `SendWhatsAppInboxNotification`.

---

## 15. Domain Model (per-tenant database)

Grouped highlights of models in `app/Models/Tenant/`:

- **Academic**: `AcademicYear`, `SchoolClass`, `Section`, `Subject`, `ClassSubject`, `Syllabus`, `SyllabusTopic`, `GradeRule`, `ClassRoutine`.
- **Exams**: `Exam`, `ExamSchedule`, `ExamMark`, `ExamResult`, `AnnualResult`, `ResultCard`.
- **Students & admissions**: `Student`, `StudentGuardian`, `StudentPromotion`, `StudentApplication`, `StudentDocument`, `StudentFee`, `AdmissionSession`, `AdmissionCriteria`, `AdmissionTest`, `AdmissionTestQuestion`, `AdmissionTestAnswer`, `AdmissionTestAttempt`, `AdmissionDelegation`, `StudentAttendanceSummary`.
- **Attendance**: `AttendanceRecord`, `AttendanceDevice`, `AttendanceSetting`, `BiometricLog`, `StaffAttendanceRecord`, `OnlineClassAttendance`.
- **Finance**: `FeeStructure`, `FeeType`, `FeeGroup`, `FeeMaster`, `FeePayment`, `FeePaymentItem`, `FeeInstallmentPlan`, `FeeInstallmentItem`, `Invoice`, `Expense`, `ExpenseCategory`, `Budget`, `PaymentGatewayLog`.
- **Staff**: `StaffProfile`, `Designation`, `Department`, `LeaveType`, `LeaveRequest`, `StaffPayroll`, `SalaryComponent`, `RoleSuccession`.
- **Hostel**: `HostelBuilding`, `HostelRoom`, `HostelRoomType`, `HostelAllocation`, `HostelGatePass`.
- **Transport**: `Vehicle`, `TransportRoute`, `TransportStop`, `TransportAssignment`.
- **Library**: `Book`, `BookCategory`, `BookIssue`, `LibraryMember`.
- **Health/Behavior**: `HealthRecord`, `BehaviorIncident`.
- **Online classes**: `OnlineClass`, `OnlineClassPlatform`, `OnlineClassQuiz`.
- **Comms**: `NotificationTemplate`, `NotificationLog`, `InAppNotification`, `CommunicationLog`, `WhatsAppMessage`, `WhatsAppConversation`, `AiConversation`, `AiMessage`, `AiUsageLog`.
- **CMS**: `CmsSetting`, `CmsPage`, `CmsAnnouncement`, `CmsSlider`, `CmsGalleryAlbum`, `CmsGalleryPhoto`.
- **Other**: `Notice`, `Campus`, `DailyActivityLog`, `Visitor`, `CafeteriaMenuItem`, `CafeteriaTransaction`, `InventoryStore`, `InventoryItem`, `InventoryCategory`, `InventoryTransaction`, `InventorySupplier`, `IdCardTemplate`, `CertificateTemplate`, `GeneratedCertificate`, `CustomReport`, `PiiAccessLog`.

### Enums (in `app/Enums/`)

`ApplicationStatus`, `ApprovalStatus`, `TenantStatus`, `StudentStatus`, `ExamStatus`, `ExamType`, `PayrollStatus`, `SignupStatus`, `ActionType` (20+ approval types), `StudentGender`, `BloodGroup`, `EmploymentType`, `GuardianType`, `CommunicationChannel`, `FeePaymentMethod`, `PaymentMethod`, `AttendanceStatus`, `AttendanceMode`, `LeaveStatus`, `ExamResultStatus`, `BookIssueStatus`, `BehaviorIncidentStatus`, `StudentDocumentType`.

---

## 16. Service Layer (`app/Services/`)

- **Approvals**: `ApprovalService`.
- **Domains**: `CustomDomainService`.
- **Admissions**: `StudentApplicationService`, `AdmissionScoringService`, `AdmissionDelegationService`, `AdmissionAiService`, `RunnerUpService`, `DocumentExtractor`.
- **Students**: `StudentBulkImporter`, `StudentAccountActivator`, `InfixImportService`.
- **Fees & finance**: `FeesService`.
- **Exams**: `ExamService`, `AnnualResultService`.
- **Attendance**: `AttendanceService`.
- **Library**: `LibraryService`.
- **Reports**: `ReportBuilderService`.
- **Comms**: `NotificationService`, WhatsApp/SMS/Push driver factories.
- **AI**: `Ai\AiAssistant`, `Ai\OpenRouterService`, `Ai\RolePrompts`, `Ai\RoleScopedFacts`.
- **Payments**: `Payment\Drivers\{JazzCash,EasyPaisa,Stripe}Gateway`.
- **Other**: `CertificateService`, `RoleSuccessionService`, `DeepLinkService`.

---

## 17. Configuration Files (selected)

| File | Purpose |
| --- | --- |
| `config/tenancy.php` | Central domains, tenant model, bootstrappers, isolation |
| `config/permission.php` | Spatie permissions (guard, cache) |
| `config/approval.php` | Action-type → handler map |
| `config/audit.php` | Audit credentials/config |
| `config/cert.php` | SSL listener URL, retries, renewal window |
| `config/platform_apis.php` | Platform-fallback SMS/WhatsApp/AI creds |
| `config/services.php` | Third-party (Resend, payment gateways, etc.) |
| `config/queue.php` | Queue connection, retry |
| `config/filament.php` | Panel registration |
| `config/dompdf.php` | PDF rendering |

---

## 18. Operational Notes (memory-derived)

- **Production topology**: host nginx is dead. The active reverse proxy is the nginx **inside** the `kynex-app` container, shared with an unrelated AI app on the same box.
- **Tenancy fallback**: central-host + session-stored `tenant_id` is the third resolution path and is load-bearing — do not drop without a migration plan.
- **Custom-domain nginx configs** (e.g., `custom-aqmdigital.com.conf`) only exist in `kynex-app`'s writable layer; recreating the container wipes them. The Phase 1.5 cert workstream is the redesign.
- **Custom-domain management UI is SaaS-only.** School Admin must never `Domain::create/update/delete` or call `CustomDomainService`. Read-only displays are fine.
- **Env-file edits are dormant** on a running stack. `.env.production` / `.env.docker` only take effect after `docker compose up -d --force-recreate`.

---

## 19. Quick Counts

| Surface | Count |
| --- | --- |
| Filament panels | 3 (SaaS Admin, School Admin, Parent Portal) |
| SaaS Admin resources | 7 |
| SaaS Admin pages | 6 (incl. Dashboard, ApiSettings, CampusManagement, AssignInstituteHead, AccessDenied, Login) |
| SaaS Admin widgets | 9 |
| School Admin resources | ~60 |
| School Admin custom pages | ~32 |
| School Admin widgets | ~50 (many role-scoped) |
| Approval handlers | 15 |
| Background jobs | 22 |
| Console commands | 16 |
| SMS drivers | 6 |
| WhatsApp drivers | 3 |
| Payment gateways | 3 |
| REST API endpoints | ~18 |
| Tenant models | 80+ |
| Permissions | ~150 across 9 categories |

---

*This document is regeneratable. If anything drifts, the `audit:run` command will surface UI/code mismatches and findings show up under SaaS Admin → Audit.*
