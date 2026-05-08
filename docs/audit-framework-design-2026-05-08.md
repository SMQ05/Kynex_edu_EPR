# Audit Framework Design — 2026-05-08

Builds on investigation doc `audit-framework-investigation-2026-05-08.md`.  
Status: **design only — no code written yet**.

### Rev2 changelog (2026-05-08)

Three refinements applied after design approval:

| # | Refinement | Sections updated |
|---|---|---|
| 1 | al-qasim subdomain access must be verified (Step 0) before build begins | New §0, §15 |
| 2 | Integration test route (`/audit-test/deliberate-500`) must be environment-gated (`local`+`testing` only) | §14 |
| 3 | `audit:prune` command spec added (build deferred until post-Phase 2A first run) | §1 |

### Rev3 changelog (2026-05-08)

Step 0 executed: al-qasim subdomain DNS does not resolve (no wildcard A record; cert is single-hostname only). Decision recorded and design updated to reflect exclusion:

| # | Change | Sections updated |
|---|---|---|
| 1 | `--all` default scope limited to haji-qamar only; al-qasim excluded until it has a custom domain or wildcard DNS+cert | §0, §1, §15 |
| 2 | `--tenant=al-qasim-school-HStisi` remains a valid flag but produces a hard abort with remediation instructions | §1 |
| 3 | Login URL table updated: al-qasim row marked blocked | §7 |
| 4 | audit:report example output updated to single-tenant | §10 |
| 5 | Follow-up docket added (al-qasim access workstream + Stancl 302 bug) | New §16 |

---

## 0. Pre-Build Gate: al-qasim Subdomain Verification

**Run before writing any code.** If this check fails, stop and do not proceed to §3 build.

```bash
curl -sk -o /dev/null -w '%{http_code} %{url_effective}\n' \
  https://al-qasim-school-HStisi.sms.kynexsolutions.com/login
```

**Expected:** Any HTTP response (200, 302, 301, 4xx). Any status code means DNS resolved and the TCP/TLS connection succeeded — the subdomain routing works.

**Fail conditions:**
- `curl: (6) Could not resolve host` → DNS is not configured. Wildcard `*.sms.kynexsolutions.com` A record is missing.
- `curl: (35) SSL peer handshake failed` → TLS wildcard cert is not provisioned for `*.sms.kynexsolutions.com`.
- No response / connection refused → nginx or reverse proxy is not routing the subdomain.

**Result (2026-05-08):** `curl: (6) Could not resolve host` — DNS wildcard missing. Cert is also single-hostname (`sms.kynexsolutions.com` only, no SAN wildcard). Fixing subdomain access requires both a Cloudflare wildcard A record AND a wildcard cert reissue — a real workstream, not a one-liner.

**Decision recorded:** al-qasim excluded from `--all` default scope. Audit proceeds on haji-qamar only. See §1 for `--tenant=al-qasim-school-HStisi` abort behavior and §16 for the follow-up docket item.

---

## 1. Command Structure

Two top-level Artisan commands under the `audit:` namespace:

### `audit:run`

```
php artisan audit:run
    [--tenant=<slug>]       # target a specific tenant
    [--role=<role>]         # target a specific role (requires --tenant)
    [--url=<url>]           # single-URL test (requires --tenant and --role)
    [--all]                 # crawl every eligible tenant × every role (haji-qamar only — al-qasim excluded, see below)
    [--skip-saas]           # skip SaaS admin panel even when --all is given
    [--max-urls=<n>]        # cap per-role URL count (smoke-test mode, default: unlimited)
    [--page-timeout=<ms>]   # per-page navigation timeout (default: 30000)
    [--role-timeout=<ms>]   # per-role total timeout (default: 1200000 = 20 min)
    [--total-timeout=<ms>]  # total run timeout (default: 5400000 = 90 min)
    [--headless]            # run browser headless (default: true; use --no-headless to debug)
```

**Mutual exclusivity rules:**
- `--all` and `--tenant`/`--role` are mutually exclusive; command aborts if both given.
- `--role` requires `--tenant`; command aborts otherwise.
- `--url` requires both `--tenant` and `--role`.

**SaaS admin password handling:**
- When SaaS panel is in scope (explicit via `--tenant=saas` or implicitly via `--all`):
  - Check `env('AUDIT_SAAS_ADMIN_PASSWORD')` at startup.
  - If missing AND `--skip-saas` is NOT set AND `--tenant=saas` was explicit → abort with:
    ```
    ERROR: AUDIT_SAAS_ADMIN_PASSWORD not set.
    Set it in .env.production or use --skip-saas to omit the SaaS panel.
    ```
  - If missing AND `--all` was used (no explicit `--skip-saas`) → skip SaaS panel silently,
    set `run.saas_skipped = true` in the run record, print a warning line, continue with
    school tenants.
- Run summary always states: `SaaS panel: crawled | skipped (AUDIT_SAAS_ADMIN_PASSWORD not set) | skipped (--skip-saas)`.

**al-qasim tenant exclusion:**
- `--all` never includes `al-qasim-school-HStisi`. It is not in the eligible tenant list until it has a verified custom domain OR wildcard DNS + cert provisioned.
- `--tenant=al-qasim-school-HStisi` is accepted as a flag but produces a hard abort:
  ```
  ERROR: al-qasim-school-HStisi access requires either:
    (a) A verified custom domain configured in the SaaS admin UI, OR
    (b) Wildcard *.sms.kynexsolutions.com DNS record + wildcard TLS cert
  Neither is currently configured. Aborting.
  To skip this check and attempt anyway, use --force-tenant (not recommended).
  ```
  This is a loud fail-fast rather than a silent skip — future operators must not hit this trap unknowingly.

### `audit:prune` _(spec only — not built in Phase 2A)_

```
php artisan audit:prune
    [--days=30]              # delete screenshot dirs from runs older than N days
    [--reports-days=90]      # delete JSON report files from runs older than N days
    [--dry-run]              # show what would be deleted without deleting
```

**Behavior:**
- Find `audit_runs` with `started_at < now() - days`. Delete `storage/app/audit-screenshots/{run_id}/`.
- Find `audit_runs` with `started_at < now() - reports_days`. Delete `storage/app/audit-reports/{run_id}.json`.
- `audit_runs` and `audit_findings` DB rows are **preserved indefinitely** (small, queryable, needed for trend tracking).
- Planned schedule: nightly at 03:00 via Laravel scheduler in `Kernel.php`.

**Rationale for deferral:** Build after the Phase 2A first full run establishes real screenshot/report volume. Pruning non-existent files is a no-op; building the command prematurely adds untested code. Schedule it once we have real data to prune.

---

### `audit:report`

```
php artisan audit:report
    [--run-id=<uuid>]       # specific run
    [--latest]              # most recent run (default if neither given)
    [--since=<iso-date>]    # all runs since date
    [--severity=<level>]    # filter by severity
    [--tenant=<slug>]       # filter by tenant
    [--format=text|json]    # output format (default: text)
```

Reads from `audit_runs` + `audit_findings` on the central DB. Produces a human-readable findings summary. Does NOT trigger a crawl.

---

## 2. Database Schema (Central DB)

Both tables use `protected $connection = 'central'` on their models. Migration runs via `php artisan migrate` (central DB, not `tenants:migrate`).

### `audit_runs`

```sql
CREATE TABLE audit_runs (
    id           UUID PRIMARY KEY,
    scope        VARCHAR(20) NOT NULL,        -- 'all' | 'tenant' | 'role' | 'url'
    tenant_slug  VARCHAR(120) NULLABLE,       -- null when scope='all'
    role         VARCHAR(60) NULLABLE,        -- null when scope='all' or 'tenant'
    url          TEXT NULLABLE,               -- only when scope='url'
    status       VARCHAR(20) NOT NULL         -- 'running' | 'complete' | 'timeout' | 'incomplete' | 'error'
                 DEFAULT 'running',
    saas_skipped BOOLEAN NOT NULL DEFAULT FALSE,
    saas_skip_reason VARCHAR(80) NULLABLE,    -- 'env_missing' | 'flag' | null
    started_at   TIMESTAMP NOT NULL,
    finished_at  TIMESTAMP NULLABLE,
    total_pages  INTEGER NOT NULL DEFAULT 0,
    findings_critical INTEGER NOT NULL DEFAULT 0,
    findings_high     INTEGER NOT NULL DEFAULT 0,
    findings_medium   INTEGER NOT NULL DEFAULT 0,
    findings_low      INTEGER NOT NULL DEFAULT 0,
    findings_info     INTEGER NOT NULL DEFAULT 0,
    last_url_crawled  TEXT NULLABLE,          -- where timeout/abort occurred
    last_role_crawled VARCHAR(60) NULLABLE,
    json_report_path  TEXT NULLABLE,          -- relative to storage_path()
    meta              JSONB NOT NULL DEFAULT '{}',
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP
);
```

### `audit_findings`

```sql
CREATE TABLE audit_findings (
    id               UUID PRIMARY KEY,
    run_id           UUID NOT NULL REFERENCES audit_runs(id) ON DELETE CASCADE,
    finding_hash     VARCHAR(64) NOT NULL,   -- SHA-256 of (tenant_slug|role|url|finding_type|title)
                                              -- stable identifier across runs for dedup
    tenant_slug      VARCHAR(120) NOT NULL,
    role             VARCHAR(60) NOT NULL,
    url              TEXT NOT NULL,
    finding_type     VARCHAR(40) NOT NULL,
    -- Enum: 5xx | 4xx | js_error | network_failure | broken_image |
    --       malformed_image_url | empty_image_src | filament_error_toast |
    --       form_500_on_validation | slow_page | blank_page | security_violation |
    --       panel_chrome_missing | access_denied_ok
    severity         VARCHAR(10) NOT NULL,
    -- Enum: critical | high | medium | low | info | ok
    -- 'ok' is used only for access_denied_ok (positive assertion findings)
    title            TEXT NOT NULL,
    details          JSONB NOT NULL DEFAULT '{}',
    -- Structure varies by finding_type — see section 8
    screenshot_path  TEXT NULLABLE,           -- relative to storage_path()
    http_status      SMALLINT NULLABLE,
    detected_at      TIMESTAMP NOT NULL,
    fixed_at         TIMESTAMP NULLABLE,
    fixed_by_commit  VARCHAR(60) NULLABLE,    -- git SHA of fixing commit
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP
);

CREATE INDEX audit_findings_run_id_idx ON audit_findings(run_id);
CREATE INDEX audit_findings_hash_idx   ON audit_findings(finding_hash);
CREATE INDEX audit_findings_severity_idx ON audit_findings(severity);
CREATE INDEX audit_findings_tenant_role_idx ON audit_findings(tenant_slug, role);
```

**Finding hash construction** (idempotency):
```
SHA-256( tenant_slug + "|" + role + "|" + url + "|" + finding_type + "|" + title )
```
The hash excludes `run_id` and timestamps so the same structural bug found in two runs produces the same hash. The Filament UI can use this to show "first seen / last seen" across runs.

---

## 3. Models

### `App\Models\AuditRun` (central DB)
```php
protected $connection = 'central';
protected $table = 'audit_runs';
protected $casts = [
    'meta' => 'array',
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
    'saas_skipped' => 'boolean',
];
public function findings(): HasMany { return $this->hasMany(AuditFinding::class, 'run_id'); }
```

### `App\Models\AuditFinding` (central DB)
```php
protected $connection = 'central';
protected $table = 'audit_findings';
protected $casts = [
    'details' => 'array',
    'detected_at' => 'datetime',
    'fixed_at' => 'datetime',
];
public function run(): BelongsTo { return $this->belongsTo(AuditRun::class, 'run_id'); }
```

---

## 4. Artisan Command Implementation Plan

### `AuditRunCommand` (`audit:run`)

Sequence inside `handle()`:

1. **Validate options** — mutual exclusivity, required combos.
2. **Check Playwright** — `which node` + `node -e "require('playwright')"`. Abort with clear message if missing.
3. **Build job list** — array of `{tenant_slug, role, panel_url, login_url, users[]}` objects.
4. **SaaS password check** — as described in §1.
5. **Create `AuditRun` record** — status=running, started_at=now().
6. **Start total-timeout timer** — register a SIGALRM handler (or use `proc_open` with timeout).
7. **For each (tenant, role) pair** in the job list:
   a. Compute credentials (PHP side; never logged).
   b. Write temp credentials file to `storage/app/audit-tmp/{run_id}-{tenant}-{role}.json` at mode 0600.
   c. Spawn Node process: `node storage/audit-scripts/crawl.js {args}`.
   d. Read stdout line by line (NDJSON); each line is one finding or a progress event.
   e. Persist each finding via `AuditFinding::create()`.
   f. Update `AuditRun` counters.
   g. Delete temp credentials file immediately after browser login (Node script signals this).
8. **After all pairs complete** (or on timeout/error):
   - Serialize all findings to `storage/app/audit-reports/{run_id}.json`.
   - Update `AuditRun.status` and `finished_at`.
   - Print summary table.

### `AuditReportCommand` (`audit:report`)

1. Resolve run(s) from DB.
2. Group findings by severity → role → URL.
3. Print formatted table. Optionally output JSON.

---

## 5. Playwright Node Script Design

**Location:** `storage/audit-scripts/crawl.js`  
Committed to git. Pure Node.js (no transpile step). Uses `require('playwright')`.

**Invocation from PHP:**
```bash
node storage/audit-scripts/crawl.js \
  --creds-file=/path/to/tmp/creds.json \
  --run-id=<uuid> \
  --tenant=<slug> \
  --role=<role> \
  --panel=admin|saas|parent \
  --login-url=https://... \
  --panel-url=https://... \
  --max-urls=<n> \
  --page-timeout=30000 \
  --role-timeout=1200000 \
  --screenshots-dir=/path/to/storage/app/audit-screenshots/<run_id>/ \
  --headless
```

**Credentials file format** (`creds.json`, mode 0600):
```json
{
  "email": "user@example.com",
  "password": "derivedAtRuntime"
}
```
File is deleted by the PHP process after `'login_complete'` event is received on stdout.

**Output protocol — NDJSON to stdout:**
Each line is one of:
```json
{"event": "login_complete", "tenant": "...", "role": "..."}
{"event": "nav_start", "url": "https://..."}
{"event": "nav_complete", "url": "...", "status": 200, "duration_ms": 1234}
{"event": "finding", "finding_type": "5xx", "severity": "critical", "title": "...", "url": "...", "http_status": 500, "details": {...}, "screenshot": "relative/path.png"}
{"event": "progress", "pages_crawled": 5, "findings_so_far": 2}
{"event": "timeout", "at_url": "...", "at_step": "navigation"}
{"event": "done", "pages_crawled": 12, "findings": 3}
{"event": "error", "message": "playwright launch failed"}
```

PHP reads these events and handles them accordingly (deletes creds file on `login_complete`, persists on `finding`, marks timeout on `timeout`).

**Script phases:**

**Phase 1 — Login:**
1. Launch Chromium headless, create browser context (cookie jar).
2. Navigate to login URL.
3. Fill email + password from creds file using `page.fill()`.
4. Submit form, wait for navigation.
5. Verify session is established (check URL is not /login).
6. Emit `login_complete`.

**Phase 2 — Breadth crawl:**
1. Navigate to panel URL (`/admin`, `/saas`, `/parent`).
2. Harvest sidebar nav links from Filament's navigation structure (CSS selector: `.fi-sidebar-nav a[href]`).
3. For each nav link:
   a. Navigate to it.
   b. Run full-page check (§6 below).
   c. Harvest action buttons and one detail-page link per resource.
   d. Visit each harvested link (one per resource type — cap at 1 detail + 1 create form per resource).
   e. Apply `--max-urls` cap across the entire role scope.
4. Record all findings.

**Phase 3 — Form-submit-with-empty-fields:**
For each create-form URL discovered in Phase 2:
1. Navigate to create form.
2. Click the primary submit button without filling any fields.
3. Wait for response.
4. Check: is response a Filament validation render (field-level errors, no toast "Error while loading page") or a server error?
5. Record finding if server error.

**STUDENT role special case (Phase 2 only):**
Instead of harvesting nav links, execute the explicit negative test:
1. Navigate to `/admin`.
2. Assert: HTTP 200.
3. Assert: Page contains AccessDenied chrome (look for Filament panel header + specific AccessDenied text).
4. Assert: Page does NOT contain any SchoolAdmin resource links (verifies restricted access).
5. Emit finding with type `access_denied_ok` (severity `ok`) if all assertions pass.
6. Emit finding with appropriate severity if any assertion fails (see §8).

---

## 6. Per-Page Check Suite

After every navigation, run these checks:

### 6a. HTTP Status Check
- Captured via `response.status()`.
- 5xx → `5xx` finding, critical.
- 4xx (except 401/403 on intentionally restricted pages) → `4xx` finding, high.
- 401/403 on pages the role SHOULD access → `4xx` finding, high.
- 401/403 on pages intentionally restricted → not a finding (expected behavior).

### 6b. Content Presence Check (blank_page detection)
After `page.waitForLoadState('networkidle')`:
1. Check `document.body.innerText.trim()` length.
2. If body has no text AND no Filament empty-state markers → `blank_page` finding, medium.

**Filament empty-state markers (DOM selectors to check):**
```
.fi-ta-empty-state          (Filament table empty state wrapper)
[data-empty-state]           (custom empty state attribute, if used)
```
**Text patterns that also indicate valid empty state:**
```
"No records found"
"No results"
"No {anything} yet"
```
Rule: HTTP 200 + Filament empty-state component present → **not a finding** (correct behavior).

### 6c. Filament Error Toast Detection
After load + 2s wait (toasts appear asynchronously):
```
.fi-notification[data-color="danger"]
.fi-notification:contains("Error while loading page")
```
If found → `filament_error_toast` finding, high.

### 6d. JavaScript Console Error Check
Collect all `console.error` and uncaught exceptions during navigation:
- Uncaught JS error that blocks render (page body empty or spinner frozen) → `js_error` finding, critical.
- `console.error` that does NOT block render → `js_error` finding, low.
- ORB blocks (`net::ERR_BLOCKED_BY_ORB`) → `network_failure` finding, medium.
- CORS errors → `network_failure` finding, medium.
- Other network failures on non-critical assets → `network_failure` finding, medium.

**Ignore list (do not generate findings for):**
- Livewire heartbeat failures (expected in test runs without a real user session).
- Font loading failures (CDN fonts sometimes blocked in headless).
- Favicon / `<link rel="icon">` failures.

### 6e. Broken Image Check
After load, query all `<img>` tags:
```js
const imgs = page.locator('img');
for (const img of await imgs.all()) {
    const src = await img.getAttribute('src');
    const naturalWidth = await img.evaluate(el => el.naturalWidth);
    // naturalWidth = 0 means failed to load
    ...
}
```

**Rules:**
- `src` is present, HTTP returns 4xx/5xx, `naturalWidth = 0` → `broken_image` finding, medium.
  Details: `{ src, http_status }`.
- `src` matches pattern `/storage/https://` or `/storage/http://` (double-path malformed URL) → `malformed_image_url` finding, medium.
  Details: `{ src, pattern: 'absolute_url_prepended_with_storage' }`.
- `src` is empty string or missing attribute → `empty_image_src` finding, low.
- `<link rel="icon">` failures → **ignored** (no finding).
- CSS `background-image` failures → **out of scope** (deferred).

### 6f. Slow Page Detection
If `DOMContentLoaded` event fires > 3000ms after navigation start → `slow_page` finding, info.
Details: `{ duration_ms }`.

### 6g. Form Submission Check (Phase 3 only)
- After empty-fields submit, wait for response.
- If `response.status() >= 500` → `form_500_on_validation` finding, high.
- If Filament error toast appears → `filament_error_toast` finding, high.
- If field-level validation errors appear (`.fi-fo-field-wrp-has-errors`) → correct behavior, no finding.

---

## 7. Authentication Strategy

### Credential Computation (PHP side)

```php
// Inside AuditRunCommand — never printed to output or logs
private function deriveCredential(string $roleKey, string $email): string
{
    $appKey = config('app.key');
    return 'Demo2026@' . substr(sha1($roleKey . $email . $appKey), 0, 6);
}
```

Role → roleKey mapping (from `Pak::roleKeyFor()`):
```
SCHOOL_ADMIN    → 'admin'
INSTITUTE_HEAD  → 'principal'
REGISTRAR       → 'vice-principal'
ACCOUNTANT      → 'accountant'
TEACHER         → 'teacher'
PARENT          → 'parent'
STUDENT         → 'student'
```

### Credential Hand-off to Node

1. PHP writes JSON to `storage/app/audit-tmp/{run_id}-{role}-{pid}.json` at mode **0600**.
2. Absolute path is passed as `--creds-file` arg to the Node script.
3. Node reads file immediately, stores credentials in memory, then emits `login_complete` to stdout.
4. PHP, upon receiving `login_complete`, **deletes the file immediately** (no delay).
5. Credentials exist on disk only during the browser login step (~2-5 seconds).

**Security guarantees:**
- Credentials never appear in process args (file path only).
- File is mode 0600 (readable only by the process owner).
- File is always deleted even on error (PHP `finally` block wraps deletion).
- Credentials never written to `audit_findings.details` or any log channel.

### SaaS Admin Credential
- Source: `env('AUDIT_SAAS_ADMIN_PASSWORD')` — operator sets in `.env.production`.
- Same temp-file hand-off mechanism. Email is hard-coded as `admin@kynexedu.com` (there is only one SaaS admin).

### Login URL Selection

| Tenant | Panel | Login URL | Status |
|---|---|---|---|
| haji-qamar (aqmdigital) | school-admin / parent | `https://aqmdigital.com/login` | Active |
| al-qasim | school-admin / parent | `https://al-qasim-school-HStisi.sms.kynexsolutions.com/login` | **Blocked** — no wildcard DNS/cert (see §1) |
| saas | saas-admin | `https://sms.kynexsolutions.com/saas/login` | Active |

The `SchoolPortalController::login()` resolves tenant from the host header. Logging in via the custom domain (`aqmdigital.com`) sets the correct tenant in session. The crawler must use the correct host per tenant to avoid session-tenant-id mismatch.

### Session Persistence
Each `(tenant, role)` pair gets its own Playwright `BrowserContext` with an isolated cookie jar. No cross-role session leakage. The browser context is destroyed after the role crawl completes.

---

## 8. Severity Assignment Rules

### Standard Rules

| Condition | finding_type | severity |
|---|---|---|
| HTTP 5xx on any crawled URL | `5xx` | **critical** |
| Uncaught JS error blocking page render | `js_error` | **critical** |
| STUDENT role sees admin panel content (security) | `security_violation` | **critical** |
| HTTP 500 on form submit instead of validation | `form_500_on_validation` | **high** |
| "Error while loading page" Filament toast | `filament_error_toast` | **high** |
| HTTP 4xx on page role SHOULD access | `4xx` | **high** |
| AccessDenied page missing Filament chrome (Phase 1.5 regression) | `panel_chrome_missing` | **high** |
| Broken image (src 4xx/5xx, naturalWidth=0) | `broken_image` | **medium** |
| Malformed image URL (/storage/https://...) | `malformed_image_url` | **medium** |
| Network failure on non-critical asset (CORS, ORB) | `network_failure` | **medium** |
| Blank page (no content AND no empty-state component) | `blank_page` | **medium** |
| JS console.error (non-blocking) | `js_error` | **low** |
| Empty img src or src attribute missing | `empty_image_src` | **low** |
| Page DOMContentLoaded > 3000ms | `slow_page` | **info** |

### Not-a-Finding Rules (explicit suppression)

| Condition | Reason |
|---|---|
| HTTP 200 + Filament empty-state component visible | Correct behavior on sparse tenants |
| HTTP 401/403 on page role should NOT access | Correct access control |
| STUDENT role → AccessDenied page WITH full chrome AND expected text | Expected negative test pass |
| Favicon / `<link rel="icon">` network failures | Not user-visible |
| Livewire heartbeat failures in headless context | Artifact of test environment |
| Font CDN failures in headless context | Artifact of test environment |

### STUDENT Role Explicit Test Outcomes

| Outcome | finding_type | severity |
|---|---|---|
| 200 + AccessDenied page + full Filament chrome + expected text | `access_denied_ok` | **ok** (positive assertion) |
| 200 + AccessDenied page WITHOUT Filament chrome (sidebar/header stripped) | `panel_chrome_missing` | **high** |
| 200 + renders SchoolAdmin resource UI (student sees admin) | `security_violation` | **critical** |
| HTTP 500 | `5xx` | **critical** |
| Any other 4xx | `4xx` | **medium** |

The `access_denied_ok` severity `ok` is only used for this explicit positive assertion. It is stored in `audit_findings` so the run has a record that the test was executed, but it never counts toward any severity bucket or surfaces as a problem in reports. The Filament UI shows it separately as "Verified OK."

**AccessDenied chrome verification:** Check for:
- Filament sidebar present: `.fi-sidebar` in DOM.
- Filament topbar present: `.fi-topbar` in DOM.
- AccessDenied content: text matching `"Access Restricted"` or `"You do not have permission"` (whichever the AccessDenied page renders — check `app/Filament/SchoolAdmin/Pages/AccessDenied.php` at build time and hard-code the expected string).

---

## 9. Timeout Handling

Defaults (all configurable via command flags):

| Scope | Default | Flag |
|---|---|---|
| Per-page navigation timeout | 30s | `--page-timeout` |
| Per-page total (incl. interactions) | 60s | (double the page-timeout; not separately configurable) |
| Per-role total | 20 min | `--role-timeout` |
| Total run | 90 min | `--total-timeout` |

**On timeout at any level:**
1. Node script emits `{"event": "timeout", "at_url": "...", "at_step": "..."}` to stdout.
2. PHP receives the event.
3. PHP flushes all accumulated findings to DB (any in-memory buffer is written).
4. PHP writes partial JSON report to `storage/app/audit-reports/{run_id}.json`.
5. PHP sets `audit_runs.status = 'timeout'`, `last_url_crawled`, `last_role_crawled`.
6. PHP exits with code **2** (non-zero; future cron alerting will use this).
7. Subsequent roles (if any) are skipped; run is marked incomplete.

The Node script enforces per-page timeout via Playwright's `page.goto(url, { timeout: N })`. Per-role timeout is enforced by PHP using `proc_open` with a `select()`-based timeout loop.

---

## 10. Output Structure

### Per Run

| Artifact | Location | Format |
|---|---|---|
| Machine-readable findings | `storage/app/audit-reports/{run_id}.json` | JSON array of findings |
| Screenshots | `storage/app/audit-screenshots/{run_id}/` | PNG, named `{role}-{url-slug}-{n}.png` |
| DB findings | `audit_findings` table, central DB | Queryable via Filament UI |
| DB run record | `audit_runs` table, central DB | Status, counters, timestamps |

Both `audit-reports/` and `audit-screenshots/` are **gitignored** (add to `.gitignore`).

### JSON Report Schema (`{run_id}.json`)

```json
{
  "run_id": "uuid",
  "scope": "all",
  "status": "complete",
  "started_at": "ISO8601",
  "finished_at": "ISO8601",
  "saas_skipped": false,
  "summary": {
    "total_pages": 320,
    "critical": 4, "high": 12, "medium": 8, "low": 3, "info": 22, "ok": 2
  },
  "findings": [
    {
      "id": "uuid",
      "finding_hash": "sha256hex",
      "tenant_slug": "haji-qamar-public-school-BEb3S9",
      "role": "SCHOOL_ADMIN",
      "url": "https://aqmdigital.com/admin/students",
      "finding_type": "5xx",
      "severity": "critical",
      "title": "HTTP 500 on Students index",
      "http_status": 500,
      "details": {
        "response_excerpt": "...",
        "console_errors": [],
        "stack_trace": null
      },
      "screenshot_path": "audit-screenshots/{run_id}/SCHOOL_ADMIN-students-1.png",
      "detected_at": "ISO8601"
    }
  ]
}
```

### `audit:report` Text Output (example)

```
═══════════════════════════════════════════════════════
KynexEdu Audit Report — Run abc123 (complete)
2026-05-08 14:30 → 2026-05-08 15:02 (32 min)
SaaS panel: crawled
Tenants: aqmdigital.com
Pages crawled: 318
═══════════════════════════════════════════════════════

SEVERITY SUMMARY
  critical  4
  high     12
  medium    8
  low       3
  info     22
  ok        2

TOP CRITICAL FINDINGS
  1. [SCHOOL_ADMIN / aqmdigital.com] HTTP 500 on /admin/students/create
     finding_hash: abc123...
     screenshot: storage/app/audit-screenshots/...
  ...
```

---

## 11. Filament Resource: Audit Findings

**Location:** `app/Filament/SaasAdmin/Resources/AuditFindingResource.php`

This is an **operator-facing** resource on the SaaS admin panel (`/saas`), not the school admin panel. School admins should not see other tenants' audit findings.

**Table columns:**
`severity` (badge colored by severity), `finding_type`, `tenant_slug`, `role`, `url` (truncated), `title`, `http_status`, `detected_at`, `fixed_at` (nullable)

**Filters:**
Severity, finding_type, tenant_slug, role, date range, fixed/unfixed toggle

**Actions (per row):**
- View details (modal with full JSON details + screenshot preview)
- Mark as fixed (sets `fixed_at = now()`, prompts for `fixed_by_commit`)
- Open URL (external link)

**Supporting resource:** `AuditRunResource` at `/saas/audit-runs` — table of all runs with status, duration, finding counts, link to findings filtered by run.

**Browsable URLs:**
- `https://sms.kynexsolutions.com/saas/audit-findings`
- `https://sms.kynexsolutions.com/saas/audit-runs`

---

## 12. Idempotency

The `finding_hash` (SHA-256 of `tenant_slug|role|url|finding_type|title`) is stable across runs. Each `AuditFinding` row is unique per `(run_id, finding_hash)`. Running `audit:run` twice on the same data:
- Produces two `audit_runs` rows (each run is a distinct event).
- Produces two sets of `audit_findings` rows, each linked to their respective run.
- The `finding_hash` allows the Filament UI to show "first seen in run X, last seen in run Y" and detect if a bug was fixed between runs.

There is NO upsert/merge of findings across runs — each run is an independent snapshot. This is intentional: it allows tracking regressions (a bug re-appearing after it was marked fixed).

---

## 13. Playwright Integration Justification

**Chosen approach: Standalone Node script spawned by PHP**

Rationale:
- The MCP Playwright integration is for Claude Code's interactive use during development. It shares the Claude Code session and is not designed for unattended, scripted crawling.
- A standalone `crawl.js` spawned via `proc_open` is self-contained, runnable from cron/CI, and has no dependency on Claude Code being active.
- NDJSON over stdout is a simple, reliable IPC mechanism between PHP and Node.
- The script is committed to `storage/audit-scripts/` (not `node_modules/`, not `resources/js/` — it's an operational tool, not a frontend asset).
- No `package.json` changes needed; the system-wide Playwright installation is used.

**Prerequisite check:** `audit:run` verifies `node` and `playwright` are available before creating any DB records. If not found, it aborts with instructions: `Run: npx playwright install chromium`

---

## 14. Test Plan

### Unit Tests

**`AuditSeverityClassifierTest`** (`tests/Unit/Audit/AuditSeverityClassifierTest.php`):
- `test_5xx_is_critical()`
- `test_4xx_is_high()`
- `test_empty_state_is_not_a_finding()` — HTTP 200 + empty-state DOM marker → returns null
- `test_blank_page_is_medium()` — HTTP 200 + no content + no empty-state → medium
- `test_broken_image_is_medium()`
- `test_malformed_image_url_is_medium()`
- `test_empty_image_src_is_low()`
- `test_favicon_failure_is_suppressed()`
- `test_slow_page_is_info()`
- `test_student_access_denied_with_chrome_is_ok()`
- `test_student_sees_admin_panel_is_critical()`
- `test_student_access_denied_without_chrome_is_high()`
- `test_finding_hash_is_stable_across_runs()` — same inputs → same hash

### Integration Test

**`AuditDeliberateBrokenRouteTest`** (`tests/Feature/Audit/AuditDeliberateBrokenRouteTest.php`):

1. Register a test-only route that returns HTTP 500 (`/audit-test/deliberate-500`).
2. Run `audit:run --tenant=haji-qamar-public-school-BEb3S9 --role=school_admin --url=/audit-test/deliberate-500`.
3. Assert: one `audit_finding` row exists with `finding_type='5xx'`, `severity='critical'`, `http_status=500`.
4. Assert: one `audit_run` row exists with `status='complete'`, `findings_critical=1`.
5. Assert: JSON report file exists and contains the finding.

This is an end-to-end smoke test of the entire pipeline (PHP → Node → Playwright → finding → DB → JSON) without needing a real broken page.

**Environment gate (Refinement 2):** The test route is registered in `routes/web.php` only in non-production environments:

```php
if (app()->environment(['local', 'testing'])) {
    Route::get('/audit-test/deliberate-500', fn () => abort(500));
}
```

PHPUnit sets `APP_ENV=testing`, so the route is active during CI. Production deploys (`APP_ENV=production`) never load this route. The integration test class must assert `APP_ENV` is not `production` at the start and skip with a clear message if somehow invoked in prod.

---

## 15. Open Questions (Resolved)

| Question | Resolution |
|---|---|
| Should Node script live in `storage/` or `resources/`? | `storage/audit-scripts/` — it's operational tooling, not a frontend asset. Committed to git. |
| Should findings be merged across runs or kept separate? | Separate per run. `finding_hash` enables cross-run correlation in UI. |
| Should cron be set up now? | No — Phase 2A infrastructure only. Cron added in a later phase after full run validates findings. |
| Should screenshots be served via Filament file upload or direct storage URL? | Direct storage URL via `Storage::url()` — no upload record needed, just a path in the DB. |
| Should `audit:report --latest` show OK findings? | No — only `critical/high/medium/low/info`. OK findings visible in Filament UI only. |
| Does al-qasim subdomain routing work? | **Resolved (2026-05-08):** No. DNS wildcard missing; cert is single-hostname only. Decision: exclude al-qasim from `--all` scope. Audit haji-qamar only for Phase 2B. Follow-up docket in §16. |
| Should `audit:prune` be built in Phase 2A? | No — spec only (§1). Build after first full run establishes real file volume. |

---

## 16. Follow-up Docket

Items surfaced during Phase 2A investigation that are **out of scope for this build** but must not be forgotten.

### 16a. al-qasim Audit Access

**Status:** Blocked — no wildcard DNS record; TLS cert is single-hostname (`sms.kynexsolutions.com` only).

**What is needed:**
1. Cloudflare: add `*.sms.kynexsolutions.com → 178.104.180.160` A record (wildcard).
2. Cert reissue: expand the LE cert to include `*.sms.kynexsolutions.com` SAN (or provision a separate wildcard cert). This is a cert workstream task touching the Phase 1.5 infrastructure.

**Effort:** ~2 hours when prioritised.

**Trigger to act:** al-qasim becomes a real customer with real data, OR any other tenant needs subdomain-based access (custom domain is the preferred path — this is only needed as a fallback).

**Until then:** `--tenant=al-qasim-school-HStisi` hard-aborts with remediation instructions (§1). `--all` silently excludes it.

---

### 16b. Stancl Tenancy Default Redirect on Unknown Subdomain

**Symptom:** `curl -H "Host: nonexistent-tenant.sms.kynexsolutions.com" https://localhost/` returns a 302 redirect to `https://nonexistent-tenant.sms.kynexsolutions.com/platform/login` — the Stancl Tenancy library's own default redirect — rather than KynexEdu's `errors.domain-not-configured` 404.

**Root cause:** `InitializeTenancyBySubdomainOrDomain` correctly calls `abort(404)` when no tenant matches the slug. But Stancl's internal exception handler catches the 404 and redirects to its configured `not_found_redirect` URL (`/platform/login`) before Laravel's exception handler renders the 404 view.

**Impact:** Currently low (subdomain DNS doesn't resolve so external traffic never hits this path). Becomes visible to internet scanners once wildcard DNS is added.

**Fix:** Override Stancl's `not_found_redirect` config to null, OR catch the `TenantCouldNotBeIdentifiedById` exception in `InitializeTenancyBySubdomainOrDomain` and call `abort(404)` directly before Stancl's handler fires.

**Effort:** ~30 minutes.

**Trigger to act:** Immediately before or alongside wildcard DNS enablement (16a).
