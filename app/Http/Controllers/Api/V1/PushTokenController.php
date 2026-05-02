<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    /**
     * Register or update a push notification device token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'       => 'required|string|max:255',
            'platform'    => 'required|in:android,ios,web',
            'app_type'    => 'required|in:management,student_parent',
            'device_name' => 'nullable|string|max:100',
        ]);

        $deviceToken = DeviceToken::registerOrUpdate(
            schoolUserId: $request->user()->id,
            token: $validated['token'],
            platform: $validated['platform'],
            appType: $validated['app_type'],
            deviceName: $validated['device_name'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully.',
            'data'    => [
                'id'       => $deviceToken->id,
                'platform' => $deviceToken->platform,
            ],
        ]);
    }

    /**
     * Unregister (deactivate) a push notification device token.
     */
    public function unregister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        DeviceToken::where('token', $validated['token'])
            ->where('school_user_id', $request->user()->id)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device token unregistered successfully.',
        ]);
    }
}
