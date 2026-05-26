# QR Attendance System - Implementation Guide

**Date:** May 13, 2026  
**Status:** Ready for Development  
**Estimated Development Time:** 2 weeks

---

## Files Generated

### Migrations (6 files)
```
src/database/migrations/
├── 2026_05_13_100001_create_presence_sessions_table.php
├── 2026_05_13_100002_create_presence_qr_tokens_table.php
├── 2026_05_13_100003_create_presences_table.php
├── 2026_05_13_100004_create_presence_devices_table.php
├── 2026_05_13_100005_create_presence_security_flags_table.php
└── 2026_05_13_100006_create_presence_audit_logs_table.php
```

### Models (6 files)
```
src/app/Models/
├── PresenceSession.php ✓
├── PresenceQrToken.php ✓
├── Presence.php ✓
├── PresenceDevice.php ✓
├── PresenceSecurityFlag.php ✓
└── PresenceAuditLog.php ✓
```

### Services (1 file)
```
src/app/Services/
└── AttendanceService.php ✓
```

### Updated Models
```
src/app/Models/
├── User.php (relationships added) ✓
└── ClassRoom.php (relationships added) ✓
```

---

## Database Installation

### Run Migrations
```bash
php artisan migrate
```

### Verify Tables Created
```bash
php artisan tinker
DB::table('presence_sessions')->count()  # Should return 0
DB::table('presence_qr_tokens')->count()  # Should return 0
DB::table('presences')->count()  # Should return 0
```

---

## Controller Structure (TO CREATE)

### Suggested Controllers

```
src/app/Http/Controllers/Api/
├── PresenceSessionController.php
│   ├── store()           # Create session
│   ├── show()            # Get session details
│   ├── activate()        # Start attendance
│   ├── deactivate()      # End attendance
│   └── report()          # Get attendance report
│
├── PresenceQrController.php
│   ├── generateToken()   # Generate QR code
│   ├── validateToken()   # Check if valid
│   └── revokeToken()     # Revoke before use
│
├── PresenceCheckInController.php
│   ├── checkIn()         # Process QR scan
│   └── checkOut()        # Record check-out
│
└── PresenceReportController.php
    ├── userStats()       # User attendance stats
    ├── sessionReport()   # Session attendance
    ├── securityFlags()   # Flagged attendances
    └── auditLogs()       # Audit trail
```

---

## API Routes (TO CREATE)

```php
// routes/api.php

Route::middleware(['auth:sanctum'])->group(function () {
    // Presence Sessions (Manager/Teacher only)
    Route::prefix('presence/sessions')->group(function () {
        Route::post('/', [PresenceSessionController::class, 'store'])
            ->middleware('can:create-presence-session');
        Route::get('/{session}', [PresenceSessionController::class, 'show']);
        Route::put('/{session}', [PresenceSessionController::class, 'update'])
            ->middleware('can:update-presence-session');
        Route::post('/{session}/activate', [PresenceSessionController::class, 'activate'])
            ->middleware('can:activate-presence-session');
        Route::post('/{session}/deactivate', [PresenceSessionController::class, 'deactivate'])
            ->middleware('can:deactivate-presence-session');
        Route::get('/{session}/report', [PresenceSessionController::class, 'report']);
        Route::get('/{session}/attendances', [PresenceSessionController::class, 'attendances']);
    });

    // QR Token Generation (Manager/Teacher only)
    Route::prefix('presence/qr')->group(function () {
        Route::post('/generate', [PresenceQrController::class, 'generateToken'])
            ->middleware('can:generate-qr-token');
        Route::get('/{token}', [PresenceQrController::class, 'show']);
        Route::delete('/{token}/revoke', [PresenceQrController::class, 'revokeToken'])
            ->middleware('can:revoke-qr-token');
    });

    // Attendance Check-In (All authenticated users)
    Route::prefix('presence/attendance')->group(function () {
        Route::post('/check-in', [PresenceCheckInController::class, 'checkIn']);
        Route::post('/{presence}/check-out', [PresenceCheckInController::class, 'checkOut']);
        Route::get('/my-attendance', [PresenceCheckInController::class, 'myAttendance']);
    });

    // Reports & Analytics
    Route::prefix('presence/reports')->group(function () {
        Route::get('/user-stats', [PresenceReportController::class, 'userStats']);
        Route::get('/session/{session}', [PresenceReportController::class, 'sessionReport']);
        Route::get('/security-flags', [PresenceReportController::class, 'securityFlags'])
            ->middleware('can:view-security-flags');
        Route::get('/audit-logs', [PresenceReportController::class, 'auditLogs'])
            ->middleware('can:view-audit-logs');
    });

    // Security Management (Manager/Admin only)
    Route::prefix('presence/security')->group(function () {
        Route::get('/flags', [PresenceSecurityController::class, 'listFlags'])
            ->middleware('can:review-security-flags');
        Route::put('/flags/{flag}', [PresenceSecurityController::class, 'reviewFlag'])
            ->middleware('can:review-security-flags');
        Route::post('/devices/{device}/trust', [PresenceSecurityController::class, 'trustDevice'])
            ->middleware('can:trust-device');
        Route::delete('/devices/{device}', [PresenceSecurityController::class, 'revokeDevice'])
            ->middleware('can:revoke-device');
    });
});
```

---

## API Endpoints Reference

### 1. Create Presence Session

**Endpoint:** `POST /api/presence/sessions`

**Request:**
```json
{
  "class_room_id": 1,
  "session_name": "Class A - Mathematics",
  "session_type": "class",
  "scheduled_start_at": "2026-05-13T10:00:00Z",
  "scheduled_end_at": "2026-05-13T11:00:00Z",
  "gps_latitude": 40.7128,
  "gps_longitude": -74.0060,
  "gps_radius_meters": 50,
  "notes": "Regular class session"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "class_room_id": 1,
    "session_name": "Class A - Mathematics",
    "is_active": false,
    "created_at": "2026-05-13T10:00:00Z"
  }
}
```

---

### 2. Activate Session (Start Attendance)

**Endpoint:** `POST /api/presence/sessions/{sessionId}/activate`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "is_active": true,
    "actual_start_at": "2026-05-13T10:00:00Z"
  }
}
```

---

### 3. Generate QR Token

**Endpoint:** `POST /api/presence/qr/generate`

**Request:**
```json
{
  "session_id": 1,
  "expires_in_seconds": 30
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "expires_at": "2026-05-13T10:00:30Z",
    "qr_code_base64": "data:image/png;base64,iVBORw0KGgoAAAANS..."
  }
}
```

---

### 4. Check-In (Scan QR)

**Endpoint:** `POST /api/presence/attendance/check-in`

**Request:**
```json
{
  "qr_token": "550e8400-e29b-41d4-a716-446655440000",
  "session_id": 1,
  "gps_latitude": 40.7128,
  "gps_longitude": -74.0060,
  "device_fingerprint": {
    "user_agent": "Mozilla/5.0...",
    "device_id": "device_123",
    "device_type": "mobile",
    "os_name": "iOS",
    "os_version": "17.0",
    "app_version": "1.2.3"
  }
}
```

**Response (200 - Valid):**
```json
{
  "success": true,
  "presence_id": 1,
  "is_valid": true,
  "checked_in_at": "2026-05-13T10:00:15Z",
  "validation_results": {
    "qr_valid": true,
    "gps_valid": true,
    "device_valid": true,
    "flags": []
  },
  "message": "Attendance recorded successfully"
}
```

**Response (200 - Flagged):**
```json
{
  "success": true,
  "presence_id": 2,
  "is_valid": false,
  "checked_in_at": "2026-05-13T10:05:00Z",
  "validation_results": {
    "qr_valid": true,
    "gps_valid": false,
    "device_valid": false,
    "flags": [
      {
        "type": "outside_geofence",
        "severity": "high",
        "description": "User is 250m away from session location (geofence: 50m)"
      },
      {
        "type": "device_mismatch",
        "severity": "medium",
        "description": "New device detected"
      }
    ]
  },
  "message": "Attendance recorded but flagged for review"
}
```

---

### 5. Check-Out

**Endpoint:** `POST /api/presence/{presenceId}/check-out`

**Response (200):**
```json
{
  "success": true,
  "checked_out_at": "2026-05-13T11:00:00Z",
  "duration_minutes": 60,
  "message": "Check-out recorded successfully"
}
```

---

### 6. Get Session Report

**Endpoint:** `GET /api/presence/sessions/{sessionId}/report`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "session_id": 1,
    "session_name": "Class A - Mathematics",
    "total_attendance": 30,
    "valid_attendance": 28,
    "flagged_attendance": 2,
    "attendance_rate": 93.33,
    "attendances": [
      {
        "id": 1,
        "user_name": "John Doe",
        "checked_in_at": "2026-05-13T10:00:15Z",
        "checked_out_at": "2026-05-13T11:00:00Z",
        "duration_minutes": 60,
        "is_valid": true,
        "flags": []
      },
      {
        "id": 2,
        "user_name": "Jane Smith",
        "checked_in_at": "2026-05-13T10:05:00Z",
        "checked_out_at": null,
        "duration_minutes": null,
        "is_valid": false,
        "flags": ["outside_geofence", "device_mismatch"]
      }
    ]
  }
}
```

---

### 7. List Unreviewed Security Flags

**Endpoint:** `GET /api/presence/security/flags?severity=critical,high`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "presence_id": 2,
      "user_name": "Jane Smith",
      "flag_type": "outside_geofence",
      "flag_severity": "high",
      "flag_description": "User is 250m away from session location",
      "is_reviewed": false,
      "created_at": "2026-05-13T10:05:00Z"
    }
  ]
}
```

---

### 8. Review Security Flag

**Endpoint:** `PUT /api/presence/security/flags/{flagId}`

**Request:**
```json
{
  "action": "approved",
  "review_notes": "Confirmed valid attendance - user was in building"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "is_reviewed": true,
    "action_taken": "approved",
    "reviewed_by": "admin@example.com",
    "reviewed_at": "2026-05-13T10:30:00Z"
  }
}
```

---

## Laravel Permissions to Configure

Add these permissions in a seeder:

```php
// database/seeders/PresencePermissionSeeder.php

Permission::firstOrCreate(['name' => 'create-presence-session']);
Permission::firstOrCreate(['name' => 'update-presence-session']);
Permission::firstOrCreate(['name' => 'activate-presence-session']);
Permission::firstOrCreate(['name' => 'deactivate-presence-session']);
Permission::firstOrCreate(['name' => 'generate-qr-token']);
Permission::firstOrCreate(['name' => 'revoke-qr-token']);
Permission::firstOrCreate(['name' => 'review-security-flags']);
Permission::firstOrCreate(['name' => 'trust-device']);
Permission::firstOrCreate(['name' => 'revoke-device']);
Permission::firstOrCreate(['name' => 'view-security-flags']);
Permission::firstOrCreate(['name' => 'view-audit-logs']);

// Assign to manager role
$managerRole = Role::findByName('manager');
$managerRole->givePermissionTo([
    'create-presence-session',
    'update-presence-session',
    'activate-presence-session',
    'deactivate-presence-session',
    'generate-qr-token',
    'revoke-qr-token',
    'review-security-flags',
    'trust-device',
    'revoke-device',
    'view-security-flags',
    'view-audit-logs',
]);

// Assign to teacher role
$teacherRole = Role::findByName('teacher');
$teacherRole->givePermissionTo([
    'create-presence-session',
    'update-presence-session',
    'activate-presence-session',
    'deactivate-presence-session',
    'generate-qr-token',
    'revoke-qr-token',
]);

// Assign to student role (minimal)
$studentRole = Role::findByName('student');
// Students can only check-in (no explicit permission needed)
```

---

## Service Usage Examples

### Example 1: Generate QR Token

```php
use App\Services\AttendanceService;
use App\Models\PresenceSession;
use App\Models\User;

$service = new AttendanceService();
$session = PresenceSession::find(1);
$teacher = User::find(5); // Teacher user

$token = $service->generateQrToken($session, $teacher, 30);
// Returns PresenceQrToken instance
```

### Example 2: Process Check-In

```php
$student = User::find(10); // Student user
$result = $service->checkIn(
    qrUuid: '550e8400-e29b-41d4-a716-446655440000',
    user: $student,
    gpsLatitude: 40.7128,
    gpsLongitude: -74.0060,
    deviceFingerprint: [
        'user_agent' => request()->header('User-Agent'),
        'device_id' => 'device_123',
        'device_type' => 'mobile',
        'os_name' => 'iOS',
        'os_version' => '17.0',
        'app_version' => '1.2.3',
    ],
    ipAddress: request()->ip(),
    userAgent: request()->header('User-Agent')
);

if ($result['success']) {
    // Attendance recorded
    $presenceId = $result['presence_id'];
}
```

### Example 3: Get Attendance Report

```php
$stats = $service->getUserAttendanceStats($student);
// Returns array with attendance statistics
```

---

## Testing Checklist

- [ ] All migrations run without errors
- [ ] All models created successfully
- [ ] User relationships work correctly
- [ ] QR token generation works
- [ ] GPS validation working
- [ ] Device fingerprint hashing working
- [ ] Security flags created on validation failure
- [ ] Audit logs recorded
- [ ] API endpoints responding correctly
- [ ] Authorization checks working
- [ ] Unit tests for AttendanceService
- [ ] Integration tests for full flow
- [ ] Load testing with concurrent check-ins

---

## Monitoring & Maintenance

### Daily Tasks
- Review flagged attendances
- Monitor for suspicious patterns
- Check for device trust/revocation requests

### Weekly Tasks
- Generate attendance reports
- Analyze fraud detection effectiveness
- Review audit logs for anomalies

### Monthly Tasks
- Archive old sessions
- Clean up expired QR tokens
- Review device trust list
- Performance optimization

---

## Next Steps

1. ✅ Migrations created
2. ✅ Models created
3. ✅ Service created
4. ✅ Relationships updated
5. ⏳ Create controllers (NEXT)
6. ⏳ Create routes
7. ⏳ Create tests
8. ⏳ Create seeders for permissions
9. ⏳ Frontend QR scanner component
10. ⏳ Production deployment

---

## Support & Troubleshooting

### Common Issues

**Q: QR code expires immediately**
A: Check `expires_in_seconds` parameter. Default is 30 seconds. Increase if needed.

**Q: GPS validation always failing**
A: Verify GPS coordinates are in decimal format (e.g., 40.7128, -74.0060)

**Q: Device fingerprint mismatches**
A: Ensure User-Agent string is consistent across requests

**Q: High number of security flags**
A: Review geofence radius - may be too small for large venues

---

**Documentation Version:** 1.0  
**Last Updated:** May 13, 2026  
**Status:** Ready for Implementation
