<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\SchoolUser;
use App\Services\Ai\Tools\AiTool;
use App\Services\Ai\Tools\AttendanceSummaryTool;
use App\Services\Ai\Tools\LookupStudentTool;
use App\Services\Ai\Tools\StudentFeesTool;

/**
 * Builds the set of agentic tools the in-app assistant may use for a given
 * user. SAFETY: data-lookup tools are limited to office/admin roles (so a
 * teacher/parent can't query arbitrary students). Admins bypass per-tool
 * permission checks; other office roles need the tool's permission.
 *
 * Only READ tools are registered today. Write tools are staged — they must
 * be RBAC-gated AND routed through ApprovalService before being added here.
 */
class AiToolRegistry
{
    /** Roles allowed to use data-lookup tools. */
    private const OFFICE_ROLES = [
        'SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD',
        'REGISTRAR', 'ACCOUNTANT', 'BURSAR', 'EXAM_ADMIN',
        'HEADMASTER', 'PRINCIPAL', 'HR_MANAGER',
    ];

    private const ADMIN_ROLES = ['SCHOOL_ADMIN', 'INSTITUTE_HEAD', 'MULTI_INSTITUTE_HEAD'];

    /** @return list<AiTool> */
    public static function forUser(SchoolUser $user): array
    {
        $roles = $user->allRoleNames();

        // Students/parents and non-office staff get no data tools (they keep
        // the role-scoped facts snapshot instead).
        if (! array_intersect($roles, self::OFFICE_ROLES)) {
            return [];
        }

        $isAdmin = (bool) array_intersect($roles, self::ADMIN_ROLES);

        $all = [
            new LookupStudentTool(),
            new StudentFeesTool(),
            new AttendanceSummaryTool(),
        ];

        return array_values(array_filter($all, function (AiTool $tool) use ($user, $isAdmin): bool {
            if ($tool->isWrite()) {
                return false; // write tools are staged/off
            }
            $perm = $tool->requiredPermission();
            if (! $perm || $isAdmin) {
                return true;
            }
            try {
                return $user->hasPermissionTo($perm);
            } catch (\Throwable) {
                return false;
            }
        }));
    }

    /**
     * @param  list<AiTool>  $tools
     * @return list<array<string,mixed>>
     */
    public static function schema(array $tools): array
    {
        return array_map(fn (AiTool $t): array => $t->toSchema(), $tools);
    }

    /** @param list<AiTool> $tools */
    public static function execute(array $tools, string $name, array $args): string
    {
        foreach ($tools as $tool) {
            if ($tool->name() === $name) {
                try {
                    return $tool->handle($args);
                } catch (\Throwable $e) {
                    return 'Tool error: ' . $e->getMessage();
                }
            }
        }

        return "Unknown tool: {$name}";
    }
}
