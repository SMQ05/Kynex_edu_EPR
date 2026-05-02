/**
 * KynexEdu API Service
 *
 * Centralized Axios client for communicating with the Laravel backend.
 * Handles token management via expo-secure-store.
 */
import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'kynexedu_auth_token';
const BASE_URL_KEY = 'kynexedu_base_url';

// Default base URL — overridden at login when tenant subdomain is known
let baseURL = 'https://school.kynexedu.com/api/v1';

const api: AxiosInstance = axios.create({
  baseURL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

// ── Request Interceptor: Attach Bearer Token ─────────────────────────
api.interceptors.request.use(
  async (config: InternalAxiosRequestConfig) => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    // Dynamically update baseURL if stored
    const storedBase = await SecureStore.getItemAsync(BASE_URL_KEY);
    if (storedBase) {
      config.baseURL = storedBase;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// ── Response Interceptor: Handle 401 Unauthenticated ─────────────────
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    if (error.response?.status === 401) {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
      // The auth store will detect token removal and redirect to login
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

export const getBaseUrl = async (): Promise<string | null> => {
  return SecureStore.getItemAsync(BASE_URL_KEY);
};

// ── API Endpoints ────────────────────────────────────────────────────

export const authApi = {
  login: (email: string, password: string, schoolSlug: string) =>
    api.post('/auth/login', { email, password, school_slug: schoolSlug }),
  logout: () => api.post('/auth/logout'),
  refresh: () => api.post('/auth/refresh'),
};

export const studentsApi = {
  list: (params?: Record<string, unknown>) => api.get('/students', { params }),
  show: (id: string) => api.get(`/students/${id}`),
  create: (data: Record<string, unknown>) => api.post('/students', data),
};

export const attendanceApi = {
  list: (params?: Record<string, unknown>) => api.get('/attendance', { params }),
  summary: (params?: Record<string, unknown>) => api.get('/attendance/summary', { params }),
  mark: (data: Record<string, unknown>) => api.post('/attendance', data),
};

export const feesApi = {
  show: (studentId: string) => api.get(`/fees/${studentId}`),
};

export const resultsApi = {
  list: (params?: Record<string, unknown>) => api.get('/results', { params }),
  show: (id: string) => api.get(`/results/${id}`),
};

export const timetableApi = {
  list: (params?: Record<string, unknown>) => api.get('/timetable', { params }),
};

export const homeworkApi = {
  list: (params?: Record<string, unknown>) => api.get('/homework', { params }),
  show: (id: string) => api.get(`/homework/${id}`),
  submit: (id: string, data: FormData) =>
    api.post(`/homework/${id}/submit`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
};

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

/** Exam endpoints */
export const examsApi = {
  list: (params?: Record<string, unknown>) => api.get('/exams', { params }),
  schedules: (examId: string) => api.get(`/exams/${examId}/schedules`),
  marksEntry: (examId: string, params?: Record<string, unknown>) =>
    api.get(`/exams/${examId}/marks-entry`, { params }),
  saveMarks: (examId: string, data: Record<string, unknown>) =>
    api.post(`/exams/${examId}/marks`, data),
};

/** Fee collection endpoints (management) */
export const feeCollectionApi = {
  studentFees: (studentId: string) => api.get(`/fees/student/${studentId}`),
  collect: (data: Record<string, unknown>) => api.post('/fees/collect', data),
  requestRefund: (feeId: string) => api.post(`/fees/${feeId}/refund`),
};

/** Payroll endpoints */
export const payrollApi = {
  list: (params?: Record<string, unknown>) => api.get('/payroll', { params }),
  payslip: (id: string) => api.get(`/payroll/${id}/payslip`),
};

/** Library endpoints */
export const libraryApi = {
  issues: (params?: Record<string, unknown>) => api.get('/library/issues', { params }),
  returnBook: (issueId: string) => api.post(`/library/return/${issueId}`),
  overdue: () => api.get('/library/overdue'),
};

/** Transport endpoints */
export const transportApi = {
  routes: () => api.get('/transport/routes'),
  routeStudents: (routeId: string) => api.get(`/transport/routes/${routeId}/students`),
};

/** Online classes endpoints */
export const onlineClassesApi = {
  list: (params?: Record<string, unknown>) => api.get('/online-classes', { params }),
  show: (id: string) => api.get(`/online-classes/${id}`),
};

/** Reports endpoints */
export const reportsApi = {
  customList: () => api.get('/reports/custom'),
  runCustom: (id: string) => api.post(`/reports/custom/${id}/run`),
  quick: (type: string) => api.get(`/reports/quick/${type}`),
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

export default api;
