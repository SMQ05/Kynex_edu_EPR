/**
 * KynexEdu Parent App — Type Definitions
 *
 * TypeScript interfaces matching the Laravel API responses,
 * tailored for the student/parent experience.
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

// ── Student / Child Types ───────────────────────────────────────────

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
  date: string;
  status: AttendanceStatus;
  remarks: string | null;
  late_minutes: number | null;
}

export interface AttendanceSummary {
  student_id: string;
  total_days: number;
  present: number;
  absent: number;
  late: number;
  excused: number;
  percentage: number;
}

export interface MonthlyAttendance {
  date: string;
  status: AttendanceStatus;
  remarks: string | null;
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

export interface FeePayment {
  id: string;
  receipt_no: string;
  amount_pkr: string;
  payment_method: string;
  paid_at: string;
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
  subject_marks?: SubjectMark[];
}

export interface SubjectMark {
  subject: string;
  marks_obtained: number;
  total_marks: number;
  is_pass: boolean;
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
  submission_status?: string;
}

// ── Notice Types ────────────────────────────────────────────────────

export interface Notice {
  id: string;
  title: string;
  content: string;
  published_at: string;
  expires_at: string | null;
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
