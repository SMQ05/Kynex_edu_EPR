/**
 * HomeworkScreen.tsx — Student/Parent App
 *
 * Displays homework assignments for a selected child.
 * Features:
 *   - Filter by status: All / Pending / Submitted / Overdue
 *   - Assignment cards with subject, due date, and status badge
 *   - Pull-to-refresh
 *   - Tap to view full assignment details in a modal
 *   - Submit homework (file upload placeholder)
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Modal,
  ScrollView,
  StatusBar,
  SafeAreaView,
  Alert,
} from 'react-native';
import { homeworkApi } from '../services/api';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

type HomeworkStatus = 'pending' | 'submitted' | 'graded' | 'overdue';

interface Homework {
  id: string;
  title: string;
  description?: string | null;
  subject_name: string;
  class_name?: string | null;
  section_name?: string | null;
  due_date: string;
  assigned_date: string;
  status: HomeworkStatus;
  marks_obtained?: number | null;
  total_marks?: number | null;
  teacher_feedback?: string | null;
  submitted_at?: string | null;
  attachment_url?: string | null;
}

type FilterTab = 'all' | 'pending' | 'submitted' | 'overdue';

// ── Helpers ────────────────────────────────────────────────────────────

function formatDate(dateString: string): string {
  try {
    return new Date(dateString).toLocaleDateString('en-PK', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return dateString;
  }
}

function isOverdue(dueDateString: string, status: HomeworkStatus): boolean {
  if (status === 'submitted' || status === 'graded') return false;
  return new Date(dueDateString) < new Date();
}

function getStatusConfig(hw: Homework): { label: string; color: string; bg: string } {
  const overdue = isOverdue(hw.due_date, hw.status);
  if (overdue) {
    return { label: 'Overdue', color: colors.danger, bg: '#fef2f2' };
  }
  switch (hw.status) {
    case 'submitted':
      return { label: 'Submitted', color: colors.info, bg: '#e0f2fe' };
    case 'graded':
      return { label: 'Graded', color: colors.success, bg: '#dcfce7' };
    default:
      return { label: 'Pending', color: colors.warning, bg: '#fffbeb' };
  }
}

// ── Component ──────────────────────────────────────────────────────────

interface Props {
  studentId?: string; // passed from navigation params or auth store
}

export default function HomeworkScreen({ studentId }: Props) {
  const [homework, setHomework]       = useState<Homework[]>([]);
  const [filtered, setFiltered]       = useState<Homework[]>([]);
  const [activeTab, setActiveTab]     = useState<FilterTab>('all');
  const [isLoading, setIsLoading]     = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError]             = useState<string | null>(null);
  const [selected, setSelected]       = useState<Homework | null>(null);
  const [submitting, setSubmitting]   = useState(false);

  // ── Data Fetching ────────────────────────────────────────────────────

  const fetchHomework = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);

    try {
      const response = await homeworkApi.list(studentId ?? '', { per_page: 100 });
      const data: Homework[] = response.data?.data ?? response.data ?? [];
      setHomework(data);
    } catch {
      setError('Failed to load homework. Please try again.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, [studentId]);

  useEffect(() => {
    fetchHomework();
  }, [fetchHomework]);

  // ── Filter Logic ─────────────────────────────────────────────────────

  useEffect(() => {
    switch (activeTab) {
      case 'pending':
        setFiltered(homework.filter(
          (hw) => hw.status === 'pending' && !isOverdue(hw.due_date, hw.status)
        ));
        break;
      case 'submitted':
        setFiltered(homework.filter(
          (hw) => hw.status === 'submitted' || hw.status === 'graded'
        ));
        break;
      case 'overdue':
        setFiltered(homework.filter(
          (hw) => isOverdue(hw.due_date, hw.status)
        ));
        break;
      default:
        setFiltered(homework);
    }
  }, [homework, activeTab]);

  // ── Submit Handler ───────────────────────────────────────────────────

  const handleSubmit = async (hw: Homework) => {
    Alert.alert(
      'Submit Homework',
      `Submit "${hw.title}" for ${hw.subject_name}?\n\nNote: File upload will be available in the next update.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Submit',
          onPress: async () => {
            try {
              setSubmitting(true);
              const formData = new FormData();
              formData.append('student_id', studentId ?? '');
              await homeworkApi.submit(hw.id, formData);
              Alert.alert('Success', 'Homework submitted successfully!');
              setSelected(null);
              fetchHomework(true);
            } catch {
              Alert.alert('Error', 'Failed to submit. Please try again.');
            } finally {
              setSubmitting(false);
            }
          },
        },
      ]
    );
  };

  // ── Tab Counts ───────────────────────────────────────────────────────

  const counts = {
    all: homework.length,
    pending: homework.filter(
      (hw) => hw.status === 'pending' && !isOverdue(hw.due_date, hw.status)
    ).length,
    submitted: homework.filter(
      (hw) => hw.status === 'submitted' || hw.status === 'graded'
    ).length,
    overdue: homework.filter((hw) => isOverdue(hw.due_date, hw.status)).length,
  };

  // ── Render Card ──────────────────────────────────────────────────────

  const renderCard = ({ item }: { item: Homework }) => {
    const statusConfig = getStatusConfig(item);
    return (
      <TouchableOpacity
        style={styles.card}
        onPress={() => setSelected(item)}
        activeOpacity={0.75}
      >
        {/* Subject row */}
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

        {/* Title */}
        <Text style={styles.cardTitle} numberOfLines={2}>
          {item.title}
        </Text>

        {/* Due date row */}
        <View style={styles.cardMeta}>
          <Text style={styles.metaIcon}>📅</Text>
          <Text style={[
            styles.metaText,
            isOverdue(item.due_date, item.status) && styles.overdueDateText,
          ]}>
            Due: {formatDate(item.due_date)}
          </Text>

          {item.total_marks != null && (
            <Text style={styles.marksText}>
              {item.marks_obtained != null
                ? ` · ${item.marks_obtained}/${item.total_marks} marks`
                : ` · ${item.total_marks} marks`}
            </Text>
          )}
        </View>

        {/* Description preview */}
        {item.description ? (
          <Text style={styles.cardPreview} numberOfLines={2}>
            {item.description}
          </Text>
        ) : null}
      </TouchableOpacity>
    );
  };

  // ── Loading / Error states ────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading homework…</Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchHomework()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Main Render ───────────────────────────────────────────────────────

  const tabs: { key: FilterTab; label: string }[] = [
    { key: 'all',       label: `All (${counts.all})` },
    { key: 'pending',   label: `Pending (${counts.pending})` },
    { key: 'submitted', label: `Done (${counts.submitted})` },
    { key: 'overdue',   label: `Overdue (${counts.overdue})` },
  ];

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor={colors.background} />

      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Homework</Text>
        <Text style={styles.headerSub}>
          {counts.pending > 0
            ? `${counts.pending} assignment${counts.pending !== 1 ? 's' : ''} pending`
            : 'All caught up! 🎉'}
        </Text>
      </View>

      {/* Filter Tabs */}
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

      {/* Homework List */}
      <FlatList
        data={filtered}
        keyExtractor={(item) => item.id}
        renderItem={renderCard}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchHomework(true)}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>📚</Text>
            <Text style={styles.emptyText}>
              {activeTab === 'all'
                ? 'No homework assigned yet.'
                : `No ${activeTab} homework.`}
            </Text>
          </View>
        }
      />

      {/* Detail / Submit Modal */}
      <Modal
        visible={!!selected}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setSelected(null)}
      >
        {selected && (() => {
          const statusConfig = getStatusConfig(selected);
          const canSubmit =
            selected.status === 'pending' && !isOverdue(selected.due_date, selected.status);

          return (
            <SafeAreaView style={styles.modalContainer}>
              {/* Modal header */}
              <View style={styles.modalHeader}>
                <TouchableOpacity onPress={() => setSelected(null)} style={styles.closeBtn}>
                  <Text style={styles.closeBtnText}>✕ Close</Text>
                </TouchableOpacity>
                <View style={[styles.statusBadge, { backgroundColor: statusConfig.bg }]}>
                  <Text style={[styles.statusText, { color: statusConfig.color }]}>
                    {statusConfig.label}
                  </Text>
                </View>
              </View>

              <ScrollView style={styles.modalBody} showsVerticalScrollIndicator={false}>
                {/* Subject */}
                <View style={styles.subjectBadge}>
                  <Text style={styles.subjectText}>{selected.subject_name}</Text>
                </View>

                {/* Title */}
                <Text style={styles.modalTitle}>{selected.title}</Text>

                {/* Metadata row */}
                <View style={styles.metaRow}>
                  <View style={styles.metaItem}>
                    <Text style={styles.metaLabel}>Assigned</Text>
                    <Text style={styles.metaValue}>{formatDate(selected.assigned_date)}</Text>
                  </View>
                  <View style={styles.metaItem}>
                    <Text style={styles.metaLabel}>Due Date</Text>
                    <Text style={[
                      styles.metaValue,
                      isOverdue(selected.due_date, selected.status) && { color: colors.danger },
                    ]}>
                      {formatDate(selected.due_date)}
                    </Text>
                  </View>
                  {selected.total_marks != null && (
                    <View style={styles.metaItem}>
                      <Text style={styles.metaLabel}>Marks</Text>
                      <Text style={styles.metaValue}>
                        {selected.marks_obtained != null
                          ? `${selected.marks_obtained} / ${selected.total_marks}`
                          : `— / ${selected.total_marks}`}
                      </Text>
                    </View>
                  )}
                </View>

                {/* Description */}
                {selected.description ? (
                  <View style={styles.section}>
                    <Text style={styles.sectionLabel}>Instructions</Text>
                    <Text style={styles.sectionContent}>{selected.description}</Text>
                  </View>
                ) : null}

                {/* Teacher Feedback */}
                {selected.teacher_feedback ? (
                  <View style={[styles.section, styles.feedbackSection]}>
                    <Text style={styles.sectionLabel}>Teacher Feedback</Text>
                    <Text style={styles.feedbackContent}>{selected.teacher_feedback}</Text>
                  </View>
                ) : null}

                {/* Submitted At */}
                {selected.submitted_at ? (
                  <Text style={styles.submittedText}>
                    ✅ Submitted on {formatDate(selected.submitted_at)}
                  </Text>
                ) : null}

                {/* Submit Button */}
                {canSubmit && (
                  <TouchableOpacity
                    style={[styles.submitBtn, submitting && { opacity: 0.7 }]}
                    onPress={() => handleSubmit(selected)}
                    disabled={submitting}
                  >
                    {submitting ? (
                      <ActivityIndicator color={colors.white} />
                    ) : (
                      <Text style={styles.submitBtnText}>📤 Submit Homework</Text>
                    )}
                  </TouchableOpacity>
                )}

                <View style={{ height: spacing.xxl }} />
              </ScrollView>
            </SafeAreaView>
          );
        })()}
      </Modal>
    </SafeAreaView>
  );
}

// ── Styles ─────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.lg,
    backgroundColor: colors.background,
  },
  header: {
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
    paddingBottom: spacing.sm,
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  headerTitle: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
    color: colors.textPrimary,
  },
  headerSub: {
    fontSize: fontSize.sm,
    color: colors.textMuted,
    marginTop: 2,
  },
  tabBar: {
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    maxHeight: 48,
  },
  tabBarContent: {
    paddingHorizontal: spacing.sm,
    alignItems: 'center',
  },
  tab: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    marginRight: 4,
    borderRadius: borderRadius.full,
  },
  tabActive: {
    backgroundColor: colors.primary + '15',
  },
  tabText: {
    fontSize: fontSize.sm,
    color: colors.textSecondary,
    fontWeight: fontWeight.medium,
  },
  tabTextActive: {
    color: colors.primary,
    fontWeight: fontWeight.semibold,
  },
  list: {
    padding: spacing.md,
  },
  card: {
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
    ...shadows.sm,
  },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  subjectBadge: {
    backgroundColor: colors.primary + '15',
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    borderRadius: borderRadius.full,
  },
  subjectText: {
    fontSize: fontSize.xs,
    fontWeight: fontWeight.semibold,
    color: colors.primary,
  },
  statusBadge: {
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    borderRadius: borderRadius.full,
  },
  statusText: {
    fontSize: fontSize.xs,
    fontWeight: fontWeight.semibold,
  },
  cardTitle: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
    marginBottom: 6,
    lineHeight: 22,
  },
  cardMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
  },
  metaIcon: {
    fontSize: 12,
    marginRight: 4,
  },
  metaText: {
    fontSize: fontSize.xs,
    color: colors.textSecondary,
  },
  overdueDateText: {
    color: colors.danger,
    fontWeight: fontWeight.semibold,
  },
  marksText: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
  },
  cardPreview: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 6,
    lineHeight: 17,
  },
  emptyContainer: {
    alignItems: 'center',
    marginTop: 60,
  },
  emptyIcon: {
    fontSize: 48,
    marginBottom: spacing.md,
  },
  emptyText: {
    fontSize: fontSize.md,
    color: colors.textMuted,
    textAlign: 'center',
  },
  loadingText: {
    marginTop: spacing.sm,
    fontSize: fontSize.sm,
    color: colors.textMuted,
  },
  errorText: {
    fontSize: fontSize.md,
    color: colors.danger,
    textAlign: 'center',
    marginBottom: spacing.md,
  },
  retryBtn: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.sm,
    borderRadius: borderRadius.sm,
  },
  retryText: {
    color: colors.white,
    fontWeight: fontWeight.semibold,
    fontSize: fontSize.sm,
  },
  // ── Modal ────────────────────────────────────────────────────────────
  modalContainer: {
    flex: 1,
    backgroundColor: colors.white,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  closeBtn: {
    padding: 4,
  },
  closeBtnText: {
    fontSize: fontSize.md,
    color: colors.primary,
    fontWeight: fontWeight.semibold,
  },
  modalBody: {
    flex: 1,
    padding: spacing.md,
  },
  modalTitle: {
    fontSize: fontSize.xl,
    fontWeight: fontWeight.bold,
    color: colors.textPrimary,
    marginTop: spacing.sm,
    marginBottom: spacing.md,
    lineHeight: 28,
  },
  metaRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginBottom: spacing.md,
    paddingBottom: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  metaItem: {
    minWidth: 100,
  },
  metaLabel: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginBottom: 2,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  metaValue: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  section: {
    marginBottom: spacing.md,
  },
  sectionLabel: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: spacing.xs,
    fontWeight: fontWeight.semibold,
  },
  sectionContent: {
    fontSize: fontSize.md,
    color: colors.textPrimary,
    lineHeight: 24,
  },
  feedbackSection: {
    backgroundColor: '#f0fdf4',
    padding: spacing.md,
    borderRadius: borderRadius.md,
    borderLeftWidth: 3,
    borderLeftColor: colors.success,
  },
  feedbackContent: {
    fontSize: fontSize.md,
    color: colors.textPrimary,
    lineHeight: 24,
    fontStyle: 'italic',
  },
  submittedText: {
    fontSize: fontSize.sm,
    color: colors.success,
    fontWeight: fontWeight.medium,
    marginBottom: spacing.md,
  },
  submitBtn: {
    backgroundColor: colors.primary,
    borderRadius: borderRadius.sm,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: spacing.md,
  },
  submitBtnText: {
    color: colors.white,
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
  },
});
