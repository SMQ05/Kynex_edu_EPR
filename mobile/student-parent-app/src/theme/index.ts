/**
 * KynexEdu Parent App — Theme
 *
 * Uses a green-tinted primary to visually distinguish from the blue management app.
 */

export const colors = {
  // Brand — green accent for parent app
  primary: '#059669',        // Emerald 600
  primaryLight: '#34d399',   // Emerald 400
  primaryDark: '#047857',    // Emerald 700

  // Semantic
  success: '#16a34a',
  warning: '#d97706',
  danger: '#dc2626',
  info: '#0891b2',

  // Neutrals
  white: '#ffffff',
  background: '#f0fdf4',    // Green 50
  surface: '#ffffff',
  border: '#e2e8f0',
  textPrimary: '#0f172a',
  textSecondary: '#64748b',
  textMuted: '#94a3b8',
  overlay: 'rgba(0, 0, 0, 0.5)',

  // Status colors for attendance
  present: '#16a34a',
  absent: '#dc2626',
  late: '#d97706',
  excused: '#7c3aed',
  halfDay: '#f59e0b',
  holiday: '#6b7280',
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  xxl: 48,
};

export const borderRadius = {
  sm: 6,
  md: 10,
  lg: 16,
  xl: 24,
  full: 9999,
};

export const fontSize = {
  xs: 12,
  sm: 14,
  md: 16,
  lg: 18,
  xl: 22,
  xxl: 28,
  display: 34,
};

export const fontWeight = {
  regular: '400' as const,
  medium: '500' as const,
  semibold: '600' as const,
  bold: '700' as const,
};

export const shadows = {
  sm: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  md: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  lg: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 8,
    elevation: 5,
  },
};
