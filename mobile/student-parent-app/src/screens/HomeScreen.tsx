/**
 * HomeScreen — Parent Dashboard
 *
 * Shows child selector and summary cards for attendance, fees, results.
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';
import { useAuthStore } from '../stores/authStore';
import { childrenApi, attendanceApi, feesApi, resultsApi } from '../services/api';
import type { Student, AttendanceSummary, FeesSummary, ExamResult } from '../types';

export default function HomeScreen() {
  const { user, schoolName } = useAuthStore();
  const [children, setChildren] = useState<Student[]>([]);
  const [selectedChild, setSelectedChild] = useState<Student | null>(null);
  const [attendance, setAttendance] = useState<AttendanceSummary | null>(null);
  const [fees, setFees] = useState<FeesSummary | null>(null);
  const [results, setResults] = useState<ExamResult[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadChildren = useCallback(async () => {
    try {
      const res = await childrenApi.list();
      const data: Student[] = res.data.data || res.data;
      setChildren(data);
      if (data.length > 0 && !selectedChild) {
        setSelectedChild(data[0]);
      }
    } catch (err) {
      console.error('Failed to load children', err);
    }
  }, [selectedChild]);

  const loadChildData = useCallback(async (child: Student) => {
    try {
      const [attRes, feeRes, resRes] = await Promise.allSettled([
        attendanceApi.summary(child.id),
        feesApi.show(child.id),
        resultsApi.list(child.id),
      ]);

      if (attRes.status === 'fulfilled') {
        setAttendance(attRes.value.data.data || attRes.value.data);
      }
      if (feeRes.status === 'fulfilled') {
        setFees(feeRes.value.data.data || feeRes.value.data);
      }
      if (resRes.status === 'fulfilled') {
        const r = resRes.value.data.data || resRes.value.data;
        setResults(Array.isArray(r) ? r.slice(0, 3) : []);
      }
    } catch (err) {
      console.error('Failed to load child data', err);
    }
  }, []);

  useEffect(() => {
    (async () => {
      setLoading(true);
      await loadChildren();
      setLoading(false);
    })();
  }, [loadChildren]);

  useEffect(() => {
    if (selectedChild) {
      loadChildData(selectedChild);
    }
  }, [selectedChild, loadChildData]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadChildren();
    if (selectedChild) await loadChildData(selectedChild);
    setRefreshing(false);
  };

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.greeting}>
          Assalam-o-Alaikum, {user?.name?.split(' ')[0]} 👋
        </Text>
        <Text style={styles.schoolName}>{schoolName}</Text>
      </View>

      {/* Child Selector */}
      {children.length > 1 && (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.childSelector}>
          {children.map((child) => (
            <TouchableOpacity
              key={child.id}
              style={[
                styles.childChip,
                selectedChild?.id === child.id && styles.childChipActive,
              ]}
              onPress={() => setSelectedChild(child)}
            >
              <View style={[
                styles.avatar,
                selectedChild?.id === child.id && styles.avatarActive,
              ]}>
                <Text style={[
                  styles.avatarText,
                  selectedChild?.id === child.id && styles.avatarTextActive,
                ]}>
                  {child.first_name[0]}
                </Text>
              </View>
              <Text
                style={[
                  styles.childName,
                  selectedChild?.id === child.id && styles.childNameActive,
                ]}
              >
                {child.first_name}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      )}

      {/* Selected Child Info */}
      {selectedChild && (
        <View style={styles.childInfo}>
          <Text style={styles.childFullName}>{selectedChild.full_name}</Text>
          <Text style={styles.childMeta}>
            {selectedChild.class?.name} — {selectedChild.section?.name} | {selectedChild.admission_no}
          </Text>
        </View>
      )}

      {/* Attendance Card */}
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Text style={styles.cardTitle}>📊 Attendance</Text>
          <Text style={styles.cardSubtitle}>This Month</Text>
        </View>
        {attendance ? (
          <View style={styles.statsRow}>
            <View style={styles.statItem}>
              <Text style={[styles.statValue, { color: colors.present }]}>
                {attendance.present}
              </Text>
              <Text style={styles.statLabel}>Present</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={[styles.statValue, { color: colors.absent }]}>
                {attendance.absent}
              </Text>
              <Text style={styles.statLabel}>Absent</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={[styles.statValue, { color: colors.late }]}>
                {attendance.late}
              </Text>
              <Text style={styles.statLabel}>Late</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={[styles.statValue, { color: colors.primary }]}>
                {attendance.percentage}%
              </Text>
              <Text style={styles.statLabel}>Rate</Text>
            </View>
          </View>
        ) : (
          <Text style={styles.emptyText}>No attendance data available</Text>
        )}
      </View>

      {/* Fees Card */}
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Text style={styles.cardTitle}>💰 Fee Summary</Text>
        </View>
        {fees ? (
          <View>
            <View style={styles.feeRow}>
              <Text style={styles.feeLabel}>Total Due</Text>
              <Text style={[styles.feeValue, { color: colors.danger }]}>
                PKR {fees.total_due_pkr}
              </Text>
            </View>
            <View style={styles.feeRow}>
              <Text style={styles.feeLabel}>Total Paid</Text>
              <Text style={[styles.feeValue, { color: colors.success }]}>
                PKR {fees.total_paid_pkr}
              </Text>
            </View>
            {fees.fees
              .filter((f) => f.status === 'pending' || f.status === 'partial')
              .slice(0, 3)
              .map((fee) => (
                <View key={fee.id} style={styles.feeItem}>
                  <Text style={styles.feeItemType}>{fee.fee_type}</Text>
                  <View style={styles.feeItemRight}>
                    <Text style={styles.feeItemAmount}>PKR {fee.balance_pkr}</Text>
                    <Text style={styles.feeItemDue}>Due: {fee.due_date}</Text>
                  </View>
                </View>
              ))}
          </View>
        ) : (
          <Text style={styles.emptyText}>No fee information available</Text>
        )}
      </View>

      {/* Results Card */}
      <View style={[styles.card, { marginBottom: spacing.xxl }]}>
        <View style={styles.cardHeader}>
          <Text style={styles.cardTitle}>📝 Recent Results</Text>
        </View>
        {results.length > 0 ? (
          results.map((result) => (
            <View key={result.id} style={styles.resultItem}>
              <View>
                <Text style={styles.resultExam}>{result.exam_name}</Text>
                <Text style={styles.resultMarks}>
                  {result.marks_obtained}/{result.total_marks}
                </Text>
              </View>
              <View style={styles.resultRight}>
                <Text
                  style={[
                    styles.resultGrade,
                    {
                      color: result.percentage >= 80
                        ? colors.success
                        : result.percentage >= 50
                        ? colors.warning
                        : colors.danger,
                    },
                  ]}
                >
                  {result.grade || `${result.percentage}%`}
                </Text>
                {result.rank && (
                  <Text style={styles.resultRank}>Rank #{result.rank}</Text>
                )}
              </View>
            </View>
          ))
        ) : (
          <Text style={styles.emptyText}>No exam results yet</Text>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  center: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
  },
  header: {
    backgroundColor: colors.primary,
    paddingTop: 60,
    paddingBottom: spacing.lg,
    paddingHorizontal: spacing.lg,
  },
  greeting: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
    color: colors.white,
  },
  schoolName: {
    fontSize: fontSize.sm,
    color: 'rgba(255,255,255,0.8)',
    marginTop: spacing.xs,
  },
  childSelector: {
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  childChip: {
    alignItems: 'center',
    marginRight: spacing.md,
    padding: spacing.sm,
    borderRadius: borderRadius.lg,
    backgroundColor: colors.surface,
    ...shadows.sm,
    minWidth: 72,
  },
  childChipActive: {
    backgroundColor: colors.primary,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: colors.primaryLight + '30',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: spacing.xs,
  },
  avatarActive: {
    backgroundColor: 'rgba(255,255,255,0.3)',
  },
  avatarText: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
    color: colors.primary,
  },
  avatarTextActive: {
    color: colors.white,
  },
  childName: {
    fontSize: fontSize.xs,
    fontWeight: fontWeight.medium,
    color: colors.textSecondary,
  },
  childNameActive: {
    color: colors.white,
  },
  childInfo: {
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.md,
  },
  childFullName: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
    color: colors.textPrimary,
  },
  childMeta: {
    fontSize: fontSize.sm,
    color: colors.textSecondary,
    marginTop: 2,
  },
  card: {
    backgroundColor: colors.surface,
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    borderRadius: borderRadius.lg,
    padding: spacing.lg,
    ...shadows.md,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.md,
  },
  cardTitle: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  cardSubtitle: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  statItem: {
    alignItems: 'center',
  },
  statValue: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
  },
  statLabel: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 2,
  },
  feeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  feeLabel: {
    fontSize: fontSize.md,
    color: colors.textSecondary,
  },
  feeValue: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.bold,
  },
  feeItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  feeItemType: {
    fontSize: fontSize.sm,
    color: colors.textPrimary,
    fontWeight: fontWeight.medium,
  },
  feeItemRight: {
    alignItems: 'flex-end',
  },
  feeItemAmount: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.semibold,
    color: colors.danger,
  },
  feeItemDue: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
  },
  resultItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  resultExam: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
    color: colors.textPrimary,
  },
  resultMarks: {
    fontSize: fontSize.xs,
    color: colors.textSecondary,
    marginTop: 2,
  },
  resultRight: {
    alignItems: 'flex-end',
  },
  resultGrade: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
  },
  resultRank: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 2,
  },
  emptyText: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    textAlign: 'center',
    paddingVertical: spacing.md,
  },
});
