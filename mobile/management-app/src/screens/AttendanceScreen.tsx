/**
 * AttendanceScreen — Mark and view attendance for classes.
 *
 * Allows management staff to:
 *   - View today's attendance summary
 *   - Quick-mark attendance (Present/Absent/Late)
 *   - See class-wise attendance percentages
 */
import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  RefreshControl,
} from 'react-native';
import { attendanceApi } from '../../src/services/api';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../../src/theme';
import type { AttendanceSummary } from '../../src/types';

type MarkStatus = 'present' | 'absent' | 'late';

interface StudentRow {
  id: string;
  name: string;
  admission_no: string;
  status: MarkStatus;
}

export default function AttendanceScreen() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [summaries, setSummaries] = useState<AttendanceSummary[]>([]);
  const [marking, setMarking] = useState(false);
  const [studentRows, setStudentRows] = useState<StudentRow[]>([]);
  const [selectedClass, setSelectedClass] = useState<string | null>(null);

  const today = new Date().toISOString().split('T')[0];

  const fetchSummary = async () => {
    try {
      const response = await attendanceApi.summary({ date: today });
      setSummaries(response.data?.data ?? []);
    } catch (err) {
      console.error('Attendance summary error:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchSummary();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    fetchSummary();
  };

  const toggleStatus = (studentId: string) => {
    setStudentRows((prev) =>
      prev.map((s) => {
        if (s.id !== studentId) return s;
        const cycle: MarkStatus[] = ['present', 'absent', 'late'];
        const nextIndex = (cycle.indexOf(s.status) + 1) % cycle.length;
        return { ...s, status: cycle[nextIndex] };
      }),
    );
  };

  const submitAttendance = async () => {
    try {
      setMarking(true);
      await attendanceApi.mark({
        date: today,
        records: studentRows.map((s) => ({
          student_id: s.id,
          status: s.status,
        })),
      });
      Alert.alert('Success', 'Attendance marked successfully!');
      fetchSummary();
    } catch (err) {
      Alert.alert('Error', 'Failed to submit attendance. Please try again.');
    } finally {
      setMarking(false);
    }
  };

  const getStatusStyle = (status: MarkStatus) => {
    switch (status) {
      case 'present': return { bg: colors.success + '20', color: colors.success, label: 'P' };
      case 'absent':  return { bg: colors.danger + '20',  color: colors.danger,  label: 'A' };
      case 'late':    return { bg: colors.warning + '20', color: colors.warning, label: 'L' };
    }
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Date Header */}
      <View style={styles.dateHeader}>
        <Text style={styles.dateText}>📅 {today}</Text>
        <Text style={styles.dateLabel}>Today&apos;s Attendance</Text>
      </View>

      {/* Summary Cards */}
      <View style={styles.summaryRow}>
        {summaries.slice(0, 4).map((summary, idx) => (
          <View key={summary.student_id ?? idx} style={styles.summaryCard}>
            <Text style={styles.summaryName} numberOfLines={1}>
              {summary.student_name}
            </Text>
            <Text style={[
              styles.summaryPct,
              { color: summary.percentage >= 75 ? colors.success : colors.danger },
            ]}>
              {summary.percentage}%
            </Text>
            <Text style={styles.summaryDetail}>
              P:{summary.present} A:{summary.absent} L:{summary.late}
            </Text>
          </View>
        ))}
      </View>

      {/* Quick Mark Section */}
      {studentRows.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Mark Attendance</Text>
          <Text style={styles.sectionSubtitle}>Tap status to cycle: Present → Absent → Late</Text>

          {studentRows.map((student) => {
            const style = getStatusStyle(student.status);
            return (
              <View key={student.id} style={styles.markRow}>
                <View style={styles.markInfo}>
                  <Text style={styles.markName}>{student.name}</Text>
                  <Text style={styles.markAdmission}>{student.admission_no}</Text>
                </View>
                <TouchableOpacity
                  style={[styles.statusButton, { backgroundColor: style.bg }]}
                  onPress={() => toggleStatus(student.id)}
                >
                  <Text style={[styles.statusButtonText, { color: style.color }]}>
                    {style.label}
                  </Text>
                </TouchableOpacity>
              </View>
            );
          })}

          <TouchableOpacity
            style={[styles.submitButton, marking && styles.submitButtonDisabled]}
            onPress={submitAttendance}
            disabled={marking}
          >
            {marking ? (
              <ActivityIndicator color={colors.white} />
            ) : (
              <Text style={styles.submitButtonText}>Submit Attendance</Text>
            )}
          </TouchableOpacity>
        </View>
      )}

      {/* Instructions */}
      {studentRows.length === 0 && (
        <View style={styles.section}>
          <Text style={styles.instructionText}>
            Select a class and section from the web dashboard to start marking attendance,
            or pull down to refresh the summary.
          </Text>
        </View>
      )}

      <View style={{ height: spacing.xxl }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  dateHeader: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
    alignItems: 'center',
  },
  dateText: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
    color: colors.white,
  },
  dateLabel: {
    fontSize: fontSize.sm,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  summaryRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    padding: spacing.md,
    gap: spacing.sm,
  },
  summaryCard: {
    width: '48%',
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    ...shadows.sm,
  },
  summaryName: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
    color: colors.textPrimary,
  },
  summaryPct: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
    marginVertical: 4,
  },
  summaryDetail: {
    fontSize: fontSize.xs,
    color: colors.textSecondary,
  },
  section: {
    paddingHorizontal: spacing.md,
    marginTop: spacing.md,
  },
  sectionTitle: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  sectionSubtitle: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginBottom: spacing.md,
  },
  markRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: borderRadius.sm,
    padding: spacing.md,
    marginBottom: spacing.sm,
    ...shadows.sm,
  },
  markInfo: {
    flex: 1,
  },
  markName: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.medium,
    color: colors.textPrimary,
  },
  markAdmission: {
    fontSize: fontSize.xs,
    color: colors.textSecondary,
  },
  statusButton: {
    width: 44,
    height: 44,
    borderRadius: borderRadius.full,
    justifyContent: 'center',
    alignItems: 'center',
  },
  statusButtonText: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
  },
  submitButton: {
    backgroundColor: colors.primary,
    borderRadius: borderRadius.sm,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: spacing.md,
  },
  submitButtonDisabled: {
    opacity: 0.7,
  },
  submitButtonText: {
    color: colors.white,
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
  },
  instructionText: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    textAlign: 'center',
    paddingVertical: spacing.xxl,
    lineHeight: 22,
  },
});
