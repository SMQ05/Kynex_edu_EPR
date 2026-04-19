/**
 * AttendanceScreen — Monthly Attendance Calendar View
 *
 * Shows attendance status for each day of the month with summary stats.
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
} from 'react-native';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';
import { childrenApi, attendanceApi } from '../services/api';
import type { Student, AttendanceSummary, MonthlyAttendance } from '../types';

const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];
const DAYS_HEADER = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export default function AttendanceScreen() {
  const [children, setChildren] = useState<Student[]>([]);
  const [selectedChild, setSelectedChild] = useState<Student | null>(null);
  const [summary, setSummary] = useState<AttendanceSummary | null>(null);
  const [monthlyData, setMonthlyData] = useState<MonthlyAttendance[]>([]);
  const [currentMonth, setCurrentMonth] = useState(() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  });
  const [loading, setLoading] = useState(true);

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
    } finally {
      setLoading(false);
    }
  }, [selectedChild]);

  const loadAttendance = useCallback(async (child: Student, month: string) => {
    try {
      const [summaryRes, monthlyRes] = await Promise.allSettled([
        attendanceApi.summary(child.id, { month }),
        attendanceApi.monthly(child.id, month),
      ]);

      if (summaryRes.status === 'fulfilled') {
        setSummary(summaryRes.value.data.data || summaryRes.value.data);
      }
      if (monthlyRes.status === 'fulfilled') {
        const d = monthlyRes.value.data.data || monthlyRes.value.data;
        setMonthlyData(Array.isArray(d) ? d : []);
      }
    } catch (err) {
      console.error('Failed to load attendance', err);
    }
  }, []);

  useEffect(() => {
    loadChildren();
  }, [loadChildren]);

  useEffect(() => {
    if (selectedChild) loadAttendance(selectedChild, currentMonth);
  }, [selectedChild, currentMonth, loadAttendance]);

  const changeMonth = (delta: number) => {
    const [year, month] = currentMonth.split('-').map(Number);
    const d = new Date(year, month - 1 + delta, 1);
    setCurrentMonth(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`);
  };

  const getStatusForDay = (day: number): MonthlyAttendance | undefined => {
    const dateStr = `${currentMonth}-${String(day).padStart(2, '0')}`;
    return monthlyData.find((d) => d.date === dateStr);
  };

  const getStatusColor = (status: string): string => {
    switch (status) {
      case 'present': return colors.present;
      case 'absent': return colors.absent;
      case 'late': return colors.late;
      case 'excused': return colors.excused;
      case 'half_day': return colors.halfDay;
      case 'holiday': return colors.holiday;
      default: return 'transparent';
    }
  };

  const renderCalendar = () => {
    const [year, month] = currentMonth.split('-').map(Number);
    const firstDay = new Date(year, month - 1, 1).getDay();
    const daysInMonth = new Date(year, month, 0).getDate();

    const cells: React.ReactNode[] = [];

    // Empty cells before first day
    for (let i = 0; i < firstDay; i++) {
      cells.push(<View key={`empty-${i}`} style={styles.calendarCell} />);
    }

    // Day cells
    for (let day = 1; day <= daysInMonth; day++) {
      const record = getStatusForDay(day);
      const bgColor = record ? getStatusColor(record.status) : 'transparent';
      const textColor = record ? colors.white : colors.textSecondary;

      cells.push(
        <View key={day} style={styles.calendarCell}>
          <View
            style={[
              styles.dayCircle,
              { backgroundColor: bgColor },
              !record && styles.dayCircleEmpty,
            ]}
          >
            <Text style={[styles.dayText, { color: textColor }]}>{day}</Text>
          </View>
        </View>,
      );
    }

    return cells;
  };

  const [year, month] = currentMonth.split('-').map(Number);

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>📅 Attendance</Text>
      </View>

      {/* Child Selector */}
      {children.length > 1 && (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.childRow}>
          {children.map((child) => (
            <TouchableOpacity
              key={child.id}
              style={[
                styles.childChip,
                selectedChild?.id === child.id && styles.childChipActive,
              ]}
              onPress={() => setSelectedChild(child)}
            >
              <Text
                style={[
                  styles.childChipText,
                  selectedChild?.id === child.id && styles.childChipTextActive,
                ]}
              >
                {child.first_name}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      )}

      {/* Month Selector */}
      <View style={styles.monthSelector}>
        <TouchableOpacity onPress={() => changeMonth(-1)} style={styles.monthArrow}>
          <Text style={styles.monthArrowText}>‹</Text>
        </TouchableOpacity>
        <Text style={styles.monthLabel}>
          {MONTHS[month - 1]} {year}
        </Text>
        <TouchableOpacity onPress={() => changeMonth(1)} style={styles.monthArrow}>
          <Text style={styles.monthArrowText}>›</Text>
        </TouchableOpacity>
      </View>

      {/* Summary Stats */}
      {summary && (
        <View style={styles.summaryRow}>
          <View style={[styles.summaryItem, { backgroundColor: colors.present + '15' }]}>
            <Text style={[styles.summaryValue, { color: colors.present }]}>{summary.present}</Text>
            <Text style={styles.summaryLabel}>Present</Text>
          </View>
          <View style={[styles.summaryItem, { backgroundColor: colors.absent + '15' }]}>
            <Text style={[styles.summaryValue, { color: colors.absent }]}>{summary.absent}</Text>
            <Text style={styles.summaryLabel}>Absent</Text>
          </View>
          <View style={[styles.summaryItem, { backgroundColor: colors.late + '15' }]}>
            <Text style={[styles.summaryValue, { color: colors.late }]}>{summary.late}</Text>
            <Text style={styles.summaryLabel}>Late</Text>
          </View>
          <View style={[styles.summaryItem, { backgroundColor: colors.primary + '15' }]}>
            <Text style={[styles.summaryValue, { color: colors.primary }]}>{summary.percentage}%</Text>
            <Text style={styles.summaryLabel}>Rate</Text>
          </View>
        </View>
      )}

      {/* Calendar */}
      <View style={styles.calendarCard}>
        <View style={styles.calendarHeader}>
          {DAYS_HEADER.map((day) => (
            <View key={day} style={styles.calendarCell}>
              <Text style={styles.calendarHeaderText}>{day}</Text>
            </View>
          ))}
        </View>
        <View style={styles.calendarGrid}>{renderCalendar()}</View>
      </View>

      {/* Legend */}
      <View style={styles.legend}>
        {[
          { label: 'Present', color: colors.present },
          { label: 'Absent', color: colors.absent },
          { label: 'Late', color: colors.late },
          { label: 'Excused', color: colors.excused },
          { label: 'Half Day', color: colors.halfDay },
          { label: 'Holiday', color: colors.holiday },
        ].map((item) => (
          <View key={item.label} style={styles.legendItem}>
            <View style={[styles.legendDot, { backgroundColor: item.color }]} />
            <Text style={styles.legendText}>{item.label}</Text>
          </View>
        ))}
      </View>

      <View style={{ height: spacing.xxl }} />
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
  headerTitle: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
    color: colors.white,
  },
  childRow: {
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.lg,
  },
  childChip: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: borderRadius.full,
    backgroundColor: colors.surface,
    marginRight: spacing.sm,
    ...shadows.sm,
  },
  childChipActive: {
    backgroundColor: colors.primary,
  },
  childChipText: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
    color: colors.textSecondary,
  },
  childChipTextActive: {
    color: colors.white,
  },
  monthSelector: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
  },
  monthArrow: {
    padding: spacing.sm,
  },
  monthArrowText: {
    fontSize: fontSize.xxl,
    color: colors.primary,
    fontWeight: fontWeight.bold,
  },
  monthLabel: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  summaryRow: {
    flexDirection: 'row',
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
    gap: spacing.sm,
  },
  summaryItem: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: spacing.sm,
    borderRadius: borderRadius.md,
  },
  summaryValue: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
  },
  summaryLabel: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 2,
  },
  calendarCard: {
    backgroundColor: colors.surface,
    marginHorizontal: spacing.lg,
    borderRadius: borderRadius.lg,
    padding: spacing.md,
    ...shadows.md,
  },
  calendarHeader: {
    flexDirection: 'row',
    marginBottom: spacing.sm,
  },
  calendarHeaderText: {
    fontSize: fontSize.xs,
    fontWeight: fontWeight.semibold,
    color: colors.textMuted,
    textAlign: 'center',
  },
  calendarGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  calendarCell: {
    width: '14.28%',
    alignItems: 'center',
    marginBottom: spacing.sm,
  },
  dayCircle: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  dayCircleEmpty: {
    backgroundColor: 'transparent',
  },
  dayText: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
  },
  legend: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: spacing.lg,
    marginTop: spacing.md,
    gap: spacing.md,
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  legendDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginRight: spacing.xs,
  },
  legendText: {
    fontSize: fontSize.xs,
    color: colors.textSecondary,
  },
});
