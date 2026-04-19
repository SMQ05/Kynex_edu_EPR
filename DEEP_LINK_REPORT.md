# KynexEdu Deep Link Service Report

## Summary
The codebase contains a complete deep linking implementation for mobile notifications with app-specific URL schemes to handle both management and student/parent apps. The system integrates deep links with notifications, push messaging, and in-app notification handling.

---

## 1. PRIMARY SERVICE: DeepLinkService.php

**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/app/Services/DeepLinkService.php`

### Key Features:

#### A. Deep Link Schemes (Lines 24-27)
```php
private const MOBILE_SCHEMES = [
    'management'     => 'kynexedu-mgmt',
    'student_parent' => 'kynexedu-parent',
];
```

**Purpose:** Two distinct schemes to avoid app disambiguation when both apps are installed:
- `kynexedu-mgmt://` - Management app
- `kynexedu-parent://` - Student/Parent app

#### B. Event-Triggered Deep Links (Lines 36-87)
`DeepLinkService::generate(string $eventTrigger, array $context = []): ?string`

**Supported Event Triggers:**
1. `student.absent` → Routes to attendance records (Lines 39-45)
2. `fee.overdue` → Routes to fee collection page (Lines 47-52)
3. `exam.result_published` → Routes to exam results (Lines 54-59)
4. `leave.approved` / `leave.rejected` → Routes to leave requests (Lines 61-63)
5. `approval.requested` / `approval.approved` / `approval.rejected` → Routes to approvals (Lines 65-71)
6. `monthly_billing` → Routes to billing statement (Lines 73-75)
7. `notice.published` → Routes to notices (Lines 78-83)

#### C. Tenant-Aware URL Construction (Lines 95-117)
`tenantRoute(string $name, array $params = []): string`

**Behavior:**
- Constructs subdomain-based URLs: `https://{tenant-id}.kynexedu.com`
- Falls back to `https://kynexedu.com` if tenant not initialized
- Uses Laravel named routes for path resolution
- Supports fallback path construction if routes don't exist yet

#### D. Mobile Deep Link Generation (Lines 126-131)
`generateMobileDeepLink(string $appType, string $path): string`

**Usage:** Generates mobile-specific URLs like `kynexedu-parent://fee/due/01HYABC`

**Parameters:**
- `$appType`: 'management' or 'student_parent'
- `$path`: Path portion (e.g., 'exam/result/01HYXYZ')

---

## 2. MOBILE APP CONFIGURATIONS

### 2A. Management App Configuration
**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/mobile/management-app/app.json`

**Key Settings (Lines 1-47):**
```json
{
  "expo": {
    "name": "KynexEdu Management",
    "slug": "kynexedu-management",
    "version": "1.0.0",
    "orientation": "portrait",
    "icon": "./assets/icon.png",
    "scheme": "kynexedu-mgmt",          // ← DEEP LINK SCHEME
    "userInterfaceStyle": "automatic",
    ...
    "ios": {
      "supportsTablet": true,
      "bundleIdentifier": "com.kynexsolutions.management"
    },
    "android": {
      "adaptiveIcon": {...},
      "package": "com.kynexsolutions.management"
    },
    "plugins": [
      "expo-router",
      "expo-secure-store",
      ["expo-notifications", {...}]
    ]
  }
}
```

**Critical Line 8:** `"scheme": "kynexedu-mgmt"`

### 2B. Student/Parent App Configuration
**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/mobile/student-parent-app/app.json`

**Key Settings (Lines 1-47):**
```json
{
  "expo": {
    "name": "KynexEdu Parent",
    "slug": "kynexedu-parent",
    "version": "1.0.0",
    "orientation": "portrait",
    "icon": "./assets/icon.png",
    "scheme": "kynexedu-parent",         // ← DEEP LINK SCHEME
    "userInterfaceStyle": "light",
    "newArchEnabled": true,
    ...
    "ios": {
      "supportsTablet": true,
      "bundleIdentifier": "com.kynexsolutions.parent"
    },
    "android": {
      "adaptiveIcon": {...},
      "package": "com.kynexsolutions.parent"
    },
    "plugins": [
      "expo-router",
      "expo-secure-store",
      ["expo-notifications", {...}]
    ]
  }
}
```

**Critical Line 8:** `"scheme": "kynexedu-parent"`

---

## 3. MOBILE APP DEEP LINK HANDLERS

### 3A. Student/Parent App Handler
**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/mobile/student-parent-app/App.tsx`

**Deep Link Handling Function (Lines 32-63):**
```typescript
function handleDeepLink(
  url: string,
  navigationRef: React.RefObject<NavigationContainerRef<any> | null>,
)
```

**Supported Routes (Lines 45-59):**
| Path Pattern | Action | Screen | Params |
|---|---|---|---|
| `fee/due/{id}` | Navigate to Fees | `Fees` | `studentId` |
| `exam/result/{id}` | Navigate to Results | `Results` | `examId` |
| `attendance/{date}/{id}` | Navigate to Attendance | `Attendance` | `date` |
| `notice/{id}` | Navigate to Notices | `Notices` | `noticeId` |
| `leave/{id}` | Fallback to Home | `Home` | `leaveId` |
| *unrecognized* | Graceful fallback | `Home` | - |

**Implementation Details:**
- Uses `Linking.parse(url)` to parse deep links
- Splits path into segments for route matching
- Handles both cold start and warm start deep links (Lines 138-149)
- Graceful error handling with try-catch (Lines 60-62)

### 3B. Management App Handler
**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/mobile/management-app/App.tsx`

**Deep Link Handling Function (Lines 32-83):**
```typescript
function handleDeepLink(
  url: string,
  navigationRef: React.RefObject<NavigationContainerRef<any> | null>,
)
```

**Supported Routes (Lines 45-79):**
| Path Pattern | Action | Screen/Navigation | Params |
|---|---|---|---|
| `attendance/{date}/{id}` | Navigate | `Main` → `Attendance` | `date` |
| `approval/{id}` | Navigate | `Main` → `Dashboard` | `approvalId` |
| `notice/{id}` | Navigate | `Main` → `Dashboard` | `noticeId` |
| `billing/{id}` | Navigate | `Main` → `Dashboard` | `invoiceId` |
| `exam/result/{id}` | Navigate | `Main` → `Dashboard` | `examId` |
| `fee/due/{id}` | Navigate | `Main` → `Students` | `studentId` |
| *unrecognized* | Fallback | `Main` → `Dashboard` | - |

**Implementation Details:**
- Uses nested navigation with `nav.navigate('Main', {screen: ..., params: ...})`
- Consistent error handling with graceful fallback (Lines 80-82)
- Cold start and warm start handling (Lines 142-155)

---

## 4. NOTIFICATION SERVICE INTEGRATION

**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/app/Services/NotificationService.php`

### Deep Link Usage Points:

#### A. In-App Notifications (Lines 78-98)
```php
public static function sendImmediate(
    string $channel,
    string $userId,
    string $body,
    ?string $eventTrigger = null,
    array $variables = [],
): void {
    if ($channel === 'in_app') {
        $deepLink = $eventTrigger
            ? DeepLinkService::generate($eventTrigger, $variables)
            : null;

        InAppNotification::create([
            'user_id'    => $userId,
            'title'      => 'Notification',
            'body'       => $body,
            'type'       => 'info',
            'action_url' => $deepLink,  // ← STORES DEEP LINK URL
        ]);
    }
}
```

#### B. Notification Dispatch (Lines 206-215)
```php
// Generate deep link if event trigger is provided
$deepLink = null;
if ($eventTrigger) {
    $deepLink = DeepLinkService::generate($eventTrigger, $variables);
}

// For SMS/WhatsApp, append deep link to message body
if ($deepLink && in_array($channel, ['sms', 'whatsapp'])) {
    $body .= "\nView details: {$deepLink}";
}
```

#### C. Push Priority Dispatch (Lines 267-281)
```php
protected function dispatchWithPushPriority(
    Model $notifiable,
    ?string $subject,
    string $body,
    ?string $actionUrl,
    NotificationLog $log,
): void {
    // 1. Always create the in-app notification record
    $inApp = InAppNotification::create([
        'user_id'    => $notifiable->getKey(),
        'title'      => $subject ?? 'Notification',
        'body'       => $body,
        'type'       => 'info',
        'action_url' => $actionUrl,  // ← INCLUDES DEEP LINK
    ]);
    ...
}
```

### Notification Channel Support (Lines 175-245):
- **Push (FCM):** Deep link passed via `data['action_url']` in push payload
- **SMS/WhatsApp:** Deep link appended to message body: `"View details: {$deepLink}"`
- **Email:** Deep link embedded in notification
- **In-App:** Deep link stored as `action_url` in InAppNotification record

---

## 5. JOB INTEGRATION

### 5A. NotifyNoticePublished Job
**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/app/Jobs/NotifyNoticePublished.php`

**Line 11:** Imports DeepLinkService
**Lines 47-49:** 
```php
$noticeUrl = DeepLinkService::generate('notice.published', [
    'notice_id' => $notice->id,
]);
```

### 5B. NotifyResultPublished Job
**File Path:** `/home/Kynex_Solutions/Pictures/KynexSolution.com/KynexEdu-ERP/app/Jobs/NotifyResultPublished.php`

**Line 10:** Imports DeepLinkService
**Lines 55-57:**
```php
$resultUrl = DeepLinkService::generate('exam.result_published', [
    'exam_id' => $exam->id,
]);
```

---

## 6. FULL FLOW EXAMPLE: Fee Overdue Notification

```
1. Event Trigger: 'fee.overdue' with context {student_id: '123'}
   ↓
2. DeepLinkService::generate('fee.overdue', ['student_id' => '123'])
   → Resolves to: 'https://{tenant}.kynexedu.com/fees/collect?student=123'
   ↓
3. NotificationService dispatches via channels:
   - Push (FCM):     action_url = 'https://{tenant}.kynexedu.com/...'
   - SMS/WhatsApp:   body += '\nView details: https://{tenant}.kynexedu.com/...'
   - In-App:         action_url = 'https://{tenant}.kynexedu.com/...'
   ↓
4. Mobile app receives notification:
   - If app open:    Linking.addEventListener triggers handleDeepLink()
   - If cold start:  Linking.getInitialURL() triggers handleDeepLink()
   ↓
5. handleDeepLink() parses URL and navigates:
   - Student/Parent App: nav.navigate('Fees', {studentId: '123'})
   - Path: 'https://{tenant}.kynexedu.com/fees/collect?student=123'
   - Parsed: ['fees', 'collect', '123']
```

---

## 7. SCHEME VERIFICATION CHECKLIST

✅ **DeepLinkService Definition:**
- `kynexedu-mgmt` (management app)
- `kynexedu-parent` (student/parent app)

✅ **app.json Scheme Definitions:**
- Management: `"scheme": "kynexedu-mgmt"` (Line 8 of app.json)
- Parent: `"scheme": "kynexedu-parent"` (Line 8 of app.json)

✅ **Deep Link Handler Registration:**
- Both apps use `Linking.addEventListener()` for warm start (Lines 143-145 in each App.tsx)
- Both apps use `Linking.getInitialURL()` for cold start (Lines 138-140 in each App.tsx)

✅ **Event Triggers Mapped:**
1. student.absent → attendance-records.index
2. fee.overdue → collect-fee
3. exam.result_published → exam-results
4. leave.approved / leave.rejected → leave-requests.index
5. approval.requested / approval.approved / approval.rejected → pending-approvals
6. monthly_billing → billing-statement
7. notice.published → notices.index

---

## 8. CONFIGURATION FILES FOUND

| File | Type | Purpose |
|------|------|---------|
| `app/Services/DeepLinkService.php` | PHP Service | Core deep link generation logic |
| `mobile/management-app/app.json` | Expo Config | Management app scheme definition |
| `mobile/student-parent-app/app.json` | Expo Config | Parent app scheme definition |
| `mobile/management-app/App.tsx` | React Native | Management app deep link handler |
| `mobile/student-parent-app/App.tsx` | React Native | Parent app deep link handler |
| `app/Services/NotificationService.php` | PHP Service | Notification dispatcher with deep link integration |
| `app/Jobs/NotifyNoticePublished.php` | Laravel Job | Notice notification with deep link |
| `app/Jobs/NotifyResultPublished.php` | Laravel Job | Result notification with deep link |

---

## 9. IMPORTANT NOTES

⚠️ **Tenant Awareness:** Deep links are generated with tenant context (`{tenant-id}.kynexedu.com`)
- Falls back to `kynexedu.com` if tenant not initialized (Line 102 of DeepLinkService.php)

⚠️ **Multi-Channel Support:** Deep links are integrated with:
- Push notifications (FCM)
- SMS/WhatsApp (appended to message)
- Email (embedded in notification)
- In-app notifications (stored as action_url)

⚠️ **Error Handling:** Mobile apps gracefully handle:
- Unrecognized deep link paths (fallback to Home/Dashboard)
- Navigation not ready scenarios (silent catch)
- Route resolution failures (Best-effort path construction, Line 110)

---

## Summary Statistics

- **Total Files with Deep Link References:** 8
- **Supported Event Triggers:** 7 distinct types (+ 3 variants of approval/leave)
- **Mobile Schemes:** 2 (kynexedu-mgmt, kynexedu-parent)
- **Notification Channels:** 4 (Push, SMS, WhatsApp, Email)
- **Deep Link Path Patterns:** 6 distinct patterns per app

