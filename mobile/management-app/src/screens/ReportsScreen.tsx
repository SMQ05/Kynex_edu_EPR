/**
 * ReportsScreen — Management App
 *
 * Quick reports (inline display) and custom reports (PDF/XLSX download).
 * Restricted to SCHOOL_ADMIN, INSTITUTE_OWNER, HR_MANAGER, BURSAR.
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
  ScrollView,
  Modal,
} from 'react-native';
import * as WebBrowser from 'expo-web-browser';
import { reportsApi } from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface CustomReport {
  id: string;
  name: string;
  description?: string;
  type?: string;
}

interface QuickReportType {
  key: string;
  label: string;
  icon: string;
}

interface QuickReportData {
  columns: string[];
  rows: string[][];
}

const ALLOWED_ROLES = ['SCHOOL_ADMIN', 'INSTITUTE_OWNER', 'HR_MANAGER', 'BURSAR'];

const QUICK_REPORTS: QuickReportType[] = [
  { key: 'attendance_today', label: "Today's Attendance", icon: '📊' },
  { key: 'fee_collection_today', label: "Today's Fee Collection", icon: '💰' },
  { key: 'absentees_today', label: "Today's Absentees", icon: '❌' },
  { key: 'birthday_today', label: "Today's Birthdays", icon: '🎂' },
];

// ── Component ──────────────────────────────────────────────────────────

export default function ReportsScreen() {
  const { user } = useAuthStore();
  const [customReports, setCustomReports] = useState<CustomReport[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [runningReport, setRunningReport] = useState<string | null>(null);
  const [quickData, setQuickData] = useState<QuickReportData | null>(null);
  const [quickTitle, setQuickTitle] = useState('');
  const [loadingQuick, setLoadingQuick] = useState<string | null>(null);

  // ── Role Check ──────────────────────────────────────────────────────

  if (!ALLOWED_ROLES.some((r) => (user?.roles ?? []).includes(r))) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyIcon}>🔒</Text>
        <Text style={styles.emptyText}>You don't have permission to view reports.</Text>
      </View>
    );
  }

  // ── Fetch Custom Reports ────────────────────────────────────────────

  const fetchCustomReports = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);
    try {
      const response = await reportsApi.customList();
      setCustomReports(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Failed to load reports.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchCustomReports();
  }, [fetchCustomReports]);

  // ── Quick Report ────────────────────────────────────────────────────

  const runQuickReport = async (report: QuickReportType) => {
    setLoadingQuick(report.key);
    try {
      const response = await reportsApi.quick(report.key);
      const data = response.data?.data ?? response.data;
      setQuickData(data);
      setQuickTitle(report.label);
    } catch {
      setQuickData({ columns: ['Error'], rows: [['Failed to load report data.']] });
      setQuickTitle(report.label);
    } finally {
      setLoadingQuick(null);
    }
  };

  // ── Run Custom Report ───────────────────────────────────────────────

  const runCustomReport = async (report: CustomReport) => {
    setRunningReport(report.id);
    try {
      const response = await reportsApi.runCustom(report.id);
      const url = response.data?.url ?? response.data?.data?.url;
      if (url) {
        await WebBrowser.openBrowserAsync(url);
      }
    } catch {
      // Silent fail
    } finally {
      setRunningReport(null);
    }
  };

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading reports…</Text>
      </View>
    );
  }

  if (error && customReports.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchCustomReports()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Reports</Text>
      </View>

      <ScrollView
        refreshControl={
          <RefreshControl refreshing={isRefreshing} onRefresh={() => fetchCustomReports(true)} colors={[colors.primary]} />
        }
      >
        {/* Quick Reports */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Quick Reports</Text>
          <View style={styles.quickGrid}>
            {QUICK_REPORTS.map((report) => (
              <TouchableOpacity
                key={report.key}
                style={styles.quickCard}
                onPress={() => runQuickReport(report)}
                disabled={loadingQuick === report.key}
                activeOpacity={0.75}
              >
                {loadingQuick === report.key ? (
                  <ActivityIndicator size="small" color={colors.primary} />
                ) : (
                  <Text style={styles.quickIcon}>{report.icon}</Text>
                )}
                <Text style={styles.quickLabel}>{report.label}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Custom Reports */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Custom Reports</Text>
          {customReports.length === 0 ? (
            <Text style={styles.emptySmallText}>No custom reports saved.</Text>
          ) : (
            customReports.map((report) => (
              <TouchableOpacity
                key={report.id}
                style={styles.customCard}
                onPress={() => runCustomReport(report)}
                disabled={runningReport === report.id}
                activeOpacity={0.75}
              >
                <View style={{ flex: 1 }}>
                  <Text style={styles.customName}>{report.name}</Text>
                  {report.description && (
                    <Text style={styles.customDesc}>{report.description}</Text>
                  )}
                </View>
                {runningReport === report.id ? (
                  <ActivityIndicator size="small" color={colors.primary} />
                ) : (
                  <Text style={styles.runText}>Run →</Text>
                )}
              </TouchableOpacity>
            ))
          )}
        </View>

        <View style={{ height: spacing.xxl }} />
      </ScrollView>

      {/* Quick Report Data Modal */}
      <Modal
        visible={!!quickData}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setQuickData(null)}
      >
        <SafeAreaView style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>{quickTitle}</Text>
            <TouchableOpacity onPress={() => setQuickData(null)}>
              <Text style={styles.closeText}>✕ Close</Text>
            </TouchableOpacity>
          </View>
          <ScrollView horizontal>
            <View style={styles.tableContainer}>
              {/* Table Header */}
              {quickData?.columns && (
                <View style={styles.tableRow}>
                  {quickData.columns.map((col, i) => (
                    <View key={i} style={styles.tableHeaderCell}>
                      <Text style={styles.tableHeaderText}>{col}</Text>
                    </View>
                  ))}
                </View>
              )}
              {/* Table Rows */}
              {quickData?.rows?.map((row, ri) => (
                <View key={ri} style={[styles.tableRow, ri % 2 === 0 && styles.tableRowEven]}>
                  {row.map((cell, ci) => (
                    <View key={ci} style={styles.tableCell}>
                      <Text style={styles.tableCellText}>{cell}</Text>
                    </View>
                  ))}
                </View>
              ))}
              {(!quickData?.rows || quickData.rows.length === 0) && (
                <Text style={styles.emptySmallText}>No data available.</Text>
              )}
            </View>
          </ScrollView>
        </SafeAreaView>
      </Modal>
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
  section: { paddingHorizontal: spacing.md, marginTop: spacing.md },
  sectionTitle: { fontSize: fontSize.lg, fontWeight: fontWeight.semibold, color: colors.textPrimary, marginBottom: spacing.sm },
  quickGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  quickCard: {
    width: '48%', backgroundColor: colors.white, borderRadius: borderRadius.md,
    padding: spacing.md, alignItems: 'center', ...shadows.sm,
  },
  quickIcon: { fontSize: 24, marginBottom: spacing.xs },
  quickLabel: { fontSize: fontSize.xs, color: colors.textPrimary, textAlign: 'center', fontWeight: fontWeight.medium },
  customCard: {
    backgroundColor: colors.white, borderRadius: borderRadius.md, padding: spacing.md,
    marginBottom: spacing.sm, flexDirection: 'row', alignItems: 'center', ...shadows.sm,
  },
  customName: { fontSize: fontSize.md, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  customDesc: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  runText: { color: colors.primary, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
  emptySmallText: { fontSize: fontSize.sm, color: colors.textMuted, paddingVertical: spacing.md },
  // ── Modal / Table ────
  modalContainer: { flex: 1, backgroundColor: colors.white },
  modalHeader: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    paddingHorizontal: spacing.md, paddingVertical: spacing.sm,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  modalTitle: { fontSize: fontSize.lg, fontWeight: fontWeight.bold, color: colors.textPrimary },
  closeText: { fontSize: fontSize.md, color: colors.primary, fontWeight: fontWeight.semibold },
  tableContainer: { padding: spacing.md },
  tableRow: { flexDirection: 'row' },
  tableRowEven: { backgroundColor: '#f8fafc' },
  tableHeaderCell: { minWidth: 120, padding: spacing.sm, borderBottomWidth: 2, borderBottomColor: colors.border },
  tableHeaderText: { fontSize: fontSize.xs, fontWeight: fontWeight.bold, color: colors.textPrimary, textTransform: 'uppercase' },
  tableCell: { minWidth: 120, padding: spacing.sm, borderBottomWidth: 1, borderBottomColor: colors.border },
  tableCellText: { fontSize: fontSize.sm, color: colors.textPrimary },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
