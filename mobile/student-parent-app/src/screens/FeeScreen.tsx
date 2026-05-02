/**
 * FeeScreen — Detailed Fee View for Parent
 *
 * Shows unpaid/paid fees per child with payment history.
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
import { childrenApi, feesApi } from '../services/api';
import type { Student, StudentFee, FeesSummary } from '../types';

type TabType = 'unpaid' | 'paid';

export default function FeeScreen() {
  const [children, setChildren] = useState<Student[]>([]);
  const [selectedChild, setSelectedChild] = useState<Student | null>(null);
  const [fees, setFees] = useState<FeesSummary | null>(null);
  const [activeTab, setActiveTab] = useState<TabType>('unpaid');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadData = useCallback(async () => {
    try {
      const childRes = await childrenApi.list();
      const data: Student[] = childRes.data.data || childRes.data;
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

  const loadFees = useCallback(async (child: Student) => {
    try {
      const res = await feesApi.show(child.id);
      setFees(res.data.data || res.data);
    } catch (err) {
      console.error('Failed to load fees', err);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  useEffect(() => {
    if (selectedChild) loadFees(selectedChild);
  }, [selectedChild, loadFees]);

  const onRefresh = async () => {
    setRefreshing(true);
    if (selectedChild) await loadFees(selectedChild);
    setRefreshing(false);
  };

  const filteredFees: StudentFee[] = fees
    ? fees.fees.filter((f) =>
        activeTab === 'unpaid'
          ? f.status === 'pending' || f.status === 'partial'
          : f.status === 'paid' || f.status === 'waived',
      )
    : [];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid':
        return colors.success;
      case 'partial':
        return colors.warning;
      case 'pending':
        return colors.danger;
      case 'waived':
        return colors.info;
      default:
        return colors.textMuted;
    }
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
        <Text style={styles.headerTitle}>💰 Fee Details</Text>
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

      {/* Summary */}
      {fees && (
        <View style={styles.summaryRow}>
          <View style={[styles.summaryCard, { borderLeftColor: colors.danger }]}>
            <Text style={styles.summaryLabel}>Total Due</Text>
            <Text style={[styles.summaryValue, { color: colors.danger }]}>
              PKR {fees.total_due_pkr}
            </Text>
          </View>
          <View style={[styles.summaryCard, { borderLeftColor: colors.success }]}>
            <Text style={styles.summaryLabel}>Total Paid</Text>
            <Text style={[styles.summaryValue, { color: colors.success }]}>
              PKR {fees.total_paid_pkr}
            </Text>
          </View>
        </View>
      )}

      {/* Tabs */}
      <View style={styles.tabs}>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'unpaid' && styles.tabActive]}
          onPress={() => setActiveTab('unpaid')}
        >
          <Text style={[styles.tabText, activeTab === 'unpaid' && styles.tabTextActive]}>
            Unpaid
          </Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.tab, activeTab === 'paid' && styles.tabActive]}
          onPress={() => setActiveTab('paid')}
        >
          <Text style={[styles.tabText, activeTab === 'paid' && styles.tabTextActive]}>
            Paid
          </Text>
        </TouchableOpacity>
      </View>

      {/* Fee List */}
      {filteredFees.length > 0 ? (
        filteredFees.map((fee) => (
          <View key={fee.id} style={styles.feeCard}>
            <View style={styles.feeCardHeader}>
              <Text style={styles.feeType}>{fee.fee_type}</Text>
              <View
                style={[styles.statusBadge, { backgroundColor: getStatusColor(fee.status) + '20' }]}
              >
                <Text style={[styles.statusText, { color: getStatusColor(fee.status) }]}>
                  {fee.status.toUpperCase()}
                </Text>
              </View>
            </View>

            <View style={styles.feeDetails}>
              <View style={styles.feeDetailRow}>
                <Text style={styles.feeDetailLabel}>Amount</Text>
                <Text style={styles.feeDetailValue}>PKR {fee.amount_pkr}</Text>
              </View>
              {fee.discount_pkr !== '0' && (
                <View style={styles.feeDetailRow}>
                  <Text style={styles.feeDetailLabel}>Discount</Text>
                  <Text style={[styles.feeDetailValue, { color: colors.success }]}>
                    -PKR {fee.discount_pkr}
                  </Text>
                </View>
              )}
              {fee.fine_pkr !== '0' && (
                <View style={styles.feeDetailRow}>
                  <Text style={styles.feeDetailLabel}>Fine</Text>
                  <Text style={[styles.feeDetailValue, { color: colors.danger }]}>
                    +PKR {fee.fine_pkr}
                  </Text>
                </View>
              )}
              <View style={styles.feeDetailRow}>
                <Text style={styles.feeDetailLabel}>Paid</Text>
                <Text style={styles.feeDetailValue}>PKR {fee.paid_pkr}</Text>
              </View>
              <View style={[styles.feeDetailRow, { borderBottomWidth: 0 }]}>
                <Text style={[styles.feeDetailLabel, { fontWeight: fontWeight.bold }]}>
                  Balance
                </Text>
                <Text
                  style={[
                    styles.feeDetailValue,
                    {
                      fontWeight: fontWeight.bold,
                      color: parseFloat(fee.balance_pkr) > 0 ? colors.danger : colors.success,
                    },
                  ]}
                >
                  PKR {fee.balance_pkr}
                </Text>
              </View>
            </View>

            <Text style={styles.dueDate}>Due: {fee.due_date}</Text>
          </View>
        ))
      ) : (
        <View style={styles.emptyContainer}>
          <Text style={styles.emptyText}>
            {activeTab === 'unpaid' ? '🎉 All fees are paid!' : 'No paid fees found'}
          </Text>
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
  summaryRow: {
    flexDirection: 'row',
    paddingHorizontal: spacing.lg,
    marginBottom: spacing.md,
    gap: spacing.md,
  },
  summaryCard: {
    flex: 1,
    backgroundColor: colors.surface,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    borderLeftWidth: 4,
    ...shadows.sm,
  },
  summaryLabel: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
  },
  summaryValue: {
    fontSize: fontSize.lg,
    fontWeight: fontWeight.bold,
    marginTop: spacing.xs,
  },
  tabs: {
    flexDirection: 'row',
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    borderRadius: borderRadius.md,
    backgroundColor: colors.border,
    padding: 2,
  },
  tab: {
    flex: 1,
    paddingVertical: spacing.sm,
    alignItems: 'center',
    borderRadius: borderRadius.sm,
  },
  tabActive: {
    backgroundColor: colors.white,
  },
  tabText: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
    color: colors.textMuted,
  },
  tabTextActive: {
    color: colors.primary,
    fontWeight: fontWeight.semibold,
  },
  feeCard: {
    backgroundColor: colors.surface,
    marginHorizontal: spacing.lg,
    marginBottom: spacing.md,
    borderRadius: borderRadius.lg,
    padding: spacing.lg,
    ...shadows.md,
  },
  feeCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: spacing.md,
  },
  feeType: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  statusBadge: {
    paddingHorizontal: spacing.sm,
    paddingVertical: 2,
    borderRadius: borderRadius.sm,
  },
  statusText: {
    fontSize: fontSize.xs,
    fontWeight: fontWeight.bold,
  },
  feeDetails: {
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingTop: spacing.sm,
  },
  feeDetailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: spacing.xs,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  feeDetailLabel: {
    fontSize: fontSize.sm,
    color: colors.textSecondary,
  },
  feeDetailValue: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
    color: colors.textPrimary,
  },
  dueDate: {
    fontSize: fontSize.xs,
    color: colors.textMuted,
    marginTop: spacing.sm,
  },
  emptyContainer: {
    padding: spacing.xl,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: fontSize.md,
    color: colors.textMuted,
  },
});
