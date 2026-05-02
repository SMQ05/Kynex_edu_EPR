/**
 * OnlineClassScreen — Student/Parent App
 *
 * List upcoming online classes for the student's class/section.
 * Join via browser. Platform badges and countdown timer.
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
} from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { onlineClassesApi } from '../services/api';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface OnlineClass {
  id: string;
  subject_name: string;
  class_name?: string;
  section_name?: string;
  platform?: string;
  meeting_url?: string;
  scheduled_at: string;
  duration_minutes?: number;
  status: 'upcoming' | 'live' | 'ended';
}

// ── Helpers ────────────────────────────────────────────────────────────

function formatDateTime(dateStr: string): string {
  try {
    const d = new Date(dateStr);
    return d.toLocaleString('en-PK', {
      weekday: 'short', day: 'numeric', month: 'short',
      hour: '2-digit', minute: '2-digit',
    });
  } catch {
    return dateStr;
  }
}

function getCountdown(dateStr: string): string {
  const now = new Date();
  const target = new Date(dateStr);
  const diffMs = target.getTime() - now.getTime();
  if (diffMs <= 0) return '';
  const diffMin = Math.floor(diffMs / 60000);
  if (diffMin < 60) return `Starts in ${diffMin}m`;
  const diffH = Math.floor(diffMin / 60);
  if (diffH < 24) return `Starts in ${diffH}h ${diffMin % 60}m`;
  const diffD = Math.floor(diffH / 24);
  return `Starts in ${diffD}d`;
}

function getStatusConfig(status: string) {
  switch (status) {
    case 'live':
      return { label: 'Live Now', color: colors.danger, bg: '#fef2f2' };
    case 'ended':
      return { label: 'Ended', color: colors.textMuted, bg: '#f1f5f9' };
    default:
      return { label: 'Upcoming', color: colors.info, bg: '#e0f2fe' };
  }
}

// ── Component ──────────────────────────────────────────────────────────

export default function OnlineClassScreen() {
  const [classes, setClasses] = useState<OnlineClass[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchClasses = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);
    try {
      const response = await onlineClassesApi.myUpcoming();
      setClasses(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Failed to load online classes.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchClasses();
  }, [fetchClasses]);

  const joinClass = async (cls: OnlineClass) => {
    if (cls.meeting_url) {
      await WebBrowser.openBrowserAsync(cls.meeting_url);
    }
  };

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading classes…</Text>
      </View>
    );
  }

  if (error && classes.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchClasses()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Online Classes</Text>
        <Text style={styles.headerSub}>{classes.length} upcoming</Text>
      </View>

      <FlatList
        data={classes}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl refreshing={isRefreshing} onRefresh={() => fetchClasses(true)} colors={[colors.primary]} />
        }
        renderItem={({ item }) => {
          const statusConfig = getStatusConfig(item.status);
          const countdown = getCountdown(item.scheduled_at);

          return (
            <View style={styles.card}>
              <View style={styles.cardTop}>
                <View style={styles.subjectBadge}>
                  <Text style={styles.subjectText}>{item.subject_name}</Text>
                </View>
                <View style={[styles.statusBadge, { backgroundColor: statusConfig.bg }]}>
                  <Text style={[styles.statusText, { color: statusConfig.color }]}>
                    {statusConfig.label}
                  </Text>
                </View>
              </View>

              <Text style={styles.dateText}>{formatDateTime(item.scheduled_at)}</Text>
              {item.duration_minutes && (
                <Text style={styles.durationText}>{item.duration_minutes} minutes</Text>
              )}
              {countdown && (
                <Text style={styles.countdownText}>{countdown}</Text>
              )}

              {item.platform && (
                <View style={styles.platformBadge}>
                  <Text style={styles.platformText}>{item.platform}</Text>
                </View>
              )}

              {item.meeting_url && item.status !== 'ended' && (
                <TouchableOpacity style={styles.joinBtn} onPress={() => joinClass(item)}>
                  <Text style={styles.joinBtnText}>
                    {item.status === 'live' ? 'Join Now' : 'Join Class'}
                  </Text>
                </TouchableOpacity>
              )}
            </View>
          );
        }}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>🖥️</Text>
            <Text style={styles.emptyText}>No upcoming online classes.</Text>
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
  list: { padding: spacing.md },
  card: { backgroundColor: colors.white, borderRadius: borderRadius.md, padding: spacing.md, marginBottom: spacing.sm, ...shadows.sm },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.xs },
  subjectBadge: { backgroundColor: colors.primary + '15', paddingHorizontal: spacing.sm, paddingVertical: 3, borderRadius: borderRadius.full },
  subjectText: { fontSize: fontSize.xs, fontWeight: fontWeight.semibold, color: colors.primary },
  statusBadge: { paddingHorizontal: spacing.sm, paddingVertical: 3, borderRadius: borderRadius.full },
  statusText: { fontSize: fontSize.xs, fontWeight: fontWeight.semibold },
  dateText: { fontSize: fontSize.sm, color: colors.textPrimary, fontWeight: fontWeight.medium },
  durationText: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  countdownText: { fontSize: fontSize.sm, color: colors.primary, fontWeight: fontWeight.semibold, marginTop: spacing.xs },
  platformBadge: {
    alignSelf: 'flex-start', backgroundColor: '#f1f5f9', paddingHorizontal: spacing.sm,
    paddingVertical: 2, borderRadius: borderRadius.full, marginTop: spacing.xs,
  },
  platformText: { fontSize: fontSize.xs, color: colors.textSecondary, fontWeight: fontWeight.medium },
  joinBtn: { backgroundColor: colors.primary, borderRadius: borderRadius.sm, paddingVertical: spacing.sm, alignItems: 'center', marginTop: spacing.sm },
  joinBtnText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
