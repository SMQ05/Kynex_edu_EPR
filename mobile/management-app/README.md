# KynexEdu Management App

React Native (Expo) mobile application for **school administrators and staff**.

---

## Overview

The Management App gives principals, administrators, class teachers, and other staff
real-time access to the KynexEdu ERP from their smartphones.

| Feature              | Status |
|----------------------|--------|
| Dashboard with stats | ✅ Done |
| Student list + search| ✅ Done |
| Attendance marking   | ✅ Done |
| Notices board        | ✅ Done |
| Login / Auth         | ✅ Done |
| Fee collection       | 🔜 Planned |
| Timetable            | 🔜 Planned |
| Reports              | 🔜 Planned |

---

## Tech Stack

| Layer        | Technology |
|--------------|------------|
| Framework    | [Expo](https://expo.dev) (managed workflow) SDK 52 |
| Language     | TypeScript 5 |
| Navigation   | React Navigation 6 (Bottom Tabs + Stack) |
| HTTP Client  | Axios 1.x with `expo-secure-store` token persistence |
| State        | Zustand (`authStore`) |
| UI           | React Native core components + custom theme system |

---

## Prerequisites

- **Node.js** ≥ 18
- **npm** ≥ 9  *(or yarn / pnpm)*
- **Expo CLI**: `npm install -g expo-cli`
- **Expo Go** app on your physical device (iOS or Android)
  — *or* Android Studio / Xcode for local emulators

---

## Getting Started

```bash
# 1. Navigate to this directory
cd mobile/management-app

# 2. Install dependencies
npm install

# 3. Start the Expo dev server
npx expo start

# 4. Scan the QR code with Expo Go (Android) or Camera (iOS)
```

---

## Project Structure

```
mobile/management-app/
├── App.tsx                        # Root navigator (Stack + Bottom Tabs)
├── app.json                       # Expo config (slug, icons, permissions)
├── package.json
├── tsconfig.json
└── src/
    ├── screens/
    │   ├── LoginScreen.tsx        # School slug + email/password login
    │   ├── DashboardScreen.tsx    # Stats cards + quick actions + notices
    │   ├── StudentsScreen.tsx     # Paginated student list with search
    │   ├── AttendanceScreen.tsx   # Today's summary + quick-mark
    │   └── NoticesScreen.tsx      # Notices list with detail modal
    ├── services/
    │   └── api.ts                 # Axios instance + all API endpoint groups
    ├── stores/
    │   └── authStore.ts           # Zustand store: user, token, login/logout
    ├── theme/
    │   └── index.ts               # Colors, spacing, typography, shadows
    └── types/
        └── index.ts               # Shared TypeScript interfaces
```

---

## Authentication Flow

1. User enters **school code** (e.g. `greenwood`) + email + password
2. App constructs tenant base URL: `https://{slug}.kynexedu.com/api/v1`
3. `POST /auth/login` returns a Sanctum personal access token
4. Token is persisted in `expo-secure-store` under key `kynexedu_auth_token`
5. Every subsequent request attaches `Authorization: Bearer <token>`
6. On 401 response, token is cleared and the user is redirected to login

---

## API Base URL

The app targets the tenant-specific subdomain. The base URL is dynamically set
at login and also stored in `expo-secure-store` for subsequent launches:

```
https://{school-slug}.kynexedu.com/api/v1
```

For local development, update `src/services/api.ts`:

```typescript
let baseURL = 'http://10.0.2.2:8000/api/v1'; // Android emulator → host machine
// or
let baseURL = 'http://localhost:8000/api/v1'; // iOS simulator
```

---

## Environment / Configuration

All runtime configuration is resolved at login via the school slug. There are no
`.env` files for the Expo app — the tenant base URL is the only variable, and
it is computed from the slug the user enters.

For EAS Build secrets (push notification keys, etc.), use
[EAS Secrets](https://docs.expo.dev/build-reference/variables/).

---

## Building for Production

```bash
# Install EAS CLI
npm install -g eas-cli

# Configure your Expo account
eas login

# Build for Android (APK / AAB)
eas build --platform android

# Build for iOS (IPA)
eas build --platform ios
```

---

## Push Notifications

The app registers the Expo Push Token with the backend on login via
`POST /auth/push-token`. The backend stores it on the `school_users` record and
uses it when dispatching notifications through the `NotificationService`.

---

## Contributing

1. Branch from `main` using the pattern `feature/mgmt-<feature-name>`
2. Follow the existing TypeScript + ESLint config
3. Import theme tokens from `src/theme` — avoid hardcoded colours
4. Test on both iOS and Android before submitting a PR
