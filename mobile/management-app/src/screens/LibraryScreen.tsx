/**
 * LibraryScreen — Management App
 *
 * View currently issued and overdue books. Return books with confirmation.
 * Restricted to LIBRARIAN, SCHOOL_ADMIN, INSTITUTE_OWNER.
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
  Alert,
  SafeAreaView,
  ScrollView,
} from 'react-native';
import { libraryApi } from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface LibraryIssue {
  id: string;
  book_title: string;
  borrower_name: string;
  issue_date: string;
  due_date: string;
  fine_amount?: string | null;
  status?: string;
}

type LibTab = 'issued' | 'overdue';

const ALLOWED_ROLES = ['LIBRARIAN', 'SCHOOL_ADMIN', 'INSTITUTE_OWNER'];

// ── Component ──────────────────────────────────────────────────────────

export default function LibraryScreen() {
  const { user } = useAuthStore();
  const [activeTab, setActiveTab] = useState<LibTab>('issued');
  const [issued, setIssued] = useState<LibraryIssue[]>([]);
  const [overdue, setOverdue] = useState<LibraryIssue[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [returning, setReturning] = useState<string | null>(null);

  // ── Role Check ──────────────────────────────────────────────────────

  if (!ALLOWED_ROLES.some((r) => (user?.roles ?? []).includes(r))) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyIcon}>🔒</Text>
        <Text style={styles.emptyText}>You don't have permission to manage library.</Text>
      </View>
    );
  }

  // ── Fetch ───────────────────────────────────────────────────────────

  const fetchData = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);
    try {
      const [issuedRes, overdueRes] = await Promise.allSettled([
        libraryApi.issues({ status: 'issued' }),
        libraryApi.overdue(),
      ]);
      if (issuedRes.status === 'fulfilled') {
        setIssued(issuedRes.value.data?.data ?? issuedRes.value.data ?? []);
      }
      if (overdueRes.status === 'fulfilled') {
        setOverdue(overdueRes.value.data?.data ?? overdueRes.value.data ?? []);
      }
      if (issuedRes.status === 'rejected' && overdueRes.status === 'rejected') {
        setError('Failed to load library data.');
      }
    } catch {
      setError('Failed to load library data.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // ── Return Book ─────────────────────────────────────────────────────

  const handleReturn = (issue: LibraryIssue) => {
    Alert.alert(
      'Return Book',
      `Confirm return of "${issue.book_title}" from ${issue.borrower_name}?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Return',
          onPress: async () => {
            setReturning(issue.id);
            try {
              await libraryApi.returnBook(issue.id);
              Alert.alert('Success', 'Book returned successfully.');
              fetchData(true);
            } catch {
              Alert.alert('Error', 'Failed to process return.');
            } finally {
              setReturning(null);
            }
          },
        },
      ],
    );
  };

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading library…</Text>
      </View>
    );
  }

  if (error && issued.length === 0 && overdue.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchData()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  const currentData = activeTab === 'issued' ? issued : overdue;
  const tabs: { key: LibTab; label: string }[] = [
    { key: 'issued', label: `Currently Issued (${issued.length})` },
    { key: 'overdue', label: `Overdue (${overdue.length})` },
  ];

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Library</Text>
        <Text style={styles.headerSub}>Manage book issues and returns</Text>
      </View>

      {/* Tabs */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={styles.tabBar}
        contentContainerStyle={styles.tabBarContent}
      >
        {tabs.map((tab) => (
          <TouchableOpacity
            key={tab.key}
            style={[styles.tab, activeTab === tab.key && styles.tabActive]}
            onPress={() => setActiveTab(tab.key)}
          >
            <Text style={[styles.tabText, activeTab === tab.key && styles.tabTextActive]}>
              {tab.label}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <FlatList
        data={currentData}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchData(true)}
            colors={[colors.primary]}
          />
        }
        renderItem={({ item }) => {
          const isOverdue = activeTab === 'overdue' || (item.fine_amount && Number(item.fine_amount) > 0);
          return (
            <View style={styles.card}>
              <Text style={styles.cardTitle}>{item.book_title}</Text>
              <Text style={styles.cardMeta}>Borrower: {item.borrower_name}</Text>
              <View style={styles.dateRow}>
                <Text style={styles.dateLabel}>Issued: {item.issue_date}</Text>
                <Text style={[styles.dateLabel, isOverdue && styles.overdueText]}>
                  Due: {item.due_date}
                </Text>
              </View>
              {item.fine_amount && Number(item.fine_amount) > 0 && (
                <Text style={styles.fineText}>Fine: PKR {item.fine_amount}</Text>
              )}
              <TouchableOpacity
                style={[styles.returnBtn, returning === item.id && { opacity: 0.7 }]}
                onPress={() => handleReturn(item)}
                disabled={returning === item.id}
              >
                {returning === item.id ? (
                  <ActivityIndicator size="small" color={colors.white} />
                ) : (
                  <Text style={styles.returnBtnText}>Return</Text>
                )}
              </TouchableOpacity>
            </View>
          );
        }}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>📚</Text>
            <Text style={styles.emptyText}>
              {activeTab === 'issued' ? 'No books currently issued.' : 'No overdue books.'}
            </Text>
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
  tabBar: { backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.border, maxHeight: 48 },
  tabBarContent: { paddingHorizontal: spacing.sm, alignItems: 'center' },
  tab: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, marginRight: 4, borderRadius: borderRadius.full },
  tabActive: { backgroundColor: colors.primary + '15' },
  tabText: { fontSize: fontSize.sm, color: colors.textSecondary, fontWeight: fontWeight.medium },
  tabTextActive: { color: colors.primary, fontWeight: fontWeight.semibold },
  list: { padding: spacing.md },
  card: { backgroundColor: colors.white, borderRadius: borderRadius.md, padding: spacing.md, marginBottom: spacing.sm, ...shadows.sm },
  cardTitle: { fontSize: fontSize.md, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  cardMeta: { fontSize: fontSize.sm, color: colors.textSecondary, marginTop: 2 },
  dateRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: spacing.xs },
  dateLabel: { fontSize: fontSize.xs, color: colors.textMuted },
  overdueText: { color: colors.danger, fontWeight: fontWeight.semibold },
  fineText: { fontSize: fontSize.sm, color: colors.danger, fontWeight: fontWeight.semibold, marginTop: spacing.xs },
  returnBtn: { backgroundColor: colors.primary, borderRadius: borderRadius.sm, paddingVertical: spacing.sm, alignItems: 'center', marginTop: spacing.sm },
  returnBtnText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
