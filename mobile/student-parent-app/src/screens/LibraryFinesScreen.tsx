/**
 * LibraryFinesScreen — Student/Parent App
 *
 * Lists student's currently issued books with due dates and fines.
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  SafeAreaView,
} from 'react-native';
import { TouchableOpacity } from 'react-native';
import { libraryStudentApi } from '../services/api';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface LibraryIssue {
  id: string;
  book_title: string;
  issue_date: string;
  due_date: string;
  fine_amount?: string | null;
  status?: string;
}

// ── Helpers ────────────────────────────────────────────────────────────

function isOverdue(dueDateStr: string): boolean {
  return new Date(dueDateStr) < new Date();
}

function formatDate(dateStr: string): string {
  try {
    return new Date(dateStr).toLocaleDateString('en-PK', {
      day: 'numeric', month: 'short', year: 'numeric',
    });
  } catch {
    return dateStr;
  }
}

// ── Component ──────────────────────────────────────────────────────────

export default function LibraryFinesScreen() {
  const [issues, setIssues] = useState<LibraryIssue[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchIssues = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);
    try {
      const response = await libraryStudentApi.myIssues();
      setIssues(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Failed to load library issues.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchIssues();
  }, [fetchIssues]);

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading library…</Text>
      </View>
    );
  }

  if (error && issues.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchIssues()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Library</Text>
        <View style={styles.countBadge}>
          <Text style={styles.countText}>{issues.length} Books Issued</Text>
        </View>
      </View>

      <FlatList
        data={issues}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchIssues(true)}
            colors={[colors.primary]}
          />
        }
        renderItem={({ item }) => {
          const overdue = isOverdue(item.due_date);
          const hasFine = item.fine_amount && Number(item.fine_amount) > 0;
          return (
            <View style={[styles.card, overdue && styles.cardOverdue]}>
              <Text style={styles.bookTitle}>{item.book_title}</Text>
              <View style={styles.dateRow}>
                <Text style={styles.dateLabel}>Issued: {formatDate(item.issue_date)}</Text>
                <Text style={[styles.dateLabel, overdue && styles.overdueDateText]}>
                  Due: {formatDate(item.due_date)}
                </Text>
              </View>
              {hasFine && (
                <View style={styles.fineRow}>
                  <Text style={styles.fineLabel}>Fine:</Text>
                  <Text style={styles.fineAmount}>PKR {item.fine_amount}</Text>
                </View>
              )}
              {overdue && !hasFine && (
                <Text style={styles.overdueLabel}>Overdue — please return</Text>
              )}
            </View>
          );
        }}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>📚</Text>
            <Text style={styles.emptyText}>No books currently issued.</Text>
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
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: spacing.md, paddingTop: spacing.md, paddingBottom: spacing.sm,
    backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  headerTitle: { fontSize: fontSize.xl, fontWeight: fontWeight.bold, color: colors.textPrimary },
  countBadge: {
    backgroundColor: colors.primary + '15', paddingHorizontal: spacing.sm,
    paddingVertical: 3, borderRadius: borderRadius.full,
  },
  countText: { fontSize: fontSize.xs, color: colors.primary, fontWeight: fontWeight.semibold },
  list: { padding: spacing.md },
  card: {
    backgroundColor: colors.white, borderRadius: borderRadius.md,
    padding: spacing.md, marginBottom: spacing.sm, ...shadows.sm,
  },
  cardOverdue: { borderLeftWidth: 3, borderLeftColor: colors.danger },
  bookTitle: { fontSize: fontSize.md, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  dateRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: spacing.xs },
  dateLabel: { fontSize: fontSize.xs, color: colors.textMuted },
  overdueDateText: { color: colors.danger, fontWeight: fontWeight.semibold },
  fineRow: { flexDirection: 'row', alignItems: 'center', marginTop: spacing.sm, backgroundColor: '#fef2f2', padding: spacing.sm, borderRadius: borderRadius.sm },
  fineLabel: { fontSize: fontSize.sm, color: colors.danger, marginRight: spacing.xs },
  fineAmount: { fontSize: fontSize.sm, fontWeight: fontWeight.bold, color: colors.danger },
  overdueLabel: { fontSize: fontSize.xs, color: colors.danger, fontWeight: fontWeight.semibold, marginTop: spacing.xs },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
