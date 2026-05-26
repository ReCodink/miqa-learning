<?php

namespace App\Services;

use App\Models\Presence;
use App\Models\PresenceAuditLog;
use App\Models\PresenceDevice;
use App\Models\PresenceQrToken;
use App\Models\PresenceSecurityFlag;
use App\Models\PresenceSession;
use App\Models\User;
use Carbon\Carbon;
use Exception;

class AttendanceService
{
    /**
     * Constants for GPS and validation
     */
    private const EARTH_RADIUS_METERS = 6371000;
    private const MIN_GEOFENCE_RADIUS = 10;
    private const MAX_GEOFENCE_RADIUS = 500;
    private const MAX_VELOCITY_KMH = 100; // Prevent teleportation
    private const SESSION_TIME_BUFFER_MINUTES = 5;

    /**
     * Generate QR token for a session
     */
    public function generateQrToken(PresenceSession $session, User $creator, int $expiresInSeconds = 30): PresenceQrToken
    {
        if (!$session->is_active) {
            throw new Exception('Session is not active');
        }

        $token = PresenceQrToken::generateForSession($session, $creator, $expiresInSeconds);

        // Log the action
        PresenceAuditLog::log(
            action: "QR code generated for session {$session->session_name}",
            actionType: PresenceAuditLog::ACTION_QR_GENERATED,
            user: $creator,
            presence: null,
            actor: $creator,
            actorRole: $this->getUserRole($creator),
            details: [
                'session_id' => $session->id,
                'expires_in_seconds' => $expiresInSeconds,
                'uuid' => $token->uuid,
            ]
        );

        return $token;
    }

    /**
     * Validate QR token and process check-in
     */
    public function checkIn(
        string $qrUuid,
        User $user,
        float $gpsLatitude,
        float $gpsLongitude,
        array $deviceFingerprint,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        // Find the token
        $token = PresenceQrToken::where('uuid', $qrUuid)->first();

        if (!$token) {
            return $this->createErrorResponse('Invalid QR token');
        }

        // Collect all validations
        $validationResults = [
            'qr_valid' => true,
            'gps_valid' => true,
            'device_valid' => true,
            'flags' => [],
        ];

        // 1. QR Token Validation
        $qrValidation = $this->validateQrToken($token, $user);
        if (!$qrValidation['valid']) {
            $validationResults['qr_valid'] = false;
            $validationResults['flags'] = array_merge($validationResults['flags'], $qrValidation['flags']);
        }

        if (!$validationResults['qr_valid']) {
            return $this->createErrorResponse(
                'QR token validation failed',
                validationResults: $validationResults,
                flags: $validationResults['flags']
            );
        }

        // 2. Session Validation
        $session = $token->session;
        if (!$session->is_active) {
            $validationResults['flags'][] = [
                'flag_type' => PresenceSecurityFlag::FLAG_SUSPICIOUS_PATTERN,
                'flag_description' => 'Session is not active',
            ];
            return $this->createErrorResponse('Session is not active', validationResults: $validationResults);
        }

        // 3. GPS Validation
        $gpsValidation = $this->validateGps($session, $gpsLatitude, $gpsLongitude, $user);
        if (!$gpsValidation['valid']) {
            $validationResults['gps_valid'] = false;
            $validationResults['flags'] = array_merge($validationResults['flags'], $gpsValidation['flags']);
        }

        // 4. Device Fingerprint Validation
        $deviceValidation = $this->validateDevice($user, $deviceFingerprint);
        if (!$deviceValidation['valid']) {
            $validationResults['device_valid'] = false;
            $validationResults['flags'] = array_merge($validationResults['flags'], $deviceValidation['flags']);
        }

        // 5. Create attendance record
        $isValid = $validationResults['qr_valid'] && $validationResults['gps_valid'] && $validationResults['device_valid'];

        $presence = Presence::create([
            'qr_token_id' => $token->id,
            'presence_session_id' => $session->id,
            'user_id' => $user->id,
            'checked_in_at' => now(),
            'gps_latitude' => $gpsLatitude,
            'gps_longitude' => $gpsLongitude,
            'gps_distance_meters' => $gpsValidation['distance_meters'] ?? null,
            'is_within_geofence' => $gpsValidation['is_within_geofence'] ?? false,
            'device_fingerprint_json' => $deviceFingerprint,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_valid' => $isValid,
        ]);

        // 6. Mark token as used
        $token->markAsUsed($user);

        // 7. Create security flags if any
        if (!empty($validationResults['flags'])) {
            foreach ($validationResults['flags'] as $flag) {
                PresenceSecurityFlag::create([
                    'presence_id' => $presence->id,
                    'user_id' => $user->id,
                    'flag_type' => $flag['flag_type'] ?? $flag['type'] ?? null,
                    'flag_severity' => $flag['flag_severity'] ?? $flag['severity'] ?? PresenceSecurityFlag::SEVERITY_MEDIUM,
                    'flag_description' => $flag['flag_description'] ?? $flag['description'] ?? null,
                    'flag_metadata' => $flag['flag_metadata'] ?? $flag['metadata'] ?? null,
                ]);
            }
        }

        // 8. Log the action
        PresenceAuditLog::log(
            action: "Attendance recorded for {$user->name}",
            actionType: PresenceAuditLog::ACTION_ATTENDANCE_RECORDED,
            user: $user,
            presence: $presence,
            actor: $user,
            actorRole: $this->getUserRole($user),
            ipAddress: $ipAddress,
            details: [
                'is_valid' => $isValid,
                'gps_distance_meters' => $gpsValidation['distance_meters'] ?? null,
                'flags_count' => count($validationResults['flags']),
            ]
        );

        return [
            'success' => true,
            'presence_id' => $presence->id,
            'is_valid' => $isValid,
            'checked_in_at' => $presence->checked_in_at,
            'validation_results' => $validationResults,
            'flags' => $validationResults['flags'],
            'message' => $isValid
                ? 'Attendance recorded successfully'
                : 'Attendance recorded but flagged for review',
        ];
    }

    /**
     * Validate QR token
     */
    public function validateQrToken(PresenceQrToken $token, User $user): array
    {
        $flags = [];

        // Check if already used
        if ($token->is_used) {
            return [
                'valid' => false,
                'flags' => [[
                    'flag_type' => PresenceSecurityFlag::FLAG_DUPLICATE_TOKEN,
                    'flag_severity' => PresenceSecurityFlag::SEVERITY_HIGH,
                    'flag_description' => 'QR token already used',
                ]],
            ];
        }

        // Check if revoked
        if ($token->is_revoked) {
            return [
                'valid' => false,
                'flags' => [[
                    'flag_type' => PresenceSecurityFlag::FLAG_SUSPICIOUS_PATTERN,
                    'flag_severity' => PresenceSecurityFlag::SEVERITY_HIGH,
                    'flag_description' => 'QR token has been revoked',
                ]],
            ];
        }

        // Check if expired
        if ($token->isExpired()) {
            return [
                'valid' => false,
                'flags' => [[
                    'flag_type' => PresenceSecurityFlag::FLAG_EXPIRED_TOKEN,
                    'flag_severity' => PresenceSecurityFlag::SEVERITY_HIGH,
                    'flag_description' => 'QR token has expired',
                ]],
            ];
        }

        // Check for duplicate attendance in session
        $existing = Presence::where('user_id', $user->id)
            ->where('presence_session_id', $token->presence_session_id)
            ->exists();

        if ($existing) {
            $flags[] = [
                'flag_type' => PresenceSecurityFlag::FLAG_DUPLICATE_SESSION_ENTRY,
                'flag_severity' => PresenceSecurityFlag::SEVERITY_HIGH,
                'flag_description' => 'User already checked in for this session',
            ];
        }

        return [
            'valid' => empty($flags),
            'flags' => $flags,
        ];
    }

    /**
     * Validate GPS location
     */
    public function validateGps(
        PresenceSession $session,
        float $userLatitude,
        float $userLongitude,
        User $user
    ): array {
        $flags = [];

        // Check if GPS data is available in session
        if (!$session->gps_latitude || !$session->gps_longitude) {
            return [
                'valid' => true,
                'is_within_geofence' => null,
                'distance_meters' => null,
            ];
        }

        // Calculate distance using Haversine formula
        $distance = $this->haversineDistance(
            (float) $session->gps_latitude,
            (float) $session->gps_longitude,
            $userLatitude,
            $userLongitude
        );

        $isWithinGeofence = $distance <= $session->gps_radius_meters;

        // Check if outside geofence
        if (!$isWithinGeofence) {
            $flags[] = [
                'flag_type' => PresenceSecurityFlag::FLAG_OUTSIDE_GEOFENCE,
                'flag_severity' => $distance > 1000
                    ? PresenceSecurityFlag::SEVERITY_CRITICAL
                    : PresenceSecurityFlag::SEVERITY_HIGH,
                'flag_description' => "User is {$distance}m away from session location (geofence: {$session->gps_radius_meters}m)",
                'flag_metadata' => [
                    'distance_meters' => $distance,
                    'geofence_radius' => $session->gps_radius_meters,
                ],
            ];
        }

        // Check for impossible velocity (teleportation)
        $velocityCheck = $this->checkImpossibleVelocity($user, $userLatitude, $userLongitude);
        if ($velocityCheck['suspicious']) {
            $flags[] = [
                'flag_type' => PresenceSecurityFlag::FLAG_IMPOSSIBLE_VELOCITY,
                'flag_severity' => PresenceSecurityFlag::SEVERITY_CRITICAL,
                'flag_description' => "Impossible velocity detected: {$velocityCheck['velocity_kmh']} km/h",
                'flag_metadata' => $velocityCheck['metadata'],
            ];
        }

        return [
            'valid' => empty($flags),
            'is_within_geofence' => $isWithinGeofence,
            'distance_meters' => $distance,
            'flags' => $flags,
        ];
    }

    /**
     * Validate device fingerprint
     */
    public function validateDevice(User $user, array $fingerprint): array
    {
        $flags = [];
        $isValid = true; // Device validation is generally valid unless hijack attempt

        $fingerprintHash = PresenceDevice::hashFingerprint($fingerprint);

        // Find or create device record
        $device = PresenceDevice::updateOrCreateFromFingerprint($user, $fingerprint);

        // If device is not trusted and is new, flag it (but don't fail validation)
        if (!$device->is_trusted && $device->wasRecentlyCreated) {
            $flags[] = [
                'flag_type' => PresenceSecurityFlag::FLAG_DEVICE_MISMATCH,
                'flag_severity' => PresenceSecurityFlag::SEVERITY_MEDIUM,
                'flag_description' => 'New device detected',
                'flag_metadata' => [
                    'device_name' => $fingerprint['device_name'] ?? null,
                    'device_type' => $fingerprint['device_type'] ?? null,
                ],
            ];
        }

        // Check for too many different devices (potential hijacking) - THIS fails validation
        $deviceCount = $user->registeredDevices()->count();
        if ($deviceCount > 5) {
            $isValid = false; // Too many devices is a real security concern
            $flags[] = [
                'flag_type' => PresenceSecurityFlag::FLAG_HIJACK_ATTEMPT,
                'flag_severity' => PresenceSecurityFlag::SEVERITY_CRITICAL,
                'flag_description' => "User has {$deviceCount} registered devices",
            ];
        }

        return [
            'valid' => $isValid,
            'device_id' => $device->id,
            'flags' => $flags,
        ];
    }

    /**
     * Haversine formula for GPS distance calculation
     */
    public function haversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $dLat = $lat2Rad - $lat1Rad;
        $dLon = $lon2Rad - $lon1Rad;

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos($lat1Rad) * cos($lat2Rad)
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Check for impossible velocity (teleportation detection)
     */
    public function checkImpossibleVelocity(
        User $user,
        float $currentLat,
        float $currentLon
    ): array {
        // Get last presence location
        $lastPresence = Presence::where('user_id', $user->id)
            ->latest('checked_in_at')
            ->first();

        if (!$lastPresence || !$lastPresence->gps_latitude || !$lastPresence->gps_longitude) {
            return ['suspicious' => false];
        }

        $distance = $this->haversineDistance(
            (float) $lastPresence->gps_latitude,
            (float) $lastPresence->gps_longitude,
            $currentLat,
            $currentLon
        );

        $timeElapsedSeconds = now()->diffInSeconds($lastPresence->checked_in_at);
        $timeElapsedHours = $timeElapsedSeconds / 3600;

        $velocityKmh = ($distance / 1000) / max($timeElapsedHours, 0.01);

        $suspicious = $velocityKmh > self::MAX_VELOCITY_KMH;

        return [
            'suspicious' => $suspicious,
            'velocity_kmh' => round($velocityKmh, 2),
            'metadata' => [
                'distance_meters' => round($distance, 2),
                'time_elapsed_seconds' => $timeElapsedSeconds,
                'velocity_kmh' => round($velocityKmh, 2),
                'max_allowed_kmh' => self::MAX_VELOCITY_KMH,
            ],
        ];
    }

    /**
     * Check out from session
     */
    public function checkOut(Presence $presence, User $user): array
    {
        if ($presence->checked_out_at) {
            return $this->createErrorResponse('User already checked out');
        }

        $presence->checkOut();

        PresenceAuditLog::log(
            action: "Check-out recorded for {$user->name}",
            actionType: PresenceAuditLog::ACTION_ATTENDANCE_RECORDED,
            user: $user,
            presence: $presence,
            actor: $user,
            actorRole: $this->getUserRole($user),
            details: [
                'duration_minutes' => $presence->duration_minutes,
            ]
        );

        return [
            'success' => true,
            'checked_out_at' => $presence->checked_out_at,
            'duration_minutes' => $presence->duration_minutes,
            'message' => 'Check-out recorded successfully',
        ];
    }

    /**
     * Get user's primary role
     */
    public function getUserRole(User $user): string
    {
        if ($user->hasRole('manager')) {
            return 'manager';
        } elseif ($user->hasRole('teacher')) {
            return 'teacher';
        } elseif ($user->hasRole('student')) {
            return 'student';
        }

        return 'unknown';
    }

    /**
     * Create error response
     */
    public function createErrorResponse(
        string $message,
        array $validationResults = [],
        array $flags = []
    ): array {
        return [
            'success' => false,
            'message' => $message,
            'validation_results' => $validationResults,
            'flags' => $flags,
        ];
    }

    /**
     * Get session attendance report
     */
    public function getSessionReport(PresenceSession $session): array
    {
        $totalAttendance = $session->presences()->count();
        $validAttendance = $session->presences()->where('is_valid', true)->count();
        $flaggedAttendance = $session->presences()
            ->whereHas('securityFlags', function ($query) {
                $query->where('is_reviewed', false);
            })
            ->count();

        return [
            'session_id' => $session->id,
            'session_name' => $session->session_name,
            'total_attendance' => $totalAttendance,
            'valid_attendance' => $validAttendance,
            'flagged_attendance' => $flaggedAttendance,
            'attendance_rate' => $totalAttendance > 0
                ? round(($validAttendance / $totalAttendance) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get user attendance statistics
     */
    public function getUserAttendanceStats(User $user): array
    {
        $totalAttendance = $user->attendanceRecords()->count();
        $validAttendance = $user->attendanceRecords()->where('is_valid', true)->count();
        $flaggedAttendance = $user->attendanceRecords()
            ->whereHas('securityFlags', function ($query) {
                $query->where('is_reviewed', false);
            })
            ->count();

        $averageDuration = $user->attendanceRecords()
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes');

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_attendance' => $totalAttendance,
            'valid_attendance' => $validAttendance,
            'total_valid_attendance' => $validAttendance,  // Alias for test compatibility
            'flagged_attendance' => $flaggedAttendance,
            'attendance_rate' => $totalAttendance > 0
                ? round(($validAttendance / $totalAttendance) * 100, 2)
                : 0,
            'average_duration_minutes' => round($averageDuration ?? 0, 2),
        ];
    }
}
