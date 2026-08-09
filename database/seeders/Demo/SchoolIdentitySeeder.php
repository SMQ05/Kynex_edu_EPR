<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\DemoProfile;
use App\Support\SchoolSettings;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Facades\Tenancy;

/**
 * a. SchoolIdentitySeeder
 *
 * Renames the school from "Haji Qamar public school" → "AQM Public School"
 * everywhere (central tenants row, tenant cms_settings, certificate/ID-card
 * template variables) and consolidates the two campuses by reassigning
 * any FK that points at the secondary campus to the main campus, then
 * soft-deleting the secondary.
 *
 * Runs FIRST in DemoTenantSeeder so all later seeders see the canonical
 * school name + a single live campus.
 */
class SchoolIdentitySeeder extends Seeder
{
    /**
     * School identity now comes from the active DemoProfile so the same
     * seeder can stand up either the Pakistani AQM school or the US
     * Lincoln Heights school. These accessors replace what used to be
     * const SCHOOL_NAME / _TAGLINE / _ADDRESS / _PHONE / _EMAIL.
     */
    public static function schoolName(): string
    {
        return DemoProfile::current()->school()['name'];
    }

    public static function schoolTagline(): string
    {
        return DemoProfile::current()->school()['tagline'];
    }

    public static function schoolAddress(): string
    {
        return DemoProfile::current()->school()['address'];
    }

    public static function schoolPhone(): string
    {
        return DemoProfile::current()->school()['phone'];
    }

    public static function schoolEmail(): string
    {
        return DemoProfile::current()->school()['email'];
    }

    public function run(): void
    {
        $tenant = $this->currentTenant();

        $this->renameCentralTenant($tenant);
        $this->consolidateCampuses();
        $this->renameCmsSettings();
        $this->seedSchoolSettings();
        $this->renameAcademicYear();
    }

    /**
     * Populate the school_settings key/value store.
     *
     * This table was left completely empty by the seeder, so every
     * SchoolSettings::get() call anywhere in the app silently fell back to its
     * inline default — the student ID card showed the framework's app.name
     * instead of the school, and currency came out as whatever each caller
     * happened to pass as a fallback. Filling it here means one source of
     * truth per tenant, taken from the active profile.
     */
    protected function seedSchoolSettings(): void
    {
        $profile = DemoProfile::current();
        $school = $profile->school();
        $cms = $profile->cms();

        SchoolSettings::setMany([
            'school.name' => $school['name'],
            'school.tagline' => $school['tagline'],
            'school.address' => $school['address'],
            'school.city' => $school['city'],
            'school.email' => $school['email'],
            'school.phone' => $school['phone'],
            'school.website' => $school['website'],
            'school.founded_year' => (string) $cms['founded_year'],
            'school.grade_range' => $cms['grade_range'],
            'school.office_hours' => $cms['office_hours'],
        ], group: 'school');

        SchoolSettings::setMany([
            'currency.code' => $school['currency_code'],
            'currency.symbol' => $school['currency_symbol'],
        ], group: 'currency');

        SchoolSettings::setMany([
            'locale.timezone' => $school['timezone'],
        ], group: 'locale');

        $this->command?->line('  ✓ school_settings seeded (school / currency / locale)');
    }

    /**
     * stancl/tenancy puts us inside `tenant->run(...)` for sub-seeders, so
     * tenancy()->tenant returns the haji-qamar tenant. We need its id to
     * write back to the central tenants row.
     */
    protected function currentTenant(): Tenant
    {
        $current = tenancy()->tenant;
        if (! $current instanceof Tenant) {
            throw new \RuntimeException(
                'SchoolIdentitySeeder must run inside a tenant context (tenants:run db:seed).'
            );
        }
        return $current;
    }

    /**
     * Update tenants.school_name in the CENTRAL DB. We temporarily end
     * tenancy so the write goes to the central connection, then restore.
     */
    protected function renameCentralTenant(Tenant $tenant): void
    {
        Tenancy::central(function () use ($tenant) {
            DB::connection(config('tenancy.database.central_connection'))
                ->table('tenants')
                ->where('id', $tenant->id)
                ->update([
                    'school_name' => self::schoolName(),
                    'updated_at' => now(),
                ]);
            $this->command?->line("  ✓ Central tenants.school_name → '" . self::schoolName() . "'");
        });
    }

    /**
     * Reassign any *.campus_id FK pointing at the secondary campus to the
     * main campus, then soft-delete the secondary. Reads campus rows by
     * is_main_campus so the seeder is idempotent and id-agnostic.
     */
    protected function consolidateCampuses(): void
    {
        $main = DB::table('campuses')
            ->where('is_main_campus', true)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->first();

        // A tenant that was never hand-prepared (any fresh demo tenant) has
        // either no campus at all or one that nobody flagged as main. The old
        // code just warned and returned, which left StaffSeeder's
        // $mainCampusId empty and pushed a blank campus_id into every staff,
        // student and class row. Promote or create instead, so the suite
        // stands up on a clean tenant. AQM already has a main campus, so this
        // branch never fires there.
        if (! $main) {
            $existing = DB::table('campuses')
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->first();

            if ($existing) {
                DB::table('campuses')->where('id', $existing->id)
                    ->update(['is_main_campus' => true, 'updated_at' => now()]);
                $this->command?->line('  ✓ Promoted existing campus to main campus.');
            } else {
                $school = DemoProfile::current()->school();
                $newId = (string) Str::ulid();
                DB::table('campuses')->insert([
                    'id' => $newId,
                    'name' => $school['name'] . ' — Main Campus',
                    'address' => $school['address'],
                    'city' => $school['city'],
                    'phone' => $school['phone'],
                    'email' => $school['email'],
                    'is_main_campus' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command?->line('  ✓ Created main campus (tenant had none).');
            }

            $main = DB::table('campuses')
                ->where('is_main_campus', true)
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->first();
        }

        $secondaries = DB::table('campuses')
            ->where('is_main_campus', false)
            ->whereNull('deleted_at')
            ->get();

        if ($secondaries->isEmpty()) {
            $this->command?->line('  ✓ No secondary campuses to consolidate.');
            $this->renameMainCampus($main->id);
            return;
        }

        // Discover every table.column where column = 'campus_id' (or known FK names).
        $rows = DB::select("
            SELECT table_name, column_name
            FROM information_schema.columns
            WHERE table_schema = current_schema()
              AND column_name = 'campus_id'
        ");

        foreach ($secondaries as $sec) {
            $reassigned = 0;
            foreach ($rows as $row) {
                if ($row->table_name === 'campuses') {
                    continue;
                }
                $count = DB::table($row->table_name)
                    ->where($row->column_name, $sec->id)
                    ->update([$row->column_name => $main->id]);
                $reassigned += $count;
                if ($count > 0) {
                    $this->command?->line(
                        "    · reassigned {$count} {$row->table_name}.{$row->column_name} → main campus"
                    );
                }
            }

            DB::table('campuses')
                ->where('id', $sec->id)
                ->update([
                    'is_active' => false,
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
            $this->command?->line(
                "  ✓ Soft-deleted secondary campus '{$sec->name}' (reassigned {$reassigned} FK rows)"
            );
        }

        $this->renameMainCampus($main->id);
    }

    protected function renameMainCampus(string $mainId): void
    {
        DB::table('campuses')
            ->where('id', $mainId)
            ->update([
                'name' => self::schoolName() . ' — Main Campus',
                'address' => self::schoolAddress(),
                'city' => DemoProfile::current()->school()['city'],
                'phone' => self::schoolPhone(),
                'email' => self::schoolEmail(),
                'is_active' => true,
                'updated_at' => now(),
            ]);
        $this->command?->line('  ✓ Main campus renamed and contact details updated');
    }

    protected function renameCmsSettings(): void
    {
        $existing = DB::table('cms_settings')->orderBy('created_at')->first();

        $payload = [
            'school_name' => self::schoolName(),
            'tagline' => self::schoolTagline(),
            'address' => self::schoolAddress(),
            'phone' => self::schoolPhone(),
            'email' => self::schoolEmail(),
            'whatsapp' => DemoProfile::current()->school()['whatsapp'],
            'facebook_url' => DemoProfile::current()->school()['facebook'],
            'twitter_url' => DemoProfile::current()->school()['twitter'],
            'instagram_url' => DemoProfile::current()->school()['instagram'],
            'youtube_url' => DemoProfile::current()->school()['youtube'],
            'primary_color' => '#1a56db',
            'admission_open' => true,
            'admission_form_url' => DemoProfile::current()->school()['admission_form_url'],
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('cms_settings')->where('id', $existing->id)->update($payload);
            $this->command?->line("  ✓ cms_settings updated (school_name='" . self::schoolName() . "')");
        } else {
            $payload['id'] = (string) \Illuminate\Support\Str::ulid();
            $payload['created_at'] = now();
            DB::table('cms_settings')->insert($payload);
            $this->command?->line('  ✓ cms_settings inserted (singleton)');
        }
    }

    protected function renameAcademicYear(): void
    {
        $years = DemoProfile::current()->academicYears();

        if ($years !== []) {
            $this->syncAcademicYears($years);

            return;
        }

        $current = DB::table('academic_years')
            ->where('is_current', true)
            ->orderBy('created_at')
            ->first();

        if (! $current) {
            return;
        }

        $startYear = Carbon::parse($current->start_date)->year;
        $endYear = Carbon::parse($current->end_date)->year;
        $name = "{$startYear}-{$endYear}";

        DB::table('academic_years')
            ->where('id', $current->id)
            ->update([
                'name' => $name,
                'updated_at' => now(),
            ]);
        $this->command?->line("  ✓ academic_years renamed → '{$name}'");
    }

    /**
     * Put the profile's calendar in place.
     *
     * The row the base seeder created is reused for the current year rather
     * than deleted and replaced: classes, fee structures and exams already
     * point at its id by foreign key, and swapping the id would orphan all of
     * them. Earlier years are inserted alongside it so the school has history.
     *
     * @param  list<array{name:string, start:string, end:string, is_current:bool}>  $years
     */
    protected function syncAcademicYears(array $years): void
    {
        $existing = DB::table('academic_years')
            ->where('is_current', true)
            ->orderBy('created_at')
            ->first()
            ?? DB::table('academic_years')->orderBy('created_at')->first();

        $names = [];

        foreach ($years as $year) {
            $row = [
                'name' => $year['name'],
                'start_date' => $year['start'],
                'end_date' => $year['end'],
                'is_current' => $year['is_current'],
                'updated_at' => now(),
            ];

            $match = DB::table('academic_years')->where('name', $year['name'])->first();

            if ($match) {
                DB::table('academic_years')->where('id', $match->id)->update($row);
            } elseif ($year['is_current'] && $existing) {
                DB::table('academic_years')->where('id', $existing->id)->update($row);
            } else {
                DB::table('academic_years')->insert($row + [
                    'id' => (string) \Illuminate\Support\Str::ulid(),
                    'created_at' => now(),
                ]);
            }

            $names[] = $year['name'] . ($year['is_current'] ? ' (current)' : '');
        }

        // Anything the base seeder left behind that the profile does not
        // define must not keep claiming to be the current year.
        DB::table('academic_years')
            ->whereNotIn('name', array_column($years, 'name'))
            ->update(['is_current' => false, 'updated_at' => now()]);

        $this->command?->line('  ✓ academic_years synced (' . implode(', ', $names) . ')');
    }
}
