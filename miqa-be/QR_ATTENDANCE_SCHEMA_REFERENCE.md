# QR Attendance System - Database Schema Reference

---

## Table: presence_sessions

**Purpose:** Registry of attendance sessions/events

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Session identifier |
| class_room_id | BIGINT | FK → class_rooms, NOT NULL | Associated classroom |
| created_by_user_id | BIGINT | FK → users, NOT NULL | Teacher/Manager creating session |
| session_name | VARCHAR(255) | NOT NULL | Session title |
| session_type | ENUM | NOT NULL, DEFAULT 'class' | Type: class, event, exam_preparation |
| scheduled_start_at | TIMESTAMP | NULLABLE | Planned start time |
| scheduled_end_at | TIMESTAMP | NULLABLE | Planned end time |
| actual_start_at | TIMESTAMP | NULLABLE | When session actually started |
| actual_end_at | TIMESTAMP | NULLABLE | When session actually ended |
| gps_latitude | DECIMAL(10,8) | NULLABLE | Geofence center latitude |
| gps_longitude | DECIMAL(11,8) | NULLABLE | Geofence center longitude |
| gps_radius_meters | INT | DEFAULT 50 | Geofence radius in meters |
| is_active | BOOLEAN | DEFAULT FALSE | Whether accepting check-ins |
| notes | TEXT | NULLABLE | Session notes |
| created_at | TIMESTAMP | DEFAULT NOW | Record creation |
| updated_at | TIMESTAMP | DEFAULT NOW | Last modification |

**Indexes:**
- class_room_id
- created_by_user_id
- is_active
- created_at

**Relationships:**
- BelongsTo: classRoom (ClassRoom)
- BelongsTo: createdBy (User)
- HasMany: qrTokens (PresenceQrToken)
- HasMany: presences (Presence)

**Query Examples:**
```php
// Get active sessions for classroom
PresenceSession::where('class_room_id', $classroomId)
    ->where('is_active', true)
    ->get();

// Get sessions created by teacher
User::find($teacherId)->createdSessions()->get();

// Get attendance records for session
$session->presences()->with('user')->get();
```

---

## Table: presence_qr_tokens

**Purpose:** Single-use QR tokens with expiration

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Token record ID |
| uuid | UUID | UNIQUE, NOT NULL | Unique UUID v4 for QR code |
| presence_session_id | BIGINT | FK → presence_sessions, NOT NULL | Associated session |
| created_by_user_id | BIGINT | FK → users, NOT NULL | Who generated token |
| generated_at | TIMESTAMP | DEFAULT NOW | When token was created |
| expires_at | TIMESTAMP | NOT NULL | Expiration time (usually 30 sec) |
| is_used | BOOLEAN | DEFAULT FALSE | Whether already scanned |
| is_revoked | BOOLEAN | DEFAULT FALSE | Whether manually revoked |
| used_by_user_id | BIGINT | FK → users, NULLABLE | Who scanned the token |
| used_at | TIMESTAMP | NULLABLE | When token was used |
| revoked_at | TIMESTAMP | NULLABLE | When token was revoked |
| revoke_reason | VARCHAR(255) | NULLABLE | Reason for revocation |
| created_at | TIMESTAMP | DEFAULT NOW | Record creation |
| updated_at | TIMESTAMP | DEFAULT NOW | Last modification |

**Indexes:**
- uuid (UNIQUE)
- presence_session_id
- expires_at
- is_used
- created_by_user_id
- (is_used, expires_at) - Composite for expiration queries

**Constraints:**
- expires_at = generated_at + INTERVAL 30 SECOND
- is_used can only be TRUE if used_by_user_id and used_at are set

**Relationships:**
- BelongsTo: session (PresenceSession)
- BelongsTo: createdBy (User)
- BelongsTo: usedBy (User)
- HasMany: presences (Presence)

**Query Examples:**
```php
// Find valid token
PresenceQrToken::where('uuid', $qrUuid)
    ->where('is_used', false)
    ->where('is_revoked', false)
    ->where('expires_at', '>', now())
    ->first();

// Get unused tokens for session
$session->qrTokens()
    ->where('is_used', false)
    ->where('expires_at', '>', now())
    ->get();

// Clean up expired tokens (daily job)
PresenceQrToken::where('expires_at', '<', now())
    ->update(['is_expired' => true]);
```

---

## Table: presences

**Purpose:** Attendance records (core table)

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Attendance record ID |
| qr_token_id | BIGINT | FK → presence_qr_tokens, NOT NULL | QR token used |
| presence_session_id | BIGINT | FK → presence_sessions, NOT NULL | Session attended |
| user_id | BIGINT | FK → users, NOT NULL | Person who checked in |
| checked_in_at | TIMESTAMP | DEFAULT NOW | Check-in time |
| checked_out_at | TIMESTAMP | NULLABLE | Check-out time |
| duration_minutes | INT | NULLABLE | Total session duration |
| gps_latitude | DECIMAL(10,8) | NULLABLE | Check-in GPS latitude |
| gps_longitude | DECIMAL(11,8) | NULLABLE | Check-in GPS longitude |
| gps_distance_meters | DECIMAL(10,2) | NULLABLE | Distance from session location |
| is_within_geofence | BOOLEAN | NULLABLE | GPS validation result |
| device_fingerprint_json | JSON | NULLABLE | Device identification data |
| ip_address | INET | NULLABLE | Check-in IP address |
| user_agent | TEXT | NULLABLE | Browser/app user agent |
| is_valid | BOOLEAN | DEFAULT FALSE | Validation status |
| created_at | TIMESTAMP | DEFAULT NOW | Record creation |
| updated_at | TIMESTAMP | DEFAULT NOW | Last modification |

**Unique Constraint:**
- UNIQUE(user_id, presence_session_id) - One check-in per user per session

**Indexes:**
- user_id
- presence_session_id
- checked_in_at
- is_valid
- qr_token_id
- (presence_session_id, is_valid) - Session attendance queries
- (user_id, checked_in_at) - User attendance timeline

**Relationships:**
- BelongsTo: qrToken (PresenceQrToken)
- BelongsTo: session (PresenceSession)
- BelongsTo: user (User)
- HasMany: securityFlags (PresenceSecurityFlag)
- HasMany: auditLogs (PresenceAuditLog)

**Query Examples:**
```php
// Get valid attendance for session
$session->presences()->where('is_valid', true)->count();

// Check if user already attended session
Presence::where('user_id', $userId)
    ->where('presence_session_id', $sessionId)
    ->exists();

// Get flagged attendances
Presence::whereHas('securityFlags', function ($query) {
    $query->where('is_reviewed', false);
})->get();

// Calculate attendance rate
$valid = $session->presences()->where('is_valid', true)->count();
$total = $session->presences()->count();
$rate = ($total > 0) ? ($valid / $total) * 100 : 0;
```

---

## Table: presence_devices

**Purpose:** Track registered devices for fraud detection

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Device record ID |
| user_id | BIGINT | FK → users, NOT NULL | Device owner |
| device_fingerprint_hash | VARCHAR(255) | UNIQUE, NOT NULL | SHA-256 hash of device |
| device_name | TEXT | NULLABLE | Device name (e.g., iPhone 14) |
| device_type | VARCHAR(50) | NULLABLE | mobile, tablet, desktop |
| os_name | VARCHAR(50) | NULLABLE | iOS, Android, Windows, macOS |
| os_version | VARCHAR(50) | NULLABLE | Version number |
| app_version | VARCHAR(50) | NULLABLE | App version |
| is_trusted | BOOLEAN | DEFAULT FALSE | Admin-approved device |
| last_seen_at | TIMESTAMP | NULLABLE | Last check-in time |
| created_at | TIMESTAMP | DEFAULT NOW | Registration time |
| updated_at | TIMESTAMP | DEFAULT NOW | Last modification |

**Indexes:**
- user_id
- device_fingerprint_hash (UNIQUE)
- is_trusted
- last_seen_at

**Relationships:**
- BelongsTo: user (User)

**Fingerprint Hash Components:**
```
hash('sha256', implode('|', [
    user_agent,      // e.g., "Mozilla/5.0..."
    device_id,       // Hardware device ID
    os_name,         // e.g., "iOS"
    os_version,      // e.g., "17.0"
    app_version,     // e.g., "1.2.3"
]))
```

**Query Examples:**
```php
// Find or create device
PresenceDevice::updateOrCreateFromFingerprint($user, $fingerprint);

// Check if device is trusted
$device->is_trusted ? 'Trusted' : 'Unverified';

// Count user's devices
$user->registeredDevices()->count();

// Flag users with too many devices (potential compromise)
User::whereHas('registeredDevices', function ($query) {
    $query->selectRaw('user_id, count(*) as cnt')
        ->having('cnt', '>', 5)
        ->groupBy('user_id');
})->get();
```

---

## Table: presence_security_flags

**Purpose:** Track suspicious attendance activities

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Flag record ID |
| presence_id | BIGINT | FK → presences, NOT NULL | Flagged attendance |
| user_id | BIGINT | FK → users, NOT NULL | User who triggered flag |
| flag_type | VARCHAR(50) | NOT NULL | Type of flag (see below) |
| flag_severity | ENUM | NOT NULL, DEFAULT 'medium' | low, medium, high, critical |
| flag_description | TEXT | NOT NULL | Human-readable description |
| flag_metadata | JSON | NULLABLE | Additional context |
| is_reviewed | BOOLEAN | DEFAULT FALSE | Has admin reviewed? |
| reviewed_by_user_id | BIGINT | FK → users, NULLABLE | Admin who reviewed |
| review_notes | TEXT | NULLABLE | Admin review comments |
| action_taken | VARCHAR(50) | NULLABLE | approved, rejected, investigate |
| created_at | TIMESTAMP | DEFAULT NOW | Flag creation |
| updated_at | TIMESTAMP | DEFAULT NOW | Last modification |

**Flag Types:**
- `duplicate_token` - Token already used
- `expired_token` - Token past expiration
- `outside_geofence` - GPS too far from location
- `impossible_velocity` - User traveled too fast
- `device_mismatch` - Device fingerprint unknown
- `duplicate_session_entry` - Already checked in
- `hijack_attempt` - Suspicious user/device combo
- `suspicious_pattern` - Unusual behavior pattern

**Severity Levels:**
- `critical` - Definite fraud, requires immediate action
- `high` - Strong fraud indicators
- `medium` - Moderate concerns, should review
- `low` - Minor inconsistencies

**Actions:**
- `approved` - Attendance is valid
- `rejected` - Attendance is fraudulent (delete record)
- `investigate` - Needs further review

**Indexes:**
- presence_id
- user_id
- flag_severity
- is_reviewed
- flag_type
- created_at
- (flag_severity, is_reviewed) - Dashboard queries

**Relationships:**
- BelongsTo: presence (Presence)
- BelongsTo: user (User)
- BelongsTo: reviewedBy (User)

**Query Examples:**
```php
// Get unreviewed critical flags
PresenceSecurityFlag::where('flag_severity', 'critical')
    ->where('is_reviewed', false)
    ->orderBy('created_at', 'desc')
    ->get();

// Get all flags for user
$user->securityFlags()
    ->where('is_reviewed', false)
    ->with('presence.session')
    ->get();

// Approve attendance
$flag->approve(Auth::user(), 'Valid - confirmed with student');

// Reject attendance (fraud)
$flag->reject(Auth::user(), 'Confirmed GPS spoofing');

// Get flag statistics
$stats = [
    'critical' => PresenceSecurityFlag::where('flag_severity', 'critical')->count(),
    'high' => PresenceSecurityFlag::where('flag_severity', 'high')->count(),
    'medium' => PresenceSecurityFlag::where('flag_severity', 'medium')->count(),
    'low' => PresenceSecurityFlag::where('flag_severity', 'low')->count(),
];
```

---

## Table: presence_audit_logs

**Purpose:** Complete audit trail for compliance

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT | PK, AUTO_INCREMENT | Log entry ID |
| presence_id | BIGINT | FK → presences, NULLABLE | Related attendance |
| user_id | BIGINT | FK → users, NOT NULL | Subject of action |
| action | VARCHAR(255) | NOT NULL | Human-readable action |
| action_type | ENUM | NOT NULL | Specific action type (see below) |
| action_details | JSON | NULLABLE | Additional context |
| actor_user_id | BIGINT | FK → users, NULLABLE | Who performed action |
| actor_role | VARCHAR(50) | NULLABLE | Actor's role |
| ip_address | INET | NULLABLE | Request IP address |
| created_at | TIMESTAMP | DEFAULT NOW | Log timestamp |
| updated_at | TIMESTAMP | DEFAULT NOW | Last modification |

**Action Types:**
- `qr_generated` - QR token created
- `qr_scanned` - QR token used
- `attendance_recorded` - Check-in/out recorded
- `attendance_verified` - Result confirmed valid
- `fraud_detected` - Security flag triggered
- `flag_reviewed` - Admin reviewed flag
- `device_trusted` - Device marked as trusted
- `session_started` - Attendance started
- `session_ended` - Attendance ended

**Indexes:**
- presence_id
- user_id
- action_type
- created_at
- (user_id, created_at) - User audit trail
- (action_type, created_at) - Activity timeline

**Relationships:**
- BelongsTo: presence (Presence)
- BelongsTo: user (User)
- BelongsTo: actor (User)

**Query Examples:**
```php
// Get audit trail for user
PresenceAuditLog::forUser($user)
    ->limit(100)
    ->get();

// Get audit trail for attendance
PresenceAuditLog::forPresence($presence)
    ->get();

// Log an action
PresenceAuditLog::log(
    action: "Attendance verified",
    actionType: PresenceAuditLog::ACTION_ATTENDANCE_VERIFIED,
    user: $student,
    presence: $presence,
    actor: Auth::user(),
    actorRole: 'manager',
    details: ['notes' => 'Manual verification']
);

// Get actions by type
PresenceAuditLog::where('action_type', 'fraud_detected')
    ->where('created_at', '>=', now()->subDays(7))
    ->count();

// Compliance report (last 90 days)
PresenceAuditLog::where('created_at', '>=', now()->subDays(90))
    ->orderBy('created_at', 'desc')
    ->get();
```

---

## Relationships Diagram

```
┌─────────────────┐
│     USERS       │
└────────┬────────┘
         │
    ┌────┴──────────────────┬──────────────────┬─────────────┐
    │                       │                  │             │
    │ (1:N)                │ (1:N)             │ (1:N)       │
    │                       │                  │             │
    ▼                       ▼                  ▼             ▼
┌──────────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐
│  CLASS_ROOMS     │ │ PRESENCE_     │ │ PRESENCE_    │ │ PRESENCE_        │
│                  │ │ SESSIONS      │ │ QR_TOKENS    │ │ DEVICES          │
│ (existing)       │ │ (new)         │ │ (new)        │ │ (new)            │
└──────────────────┘ └────┬──────────┘ └────┬─────────┘ └──────────────────┘
         │                 │                 │
         │ (1:N)           │ (1:N)           │ (1:N)
         │                 │                 │
         ▼                 ▼                 ▼
      PRESENCES (new)
         │
    ┌────┴─────────────────┐
    │                      │
    │ (1:N)                │ (1:N)
    │                      │
    ▼                      ▼
PRESENCE_             PRESENCE_
SECURITY_FLAGS    ←   AUDIT_LOGS (new)
(new)                  (new)
```

---

## Data Flow Example

### Scenario: Student checks in to class

```
1. Teacher creates session
   → INSERT presence_sessions

2. Teacher activates session
   → UPDATE presence_sessions (is_active = TRUE, actual_start_at = NOW())

3. Teacher generates QR token
   → INSERT presence_qr_tokens (uuid, expires_at = NOW + 30 sec)
   → INSERT presence_audit_logs (action_type = 'qr_generated')

4. Student scans QR
   → SELECT presence_qr_tokens WHERE uuid = ? (validate)
   → INSERT presences (is_valid = result of validations)
   → UPDATE presence_qr_tokens (is_used = TRUE)

5. Validations trigger flags if needed
   → INSERT presence_security_flags (if GPS/device issues)

6. All actions logged
   → INSERT presence_audit_logs (multiple entries)

7. Teacher ends session
   → UPDATE presence_sessions (is_active = FALSE, actual_end_at = NOW())

8. Manager reviews flags
   → UPDATE presence_security_flags (is_reviewed = TRUE, action_taken = ?)
   → IF rejected: DELETE presences
   → INSERT presence_audit_logs (action_type = 'flag_reviewed')
```

---

## Performance Considerations

### Query Optimization
```php
// Load relationships eagerly
$presences = Presence::with([
    'user',
    'session',
    'qrToken',
    'securityFlags',
])->where('is_valid', false)->get();

// Use select() to limit columns
PresenceSession::select('id', 'session_name', 'is_active')
    ->where('is_active', true)
    ->get();

// Batch operations
Presence::where('checked_out_at', '<', now()->subHours(2))
    ->whereNull('duration_minutes')
    ->update(['duration_minutes' => DB::raw('EXTRACT(EPOCH FROM (NOW() - checked_in_at)) / 60')]);
```

### Indexes Impact
- Presence queries by session_id: ~1ms (with index)
- QR token validation: ~<1ms (with uuid unique index)
- Security flag review: ~5ms (with severity + is_reviewed)

### Caching Strategy
```php
// Cache active sessions (5 minutes)
Cache::remember("active_sessions", now()->addMinutes(5), function () {
    return PresenceSession::where('is_active', true)->get();
});

// Cache user attendance (10 minutes)
Cache::remember("user_attendance_{$user->id}", now()->addMinutes(10), function () use ($user) {
    return $user->attendanceRecords()->count();
});
```

---

## Constraints & Validations

### Database Constraints
```sql
-- Prevent duplicate check-ins
ALTER TABLE presences 
ADD CONSTRAINT unique_user_session 
UNIQUE(user_id, presence_session_id);

-- Ensure expiration time logic
ALTER TABLE presence_qr_tokens 
ADD CONSTRAINT check_expiration 
CHECK (expires_at > generated_at);

-- Ensure session time logic
ALTER TABLE presence_sessions 
ADD CONSTRAINT check_session_times 
CHECK (scheduled_end_at > scheduled_start_at);

-- GPS radius validation
ALTER TABLE presence_sessions 
ADD CONSTRAINT check_gps_radius 
CHECK (gps_radius_meters BETWEEN 10 AND 500);
```

### Application Validations
```php
// In models using $casts and $rules

class PresenceSession extends Model {
    public function rules() {
        return [
            'gps_latitude' => 'required_with:gps_longitude|between:-90,90',
            'gps_longitude' => 'required_with:gps_latitude|between:-180,180',
            'gps_radius_meters' => 'required|min:10|max:500',
            'session_type' => 'in:class,event,exam_preparation',
        ];
    }
}
```

---

**Last Updated:** May 13, 2026  
**Schema Version:** 1.0  
**PostgreSQL Version:** 12+
