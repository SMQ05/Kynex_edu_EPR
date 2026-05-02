/**
 * PayrollScreen — Management App
 *
 * View staff payroll records by month/year, open payslips.
 * Restricted to HR_MANAGER, SCHOOL_ADMIN, INSTITUTE_OWNER.
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
import { payrollApi } from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface PayrollRecord {
  id: string;
  employee_name: string;
  designation: string;
  gross_salary_pkr: string;
  deductions_pkr: string;
  net_salary_pkr: string;
  status: 'generated' | 'paid' | 'pending';
}

const ALLOWED_ROLES = ['HR_MANAGER', 'SCHOOL_ADMIN', 'INSTITUTE_OWNER'];
const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

// ── Component ──────────────────────────────────────────────────────────

export default function PayrollScreen() {
  const { user } = useAuthStore();
  const now = new Date();
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [year, setYear] = useState(now.getFullYear());
  const [records, setRecords] = useState<PayrollRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // ── Role Check ──────────────────────────────────────────────────────

  if (!ALLOWED_ROLES.some((r) => (user?.roles ?? []).includes(r))) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyIcon}>🔒</Text>
        <Text style={styles.emptyText}>You don't have permission to view payroll.</Text>
      </View>
    );
  }

  // ── Fetch ───────────────────────────────────────────────────────────

  const fetchPayroll = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);
    try {
      const response = await payrollApi.list({ month, year });
      setRecords(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Failed to load payroll data.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, [month, year]);

  useEffect(() => {
    fetchPayroll();
  }, [fetchPayroll]);

  // ── Open Payslip ────────────────────────────────────────────────────

  const openPayslip = async (id: string) => {
    try {
      const response = await payrollApi.payslip(id);
      const url = response.data?.url ?? response.data?.data?.url;
      if (url) {
        await WebBrowser.openBrowserAsync(url);
      } else {
        throw new Error('No URL');
      }
    } catch {
      // Alert handled inline
    }
  };

  // ── Status Badge ────────────────────────────────────────────────────

  const getStatusConfig = (status: string) => {
    switch (status) {
      case 'paid':
        return { label: 'Paid', color: colors.success, bg: '#dcfce7' };
      case 'generated':
        return { label: 'Generated', color: colors.info, bg: '#e0f2fe' };
      default:
        return { label: 'Pending', color: colors.warning, bg: '#fffbeb' };
    }
  };

  // ── Month Navigation ────────────────────────────────────────────────

  const prevMonth = () => {
    if (month === 1) { setMonth(12); setYear(year - 1); }
    else setMonth(month - 1);
  };

  const nextMonth = () => {
    if (month === 12) { setMonth(1); setYear(year + 1); }
    else setMonth(month + 1);
  };

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading && records.length === 0) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading payroll…</Text>
      </View>
    );
  }

  if (error && records.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchPayroll()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Payroll</Text>
      </View>

      {/* Month Picker */}
      <View style={styles.monthPicker}>
        <TouchableOpacity onPress={prevMonth} style={styles.monthArrow}>
          <Text style={styles.arrowText}>‹</Text>
        </TouchableOpacity>
        <Text style={styles.monthLabel}>{MONTHS[month - 1]} {year}</Text>
        <TouchableOpacity onPress={nextMonth} style={styles.monthArrow}>
          <Text style={styles.arrowText}>›</Text>
        </TouchableOpacity>
      </View>

      <FlatList
        data={records}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchPayroll(true)}
            colors={[colors.primary]}
          />
        }
        renderItem={({ item }) => {
          const statusConfig = getStatusConfig(item.status);
          return (
            <TouchableOpacity
              style={styles.card}
              onPress={() => openPayslip(item.id)}
              activeOpacity={0.75}
            >
              <View style={styles.cardHeader}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.cardTitle}>{item.employee_name}</Text>
                  <Text style={styles.cardMeta}>{item.designation}</Text>
                </View>
                <View style={[styles.statusBadge, { backgroundColor: statusConfig.bg }]}>
                  <Text style={[styles.statusText, { color: statusConfig.color }]}>
                    {statusConfig.label}
                  </Text>
                </View>
              </View>
              <View style={styles.salaryRow}>
                <View style={styles.salaryItem}>
                  <Text style={styles.salaryLabel}>Gross</Text>
                  <Text style={styles.salaryValue}>PKR {item.gross_salary_pkr}</Text>
                </View>
                <View style={styles.salaryItem}>
                  <Text style={styles.salaryLabel}>Deductions</Text>
                  <Text style={[styles.salaryValue, { color: colors.danger }]}>
                    PKR {item.deductions_pkr}
                  </Text>
                </View>
                <View style={styles.salaryItem}>
                  <Text style={styles.salaryLabel}>Net</Text>
                  <Text style={[styles.salaryValue, { color: colors.success }]}>
                    PKR {item.net_salary_pkr}
                  </Text>
                </View>
              </View>
            </TouchableOpacity>
          );
        }}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>💼</Text>
            <Text style={styles.emptyText}>No payroll records for {MONTHS[month - 1]} {year}.</Text>
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
  monthPicker: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    paddingVertical: spacing.sm, backgroundColor: colors.white,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  monthArrow: { padding: spacing.md },
  arrowText: { fontSize: fontSize.xl, color: colors.primary, fontWeight: fontWeight.bold },
  monthLabel: { fontSize: fontSize.lg, fontWeight: fontWeight.semibold, color: colors.textPrimary, minWidth: 160, textAlign: 'center' },
  list: { padding: spacing.md },
  card: { backgroundColor: colors.white, borderRadius: borderRadius.md, padding: spacing.md, marginBottom: spacing.sm, ...shadows.sm },
  cardHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.sm },
  cardTitle: { fontSize: fontSize.md, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  cardMeta: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  statusBadge: { paddingHorizontal: spacing.sm, paddingVertical: 3, borderRadius: borderRadius.full },
  statusText: { fontSize: fontSize.xs, fontWeight: fontWeight.semibold },
  salaryRow: { flexDirection: 'row', gap: spacing.md },
  salaryItem: { flex: 1 },
  salaryLabel: { fontSize: fontSize.xs, color: colors.textMuted, textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 2 },
  salaryValue: { fontSize: fontSize.sm, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
