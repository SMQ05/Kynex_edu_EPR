<?php
/**
 * AQM credentials writer — runs inside kynexedu-app container.
 *
 * Reads APP_KEY, derives demo passwords for sample users, writes the
 * credentials file to /tmp/aqm-demo-credentials-<timestamp>.txt.
 * Sample passwords are written for one user per role; the rest of
 * each role's user list is included with NAME + EMAIL only and the
 * formula. APP_KEY is NOT written to the file.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

use App\Models\SchoolUser;
use App\Models\Tenant;

$tenantId = 'haji-qamar-public-school-BEb3S9';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = Tenant::find($tenantId);
if (! $tenant) {
    fwrite(STDERR, "Tenant not found: {$tenantId}\n");
    exit(1);
}
$appKey = config('app.key');

function demoPassword(string $roleKey, string $login, string $appKey): string
{
    return 'Demo2026@' . substr(sha1($roleKey . $login . $appKey), 0, 6);
}

function roleKeyFor(string $authRole): string
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

tenancy()->initialize($tenant);

$ts = date('Ymd-His');
$out = "/tmp/aqm-demo-credentials-{$ts}.txt";
$fh = fopen($out, 'w');

$lines = [];

$lines[] = '=== AQM Public School Demo Credentials ===';
$lines[] = 'Generated: ' . date('Y-m-d H:i:s T');
$lines[] = 'Tenant: haji-qamar-public-school-BEb3S9';
$lines[] = 'Domain: https://aqmdigital.com';
$lines[] = '';
$lines[] = 'Login URLs:';
$lines[] = '  School portal (all roles)  → https://aqmdigital.com/login';
$lines[] = '    - Lands at /admin for: SCHOOL_ADMIN, INSTITUTE_HEAD, REGISTRAR, ACCOUNTANT, TEACHER, STUDENT';
$lines[] = '    - Lands at /admin for: PARENT also (school portal does not differentiate);';
$lines[] = '      after authenticating via /login, navigate to /parent for the parent panel.';
$lines[] = '  Parent panel              → https://aqmdigital.com/parent (Filament Livewire)';
$lines[] = '  School-admin panel        → https://aqmdigital.com/admin';
$lines[] = '';
$lines[] = 'Password formula (per spec):';
$lines[] = "  Demo2026@<first 6 hex chars of sha1(\$role . \$email . APP_KEY)>";
$lines[] = '';
$lines[] = '  $role values: admin | principal | vice-principal | accountant | teacher | staff | parent | student';
$lines[] = '  APP_KEY      : read from /var/www/kynexedu/.env.production (do NOT paste here).';
$lines[] = '';
$lines[] = 'Note: gatekeeper and driver have NO portal login (no auth role assigned).';
$lines[] = '';

// School admin
$lines[] = '=== School Admin (SCHOOL_ADMIN) ===';
$user = SchoolUser::where('email', 'admin@aqmdigital.com')->first();
$pw = demoPassword('admin', $user->email, $appKey);
$lines[] = "  Name    : {$user->name}";
$lines[] = "  Email   : {$user->email}";
$lines[] = "  Password: {$pw}";
$lines[] = "  URL     : https://aqmdigital.com/admin";
$lines[] = '';

// Principal
$lines[] = '=== Principal / Head (INSTITUTE_HEAD) ===';
$user = SchoolUser::where('email', 'principal@aqmdigital.com')->first();
$pw = demoPassword('principal', $user->email, $appKey);
$lines[] = "  Name    : {$user->name}";
$lines[] = "  Email   : {$user->email}";
$lines[] = "  Password: {$pw}";
$lines[] = "  URL     : https://aqmdigital.com/admin";
$lines[] = '';

// Vice Principal
$lines[] = '=== Vice Principal (REGISTRAR auth role, "Vice Principal" designation) ===';
$user = SchoolUser::where('active_role', 'REGISTRAR')->first();
$pw = demoPassword('vice-principal', $user->email, $appKey);
$lines[] = "  Name    : {$user->name}";
$lines[] = "  Email   : {$user->email}";
$lines[] = "  Password: {$pw}";
$lines[] = "  URL     : https://aqmdigital.com/admin";
$lines[] = '';

// Accountant
$lines[] = '=== Accountant (ACCOUNTANT) ===';
$user = SchoolUser::where('active_role', 'ACCOUNTANT')->first();
$pw = demoPassword('accountant', $user->email, $appKey);
$lines[] = "  Name    : {$user->name}";
$lines[] = "  Email   : {$user->email}";
$lines[] = "  Password: {$pw}";
$lines[] = "  URL     : https://aqmdigital.com/admin";
$lines[] = '';

// Teachers — sample 3 with passwords, full list of 10
$teachers = SchoolUser::where('active_role', 'TEACHER')->orderBy('name')->get();
$lines[] = "=== Teachers ({$teachers->count()}) — sample 3 with passwords below, full list of 10 follows ===";
foreach ($teachers->take(3) as $t) {
    $pw = demoPassword('teacher', $t->email, $appKey);
    $lines[] = "  {$t->name} | {$t->email} | {$pw}";
}
$lines[] = '';
$lines[] = "  All other teacher passwords use the formula:";
$lines[] = "    Demo2026@<first 6 chars of sha1('teacher' + email + APP_KEY)>";
$lines[] = '';
$lines[] = '  Full teacher list:';
foreach ($teachers as $t) {
    $sp = DB::table('staff_profiles')
        ->join('designations', 'designations.id', '=', 'staff_profiles.designation_id')
        ->where('staff_profiles.school_user_id', $t->id)
        ->value('designations.name');
    $lines[] = sprintf('    %-25s %-35s designation=%s', $t->name, $t->email, $sp ?? '?');
}
$lines[] = '';

// Support staff (LIBRARIAN, ATTENDANCE_CLERK, plus no-role staff for completeness)
$support = SchoolUser::whereIn('active_role', ['LIBRARIAN', 'ATTENDANCE_CLERK'])->get();
$lines[] = '=== Support Staff (with portal access) ===';
foreach ($support as $u) {
    $pw = demoPassword('staff', $u->email, $appKey);
    $lines[] = "  {$u->name} | {$u->email} | active_role={$u->active_role} | {$pw}";
}

// Non-portal staff (gatekeeper, driver)
$nonPortal = DB::table('staff_profiles')
    ->join('school_users', 'school_users.id', '=', 'staff_profiles.school_user_id')
    ->join('designations', 'designations.id', '=', 'staff_profiles.designation_id')
    ->whereIn('designations.name', ['Gatekeeper', 'Driver'])
    ->select('school_users.name', 'school_users.email', 'designations.name as designation')
    ->get();
$lines[] = '';
$lines[] = '  Non-portal staff (no auth role; included for HR records only):';
foreach ($nonPortal as $u) {
    $lines[] = "    {$u->name} | {$u->email} | designation={$u->designation} (NO LOGIN)";
}
$lines[] = '';

// Parents — sample 5 + full list (~75)
$parents = SchoolUser::where('active_role', 'PARENT')->orderBy('name')->get();
$lines[] = "=== Parents ({$parents->count()}) — sample 5 with passwords below ===";
foreach ($parents->take(5) as $p) {
    $pw = demoPassword('parent', $p->email, $appKey);
    $lines[] = "  {$p->name} | {$p->email} | {$pw}";
}
$lines[] = '';
$lines[] = '  All other parent passwords:';
$lines[] = "    Demo2026@<first 6 chars of sha1('parent' + email + APP_KEY)>";
$lines[] = '';
$lines[] = '  Full parent list:';
foreach ($parents as $p) {
    $lines[] = "    {$p->name} | {$p->email}";
}
$lines[] = '';

// Students — sample 5 + full list (100)
$students = SchoolUser::where('active_role', 'STUDENT')->orderBy('email')->get();
$lines[] = "=== Students ({$students->count()}) — sample 5 with passwords below ===";
foreach ($students->take(5) as $s) {
    $pw = demoPassword('student', $s->email, $appKey);
    $studentRow = DB::table('students')->where('school_user_id', $s->id)->first();
    $admission = $studentRow->admission_number ?? '?';
    $lines[] = "  {$s->name} | {$s->email} | admission={$admission} | {$pw}";
}
$lines[] = '';
$lines[] = '  All other student passwords:';
$lines[] = "    Demo2026@<first 6 chars of sha1('student' + email + APP_KEY)>";
$lines[] = '  (login_id == email — synthesized from admission number, e.g. firstname.aqm2025NNN@aqmdigital.com)';
$lines[] = '';
$lines[] = '  Full student list:';
foreach ($students as $s) {
    $row = DB::table('students')->where('school_user_id', $s->id)->first();
    if (! $row) continue;
    $cls = DB::table('classes')->where('id', $row->class_id)->value('name');
    $lines[] = sprintf('    %-30s %-45s class=%s admission=%s', $s->name, $s->email, $cls, $row->admission_number);
}
$lines[] = '';

$lines[] = '=== APP_KEY ===';
$lines[] = '(Operator: read from /var/www/kynexedu/.env.production line APP_KEY=. Do NOT include here.)';
$lines[] = '';
$lines[] = '=== End of file. Delete after demo. ===';

fwrite($fh, implode("\n", $lines) . "\n");
fclose($fh);

tenancy()->end();

chmod($out, 0600);
echo "Wrote credentials file: {$out}\n";
echo "Lines: " . count($lines) . "\n";
echo "Size: " . filesize($out) . " bytes\n";
