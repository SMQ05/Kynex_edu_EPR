/**
 * ExamMarksEntryScreen — Management App
 *
 * Step-wise exam marks entry: Select Exam → Select Subject → Enter Marks.
 * Validates against max_marks, shows pre-filled values, batch saves.
 */
import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  Alert,
  SafeAreaView,
} from 'react-native';
import { examsApi } from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface Exam {
  id: string;
  name: string;
  start_date?: string;
  end_date?: string;
  status?: string;
}

interface ExamSchedule {
  id: string;
  subject_name: string;
  exam_date?: string;
  max_marks: number;
}

interface StudentMark {
  student_id: string;
  student_name: string;
  roll_number: string | null;
  marks_obtained: number | null;
  max_marks: number;
}

// ── Helpers ────────────────────────────────────────────────────────────

const ALLOWED_ROLES = ['TEACHER', 'EXAM_ADMIN', 'SCHOOL_ADMIN', 'INSTITUTE_OWNER'];

function hasPermission(roles: string[]): boolean {
  return roles.some((r) => ALLOWED_ROLES.includes(r));
}

// ── Component ──────────────────────────────────────────────────────────

export default function ExamMarksEntryScreen() {
  const { user } = useAuthStore();

  // Step state
  const [step, setStep] = useState<1 | 2 | 3>(1);

  // Exams
  const [exams, setExams] = useState<Exam[]>([]);
  const [selectedExam, setSelectedExam] = useState<Exam | null>(null);

  // Schedules
  const [schedules, setSchedules] = useState<ExamSchedule[]>([]);
  const [selectedSchedule, setSelectedSchedule] = useState<ExamSchedule | null>(null);

  // Marks
  const [students, setStudents] = useState<StudentMark[]>([]);
  const [marksMap, setMarksMap] = useState<Record<string, string>>({});
  const [errors, setErrors] = useState<Record<string, string>>({});

  // UI state
  const [isLoading, setIsLoading] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  // ── Role Check ──────────────────────────────────────────────────────

  if (!hasPermission(user?.roles ?? [])) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyIcon}>🔒</Text>
        <Text style={styles.emptyText}>
          You don't have permission to access marks entry.
        </Text>
      </View>
    );
  }

  // ── Data Fetching ───────────────────────────────────────────────────

  const fetchExams = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);

    try {
      const response = await examsApi.list({ status: 'active' });
      setExams(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Failed to load exams. Please try again.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  const fetchSchedules = async (examId: string) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await examsApi.schedules(examId);
      setSchedules(response.data?.data ?? response.data ?? []);
      setStep(2);
    } catch {
      setError('Failed to load subjects.');
    } finally {
      setIsLoading(false);
    }
  };

  const fetchStudents = async (examId: string, scheduleId: string) => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await examsApi.marksEntry(examId, { schedule_id: scheduleId });
      const data: StudentMark[] = response.data?.data ?? response.data ?? [];
      setStudents(data);
      // Pre-fill existing marks
      const prefill: Record<string, string> = {};
      data.forEach((s) => {
        if (s.marks_obtained != null) {
          prefill[s.student_id] = String(s.marks_obtained);
        }
      });
      setMarksMap(prefill);
      setErrors({});
      setStep(3);
    } catch {
      setError('Failed to load student list.');
    } finally {
      setIsLoading(false);
    }
  };

  // ── Initialize on mount ─────────────────────────────────────────────

  React.useEffect(() => {
    fetchExams();
  }, [fetchExams]);

  // ── Marks Handlers ──────────────────────────────────────────────────

  const handleMarksChange = (studentId: string, value: string) => {
    setMarksMap((prev) => ({ ...prev, [studentId]: value }));
    // Clear error on change
    if (errors[studentId]) {
      setErrors((prev) => {
        const next = { ...prev };
        delete next[studentId];
        return next;
      });
    }
  };

  const validateAndSave = async () => {
    if (!selectedExam || !selectedSchedule) return;

    const maxMarks = selectedSchedule.max_marks;
    const newErrors: Record<string, string> = {};

    students.forEach((s) => {
      const val = marksMap[s.student_id];
      if (val === undefined || val === '') return; // allow empty (not graded yet)
      const num = Number(val);
      if (isNaN(num) || num < 0) {
        newErrors[s.student_id] = 'Invalid marks';
      } else if (num > maxMarks) {
        newErrors[s.student_id] = `Max ${maxMarks}`;
      }
    });

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      Alert.alert('Validation Error', 'Please fix the highlighted marks before saving.');
      return;
    }

    setSaving(true);
    try {
      const marks = students
        .filter((s) => marksMap[s.student_id] !== undefined && marksMap[s.student_id] !== '')
        .map((s) => ({
          student_id: s.student_id,
          marks_obtained: Number(marksMap[s.student_id]),
          schedule_id: selectedSchedule.id,
        }));

      await examsApi.saveMarks(selectedExam.id, { marks });
      Alert.alert('Success', 'Marks saved successfully!');
    } catch {
      Alert.alert('Error', 'Failed to save marks. Please try again.');
    } finally {
      setSaving(false);
    }
  };

  // ── Loading / Error states ──────────────────────────────────────────

  if (isLoading && exams.length === 0 && step === 1) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading exams…</Text>
      </View>
    );
  }

  if (error && exams.length === 0 && step === 1) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchExams()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Step 1: Select Exam ─────────────────────────────────────────────

  if (step === 1) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Exam Marks Entry</Text>
          <Text style={styles.headerSub}>Step 1: Select an exam</Text>
        </View>
        <FlatList
          data={exams}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={isRefreshing}
              onRefresh={() => fetchExams(true)}
              colors={[colors.primary]}
            />
          }
          renderItem={({ item }) => (
            <TouchableOpacity
              style={styles.card}
              onPress={() => {
                setSelectedExam(item);
                fetchSchedules(item.id);
              }}
              activeOpacity={0.75}
            >
              <Text style={styles.cardTitle}>{item.name}</Text>
              {item.start_date && (
                <Text style={styles.cardMeta}>
                  {item.start_date} — {item.end_date ?? ''}
                </Text>
              )}
              {item.status && (
                <View style={styles.statusBadge}>
                  <Text style={styles.statusText}>{item.status}</Text>
                </View>
              )}
            </TouchableOpacity>
          )}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyIcon}>📝</Text>
              <Text style={styles.emptyText}>No active exams found.</Text>
            </View>
          }
        />
      </SafeAreaView>
    );
  }

  // ── Step 2: Select Subject ──────────────────────────────────────────

  if (step === 2) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => setStep(1)}>
            <Text style={styles.backText}>← Back</Text>
          </TouchableOpacity>
          <Text style={styles.headerTitle}>{selectedExam?.name}</Text>
          <Text style={styles.headerSub}>Step 2: Select a subject</Text>
        </View>
        {isLoading ? (
          <View style={styles.centered}>
            <ActivityIndicator size="large" color={colors.primary} />
          </View>
        ) : (
          <FlatList
            data={schedules}
            keyExtractor={(item) => item.id}
            contentContainerStyle={styles.list}
            renderItem={({ item }) => (
              <TouchableOpacity
                style={styles.card}
                onPress={() => {
                  setSelectedSchedule(item);
                  fetchStudents(selectedExam!.id, item.id);
                }}
                activeOpacity={0.75}
              >
                <Text style={styles.cardTitle}>{item.subject_name}</Text>
                <Text style={styles.cardMeta}>Max Marks: {item.max_marks}</Text>
                {item.exam_date && (
                  <Text style={styles.cardMeta}>Date: {item.exam_date}</Text>
                )}
              </TouchableOpacity>
            )}
            ListEmptyComponent={
              <View style={styles.emptyContainer}>
                <Text style={styles.emptyIcon}>📋</Text>
                <Text style={styles.emptyText}>No subjects scheduled for this exam.</Text>
              </View>
            }
          />
        )}
      </SafeAreaView>
    );
  }

  // ── Step 3: Enter Marks ─────────────────────────────────────────────

  const renderStudentRow = ({ item }: { item: StudentMark }) => {
    const hasError = !!errors[item.student_id];
    return (
      <View style={[styles.studentRow, hasError && styles.studentRowError]}>
        <View style={styles.studentInfo}>
          <Text style={styles.studentName}>{item.student_name}</Text>
          {item.roll_number && (
            <Text style={styles.rollNumber}>Roll# {item.roll_number}</Text>
          )}
        </View>
        <View style={styles.marksInputContainer}>
          <TextInput
            style={[styles.marksInput, hasError && styles.marksInputError]}
            value={marksMap[item.student_id] ?? ''}
            onChangeText={(val) => handleMarksChange(item.student_id, val)}
            keyboardType="numeric"
            placeholder="—"
            placeholderTextColor={colors.textMuted}
            maxLength={5}
          />
          <Text style={styles.maxMarksLabel}>/ {selectedSchedule?.max_marks}</Text>
        </View>
        {hasError && <Text style={styles.inlineError}>{errors[item.student_id]}</Text>}
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => setStep(2)}>
          <Text style={styles.backText}>← Back</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{selectedSchedule?.subject_name}</Text>
        <Text style={styles.headerSub}>
          {selectedExam?.name} · Max: {selectedSchedule?.max_marks}
        </Text>
      </View>

      {isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <>
          <FlatList
            data={students}
            keyExtractor={(item) => item.student_id}
            renderItem={renderStudentRow}
            contentContainerStyle={styles.list}
            ListEmptyComponent={
              <View style={styles.emptyContainer}>
                <Text style={styles.emptyIcon}>👨‍🎓</Text>
                <Text style={styles.emptyText}>No students found for this subject.</Text>
              </View>
            }
          />
          {students.length > 0 && (
            <View style={styles.footer}>
              <TouchableOpacity
                style={[styles.saveBtn, saving && { opacity: 0.7 }]}
                onPress={validateAndSave}
                disabled={saving}
              >
                {saving ? (
                  <ActivityIndicator color={colors.white} />
                ) : (
                  <Text style={styles.saveBtnText}>Save All Marks</Text>
                )}
              </TouchableOpacity>
            </View>
          )}
        </>
      )}
    </SafeAreaView>
  );
}

// ── Styles ─────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.lg,
    backgroundColor: colors.background,
  },
  header: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
    paddingBottom: spacing.sm,
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
    color: colors.textPrimary,
  },
  headerSub: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    marginTop: 2,
  },
  backText: {
    fontSize: fontSize.sm,
    color: colors.primary,
    fontWeight: fontWeight.semibold,
    marginBottom: spacing.xs,
  },
  list: {
    padding: spacing.md,
  },
  card: {
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
    ...shadows.sm,
  },
  cardTitle: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  cardMeta: {
    fontSize: fontSize.xs,
    color: colors.textSecondary,
    marginTop: 2,
  },
  statusBadge: {
    alignSelf: 'flex-start',
    backgroundColor: colors.primary + '15',
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
    borderRadius: borderRadius.full,
    marginTop: spacing.xs,
  },
  statusText: {
    fontSize: fontSize.xs,
    color: colors.primary,
    fontWeight: fontWeight.semibold,
  },
  studentRow: {
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    ...shadows.sm,
  },
  studentRowError: {
    borderWidth: 1,
    borderColor: colors.danger,
  },
  studentInfo: {
    flex: 1,
  },
  studentName: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.medium,
    color: colors.textPrimary,
  },
  rollNumber: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 2,
  },
  marksInputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  marksInput: {
    width: 60,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: borderRadius.sm,
    paddingHorizontal: spacing.sm,
    paddingVertical: 8,
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
    textAlign: 'center',
    backgroundColor: colors.background,
  },
  marksInputError: {
    borderColor: colors.danger,
    backgroundColor: '#fef2f2',
  },
  maxMarksLabel: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    marginLeft: spacing.xs,
  },
  inlineError: {
    width: '100%',
    fontSize: fontSize.xs,
    color: colors.danger,
    marginTop: 4,
    textAlign: 'right',
  },
  footer: {
    padding: spacing.md,
    backgroundColor: colors.white,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  saveBtn: {
    backgroundColor: colors.primary,
    borderRadius: borderRadius.sm,
    paddingVertical: 14,
    alignItems: 'center',
  },
  saveBtnText: {
    color: colors.white,
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
  },
  emptyContainer: {
    alignItems: 'center',
    marginTop: 60,
  },
  emptyIcon: {
    fontSize: 48,
    marginBottom: spacing.md,
  },
  emptyText: {
    fontSize: fontSize.md,
    color: colors.textMuted,
    textAlign: 'center',
  },
  loadingText: {
    marginTop: spacing.sm,
    fontSize: fontSize.sm,
    color: colors.textMuted,
  },
  errorText: {
    fontSize: fontSize.md,
    color: colors.danger,
    textAlign: 'center',
    marginBottom: spacing.md,
  },
  retryBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: borderRadius.sm,
  },
  retryText: {
    color: colors.white,
    fontWeight: fontWeight.semibold,
    fontSize: fontSize.sm,
  },
});
