/**
 * TimetableScreen — Student/Parent App
 *
 * Day-selector tabs (Mon–Fri), shows each period with subject,
 * teacher, room, time. Highlights current period. Parent can
 * select child if multiple.
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  SafeAreaView,
  ScrollView,
} from 'react-native';
import { timetableStudentApi } from '../services/api';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface TimetableEntry {
  id: string;
  day: string;
  start_time: string;
  end_time: string;
  subject: string;
  teacher: string;
  room: string | null;
}

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
const SHORT_DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

// ── Helpers ────────────────────────────────────────────────────────────

function getCurrentDay(): string {
  const day = new Date().getDay(); // 0=Sun, 6=Sat
  if (day === 0 || day === 6) return 'Monday'; // default to Monday on weekends
  return DAYS[day - 1];
}

function isCurrentPeriod(entry: TimetableEntry): boolean {
  const now = new Date();
  const today = DAYS[now.getDay() - 1];
  if (entry.day !== today) return false;

  const [startH, startM] = entry.start_time.split(':').map(Number);
  const [endH, endM] = entry.end_time.split(':').map(Number);
  const currentMinutes = now.getHours() * 60 + now.getMinutes();
  const startMinutes = startH * 60 + startM;
  const endMinutes = endH * 60 + endM;

  return currentMinutes >= startMinutes && currentMinutes < endMinutes;
}

function isWeekend(): boolean {
  const day = new Date().getDay();
  return day === 0 || day === 6;
}

// ── Component ──────────────────────────────────────────────────────────

export default function TimetableScreen() {
  const [timetable, setTimetable] = useState<TimetableEntry[]>([]);
  const [selectedDay, setSelectedDay] = useState(getCurrentDay());
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchTimetable = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);
    try {
      const response = await timetableStudentApi.my();
      setTimetable(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Failed to load timetable.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchTimetable();
  }, [fetchTimetable]);

  const dayEntries = timetable
    .filter((e) => e.day === selectedDay)
    .sort((a, b) => a.start_time.localeCompare(b.start_time));

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading timetable…</Text>
      </View>
    );
  }

  if (error && timetable.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchTimetable()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Timetable</Text>
        {isWeekend() && (
          <Text style={styles.headerSub}>Enjoy your weekend!</Text>
        )}
      </View>

      {/* Day Tabs */}
      <ScrollView
        horizontal showsHorizontalScrollIndicator={false}
        style={styles.dayBar} contentContainerStyle={styles.dayBarContent}
      >
        {DAYS.map((day, i) => (
          <TouchableOpacity
            key={day}
            style={[styles.dayTab, selectedDay === day && styles.dayTabActive]}
            onPress={() => setSelectedDay(day)}
          >
            <Text style={[styles.dayText, selectedDay === day && styles.dayTextActive]}>
              {SHORT_DAYS[i]}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <FlatList
        data={dayEntries}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchTimetable(true)}
            colors={[colors.primary]}
          />
        }
        renderItem={({ item }) => {
          const isCurrent = isCurrentPeriod(item);
          return (
            <View style={[styles.card, isCurrent && styles.cardCurrent]}>
              <View style={styles.timeColumn}>
                <Text style={[styles.timeText, isCurrent && styles.timeCurrent]}>
                  {item.start_time}
                </Text>
                <Text style={styles.timeEnd}>{item.end_time}</Text>
              </View>
              <View style={styles.detailColumn}>
                <Text style={[styles.subjectName, isCurrent && styles.subjectCurrent]}>
                  {item.subject}
                </Text>
                <Text style={styles.teacherName}>{item.teacher}</Text>
                {item.room && (
                  <Text style={styles.roomText}>Room: {item.room}</Text>
                )}
              </View>
              {isCurrent && (
                <View style={styles.currentBadge}>
                  <Text style={styles.currentText}>Now</Text>
                </View>
              )}
            </View>
          );
        }}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>📅</Text>
            <Text style={styles.emptyText}>No classes on {selectedDay}.</Text>
          </View>
        }
      />
    </SafeAreaView>
  );
}

// ── Styles ─────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: spacing.lg, backgroundColor: colors.background },
  header: {
    paddingHorizontal: spacing.md, paddingTop: spacing.md, paddingBottom: spacing.sm,
    backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  headerTitle: { fontSize: fontSize.xl, fontWeight: fontWeight.bold, color: colors.textPrimary },
  headerSub: { fontSize: fontSize.sm, color: colors.textMuted, marginTop: 2 },
  dayBar: { backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.border, maxHeight: 52 },
  dayBarContent: { paddingHorizontal: spacing.sm, alignItems: 'center', gap: spacing.xs },
  dayTab: {
    paddingHorizontal: spacing.md, paddingVertical: spacing.sm,
    borderRadius: borderRadius.full, marginHorizontal: 2,
  },
  dayTabActive: { backgroundColor: colors.primary },
  dayText: { fontSize: fontSize.sm, color: colors.textSecondary, fontWeight: fontWeight.medium },
  dayTextActive: { color: colors.white, fontWeight: fontWeight.semibold },
  list: { padding: spacing.md },
  card: {
    flexDirection: 'row', backgroundColor: colors.white, borderRadius: borderRadius.md,
    padding: spacing.md, marginBottom: spacing.sm, ...shadows.sm,
  },
  cardCurrent: { borderLeftWidth: 3, borderLeftColor: colors.primary, backgroundColor: colors.primary + '08' },
  timeColumn: { width: 55, marginRight: spacing.md },
  timeText: { fontSize: fontSize.sm, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  timeCurrent: { color: colors.primary },
  timeEnd: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  detailColumn: { flex: 1 },
  subjectName: { fontSize: fontSize.md, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  subjectCurrent: { color: colors.primary },
  teacherName: { fontSize: fontSize.sm, color: colors.textSecondary, marginTop: 2 },
  roomText: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  currentBadge: {
    backgroundColor: colors.primary, borderRadius: borderRadius.full,
    paddingHorizontal: spacing.sm, paddingVertical: 2, alignSelf: 'flex-start',
  },
  currentText: { fontSize: fontSize.xs, color: colors.white, fontWeight: fontWeight.semibold },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
