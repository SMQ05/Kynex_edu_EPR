/**
 * FeeCollectionScreen — Management App
 *
 * Search students, view outstanding fees, collect payments,
 * and request refunds. Restricted to BURSAR, ACCOUNTANT,
 * SCHOOL_ADMIN, INSTITUTE_OWNER roles.
 */
import React, { useState, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Modal,
  Alert,
  SafeAreaView,
} from 'react-native';
import { studentsApi, feeCollectionApi } from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface Student {
  id: string;
  full_name: string;
  admission_no: string;
  class?: { name: string } | null;
  section?: { name: string } | null;
}

interface FeeItem {
  id: string;
  fee_type: string;
  amount_pkr: string;
  paid_pkr: string;
  balance_pkr: string;
  due_date: string;
  status: string;
}

type PaymentMethod = 'Cash' | 'JazzCash' | 'EasyPaisa';

const ALLOWED_ROLES = ['BURSAR', 'ACCOUNTANT', 'SCHOOL_ADMIN', 'INSTITUTE_OWNER'];
const PAYMENT_METHODS: PaymentMethod[] = ['Cash', 'JazzCash', 'EasyPaisa'];

// ── Component ──────────────────────────────────────────────────────────

export default function FeeCollectionScreen() {
  const { user } = useAuthStore();

  // Search
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<Student[]>([]);
  const [searching, setSearching] = useState(false);

  // Selected student fees
  const [selectedStudent, setSelectedStudent] = useState<Student | null>(null);
  const [fees, setFees] = useState<FeeItem[]>([]);
  const [loadingFees, setLoadingFees] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Payment modal
  const [payingFee, setPayingFee] = useState<FeeItem | null>(null);
  const [selectedMethod, setSelectedMethod] = useState<PaymentMethod>('Cash');
  const [collecting, setCollecting] = useState(false);

  // ── Role Check ──────────────────────────────────────────────────────

  if (!ALLOWED_ROLES.some((r) => (user?.roles ?? []).includes(r))) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyIcon}>🔒</Text>
        <Text style={styles.emptyText}>
          You don't have permission to collect fees.
        </Text>
      </View>
    );
  }

  // ── Search ──────────────────────────────────────────────────────────

  const handleSearch = async () => {
    if (!searchQuery.trim()) return;
    setSearching(true);
    setError(null);
    try {
      const response = await studentsApi.list({ search: searchQuery.trim() });
      setSearchResults(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Search failed. Please try again.');
    } finally {
      setSearching(false);
    }
  };

  // ── Fetch Fees ──────────────────────────────────────────────────────

  const fetchFees = useCallback(async (studentId: string, refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoadingFees(true);
    setError(null);
    try {
      const response = await feeCollectionApi.studentFees(studentId);
      setFees(response.data?.data ?? response.data?.fees ?? response.data ?? []);
    } catch {
      setError('Failed to load fees.');
    } finally {
      setLoadingFees(false);
      setRefreshing(false);
    }
  }, []);

  const selectStudent = (student: Student) => {
    setSelectedStudent(student);
    setSearchResults([]);
    setSearchQuery('');
    fetchFees(student.id);
  };

  // ── Collect Payment ─────────────────────────────────────────────────

  const handleCollect = async () => {
    if (!payingFee || !selectedStudent) return;
    setCollecting(true);
    try {
      const response = await feeCollectionApi.collect({
        student_id: selectedStudent.id,
        fee_id: payingFee.id,
        payment_method: selectedMethod,
        amount_pkr: payingFee.balance_pkr,
      });
      const receiptNo = response.data?.receipt_no ?? response.data?.data?.receipt_no ?? 'N/A';
      setPayingFee(null);
      Alert.alert('Payment Collected', `Receipt No: ${receiptNo}`);
      fetchFees(selectedStudent.id, true);
    } catch {
      Alert.alert('Error', 'Failed to collect payment. Please try again.');
    } finally {
      setCollecting(false);
    }
  };

  // ── Request Refund ──────────────────────────────────────────────────

  const handleRefund = (fee: FeeItem) => {
    Alert.alert(
      'Request Refund',
      `Request refund for ${fee.fee_type}?\n\nThis will require admin approval.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Request',
          onPress: async () => {
            try {
              await feeCollectionApi.requestRefund(fee.id);
              Alert.alert('Submitted', 'Refund request submitted for approval.');
              if (selectedStudent) fetchFees(selectedStudent.id, true);
            } catch {
              Alert.alert('Error', 'Failed to submit refund request.');
            }
          },
        },
      ],
    );
  };

  // ── Fee Status Color ────────────────────────────────────────────────

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid': return colors.success;
      case 'partial': return colors.warning;
      case 'pending': return colors.danger;
      case 'refunded': return colors.info;
      default: return colors.textMuted;
    }
  };

  // ── Render ─────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Fee Collection</Text>
        <Text style={styles.headerSub}>
          {selectedStudent
            ? `${selectedStudent.full_name} (${selectedStudent.admission_no})`
            : 'Search a student to begin'}
        </Text>
      </View>

      {/* Search Bar */}
      <View style={styles.searchContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder="Search by student name or ID..."
          placeholderTextColor={colors.textMuted}
          value={searchQuery}
          onChangeText={setSearchQuery}
          onSubmitEditing={handleSearch}
          returnKeyType="search"
        />
        <TouchableOpacity style={styles.searchBtn} onPress={handleSearch} disabled={searching}>
          {searching ? (
            <ActivityIndicator size="small" color={colors.white} />
          ) : (
            <Text style={styles.searchBtnText}>Search</Text>
          )}
        </TouchableOpacity>
      </View>

      {/* Search Results Dropdown */}
      {searchResults.length > 0 && (
        <View style={styles.resultsDropdown}>
          {searchResults.map((student) => (
            <TouchableOpacity
              key={student.id}
              style={styles.resultItem}
              onPress={() => selectStudent(student)}
            >
              <Text style={styles.resultName}>{student.full_name}</Text>
              <Text style={styles.resultMeta}>
                {student.admission_no}
                {student.class ? ` · ${student.class.name}` : ''}
                {student.section ? `-${student.section.name}` : ''}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      )}

      {/* Error */}
      {error && !loadingFees && (
        <View style={styles.errorBox}>
          <Text style={styles.errorText}>{error}</Text>
          {selectedStudent && (
            <TouchableOpacity onPress={() => fetchFees(selectedStudent.id)}>
              <Text style={styles.retryLink}>Retry</Text>
            </TouchableOpacity>
          )}
        </View>
      )}

      {/* Fee List */}
      {loadingFees ? (
        <View style={styles.centered}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : selectedStudent ? (
        <FlatList
          data={fees}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => fetchFees(selectedStudent.id, true)}
              colors={[colors.primary]}
            />
          }
          renderItem={({ item }) => (
            <View style={styles.feeCard}>
              <View style={styles.feeRow}>
                <Text style={styles.feeType}>{item.fee_type}</Text>
                <View style={[styles.feeStatusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
                  <Text style={[styles.feeStatusText, { color: getStatusColor(item.status) }]}>
                    {item.status.toUpperCase()}
                  </Text>
                </View>
              </View>
              <View style={styles.feeDetails}>
                <Text style={styles.feeLabel}>Amount: <Text style={styles.feeValue}>PKR {item.amount_pkr}</Text></Text>
                <Text style={styles.feeLabel}>Paid: <Text style={styles.feeValue}>PKR {item.paid_pkr}</Text></Text>
                <Text style={styles.feeLabel}>Balance: <Text style={[styles.feeValue, { color: colors.danger }]}>PKR {item.balance_pkr}</Text></Text>
                <Text style={styles.feeLabel}>Due: <Text style={styles.feeValue}>{item.due_date}</Text></Text>
              </View>
              <View style={styles.feeActions}>
                {item.status !== 'paid' && item.status !== 'refunded' && (
                  <TouchableOpacity
                    style={styles.collectBtn}
                    onPress={() => { setPayingFee(item); setSelectedMethod('Cash'); }}
                  >
                    <Text style={styles.collectBtnText}>Collect</Text>
                  </TouchableOpacity>
                )}
                {item.status === 'paid' && (
                  <TouchableOpacity style={styles.refundBtn} onPress={() => handleRefund(item)}>
                    <Text style={styles.refundBtnText}>Request Refund</Text>
                  </TouchableOpacity>
                )}
              </View>
            </View>
          )}
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyIcon}>💰</Text>
              <Text style={styles.emptyText}>No outstanding fees found.</Text>
            </View>
          }
        />
      ) : (
        <View style={styles.centered}>
          <Text style={styles.emptyIcon}>🔍</Text>
          <Text style={styles.emptyText}>Search for a student to view their fees.</Text>
        </View>
      )}

      {/* Payment Method Modal */}
      <Modal
        visible={!!payingFee}
        animationType="slide"
        transparent
        onRequestClose={() => setPayingFee(null)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Collect Payment</Text>
            <Text style={styles.modalSub}>
              {payingFee?.fee_type} — PKR {payingFee?.balance_pkr}
            </Text>

            <Text style={styles.modalLabel}>Payment Method</Text>
            {PAYMENT_METHODS.map((method) => (
              <TouchableOpacity
                key={method}
                style={[
                  styles.methodOption,
                  selectedMethod === method && styles.methodOptionActive,
                ]}
                onPress={() => setSelectedMethod(method)}
              >
                <Text style={[
                  styles.methodText,
                  selectedMethod === method && styles.methodTextActive,
                ]}>
                  {method}
                </Text>
              </TouchableOpacity>
            ))}

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.modalCancelBtn}
                onPress={() => setPayingFee(null)}
              >
                <Text style={styles.modalCancelText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalConfirmBtn, collecting && { opacity: 0.7 }]}
                onPress={handleCollect}
                disabled={collecting}
              >
                {collecting ? (
                  <ActivityIndicator color={colors.white} size="small" />
                ) : (
                  <Text style={styles.modalConfirmText}>Confirm Collection</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

// ── Styles ─────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: spacing.lg },
  header: {
    paddingHorizontal: spacing.md, paddingTop: spacing.md, paddingBottom: spacing.sm,
    backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  headerTitle: { fontSize: fontSize.xl, fontWeight: fontWeight.bold, color: colors.textPrimary },
  headerSub: { fontSize: fontSize.sm, color: colors.textMuted, marginTop: 2 },
  searchContainer: {
    flexDirection: 'row', padding: spacing.md, backgroundColor: colors.white,
    borderBottomWidth: 1, borderBottomColor: colors.border,
  },
  searchInput: {
    flex: 1, borderWidth: 1, borderColor: colors.border, borderRadius: borderRadius.sm,
    paddingHorizontal: spacing.md, paddingVertical: 10, fontSize: fontSize.sm,
    color: colors.textPrimary, backgroundColor: colors.background, marginRight: spacing.sm,
  },
  searchBtn: {
    backgroundColor: colors.primary, borderRadius: borderRadius.sm,
    paddingHorizontal: spacing.md, justifyContent: 'center',
  },
  searchBtnText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
  resultsDropdown: {
    backgroundColor: colors.white, borderBottomWidth: 1, borderBottomColor: colors.border,
    maxHeight: 200,
  },
  resultItem: { paddingHorizontal: spacing.md, paddingVertical: spacing.sm, borderBottomWidth: 1, borderBottomColor: colors.border },
  resultName: { fontSize: fontSize.md, fontWeight: fontWeight.medium, color: colors.textPrimary },
  resultMeta: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  errorBox: {
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#fef2f2',
    padding: spacing.md, marginHorizontal: spacing.md, marginTop: spacing.sm,
    borderRadius: borderRadius.sm,
  },
  errorText: { flex: 1, color: colors.danger, fontSize: fontSize.sm },
  retryLink: { color: colors.primary, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
  list: { padding: spacing.md },
  feeCard: { backgroundColor: colors.white, borderRadius: borderRadius.md, padding: spacing.md, marginBottom: spacing.sm, ...shadows.sm },
  feeRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.sm },
  feeType: { fontSize: fontSize.md, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  feeStatusBadge: { paddingHorizontal: spacing.sm, paddingVertical: 2, borderRadius: borderRadius.full },
  feeStatusText: { fontSize: fontSize.xs, fontWeight: fontWeight.semibold },
  feeDetails: { marginBottom: spacing.sm },
  feeLabel: { fontSize: fontSize.sm, color: colors.textSecondary, marginTop: 2 },
  feeValue: { fontWeight: fontWeight.semibold, color: colors.textPrimary },
  feeActions: { flexDirection: 'row', gap: spacing.sm },
  collectBtn: { backgroundColor: colors.primary, borderRadius: borderRadius.sm, paddingHorizontal: spacing.md, paddingVertical: spacing.sm },
  collectBtnText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
  refundBtn: { backgroundColor: colors.warning + '20', borderRadius: borderRadius.sm, paddingHorizontal: spacing.md, paddingVertical: spacing.sm },
  refundBtnText: { color: colors.warning, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  // ── Modal ────
  modalOverlay: { flex: 1, backgroundColor: colors.overlay, justifyContent: 'flex-end' },
  modalContent: { backgroundColor: colors.white, borderTopLeftRadius: borderRadius.lg, borderTopRightRadius: borderRadius.lg, padding: spacing.lg },
  modalTitle: { fontSize: fontSize.xl, fontWeight: fontWeight.bold, color: colors.textPrimary },
  modalSub: { fontSize: fontSize.sm, color: colors.textMuted, marginTop: 2, marginBottom: spacing.lg },
  modalLabel: { fontSize: fontSize.sm, fontWeight: fontWeight.semibold, color: colors.textSecondary, marginBottom: spacing.sm },
  methodOption: {
    borderWidth: 1, borderColor: colors.border, borderRadius: borderRadius.sm,
    padding: spacing.md, marginBottom: spacing.sm,
  },
  methodOptionActive: { borderColor: colors.primary, backgroundColor: colors.primary + '10' },
  methodText: { fontSize: fontSize.md, color: colors.textPrimary },
  methodTextActive: { color: colors.primary, fontWeight: fontWeight.semibold },
  modalActions: { flexDirection: 'row', gap: spacing.sm, marginTop: spacing.md },
  modalCancelBtn: { flex: 1, borderWidth: 1, borderColor: colors.border, borderRadius: borderRadius.sm, paddingVertical: 14, alignItems: 'center' },
  modalCancelText: { color: colors.textSecondary, fontWeight: fontWeight.semibold },
  modalConfirmBtn: { flex: 2, backgroundColor: colors.primary, borderRadius: borderRadius.sm, paddingVertical: 14, alignItems: 'center' },
  modalConfirmText: { color: colors.white, fontWeight: fontWeight.semibold },
});
