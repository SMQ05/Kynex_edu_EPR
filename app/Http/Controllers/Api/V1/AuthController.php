<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SchoolUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * AuthController — Sanctum token-based authentication for the tenant API.
 *
 * POST /api/v1/auth/login   — Issue a personal access token
 * POST /api/v1/auth/logout  — Revoke the current token
 * POST /api/v1/auth/refresh — Revoke current + issue new token
 */
class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'sometimes|string|max:255',
        ]);

        $user = SchoolUser::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $deviceName = $request->device_name ?? ($request->userAgent() ?? 'api-client');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully.',
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $deviceName = $request->user()->currentAccessToken()->name ?? 'api-client';

        // Revoke current token
        $user->currentAccessToken()->delete();

        // Issue new token
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
