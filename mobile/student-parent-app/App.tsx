/**
 * KynexEdu Parent App — Root Component
 *
 * Bottom tab navigation for parents:
 * Home | Fees | Attendance | Results | Notifications
 *
 * Deep link routing via kynexedu-parent:// scheme.
 * Stack screens for additional features.
 */
import React, { useEffect, useRef } from 'react';
import { NavigationContainer, NavigationContainerRef } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { StatusBar } from 'expo-status-bar';
import { ActivityIndicator, View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import * as Linking from 'expo-linking';

import { useAuthStore } from './src/stores/authStore';
import LoginScreen from './src/screens/LoginScreen';
import HomeScreen from './src/screens/HomeScreen';
import FeeScreen from './src/screens/FeeScreen';
import AttendanceScreen from './src/screens/AttendanceScreen';
import ResultsScreen from './src/screens/ResultsScreen';
import NoticesScreen from './src/screens/NoticesScreen';
import NotificationCenterScreen from './src/screens/NotificationCenterScreen';
import TimetableScreen from './src/screens/TimetableScreen';
import OnlineClassScreen from './src/screens/OnlineClassScreen';
import LibraryFinesScreen from './src/screens/LibraryFinesScreen';
import HostelTransportScreen from './src/screens/HostelTransportScreen';
import RoleSwitcherScreen from './src/screens/RoleSwitcherScreen';
import { colors } from './src/theme';

const Tab = createBottomTabNavigator();
const Stack = createNativeStackNavigator();

/**
 * Map deep link paths to navigation actions.
 * Student/parent app handles: fees, attendance, results, notices.
 * Unrecognized paths gracefully fall back to Home.
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
    if (segments[0] === 'fee' && segments[1] === 'due') {
      nav.navigate('Main', { screen: 'Fees', params: { studentId: segments[2] ?? undefined } });
    } else if (segments[0] === 'exam' && segments[1] === 'result') {
      nav.navigate('Main', { screen: 'Results', params: { examId: segments[2] ?? undefined } });
    } else if (segments[0] === 'attendance') {
      nav.navigate('Main', { screen: 'Attendance', params: { date: segments[2] ?? undefined } });
    } else if (segments[0] === 'notice' && segments[1]) {
      nav.navigate('Main', { screen: 'Home', params: { noticeId: segments[1] } });
    } else if (segments[0] === 'notifications') {
      nav.navigate('Main', { screen: 'Notifications' });
    } else if (segments[0] === 'leave' && segments[1]) {
      nav.navigate('Main', { screen: 'Home', params: { leaveId: segments[1] } });
    } else {
      nav.navigate('Main', { screen: 'Home' });
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
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.textMuted,
        tabBarStyle: {
          backgroundColor: colors.white,
          borderTopColor: colors.border,
          height: 60,
          paddingBottom: 8,
          paddingTop: 4,
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '500',
        },
      }}
    >
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{
          tabBarIcon: ({ color, size }) => (
            <View><StatusBar style="auto" /></View>
          ),
          tabBarLabel: 'Home',
        }}
      />
      <Tab.Screen
        name="Fees"
        component={FeeScreen}
        options={{ tabBarLabel: 'Fees' }}
      />
      <Tab.Screen
        name="Attendance"
        component={AttendanceScreen}
        options={{ tabBarLabel: 'Attendance' }}
      />
      <Tab.Screen
        name="Results"
        component={ResultsScreen}
        options={{ tabBarLabel: 'Results' }}
      />
      <Tab.Screen
        name="Notifications"
        component={NotificationCenterScreen}
        options={{
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
  }, [checkAuth]);

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
      <View style={styles.loading}>
        <ActivityIndicator size="large" color={colors.primary} />
        <StatusBar style="dark" />
      </View>
    );
  }

  if (!isAuthenticated) {
    return (
      <NavigationContainer ref={navigationRef}>
        <StatusBar style="dark" />
        <LoginScreen />
      </NavigationContainer>
    );
  }

  return (
    <NavigationContainer ref={navigationRef}>
      <StatusBar style="light" />
      <PushBanner />
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="Main" component={MainTabs} />
        <Stack.Screen
          name="Timetable"
          component={TimetableScreen}
          options={{ headerShown: true, headerTitle: 'Timetable', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
        />
        <Stack.Screen
          name="OnlineClass"
          component={OnlineClassScreen}
          options={{ headerShown: true, headerTitle: 'Online Classes', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
        />
        <Stack.Screen
          name="LibraryFines"
          component={LibraryFinesScreen}
          options={{ headerShown: true, headerTitle: 'Library', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
        />
        <Stack.Screen
          name="HostelTransport"
          component={HostelTransportScreen}
          options={{ headerShown: true, headerTitle: 'Hostel & Transport', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
        />
        <Stack.Screen
          name="RoleSwitcher"
          component={RoleSwitcherScreen}
          options={{ headerShown: true, headerTitle: 'Switch Role', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
        />
        <Stack.Screen
          name="Notices"
          component={NoticesScreen}
          options={{ headerShown: true, headerTitle: 'Notices', headerStyle: { backgroundColor: colors.primary }, headerTintColor: colors.white }}
        />
      </Stack.Navigator>
    </NavigationContainer>
  );
}

const styles = StyleSheet.create({
  loading: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.background,
  },
  badge: {
    position: 'absolute',
    top: -4,
    right: -8,
    backgroundColor: '#dc2626',
    borderRadius: 10,
    minWidth: 18,
    height: 18,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 4,
  },
  badgeText: {
    color: '#ffffff',
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
