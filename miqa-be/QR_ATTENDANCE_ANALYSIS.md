# QR Attendance System - Architecture Analysis & Implementation Guide

**Date:** May 13, 2026  
**Laravel Version:** 12  
**Database:** PostgreSQL  
**Status:** Production-Ready Implementation  

---

## Executive Summary

Your existing MIQA system has:
- ✅ User authentication with Laravel Sanctum
- ✅ Role-Based Access Control (Spatie Permission): Manager, Teacher, Student
- ✅ Classroom management (ClassRoom, ClassStudent, ClassSubject)
- ✅ Exam management (SubjectExam, ExamQuestion, ExamAttempt)
- ⚠️ **Missing:** Unified attendance tracking system

### Proposed Extension
Add a **secure QR-based attendance system** for Manager, Teacher, and Student roles without redesigning existing tables.

---

## Current Architecture Analysis

### Existing Tables & Models
```
├── users (id, name, email, password, photo, gender, timestamps)
├── roles (Spatie Permission)
├── permissions (Spatie Permission)
├── class_rooms (id, name, photo, grade, timestamps)
├── class_students (student_id FK → users, class_room_id FK → class_rooms)
├── class_subjects (class_room_id FK, subject_id FK)
├── subjects (id, name, topic_id, teacher_id, timestamps)
├── subject_exams (id, subject_id, name, timestamps)
└── exam_attempts (student_id, subject_exam_id, timestamps)
```

### Key Roles
```
- MANAGER: Full platform access
- TEACHER: Can create exams, manage students in assigned classes
- STUDENT: Can take exams, submit answers
```

### Authentication Strategy
- **Guards:** sanctum (API tokens)
- **Authorization:** Spatie Permission (roles + permissions)
- **Middleware:** CheckAccessPermission (custom)

---

## Recommended Schema Changes

### New Tables Required (Non-Breaking)

| Table | Purpose | Dependencies |
|-------|---------|--------------|
| `presence_sessions` | Attendance event registry | class_rooms |
| `presence_qr_tokens` | Single-use QR codes (UUID v4, 15-30 sec) | users |
| `presences` | Attendance records | presence_sessions, users |
| `presence_devices` | Device fingerprints for fraud detection | users |
| `presence_security_flags` | Suspicious activity logging | presences |
| `presence_audit_logs` | Audit trail for compliance | users, presences |

### Reused Existing Tables
- `users` - No changes (add relationships only)
- `class_rooms` - No changes (add relationships only)
- `roles` - Spatie Permission (no changes)

### Data Types & Best Practices
```
✓ UUIDs for QR tokens (not IDs) - prevents sequential guessing
✓ PostgreSQL: BIGINT for IDs, UUID for tokens
✓ Timestamps: precise attendance tracking
✓ JSON fields: device fingerprints, fraud flags, GPS coordinates
✓ Indexes: Query optimization for high-volume attendance
✓ Constraints: Unique (user, session), prevent duplicates
```

---

## Implementation Plan

### Phase 1: Database Migrations
1. Create `presence_sessions` table
2. Create `presence_qr_tokens` table
3. Create `presences` table
4. Create `presence_devices` table
5. Create `presence_security_flags` table
6. Create `presence_audit_logs` table
7. Add indexes for performance

### Phase 2: Eloquent Models
1. `PresenceSession` - event/session model
2. `PresenceQrToken` - QR token model
3. `Presence` - attendance record model
4. `PresenceDevice` - device fingerprint model
5. `PresenceSecurityFlag` - fraud flag model
6. `PresenceAuditLog` - audit trail model

### Phase 3: Model Relationships
Update `User` model to include:
- `hasMany(PresenceQrToken)` - generated QR codes
- `hasMany(Presence)` - attendance records
- `hasMany(PresenceDevice)` - registered devices
- `hasMany(PresenceSecurityFlag)` - triggered flags
- `hasMany(PresenceAuditLog)` - audit entries

### Phase 4: Business Logic
1. QR Token generation (UUID v4 + 15-30 sec expiration)
2. QR Token validation (single-use enforcement)
3. GPS validation (geofencing)
4. Device fingerprint tracking
5. Replay attack prevention
6. Duplicate attendance prevention
7. Fraud detection & logging

---

## Security Architecture

### QR Token Lifecycle
```
1. GENERATION (Teacher/Manager)
   └─ UUID v4 generated
   └─ expires_at = NOW() + 30 seconds
   └─ is_used = FALSE
   └─ qr_code rendered for display

2. SCANNING (Student/Teacher/Manager)
   └─ Validate: UUID exists
   └─ Validate: NOT expired (NOW() < expires_at)
   └─ Validate: NOT already used (is_used = FALSE)
   └─ Validate: GPS within geofence
   └─ Validate: Device fingerprint (prevent sharing)
   └─ Validate: Not duplicate attendance in session

3. VALIDATION (Backend)
   └─ All checks pass → Mark is_used = TRUE
   └─ Create PRESENCE record
   └─ Log to PRESENCE_AUDIT_LOGS

4. EXPIRATION
   └─ Automatic: Timestamp-based (PostgreSQL)
   └─ Manual: Revoke token before expiration
```

### Anti-Fraud Measures

| Check | Implementation | Flag |
|-------|-----------------|------|
| **Single-Use** | is_used BOOLEAN, UNIQUE(token_id, user_id) | duplicate_token |
| **Expiration** | expires_at < NOW() | expired_token |
| **Replay Attack** | Device fingerprint match | device_mismatch |
| **GPS Geofencing** | Distance calculation (Haversine) | outside_geofence |
| **Fake GPS** | GPS velocity check, pattern analysis | impossible_velocity |
| **Duplicate Attendance** | UNIQUE(user_id, session_id) | duplicate_session_entry |
| **Device Spoofing** | Device fingerprint hash | fingerprint_mismatch |
| **Session Hijacking** | Token + User + Device combination | hijack_attempt |

### Device Fingerprint Components
```json
{
  "user_agent": "Mozilla/5.0...",
  "device_id": "hardware_id",
  "os_name": "iOS/Android/Windows",
  "os_version": "14.0",
  "app_version": "1.2.3",
  "ip_address": "192.168.1.1"
}
```

---

## Database Schema Details

### 1. presence_sessions
```sql
CREATE TABLE presence_sessions (
  id BIGINT PRIMARY KEY,
  class_room_id BIGINT NOT NULL REFERENCES class_rooms(id),
  created_by_user_id BIGINT NOT NULL REFERENCES users(id),
  session_name VARCHAR(255) NOT NULL,
  session_type ENUM('class', 'event', 'exam_preparation'),
  scheduled_start_at TIMESTAMP,
  scheduled_end_at TIMESTAMP,
  actual_start_at TIMESTAMP,
  actual_end_at TIMESTAMP,
  gps_latitude DECIMAL(10, 8),
  gps_longitude DECIMAL(11, 8),
  gps_radius_meters INT DEFAULT 50,
  is_active BOOLEAN DEFAULT FALSE,
  notes TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### 2. presence_qr_tokens
```sql
CREATE TABLE presence_qr_tokens (
  id BIGINT PRIMARY KEY,
  uuid UUID UNIQUE NOT NULL,
  presence_session_id BIGINT NOT NULL REFERENCES presence_sessions(id),
  created_by_user_id BIGINT NOT NULL REFERENCES users(id),
  generated_at TIMESTAMP,
  expires_at TIMESTAMP NOT NULL,
  is_used BOOLEAN DEFAULT FALSE,
  used_by_user_id BIGINT REFERENCES users(id),
  used_at TIMESTAMP,
  is_revoked BOOLEAN DEFAULT FALSE,
  revoked_at TIMESTAMP,
  revoke_reason TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### 3. presences
```sql
CREATE TABLE presences (
  id BIGINT PRIMARY KEY,
  qr_token_id BIGINT NOT NULL REFERENCES presence_qr_tokens(id),
  presence_session_id BIGINT NOT NULL REFERENCES presence_sessions(id),
  user_id BIGINT NOT NULL REFERENCES users(id),
  checked_in_at TIMESTAMP DEFAULT NOW,
  checked_out_at TIMESTAMP,
  duration_minutes INT,
  gps_latitude DECIMAL(10, 8),
  gps_longitude DECIMAL(11, 8),
  gps_distance_meters DECIMAL(10, 2),
  is_within_geofence BOOLEAN,
  device_fingerprint_json JSON,
  ip_address INET,
  user_agent TEXT,
  is_valid BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE(user_id, presence_session_id)
);
```

### 4. presence_devices
```sql
CREATE TABLE presence_devices (
  id BIGINT PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id),
  device_fingerprint_hash VARCHAR(255) UNIQUE NOT NULL,
  device_name TEXT,
  device_type VARCHAR(50),
  os_name VARCHAR(50),
  os_version VARCHAR(50),
  app_version VARCHAR(50),
  is_trusted BOOLEAN DEFAULT FALSE,
  last_seen_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### 5. presence_security_flags
```sql
CREATE TABLE presence_security_flags (
  id BIGINT PRIMARY KEY,
  presence_id BIGINT NOT NULL REFERENCES presences(id),
  user_id BIGINT NOT NULL REFERENCES users(id),
  flag_type VARCHAR(50) NOT NULL,
  flag_severity ENUM('low', 'medium', 'high', 'critical'),
  flag_description TEXT,
  flag_metadata JSON,
  is_reviewed BOOLEAN DEFAULT FALSE,
  reviewed_by_user_id BIGINT REFERENCES users(id),
  review_notes TEXT,
  action_taken VARCHAR(50),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### 6. presence_audit_logs
```sql
CREATE TABLE presence_audit_logs (
  id BIGINT PRIMARY KEY,
  presence_id BIGINT REFERENCES presences(id),
  user_id BIGINT NOT NULL REFERENCES users(id),
  action VARCHAR(255) NOT NULL,
  action_type ENUM('qr_generated', 'qr_scanned', 'attendance_recorded', 'attendance_verified', 'fraud_detected'),
  action_details JSON,
  actor_user_id BIGINT REFERENCES users(id),
  actor_role VARCHAR(50),
  ip_address INET,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Indexes for Performance

```sql
-- Presence Sessions
CREATE INDEX idx_presence_sessions_class_room_id ON presence_sessions(class_room_id);
CREATE INDEX idx_presence_sessions_is_active ON presence_sessions(is_active);
CREATE INDEX idx_presence_sessions_created_at ON presence_sessions(created_at);

-- QR Tokens
CREATE INDEX idx_presence_qr_tokens_uuid ON presence_qr_tokens(uuid);
CREATE INDEX idx_presence_qr_tokens_session_id ON presence_qr_tokens(presence_session_id);
CREATE INDEX idx_presence_qr_tokens_expires_at ON presence_qr_tokens(expires_at);
CREATE INDEX idx_presence_qr_tokens_is_used ON presence_qr_tokens(is_used);
CREATE UNIQUE INDEX idx_presence_qr_tokens_user_session ON presences(user_id, presence_session_id);

-- Presences
CREATE INDEX idx_presences_user_id ON presences(user_id);
CREATE INDEX idx_presences_session_id ON presences(presence_session_id);
CREATE INDEX idx_presences_checked_in_at ON presences(checked_in_at);
CREATE INDEX idx_presences_is_valid ON presences(is_valid);

-- Devices
CREATE INDEX idx_presence_devices_user_id ON presence_devices(user_id);
CREATE INDEX idx_presence_devices_fingerprint ON presence_devices(device_fingerprint_hash);

-- Security Flags
CREATE INDEX idx_presence_security_flags_presence_id ON presence_security_flags(presence_id);
CREATE INDEX idx_presence_security_flags_severity ON presence_security_flags(flag_severity);
CREATE INDEX idx_presence_security_flags_is_reviewed ON presence_security_flags(is_reviewed);

-- Audit Logs
CREATE INDEX idx_presence_audit_logs_presence_id ON presence_audit_logs(presence_id);
CREATE INDEX idx_presence_audit_logs_action_type ON presence_audit_logs(action_type);
CREATE INDEX idx_presence_audit_logs_created_at ON presence_audit_logs(created_at);
```

---

## Attendance Validation Flow

```
┌─────────────────────────────────────────────────────────┐
│ 1. TEACHER/MANAGER GENERATES QR CODE                   │
├─────────────────────────────────────────────────────────┤
│ POST /api/presence/sessions/{sessionId}/qr-token       │
│                                                          │
│ ✓ Verify user role (teacher/manager)                  │
│ ✓ Verify session exists & is_active = true           │
│ ✓ Generate UUID v4                                     │
│ ✓ Create presence_qr_tokens record                     │
│ ✓ Set expires_at = NOW() + 30 seconds                 │
│ ✓ Encode UUID as QR image                              │
│ ✓ Return QR image + expiration time                    │
│ ✓ Log in presence_audit_logs                          │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│ 2. STUDENT SCANS QR CODE                               │
├──────────────────────────────────────────────────────────┤
│ POST /api/presence/check-in                            │
│   {                                                     │
│     "qr_token": "uuid-here",                           │
│     "session_id": 123,                                 │
│     "gps_latitude": 40.7128,                           │
│     "gps_longitude": -74.0060,                         │
│     "device_fingerprint": {...}                        │
│   }                                                     │
│                                                          │
│ ✓ Verify QR token exists in presence_qr_tokens        │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│ 3. VALIDATION CHECKS                                   │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ CHECK 1: QR Token Validity                             │
│ ├─ is_used = FALSE? (Not already scanned)             │
│ ├─ is_revoked = FALSE?                                │
│ ├─ NOW() < expires_at? (Not expired)                  │
│                                                          │
│ CHECK 2: GPS Validation                                │
│ ├─ Calculate distance using Haversine formula          │
│ ├─ distance <= gps_radius_meters? (Geofence OK)       │
│ ├─ velocity realistic? (Prevent teleportation)        │
│                                                          │
│ CHECK 3: Device Fingerprint                            │
│ ├─ Hash device info                                    │
│ ├─ Match with presence_devices?                        │
│ ├─ Is device trusted?                                  │
│                                                          │
│ CHECK 4: Duplicate Prevention                          │
│ ├─ UNIQUE(user_id, presence_session_id)?              │
│ ├─ Already checked in this session?                    │
│                                                          │
│ CHECK 5: Session Validity                              │
│ ├─ is_active = TRUE?                                   │
│ ├─ NOW() within scheduled time window?                │
│                                                          │
│ RESULT: Pass all checks → is_valid = TRUE              │
│         Fail any check → Flag for review               │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│ 4. FRAUD DETECTION & FLAGGING                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ IF Check failed:                                        │
│ ├─ Create presence_security_flags record               │
│ ├─ Set flag_severity based on issue                    │
│ ├─ Add flag_metadata (details)                         │
│ ├─ is_valid = FALSE                                    │
│ ├─ Mark for manual review                              │
│ └─ Alert manager/teacher                               │
│                                                          │
│ Flag Types:                                             │
│ ├─ duplicate_token (already used)                      │
│ ├─ expired_token (past expiration)                     │
│ ├─ outside_geofence (GPS far away)                     │
│ ├─ impossible_velocity (too fast movement)             │
│ ├─ device_mismatch (fingerprint different)             │
│ ├─ duplicate_session_entry (already in session)        │
│ ├─ hijack_attempt (user/device combo wrong)            │
│ └─ suspicious_pattern (unusual behavior)               │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│ 5. ATTENDANCE RECORD CREATION                           │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ CREATE presences record:                                │
│ ├─ qr_token_id = scanned token                         │
│ ├─ presence_session_id = session                       │
│ ├─ user_id = current user                             │
│ ├─ checked_in_at = NOW()                              │
│ ├─ gps_latitude/longitude = actual location            │
│ ├─ is_within_geofence = validation result              │
│ ├─ device_fingerprint_json = device info               │
│ ├─ is_valid = validation result                        │
│                                                          │
│ UPDATE presence_qr_tokens:                              │
│ ├─ is_used = TRUE                                      │
│ ├─ used_by_user_id = current user                      │
│ ├─ used_at = NOW()                                     │
│                                                          │
│ CREATE presence_audit_logs:                             │
│ ├─ action_type = 'attendance_recorded'                 │
│ ├─ action_details = validation results                 │
│ └─ Log all security flags                              │
└──────────────────────┬──────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────┐
│ 6. RESPONSE TO CLIENT                                  │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ {                                                       │
│   "success": true/false,                               │
│   "presence_id": 456,                                  │
│   "is_valid": true/false,                              │
│   "checked_in_at": "2026-05-13T10:30:00Z",            │
│   "security_flags": [...],                             │
│   "message": "Attendance recorded successfully"        │
│ }                                                       │
└──────────────────────────────────────────────────────────┘
```

---

## Anti-Fraud Flow

```
┌─────────────────────────────────────────────────┐
│ FRAUD DETECTION ENGINE                          │
└─────────────────────────────────────────────────┘

1. QR TOKEN REUSE
   ├─ Detect: is_used = TRUE, trying to use again
   ├─ Severity: HIGH
   ├─ Action: Block, flag, alert
   └─ Prevention: UNIQUE constraint + validation

2. GPS SPOOFING
   ├─ Detect: distance > gps_radius_meters
   ├─ Calculate: Haversine formula
   ├─ Severity: CRITICAL if outside 1km
   ├─ Action: Block, flag, require manual approval
   └─ Prevention: Real-time GPS validation

3. IMPOSSIBLE VELOCITY
   ├─ Detect: User traveled > 100 km/hour
   ├─ Calculate: (last_gps_distance) / (time_delta)
   ├─ Severity: CRITICAL
   ├─ Action: Block, flag, alert admin
   └─ Prevention: Velocity check on each check-in

4. DEVICE FINGERPRINT MISMATCH
   ├─ Detect: Device hash doesn't match registered
   ├─ Hash: user_agent + device_id + os + ip
   ├─ Severity: MEDIUM (first time) / HIGH (repeated)
   ├─ Action: Log, require confirmation, flag if repeated
   └─ Prevention: Trust device after verification

5. DUPLICATE ATTENDANCE
   ├─ Detect: UNIQUE(user_id, session_id) violation
   ├─ Severity: HIGH
   ├─ Action: Block, flag, require admin override
   └─ Prevention: Database constraint + validation

6. TOKEN EXPIRATION EXPLOITATION
   ├─ Detect: expires_at < NOW()
   ├─ Severity: HIGH
   ├─ Action: Block immediately
   └─ Prevention: Automatic expiration check

7. SESSION HIJACKING
   ├─ Detect: Token + User + Device combo doesn't match
   ├─ Severity: CRITICAL
   ├─ Action: Block, flag, alert admin, log attempt
   └─ Prevention: Multi-factor validation

8. PATTERN ANOMALY DETECTION
   ├─ Detect: User never attended, sudden high attendance
   ├─ Detect: Multiple students same GPS + device
   ├─ Severity: MEDIUM
   ├─ Action: Flag for review, add to suspicious list
   └─ Prevention: ML-based flagging system (future)

┌─────────────────────────────────────────────────┐
│ SECURITY FLAG RESOLUTION                        │
└─────────────────────────────────────────────────┘

Manager/Admin Dashboard:
├─ View all flagged presences
├─ Sort by severity (critical, high, medium, low)
├─ Review flag_metadata for details
├─ Options:
│  ├─ Approve (is_valid = TRUE, move to valid)
│  ├─ Reject (delete presence, block user)
│  ├─ Investigate (request student explanation)
│  └─ Whitelist device (trust_device = TRUE)
└─ All actions logged in presence_audit_logs
```

---

## Suggested API Structure

### Presence Session Management
```
GET    /api/presence/sessions
POST   /api/presence/sessions
GET    /api/presence/sessions/{sessionId}
PUT    /api/presence/sessions/{sessionId}
DELETE /api/presence/sessions/{sessionId}
POST   /api/presence/sessions/{sessionId}/start
POST   /api/presence/sessions/{sessionId}/end
GET    /api/presence/sessions/{sessionId}/report
```

### QR Token Management
```
POST   /api/presence/sessions/{sessionId}/qr-token
GET    /api/presence/qr-tokens/{tokenId}
DELETE /api/presence/qr-tokens/{tokenId}/revoke
GET    /api/presence/qr-tokens/{tokenId}/status
```

### Attendance Check-In/Out
```
POST   /api/presence/check-in
POST   /api/presence/check-out/{presenceId}
GET    /api/presence/my-attendance
GET    /api/presence/session/{sessionId}/attendance
```

### Reporting & Analytics
```
GET    /api/presence/reports/attendance-summary
GET    /api/presence/reports/by-class
GET    /api/presence/reports/by-user/{userId}
GET    /api/presence/security-flags
GET    /api/presence/audit-logs
```

### Security Management
```
GET    /api/presence/security-flags/{flagId}
PUT    /api/presence/security-flags/{flagId}/review
GET    /api/presence/audit-logs
POST   /api/presence/devices/{deviceId}/trust
DELETE /api/presence/devices/{deviceId}/revoke
```

---

## Security Recommendations

### 1. Authentication & Authorization
```
✓ Verify user is authenticated via Sanctum
✓ Check role-based permissions (Manager, Teacher, Student)
✓ Validate user_id matches authenticated user
✓ Use Laravel's authorization policies
```

### 2. Rate Limiting
```
✓ QR generation: 10 per minute per teacher
✓ Check-in: 1 per session per student
✓ API endpoints: Standard rate limiting (60 req/min)
✓ Fraud checking: Aggressive (5 suspicious attempts = block)
```

### 3. Data Validation
```
✓ GPS coordinates: Valid lat/lon ranges
✓ Device fingerprint: Hash validation
✓ UUID format: Valid UUID v4 format
✓ Timestamps: Reasonable values
✓ Session window: 15 minutes before/after scheduled time
```

### 4. Encryption
```
✓ Device fingerprints: Hash (SHA-256) + salt
✓ GPS coordinates: No encryption needed (not sensitive)
✓ In transit: HTTPS/TLS 1.3
✓ At rest: Database-level encryption if needed
```

### 5. Logging & Monitoring
```
✓ All QR operations logged
✓ All security flags logged
✓ All fraud detections logged
✓ Monitor for patterns (same device, multiple users)
✓ Alert on CRITICAL severity flags
✓ Daily audit report for managers
```

### 6. Privacy Compliance
```
✓ GDPR: GPS data retention (180 days max)
✓ Device fingerprints: Pseudonymized
✓ Audit logs: Retained for 1 year
✓ User right to access: API endpoint
✓ User right to erasure: Anonymize data
```

---

## Implementation Timeline

| Phase | Duration | Task |
|-------|----------|------|
| 1 | 2 days | Create migrations & models |
| 2 | 3 days | Implement validation logic |
| 3 | 2 days | Build API endpoints |
| 4 | 2 days | Implement fraud detection |
| 5 | 2 days | Create admin dashboard components |
| 6 | 1 day | Testing & security audit |
| 7 | 1 day | Documentation & deployment |
| **Total** | **~2 weeks** | Production ready |

---

## Next Steps

1. Review this analysis document
2. Run the provided migrations
3. Create the new models (see generated files)
4. Implement the validation logic in services
5. Build the API controllers
6. Test with real QR codes
7. Deploy to production

All code files are ready in the following files:
- Migrations: `2026_05_13_*_create_presence_*.php`
- Models: `app/Models/Presence*.php`
- Service: `app/Services/AttendanceService.php`
- API structure outlined below
