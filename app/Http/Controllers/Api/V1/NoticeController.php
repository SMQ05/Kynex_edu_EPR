<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NoticeResource;
use App\Models\Tenant\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * NoticeController — API endpoints for school notices/announcements.
 *
 * Supports:
 *   GET /api/v1/notices       — list published notices (role-filtered)
 *   GET /api/v1/notices/{id}  — get a single notice detail
 */
class NoticeController extends Controller
{
    /**
     * GET /api/v1/notices
     *
     * Returns published, non-expired notices filtered by the user's role.
     *
     * Query params:
     *   - per_page (optional, default 15)
     *   - search (optional) search in title/content
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $userRole = $user->getActiveRoleName() ?? 'student';

        $query = Notice::query()
            ->with('creator')
            ->published()
            ->active()
            ->forRole($userRole);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%");
            });
        }

        $notices = $query->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 15));

        return NoticeResource::collection($notices);
    }

    /**
     * GET /api/v1/notices/{id}
     *
     * Retrieve a single notice detail.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $userRole = $user->getActiveRoleName() ?? 'student';

        $notice = Notice::with('creator')
            ->published()
            ->forRole($userRole)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new NoticeResource($notice),
        ]);
    }
}
