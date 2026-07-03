<?php

namespace App\Services;

use App\Models\PresenceQrToken;
use App\Models\PresenceSession;
use App\Models\User;
use App\Repositories\PresenceRepository;
use App\Repositories\PresenceSessionRepository;
use App\Repositories\PresenceQrTokenRepository;
use Exception;

class AttendanceService
{
    protected PresenceRepository $presenceRepo;
    protected PresenceSessionRepository $sessionRepo;
    protected PresenceQrTokenRepository $tokenRepo;

    private const EARTH_RADIUS_METERS = 6371000;
    private const MAX_VELOCITY_KMH = 100;

    public function __construct(
        PresenceRepository $presenceRepo,
        PresenceSessionRepository $sessionRepo,
        PresenceQrTokenRepository $tokenRepo
    ) {
        $this->presenceRepo = $presenceRepo;
        $this->sessionRepo = $sessionRepo;
        $this->tokenRepo = $tokenRepo;
    }

    public function generateQrToken(PresenceSession $session, int $expiresInSeconds = 30): PresenceQrToken
    {
        if (!$session->is_active) {
            throw new Exception('Session is not active');
        }

        return $this->tokenRepo->createToken($session->id, $expiresInSeconds);
    }

    public function checkIn(
        string $qrToken,
        User $user,
        float $gpsLatitude,
        float $gpsLongitude,
        array $deviceFingerprint = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {

        $token = $this->tokenRepo->validateToken($qrToken);
        if (!$token) {
            return $this->createErrorResponse('Invalid or expired QR token');
        }

        $session = $token->session;
        if (!$session || !$session->is_active) {
            return $this->createErrorResponse('Session is not active');
        }

        if ($this->presenceRepo->hasUserCheckedIn($user->id, $session->id)) {
            return $this->createErrorResponse('User already checked in for this session');
        }

        $gpsValidation = $this->validateGps($session, $gpsLatitude, $gpsLongitude, $user);

        $status = 'valid';
        if (!$gpsValidation['valid']) {
            $status = 'suspicious';
        }

        $presence = $this->presenceRepo->create([
            'presence_session_id' => $session->id,
            'user_id' => $user->id,
            'checked_in_at' => now(),
            'gps_latitude' => $gpsLatitude,
            'gps_longitude' => $gpsLongitude,
            'is_within_geofence' => $gpsValidation['is_within_geofence'] ?? false,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => $status,
        ]);

        return [
            'success' => true,
            'presence_id' => $presence->id,
            'status' => $status,
            'checked_in_at' => $presence->checked_in_at,
            'is_within_geofence' => $presence->is_within_geofence,
            'flags' => $gpsValidation['flags'],
            'message' => $status === 'valid'
                ? 'Attendance recorded successfully'
                : 'Attendance recorded but flagged as suspicious for review',
        ];
    }

    public function validateGps(PresenceSession $session, float $userLatitude, float $userLongitude, User $user): array
    {
        $flags = [];

        if (!$session->gps_latitude || !$session->gps_longitude) {
            return [
                'valid' => true,
                'is_within_geofence' => true,
                'distance_meters' => null,
                'flags' => [],
            ];
        }

        $distance = $this->haversineDistance(
            (float) $session->gps_latitude,
            (float) $session->gps_longitude,
            $userLatitude,
            $userLongitude
        );

        $isWithinGeofence = $distance <= $session->gps_radius_meters;

        if (!$isWithinGeofence) {
            $flags[] = [
                'type' => 'OUTSIDE_GEOFENCE',
                'description' => "User is " . round($distance, 2) . "m away from session location",
            ];
        }

        $velocityCheck = $this->checkImpossibleVelocity($user, $userLatitude, $userLongitude);
        if ($velocityCheck['suspicious']) {
            $flags[] = [
                'type' => 'IMPOSSIBLE_VELOCITY',
                'description' => "Impossible velocity detected: {$velocityCheck['velocity_kmh']} km/h",
            ];
        }

        return [
            'valid' => empty($flags),
            'is_within_geofence' => $isWithinGeofence,
            'distance_meters' => $distance,
            'flags' => $flags,
        ];
    }

    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
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
     * MENGGUNAKAN REPOSITORY: Mengganti query langsung Presence:: ke Repository
     */
    public function checkImpossibleVelocity(User $user, float $currentLat, float $currentLon): array
    {
        // Interaksi dipindahkan ke Repository
        $lastPresence = $this->presenceRepo->getLastPresenceByUserId($user->id);

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

        return [
            'suspicious' => $velocityKmh > self::MAX_VELOCITY_KMH,
            'velocity_kmh' => round($velocityKmh, 2),
        ];
    }

    public function createErrorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'status' => 'invalid',
            'flags' => []
        ];
    }

    /**
     * MENGGUNAKAN REPOSITORY: Menghitung laporan agregasi via repository
     */
    public function getSessionReport(PresenceSession $session): array
    {
        // Menggunakan metode hitung dari repository, bukan kueri relasi langsung
        $totalAttendance      = $this->presenceRepo->countBySessionAndStatus($session->id);
        $validAttendance      = $this->presenceRepo->countBySessionAndStatus($session->id, 'valid');
        $suspiciousAttendance = $this->presenceRepo->countBySessionAndStatus($session->id, 'suspicious');
        $invalidAttendance    = $this->presenceRepo->countBySessionAndStatus($session->id, 'invalid');

        return [
            'session_id' => $session->id,
            'session_name' => $session->session_name,
            'total_attendance' => $totalAttendance,
            'valid_attendance' => $validAttendance,
            'suspicious_attendance' => $suspiciousAttendance,
            'invalid_attendance' => $invalidAttendance,
            'attendance_rate' => $totalAttendance > 0
                ? round(($validAttendance / $totalAttendance) * 100, 2)
                : 0,
        ];
    }

    /**
     * MENGGUNAKAN REPOSITORY: Menghitung statistik user via repository
     */
    public function getUserAttendanceStats(User $user): array
    {
        // Mengambil array ringkasan hitungan dari repository
        $stats = $this->presenceRepo->getUserStatsSummary($user->id);

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_attendance' => $stats['total'],
            'valid_attendance' => $stats['valid'],
            'total_valid_attendance' => $stats['valid'],
            'suspicious_attendance' => $stats['suspicious'],
            'attendance_rate' => $stats['total'] > 0
                ? round(($stats['valid'] / $stats['total']) * 100, 2)
                : 0,
        ];
    }
}
