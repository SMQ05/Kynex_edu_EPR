# Audit Framework Investigation — 2026-05-08

## 1. Panel Inventory

KynexEdu has four Filament panels registered via `app/Providers/Filament/`:

| Panel ID | Path | Guard | Provider |
|---|---|---|---|
| `saas-admin` | `/saas` | `saas_admin` | `SaasAdminPanelProvider` |
| `school-admin` | `/admin` | `school_users` | `SchoolAdminPanelProvider` |
| `parent` | `/parent` | `school_users` | `ParentPanelProvider` |
| _(Shared)_ | — | — | Shared components only, no panel |

**No student panel exists.** The `STUDENT` role is a Spatie permission role assigned to `SchoolUser` model instances, but there is no Filament panel mounted for students. After login, STUDENT users are redirected to `/admin` but will hit the `AccessDenied` page (role-based nav filtering). The audit should cover STUDENT login + /admin landing as a separate finding category.

---

## 2. Login and Logout URLs per Panel

### SaaS Admin Panel
- **Login:** `https://sms.kynexsolutions.com/saas/login`
- **Logout:** `https://sms.kynexsolutions.com/saas/logout` (Filament default, POST)
- **Credential:** `admin@kynexedu.com` — password NOT from demo formula (SaaS admin is a separate `SaasAdmin` model, not a `SchoolUser`)
- **Note:** The login page overrides the failure message with an IP-restriction message. The audit runner must be on a whitelisted IP or the test will always fail. Must verify connectivity before attempting.

### School Admin Panel (staff roles: school_admin, institute_head, registrar, accountant, teacher)
- **Login:** `https://sms.kynexsolutions.com/login` (central host, session-based tenant routing)
  - OR `https://aqmdigital.com/login` for aqmdigital tenant (custom domain, skips tenant scan)
- **Logout:** POST to `https://sms.kynexsolutions.com/admin/logout` → custom `SchoolAdminLogoutResponse` redirects to `/login`
- **Panel URL after login:** `/admin` (same host as login)

### Parent Portal Panel
- **Login:** Same `/login` URL as school admin (shared `school_users` guard). `EnsureParentRole` middleware on `/parent` routes enforces the PARENT role; others get 403.
- **Panel URL:** `/parent`
- **Logout:** `/parent/logout`

---

## 3. Tenant Context Establishment

Resolved in `InitializeTenancyBySubdomainOrDomain` middleware. Three modes:

### Mode 1: Central host + session fallback (load-bearing)
- Host is `sms.kynexsolutions.com` (in `tenancy.central_domains`)
- After login, `SchoolPortalController::attemptTenantLogin()` calls `tenancy()->initialize($tenant)` and sets `session('tenant_id', $tenant->id)`
- Every subsequent request reads `tenant_id` from session
- **Implication for audit:** The crawler must maintain a cookie jar so the session cookie carries between requests. Playwright does this automatically per browser context.

### Mode 2: Subdomain
- Host matches `{slug}.sms.kynexsolutions.com` pattern
- `extractSubdomain()` strips the central domain suffix → tenant slug → `Tenant::find($slug)`
- **al-qasim access:** `al-qasim-school-HStisi.sms.kynexsolutions.com/login`

### Mode 3: Verified custom domain
- `domains` table lookup → `is_verified=true`, `domain_type='custom'`
- **aqmdigital access:** `aqmdigital.com/login` → resolves to `haji-qamar-public-school-BEb3S9`

---

## 4. Tenant Roster (from central DB)

| Tenant ID | School Name | Admin Email | Custom Domain | Access Host |
|---|---|---|---|---|
| `haji-qamar-public-school-BEb3S9` | AQM Public School | qmr750@gmail.com | aqmdigital.com (verified) | `aqmdigital.com` |
| `al-qasim-school-HStisi` | Al Qasim School | smqasim08@gmail.com | none | `al-qasim-school-HStisi.sms.kynexsolutions.com` |

---

## 5. Credentials and Role Roster

### Credential Derivation Formula
From `database/seeders/Demo/Support/Pak::demoPassword()`:
```
password = 'Demo2026@' . substr(sha1($roleKey . $email . $appKey), 0, 6)
```

Role → roleKey mapping from `Pak::roleKeyFor()`:

| Role (Spatie name) | roleKey |
|---|---|
| `SCHOOL_ADMIN` | `admin` |
| `INSTITUTE_HEAD` | `principal` |
| `REGISTRAR` | `vice-principal` |
| `ACCOUNTANT` | `accountant` |
| `TEACHER` | `teacher` |
| `PARENT` | `parent` |
| `STUDENT` | `student` |

**IMPORTANT:** The `$appKey` is `config('app.key')` at runtime — the audit framework must compute passwords inside the PHP process (where `config('app.key')` resolves), then pass them to the Node Playwright script via a secure IPC mechanism (environment variable or a one-time temp file with mode 600, deleted after read). Never log the derivation or the result.

### SaaS Admin
- `admin@kynexedu.com` — password stored in `saas_admins` table; not derivable via demo formula. Must be supplied via environment variable `AUDIT_SAAS_ADMIN_PASSWORD`.

### aqmdigital.com Users (haji-qamar-public-school-BEb3S9)
Fully seeded with demo data:

| Role | Name | Email |
|---|---|---|
| SCHOOL_ADMIN | Qamar Abbas | admin@aqmdigital.com |
| INSTITUTE_HEAD | Khalid Mahmood | principal@aqmdigital.com |
| REGISTRAR | Saima Naveed | saima.naveed@aqmdigital.com |
| ACCOUNTANT | Imran Sheikh | imran.sheikh@aqmdigital.com |
| TEACHER | Bushra Iqbal | bushra.iqbal@aqmdigital.com |
| PARENT | Khadija Hassan | khadija.hassan@aqmdigital.com |
| STUDENT | Nadia Aslam | nadia.aqm2025043@aqmdigital.com |

### al-qasim-school-HStisi Users
Sparsely seeded — only 2 users:

| Role | Name | Email | Notes |
|---|---|---|---|
| SCHOOL_ADMIN + INSTITUTE_HEAD | Syed Muhammad Qasim | smqasim08@gmail.com | Both roles stacked |
| MULTI_INSTITUTE_HEAD | Syeda Tahira | qasim@kynexsolutions.com | Unusual role, may lack nav |

**al-qasim audit scope:** Because al-qasim has only 2 users and no seeded demo data (no students, no fees, no exams), the breadth crawl will find mostly empty list pages rather than functioning features. This is itself useful (empty-state renders without errors). The audit should flag it as a data-sparse tenant and limit role coverage to school_admin only unless more users are seeded.

---

## 6. SchoolAdmin Panel: Resources and Pages

The panel auto-discovers ~60+ resources and ~30+ pages. Major navigation groups (from `SchoolAdminPanelProvider`):
`Dashboard`, `Admissions`, `Students`, `Academic Setup`, `Examinations`, `Attendance`, `Staff & HR`, `Fees & Finance`, `Communication`, `Front Office`, `Library`, `Transport`, `Hostel`, `Inventory`, `Cafeteria`, `Health & Wellbeing`, `Online Classes`, `Certificates & ID Cards`, `Reports`, `Website CMS`, `Settings`, `Compliance`, `System`

Full resource list (from `app/Filament/SchoolAdmin/Resources/`):
AcademicYear, AdmissionCriteria, AdmissionTest, AnnualResult, AttendanceDevice, AttendanceSettings, BehaviorIncident, BookIssue, Book, Budget, BulkStudentOperations, CafeteriaMenuItem, Campus, CertificateTemplate, Class, ClassRoutine, ClassSubject, CmsAnnouncement, CmsGalleryAlbum, CmsPage, CmsSlider, Department, Designation, Exam, ExpenseCategory, Expense, FeeCollection, FeeGroup, FeeMaster, FeeReports, FeeType, Grade, HealthRecord, HomeworkAssignment, HostelAllocation, HostelBuilding, HostelGatePass, HostelRoom, HostelRoomType, IdCardTemplate, InventoryCategory, InventoryItem, InventoryStore, InventorySupplier, InventoryTransaction, LeaveManagement, LeaveRequest, LeaveType, Notice, NotificationTemplate, OnlineClass, Payroll, PiiAccessLog, PlatformSettings, RoleManagement, SalaryComponent, SchoolUser, Section, StaffAttendance, StaffPayroll, Staff, StudentApplication, StudentCategory, Student, Subject, Syllabus, TransportRoute, Vehicle, Visitor

Major standalone pages:
Dashboard, AnalyticsDashboard, ApprovalQueue, AdmissionDelegations, AdmissionMarksEntry, AttendanceReport, ClassRoutinePlanner, CmsSettings, CommunicationSettingsPage, FeeCatalog, FeeCollectionPage, FeeDefaulters, FeeReportsPage, FinancialReport, GenerateFees, GenerateIdCards, GradingWeights, HelpCenter, InfixImport, IssueCertificate, MarkAttendance, MarksEntry, NotificationComposer, NotificationSettingsPage, ReportBuilderPage, ResultsPage, SendStudentMessage, StudentBulkImport, SwitchRole

### SaasAdmin Panel Resources:
ApprovalRequest, Invoice, SubscriptionPlan, Tenant, TenantSignup

### SaasAdmin Pages:
AccessDenied, ApiSettings, AssignInstituteHead, Dashboard, CampusManagement

### ParentPortal Pages:
Dashboard (read-only child info), AccessDenied

---

## 7. Existing Artisan Command Conventions

All custom commands live in `app/Console/Commands/` and use the `kynex:` prefix:
- `kynex:check-env` — environment validation
- `kynex:reset-demo` — demo tenant wipe/reseed
- `kynex:ensure-dev-demo-tenant` — dev environment setup
- `kynex:generate-monthly-invoices`
- `kynex:prune-pii-access-log`
- `kynex:refresh-materialized-views`
- `kynex:repair-school-user-roles`
- `kynex:reset-all-tenants`
- `kynex:seed-default-templates`
- `kynex:send-billing-notifications`
- `kynex:verify-pending-custom-domains`
- `kynex:wipe-tenant-data`

Command signatures use `--option=value` style with docblock descriptions.

**Proposal for audit commands:** Use `audit:` as a separate namespace (not `kynex:`) to clearly separate the audit infrastructure from operational commands. This follows the mission spec and keeps the two concerns distinct.

---

## 8. Node/Playwright Environment

- Node: v20.20.2 (system-wide at `/usr/bin/node`)
- npx: available at `/usr/bin/npx`
- Playwright CLI: v1.59.1 (accessible via `npx playwright`)
- Chromium binaries: installed at `~/.cache/ms-playwright/chromium-1224/`
- **No package.json Playwright dependency** — Playwright is system-installed, not a project dependency
- No Playwright config file in the project

**Implication:** The audit Node script can `require('playwright')` directly using the system installation. The Node script does not need to be included in `package.json` (which is Vite/frontend only). It should be committed to `storage/audit-scripts/` as a standalone `.js` script.

**Caveat:** If the stack is rebuilt on a different host, `npx playwright install` must be run. The `audit:run` command should check Playwright availability at startup and abort with a clear message if not found.

---

## 9. Recommended Command Structure

Given the investigation, a **parent command + subcommands** pattern makes sense because:
- `audit:run` is a long-running operation (spawns Playwright)
- `audit:report` is a fast read-only reporting command
- Subcommand separation allows running just reporting without triggering a crawl

Proposed commands:
- `audit:run --tenant=<slug> --role=<role>` — single tenant + role crawl
- `audit:run --all` — every tenant + every role (per-role sequential within each tenant)
- `audit:run --tenant=<slug> --role=<role> --url=<url>` — single URL test
- `audit:report [--run-id=<id>] [--latest] [--since=<iso-date>]` — human-readable findings

---

## 10. Key Architectural Decisions for Design Phase

1. **Playwright approach:** Standalone Node script spawned by PHP (`Process::run('node storage/audit-scripts/crawl.js ...')`). The Node script outputs newline-delimited JSON (one finding per line) to stdout. PHP reads it line-by-line and persists. This is simpler than a Playwright server. Avoids MCP (which is for interactive Claude Code use, not unattended runs).

2. **Central DB only:** `audit_runs` and `audit_findings` tables go on the `central` DB connection (not tenant DBs). Use `protected $connection = 'central';` on the models.

3. **Credential security:** Passwords computed in PHP (`Pak::demoPassword()` or equivalent inline formula), written to a temp file at mode 0600, path passed to the Node script, file deleted after the browser context logs in. Never appear in process args, logs, or DB.

4. **Screenshot storage:** `storage/app/audit-screenshots/<run_id>/` — gitignored, not committed. The `audit_findings` table stores the relative path; the Filament resource serves the image.

5. **al-qasim audit scope:** Only crawl as `school_admin` (smqasim08@gmail.com) unless more roles are seeded. Flag data sparseness in the run metadata.

6. **STUDENT role:** Test login + `/admin` landing. Expect `AccessDenied` page (correct behavior). Flag if anything other than 200 + correct AccessDenied content is returned.

7. **SaaS Admin password:** Must be injected via env var `AUDIT_SAAS_ADMIN_PASSWORD`. The `audit:run` command aborts if this var is missing when a SaaS panel crawl is requested.
