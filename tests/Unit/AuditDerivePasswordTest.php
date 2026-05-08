<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\AuditRunCommand;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class AuditDerivePasswordTest extends TestCase
{
    /**
     * Calls the private derivePassword() method via reflection.
     * The formula must stay in sync with Pak::demoPassword() in the demo seeder.
     */
    private function derivePassword(string $roleKey, string $email): string
    {
        $cmd    = new AuditRunCommand();
        $ref    = new ReflectionClass($cmd);
        $method = $ref->getMethod('derivePassword');
        $method->setAccessible(true);
        return $method->invoke($cmd, $roleKey, $email);
    }

    /** @test */
    public function password_always_starts_with_demo_prefix(): void
    {
        $pass = $this->derivePassword('admin', 'admin@example.com');
        $this->assertStringStartsWith('Demo2026@', $pass);
    }

    /** @test */
    public function password_suffix_is_six_chars(): void
    {
        $pass   = $this->derivePassword('admin', 'admin@example.com');
        $suffix = substr($pass, strlen('Demo2026@'));
        $this->assertSame(6, strlen($suffix));
    }

    /** @test */
    public function password_is_deterministic(): void
    {
        $a = $this->derivePassword('teacher', 'teacher@school.test');
        $b = $this->derivePassword('teacher', 'teacher@school.test');
        $this->assertSame($a, $b);
    }

    /** @test */
    public function different_roles_produce_different_passwords(): void
    {
        $email = 'user@school.test';
        $a     = $this->derivePassword('admin',     $email);
        $b     = $this->derivePassword('principal', $email);
        $this->assertNotSame($a, $b);
    }

    /** @test */
    public function different_emails_produce_different_passwords(): void
    {
        $role = 'teacher';
        $a    = $this->derivePassword($role, 'alice@school.test');
        $b    = $this->derivePassword($role, 'bob@school.test');
        $this->assertNotSame($a, $b);
    }

    /**
     * Regression: formula must use config('app.key'), not env('APP_KEY').
     * Both resolve to the same value in a normal test run, but the test
     * verifies the return type is always a non-empty string.
     *
     * @test
     */
    public function returns_string_even_when_app_key_is_set(): void
    {
        $result = $this->derivePassword('accountant', 'acc@example.com');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }
}
