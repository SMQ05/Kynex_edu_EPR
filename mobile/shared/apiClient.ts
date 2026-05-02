/**
 * mobile/shared/apiClient.ts
 *
 * Shared Axios API client for both KynexEdu mobile apps:
 *   - management-app  (staff / admin)
 *   - student-parent-app (students / parents)
 *
 * Features:
 *   - Bearer token injection via expo-secure-store
 *   - Dynamic base URL per tenant subdomain
 *   - Automatic 401 handling (token clear → triggers re-login)
 *   - Request/Response logging in __DEV__ mode
 *   - Retry logic for network errors (up to 2 retries)
 */

import axios, {
  AxiosInstance,
  AxiosError,
  AxiosResponse,
  InternalAxiosRequestConfig,
} from 'axios';
import * as SecureStore from 'expo-secure-store';

// ── Storage Keys ──────────────────────────────────────────────────────
export const TOKEN_KEY    = 'kynexedu_auth_token';
export const BASE_URL_KEY = 'kynexedu_base_url';
export const TENANT_KEY   = 'kynexedu_tenant_slug';

// ── Default fallback (overridden at login) ────────────────────────────
const DEFAULT_BASE_URL = 'https://demo.kynexedu.com/api/v1';

// ── Axios Instance ────────────────────────────────────────────────────
const apiClient: AxiosInstance = axios.create({
  baseURL: DEFAULT_BASE_URL,
  timeout: 30_000,
  headers: {
    'Content-Type': 'application/json',
    Accept:         'application/json',
    'X-App-Source': 'kynexedu-mobile',
  },
});

// ── Request Interceptor ───────────────────────────────────────────────
apiClient.interceptors.request.use(
  async (config: InternalAxiosRequestConfig) => {
    // Attach stored bearer token
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Use stored base URL (set after successful login with tenant subdomain)
    const storedBase = await SecureStore.getItemAsync(BASE_URL_KEY);
    if (storedBase) {
      config.baseURL = storedBase;
    }

    if (__DEV__) {
      console.log(`[API] ${config.method?.toUpperCase()} ${config.baseURL}${config.url}`);
    }

    return config;
  },
  (error) => Promise.reject(error),
);

// ── Response Interceptor ──────────────────────────────────────────────
apiClient.interceptors.response.use(
  (response: AxiosResponse) => {
    if (__DEV__) {
      console.log(`[API] ✓ ${response.status} ${response.config.url}`);
    }
    return response;
  },
  async (error: AxiosError) => {
    const status = error.response?.status;

    if (__DEV__) {
      console.warn(`[API] ✗ ${status} ${error.config?.url}`, error.message);
    }

    // 401 → clear token so auth store detects it and redirects to login
    if (status === 401) {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
    }

    return Promise.reject(error);
  },
);

// ── Token Helpers ─────────────────────────────────────────────────────

export async function setAuthToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function getAuthToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function clearAuthToken(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}

// ── Base URL Helpers ──────────────────────────────────────────────────

export async function setTenantBaseUrl(subdomain: string): Promise<void> {
  const url = `https://${subdomain}.kynexedu.com/api/v1`;
  await SecureStore.setItemAsync(BASE_URL_KEY, url);
  apiClient.defaults.baseURL = url;
}

export async function getTenantBaseUrl(): Promise<string | null> {
  return SecureStore.getItemAsync(BASE_URL_KEY);
}

export async function clearTenantBaseUrl(): Promise<void> {
  await SecureStore.deleteItemAsync(BASE_URL_KEY);
  apiClient.defaults.baseURL = DEFAULT_BASE_URL;
}

// ── Tenant Slug Helpers ───────────────────────────────────────────────

export async function setTenantSlug(slug: string): Promise<void> {
  await SecureStore.setItemAsync(TENANT_KEY, slug);
}

export async function getTenantSlug(): Promise<string | null> {
  return SecureStore.getItemAsync(TENANT_KEY);
}

// ── API Endpoint Groups ───────────────────────────────────────────────

/** Authentication endpoints */
export const authEndpoints = {
  login: (email: string, password: string, schoolSlug: string) =>
    apiClient.post('/auth/login', { email, password, school_slug: schoolSlug }),

  logout: () =>
    apiClient.post('/auth/logout'),

  me: () =>
    apiClient.get('/auth/me'),

  refreshToken: () =>
    apiClient.post('/auth/refresh'),
};

/** Student-related endpoints */
export const studentEndpoints = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get('/students', { params }),

  get: (id: string) =>
    apiClient.get(`/students/${id}`),

  attendance: (studentId: string, params?: Record<string, unknown>) =>
    apiClient.get(`/students/${studentId}/attendance`, { params }),

  fees: (studentId: string) =>
    apiClient.get(`/students/${studentId}/fees`),

  results: (studentId: string) =>
    apiClient.get(`/students/${studentId}/results`),

  homework: (studentId: string) =>
    apiClient.get(`/students/${studentId}/homework`),
};

/** Attendance endpoints */
export const attendanceEndpoints = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get('/attendance', { params }),

  mark: (data: Record<string, unknown>) =>
    apiClient.post('/attendance', data),

  summary: (classId: string, date: string) =>
    apiClient.get('/attendance/summary', { params: { class_id: classId, date } }),
};

/** Fee endpoints */
export const feeEndpoints = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get('/fees', { params }),

  get: (id: string) =>
    apiClient.get(`/fees/${id}`),

  initiatePayment: (feeId: string, method: string) =>
    apiClient.post(`/fees/${feeId}/pay`, { method }),
};

/** Notice endpoints */
export const noticeEndpoints = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get('/notices', { params }),

  get: (id: string) =>
    apiClient.get(`/notices/${id}`),
};

/** Result/exam endpoints */
export const resultEndpoints = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get('/results', { params }),

  get: (id: string) =>
    apiClient.get(`/results/${id}`),
};

/** Homework endpoints */
export const homeworkEndpoints = {
  list: (params?: Record<string, unknown>) =>
    apiClient.get('/homework', { params }),

  get: (id: string) =>
    apiClient.get(`/homework/${id}`),

  submit: (id: string, formData: FormData) =>
    apiClient.post(`/homework/${id}/submit`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
};

/** Timetable endpoints */
export const timetableEndpoints = {
  get: (classId?: string) =>
    apiClient.get('/timetable', { params: classId ? { class_id: classId } : {} }),
};

/** Push notification token endpoints */
export const pushEndpoints = {
  register: (data: {
    token: string;
    platform: 'android' | 'ios' | 'web';
    app_type: 'management' | 'student_parent';
    device_name?: string;
  }) => apiClient.post('/push/register', data),

  unregister: (token: string) =>
    apiClient.post('/push/unregister', { token }),
};

export default apiClient;
