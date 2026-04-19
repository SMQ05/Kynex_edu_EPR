/**
 * RoleSwitcherScreen — Management App
 *
 * Lists all roles the current user has, highlights the active role,
 * and allows switching between roles via PATCH /api/v1/auth/switch-role.
 */
import React, { useState } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuthStore } from '../stores/authStore';
import { colors, spacing, borderRadius, fontSize, fontWeight, shadows } from '../theme';

// ── Types ──────────────────────────────────────────────────────────────

const ROLE_DISPLAY_NAMES: Record<string, string> = {
  SCHOOL_ADMIN: 'School Admin',
  TEACHER: 'Teacher',
  INSTITUTE_OWNER: 'Institute Owner',
  HR_MANAGER: 'HR Manager',
  BURSAR: 'Bursar',
  ACCOUNTANT: 'Accountant',
  LIBRARIAN: 'Librarian',
  TRANSPORT_MANAGER: 'Transport Manager',
  EXAM_ADMIN: 'Exam Admin',
  RECEPTIONIST: 'Receptionist',
  HOSTEL_WARDEN: 'Hostel Warden',
  CAMPUS_HEAD: 'Campus Head',
  PARENT: 'Parent',
  STUDENT: 'Student',
};

function getRoleDisplayName(role: string): string {
  return ROLE_DISPLAY_NAMES[role] ?? role.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function getRoleIcon(role: string): string {
  const icons: Record<string, string> = {
    SCHOOL_ADMIN: '🏫',
    TEACHER: '👩‍🏫',
    INSTITUTE_OWNER: '🎓',
    HR_MANAGER: '👔',
    BURSAR: '💰',
    ACCOUNTANT: '📊',
    LIBRARIAN: '📚',
    TRANSPORT_MANAGER: '🚌',
    EXAM_ADMIN: '📝',
    RECEPTIONIST: '🗂️',
    HOSTEL_WARDEN: '🏠',
    CAMPUS_HEAD: '🏛️',
  };
  return icons[role] ?? '👤';
}

// ── Component ──────────────────────────────────────────────────────────

export default function RoleSwitcherScreen() {
  const navigation = useNavigation();
  const { user, switchRole } = useAuthStore();
  const [switching, setSwitching] = useState<string | null>(null);

  const roles = user?.roles ?? [];
  const activeRole = user?.active_role ?? null;

  const handleSwitch = async (role: string) => {
    if (role === activeRole) return;
    setSwitching(role);
    const success = await switchRole(role);
    setSwitching(null);

    if (success) {
      Alert.alert('Role Switched', `You are now logged in as ${getRoleDisplayName(role)}.`, [
        { text: 'OK', onPress: () => navigation.goBack() },
      ]);
    } else {
      Alert.alert('Error', 'Failed to switch role. Please try again.');
    }
  };

  // ── Empty State ────────────────────────────────────────────────────

  if (roles.length === 0) {
    return (
      <View style={styles.centered}>
        <Text style={styles.emptyIcon}>👤</Text>
        <Text style={styles.emptyText}>No roles assigned to your account.</Text>
      </View>
    );
  }

  // ── Render ─────────────────────────────────────────────────────────

  const renderRole = ({ item }: { item: string }) => {
    const isActive = item === activeRole;
    const isSwitching = item === switching;

    return (
      <TouchableOpacity
        style={[styles.roleCard, isActive && styles.roleCardActive]}
        onPress={() => handleSwitch(item)}
        disabled={isSwitching || isActive}
        activeOpacity={0.75}
      >
        <Text style={styles.roleIcon}>{getRoleIcon(item)}</Text>
        <View style={styles.roleInfo}>
          <Text style={[styles.roleName, isActive && styles.roleNameActive]}>
            {getRoleDisplayName(item)}
          </Text>
          {isActive && <Text style={styles.activeLabel}>Currently Active</Text>}
        </View>
        {isSwitching ? (
          <ActivityIndicator size="small" color={colors.primary} />
        ) : isActive ? (
          <View style={styles.activeDot} />
        ) : (
          <Text style={styles.switchText}>Switch</Text>
        )}
      </TouchableOpacity>
    );
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Switch Role</Text>
        <Text style={styles.headerSub}>
          Select a role to change your dashboard view
        </Text>
      </View>
      <FlatList
        data={roles}
        keyExtractor={(item) => item}
        renderItem={renderRole}
        contentContainerStyle={styles.list}
      />
    </View>
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
  list: {
    padding: spacing.md,
  },
  roleCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
    ...shadows.sm,
  },
  roleCardActive: {
    borderWidth: 2,
    borderColor: colors.primary,
    backgroundColor: colors.primary + '08',
  },
  roleIcon: {
    fontSize: 28,
    marginRight: spacing.md,
  },
  roleInfo: {
    flex: 1,
  },
  roleName: {
    fontSize: fontSize.md,
    fontWeight: fontWeight.semibold,
    color: colors.textPrimary,
  },
  roleNameActive: {
    color: colors.primary,
  },
  activeLabel: {
    fontSize: fontSize.xs,
    color: colors.primary,
    marginTop: 2,
  },
  activeDot: {
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: colors.primary,
  },
  switchText: {
    fontSize: fontSize.sm,
    fontWeight: fontWeight.medium,
    color: colors.primary,
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
});
