/**
 * NotificationCenterScreen — Student/Parent App
 *
 * Infinite scroll list of notifications grouped by date.
 * Tap to mark read and deep link navigate. "Mark all read" in header.
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
import * as Linking from 'expo-linking';
import { notificationsApi } from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface Notification {
  id: string;
  title: string;
  body: string;
  type?: string;
  is_read: boolean;
  action_url?: string | null;
  created_at: string;
}

// ── Helpers ────────────────────────────────────────────────────────────

function timeAgo(dateStr: string): string {
  const now = new Date();
  const d = new Date(dateStr);
  const diffMs = now.getTime() - d.getTime();
  const diffMin = Math.floor(diffMs / 60000);
  if (diffMin < 1) return 'Just now';
  if (diffMin < 60) return `${diffMin}m ago`;
  const diffH = Math.floor(diffMin / 60);
  if (diffH < 24) return `${diffH}h ago`;
  const diffD = Math.floor(diffH / 24);
  if (diffD < 7) return `${diffD}d ago`;
  return d.toLocaleDateString('en-PK', { day: 'numeric', month: 'short' });
}

function getDateGroup(dateStr: string): string {
  const d = new Date(dateStr);
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 1);
  const itemDate = new Date(d.getFullYear(), d.getMonth(), d.getDate());

  if (itemDate.getTime() === today.getTime()) return 'Today';
  if (itemDate.getTime() === yesterday.getTime()) return 'Yesterday';
  return 'Earlier';
}

function getTypeIcon(type?: string): string {
  const icons: Record<string, string> = {
    fee: '💰', attendance: '📊', exam: '📝', notice: '📢',
    homework: '📚', result: '🏆', general: '🔔',
  };
  return icons[type ?? ''] ?? '🔔';
}

// ── Component ──────────────────────────────────────────────────────────

export default function NotificationCenterScreen() {
  const { setUnreadCount } = useAuthStore();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);

  // ── Fetch ───────────────────────────────────────────────────────────

  const fetchNotifications = useCallback(async (pageNum: number, refresh = false) => {
    if (refresh) { setIsRefreshing(true); setPage(1); }
    else if (pageNum === 1) setIsLoading(true);
    else setLoadingMore(true);
    setError(null);

    try {
      const response = await notificationsApi.list({ page: refresh ? 1 : pageNum, per_page: 20 });
      const data: Notification[] = response.data?.data ?? response.data ?? [];
      const meta = response.data?.meta;

      if (refresh || pageNum === 1) {
        setNotifications(data);
      } else {
        setNotifications((prev) => [...prev, ...data]);
      }

      setHasMore(meta ? meta.current_page < meta.last_page : data.length === 20);

      const unreadCount = (refresh || pageNum === 1 ? data : [...notifications, ...data])
        .filter((n) => !n.is_read).length;
      setUnreadCount(unreadCount);
    } catch {
      setError('Failed to load notifications.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
      setLoadingMore(false);
    }
  }, [notifications, setUnreadCount]);

  useEffect(() => {
    fetchNotifications(1);
  }, []);

  // ── Mark Read ───────────────────────────────────────────────────────

  const markRead = async (notification: Notification) => {
    if (!notification.is_read) {
      try {
        await notificationsApi.markRead(notification.id);
        setNotifications((prev) =>
          prev.map((n) => n.id === notification.id ? { ...n, is_read: true } : n),
        );
        setUnreadCount(
          notifications.filter((n) => !n.is_read && n.id !== notification.id).length,
        );
      } catch {
        // Silent fail
      }
    }

    if (notification.action_url) {
      try {
        await Linking.openURL(notification.action_url);
      } catch {
        // URL may not be handled
      }
    }
  };

  const markAllRead = async () => {
    try {
      await notificationsApi.markAllRead();
      setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
      setUnreadCount(0);
    } catch {
      // Silent fail
    }
  };

  const loadMore = () => {
    if (hasMore && !loadingMore && !isLoading) {
      const nextPage = page + 1;
      setPage(nextPage);
      fetchNotifications(nextPage);
    }
  };

  // ── Group Data ──────────────────────────────────────────────────────

  type ListItem = { type: 'header'; title: string } | { type: 'notification'; data: Notification };

  const groupedData: ListItem[] = [];
  let lastGroup = '';
  notifications.forEach((n) => {
    const group = getDateGroup(n.created_at);
    if (group !== lastGroup) {
      groupedData.push({ type: 'header', title: group });
      lastGroup = group;
    }
    groupedData.push({ type: 'notification', data: n });
  });

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading && notifications.length === 0) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading notifications…</Text>
      </View>
    );
  }

  if (error && notifications.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchNotifications(1)}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  const unreadTotal = notifications.filter((n) => !n.is_read).length;

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <View style={{ flex: 1 }}>
          <Text style={styles.headerTitle}>Notifications</Text>
          {unreadTotal > 0 && (
            <Text style={styles.headerSub}>{unreadTotal} unread</Text>
          )}
        </View>
        {unreadTotal > 0 && (
          <TouchableOpacity onPress={markAllRead}>
            <Text style={styles.markAllText}>Mark all read</Text>
          </TouchableOpacity>
        )}
      </View>

      <FlatList
        data={groupedData}
        keyExtractor={(item, index) =>
          item.type === 'header' ? `header-${item.title}` : item.data.id
        }
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchNotifications(1, true)}
            colors={[colors.primary]}
          />
        }
        onEndReached={loadMore}
        onEndReachedThreshold={0.3}
        renderItem={({ item }) => {
          if (item.type === 'header') {
            return <Text style={styles.groupHeader}>{item.title}</Text>;
          }
          const n = item.data;
          return (
            <TouchableOpacity
              style={[styles.notifCard, !n.is_read && styles.notifCardUnread]}
              onPress={() => markRead(n)}
              activeOpacity={0.75}
            >
              <Text style={styles.notifIcon}>{getTypeIcon(n.type)}</Text>
              <View style={styles.notifContent}>
                <View style={styles.notifTitleRow}>
                  <Text style={[styles.notifTitle, !n.is_read && styles.notifTitleUnread]} numberOfLines={1}>
                    {n.title}
                  </Text>
                  {!n.is_read && <View style={styles.unreadDot} />}
                </View>
                <Text style={styles.notifBody} numberOfLines={2}>{n.body}</Text>
                <Text style={styles.notifTime}>{timeAgo(n.created_at)}</Text>
              </View>
            </TouchableOpacity>
          );
        }}
        ListFooterComponent={
          loadingMore ? (
            <ActivityIndicator size="small" color={colors.primary} style={{ padding: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>🔔</Text>
            <Text style={styles.emptyText}>No notifications yet.</Text>
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
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: spacing.md, paddingTop: spacing.md, paddingBottom: spacing.sm,
    backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  headerTitle: { fontSize: fontSize.xl, fontWeight: fontWeight.bold, color: colors.textPrimary },
  headerSub: { fontSize: fontSize.sm, color: colors.textMuted, marginTop: 2 },
  markAllText: { fontSize: fontSize.sm, color: colors.primary, fontWeight: fontWeight.semibold },
  list: { padding: spacing.md },
  groupHeader: {
    fontSize: fontSize.xs, fontWeight: fontWeight.semibold, color: colors.textMuted,
    textTransform: 'uppercase', letterSpacing: 0.5,
    marginTop: spacing.md, marginBottom: spacing.xs,
  },
  notifCard: {
    flexDirection: 'row', backgroundColor: colors.white, borderRadius: borderRadius.md,
    padding: spacing.md, marginBottom: spacing.xs, ...shadows.sm,
  },
  notifCardUnread: { backgroundColor: colors.primary + '08', borderLeftWidth: 3, borderLeftColor: colors.primary },
  notifIcon: { fontSize: 20, marginRight: spacing.sm, marginTop: 2 },
  notifContent: { flex: 1 },
  notifTitleRow: { flexDirection: 'row', alignItems: 'center' },
  notifTitle: { flex: 1, fontSize: fontSize.sm, fontWeight: fontWeight.medium, color: colors.textPrimary },
  notifTitleUnread: { fontWeight: fontWeight.semibold },
  unreadDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: colors.primary, marginLeft: spacing.xs },
  notifBody: { fontSize: fontSize.xs, color: colors.textSecondary, marginTop: 2, lineHeight: 17 },
  notifTime: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 4 },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
