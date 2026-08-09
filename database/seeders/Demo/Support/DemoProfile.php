<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

use Illuminate\Support\Str;

/**
 * A locale + school-identity profile for the Demo seeder suite.
 *
 * WHY THIS EXISTS
 * ---------------
 * The Demo/* seeders were originally written for exactly one school:
 * AQM Public School in Lahore. Two things were baked in hard:
 *
 *   1. Locale data — Pakistani name pools, CNIC national IDs, +923 phone
 *      numbers, Lahore/Karachi addresses, easypaisa/jazzcash payments
 *      (see the still-intact {@see Pak} helper).
 *   2. School identity — name, tagline, postal address and email were
 *      const values on SchoolIdentitySeeder.
 *
 * That made the suite unusable for a demo aimed at any other market. Rather
 * than fork a second copy of ~145KB of seeders (which would immediately
 * drift), the locale- and school-specific values are pulled out behind this
 * profile. The seeders keep their logic; only their *data* varies.
 *
 * Everything locale-independent (weighted pick, password formula, email
 * slug) lives here once so profiles only declare what genuinely differs.
 *
 * PakProfile delegates to {@see Pak} verbatim, so the existing AQM demo
 * seeds byte-identically to before this refactor.
 *
 * All randomness goes through mt_rand(), which DemoTenantSeeder seeds once
 * via mt_srand() so a reseed reproduces the same school.
 */
abstract class DemoProfile
{
    /** The active profile for this seeding run. */
    private static ?self $current = null;

    /** Select the profile the Demo seeders should read from. */
    public static function use(self $profile): void
    {
        self::$current = $profile;
    }

    /**
     * The active profile, defaulting to Pakistan so any existing caller
     * that never opts in behaves exactly as it did before.
     */
    public static function current(): self
    {
        return self::$current ??= new PakProfile();
    }

    /** Reset to the default profile (test/reseed hygiene). */
    public static function reset(): void
    {
        self::$current = null;
    }

    // ── Locale data each profile must supply ─────────────────────────

    /** @return array<int,string> */
    abstract public function maleFirstNames(): array;

    /** @return array<int,string> */
    abstract public function femaleFirstNames(): array;

    /** @return array<int,string> */
    abstract public function surnames(): array;

    /**
     * Cities the families live in.
     *
     * @return array<int,array{name:string,province:string,postal:string}>
     */
    abstract public function cities(): array;

    /** @return array<string,int> blood group => relative weight */
    abstract public function bloodGroupWeights(): array;

    /** @return array<int,string> */
    abstract public function parentOccupations(): array;

    /** @return array<string,int> payment method => relative weight */
    abstract public function paymentMethods(): array;

    /** A synthetic phone number in this locale's format. */
    abstract public function phone(): string;

    /**
     * A synthetic street address, optionally in a named city.
     *
     * @return array{address:string,city:string}
     */
    abstract public function address(?string $cityName = null): array;

    /**
     * A synthetic guardian identity-document number, or null where the
     * locale has no school-appropriate equivalent.
     *
     * Deliberately nullable: US schools do not record Social Security
     * numbers against guardians, and generating synthetic SSN-shaped
     * values into a national-ID column would be actively wrong. Profiles
     * without a suitable document return null and the column stays empty.
     */
    abstract public function guardianDocumentNumber(): ?string;

    /**
     * Retail banks staff salaries are paid into.
     *
     * @return array<int,string>
     */
    abstract public function banks(): array;

    /** A synthetic staff bank account number in this locale's format. */
    abstract public function bankAccountNumber(): string;

    /** Email domain used for generated demo logins. */
    abstract public function emailDomain(): string;

    /**
     * The school's academic calendar.
     *
     * Returned oldest-first; exactly one entry carries is_current. A profile
     * may return an empty array, in which case the seeder leaves whatever the
     * base tenant seeder created alone and only normalises its name — that is
     * the behaviour every profile had before this method existed.
     *
     * Defining the calendar here matters because a school year is a locale
     * decision, not a global one: an August-to-June year and an April-to-March
     * year put "this term" in completely different months, and a syllabus plan
     * is meaningless if its week numbers are counted from the wrong start.
     *
     * @return list<array{name:string, start:string, end:string, is_current:bool}>
     */
    public function academicYears(): array
    {
        return [];
    }

    /**
     * Week-by-week term plans, keyed "Grade N|Subject".
     *
     * Empty by default: a profile that does not author a curriculum simply
     * has no syllabi, which is preferable to generating plausible-looking
     * topic titles from the subject name. Filler curriculum is worse than
     * none — it reads as real content until somebody opens it.
     *
     * @return array<string, array{title:string, description:string, topics:list<array{title:string, description:string, objective:string, match?:string}>}>
     */
    public function syllabusPlans(): array
    {
        return [];
    }

    /**
     * Canonical school identity.
     *
     * @return array{
     *     name:string, tagline:string, address:string, email:string,
     *     phone:string, whatsapp:string, facebook:string,
     *     twitter:string, instagram:string, youtube:string,
     *     city:string, website:string, admission_form_url:string,
     *     currency_code:string, currency_symbol:string, timezone:string
     * }
     */
    abstract public function school(): array;

    /**
     * The two standing leadership accounts (admin + head of school).
     *
     * These are seeded before the general roster because other seeders
     * reference them by the 'admin' / 'principal' labels.
     *
     * @return array{
     *     admin:array{name:string,designation:string,qualification:string,employee_id:string,salary:int},
     *     principal:array{name:string,designation:string,qualification:string,employee_id:string,salary:int}
     * }
     */
    abstract public function leadership(): array;

    /**
     * Job titles offered at this school, title => department name.
     * Departments must match those seeded by StaffSeeder.
     *
     * @return array<string,string>
     */
    abstract public function designations(): array;

    /**
     * Payroll components, in this locale's minor currency unit.
     *
     * @return array<int,array{0:string,1:string,2:string,3:int,4:bool}>
     *         [name, component_type, calculation_type, default_value, is_taxable]
     */
    abstract public function salaryComponents(): array;

    /**
     * The staff roster, one row per employee.
     *
     * @return array<int,array{0:?string,1:string,2:string,3:string,4:int,5:string,6?:string}>
     *         [authRole|null, designation, label, qualification, salaryMinorUnits, name, subject?]
     */
    abstract public function staffRoster(): array;

    /**
     * Public-website narrative copy.
     *
     * Keys map onto cms_settings columns; the JSON-shaped ones are encoded
     * by CmsContentSeeder rather than here so the profile stays plain data.
     *
     * @return array{
     *     about:string, vision:string, mission:string,
     *     principal_message:string,
     *     why_choose_us:array<int,array{title:string,icon:string,description:string}>,
     *     facilities:array<int,array{name:string,description:string}>,
     *     testimonials:array<int,array{name:string,relation:string,message:string}>,
     *     stats:array<string,int>,
     *     exam_highlights:array<int,array{exam:string,top_class:string,top_percent:float}>,
     *     admission_steps:array<int,array{title:string,description:string}>,
     *     hero_image:string, about_image:string, about_tagline:string,
     *     grade_range:string, founded_year:int, office_hours:string
     * }
     */
    abstract public function cms(): array;

    /**
     * Public website pages, in menu order.
     *
     * Each profile owns its own marketing copy: the page bodies reference
     * locale-specific things (curriculum framework, required enrolment
     * documents, grade nomenclature) that cannot be templated from a few
     * fragments without reading badly.
     *
     * @return array<int,array{title:string,slug:string,content:string,meta_title:string,meta_description:string,sort_order:int}>
     */
    abstract public function pages(): array;

    /**
     * Grade levels offered, level number => display name.
     *
     * Level numbers are the seeder's internal key (classIdByNumber), not
     * necessarily a year: the US profile uses 0 for Kindergarten so the
     * remaining keys line up with Grade 1..12.
     *
     * @return array<int,string>
     */
    abstract public function gradeLevels(): array;

    /**
     * Section letters for a given grade level. Lower grades typically run
     * two sections, upper grades one.
     *
     * @return array<int,string>
     */
    abstract public function sectionsForLevel(int $level): array;

    /**
     * Subjects taught at each grade level.
     *
     * @return array<int,list<string>>
     */
    abstract public function classSubjects(): array;

    /**
     * Subject name => teacher label in StaffSeeder::$userIdByLabel, so each
     * subject resolves to a real member of staffRoster().
     *
     * @return array<string,string>
     */
    abstract public function subjectTeacherLabels(): array;

    /**
     * How many students to enrol at each grade level.
     *
     * @return array<int,int>
     */
    abstract public function classSizes(): array;

    /** Prefix for generated certificate numbers, e.g. 'AQM' -> AQM-CERT-2026-001. */
    /**
     * Subject catalogue: [display name, short code, hex colour].
     *
     * Must be a superset of every subject named in classSubjects(),
     * otherwise class_subjects rows silently fail to link.
     *
     * @return array<int,array{0:string,1:string,2:string}>
     */
    abstract public function subjects(): array;

    /**
     * Homepage slider slides: [heading, subheading, cta label, cta href].
     *
     * @return array<int,array{0:string,1:string,2:string,3:string}>
     */
    abstract public function sliders(): array;

    /**
     * Grading scale: [grade, min percent, max percent, gpa, remark].
     *
     * @return array<int,array{0:string,1:int,2:int,3:float,4:string}>
     */
    abstract public function gradeRules(): array;

    /**
     * Monthly recurring operating expenses.
     *
     * Amounts are in this locale's MAJOR currency unit (the seeder multiplies
     * by 100). Line-item names are locale-specific: a US school does not have
     * a gas bill from Sui Northern or fiber from PTCL.
     *
     * @return array<int,array{0:string,1:string,2:int,3:int}>
     *         [category, title, min amount, max amount]
     */
    abstract public function recurringExpenses(): array;

    /**
     * One-off / periodic expenses with a fixed date.
     *
     * @return array<int,array{0:string,1:string,2:int,3:int,4:string}>
     *         [category, title, min amount, max amount, date]
     */
    abstract public function periodicExpenses(): array;

    /** Prefix for generated certificate numbers, e.g. 'AQM' -> AQM-CERT-2026-001. */
    /**
     * Graduated alumni, used as FK targets for completion certificates.
     *
     * @return array<int,array{0:string,1:string,2:string,3:int}>
     *         [first name, last name, gender, graduation year]
     */
    /**
     * Fee groups: name => description.
     *
     * @return array<string,string>
     */
    abstract public function feeGroups(): array;

    /**
     * Fee types: [name, group name, is recurring].
     *
     * @return array<int,array{0:string,1:string,2:bool}>
     */
    abstract public function feeTypes(): array;

    /**
     * Rate card, fee type name => tier => amount in MAJOR currency units.
     * A zero means the fee does not apply at that tier.
     *
     * @return array<string,array<string,int>>
     */
    abstract public function feeRates(): array;

    /**
     * Which rate tier a grade level falls into. Must return a key present in
     * every feeRates() entry.
     */
    abstract public function feeTierFor(int $level): string;

    /**
     * Semantic fee roles mapped onto this profile's fee type NAMES.
     *
     * FeesSeeder needs to know which type is the recurring tuition line, which
     * is the one-time joining charge, and which is the opt-in transport line.
     * It used to hardcode the Pakistani names ('Tuition Monthly',
     * 'Admission Fee', 'Transport'), so renaming the types for another market
     * broke the lookups with "Undefined array key". Roles decouple the two.
     *
     * @return array{tuition:string,admission:string,transport:string,recurring:array<int,string>}
     */
    abstract public function feeRoles(): array;

    /**
     * Lecture catalogue for the study_materials table.
     *
     * Each entry: [grade level, subject name, lecture title, YouTube video id,
     * lesson notes].
     *
     * The video id is a LINK ONLY — nothing is downloaded or re-hosted, which
     * is both the lawful way to use someone else's video and how a real school
     * shares one. Every id in the US catalogue was checked against YouTube's
     * oEmbed endpoint before being committed, so there are no dead embeds in a
     * live demo.
     *
     * The notes are the school's own words, written to be a faithful summary of
     * the topic rather than a transcript of the video. They matter more than
     * they look: MyLectures feeds them to the AI tutor as its grounding, and
     * the tutor is instructed to defer to the teacher when a question falls
     * outside them. Thin notes therefore produce a cautious, unhelpful tutor.
     *
     * @return array<int,array{0:int,1:string,2:string,3:string,4:string}>
     */
    abstract public function lectures(): array;

    /**
     * Online exam bank, for the AI-graded assessment feature.
     *
     * Each exam: level, subject, name, state, duration in minutes, and its
     * questions. State is one of:
     *
     *   graded   — window closed, attempts submitted and already AI-graded.
     *              This is what you show a customer who wants to see the output.
     *   open     — window open right now, so an exam can be sat live during the
     *              demo and graded in front of them.
     *   upcoming — window in the future, so the schedule is not empty.
     *
     * Each question: [type, text, options|null, correct answer|null, marks,
     * explanation|null]. The type drives grading, per ExamQuestion:
     *   mcq / true_false with a correct answer  -> auto-graded
     *   essay, or short_answer with NO correct answer -> routed to AI
     * A question bank with no ungraded types would never exercise the AI path.
     *
     * @return array<int,array{level:int,subject:string,name:string,state:string,duration:int,questions:array<int,array{0:string,1:string,2:?array,3:?string,4:int,5:?string}>}>
     */
    abstract public function onlineExams(): array;

    /**
     * Per-lecture practice material, keyed by the lecture's video id.
     *
     *   [videoId => ['quiz' => [...], 'flashcards' => [[front, back], ...]]]
     *
     * Quiz entries are [type, question, options|null, correctAnswer, explanation]
     * and are stored in exam_questions against study_material_id, so a teacher
     * can promote one into a real exam. Every practice question carries a
     * correct answer and an explanation — unlike the online exam bank, which
     * deliberately includes open-response items for the AI grader. Practice is
     * self-marking, so an item with nothing to check against would just stall.
     *
     * Keyed by video id rather than array position so lectures() can be
     * reordered or extended without silently repointing the questions.
     *
     * @return array<string,array{quiz:array<int,array{0:string,1:string,2:?array,3:string,4:string}>,flashcards:array<int,array{0:string,1:string}>}>
     */
    abstract public function lecturePractice(): array;

    abstract public function alumni(): array;

    /**
     * The final grade level students graduate from — 10 for a school ending
     * at Class 10, 12 for a US K-12 school. Alumni are attached to this
     * level's first section.
     */
    abstract public function graduatingLevel(): int;

    /** Prefix for generated certificate numbers, e.g. 'AQM' -> AQM-CERT-2026-001. */
    abstract public function certificatePrefix(): string;

    // ── Shared, locale-independent helpers ───────────────────────────

    /**
     * Pick one element with a uniform random index.
     *
     * @template T
     * @param  array<int,T>  $list
     * @return T
     */
    public function pick(array $list)
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    /**
     * Weighted pick over a name => weight map.
     *
     * @param  array<string,int>  $weights
     */
    public function weightedPick(array $weights): string
    {
        $total = array_sum($weights);
        $r = mt_rand(1, $total);
        $cum = 0;
        foreach ($weights as $key => $weight) {
            $cum += $weight;
            if ($r <= $cum) {
                return (string) $key;
            }
        }

        return (string) array_key_first($weights);
    }

    /** Slugify a person's name into a deterministic email handle. */
    public function emailHandle(string $first, string $last): string
    {
        $base = Str::lower(Str::ascii($first . '.' . $last));

        return preg_replace('/[^a-z0-9.]/', '', $base) ?: 'user';
    }

    /**
     * Email address on this profile's domain, suffixed on collision.
     *
     * @param  array<string,bool>  &$issued
     */
    public function uniqueEmail(string $handle, array &$issued, ?string $domain = null): string
    {
        $domain ??= $this->emailDomain();
        $candidate = $handle . '@' . $domain;
        $i = 1;
        while (isset($issued[$candidate])) {
            $i++;
            $candidate = $handle . $i . '@' . $domain;
        }
        $issued[$candidate] = true;

        return $candidate;
    }

    /**
     * Deterministic demo password: Demo2026@<6 hex of sha1(role+login+key)>.
     *
     * Stable across reseeds so a printed credential sheet stays valid.
     */
    public function demoPassword(string $roleKey, string $login, string $appKey): string
    {
        return 'Demo2026@' . substr(sha1($roleKey . $login . $appKey), 0, 6);
    }

    /** Map an auth role name to the password roleKey. */
    public function roleKeyFor(string $authRole): string
    {
        return match ($authRole) {
            'SCHOOL_ADMIN' => 'admin',
            'INSTITUTE_HEAD' => 'principal',
            'REGISTRAR' => 'vice-principal',
            'ACCOUNTANT' => 'accountant',
            'TEACHER' => 'teacher',
            'PARENT' => 'parent',
            'STUDENT' => 'student',
            default => 'staff',
        };
    }

    /** Convenience: a full name for the given gender. */
    public function fullName(string $gender): string
    {
        $first = $gender === 'female'
            ? $this->pick($this->femaleFirstNames())
            : $this->pick($this->maleFirstNames());

        return $first . ' ' . $this->pick($this->surnames());
    }
}
