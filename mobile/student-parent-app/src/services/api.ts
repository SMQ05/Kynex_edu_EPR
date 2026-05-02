/**
 * KynexEdu Parent App — API Service
 *
 * Axios client configured for the student/parent mobile app.
 * Handles Sanctum token storage via expo-secure-store and
 * tenant-specific base URL resolution.
 */
import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'kynexedu_auth_token';
const BASE_URL_KEY = 'kynexedu_base_url';

let baseURL = 'https://school.kynexedu.com/api/v1';

const api: AxiosInstance = axios.create({
  baseURL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// ── Request Interceptor ──────────────────────────────────────────────
api.interceptors.request.use(
  async (config: InternalAxiosRequestConfig) => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    const storedBase = await SecureStore.getItemAsync(BASE_URL_KEY);
    if (storedBase) {
      config.baseURL = storedBase;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// ── Response Interceptor ─────────────────────────────────────────────
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    if (error.response?.status === 401) {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
    }
    return Promise.reject(error);
  },
);

// ── Token Management ─────────────────────────────────────────────────
export const setAuthToken = async (token: string): Promise<void> => {
  await SecureStore.setItemAsync(TOKEN_KEY, token);
};

export const getAuthToken = async (): Promise<string | null> => {
  return SecureStore.getItemAsync(TOKEN_KEY);
};

export const clearAuthToken = async (): Promise<void> => {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
};

export const setBaseUrl = async (url: string): Promise<void> => {
  await SecureStore.setItemAsync(BASE_URL_KEY, url);
  api.defaults.baseURL = url;
};

// ── Parent-Specific API Endpoints ────────────────────────────────────

export const authApi = {
  login: (email: string, password: string, schoolSlug: string) =>
    api.post('/auth/login', { email, password, school_slug: schoolSlug }),
  logout: () => api.post('/auth/logout'),
  refresh: () => api.post('/auth/refresh'),
  profile: () => api.get('/auth/profile'),
};

/** Parent sees their children list */
export const childrenApi = {
  list: () => api.get('/students'),
  show: (id: string) => api.get(`/students/${id}`),
};

/** Attendance per child */
export const attendanceApi = {
  summary: (studentId: string, params?: Record<string, unknown>) =>
    api.get(`/attendance/summary`, { params: { student_id: studentId, ...params } }),
  monthly: (studentId: string, month: string) =>
    api.get(`/attendance`, { params: { student_id: studentId, month } }),
};

/** Fee details per child */
export const feesApi = {
  show: (studentId: string) => api.get(`/fees/${studentId}`),
  paymentHistory: (studentId: string) =>
    api.get(`/fees/${studentId}/payments`),
};

/** Exam results per child */
export const resultsApi = {
  list: (studentId: string) =>
    api.get('/results', { params: { student_id: studentId } }),
  show: (id: string) => api.get(`/results/${id}`),
};

/** Timetable for child's class */
export const timetableApi = {
  list: (studentId: string) =>
    api.get('/timetable', { params: { student_id: studentId } }),
};

/** Homework assigned to child's class */
export const homeworkApi = {
  list: (studentId: string, params?: Record<string, unknown>) =>
    api.get('/homework', { params: { student_id: studentId, ...params } }),
  show: (id: string) => api.get(`/homework/${id}`),
  submit: (id: string, data: FormData) =>
    api.post(`/homework/${id}/submit`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
};

/** School notices */
export const noticesApi = {
  list: (params?: Record<string, unknown>) => api.get('/notices', { params }),
  show: (id: string) => api.get(`/notices/${id}`),
};

/** Push notification token endpoints */
export const pushApi = {
  register: (data: {
    token: string;
    platform: string;
    app_type: string;
    device_name?: string;
  }) => api.post('/push/register', data),
  unregister: (token: string) => api.post('/push/unregister', { token }),
};

/** Online classes (student view) */
export const onlineClassesApi = {
  myUpcoming: () => api.get('/online-classes/my-upcoming'),
};

/** Library (student view) */
export const libraryStudentApi = {
  myIssues: () => api.get('/library/my-issues'),
};

/** Hostel endpoints */
export const hostelApi = {
  myAllocation: () => api.get('/hostel/my-allocation'),
};

/** Transport endpoints */
export const transportStudentApi = {
  myAssignment: () => api.get('/transport/my-assignment'),
};

/** Notification endpoints */
export const notificationsApi = {
  list: (params?: Record<string, unknown>) => api.get('/notifications', { params }),
  markRead: (id: string) => api.patch(`/notifications/${id}/read`),
  markAllRead: () => api.post('/notifications/read-all'),
};

/** Auth role switching */
export const roleSwitchApi = {
  switchRole: (role: string) => api.patch('/auth/switch-role', { role }),
};

/** Timetable (student view) */
export const timetableStudentApi = {
  my: (params?: Record<string, unknown>) => api.get('/timetable/my', { params }),
};

export default api;
