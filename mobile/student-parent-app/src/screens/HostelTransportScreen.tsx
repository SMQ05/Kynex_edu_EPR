/**
 * HostelTransportScreen — Student/Parent App
 *
 * Two cards: Hostel allocation and Transport assignment.
 * Shows "Not allocated" / "Not assigned" when no record.
 */
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  SafeAreaView,
  TouchableOpacity,
} from 'react-native';
import { hostelApi, transportStudentApi } from '../services/api';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface HostelAllocation {
  building_name: string;
  room_number: string;
  room_type?: string;
  warden_name?: string;
  warden_contact?: string;
}

interface TransportAssignment {
  route_name: string;
  vehicle_number?: string;
  stop_name?: string;
  pickup_time?: string;
  driver_name?: string;
  driver_contact?: string;
}

// ── Component ──────────────────────────────────────────────────────────

export default function HostelTransportScreen() {
  const [hostel, setHostel] = useState<HostelAllocation | null>(null);
  const [transport, setTransport] = useState<TransportAssignment | null>(null);
  const [hasHostel, setHasHostel] = useState(false);
  const [hasTransport, setHasTransport] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchData = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);

    try {
      const [hostelRes, transportRes] = await Promise.allSettled([
        hostelApi.myAllocation(),
        transportStudentApi.myAssignment(),
      ]);

      if (hostelRes.status === 'fulfilled') {
        const data = hostelRes.value.data?.data ?? hostelRes.value.data;
        if (data && data.building_name) {
          setHostel(data);
          setHasHostel(true);
        } else {
          setHasHostel(false);
        }
      }

      if (transportRes.status === 'fulfilled') {
        const data = transportRes.value.data?.data ?? transportRes.value.data;
        if (data && data.route_name) {
          setTransport(data);
          setHasTransport(true);
        } else {
          setHasTransport(false);
        }
      }

      if (hostelRes.status === 'rejected' && transportRes.status === 'rejected') {
        setError('Failed to load data.');
      }
    } catch {
      setError('Failed to load data.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading…</Text>
      </View>
    );
  }

  if (error) {
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

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Hostel & Transport</Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchData(true)}
            colors={[colors.primary]}
          />
        }
      >
        {/* Hostel Card */}
        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardIcon}>🏠</Text>
            <Text style={styles.cardTitle}>Hostel</Text>
          </View>
          {hasHostel && hostel ? (
            <View style={styles.infoList}>
              <InfoRow label="Building" value={hostel.building_name} />
              <InfoRow label="Room" value={hostel.room_number} />
              {hostel.room_type && <InfoRow label="Room Type" value={hostel.room_type} />}
              {hostel.warden_name && <InfoRow label="Warden" value={hostel.warden_name} />}
              {hostel.warden_contact && <InfoRow label="Contact" value={hostel.warden_contact} />}
            </View>
          ) : (
            <View style={styles.notAllocated}>
              <Text style={styles.notAllocatedIcon}>🏠</Text>
              <Text style={styles.notAllocatedText}>Not allocated to any hostel.</Text>
            </View>
          )}
        </View>

        {/* Transport Card */}
        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardIcon}>🚌</Text>
            <Text style={styles.cardTitle}>Transport</Text>
          </View>
          {hasTransport && transport ? (
            <View style={styles.infoList}>
              <InfoRow label="Route" value={transport.route_name} />
              {transport.vehicle_number && <InfoRow label="Vehicle" value={transport.vehicle_number} />}
              {transport.stop_name && <InfoRow label="Stop" value={transport.stop_name} />}
              {transport.pickup_time && <InfoRow label="Pickup Time" value={transport.pickup_time} />}
              {transport.driver_name && <InfoRow label="Driver" value={transport.driver_name} />}
              {transport.driver_contact && <InfoRow label="Driver Contact" value={transport.driver_contact} />}
            </View>
          ) : (
            <View style={styles.notAllocated}>
              <Text style={styles.notAllocatedIcon}>🚌</Text>
              <Text style={styles.notAllocatedText}>Not assigned to any transport route.</Text>
            </View>
          )}
        </View>

        <View style={{ height: spacing.xxl }} />
      </ScrollView>
    </SafeAreaView>
  );
}

// ── Info Row Component ────────────────────────────────────────────────

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
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
  scrollContent: { padding: spacing.md },
  card: {
    backgroundColor: colors.white, borderRadius: borderRadius.md,
    padding: spacing.md, marginBottom: spacing.md, ...shadows.sm,
  },
  cardHeaderRow: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.md, borderBottomWidth: 1, borderBottomColor: colors.border, paddingBottom: spacing.sm },
  cardIcon: { fontSize: 24, marginRight: spacing.sm },
  cardTitle: { fontSize: fontSize.lg, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  infoList: {},
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: spacing.sm, borderBottomWidth: 1, borderBottomColor: colors.border },
  infoLabel: { fontSize: fontSize.sm, color: colors.textMuted, fontWeight: fontWeight.medium },
  infoValue: { fontSize: fontSize.sm, color: colors.textPrimary, fontWeight: fontWeight.semibold, maxWidth: '60%', textAlign: 'right' },
  notAllocated: { alignItems: 'center', paddingVertical: spacing.lg },
  notAllocatedIcon: { fontSize: 36, marginBottom: spacing.sm, opacity: 0.4 },
  notAllocatedText: { fontSize: fontSize.sm, color: colors.textMuted },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
