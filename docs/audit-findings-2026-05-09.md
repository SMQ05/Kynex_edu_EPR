# Audit Findings — 2026-05-09

## Run Metadata

| Field          | Value |
|----------------|-------|
| Run ID         | `755cf402-9d83-43e5-abcc-1440aaca2cce` |
| Date           | 2026-05-08 23:10–23:12 UTC (136 seconds) |
| Scope          | `--all` → `haji-qamar-public-school-BEb3S9`, all 7 school roles |
| SaaS panel     | Skipped — `AUDIT_SAAS_ADMIN_PASSWORD` not set |
| Pages crawled  | 119 |
| Findings total | 110 (107 HIGH · 3 MEDIUM · 0 CRITICAL · 0 LOW) |
| Report file    | `/var/www/html/storage/audit-reports/755cf402-9d83-43e5-abcc-1440aaca2cce.json` |

---

## Severity × Role Matrix

| Role           | CRITICAL | HIGH | MEDIUM | LOW | Total | Notes |
|----------------|:--------:|:----:|:------:|:---:|------:|-------|
| SCHOOL_ADMIN   | 0 | 89 | 0 | 0 | **89** | ~81 pages crawled |
| INSTITUTE_HEAD | — | — | — | — | — | Login failed (credential mismatch) |
| REGISTRAR      | 0 | 0 | 0 | 0 | 0 | Crawled, clean |
| ACCOUNTANT     | 0 | 0 | 2 | 0 | **2** | |
| TEACHER        | 0 | 18 | 0 | 0 | **18** | ~20 pages crawled |
| PARENT         | 0 | 0 | 0 | 0 | 0 | Crawled, clean |
| STUDENT        | 0 | 0 | 1 | 0 | **1** | |

**Concentration**: SCHOOL_ADMIN alone accounts for 81% of all findings (89/110). TEACHER contributes 16%. All other roles together: 3%.

---

## Findings by Type

| Type                  | Count | Severity | Roles Affected |
|-----------------------|------:|----------|----------------|
| `panel_chrome_missing` | 105  | HIGH     | SCHOOL_ADMIN (88), TEACHER (17) |
| `blank_page`          | 2    | HIGH     | SCHOOL_ADMIN, TEACHER |
| `js_error`            | 2    | MEDIUM   | ACCOUNTANT |
| `security_violation`  | 1    | MEDIUM   | STUDENT |

---

## Cross-Role Overlap Analysis

Finding hashes include `role` by design (same bug on same URL = distinct hash per role). Grouping by `url + finding_type` across roles yields:

**17 URL+type pairs** affect both SCHOOL_ADMIN and TEACHER simultaneously. A single fix to the shared component resolves the finding for both roles.

| URL path | Type |
|----------|------|
| `/admin/student-applications/create` | `blank_page` |
| `/admin/student-categories` | `panel_chrome_missing` |
| `/admin/mark-attendance` | `panel_chrome_missing` |
| `/admin/students` | `panel_chrome_missing` |
| `/admin/student-bulk-import` | `panel_chrome_missing` |
| `/admin/syllabi` | `panel_chrome_missing` |
| `/admin/exams` | `panel_chrome_missing` |
| `/admin/results-page` | `panel_chrome_missing` |
| `/admin/grading-weights` | `panel_chrome_missing` |
| `/admin/annual-results` | `panel_chrome_missing` |
| `/admin/notices` | `panel_chrome_missing` |
| `/admin/send-student-message` | `panel_chrome_missing` |
| `/admin/behavior-incidents` | `panel_chrome_missing` |
| `/admin/online-classes` | `panel_chrome_missing` |
| `/admin/cms-announcements` | `panel_chrome_missing` |
| `/admin/help-center` | `panel_chrome_missing` |

The 16 shared `panel_chrome_missing` paths are all pages accessible to both SCHOOL_ADMIN and TEACHER. The remaining 72 `panel_chrome_missing` findings (SCHOOL_ADMIN-only) are on pages exclusive to that role.

---

## Detailed Findings

### HIGH — `panel_chrome_missing` (105 findings)

**One root cause suspected.**

The crawler's sidebar-presence check looks for:
```javascript
document.querySelector('nav.fi-sidebar')   // preferred
document.querySelector('.fi-sidebar')       // fallback
document.querySelector('nav[aria-label="sidebar"]') // last resort
```

This check fires false on **every resource page** for SCHOOL_ADMIN and TEACHER, but **not** for ACCOUNTANT, REGISTRAR, PARENT, or STUDENT.

**Critical data point**: The STUDENT role's `/admin` dashboard *passes* the sidebar check (that finding is `security_violation`, not `panel_chrome_missing`). The dashboard has the sidebar; resource pages apparently don't.

**Two candidate root causes — must be verified with browser DevTools before writing any code:**

**A) Selector mismatch (most likely if the panel looks fine in a browser)**
Filament v5 may render the sidebar as `<aside class="fi-sidebar">` or with an additional wrapper that changes the structure on authenticated pages but not the dashboard. If the browser shows a working sidebar on `/admin/students`, the CSS selectors in `crawl.js` check 3 need updating to match the actual DOM.

**B) Real regression (less likely, but must rule out)**
A layout change in Filament v5 or a provider registration issue silently removed the sidebar from resource pages. ACCOUNTANT/REGISTRAR panels may be unaffected because they use fewer sidebar items or hit a different code path.

**Verification step** (do this before any fix session):
1. Log in as SCHOOL_ADMIN at `https://aqmdigital.com/admin`
2. Navigate to `https://aqmdigital.com/admin/students`
3. DevTools → Elements → search for `fi-sidebar`
4. If found: update `crawl.js` selector (false positive). If absent: real sidebar regression.

**Affected paths — SCHOOL_ADMIN (88 pages):**
```
/admin/admission-schedule         /admin/admission-criterias
/admin/admission-exam-attendance  /admin/admission-tests
/admin/admission-marks-entry      /admin/admission-delegations
/admin/student-categories         /admin/mark-attendance
/admin/students                   /admin/student-bulk-import
/admin/academic-years             /admin/classes
/admin/sections                   /admin/subjects
/admin/class-routines             /admin/teaching-assignments
/admin/class-routine-planner      /admin/syllabi
/admin/exams                      /admin/grades
/admin/results-page               /admin/grading-weights
/admin/annual-results             /admin/staff
/admin/school-users               /admin/staff-attendances
/admin/salary-components          /admin/staff-payrolls
/admin/leave-requests             /admin/leave-types
/admin/departments                /admin/designations
/admin/notices                    /admin/send-student-message
/admin/notification-templates     /admin/notification-composer
/admin/visitors                   /admin/books
/admin/book-issues                /admin/vehicles
/admin/transport-routes           /admin/hostel-buildings
/admin/hostel-room-types          /admin/hostel-rooms
/admin/hostel-allocations         /admin/hostel-gate-passes
/admin/inventory-categories       /admin/inventory-stores
/admin/inventory-suppliers        /admin/inventory-items
/admin/inventory-transactions     /admin/cafeteria-menu-items
/admin/health-records             /admin/behavior-incidents
/admin/online-classes             /admin/platform-settings
/admin/certificate-templates      /admin/issue-certificate
/admin/generate-id-cards          /admin/id-card-templates
/admin/attendance-report          /admin/report-builder-page
/admin/analytics-dashboard        /admin/cms-settings
/admin/cms-sliders                /admin/cms-pages
/admin/cms-announcements          /admin/cms-gallery-albums
/admin/attendance-settings        /admin/notification-settings-page
/admin/communication-settings-page /admin/infix-import
/admin/pii-access-logs            /admin/campuses
/admin/role-management            /admin/approval-queue
/admin/attendance-devices         /admin/fee-catalog
/admin/fee-collection-page        /admin/fee-reports-page
/admin/fee-masters                /admin/generate-fees
/admin/fee-defaulters             /admin/financial-report
/admin/expense-categories         /admin/budgets
/admin/expenses                   /admin/help-center
```

**Affected paths — TEACHER (17 pages, subset of above):**
```
/admin/student-categories  /admin/mark-attendance     /admin/students
/admin/student-bulk-import /admin/syllabi             /admin/homework-assignments
/admin/exams               /admin/marks-entry         /admin/results-page
/admin/grading-weights     /admin/annual-results      /admin/notices
/admin/send-student-message /admin/behavior-incidents /admin/online-classes
/admin/cms-announcements   /admin/help-center
```

---

### HIGH — `blank_page` (2 findings)

**URL**: `https://aqmdigital.com/admin/student-applications/create`  
**Roles**: SCHOOL_ADMIN and TEACHER (shared — same underlying bug)  
**Trigger**: Crawler submits the create form with all fields empty → receives HTTP 200 with body < 100 chars, no `<main>` element, no empty-state widget.

**What this means**: The form's submit/validation path renders a blank page instead of showing validation errors. A real user submitting an incomplete form would see nothing.

**Likely cause**: Missing `withErrors()` redirect on validation failure in `StudentApplicationResource` create page, OR a Livewire component that silently fails without re-rendering.

**File to check**: `app/Filament/SchoolAdmin/Resources/StudentApplicationResource.php` → CreateStudentApplication page action; also the form's `mutateFormDataBeforeCreate()` or `handleRecordCreation()` if overridden.

**Impact**: Medium-severity UX regression for school admins and teachers creating student applications.

---

### MEDIUM — `js_error` (2 findings)

**URL**: `https://aqmdigital.com/admin` (dashboard)  
**Role**: ACCOUNTANT  

**Finding 1**: `Uncaught page error: Object` — an unhandled JS exception in a dashboard widget or component.  
**Finding 2**: `JS error: Failed to load resource: the server responded with a status of 5xx` — a Livewire poll or XHR request from the dashboard is hitting a 5xx endpoint.

**Diagnosis path**: Log in as ACCOUNTANT, open DevTools → Network tab, reload `/admin`, find the failing request (filter by `5xx` status). The endpoint it hits is the actual bug; the JS error is downstream.

**Likely cause**: A stats widget or chart component registered in the ACCOUNTANT dashboard calls a route or Livewire method that throws. Could be a `DB::select()` or Eloquent query that fails for a scope the ACCOUNTANT doesn't have.

**Impact**: ACCOUNTANT dashboard may render partially or with broken widget(s).

---

### MEDIUM — `security_violation` (1 finding)

**URL**: `https://aqmdigital.com/admin`  
**Role**: STUDENT  
**Description**: STUDENT role accessed `/admin`, the sidebar was present (panel chrome OK), but no "Access Denied" text was found in the page body. The negative test expects either: (a) access denied message + sidebar = OK, or (b) a redirect away from `/admin`.

**What happened**: STUDENT can load the school admin panel dashboard without seeing an explicit "You don't have permission" message. The panel may be showing an empty dashboard rather than blocking access.

**Likely cause**: `SchoolAdminPanelProvider` auth middleware doesn't explicitly block STUDENT, or the panel renders an empty view for STUDENT rather than a proper access-denied error page.

**Impact**: STUDENT may be able to browse `/admin` URLs. Even if they see empty data, their presence in the admin panel represents an authorization boundary failure.

**Fix**: Ensure `SchoolAdminPanelProvider` middleware redirects STUDENT away from `/admin` entirely, or that the access-denied page includes text matching the crawler's check (e.g. "Access Denied", "You don't have permission", "403", or similar).

---

## Known Gaps

| Gap | Cause | Fix |
|-----|-------|-----|
| INSTITUTE_HEAD: 0 pages (login failed) | Demo user `qmr750@gmail.com` credentials rejected | Reset password in tenant DB for `haji-qamar-public-school-BEb3S9` |
| SaaS panel: skipped | `AUDIT_SAAS_ADMIN_PASSWORD` not in `.env.production` | Add env var + `docker compose restart app` |
| REGISTRAR / PARENT: appear clean | May have very limited nav sets — crawler had fewer links to follow | Acceptable; zero findings is a valid result |

---

## Fix Priority for Next Session

| # | Finding | Effort | Impact |
|---|---------|--------|--------|
| 1 | **Verify `panel_chrome_missing`** — browser DevTools on `/admin/students` as SCHOOL_ADMIN | 5 min | Determines if 105 findings are real or selector bug |
| 2 | **Fix STUDENT `security_violation`** — block STUDENT in SchoolAdminPanelProvider | Low | Closes auth boundary gap |
| 3 | **Fix `blank_page`** on student-applications/create | Low-Medium | Fixes for both SCHOOL_ADMIN and TEACHER simultaneously |
| 4 | **Fix ACCOUNTANT dashboard JS error** — identify the failing XHR endpoint | Medium | Dashboard stability |
| 5 | **Fix INSTITUTE_HEAD credentials** — reset demo user in tenant DB | Low | Unblocks that role in future runs |
| 6 | **Set `AUDIT_SAAS_ADMIN_PASSWORD`** — `.env.production` + restart | 2 min | Adds SaaS panel coverage |

> **Note**: Items 2–4 are independent. If verification (item 1) reveals `panel_chrome_missing` is a selector bug, fix the `crawl.js` selector and re-run — the count will drop from 105 to near zero, exposing real issues underneath.
