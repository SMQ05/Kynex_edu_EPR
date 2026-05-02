# KynexEdu Student & Parent App

React Native (Expo) mobile application for **students and parents**.

---

## Overview

The Student & Parent App lets parents monitor their children's school life and lets
students access their own academic information — all in real time.

| Feature                   | Status |
|---------------------------|--------|
| Login (parent / student)  | ✅ Done |
| Home dashboard            | ✅ Done |
| Attendance (monthly view) | ✅ Done |
| Fee status & history      | ✅ Done |
| Exam results              | ✅ Done |
| School notices            | ✅ Done |
| Homework tracker          | ✅ Done |
| Homework submission       | ✅ Done (file upload in next update) |
| Timetable                 | 🔜 Planned |
| Push notifications        | 🔜 Planned |

---

## Tech Stack

| Layer        | Technology |
|--------------|------------|
| Framework    | [Expo](https://expo.dev) (managed workflow) SDK 52 |
| Language     | TypeScript 5 |
| Navigation   | React Navigation 6 (Bottom Tabs + Stack) |
| HTTP Client  | Axios 1.x with `expo-secure-store` token persistence |
| State        | Zustand (`authStore`) |
| UI           | React Native core components + custom green-accent theme |

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
cd mobile/student-parent-app

# 2. Install dependencies
npm install

# 3. Start the Expo dev server
npx expo start

# 4. Scan the QR code with Expo Go (Android) or Camera (iOS)
```

---

## Project Structure

```
mobile/student-parent-app/
├── App.tsx                          # Root navigator (Stack + Bottom Tabs)
├── app.json                         # Expo config (slug, icons, permissions)
├── package.json
├── tsconfig.json
└── src/
    ├── screens/
    │   ├── LoginScreen.tsx          # School slug + email/password login
    │   ├── HomeScreen.tsx           # Child selector + summary cards
    │   ├── AttendanceScreen.tsx     # Monthly attendance calendar
    │   ├── FeeScreen.tsx            # Fee status + payment history
    │   ├── ResultsScreen.tsx        # Exam results by term
    │   ├── NoticesScreen.tsx        # School notices with detail modal
    │   └── HomeworkScreen.tsx       # Homework list, filter, submit
    ├── services/
    │   └── api.ts                   # Axios instance + parent-specific endpoints
    ├── stores/
    │   └── authStore.ts             # Zustand store: user, children, token
    ├── theme/
    │   └── index.ts                 # Green-accent color palette + tokens
    └── types/
        └── index.ts                 # Shared TypeScript interfaces
```

---

## Authentication Flow

1. Parent/student enters **school code** + email + password
2. App constructs tenant base URL: `https://{slug}.kynexedu.com/api/v1`
3. `POST /auth/login` returns a Sanctum personal access token
4. Token persisted in `expo-secure-store` under `kynexedu_auth_token`
5. Every request attaches `Authorization: Bearer <token>`
6. On 401, token is cleared and the user returns to the login screen

### Parent vs Student Roles

The backend returns a `role` field in the `/auth/me` response:

- **`parent`** — The home screen shows a child selector. All data endpoints
  accept a `student_id` parameter to scope results to the selected child.
- **`student`** — The home screen defaults to the authenticated student's own
  data; no child selector is shown.

---

## API Base URL

```
https://{school-slug}.kynexedu.com/api/v1
```

For local development, update `src/services/api.ts`:

```typescript
let baseURL = 'http://10.0.2.2:8000/api/v1'; // Android emulator → host
// or
let baseURL = 'http://localhost:8000/api/v1'; // iOS simulator
```

---

## Key API Endpoints Used

| Screen       | Endpoint                                  |
|--------------|-------------------------------------------|
| Login        | `POST /auth/login`                        |
| Home         | `GET /students` (children list)           |
| Attendance   | `GET /attendance?student_id=&month=`      |
| Fees         | `GET /fees/{studentId}`                   |
| Results      | `GET /results?student_id=`                |
| Notices      | `GET /notices`                            |
| Homework     | `GET /homework?student_id=`               |
| Submit HW    | `POST /homework/{id}/submit`              |

---

## Homework Screen

[`HomeworkScreen.tsx`](src/screens/HomeworkScreen.tsx) supports:

- **Filter tabs**: All · Pending · Done · Overdue
- Automatic overdue detection (client-side, based on `due_date`)
- Detail modal with teacher feedback and marks display
- Submit button for pending assignments (triggers `POST /homework/{id}/submit`)
- Pull-to-refresh

---

## Theme

The parent app uses an **emerald-green** primary (`#059669`) to visually
distinguish it from the blue management app. All colour tokens are in
[`src/theme/index.ts`](src/theme/index.ts).

---

## Building for Production

```bash
# Install EAS CLI
npm install -g eas-cli

eas login

# Android
eas build --platform android

# iOS
eas build --platform ios
```

---

## Push Notifications

The app registers the Expo Push Token via `POST /auth/push-token` after login.
Notifications are sent server-side through Laravel's `NotificationService` and
dispatched via `expo-server-sdk`.

---

## Contributing

1. Branch from `main` using `feature/parent-<feature-name>`
2. Follow the existing TypeScript + ESLint config
3. Use theme tokens from `src/theme` — no hardcoded colours
4. Test on both iOS and Android before submitting a PR
