/**
 * App.tsx — Management App root component.
 *
 * Handles:
 *   - Auth state check on startup
 *   - Deep link routing (kynexedu-mgmt:// scheme)
 *   - Conditional rendering: Login vs Main Tab Navigator
 *   - Bottom tab navigation with Dashboard, Students, Attendance, Notifications
 *   - Push notification permission banner
 */
import React, { useEffect, useRef } from 'react';
import { ActivityIndicator, View, Text, StyleSheet, StatusBar, TouchableOpacity } from 'react-native';
import { NavigationContainer, NavigationContainerRef } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import * as Linking from 'expo-linking';
import { useAuthStore } from './src/stores/authStore';
import { colors } from './src/theme';

import LoginScreen from './src/screens/LoginScreen';
import DashboardScreen from './src/screens/DashboardScreen';
import StudentsScreen from './src/screens/StudentsScreen';
import AttendanceScreen from './src/screens/AttendanceScreen';
import NotificationCenterScreen from './src/screens/NotificationCenterScreen';
import RoleSwitcherScreen from './src/screens/RoleSwitcherScreen';
import ExamMarksEntryScreen from './src/screens/ExamMarksEntryScreen';
import FeeCollectionScreen from './src/screens/FeeCollectionScreen';
import PayrollScreen from './src/screens/PayrollScreen';
import LibraryScreen from './src/screens/LibraryScreen';
import TransportScreen from './src/screens/TransportScreen';
import OnlineClassesScreen from './src/screens/OnlineClassesScreen';
import ReportsScreen from './src/screens/ReportsScreen';

const Tab = createBottomTabNavigator();
const Stack = createNativeStackNavigator();

/**
 * Map deep link paths to navigation actions.
 * Management app handles: approval, attendance, notices, billing.
 * Unrecognized paths gracefully fall back to Dashboard.
 */
function handleDeepLink(
  url: string,
  navigationRef: React.RefObject<NavigationContainerRef<any> | null>,
) {
  const parsed = Linking.parse(url);
  const path = parsed.path ?? '';
  const segments = path.split('/').filter(Boolean);

  if (!navigationRef.current) return;

  const nav = navigationRef.current;

  try {
    if (segments[0] === 'attendance') {
      nav.navigate('Main', {
        screen: 'Attendance',
        params: { date: segments[2] ?? undefined },
      });
    } else if (segments[0] === 'approval' && segments[1]) {
      nav.navigate('Main', {
        screen: 'Dashboard',
        params: { approvalId: segments[1] },
      });
    } else if (segments[0] === 'notice' && segments[1]) {
      nav.navigate('Main', {
        screen: 'Dashboard',
        params: { noticeId: segments[1] },
      });
    } else if (segments[0] === 'billing' && segments[1]) {
      nav.navigate('Main', {
        screen: 'Dashboard',
        params: { invoiceId: segments[1] },
      });
    } else if (segments[0] === 'exam' && segments[1] === 'result') {
      nav.navigate('Main', {
        screen: 'Dashboard',
        params: { examId: segments[2] },
      });
    } else if (segments[0] === 'fee' && segments[1] === 'due') {
      nav.navigate('Main', {
        screen: 'Students',
        params: { studentId: segments[2] },
      });
    } else if (segments[0] === 'notifications') {
      nav.navigate('Main', { screen: 'Notifications' });
    } else {
      nav.navigate('Main', { screen: 'Dashboard' });
    }
  } catch {
    // Navigation not ready or screen not found — ignore silently
  }
}

function NotificationBellIcon({ color }: { color: string }) {
  const { unreadNotificationCount } = useAuthStore();
  return (
    <View>
      <Text style={{ fontSize: 20, color }}>🔔</Text>
      {unreadNotificationCount > 0 && (
        <View style={styles.badge}>
          <Text style={styles.badgeText}>
            {unreadNotificationCount > 99 ? '99+' : unreadNotificationCount}
          </Text>
        </View>
      )}
    </View>
  );
}

function MainTabs() {
  return (
    <Tab.Navigator
      screenOptions={{
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarStyle: {
          backgroundColor: colors.white,
          borderTopColor: colors.border,
          paddingBottom: 4,
          height: 56,
        },
        headerStyle: { backgroundColor: colors.primary },
        headerTintColor: colors.white,
        headerTitleStyle: { fontWeight: '600' },
      }}
    >
      <Tab.Screen
        name="Dashboard"
        component={DashboardScreen}
        options={{
          headerShown: false,
          tabBarLabel: 'Home',
          tabBarIcon: ({ color }) => (
            <View><StatusBar barStyle="light-content" /></View>
          ),
        }}
      />
      <Tab.Screen
        name="Students"
        component={StudentsScreen}
        options={{
          tabBarLabel: 'Students',
          headerTitle: 'Students',
        }}
      />
      <Tab.Screen
        name="Attendance"
        component={AttendanceScreen}
        options={{
          headerShown: false,
          tabBarLabel: 'Attendance',
        }}
      />
      <Tab.Screen
        name="Notifications"
        component={NotificationCenterScreen}
        options={{
          headerShown: false,
          tabBarLabel: 'Alerts',
          tabBarIcon: ({ color }) => <NotificationBellIcon color={color} />,
        }}
      />
    </Tab.Navigator>
  );
}

function PushBanner() {
  const { pushPermissionDenied } = useAuthStore();
  const [dismissed, setDismissed] = React.useState(false);

  if (!pushPermissionDenied || dismissed) return null;

  return (
    <View style={styles.pushBanner}>
      <Text style={styles.pushBannerText}>
        Enable notifications to get fee and attendance alerts
      </Text>
      <TouchableOpacity onPress={() => setDismissed(true)}>
        <Text style={styles.pushBannerDismiss}>✕</Text>
      </TouchableOpacity>
    </View>
  );
}

export default function App() {
  const { isAuthenticated, isLoading, checkAuth } = useAuthStore();
  const navigationRef = useRef<NavigationContainerRef<any>>(null);

  useEffect(() => {
    checkAuth();
  }, []);

  // Deep link handling
  useEffect(() => {
    Linking.getInitialURL().then((url: string | null) => {
      if (url) handleDeepLink(url, navigationRef);
    });

    const subscription = Linking.addEventListener(
      'url',
      ({ url }: { url: string }) => handleDeepLink(url, navigationRef),
    );

    return () => subscription.remove();
  }, []);

  if (isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <NavigationContainer ref={navigationRef}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primary} />
      {isAuthenticated && <PushBanner />}
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {isAuthenticated ? (
          <>
            <Stack.Screen name="Main" component={MainTabs} />
            <Stack.Screen
              name="RoleSwitcher"
              component={RoleSwitcherScreen}
              options={{ headerShown: true, headerTitle: 'Switch Role', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
            <Stack.Screen
              name="ExamMarksEntry"
              component={ExamMarksEntryScreen}
              options={{ headerShown: true, headerTitle: 'Exam Marks', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
            <Stack.Screen
              name="FeeCollection"
              component={FeeCollectionScreen}
              options={{ headerShown: true, headerTitle: 'Fee Collection', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
            <Stack.Screen
              name="Payroll"
              component={PayrollScreen}
              options={{ headerShown: true, headerTitle: 'Payroll', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
            <Stack.Screen
              name="Library"
              component={LibraryScreen}
              options={{ headerShown: true, headerTitle: 'Library', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
            <Stack.Screen
              name="Transport"
              component={TransportScreen}
              options={{ headerShown: true, headerTitle: 'Transport', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
            <Stack.Screen
              name="OnlineClasses"
              component={OnlineClassesScreen}
              options={{ headerShown: true, headerTitle: 'Online Classes', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
            <Stack.Screen
              name="Reports"
              component={ReportsScreen}
              options={{ headerShown: true, headerTitle: 'Reports', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
            />
          </>
        ) : (
          <Stack.Screen name="Login" component={LoginScreen} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
  },
  badge: {
    position: 'absolute',
    top: -4,
    right: -8,
    backgroundColor: colors.danger,
    borderRadius: 10,
    minWidth: 18,
    height: 18,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
  },
  badgeText: {
    color: colors.white,
    fontSize: 10,
    fontWeight: '700',
  },
  pushBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fffbeb',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#fde68a',
  },
  pushBannerText: {
    flex: 1,
    fontSize: 13,
    color: '#92400e',
  },
  pushBannerDismiss: {
    fontSize: 16,
    color: '#92400e',
    paddingLeft: 12,
  },
});
