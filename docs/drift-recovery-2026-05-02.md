# Drift recovery report — 2026-05-02

The running `kynexedu-app` container's `/var/www/html/` source tree has diverged from the host source at `/var/www/kynexedu/` (and from `git HEAD` = `a3dfcc3`, which is clean). A naive `docker compose build` from the current host source would regress production. This report enumerates everything that needs to be copied container → host → git before any image rebuild.

## Totals

| Bucket | Count |
|---|---|
| New files (only in container, missing from host & git) | 42 |
| ├─ Application code & views | 37 |
| └─ Tenant DB migrations | 5 |
| Modified files (present in both, container is newer) | 53 |
| Files only on host (in git but missing from container) | 0 |
| **Grand total** | **95 files** |

(The opening "89" estimate from the investigation phase was a mid-pass undercount — proper directory expansion gives 95.)

The container's newest source mtimes are dated 2026-05-02 16:13 UTC (today). Most of the drift accumulated across multiple sessions before the Plan A-E commits; only the 6 files Plan A-E committed match cleanly between host and container.

## How to read this report

- **Purpose** for new files is taken verbatim from the file's class docblock or first blade comment, then truncated. Where the file has no docblock, the column is blank — usually a Filament resource/page where the filename already states the entity.
- **+lines / −lines** for modified files counts diff-output `+`/`−` lines (excluding the file header). Sort is descending by `+lines` so the heaviest rewrites are at the top.

---

## New files — application code & views (37)

### Filament SchoolAdmin pages — Fee management module (7 pages + 1 resource + 5 widgets)

| Path | Purpose |
|---|---|
| `app/Filament/SchoolAdmin/Pages/FeeCatalog.php` | Fee Catalog — a single, user-friendly page that merges Fee Group and (Fee Master) |
| `app/Filament/SchoolAdmin/Pages/FeeDefaulters.php` | Lists fee invoices that are past their due_date and not fully paid |
| `app/Filament/SchoolAdmin/Pages/FinancialReport.php` | Total Financial Report — enterprise-grade P&L for schools |
| `app/Filament/SchoolAdmin/Pages/GenerateFees.php` | Admin-driven fee invoicing dashboard |
| `app/Filament/SchoolAdmin/Pages/HelpCenter.php` | In-app help center. Explains every navigation section in plain words |
| `app/Filament/SchoolAdmin/Pages/SchoolWebsiteDomain.php` | Admin page for the school to claim its own domain for the PUBLIC site |
| `app/Filament/SchoolAdmin/Pages/StudentBulkImport.php` | (Filament page — paired with `StudentBulkImporter` service below) |
| `app/Filament/SchoolAdmin/Resources/FeeMasterResource.php` | (Filament resource for the new `FeeMaster` model — price book) |
| `app/Filament/SchoolAdmin/Resources/FeeMasterResource/Pages/CreateFeeMaster.php` | (Filament CRUD page) |
| `app/Filament/SchoolAdmin/Resources/FeeMasterResource/Pages/EditFeeMaster.php` | (Filament CRUD page) |
| `app/Filament/SchoolAdmin/Resources/FeeMasterResource/Pages/ListFeeMasters.php` | (Filament CRUD page) |
| `app/Filament/SchoolAdmin/Widgets/FeeReports/ClassWiseOutstandingWidget.php` | Horizontal bar chart of outstanding amount grouped by class |
| `app/Filament/SchoolAdmin/Widgets/FeeReports/CollectionTrendWidget.php` | Last 12 months of fee collection (line chart) with a 3-month trend |
| `app/Filament/SchoolAdmin/Widgets/FeeReports/DefaulterAgingWidget.php` | Aging analysis: count of overdue invoices bucketed by age in days |
| `app/Filament/SchoolAdmin/Widgets/FeeReports/FeeKpiWidget.php` | Top-row KPIs for the Fee Reports page: billed, collected, collection rate |
| `app/Filament/SchoolAdmin/Widgets/FeeReports/StatusDistributionWidget.php` | Pie chart of student-fee status distribution for the current period |

### Public-facing controllers (4)

| Path | Purpose |
|---|---|
| `app/Http/Controllers/CertificateVerificationController.php` | Public-facing certificate verification |
| `app/Http/Controllers/FeeReceiptController.php` | Renders a printable HTML receipt for a fee payment. Tenancy must be initialised |
| `app/Http/Controllers/FinancialReportPrintController.php` | Renders an international-standard, print-friendly financial report |
| `app/Http/Controllers/StudentVerificationController.php` | Public student card verification |

### Middleware (1)

| Path | Purpose |
|---|---|
| `app/Http/Middleware/BlockCentralPortalOnTenantHost.php` | Blocks `/login`, `/register`, `/forgot-password`, `/saas` etc. on tenant hosts. Path-aware: keeps `/site/{tenant}`, `/apply`, `/verify/*`, `/payslip`, `/result-card`, `/fee-receipt` working. **Wired into `SaasAdminPanelProvider` already** — companion to Plan D. |

### Services & support (3)

| Path | Purpose |
|---|---|
| `app/Services/Ai/RoleScopedFacts.php` | Builds a small JSON facts snapshot for the AI assistant, scoped to the current role |
| `app/Services/StudentBulkImporter.php` | Imports students from a CSV file. Resolves foreign keys by name |
| `app/Support/CampusField.php` | Centralised builder for the "Campus" form field |

### Config (1) — needs your decision

| Path | Purpose |
|---|---|
| `config/livewire.php` | Published Livewire vendor config. ⚠️ **Decide whether to commit or `.gitignore`** — published configs are usually committed only if customised. |

### Blade views — Filament page templates (7) + reports (1) + public (3) + fees (1)

All of these are paired with the Filament Pages / Controllers above.

| Path | Pairs with |
|---|---|
| `resources/views/filament/school-admin/pages/fee-catalog.blade.php` | `FeeCatalog.php` |
| `resources/views/filament/school-admin/pages/fee-defaulters.blade.php` | `FeeDefaulters.php` |
| `resources/views/filament/school-admin/pages/financial-report.blade.php` | `FinancialReport.php` |
| `resources/views/filament/school-admin/pages/generate-fees.blade.php` | `GenerateFees.php` |
| `resources/views/filament/school-admin/pages/help-center.blade.php` | `HelpCenter.php` |
| `resources/views/filament/school-admin/pages/school-website-domain.blade.php` | `SchoolWebsiteDomain.php` |
| `resources/views/filament/school-admin/pages/student-bulk-import.blade.php` | `StudentBulkImport.php` |
| `resources/views/reports/financial-print.blade.php` | `FinancialReportPrintController.php` |
| `resources/views/public/apply-closed.blade.php` | (admissions-closed state for `PublicAdmissionController`) |
| `resources/views/public/certificate-verify.blade.php` | `CertificateVerificationController.php` |
| `resources/views/public/student-verify.blade.php` | `StudentVerificationController.php` |
| `resources/views/fees/receipt.blade.php` | `FeeReceiptController.php` |

---

## New files — tenant DB migrations (5)

To be verified separately in step 2 (`migrate:status` against haji-qamar tenant DB).

| Migration filename |
|---|
| `database/migrations/tenant/2026_05_01_020_add_is_active_to_fee_masters.php` |
| `database/migrations/tenant/2026_05_01_021_add_month_and_remarks_to_student_fees.php` |
| `database/migrations/tenant/2026_05_01_030_add_section_id_to_fee_masters.php` |
| `database/migrations/tenant/2026_05_01_040_extend_cms_settings.php` |
| `database/migrations/tenant/2026_05_02_001_make_student_gender_nullable.php` |

Docblocks (where present):
- `_021_…_student_fees.php` → "The original `student_fees` table omitted the `month` column even though …"
- `_030_…_fee_masters.php` → "Adds section-level overrides on the `fee_masters` price book"
- `_040_extend_cms_settings.php` → "Adds the missing fields a real school website needs: vision, …"
- `_001_make_student_gender_nullable.php` → "`gender` on students was originally NOT NULL, but bulk imports and …"
- `_020_add_is_active_to_fee_masters.php` → no docblock; filename describes intent

---

## Modified files (53) — sorted by line-change magnitude

`+L` / `−L` are added/removed lines (container vs host). `host total` is the line count of the host version, for context.

### Major rewrites (≥ 50 lines added)

| +L | −L | host total | Path |
|---:|---:|---:|---|
| 406 | 78 | 151 | `app/Filament/SchoolAdmin/Pages/FeeCollectionPage.php` |
| 338 | 171 | 365 | `app/Database/Seeders/DefaultCertificateAndIdCardTemplatesSeeder.php` |
| 302 | 98 | 250 | `app/Services/CertificateService.php` |
| 247 | 1 | 321 | `app/Filament/SchoolAdmin/Pages/CmsSettings.php` |
| 233 | 227 | 277 | `resources/views/cms/home.blade.php` |
| 163 | 28 | 164 | `app/Filament/SchoolAdmin/Resources/StudentApplicationResource.php` |
| 135 | 5 | 182 | `app/Filament/SchoolAdmin/Pages/ApprovalQueue.php` |
| 100 | 160 | 189 | `resources/views/cms/contact.blade.php` |
| 85 | 105 | 138 | `resources/views/cms/admissions.blade.php` |
| 79 | 86 | 109 | `resources/views/cms/about.blade.php` |
| 76 | 41 | 257 | `resources/views/filament/school-admin/resources/class-routine-resource/pages/timetable-index.blade.php` |
| 70 | 9 | 194 | `app/Filament/SchoolAdmin/Resources/ExpenseResource.php` |
| 68 | 25 | 418 | `app/Services/FeesService.php` |
| 64 | 12 | 537 | `app/Filament/SchoolAdmin/Resources/StudentResource.php` |
| 58 | 13 | 138 | `app/Filament/SchoolAdmin/Pages/IssueCertificate.php` |
| 50 | 18 | 100 | `app/Http/Middleware/InitializeTenancyBySubdomain.php` |

### Moderate (10–49 lines added)

| +L | −L | host total | Path |
|---:|---:|---:|---|
| 47 | 36 | 93 | `app/Filament/SchoolAdmin/Resources/ExpenseResource/Pages/CreateExpense.php` |
| 41 | 8 | 159 | `app/Filament/SchoolAdmin/Pages/FeeReportsPage.php` |
| 38 | 1 | 201 | `app/Http/Controllers/PublicAdmissionController.php` |
| 37 | 19 | 187 | `resources/views/cms/layout.blade.php` |
| 33 | 8 | 126 | `app/Filament/SchoolAdmin/Pages/GenerateIdCards.php` |
| 33 | 0 | 64 | `app/Providers/AppServiceProvider.php` |
| 28 | 45 | 55 | `resources/views/filament/school-admin/pages/cms-settings.blade.php` |
| 22 | 5 | 9 | `resources/views/filament/school-admin/pages/fee-collection.blade.php` |
| 22 | 3 | 183 | `app/Filament/SchoolAdmin/Resources/StudentResource/Pages/StudentProfile.php` |
| 18 | 13 | 50 | `app/Actions/Approvals/HandleExpenseApproval.php` |
| 18 | 0 | 652 | `app/Filament/SchoolAdmin/Pages/MarksEntry.php` |
| 18 | 0 | 349 | `app/Filament/SchoolAdmin/Resources/HomeworkAssignmentResource.php` |
| 13 | 1 | 44 | `app/Models/Tenant/CmsSetting.php` |
| 12 | 0 | 99 | `app/Models/Tenant/StudentFee.php` |

### Minor (≤ 9 lines added) — likely small touches, refactor-driven

| +L | −L | host total | Path |
|---:|---:|---:|---|
| 9 | 2 | 64 | `app/Filament/SchoolAdmin/Widgets/InstituteOwner/InstituteOwnerStatsWidget.php` |
| 8 | 1 | 76 | `app/Filament/SchoolAdmin/Resources/FeeGroupResource.php` |
| 8 | 0 | 175 | `app/Livewire/AiAssistantPanel.php` |
| 7 | 1 | 196 | `app/Filament/SchoolAdmin/Resources/ClassRoutineResource/Pages/ListClassRoutines.php` |
| 7 | 0 | 53 | `app/Models/Tenant/FeeMaster.php` |
| 7 | 0 | 11 | `app/Filament/SchoolAdmin/Resources/CmsSliderResource/Pages/ListCmsSliders.php` |
| 7 | 0 | 11 | `app/Filament/SchoolAdmin/Resources/CmsPageResource/Pages/ListCmsPages.php` |
| 7 | 0 | 11 | `app/Filament/SchoolAdmin/Resources/CmsGalleryAlbumResource/Pages/ListCmsGalleryAlbums.php` |
| 7 | 0 | 11 | `app/Filament/SchoolAdmin/Resources/CmsAnnouncementResource/Pages/ListCmsAnnouncements.php` |
| 6 | 1 | 98 | `app/Filament/SchoolAdmin/Resources/FeeTypeResource.php` |
| 4 | 1 | 144 | `resources/views/public/apply.blade.php` |
| 3 | 1 | 231 | `app/Filament/SchoolAdmin/Resources/SectionResource.php` |
| 2 | 2 | 103 | `app/Http/Controllers/PublicSiteController.php` |
| 1 | 4 | 230 | `app/Filament/SchoolAdmin/Resources/HealthRecordResource.php` |
| 1 | 4 | 181 | `app/Filament/SchoolAdmin/Resources/CafeteriaMenuItemResource.php` |
| 1 | 1 | 49 | `resources/views/cms/page.blade.php` |
| 1 | 1 | 173 | `resources/views/cms/results.blade.php` |
| 1 | 1 | 149 | `app/Filament/SchoolAdmin/Resources/BudgetResource.php` |
| 1 | 1 | 120 | `app/Filament/SchoolAdmin/Resources/ExpenseCategoryResource.php` |
| 1 | 1 | 109 | `resources/views/cms/gallery.blade.php` |
| 1 | 1 | 104 | `resources/views/cms/news.blade.php` |
| 1 | 0 | 80 | `app/Providers/Filament/SaasAdminPanelProvider.php` |
| 1 | 0 | 115 | `app/Models/ApprovalRequest.php` |

---

## Things to look at first

A handful of items I'd specifically flag for human eyes during your review pass:

1. **`config/livewire.php`** — published vendor config. Common practice is to NOT commit it unless you've customised it. Want me to check whether any setting differs from `vendor/livewire/livewire/config/livewire.php` before deciding?
2. **The 5 tenant migrations** — being checked in step 2. If they're already applied to live tenant DBs, we just commit the files. If not, the `_021_…_student_fees.php` and `_001_make_student_gender_nullable.php` ones in particular need idempotency review since they alter columns on tables that already hold production data.
3. **`app/Http/Middleware/InitializeTenancyBySubdomain.php` (+50 / −18)** — modified middleware in the tenancy hot path. This is the kind of change that needs careful review since regressing it can break tenant-resolution for everyone.
4. **`app/Filament/SchoolAdmin/Pages/CmsSettings.php` (+247 / −1)** — almost pure addition. Likely a feature expansion (paired with the `CmsSetting.php` model +13 / −1 and the `_040_extend_cms_settings.php` migration). Probably safe but worth a quick eyeball.
5. **`app/Database/Seeders/DefaultCertificateAndIdCardTemplatesSeeder.php` (+338 / −171)** — heavy rewrite of a seeder. Confirm it doesn't re-insert duplicates if re-run on existing tenants (idempotent INSERT vs `firstOrCreate`).
6. **The four 7-line additions to `Cms*Resource/Pages/List*.php`** — identical pattern across 4 files, likely a small refactor (header action / button). Safe.
7. **Spot-checked sanity diffs** during report generation — none of the small `+1 −0` / `+1 −4` modifications looked like accidental debug code or experiments. Examples: `SaasAdminPanelProvider` adds the new `BlockCentralPortalOnTenantHost::class` middleware; `ApprovalRequest` adds an enum cast for `status`; `CafeteriaMenuItemResource` swaps an inline `Select::make('campus_id')` for `\App\Support\CampusField::make(required: false)`.

## What's not in this report (intentionally)

- The Plan A-E source files — they already match between host and container; nothing to do for them.
- Runtime artifacts (`bootstrap/cache/services.php`, `packages.php`, `livewire.php` cache, `framework/cache/*`, `framework/sessions/*`) — not source, regenerated on container start.
- The kynex-app reverse-proxy `custom-aqmdigital.com.conf` — handled separately in Phase 2 (Option B1: per-file bind mount).

## Working files (do not commit)

- `/tmp/container_snapshot/` — full extract of container source for the diffs in this report. Will be deleted after Phase 0 commit.
- `/tmp/diff_raw.txt`, `/tmp/only_in_container.txt`, `/tmp/differs.txt`, `/tmp/new_with_purpose.txt`, `/tmp/modified_with_counts.txt` — intermediate scan output.
