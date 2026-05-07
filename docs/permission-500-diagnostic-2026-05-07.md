# Permission / 500 Diagnostic — 2026-05-07

## 1. Are the two "fixes" actually live?

| Fix | Git commit | In current image (`kynexedu-erp:prod`, built 2026-05-07 21:28 UTC) | In running container |
|---|---|---|---|
| `fee.receipt` route registration | **Not committed** (commit was interrupted) | **No** | **Yes** — `docker cp`'d to container during this session; `artisan route:clear` run |
| Exam / GradingWeights in-layout forbidden page | **Not committed** | **No** | **Yes** — `docker cp`'d same session |

**Risk:** a container restart or image rebuild will **wipe both fixes**. They must be committed and the image rebuilt to be durable.

---

## 2. What is actually producing the 500s?

The logs (`laravel.log`, 6,300+ lines through today) contain **no AuthorizationException or Filament policy-denial 500s**. The real 500s come from five distinct causes, ranked by frequency in the log:

### Bug 1 — ExamType enum vs. DB mismatch (MOST FREQUENT, still live)

```
"first_term" is not a valid backing value for enum App\Enums\ExamType
  at vendor/laravel/framework/…/HasAttributes.php:1317
```

- **First seen:** 2026-05-05 17:37:39  
- **Last seen:** 2026-05-07 21:27:53 (still happening today, after image rebuild)  
- **Occurrences in log:** 8+ separate incidents  
- **Root cause:** Tenant DB `tenanthaji-qamar-public-school-BEb3S9` has `exam_type = 'first_term'` in the `exams` table. `App\Enums\ExamType` only defines: `quarterly`, `mid_term`, `semi_final`, `final`, `annual`, `custom`. Laravel's Eloquent model tries to cast the column to the enum and hard-crashes.  
- **Pages hit:** `/admin/exams` (ListExams table) AND `/admin/grading-weights` blade  
- **This is the actual cause of the Exam / GradingWeights 500s.** The permission/authorization layer is unrelated — the crash happens even for users who have `view_marks`.

### Bug 2 — PaymentMethod enum vs. DB mismatch

```
"easypaisa" is not a valid backing value for enum App\Enums\PaymentMethod
```

- **Last seen:** 2026-05-05 18:06:26  
- **Root cause:** `fee_payments` table has `easypaisa`, `jazzcash`, `bank_transfer`; `PaymentMethod` enum only defines `cash`, `bank`, `cheque`, `online`. Fee Collection table crashes when loading rows with those methods.  
- **Page hit:** `/admin/fee-collection-page`

### Bug 3 — AttendanceStatus enum vs. DB mismatch

```
"leave" is not a valid backing value for enum App\Enums\AttendanceStatus
```

- **Last seen:** 2026-05-05 17:55:00  
- **Root cause:** `attendance_records` has `leave`; enum only knows `present`, `absent`, `late`, `half_day`, `holiday`, `excused`.

### Bug 4 — Route `fee.receipt` not defined

```
Route [fee.receipt] not defined.
  at vendor/filament/tables/…/index.blade.php
```

- **Last seen:** 2026-05-07 20:57 (before today's session fix; not seen after)  
- **Status:** **Fixed in container**, not yet committed/imaged.

### Bug 5 — `CmsPublicController::contactForm()` missing

```
Call to undefined method App\Http\Controllers\Cms\PublicController::contactForm()
```

- **Last seen:** 2026-05-05 20:57:33  
- **Route exists** (`public.site.contact.form` in `web.php:67`); controller method does not.

---

## 3. Exception handling configuration

**`bootstrap/app.php`:**
```php
->withExceptions(function (Exceptions $exceptions): void {
    // (empty)
})
```

**No `app/Exceptions/Handler.php`** (project is on Laravel 11 style).  
**No custom exception mapping** of any kind — every uncaught exception falls through to Laravel's default renderer.

**Custom error views that exist:** `403.blade.php`, `404.blade.php`, `500.blade.php`, `domain-not-configured.blade.php`, `expired.blade.php`, `suspended.blade.php`.

**Consequence:** when Filament calls `abort(403)` (which it does when `canAccess()` / `canViewAny()` returns false), the custom `resources/views/errors/403.blade.php` **is** rendered — this is a proper 403, not a 500. The "stripped" look the user objects to is the error page itself, not a crash. When enum mismatches or missing routes throw unhandled exceptions, they become 500s rendered by `500.blade.php` — also stripped.

---

## 4. Authorization patterns in use

### Pattern A — `HasPermissionCheck` trait (30+ Resources / Pages)

```php
trait HasPermissionCheck {
    public static function shouldRegisterNavigation(): bool { … }
    public static function canAccess(): bool { … }         // Pages
    public static function canViewAny(): bool { … }        // Resources
    public static function canCreate(): bool { … }
    // …
}
```

Used by: almost every SchoolAdmin resource and page.  
**Denial path:** `canAccess()` / `canViewAny()` returns `false` → Filament calls `abort(403)` → renders `403.blade.php` (no dashboard sidebar). **Not a 500.** Just not pretty.

### Pattern B — Inline `hasRole()` / `hasPermissionTo()` checks (5 pages)

```php
// ApprovalQueue, FeeReportsPage, NotificationComposer, etc.
$user->hasRole(['SCHOOL_ADMIN', 'INSTITUTE_HEAD', …])
```

Used inside visibility closures and actions, not as gate methods. No denial path issues — they just control widget/action visibility.

### Pattern C — `abort(403)` in controller / page action (2 places)

```php
// InventoryTransactionResource/CreateInventoryTransaction.php
// ExpenseResource/CreateExpense.php
abort(403, '…');
```

Results in clean 403 responses. Not a 500.

### Pattern D — Custom `canViewAny()` without trait (3 resources)

```
RoleManagementResource, SchoolUserResource, HomeworkAssignmentResource
```

Each implements their own logic. No Spatie exception bubbling observed in logs from these.

### What's inconsistent

| Resource/Page | Nav hidden for unauthorized? | URL-block for unauthorized? | Denial output |
|---|---|---|---|
| 30+ `HasPermissionCheck` pages | ✅ Yes | ✅ Yes (`abort 403`) | `403.blade.php` (stripped) |
| `ExamResource` (before today's fix) | ✅ Yes | ✅ Yes (`abort 403`) | `403.blade.php` (stripped) |
| `GradingWeights` (before today's fix) | ✅ Yes | ✅ Yes (`abort 403`) | `403.blade.php` (stripped) |
| `ExamResource` (today's fix, container only) | ✅ Nav shown to all | ✅ Renders in-layout forbidden | Full panel chrome ✅ |
| `GradingWeights` (today's fix, container only) | ✅ Nav shown to all | ✅ Renders in-layout forbidden | Full panel chrome ✅ |

**The global pattern is consistent** — no resource is accidentally granting access. The 500s are all enum/route bugs, not authorization bugs.

---

## 5. Proposed fix strategy

Three independent workstreams, in priority order:

### Priority 1 — Fix enum mismatches (active production crashes)

**Option A — Extend the enums** (add missing cases):
- `ExamType`: add `case FirstTerm = 'first_term'`
- `PaymentMethod`: add `case EasyPaisa = 'easypaisa'`, `case JazzCash = 'jazzcash'`, `case BankTransfer = 'bank_transfer'`
- `AttendanceStatus`: add `case Leave = 'leave'`

Pros: canonical, works everywhere enums are used.  
Cons: exposes legacy values to new code; `first_term` was probably renamed to `quarterly` intentionally.

**Option B — Use `tryFrom()` fallback in model casts** (safer, non-breaking):
Switch the Eloquent `$casts` from `'exam_type' => ExamType::class` to a custom cast that calls `ExamType::tryFrom($value) ?? ExamType::Custom` (or null). Same for the other two.

Pros: stale DB data never crashes; clean null/fallback.  
Cons: hides the stale data problem; user sees "—" or "Custom" for old rows.

**Option C — Migrate the data** (cleanest long-term):
`UPDATE exams SET exam_type = 'quarterly' WHERE exam_type = 'first_term'` in the tenant DB; similarly for the other tables.

Pros: fixes root cause in data, no code change needed.  
Cons: destructive; requires knowing the correct mapping.

**Recommendation:** Option B (tryFrom fallback) immediately to stop crashes; Option C (data migration) as follow-up once the correct mapping is confirmed with the school admin.

### Priority 2 — Commit + rebuild image for today's in-flight fixes

The `fee.receipt` route and the Exam/GradingWeights forbidden-page changes are live only in the running container. They will be lost on any container restart. Commit and rebuild now.  
The partial `routes/web.php` conflict (admission-test lines referencing untracked controller) needs to be resolved first — either commit the full admission-test workstream together, or stage just the `fee.receipt` hunk.

### Priority 3 — Fix `CmsPublicController::contactForm()` missing method

Route `public.site.contact.form` points to a method that doesn't exist. Add the method or remove the route.

---

## What's NOT a problem

- **Authorization causing 500s:** not observed. Filament's deny path is a clean `abort(403)`.  
- **Global "permission 500" pattern:** no such pattern exists. Each page's 500 has a specific enum/route cause.  
- **`HasPermissionCheck` correctness:** the trait works. The deny path just lacks the dashboard chrome, which today's in-container fix addresses for Exams and GradingWeights.  
- **Spatie PermissionDoesNotExist bubbling:** `HasPermissionCheck::currentUserCanAccess()` already catches this exception and returns `false` — no 500 risk.
