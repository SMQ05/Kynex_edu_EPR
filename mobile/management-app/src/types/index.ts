/**
 * KynexEdu Type Definitions
 *
 * Shared TypeScript interfaces matching the Laravel API responses.
 */

// ── Auth Types ──────────────────────────────────────────────────────

export interface User {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  whatsapp: string | null;
  profile_photo_path: string | null;
  is_active: boolean;
  active_role: string | null;
  campus_id: string | null;
  roles: string[];
}

export interface LoginResponse {
  token: string;
  user: User;
  school: {
    id: string;
    name: string;
    logo_url: string | null;
  };
}

// ── Student Types ───────────────────────────────────────────────────

export interface Student {
  id: string;
  first_name: string;
  last_name: string;
  full_name: string;
  admission_no: string;
  roll_number: string | null;
  gender: string;
  date_of_birth: string;
  status: string;
  class: SchoolClass | null;
  section: Section | null;
  campus: Campus | null;
  photo_url: string | null;
}

export interface SchoolClass {
  id: string;
  name: string;
  numeric_name: number;
}

export interface Section {
  id: string;
  name: string;
}

export interface Campus {
  id: string;
  name: string;
}

// ── Attendance Types ────────────────────────────────────────────────

export type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day' | 'holiday' | 'excused';

export interface AttendanceRecord {
  id: string;
  student_id: string;
  student_name: string;
  date: string;
  status: AttendanceStatus;
  remarks: string | null;
  late_minutes: number | null;
}

export interface AttendanceSummary {
  student_id: string;
  student_name: string;
  total_days: number;
  present: number;
  absent: number;
  late: number;
  excused: number;
  percentage: number;
}

// ── Fee Types ───────────────────────────────────────────────────────

export type FeeStatus = 'pending' | 'partial' | 'paid' | 'waived' | 'refunded';

export interface StudentFee {
  id: string;
  fee_type: string;
  amount_pkr: string;
  discount_pkr: string;
  fine_pkr: string;
  paid_pkr: string;
  balance_pkr: string;
  due_date: string;
  status: FeeStatus;
}

export interface FeesSummary {
  student: Student;
  fees: StudentFee[];
  total_due_pkr: string;
  total_paid_pkr: string;
}

// ── Exam Result Types ───────────────────────────────────────────────

export interface ExamResult {
  id: string;
  exam_name: string;
  student_id: string;
  total_marks: number;
  marks_obtained: number;
  percentage: number;
  grade: string | null;
  rank: number | null;
  status: string;
}

// ── Timetable Types ─────────────────────────────────────────────────

export interface TimetableEntry {
  id: string;
  day: string;
  start_time: string;
  end_time: string;
  subject: string;
  teacher: string;
  room: string | null;
}

// ── Homework Types ──────────────────────────────────────────────────

export interface Homework {
  id: string;
  title: string;
  description: string;
  subject: string;
  class_name: string;
  section_name: string;
  due_date: string;
  created_at: string;
  has_submission: boolean;
}

// ── Notice Types ────────────────────────────────────────────────────

export interface Notice {
  id: string;
  title: string;
  content: string;
  published_at: string;
  expires_at: string | null;
}

// ── Dashboard Stats ─────────────────────────────────────────────────

export interface DashboardStats {
  total_students: number;
  total_staff: number;
  attendance_today_pct: number;
  fee_collection_this_month_pkr: string;
  pending_fees_pkr: string;
  recent_notices: Notice[];
}

// ── API Response Wrappers ───────────────────────────────────────────

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
