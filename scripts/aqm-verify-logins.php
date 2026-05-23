<?php
/**
 * AQM demo login verification — runs inside kynexedu-app container.
 *
 * Verifies HTTP login for 6 roles by:
 *   1. Reading APP_KEY from the live config so passwords match what the
 *      seeder hashed.
 *   2. Computing each role's deterministic demo password.
 *   3. GET /login to grab a fresh CSRF token + session cookie.
 *   4. POST /login (or /parent/login for parent) with credentials.
 *   5. Asserting we get a 302 to a tenant-side dashboard URL.
 *
 * Hits https://aqmdigital.com via the host's network. Set
 * VERIFY_BASE_URL to override (e.g. for in-container loopback testing).
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

use App\Models\SchoolUser;
use App\Models\Tenant;

$appBase = getenv('VERIFY_BASE_URL') ?: 'https://aqmdigital.com';
$tenantId = 'haji-qamar-public-school-BEb3S9';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = Tenant::find($tenantId);
if (! $tenant) {
    fwrite(STDERR, "Tenant not found: {$tenantId}\n");
    exit(1);
}

$appKey = config('app.key');

// Re-derive the demo password the same way the seeder did.
function demoPassword(string $roleKey, string $login, string $appKey): string
{
    return 'Demo2026@' . substr(sha1($roleKey . $login . $appKey), 0, 6);
}

// Initialize tenancy so we can fetch the user records.
tenancy()->initialize($tenant);

$cases = [];

// 1. school admin
$cases[] = [
    'role' => 'school_admin',
    'role_key' => 'admin',
    'user' => SchoolUser::where('email', 'admin@aqmdigital.com')->first(),
    'login_path' => '/login',
    'expect_redirect_contains' => '/admin',
];

// 2. principal / head
$cases[] = [
    'role' => 'principal',
    'role_key' => 'principal',
    'user' => SchoolUser::where('email', 'principal@aqmdigital.com')->first(),
    'login_path' => '/login',
    'expect_redirect_contains' => '/admin',
];

// 3. accountant
$cases[] = [
    'role' => 'accountant',
    'role_key' => 'accountant',
    'user' => SchoolUser::where('active_role', 'ACCOUNTANT')->first(),
    'login_path' => '/login',
    'expect_redirect_contains' => '/admin',
];

// 4. teacher
$cases[] = [
    'role' => 'teacher',
    'role_key' => 'teacher',
    'user' => SchoolUser::where('active_role', 'TEACHER')->first(),
    'login_path' => '/login',
    'expect_redirect_contains' => '/admin',
];

// 5. parent — Filament's /parent/login is a Livewire component (HTTP 405
// for plain POSTs). Use /login: SchoolPortalController accepts any role and
// returns 302 → /admin. Parent panel access is checked separately below
// via GET /parent with the session cookie.
$cases[] = [
    'role' => 'parent',
    'role_key' => 'parent',
    'user' => SchoolUser::where('active_role', 'PARENT')->first(),
    'login_path' => '/login',
    'expect_redirect_contains' => '/admin',
    'parent_panel_check' => true,
];

// 6. student — uses /login too; lands at /admin (per plan §7.1)
$cases[] = [
    'role' => 'student',
    'role_key' => 'student',
    'user' => SchoolUser::where('active_role', 'STUDENT')->first(),
    'login_path' => '/login',
    'expect_redirect_contains' => '/admin',
];

tenancy()->end();

echo str_repeat('=', 80) . "\n";
echo "AQM Demo Login Verification\n";
echo "Base URL: {$appBase}\n";
echo str_repeat('=', 80) . "\n\n";

$rows = [];
foreach ($cases as $c) {
    if (! $c['user']) {
        $rows[] = [$c['role'], 'n/a', 'FAIL', 'no user found'];
        continue;
    }
    $email = $c['user']->email;
    $password = demoPassword($c['role_key'], $email, $appKey);

    [$ok, $note] = httpLoginCheck($appBase, $c['login_path'], $email, $password, $c['expect_redirect_contains']);
    $rows[] = [$c['role'], $email, $ok ? 'PASS' : 'FAIL', $note];

    if (! empty($c['parent_panel_check']) && $ok) {
        // The cookie jar from the login has been wiped, so do a fresh
        // login + GET /parent flow.
        [$panelOk, $panelNote] = parentPanelAccessCheck($appBase, $email, $password);
        $rows[] = ['parent (panel)', $email, $panelOk ? 'PASS' : 'WARN', $panelNote];
    }
}

printf("%-15s %-40s %-6s %s\n", 'role', 'email', 'status', 'detail');
printf("%-15s %-40s %-6s %s\n", str_repeat('-', 15), str_repeat('-', 40), str_repeat('-', 6), str_repeat('-', 30));
foreach ($rows as $r) {
    printf("%-15s %-40s %-6s %s\n", $r[0], $r[1], $r[2], $r[3]);
}

$pass = count(array_filter($rows, fn($r) => $r[2] === 'PASS'));
$total = count($rows);
echo "\n{$pass}/{$total} roles authenticated successfully.\n";
exit($pass === $total ? 0 : 2);

/**
 * @return array{0:bool,1:string}
 */
function httpLoginCheck(string $base, string $path, string $email, string $password, string $expectContains): array
{
    $jar = tempnam(sys_get_temp_dir(), 'aqm-jar-');
    $cleanup = function () use ($jar) {
        @unlink($jar);
    };

    // 1. GET the login page to grab CSRF + session cookie.
    $loginUrl = $base . $path;
    $ch = curl_init($loginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'aqm-verify/1.0',
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode < 200 || $httpCode >= 400) {
        $cleanup();
        return [false, "GET {$path} returned HTTP {$httpCode}"];
    }

    // Extract CSRF: prefer <meta name="csrf-token"> then any name="_token" hidden input.
    $token = null;
    if (preg_match('/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"/i', (string) $body, $m)) {
        $token = $m[1];
    } elseif (preg_match('/name="_token"\s+value="([^"]+)"/i', (string) $body, $m)) {
        $token = $m[1];
    }
    if (! $token) {
        $cleanup();
        return [false, 'CSRF token not found on login page'];
    }

    // The XSRF-TOKEN cookie is a URL-encoded encrypted JWT-ish string.
    // Read it from the jar so we can mirror it in X-XSRF-TOKEN if needed.
    // For the school portal /login, the form submission sends _token in the body, and CSRF middleware accepts that.
    $postFields = http_build_query([
        '_token' => $token,
        'email' => $email,
        'password' => $password,
    ]);

    $ch = curl_init($loginUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, // we WANT to see the 302
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => 'aqm-verify/1.0',
    ]);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $code = $info['http_code'] ?? 0;
    $headerSize = $info['header_size'] ?? 0;
    $headerBlock = substr((string) $resp, 0, $headerSize);
    $location = '';
    if (preg_match('/^Location:\s*(\S+)/im', $headerBlock, $m)) {
        $location = trim($m[1]);
    }

    $sessionCookieSet = preg_match('/Set-Cookie:[^;]*kynexedu-erp-session=/i', $headerBlock);

    $cleanup();

    if ($code !== 302) {
        return [false, "POST {$path} returned HTTP {$code} (expected 302)"];
    }
    if ($expectContains !== '' && stripos($location, $expectContains) === false) {
        return [false, "302 redirect → '{$location}' does not contain '{$expectContains}'"];
    }
    return [true, "302 → {$location}" . ($sessionCookieSet ? ' (session cookie set)' : '')];
}

/**
 * Login as parent via /login, then GET /parent to verify the parent
 * panel is reachable with the session cookie.
 *
 * @return array{0:bool,1:string}
 */
function parentPanelAccessCheck(string $base, string $email, string $password): array
{
    $jar = tempnam(sys_get_temp_dir(), 'aqm-parent-jar-');

    // GET /login for CSRF.
    $ch = curl_init($base . '/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'aqm-verify/1.0',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (! preg_match('/<meta[^>]*name="csrf-token"[^>]*content="([^"]+)"/i', (string) $body, $m)
        && ! preg_match('/name="_token"\s+value="([^"]+)"/i', (string) $body, $m)) {
        @unlink($jar);
        return [false, 'CSRF token not found'];
    }
    $token = $m[1];

    // POST /login.
    $ch = curl_init($base . '/login');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_token' => $token,
            'email' => $email,
            'password' => $password,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'aqm-verify/1.0',
    ]);
    curl_exec($ch);
    curl_close($ch);

    // GET /parent with session cookie.
    $ch = curl_init($base . '/parent');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => 'aqm-verify/1.0',
    ]);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    @unlink($jar);

    $code = $info['http_code'] ?? 0;
    $headerSize = $info['header_size'] ?? 0;
    $headerBlock = substr((string) $resp, 0, $headerSize);
    $location = '';
    if (preg_match('/^Location:\s*(\S+)/im', $headerBlock, $m)) {
        $location = trim($m[1]);
    }

    // Acceptable outcomes:
    //   200 — parent panel rendered
    //   302 → /parent/* — Filament internal redirect (e.g. dashboard)
    if ($code === 200) {
        return [true, "GET /parent → HTTP 200 (panel rendered)"];
    }
    if ($code === 302 && stripos($location, '/parent') !== false) {
        return [true, "GET /parent → 302 {$location}"];
    }
    return [false, "GET /parent → HTTP {$code}" . ($location ? " → {$location}" : '')];
}
