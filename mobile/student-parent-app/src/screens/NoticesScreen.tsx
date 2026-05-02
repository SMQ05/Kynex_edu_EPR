/**
 * NoticesScreen — School Announcements
 *
 * Shows published school notices in a card list with expandable content.
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';
import { noticesApi } from '../services/api';
import type { Notice } from '../types';

export default function NoticesScreen() {
  const [notices, setNotices] = useState<Notice[]>([]);
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  const loadNotices = useCallback(async (pageNum: number, refresh = false) => {
    try {
      const res = await noticesApi.list({ page: pageNum, per_page: 15 });
      const data = res.data.data || res.data;
      const items: Notice[] = Array.isArray(data) ? data : data.data || [];

      if (refresh) {
        setNotices(items);
      } else {
        setNotices((prev) => [...prev, ...items]);
      }

      const meta = res.data.meta;
      if (meta) {
        setHasMore(meta.current_page < meta.last_page);
      } else {
        setHasMore(items.length >= 15);
      }
    } catch (err) {
      console.error('Failed to load notices', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadNotices(1, true);
  }, [loadNotices]);

  const onRefresh = () => {
    setRefreshing(true);
    setPage(1);
    loadNotices(1, true);
  };

  const onEndReached = () => {
    if (hasMore && !loading) {
      const nextPage = page + 1;
      setPage(nextPage);
      loadNotices(nextPage);
    }
  };

  const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PK', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  };

  const getTimeAgo = (dateStr: string) => {
    const now = new Date();
    const d = new Date(dateStr);
    const diffMs = now.getTime() - d.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return formatDate(dateStr);
  };

  const renderNotice = ({ item }: { item: Notice }) => {
    const isExpanded = expandedId === item.id;

    return (
      <TouchableOpacity
        style={styles.noticeCard}
        onPress={() => setExpandedId(isExpanded ? null : item.id)}
        activeOpacity={0.8}
      >
        <View style={styles.noticeHeader}>
          <View style={styles.noticeIcon}>
            <Text style={styles.noticeIconText}>📢</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.noticeTitle} numberOfLines={isExpanded ? undefined : 2}>
              {item.title}
            </Text>
            <Text style={styles.noticeTime}>{getTimeAgo(item.published_at)}</Text>
          </View>
        </View>

        {isExpanded && (
          <View style={styles.noticeBody}>
            <Text style={styles.noticeContent}>{item.content}</Text>
            <Text style={styles.noticeDateFull}>
              Published: {formatDate(item.published_at)}
              {item.expires_at && ` • Expires: ${formatDate(item.expires_at)}`}
            </Text>
          </View>
        )}

        {!isExpanded && (
          <Text style={styles.noticePreview} numberOfLines={2}>
            {item.content}
          </Text>
        )}
      </TouchableOpacity>
    );
  };

  if (loading && notices.length === 0) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>📢 Notices</Text>
        <Text style={styles.headerSubtitle}>School Announcements</Text>
      </View>

      <FlatList
        data={notices}
        keyExtractor={(item) => item.id}
        renderItem={renderNotice}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        onEndReached={onEndReached}
        onEndReachedThreshold={0.3}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyEmoji}>📭</Text>
            <Text style={styles.emptyText}>No notices published yet</Text>
          </View>
        }
        ListFooterComponent={
          hasMore && notices.length > 0 ? (
            <ActivityIndicator style={{ padding: spacing.lg }} color={colors.primary} />
          ) : null
        }
      />
    </View>
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
  headerSubtitle: {
    fontSize: fontSize.sm,
    color: 'rgba(255,255,255,0.8)',
    marginTop: 2,
  },
  listContent: {
    padding: spacing.lg,
    paddingBottom: spacing.xxl,
  },
  noticeCard: {
    backgroundColor: colors.surface,
    borderRadius: borderRadius.lg,
    padding: spacing.lg,
    marginBottom: spacing.md,
    ...shadows.md,
  },
  noticeHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  noticeIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: colors.primary + '15',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: spacing.md,
  },
  noticeIconText: {
    fontSize: 20,
  },
  noticeTitle: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
    lineHeight: 22,
  },
  noticeTime: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 4,
  },
  noticePreview: {
    fontSize: fontSize.sm,
    color: colors.textSecondary,
    lineHeight: 20,
    marginTop: spacing.sm,
    paddingLeft: 52,
  },
  noticeBody: {
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  noticeContent: {
    fontSize: fontSize.sm,
    color: colors.textPrimary,
    lineHeight: 22,
  },
  noticeDateFull: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: spacing.md,
  },
  emptyContainer: {
    padding: spacing.xxl,
    alignItems: 'center',
  },
  emptyEmoji: {
    fontSize: 48,
    marginBottom: spacing.md,
  },
  emptyText: {
    fontSize: fontSize.md,
    color: colors.textMuted,
  },
});
