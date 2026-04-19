/**
 * KynexEdu Theme Constants
 *
 * Design system colors, spacing, and typography for mobile apps.
 */

export const colors = {
  // Brand
  primary: '#1e40af',       // Blue 800
  primaryLight: '#3b82f6',  // Blue 500
  primaryDark: '#1e3a8a',   // Blue 900

  // Semantic
  success: '#16a34a',       // Green 600
  warning: '#d97706',       // Amber 600
  danger: '#dc2626',        // Red 600
  info: '#0891b2',          // Cyan 600

  // Neutrals
  white: '#ffffff',
  background: '#f8fafc',    // Slate 50
  surface: '#ffffff',
  border: '#e2e8f0',        // Slate 200
  textPrimary: '#0f172a',   // Slate 900
  textSecondary: '#64748b', // Slate 500
  textMuted: '#94a3b8',     // Slate 400
  overlay: 'rgba(0, 0, 0, 0.5)',

  // Status colors for attendance
  present: '#16a34a',
  absent: '#dc2626',
  late: '#d97706',
  excused: '#7c3aed',
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
