/**
 * ResultsScreen — Exam Results per Child
 *
 * Displays exam results with percentage bars and subject-wise breakdown.
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
import { childrenApi, resultsApi } from '../services/api';
import type { Student, ExamResult } from '../types';

export default function ResultsScreen() {
  const [children, setChildren] = useState<Student[]>([]);
  const [selectedChild, setSelectedChild] = useState<Student | null>(null);
  const [results, setResults] = useState<ExamResult[]>([]);
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadChildren = useCallback(async () => {
    try {
      const res = await childrenApi.list();
      const data: Student[] = res.data.data || res.data;
      setChildren(data);
      if (data.length > 0 && !selectedChild) setSelectedChild(data[0]);
    } catch (err) {
      console.error('Failed to load children', err);
    } finally {
      setLoading(false);
    }
  }, [selectedChild]);

  const loadResults = useCallback(async (child: Student) => {
    try {
      const res = await resultsApi.list(child.id);
      const data = res.data.data || res.data;
      setResults(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error('Failed to load results', err);
    }
  }, []);

  useEffect(() => {
    loadChildren();
  }, [loadChildren]);

  useEffect(() => {
    if (selectedChild) loadResults(selectedChild);
  }, [selectedChild, loadResults]);

  const onRefresh = async () => {
    setRefreshing(true);
    if (selectedChild) await loadResults(selectedChild);
    setRefreshing(false);
  };

  const getGradeColor = (pct: number): string => {
    if (pct >= 80) return colors.success;
    if (pct >= 60) return colors.primaryLight;
    if (pct >= 40) return colors.warning;
    return colors.danger;
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
        <Text style={styles.headerTitle}>📝 Exam Results</Text>
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

      {/* Results */}
      {results.length > 0 ? (
        results.map((result) => {
          const isExpanded = expandedId === result.id;
          const gradeColor = getGradeColor(result.percentage);

          return (
            <TouchableOpacity
              key={result.id}
              style={styles.resultCard}
              onPress={() => setExpandedId(isExpanded ? null : result.id)}
              activeOpacity={0.8}
            >
              {/* Result Header */}
              <View style={styles.resultHeader}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.examName}>{result.exam_name}</Text>
                  <Text style={styles.marksText}>
                    {result.marks_obtained} / {result.total_marks}
                  </Text>
                </View>
                <View style={styles.resultRight}>
                  <View style={[styles.gradeBadge, { backgroundColor: gradeColor + '20' }]}>
                    <Text style={[styles.gradeText, { color: gradeColor }]}>
                      {result.grade || `${result.percentage}%`}
                    </Text>
                  </View>
                  {result.rank && (
                    <Text style={styles.rankText}>Rank #{result.rank}</Text>
                  )}
                </View>
              </View>

              {/* Percentage Bar */}
              <View style={styles.progressBarBg}>
                <View
                  style={[
                    styles.progressBarFill,
                    {
                      width: `${Math.min(result.percentage, 100)}%`,
                      backgroundColor: gradeColor,
                    },
                  ]}
                />
              </View>
              <Text style={[styles.percentageText, { color: gradeColor }]}>
                {result.percentage.toFixed(1)}%
              </Text>

              {/* Status Badge */}
              <View style={styles.statusRow}>
                <View
                  style={[
                    styles.statusBadge,
                    {
                      backgroundColor:
                        result.status === 'passed' ? colors.success + '20' : colors.danger + '20',
                    },
                  ]}
                >
                  <Text
                    style={{
                      color: result.status === 'passed' ? colors.success : colors.danger,
                      fontSize: fontSize.xs,
                      fontWeight: fontWeight.bold,
                    }}
                  >
                    {result.status.toUpperCase()}
                  </Text>
                </View>
                <Text style={styles.tapHint}>
                  {isExpanded ? 'Tap to collapse ▲' : 'Tap for details ▼'}
                </Text>
              </View>

              {/* Subject Breakdown (expanded) */}
              {isExpanded && result.subject_marks && result.subject_marks.length > 0 && (
                <View style={styles.subjectSection}>
                  <Text style={styles.subjectSectionTitle}>Subject-wise Marks</Text>
                  {result.subject_marks.map((sub, idx) => (
                    <View key={idx} style={styles.subjectRow}>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.subjectName}>{sub.subject}</Text>
                        <View style={styles.subjectBarBg}>
                          <View
                            style={[
                              styles.subjectBarFill,
                              {
                                width: `${(sub.marks_obtained / sub.total_marks) * 100}%`,
                                backgroundColor: sub.is_pass ? colors.success : colors.danger,
                              },
                            ]}
                          />
                        </View>
                      </View>
                      <Text style={styles.subjectMarks}>
                        {sub.marks_obtained}/{sub.total_marks}
                      </Text>
                    </View>
                  ))}
                </View>
              )}
            </TouchableOpacity>
          );
        })
      ) : (
        <View style={styles.emptyContainer}>
          <Text style={styles.emptyEmoji}>📋</Text>
          <Text style={styles.emptyText}>No exam results available yet</Text>
        </View>
      )}

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
  resultCard: {
    backgroundColor: colors.surface,
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    borderRadius: borderRadius.lg,
    padding: spacing.lg,
    ...shadows.md,
  },
  resultHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  examName: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  marksText: {
    fontSize: fontSize.sm,
    color: colors.textSecondary,
    marginTop: 2,
  },
  resultRight: {
    alignItems: 'flex-end',
  },
  gradeBadge: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.xs,
    borderRadius: borderRadius.md,
  },
  gradeText: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
  },
  rankText: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: 4,
  },
  progressBarBg: {
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.border,
  },
  progressBarFill: {
    height: 8,
    borderRadius: 4,
  },
  percentageText: {
    fontSize: fontSize.xs,
    fontWeight: fontWeight.semibold,
    marginTop: 4,
    textAlign: 'right',
  },
  statusRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: spacing.sm,
  },
  statusBadge: {
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
    borderRadius: borderRadius.sm,
  },
  tapHint: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
  },
  subjectSection: {
    marginTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.md,
  },
  subjectSectionTitle: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
    marginBottom: spacing.sm,
  },
  subjectRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: spacing.sm,
  },
  subjectName: {
    fontSize: fontSize.sm,
    color: colors.textSecondary,
    marginBottom: 4,
  },
  subjectBarBg: {
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.border,
  },
  subjectBarFill: {
    height: 6,
    borderRadius: 3,
  },
  subjectMarks: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
    marginLeft: spacing.md,
    minWidth: 50,
    textAlign: 'right',
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
