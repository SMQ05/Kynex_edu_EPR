/**
 * Auth Store — Zustand store for parent app authentication.
 *
 * Handles FCM push token registration/unregistration on login/logout.
 */
import { create } from 'zustand';
import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import { authApi, pushApi, roleSwitchApi, setAuthToken, clearAuthToken, setBaseUrl } from '../services/api';
import type { User } from '../types';

interface AuthState {
  user: User | null;
  schoolName: string | null;
  schoolLogo: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  pushToken: string | null;
  pushPermissionDenied: boolean;
  unreadNotificationCount: number;

  login: (email: string, password: string, schoolSlug: string) => Promise<boolean>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
  clearError: () => void;
  registerPushToken: () => Promise<void>;
  setUnreadCount: (count: number) => void;
  switchRole: (role: string) => Promise<boolean>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  schoolName: null,
  schoolLogo: null,
  isAuthenticated: false,
  isLoading: true,
  error: null,
  pushToken: null,
  pushPermissionDenied: false,
  unreadNotificationCount: 0,

  login: async (email: string, password: string, schoolSlug: string) => {
    set({ isLoading: true, error: null });

    try {
      const baseUrl = `https://${schoolSlug}.kynexedu.com/api/v1`;
      await setBaseUrl(baseUrl);

      const response = await authApi.login(email, password, schoolSlug);
      const { token, user, school } = response.data;

      await setAuthToken(token);
      await SecureStore.setItemAsync('school_name', school.name);
      await SecureStore.setItemAsync('school_slug', schoolSlug);

      set({
        user,
        schoolName: school.name,
        schoolLogo: school.logo_url,
        isAuthenticated: true,
        isLoading: false,
        error: null,
      });

      // Register push token silently after login
      get().registerPushToken();

      return true;
    } catch (err: any) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.error ||
        'Login failed. Please check your credentials.';

      set({ isLoading: false, error: message, isAuthenticated: false });
      return false;
    }
  },

  logout: async () => {
    // Unregister push token before logout
    const { pushToken } = get();
    if (pushToken) {
      try {
        await pushApi.unregister(pushToken);
      } catch {
        // Ignore unregister errors
      }
    }

    try {
      await authApi.logout();
    } catch {
      // Token may already be invalid
    }

    await clearAuthToken();
    await SecureStore.deleteItemAsync('school_name');
    await SecureStore.deleteItemAsync('school_slug');

    set({
      user: null,
      schoolName: null,
      schoolLogo: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,
      pushToken: null,
      pushPermissionDenied: false,
      unreadNotificationCount: 0,
    });
  },

  checkAuth: async () => {
    set({ isLoading: true });

    try {
      const token = await SecureStore.getItemAsync('kynexedu_auth_token');
      const schoolName = await SecureStore.getItemAsync('school_name');
      const schoolSlug = await SecureStore.getItemAsync('school_slug');

      if (!token || !schoolSlug) {
        set({ isAuthenticated: false, isLoading: false });
        return;
      }

      const baseUrl = `https://${schoolSlug}.kynexedu.com/api/v1`;
      await setBaseUrl(baseUrl);

      const response = await authApi.refresh();
      const { token: newToken, user } = response.data;

      await setAuthToken(newToken);

      set({
        user,
        schoolName,
        isAuthenticated: true,
        isLoading: false,
      });
    } catch {
      await clearAuthToken();
      set({ user: null, isAuthenticated: false, isLoading: false });
    }
  },

  clearError: () => set({ error: null }),

  registerPushToken: async () => {
    try {
      const { status: existingStatus } = await Notifications.getPermissionsAsync();
      let finalStatus = existingStatus;

      if (existingStatus !== 'granted') {
        const { status } = await Notifications.requestPermissionsAsync();
        finalStatus = status;
      }

      if (finalStatus !== 'granted') {
        set({ pushPermissionDenied: true });
        return;
      }

      const tokenData = await Notifications.getExpoPushTokenAsync();
      const pushToken = tokenData.data;

      await pushApi.register({
        token: pushToken,
        platform: Platform.OS,
        app_type: 'student_parent',
        device_name: Device.deviceName ?? undefined,
      });

      set({ pushToken, pushPermissionDenied: false });
    } catch {
      // Silent failure — push registration is non-critical
    }
  },

  setUnreadCount: (count: number) => set({ unreadNotificationCount: count }),

  switchRole: async (role: string) => {
    try {
      const response = await roleSwitchApi.switchRole(role);
      const user = response.data?.user ?? response.data?.data?.user;
      if (user) {
        set({ user });
      } else {
        const currentUser = get().user;
        if (currentUser) {
          set({ user: { ...currentUser, active_role: role } });
        }
      }
      return true;
    } catch {
      return false;
    }
  },
}));
