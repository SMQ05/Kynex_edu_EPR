/**
 * DashboardScreen — Management App main dashboard.
 *
 * Displays overview stats cards: students, staff, today's attendance,
 * fee collection, and recent notices. Designed for school administrators.
 */
import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
} from 'react-native';
import { useAuthStore } from '../../src/stores/authStore';
import { studentsApi, attendanceApi, noticesApi } from '../../src/services/api';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../../src/theme';

interface StatCard {
  label: string;
  value: string | number;
  icon: string;
  color: string;
}

export default function DashboardScreen() {
  const { user, schoolName, logout } = useAuthStore();
  const [refreshing, setRefreshing] = useState(false);
  const [stats, setStats] = useState<StatCard[]>([]);
  const [notices, setNotices] = useState<any[]>([]);

  const fetchData = async () => {
    try {
      // Fetch dashboard data in parallel
      const [studentsRes, attendanceRes, noticesRes] = await Promise.allSettled([
        studentsApi.list({ per_page: 1 }),
        attendanceApi.summary({ date: new Date().toISOString().split('T')[0] }),
        noticesApi.list({ per_page: 5 }),
      ]);

      const studentCount =
        studentsRes.status === 'fulfilled' ? studentsRes.value.data?.meta?.total ?? 0 : 0;
      const attendancePct =
        attendanceRes.status === 'fulfilled'
          ? studentsRes.value.data?.data?.percentage ?? 0
          : 0;

      setStats([
        { label: 'Total Students', value: studentCount, icon: '👨‍🎓', color: colors.primary },
        { label: "Today's Attendance", value: `${attendancePct}%`, icon: '📊', color: colors.success },
        { label: 'Fee Collection', value: 'PKR —', icon: '💰', color: colors.warning },
        { label: 'Active Staff', value: '—', icon: '👩‍🏫', color: colors.info },
      ]);

      if (noticesRes.status === 'fulfilled') {
        setNotices(noticesRes.value.data?.data ?? []);
      }
    } catch (err) {
      console.error('Dashboard fetch error:', err);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Welcome back,</Text>
          <Text style={styles.userName}>{user?.name ?? 'Admin'}</Text>
          <Text style={styles.schoolName}>{schoolName}</Text>
        </View>
        <TouchableOpacity style={styles.logoutButton} onPress={logout}>
          <Text style={styles.logoutText}>Logout</Text>
        </TouchableOpacity>
      </View>

      {/* Stats Grid */}
      <View style={styles.statsGrid}>
        {stats.map((stat, index) => (
          <View key={index} style={styles.statCard}>
            <Text style={styles.statIcon}>{stat.icon}</Text>
            <Text style={[styles.statValue, { color: stat.color }]}>{stat.value}</Text>
            <Text style={styles.statLabel}>{stat.label}</Text>
          </View>
        ))}
      </View>

      {/* Quick Actions */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Quick Actions</Text>
        <View style={styles.actionsGrid}>
          {[
            { label: 'Mark Attendance', icon: '✅', screen: 'attendance' },
            { label: 'Students', icon: '👨‍🎓', screen: 'students' },
            { label: 'Fee Collection', icon: '💳', screen: 'fees' },
            { label: 'Notices', icon: '📢', screen: 'notices' },
            { label: 'Timetable', icon: '📅', screen: 'timetable' },
            { label: 'Reports', icon: '📈', screen: 'reports' },
          ].map((action, index) => (
            <TouchableOpacity key={index} style={styles.actionCard}>
              <Text style={styles.actionIcon}>{action.icon}</Text>
              <Text style={styles.actionLabel}>{action.label}</Text>
            </TouchableOpacity>
          ))}
        </View>
      </View>

      {/* Recent Notices */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Recent Notices</Text>
        {notices.length === 0 ? (
          <Text style={styles.emptyText}>No recent notices</Text>
        ) : (
          notices.map((notice: any, index: number) => (
            <View key={notice.id ?? index} style={styles.noticeCard}>
              <Text style={styles.noticeTitle}>{notice.title}</Text>
              <Text style={styles.noticeDate}>{notice.published_at ?? ''}</Text>
              <Text style={styles.noticeContent} numberOfLines={2}>
                {notice.content ?? ''}
              </Text>
            </View>
          ))
        )}
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
  header: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xxl + spacing.md,
    paddingBottom: spacing.lg,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  greeting: {
    fontSize: fontSize.sm,
    color: 'rgba(255,255,255,0.8)',
  },
  userName: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
    color: colors.white,
  },
  schoolName: {
    fontSize: fontSize.sm,
    color: 'rgba(255,255,255,0.7)',
    marginTop: 2,
  },
  logoutButton: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: borderRadius.sm,
    marginTop: spacing.sm,
  },
  logoutText: {
    color: colors.white,
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    padding: spacing.md,
    gap: spacing.sm,
  },
  statCard: {
    width: '48%',
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    alignItems: 'center',
    ...shadows.sm,
  },
  statIcon: {
    fontSize: 28,
    marginBottom: spacing.xs,
  },
  statValue: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
  },
  statLabel: {
    fontSize: fontSize.xs,
    color: colors.textSecondary,
    marginTop: 2,
  },
  section: {
    paddingHorizontal: spacing.md,
    marginTop: spacing.md,
  },
  sectionTitle: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
    marginBottom: spacing.sm,
  },
  actionsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  actionCard: {
    width: '31%',
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    alignItems: 'center',
    ...shadows.sm,
  },
  actionIcon: {
    fontSize: 24,
    marginBottom: spacing.xs,
  },
  actionLabel: {
    fontSize: fontSize.xs,
    color: colors.textPrimary,
    textAlign: 'center',
    fontWeight: fontWeight.medium,
  },
  noticeCard: {
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
    ...shadows.sm,
  },
  noticeTitle: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  noticeDate: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 2,
  },
  noticeContent: {
    fontSize: fontSize.sm,
    color: colors.textSecondary,
    marginTop: spacing.xs,
  },
  emptyText: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    textAlign: 'center',
    paddingVertical: spacing.lg,
  },
});
