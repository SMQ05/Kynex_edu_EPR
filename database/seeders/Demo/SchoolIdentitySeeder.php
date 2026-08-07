<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\Demo\Support\DemoProfile;

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
    public const SCHOOL_WHATSAPP = '+923001234567';
    public const SCHOOL_FACEBOOK = 'https://facebook.com/aqmpublicschool';

    public function run(): void
    {
        $tenant = $this->currentTenant();

        $this->renameCentralTenant($tenant);
        $this->consolidateCampuses();
        $this->renameCmsSettings();
        $this->renameAcademicYear();
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
            'whatsapp' => self::SCHOOL_WHATSAPP,
            'facebook_url' => self::SCHOOL_FACEBOOK,
            'twitter_url' => 'https://x.com/aqmpublicschool',
            'instagram_url' => 'https://instagram.com/aqmpublicschool',
            'youtube_url' => 'https://youtube.com/@aqmpublicschool',
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
}
