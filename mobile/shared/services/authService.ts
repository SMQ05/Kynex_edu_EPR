/**
 * mobile/shared/services/authService.ts
 *
 * Shared authentication service for both KynexEdu mobile apps.
 *
 * Responsibilities:
 *   - login / logout
 *   - persist token + tenant base URL via expo-secure-store
 *   - FCM push notification token registration
 *   - current user profile fetch
 *
 * Both apps import from this shared service to avoid duplication.
 * Each app's zustand authStore calls these functions.
 */

import * as SecureStore from 'expo-secure-store';
import {
  authEndpoints,
  setAuthToken,
  clearAuthToken,
  setTenantBaseUrl,
  clearTenantBaseUrl,
  setTenantSlug,
  TOKEN_KEY,
} from '../apiClient';

// ── Types ─────────────────────────────────────────────────────────────

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  phone?: string | null;
  profile_photo_url?: string | null;
  roles: string[];
  active_role?: string | null;
  campus_id?: string | null;
  is_active: boolean;
}

export interface LoginPayload {
  email: string;
  password: string;
  schoolSlug: string;
}

export interface LoginResult {
  success: boolean;
  user?: AuthUser;
  token?: string;
  error?: string;
}

// ── Login ─────────────────────────────────────────────────────────────

/**
 * Authenticate a user against their tenant's backend.
 *
 * Steps:
 *   1. Set base URL to tenant subdomain BEFORE making the login request
 *   2. POST /auth/login
 *   3. Store token in SecureStore
 *   4. Return user object
 */
export async function login(payload: LoginPayload): Promise<LoginResult> {
  try {
    // Pre-configure the base URL for this tenant
    await setTenantBaseUrl(payload.schoolSlug);
    await setTenantSlug(payload.schoolSlug);

    const response = await authEndpoints.login(
      payload.email,
      payload.password,
      payload.schoolSlug,
    );

    const { token, user } = response.data;

    if (!token || !user) {
      return {
        success: false,
        error: 'Invalid response from server.',
      };
    }

    // Persist token
    await setAuthToken(token);

    return { success: true, user, token };

  } catch (error: unknown) {
    const axiosError = error as { response?: { data?: { message?: string }; status?: number } };

    if (axiosError.response?.status === 401) {
      return { success: false, error: 'Invalid email or password.' };
    }

    if (axiosError.response?.status === 403) {
      return { success: false, error: 'Your account is not active. Contact admin.' };
    }

    const message = axiosError.response?.data?.message ?? 'Network error. Please try again.';
    return { success: false, error: message };
  }
}

// ── Logout ────────────────────────────────────────────────────────────

/**
 * Log out the current user.
 *
 * Steps:
 *   1. POST /auth/logout (to invalidate token server-side)
 *   2. Clear all stored credentials
 */
export async function logout(): Promise<void> {
  try {
    await authEndpoints.logout();
  } catch {
    // Ignore errors — we always clear local storage
  } finally {
    await clearAuthToken();
    await clearTenantBaseUrl();
  }
}

// ── Get Current User ──────────────────────────────────────────────────

/**
 * Fetch the currently authenticated user's profile.
 * Returns null if not authenticated or request fails.
 */
export async function getCurrentUser(): Promise<AuthUser | null> {
  try {
    const response = await authEndpoints.me();
    return response.data.user ?? response.data ?? null;
  } catch {
    return null;
  }
}

// ── Check Auth ────────────────────────────────────────────────────────

/**
 * Check if a valid token exists in SecureStore.
 * Does NOT verify the token with the server — use getCurrentUser() for that.
 */
export async function hasStoredToken(): Promise<boolean> {
  const token = await SecureStore.getItemAsync(TOKEN_KEY);
  return !!token;
}

// ── FCM Token Registration ────────────────────────────────────────────

/**
 * Register an Expo/FCM push notification token with the backend.
 * Called after successful login if push permission is granted.
 */
export async function registerPushToken(expoPushToken: string): Promise<void> {
  try {
    // Lazy import to avoid issues in environments without expo-notifications
    const apiClient = (await import('../apiClient')).default;
    await apiClient.post('/auth/push-token', { token: expoPushToken });
  } catch (error) {
    console.warn('[AuthService] Failed to register push token:', error);
  }
}
