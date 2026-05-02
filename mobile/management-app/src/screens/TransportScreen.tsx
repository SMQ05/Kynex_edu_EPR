/**
 * TransportScreen — Management App
 *
 * View transport routes with vehicle info, expand to see assigned students/stops.
 * Restricted to TRANSPORT_MANAGER, SCHOOL_ADMIN, INSTITUTE_OWNER.
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
import { transportApi } from '../services/api';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

interface TransportRoute {
  id: string;
  route_name: string;
  vehicle_number?: string;
  driver_name?: string;
  stops_count?: number;
  students_count?: number;
}

interface RouteStudent {
  id: string;
  student_name: string;
  stop_name: string;
  pickup_time?: string;
}

const ALLOWED_ROLES = ['TRANSPORT_MANAGER', 'SCHOOL_ADMIN', 'INSTITUTE_OWNER'];

// ── Component ──────────────────────────────────────────────────────────

export default function TransportScreen() {
  const { user } = useAuthStore();
  const [routes, setRoutes] = useState<TransportRoute[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [expandedRoute, setExpandedRoute] = useState<string | null>(null);
  const [routeStudents, setRouteStudents] = useState<Record<string, RouteStudent[]>>({});
  const [loadingStudents, setLoadingStudents] = useState<string | null>(null);

  // ── Role Check ──────────────────────────────────────────────────────

  if (!ALLOWED_ROLES.some((r) => (user?.roles ?? []).includes(r))) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyIcon}>🔒</Text>
        <Text style={styles.emptyText}>You don't have permission to view transport.</Text>
      </View>
    );
  }

  // ── Fetch Routes ────────────────────────────────────────────────────

  const fetchRoutes = useCallback(async (refresh = false) => {
    if (refresh) setIsRefreshing(true);
    else setIsLoading(true);
    setError(null);
    try {
      const response = await transportApi.routes();
      setRoutes(response.data?.data ?? response.data ?? []);
    } catch {
      setError('Failed to load transport routes.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchRoutes();
  }, [fetchRoutes]);

  // ── Toggle Route Expand ─────────────────────────────────────────────

  const toggleRoute = async (routeId: string) => {
    if (expandedRoute === routeId) {
      setExpandedRoute(null);
      return;
    }
    setExpandedRoute(routeId);

    if (!routeStudents[routeId]) {
      setLoadingStudents(routeId);
      try {
        const response = await transportApi.routeStudents(routeId);
        const data = response.data?.data ?? response.data ?? [];
        setRouteStudents((prev) => ({ ...prev, [routeId]: data }));
      } catch {
        // fail silently, show empty
        setRouteStudents((prev) => ({ ...prev, [routeId]: [] }));
      } finally {
        setLoadingStudents(null);
      }
    }
  };

  // ── Loading / Error ─────────────────────────────────────────────────

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Loading routes…</Text>
      </View>
    );
  }

  if (error && routes.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => fetchRoutes()}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Transport</Text>
        <Text style={styles.headerSub}>Routes and assignments</Text>
      </View>

      {/* GPS Placeholder */}
      <View style={styles.gpsPlaceholder}>
        <Text style={styles.gpsIcon}>📍</Text>
        <Text style={styles.gpsText}>Live GPS tracking coming soon</Text>
      </View>

      <FlatList
        data={routes}
        keyExtractor={(item) => item.id}
        contentContainerStyle={styles.list}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={() => fetchRoutes(true)}
            colors={[colors.primary]}
          />
        }
        renderItem={({ item }) => {
          const isExpanded = expandedRoute === item.id;
          const students = routeStudents[item.id] ?? [];
          const isLoadingStudents = loadingStudents === item.id;

          return (
            <View style={styles.card}>
              <TouchableOpacity
                style={styles.cardHeader}
                onPress={() => toggleRoute(item.id)}
                activeOpacity={0.75}
              >
                <View style={{ flex: 1 }}>
                  <Text style={styles.cardTitle}>{item.route_name}</Text>
                  {item.vehicle_number && (
                    <Text style={styles.cardMeta}>Vehicle: {item.vehicle_number}</Text>
                  )}
                  {item.driver_name && (
                    <Text style={styles.cardMeta}>Driver: {item.driver_name}</Text>
                  )}
                  <Text style={styles.cardMeta}>
                    {item.stops_count ?? 0} stops · {item.students_count ?? 0} students
                  </Text>
                </View>
                <Text style={styles.expandIcon}>{isExpanded ? '▲' : '▼'}</Text>
              </TouchableOpacity>

              {isExpanded && (
                <View style={styles.expandedSection}>
                  {isLoadingStudents ? (
                    <ActivityIndicator size="small" color={colors.primary} style={{ padding: spacing.md }} />
                  ) : students.length === 0 ? (
                    <Text style={styles.noStudentsText}>No students assigned to this route.</Text>
                  ) : (
                    students.map((student) => (
                      <View key={student.id} style={styles.studentRow}>
                        <Text style={styles.studentName}>{student.student_name}</Text>
                        <Text style={styles.studentStop}>
                          {student.stop_name}
                          {student.pickup_time ? ` · ${student.pickup_time}` : ''}
                        </Text>
                      </View>
                    ))
                  )}
                </View>
              )}
            </View>
          );
        }}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyIcon}>🚌</Text>
            <Text style={styles.emptyText}>No transport routes configured.</Text>
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
  gpsPlaceholder: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    backgroundColor: colors.info + '15', paddingVertical: spacing.sm, paddingHorizontal: spacing.md,
  },
  gpsIcon: { fontSize: 16, marginRight: spacing.xs },
  gpsText: { fontSize: fontSize.sm, color: colors.info, fontWeight: fontWeight.medium },
  list: { padding: spacing.md },
  card: { backgroundColor: colors.white, borderRadius: borderRadius.md, marginBottom: spacing.sm, ...shadows.sm, overflow: 'hidden' },
  cardHeader: { flexDirection: 'row', alignItems: 'center', padding: spacing.md },
  cardTitle: { fontSize: fontSize.md, fontWeight: fontWeight.semibold, color: colors.textPrimary },
  cardMeta: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  expandIcon: { fontSize: fontSize.sm, color: colors.textMuted, marginLeft: spacing.sm },
  expandedSection: { borderTopWidth: 1, borderTopColor: colors.border, paddingHorizontal: spacing.md, paddingBottom: spacing.sm },
  studentRow: { paddingVertical: spacing.sm, borderBottomWidth: 1, borderBottomColor: colors.border },
  studentName: { fontSize: fontSize.sm, fontWeight: fontWeight.medium, color: colors.textPrimary },
  studentStop: { fontSize: fontSize.xs, color: colors.textMuted, marginTop: 2 },
  noStudentsText: { fontSize: fontSize.sm, color: colors.textMuted, paddingVertical: spacing.md, textAlign: 'center' },
  emptyContainer: { alignItems: 'center', marginTop: 60 },
  emptyIcon: { fontSize: 48, marginBottom: spacing.md },
  emptyText: { fontSize: fontSize.md, color: colors.textMuted, textAlign: 'center' },
  loadingText: { marginTop: spacing.sm, fontSize: fontSize.sm, color: colors.textMuted },
  errorText: { fontSize: fontSize.md, color: colors.danger, textAlign: 'center', marginBottom: spacing.md },
  retryBtn: { backgroundColor: colors.primary, paddingHorizontal: spacing.lg, paddingVertical: spacing.sm, borderRadius: borderRadius.sm },
  retryText: { color: colors.white, fontWeight: fontWeight.semibold, fontSize: fontSize.sm },
});
