# InfixEdu → KynexEdu Feature Porting Roadmap

**Goal:** Bring the best of InfixEdu's school-management features into KynexEdu, keeping KynexEdu's modern architecture. For every feature: **Keep** KynexEdu's version where it's already as good or better, **Enhance** existing features with InfixEdu's extra capabilities, and **Add** genuinely missing features — all re-implemented natively in KynexEdu.

## Architecture decisions (locked)
- **Re-implement natively, never copy.** InfixEdu is Laravel 12 Blade MVC (MySQL, integer IDs, `sm_`/`fm_` table prefixes, `nwidart/laravel-modules`). KynexEdu is Laravel 13 + **Filament 5** (PostgreSQL, ULIDs, multi-tenant DB-per-tenant, Spatie permissions). Every ported feature = tenant migration + `App\Models\Tenant\*` model + `App\Filament\SchoolAdmin\Resources\*` resource/page.
- **Match KynexEdu conventions:** ULID PKs, `SoftDeletes`, multi-tenant DB isolation (drop Infix's `school_id`/`academic_id`/`active_status` — tenancy handles school scope; use `academic_year_id` FK only where academic-year filtering is genuinely needed), `created_by`/`updated_by` per existing patterns, `HasPermissionCheck` RBAC trait, navigation groups.
- **Enhance existing too:** where KynexEdu already has a feature, fold in Infix's extra fields/workflows.
- **Existing navigation groups to reuse:** Academic Setup, Admissions, Cafeteria, Certificates & ID Cards, Communication, Compliance, Examinations, Fees, Finance, Front Office, Health & Wellbeing, Hostel, Inventory, Library, Online Classes, Settings, Staff & HR, Students, System, Transport, Website CMS.

---

## Section-by-section gap analysis

Legend: ✅ Keep (already good) · 🔧 Enhance (add Infix extras) · ➕ Add (missing)

### 1. Admin Section → "Front Office" group
| Page | Status | Action |
|---|---|---|
| Admission Query (+ follow-ups) | ➕ | New `AdmissionEnquiry` + `AdmissionEnquiryFollowup` models/resource |
| Complaint | ➕ | New `Complaint` resource (type/source dropdowns, action taken, attachment) |
| Postal Receive | ➕ | New `PostalReceive` resource |
| Postal Dispatch | ➕ | New `PostalDispatch` resource |
| Phone Call Log | ➕ | New `PhoneCallLog` resource (incoming/outgoing, follow-up) |
| Admin Setup (reference lists) | ➕ | New `FrontOfficeReference` (purpose/complaint type/source/reference) — foundation for the above |
| Visitor Book | 🔧 | Exists (`Visitor`); add "purpose" dropdown sourced from Admin Setup |
| ID Card / Generate ID Card | ✅ | `IdCardTemplate` + `GenerateIdCards` page |
| Certificate / Generate Certificate | ✅ | `CertificateTemplate` + `GeneratedCertificate` + `IssueCertificate` |

### 2. Academics → "Academic Setup" group
| Page | Status | Action |
|---|---|---|
| Class / Section / Subjects / Assign Subject / Class Routine | ✅ | All present |
| Optional Subject | 🔧 | Exists as `is_optional` flag on `ClassSubject`; optionally extend to per-student elective selection |
| Assign Class Teacher | ➕ | Class/section in-charge teacher (distinct from per-subject teacher) |
| Class Room | ➕ | Physical classroom registry (+ link to routine) |

### 3. Student Info → "Students" group
| Page | Status | Action |
|---|---|---|
| Student Category / Add Student / Student List / Attendance / Promote / Delete | ✅ | Present |
| Multi Class Student | ➕ | Pivot enrolment so a student can sit in multiple classes |
| Unassigned Student | ➕ | Filtered page (no class/section) |
| Disabled Students | ➕ | Page listing inactive/soft-deleted students with restore |
| Student Group | ➕ | `StudentGroup` model + assignment |
| Subject Wise Attendance | ➕ | Per-subject/period attendance (current is per-day) |
| Student Export | ➕ | Excel export action on Student table |
| SMS Sending Time / Student Settings | ➕ | Student-module config page |

### 4. Study Material & Lesson Plan → "Academic Setup" group
| Page | Status | Action |
|---|---|---|
| Syllabus | ✅ | `Syllabus` + `SyllabusTopic` |
| Assignment | ✅ | `HomeworkAssignment` |
| Upload Content (study material) | ➕ | Teacher content upload per class/subject |
| Other Downloads | ➕ | General downloadable docs (or fold into Download Center) |
| Lesson Plan module (Lesson, Topic, Topic Overview, Lesson Plan, Settings) | 🔧/➕ | Partial overlap with Syllabus/Topic; add proper Lesson + Lesson Plan layer |

### 5. Download Center → new "Resources" group
| Page | Status | Action |
|---|---|---|
| Content Type / Content List / Shared Content List / Video List | ➕ | Whole module missing |

### 6. Behaviour Records → "Health & Wellbeing" group
| Page | Status | Action |
|---|---|---|
| Incidents / Assign Incident | ✅/🔧 | `BehaviorIncident` present; add incident points/levels + assign workflow |
| Behaviour reports / Settings | ➕ | Reports + settings (incident catalog) |

### 7. HomeWork → "Online Classes"/"Academic Setup"
| Page | Status | Action |
|---|---|---|
| Add/List | ✅ | Present |
| Homework Report / evaluation | ✅/🔧 | Submission grading exists; add evaluation report page |

### 8. Fees → "Fees" group
| Page | Status | Action |
|---|---|---|
| Group / Type / Master / Collection / Discount / Fine / Reports | ✅ | Present |
| Fees Invoice (printable per-student) | ➕ | Invoice document + PDF |
| Bank Payment (submit → admin approve) | ➕/🔧 | Approval workflow (enum exists, no flow) |
| Fees Carry Forward | ➕ | Carry unpaid balance across year/term |

### 9. Wallet → new "Finance" subgroup
| Page | Status | Action |
|---|---|---|
| Wallet balance / Pending-Approve-Reject Deposit / Transaction ledger / Refund Request | ➕ | Whole module missing |

### 10. Accounts → "Finance" group
| Page | Status | Action |
|---|---|---|
| Expense / Budget / Profit&Loss | ✅ | `Expense`, `Budget`, `FinancialReport` page |
| Income (heads + entries) | ➕ | Missing |
| Chart Of Account | ➕ | Missing |
| Bank Account | ➕ | Missing |
| Fund Transfer | ➕ | Missing |

### 11. Inventory → "Inventory" group
| Page | Status | Action |
|---|---|---|
| Category / Item / Store / Supplier / Receive / Issue | ✅ | Present |
| Item Sell | ➕ | Sell-to-student/staff transaction type |

### 12. Library → "Library" group
| Page | Status | Action |
|---|---|---|
| Book / Issue-Return / All Issued | ✅ | Present |
| Book Categories | 🔧 | Model exists, managed inline; add standalone resource if desired |
| Library Member | 🔧 | `LibraryMember` model exists, **no resource** — add resource |
| Library Subject | ➕ | Minor |

### 13. Transport → ✅ complete (Routes, Vehicle, Assign Vehicle)
### 14. Dormitory/Hostel → ✅ complete (Building, Room, Room Type, Allocation, Gate Pass)

### 15. Examination → "Examinations" group
| Page | Status | Action |
|---|---|---|
| Exam Type / Setup / Schedule / Marks Register / Marks Grade | ✅ | Present |
| Exam Attendance (regular exams) | ➕ | Only admission tests have it |
| Send Marks By SMS | ➕ | One-click marks SMS |

### 16. Exam Plan → "Examinations" group
| Page | Status | Action |
|---|---|---|
| Admit Card (regular exams) | ➕ | Missing |
| Seat Plan | ➕ | Missing |

### 17. Online Exam → "Examinations" group
| Page | Status | Action |
|---|---|---|
| Question Group / Question Bank / Online Exam (enrolled students) | ➕ | Only admission-test engine + `OnlineClassQuiz` exist; add general online exam |

### 18. Reports → "Reports" (existing pages + new formats)
| Report | Status | Action |
|---|---|---|
| Student Attendance Report | ✅ | `AttendanceReport` |
| Merit List / Progress Card | 🔧 | Partial (`ResultsPage`, `ResultCardController`); formalize |
| Exam Routine / Online Exam / Subject-wise Marksheet / Tabulation Sheet / Mark Sheet / Progress Card 100% / Previous Result | ➕ | Missing formats |
| Subject Attendance / Homework Evaluation / Student History / Login Report / Transport Report / Dormitory Report / Guardian Report | ➕ | Missing |

### 19. Human Resource → "Staff & HR" group
| Page | Status | Action |
|---|---|---|
| Designation / Department / Staff / Staff Attendance / Payroll / Salary Components | ✅ | Present |
| Staff Settings | ➕ | Minor config |

### 20. Teacher Evaluation → new module, "Staff & HR" group
| Page | Status | Action |
|---|---|---|
| Evaluation / Approved-Pending reports / Settings | ➕ | Whole module missing |

### 21. Leave → "Staff & HR" group
| Page | Status | Action |
|---|---|---|
| Apply / Approve-Pending / Leave Type | ✅ | Present |
| Leave Define (quota per role) | ➕ | Missing |

### 22. Role & Permission → "Settings"/"System"
| Page | Status | Action |
|---|---|---|
| Role | ✅ | Spatie + `RoleManagement` |
| Login Permission (which roles can log in) | ➕ | Missing |
| Due Fees Login Permission (block on overdue fees) | ➕ | Missing |

### 23. Chat → new module, "Communication" group
| Page | Status | Action |
|---|---|---|
| Chat Box / Invitation / Blocked User / Settings | ➕ | User-to-user messaging (distinct from AI assistant & WhatsApp inbox) |

### 24. Communicate → "Communication" group
| Page | Status | Action |
|---|---|---|
| Notice / Send Email-SMS / Email-SMS Log / Email-SMS Template | ✅ | Present |
| Event | ➕ | Missing |
| Calendar (events view) | ➕ | Missing |

### 25. Style → "Settings" group
| Page | Status | Action |
|---|---|---|
| Background Settings / Color Theme | ➕ | Appearance/theme settings (CmsSetting has single `primary_color` only) |

### 26. User Log → "System" group
| Page | Status | Action |
|---|---|---|
| User activity/login log | ➕ | `PiiAccessLog` is PII-access only; add general activity log |

### 27. Utilities / Module Manager → "System" group
| Page | Status | Action |
|---|---|---|
| Utilities (cache/optimize) | ➕ | Low priority |
| Module Manager (feature toggles) | ➕ | Maps to plan flags / SaaS feature gating |

### 28. Settings — Custom Field → "Settings" group
| Page | Status | Action |
|---|---|---|
| Student / Staff registration custom fields | ➕ | Dynamic custom fields engine |

### 29. Settings — General Settings → "Settings" group
| Area | Status | Action |
|---|---|---|
| School profile / Academic Year / Notification / Email / SMS | ✅/🔧 | Present (partly via `CmsSettings`, `CommunicationSettingsPage`) |
| Holiday / Manage Currency / Payment Settings / Weekend / Language / Backup / About&Update / Api Permission / Preloader / Cron Job / Two Factor | ➕ | Missing config areas |

### 30. Settings — Frontend CMS → "Website CMS" group
| Area | Status | Action |
|---|---|---|
| Home Slider / Pages / News(Announcement) / Gallery / Result | ✅ | Present |
| Manage Theme / Menu / Testimonial / Header-Footer content / Contact Message | ➕ | Missing |

### 31. Settings — Fees Settings & Exam Settings
| Area | Status | Action |
|---|---|---|
| Fees Invoice Settings | ➕ | Invoice format/branding config |
| Exam Settings (format / position / admit card / seat plan) | ➕ | Backs Exam Plan + reports |

---

## Suggested execution order (phases)

Ordered by value, low risk, and dependencies. Each phase is a self-contained deliverable we verify before moving on.

1. **Phase 1 — Front Office (Admin Section).** Admin Setup (reference data) → Admission Query (+follow-ups), Complaint, Postal Receive, Postal Dispatch, Phone Call Log; enhance Visitor. *All simple CRUD; quickest visible win.*
2. **Phase 2 — Academics & Student Info gaps.** Assign Class Teacher, Class Room, Multi Class Student, Student Group, Unassigned/Disabled student pages, Subject-wise Attendance, Student Export, Student Settings.
3. **Phase 3 — Finance expansion.** Income, Chart of Account, Bank Account, Fund Transfer; Fees Invoice, Carry Forward, Bank Payment approval; Wallet module; Inventory Item Sell.
4. **Phase 4 — Examination expansion.** Exam Attendance, Admit Card, Seat Plan, Online Exam (Question Group/Bank/Exam), Send Marks By SMS, Exam Settings.
5. **Phase 5 — Reports.** All missing exam + student report formats.
6. **Phase 6 — Academic content & HR modules.** Lesson Plan module, Study Material/Upload Content, Download Center, Behaviour enhancements; Teacher Evaluation, Leave Define.
7. **Phase 7 — Communication & Access.** Event + Calendar, Chat module, User Log, Login Permission + Due-Fees Login Permission, Style/Theme, Utilities, Module Manager.
8. **Phase 8 — Settings & CMS completeness.** Custom Fields; General Settings areas (Holiday, Currency, Payment, Weekend, Backup, etc.); CMS (Menu, Testimonial, Header/Footer, Contact Message); Fees Invoice Settings.

---

# AI + Automation + Modernization Layer

**Directive (2026-05-24):** don't just clone InfixEdu — make every ported/modernized feature **AI-powered and automated** where it adds real value. KynexEdu is a kynexsolutions.com product with a mature AI stack; we plug into it, never rebuild it.

## Locked AI policy (2026-05-24)
- **Enablement & billing:** SaaS admin enables AI on the platform key, capped by each tenant's monthly budget (`ai_monthly_budget_paisas`). A school admin can **request a budget upgrade** and/or **bring their own provider key + model** (BYO key = the school pays its own provider; BYO self-enables). School-side **AI Settings** page = status + BYO key/model + request-upgrade + live usage from `AiUsageLog`.
- **Academic integrity (hard rules):**
  - **No AI auto-grading of homework/assignments.** Grading stays human. (Exam/paper checking — e.g., essay/short-answer marking like the existing admission flow — remains available.)
  - **Students may NOT use AI to write/complete homework or assignments.** The student assistant explains concepts, how-to, gives worked examples and practice questions, but never produces submittable work. Enforced in `RolePrompts` STUDENT prompt.
  - **Optional premium add-on: AI-content / originality detection** (e.g., Originality.ai Pro ≈ $59/mo ≈ 16,500 PKR for ~1M words) that schools subscribe to **separately**. Surfaces on Homework/Assignment submissions as an "AI-written?" / originality check — opt-in per school, not on by default. Build via an `AiContentDetector` interface + pluggable driver (Originality.ai) in Phase 6.

## Reuse these existing abstractions (do NOT reinvent)
| Need | Use | Entry point |
|---|---|---|
| Call an LLM | `App\Services\Ai\AiAssistant` | `AiAssistant::forCurrentTenant()->chat($systemPrompt, $messages, $feature)` — handles per-tenant model/key (encrypted), monthly budget, `AiUsageLog` cost logging |
| Feature-level AI (generate/grade/extract) | Pattern from `App\Services\AdmissionAiService` | JSON-schema system prompt → `chat()` → `extractJson()` → write DB; batch heavy work via a self-chaining `ShouldQueue` job (see `GradeAdmissionAnswersBatch`) |
| Assistant prompts + live data | `App\Services\Ai\RolePrompts`, `RoleScopedFacts` | role-scoped system prompt + authoritative data snapshot |
| Async work | `database` queue + `app/Jobs` | `YourJob::dispatch(...)->delay(...)`, `$tries`/`$backoff` |
| Recurring work | per-tenant scheduler in `routes/console.php` | `Schedule::call(fn()=>Tenant::where('status','active')->each(fn($t)=>$t->run(fn()=>YourJob::dispatch())))->dailyAt(...)` |
| React to domain changes | `app/Events` + `app/Listeners` + `app/Observers` | dispatch a new Event; listener dispatches a Job |
| Send a message | `App\Services\NotificationService` | `sendFromTemplate($slug, $notifiable, $data, $eventTrigger)` — push→WhatsApp→SMS→Email fallback |
| Per-tenant AI budget/cost | `Tenant` cols `ai_enabled`, `ai_model`, `ai_monthly_budget_paisas`, `ai_used_this_month_paisas` | already enforced inside `AiAssistant` |

## Phase 0 — AI platform building blocks (build FIRST; everything else reuses these)
1. **Feature-AI helper services** (generalize the `AdmissionAiService` pattern so each feature isn't ad-hoc):
   - `AiDraftService` — draft prose (notices, emails/SMS, complaint replies, report narratives, CMS content). Always tenant-budget-aware.
   - `AiClassifier` — classify/route/score (complaint category + urgency, enquiry intent + lead score, sentiment).
   - `AiExtractor` — structured + vision extraction from uploads (admission docs, **bank payment slips**, postal letters, ID images) using `smalot/pdfparser` + vision model.
   - `AiInsights` — analytics narratives + predictions (at-risk students, fee-default risk, P&L explainers).
   - Generalize `AiQuestionGenerator` (lift from AdmissionAiService) for the Question Bank.
2. **Reusable Filament "AI" actions** — drop-in buttons (`✨ Draft`, `✨ Summarize`, `✨ Suggest`, `✨ Explain`) usable on any resource form/table/page. One trait/action class; wired per feature.
3. **Agentic assistant (tool/function-calling)** — upgrade `AiAssistantPanel` from read-only Q&A to an action copilot with a **tool registry**: read tools (lookup student/fees/attendance, run report) + write tools (create notice, draft+queue SMS to a class, mark attendance) — every write tool **RBAC-gated** and routed through the existing `ApprovalService` for sensitive actions. Staged: read tools first, then gated write tools.
4. **`StudentRiskService`** — composite risk score (attendance + marks + behaviour + fees) with an AI narrative; surfaced via a dashboard widget + automated alerts.
5. **AI Settings page** (Filament, Settings group) — per-tenant: enable/disable AI, choose model/provider, set monthly budget, toggle which AI features are on, view usage from `AiUsageLog`.
6. **New domain events** for automation hooks added as features land (e.g., `EnquiryCreated`, `ComplaintCreated`, `PaymentRecorded`, `MarksFinalized`, `ResultPublished`).

## Per-phase AI + automation enhancements
Tags: 🤖 AI · ⚙️ Automation · ✨ Modernization (UX)

- **Phase 1 — Front Office:** Admission Query: 🤖 intent classify + lead score, 🤖 auto-draft follow-up message, ⚙️ scheduled follow-up reminders, ⚙️ auto-create enquiry from WhatsApp/contact form. Complaint: 🤖 auto-categorize + urgency + 🤖 suggested response, ⚙️ auto-escalate if unresolved N days. Postal: 🤖 extract sender/ref from scanned letter. Phone Call: 🤖 summarize notes, ⚙️ follow-up reminder. Visitor: ⚙️ pre-register + QR + host check-in notify.
- **Phase 2 — Academics & Students:** 🤖/⚙️ auto class-routine generation + conflict detection (Class Routine, Assign Teacher, Class Room). 🤖 auto-grouping (remedial groups by performance). ⚙️ subject-wise attendance from biometric. ✨ natural-language Student Export ("class 5 girls >90% attendance"). 🤖 **at-risk student detection** widget + ⚙️ teacher/parent alerts.
- **Phase 3 — Finance:** ⚙️ auto invoice generation + 🤖 dunning messages. 🤖 **bank slip verification** (extract amount/date/ref, match invoice). ⚙️ carry-forward. 🤖 fee-default prediction. 🤖 expense auto-categorization + anomaly detection + P&L narrative. Wallet: ⚙️ top-up reminders, 🤖 anomaly detection.
- **Phase 4 — Examination:** 🤖 **question generation** from syllabus/topic (Question Bank), 🤖 auto-grade essays/short answers, 🤖 proctoring signals. ⚙️/🤖 seat-plan optimization. ⚙️ send-marks-by-SMS. 🤖 per-student/class result analysis.
- **Phase 5 — Reports:** 🤖 executive-summary narrative on every report. ✨ natural-language report builder. 🤖 **personalized progress-card comments** per student. ⚙️ scheduled auto-email (infra exists).
- **Phase 6 — Content & HR:** 🤖 **lesson-plan generation** from topic + standards. 🤖 summarize/tag study material + recommend to weak students. ⚙️ video transcribe/caption. 🤖 behaviour incident summary + suggested intervention. 🤖 teacher-evaluation sentiment + summary. ⚙️ leave accrual.
- **Phase 7 — Communication & Access:** 🤖 event description drafting + ⚙️ reminders. Chat: 🤖 smart replies, 🤖 Urdu⇄English translation, 🤖 thread summary, 🤖 moderation/toxicity. 🤖 login anomaly detection. ⚙️ login + due-fees enforcement. 🤖 theme palette from logo.
- **Phase 8 — Settings & CMS:** 🤖 suggest custom fields. 🤖 **CMS content + SEO meta generation** (multilingual; ties to kynexsolutions.com SEO tooling). 🤖 contact-message auto-reply/route. ⚙️ backup + health monitoring with AI summaries.

## Build status
- **Phase 0 — DONE.** AI foundation in `app/Services/Ai/` (AiDraftService, AiClassifier, AiInsights, AiExtractor, AiAvailability, ExtractsJson trait, StudentRiskService), reusable `AiActions` Filament field actions, `AiSettingsPage`, RolePrompts student guardrail. (Agentic write-tools → later task.)
- **Phase 1 — DONE.** Front Office, all native Filament in the "Front Office" group, AI + automation wired:
  - `FrontOfficeReference` (Admin Setup reference lists) + resource.
  - `AdmissionEnquiry` (+ `AdmissionEnquiryFollowup` relation manager) — AI lead-score action + AI follow-up draft.
  - `Complaint` — AI triage (auto category/urgency/sentiment) + AI suggested-response draft.
  - `PostalRecord` (single table) → `PostalReceiveResource` + `PostalDispatchResource` (shared `PostalResourceShared` trait) — AI extract-from-scan (PDF/vision).
  - `PhoneCallLog` — AI note refine.
  - `Visitor` enhanced with structured `visit_purpose_id`.
  - Automation: `FrontOfficeFollowUpReminders` job + daily scheduler (08:00) → in-app reminders to assigned staff for due enquiry/call follow-ups.
  - RBAC: reused `manage_inquiries`; added `manage_complaints`, `manage_postal`, `manage_phone_call_log`, `manage_front_office_setup` (seeded to school_admin + receptionist). `created_by` auto-set via `TracksCreator` trait.
  - Migrations: `database/migrations/tenant/2026_05_24_0000{01..06}_*` (run via `php artisan tenants:migrate`).

- **Phase 2 — DONE.** Academics & Student Info, no duplication (reused existing columns/enums where present):
  - `Classroom` (Class Rooms registry) + resource — `manage_classes`.
  - `StudentGroup` (+ members relation manager) + resource — `view_students`.
  - `StudentClassEnrolment` (Multi-Class Student) → relation manager on StudentResource; Student model got `classEnrolments()` + `groups()`.
  - **Assign Class Teacher** → bulk action on existing `SectionResource` (reuses `sections.class_teacher_id`; no new table).
  - **Unassigned / Disabled Students** → tabs on `ListStudents` (status buckets; no new pages/tables) + **Export CSV** header action.
  - `SubjectAttendanceRecord` (per-period attendance) + resource — `mark_attendance_manual`. (Bulk marking page = future enhancement.)
  - Migrations `2026_05_24_0001{01..04}`. No new permissions needed (reused existing). Deferred: Student Settings (no settings infra yet).

- **Phase 3 — IN PROGRESS.**
  - Wave 1 DONE — Accounts module (Finance group, `manage_accounts` perm seeded to school_admin + bursar/accountant + owner): `AccountHead` (Chart of Accounts), `BankAccount` (running balance), `Income` (AI categorize; credits bank), `FundTransfer` (debits/credits banks). Balance maintained via model events with reverse-on-edit/delete/restore. Migration `2026_05_24_000201`. **Money entered in PKR, stored in paisas** (format/dehydrate on fields; display via `formatStateUsing`, the codebase convention — NOT `->money()`).
  - **Fee easiness (user request) DONE** — added AI "Suggest with AI" action to existing `FeeCatalog` page: one click drafts fee groups + types (firstOrCreate, skips existing), admin then sets amounts. No duplication (FeeCatalog already merges Group+Type).
  - Wave 2 DONE — **Wallet module** (Finance group, `manage_wallet` perm seeded): `Wallet` (per-student balance + atomic `credit()/debit()` ledger), `WalletTransaction`, `WalletDeposit` (approve→credits; 🤖 AI slip verify; pending nav badge), `WalletRefund` (approve→debits; pending nav badge). WalletResource = balances + admin Top-up action + transactions relation manager. Migration `2026_05_24_000202`.
  - Wave 3 REMAINING — Fees Invoice PDF, Bank-Payment approval flow, Fee Carry-Forward, Inventory Item Sell.

## Per-feature build checklist (applied to every ➕/🔧 item)
1. Tenant migration in `database/migrations/tenant/` (ULID, soft deletes, FKs, indexes).
2. `App\Models\Tenant\*` model (fillable, casts, relationships, `HasUlids`, `SoftDeletes`).
3. Filament resource/page under `App\Filament\SchoolAdmin\` (form + table + pages), correct `navigationGroup`, `HasPermissionCheck` + RBAC permission.
4. Seed default reference data where relevant.
5. Register any new permission(s); wire navigation sort.
6. `php artisan tenants:migrate` and smoke-test in a tenant.

> Note: new tenant migrations must be run with `php artisan tenants:migrate` (not plain `migrate`); on Coolify, mind the `RUN_MIGRATIONS_AND_SEED` flag.
