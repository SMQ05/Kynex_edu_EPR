# KynexEdu — InfixEdu Feature Port: Developer Guide

This guide documents the ongoing effort to port the best of **InfixEdu** (reference source at `/home/kynex_solutions/Documents/infix_school`) into **KynexEdu**, re-implemented natively, AI-powered and automated. It is both a build log and the **conventions reference** every contributor (human or agent) must follow.

Companion docs: `INFIX_PORTING_ROADMAP.md` (full gap analysis + phase plan).

---

## 1. Architecture (do not fight it)

| Aspect | KynexEdu (target) | InfixEdu (reference only) |
|---|---|---|
| Framework | Laravel 13 + **Filament 5** | Laravel 12 Blade MVC |
| DB | PostgreSQL, **ULIDs**, multi-tenant (DB-per-tenant via stancl/tenancy) | MySQL, integer IDs, `sm_`/`fm_` prefixes |
| Admin UI | `app/Filament/SchoolAdmin/Resources` + `Pages` | Blade controllers/views |
| Money | integer **paisas** (`*_paisas`) | decimals |

**Golden rule: never copy InfixEdu code.** Re-implement features natively. Drop Infix's `school_id`/`academic_id`/`active_status` (tenancy handles school scope; use soft deletes + `academic_year_id` only where genuinely needed).

**No duplicates.** Before building anything, grep KynexEdu for the capability under all plausible names. If it exists → enhance in place. Prefer one table + a discriminator column over near-identical twins (e.g. single `postal_records` with a `direction` column).

---

## 2. Conventions (copy these exactly)

### Tenant migration
`database/migrations/tenant/2026_05_24_NNNNNN_*.php` — run with `php artisan tenants:migrate` (NOT plain migrate).
```php
Schema::create('things', function (Blueprint $table): void {
    $table->ulid('id')->primary();
    // ... columns ...
    $table->ulid('created_by')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('created_by')->references('id')->on('school_users')->nullOnDelete();
});
```
Real table names: classes table is **`classes`** (model `SchoolClass`), plus `students`, `subjects`, `sections`, `academic_years`, `campuses`, `school_users`. Money columns: `bigInteger` for balances (can go negative), `unsignedBigInteger` for amounts.

### Model (`app/Models/Tenant/*`)
```php
use HasUlids, SoftDeletes, BelongsToCampus, TracksCreator; // TracksCreator auto-sets created_by
```
- `App\Models\Concerns\TracksCreator` — auto-fills `created_by` from the school_users guard on insert.
- `App\Models\Concerns\BelongsToCampus` — adds `campus_id` auto-scoping (admins bypass).
- `App\Models\Concerns\HasPaisaAttributes` — `getPriceInPkr('col')` helper.
- Casts via `protected function casts(): array`.

### Filament resource (`app/Filament/SchoolAdmin/Resources/*`)
Filament 5 namespaces (these differ from v3/v4 — use exactly these):
```php
use App\Filament\SchoolAdmin\Concerns\HasPermissionCheck;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;                       // form(Schema $schema)
use Filament\Schemas\Components\Section;           // form sections
use Filament\Schemas\Components\Utilities\Get;     // and ...\Set
use Filament\Forms\Components\{TextInput,Select,Textarea,DatePicker,Toggle,FileUpload,...};
use Filament\Tables\Table;
use Filament\Tables\Columns\{TextColumn,IconColumn};
use Filament\Tables\Filters\{SelectFilter,Filter};
use Filament\Actions\{Action,EditAction,DeleteAction,BulkAction,BulkActionGroup,DeleteBulkAction,CreateAction,AttachAction,DetachAction};
use Filament\Notifications\Notification;

class XResource extends Resource {
    use HasPermissionCheck;
    protected static string $rbacPermission = 'some_permission';
    protected static ?string $model = X::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-...';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = '...';
    protected static ?int $navigationSort = 1;
    public static function form(Schema $schema): Schema { return $schema->schema([...]); }
    public static function table(Table $table): Table { return $table->columns([...])->filters([...])->actions([...])->bulkActions([...]); }
    public static function getPages(): array { return ['index'=>Pages\ListX::route('/'),'create'=>Pages\CreateX::route('/create'),'edit'=>Pages\EditX::route('/{record}/edit')]; }
}
```
- **Always create the 3 page classes** (`ListX`, `CreateX`, `EditX`) — this codebase does NOT use the `ManageRecords` simple-resource pattern. Create pages: `getRedirectUrl()` → index. Edit pages: `getHeaderActions()` → `[DeleteAction::make()]`. List pages: `getHeaderActions()` → `[CreateAction::make()]`.
- Money columns display via `->formatStateUsing(fn (int $state): string => 'PKR '.number_format($state/100,2))` — **do NOT use `->money()`**.
- Money form fields: `->formatStateUsing(fn($s)=>$s/100)->dehydrateStateUsing(fn($s)=>(int)round((float)$s*100))` (enter PKR, store paisas).
- List-page filter tabs: `getTabs()` returning `Filament\Schemas\Components\Tabs\Tab` with `->modifyQueryUsing()` + `->badge()`.
- Relation managers: `Filament\Resources\RelationManagers\RelationManager`, `form(Schema)->components([...])`, `table(Table)`.
- AI field buttons: `Textarea::make('x')->hintActions([ \App\Filament\SchoolAdmin\Support\AiActions::draftInto('x',[...]), AiActions::refineInto('x') ])`.

### RBAC / permissions
- `HasPermissionCheck` + `protected static string $rbacPermission`. Admins (`SCHOOL_ADMIN`, `INSTITUTE_HEAD`, `MULTI_INSTITUTE_HEAD`) **bypass all checks**; a missing permission slug fails safe (returns false, never errors). So new slugs are safe to use immediately.
- Register new permissions in `database/seeders/RbacPermissionSeeder.php`: add to the definitions array (~line 124) and grant in role arrays (`school_admin`, plus the relevant role + the owner/all-permissions block).

---

## 3. AI + Automation (reuse, never rebuild)

| Need | Use | Entry point |
|---|---|---|
| Call an LLM | `App\Services\Ai\AiAssistant` | `AiAssistant::forCurrentTenant()->chat($system,$messages,$feature)` — per-tenant key/model, monthly budget, `AiUsageLog` cost logging |
| Draft prose | `App\Services\Ai\AiDraftService` | `draft()/refine()/translate()` |
| Classify/score/sentiment | `App\Services\Ai\AiClassifier` | `classify()/score()/sentiment()` |
| Summaries/recommendations | `App\Services\Ai\AiInsights` | `summarize()/recommendations()` |
| Extract from PDF/image | `App\Services\Ai\AiExtractor` | `extract()/extractFromFile()` (vision) |
| At-risk students | `App\Services\Ai\StudentRiskService` | `forStudent()/explain()` |
| "Is AI on?" (UI gating) | `App\Services\Ai\AiAvailability` | `AiAvailability::enabled()` |
| Reusable ✨ field buttons | `App\Filament\SchoolAdmin\Support\AiActions` | `draftInto()/refineInto()` |
| JSON parsing of AI replies | `App\Services\Ai\Concerns\ExtractsJson` (trait) | `$this->extractJson($reply)` |
| Async work | `database` queue + `app/Jobs` | `Job::dispatch()` |
| Recurring work | per-tenant scheduler in `routes/console.php` | `Schedule::call(fn()=>Tenant::on('central')->where('status','active')->each(fn($t)=>$t->run(fn()=>Job::dispatch())))->dailyAt('HH:MM')->name('...')->withoutOverlapping()` |
| Send message | `App\Services\NotificationService` | `sendFromTemplate(...)`, or `NotificationService::sendImmediate('in_app',$userId,$body,$trigger)` |

### Locked AI policy
- **Billing:** SaaS admin enables AI on the platform key, capped by each tenant's `ai_monthly_budget_paisas`. Schools can request an upgrade or bring their own key/model (school-side `AiSettingsPage`).
- **Academic integrity (hard rules):** NO AI auto-grading of homework/assignments (grading stays human; exam/essay paper-checking like the admission flow is OK). Students may use AI to learn (explain/how-to) but **never to write/complete** homework — enforced in `RolePrompts` STUDENT prompt. Optional **premium** AI-originality detection (Originality.ai) is opt-in per school via an `AiContentDetector` interface (Phase 6).

---

## 4. Per-feature build checklist
1. Grep existing — confirm it's genuinely new (or enhance in place).
2. Tenant migration (ULID, soft deletes, FKs, indexes).
3. `App\Models\Tenant\*` model (fillable, casts, relations, `HasUlids`+`SoftDeletes`+`TracksCreator`).
4. Filament resource + 3 pages (+ relation managers) with correct `navigationGroup` + `HasPermissionCheck`.
5. Wire AI (`AiActions` on fields, or AI row actions gated by `AiAvailability::enabled()`).
6. Automation hook if relevant (job + scheduler entry; reuse `NotificationService`).
7. Register permission(s) in `RbacPermissionSeeder`.
8. `php -l` every file. In container: `php artisan tenants:migrate` + `tenant:seed --class=RbacPermissionSeeder` + `filament:optimize-clear`.

---

## 5. Build status (what's done)

### Phase 0 — AI foundation ✅
`app/Services/Ai/`: `AiAssistant` (existing), `AiDraftService`, `AiClassifier`, `AiInsights`, `AiExtractor`, `AiAvailability`, `StudentRiskService`, `Concerns/ExtractsJson`. Reusable `AiActions` Filament field actions. `AiSettingsPage` (school BYO key/model + usage + request upgrade). `RolePrompts` student integrity guardrail.

### Phase 1 — Front Office ✅ ("Front Office" group)
- `FrontOfficeReference` (Admin Setup reference lists) + resource.
- `AdmissionEnquiry` (+ follow-ups RM) — AI lead-score + AI follow-up draft.
- `Complaint` — AI triage (category/urgency/sentiment) + AI suggested reply.
- `PostalRecord` → `PostalReceiveResource` + `PostalDispatchResource` (shared trait) — AI extract-from-scan.
- `PhoneCallLog` — AI note refine.
- `Visitor` enhanced with `visit_purpose_id`.
- `FrontOfficeFollowUpReminders` job + daily scheduler.
- Perms: reused `manage_inquiries`; added `manage_complaints`, `manage_postal`, `manage_phone_call_log`, `manage_front_office_setup`.

### Phase 2 — Academics & Student Info ✅
- `Classroom` (Class Rooms) resource.
- `StudentGroup` (+ members RM) resource.
- `StudentClassEnrolment` (Multi-Class) RM on StudentResource (+ Student `classEnrolments()`/`groups()`).
- Assign Class Teacher = **bulk action on SectionResource** (reuses `sections.class_teacher_id`).
- Unassigned/Disabled = **tabs on ListStudents** + **Export CSV** action.
- `SubjectAttendanceRecord` (per-period attendance) resource.
- No new permissions (reused existing).

### Phase 3 — Finance (in progress)
- Wave 1 ✅ Accounts: `AccountHead` (Chart of Accounts), `BankAccount` (running balance), `Income` (AI categorize), `FundTransfer` — balances maintained via model events. Perm `manage_accounts`.
- Fee easiness ✅ AI "Suggest with AI" on existing `FeeCatalog` (drafts groups+types).
- Wave 2 ✅ Wallet: `Wallet` (atomic credit/debit ledger), `WalletTransaction`, `WalletDeposit` (approve→credit, AI slip verify), `WalletRefund` (approve→debit). Perm `manage_wallet`.
- Wave 3 ✅ Fees Invoice PDF (`FeeInvoice` page), Bank-Payment approval (`BankPaymentRequest` + AI slip verify), Fee Carry-Forward (`FeeCarryForward`), Inventory Item Sell (`InventorySale`).

### Phase 4 — Examination ✅
`ExamAttendanceRecord`, Admit Card PDF, Seat Plan, Online Exam engine (`QuestionGroup`, `ExamQuestion` bank, `OnlineExam`+attempts/answers, `ExamQuestionAiService`), Send Marks by SMS, Exam Settings. Perms: `manage_question_bank`, `manage_online_exams`, `manage_exam_plan`, `manage_exam_settings`.

### Phase 5 — Reports ✅
`ExamReportsPage` (Tabulation/Mark Sheet/Subject-wise Marksheet/Merit List/Progress Card/Previous Result/Exam Routine) + `StudentReportsPage` (Subject Attendance/Homework Evaluation/History/Transport/Dormitory/Guardian) — each with PDF + AI summary. Reused `view_marks`/`view_students`.

### Phase 6 — Academic content & HR ✅
`Lesson`+`LessonPlan` (AI gen), `StudyMaterial`, Download Center (`ContentType`+`DownloadContent`), `BehaviorIncidentType` + Behaviour report, `TeacherEvaluation` (AI sentiment), `LeaveQuota`, `AiContentDetector` (Originality.ai, opt-in) + `CheckOriginalityAction`. Perms: `manage_lesson_plans`, `manage_study_materials`, `manage_download_center`, `manage_teacher_evaluations`.

### Phase 7 — Communication & Access ✅
`Event`+Calendar, Chat (`Conversation`/`Message`/invitations/blocks+ChatBox), `UserActivityLog`+`UserActivity::log()`, Login/Due-Fees permission settings, Appearance/Theme, Utilities, Module Manager. Perms: `manage_events`, `use_chat`, `view_user_logs`, `manage_login_permissions`, `manage_appearance_settings`, `manage_system_utilities`, `manage_modules`.

### Phase 8 — Settings & CMS ✅
`school_settings` table + `SchoolSettings` helper, Custom Fields engine (`custom_fields`/`custom_field_values`+`CustomFieldsForm`), Holiday + Currency/Weekend/Payment/Language/Preloader/2FA/Backup/About/Cron pages, CMS Menu/Testimonial/Contact Message/Header-Footer, Fees Invoice Settings. Perm: `manage_custom_fields`.

### Follow-up wiring — ✅ APPLIED
- ✅ **Login Permission + Due-Fees block** enforced in `SchoolUser::canAccessPanel()` — fail-open on error, admins always retain access; due-fees only applies to guardians within threshold + grace.
- ✅ **Custom Fields** surfaced on Student & Staff forms (`CustomFieldsForm::section()`) with load/save in Create/Edit pages (staff strips the non-model key before persistence).
- ✅ **CheckOriginalityAction** attached to the homework-submissions relation manager (auto-hidden unless Originality.ai key set; advisory-only, never grades).
- ✅ **Appearance/Theme** applied via a fail-safe render-hook in `SchoolAdminPanelProvider` (`AppearanceSetting::current()`); added `Fees`/`Finance`/`Resources` to navigationGroups.
- ✅ **UserActivity::log()** wired to `Login`/`Logout` events (school_users guard) in `EventServiceProvider`.
- Still optional: link `behavior_incident_type_id` on BehaviorIncident; move Download Center into the `Resources` group; leave-quota accrual job; student-facing online-exam taking screen.

### Agentic assistant tools — ✅ DONE (task #10)
`AiAssistantPanel` now does **tool/function-calling** via `AiAssistant::chatWithTools()` (bounded read-tool loop, fail-safe fallback to plain chat). `AiToolRegistry` gates tools to **office/admin roles only** (teachers/parents/students excluded) + per-tool permission (admins bypass). Read tools: `LookupStudentTool`, `StudentFeesTool`, `AttendanceSummaryTool`. **Write tools stay staged** (must be RBAC + ApprovalService gated before registering).

---

## 6. Deploy (run in the Docker container — local CLI is PHP 8.3, vendor needs 8.4)
```bash
php artisan tenants:migrate
php artisan tenant:seed --class=RbacPermissionSeeder   # grant new permissions
php artisan filament:optimize-clear && php artisan icons:cache
php artisan about    # sanity check
```
Mind the Coolify `RUN_MIGRATIONS_AND_SEED` flag. Verify everything in a tenant context (multi-tenant — features live in tenant DBs).
