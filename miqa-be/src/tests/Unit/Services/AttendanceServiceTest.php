<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AttendanceService;
use App\Models\PresenceSession;
use App\Models\PresenceQrToken;
use App\Models\Presence;
use App\Models\User;
use App\Models\ClassRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;
    protected AttendanceService $service;
    protected User $teacher;
    protected User $student;
    protected ClassRoom $classroom;
    protected PresenceSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceService();

        // Create test data
        $this->teacher = User::factory()->create();
        $this->student = User::factory()->create();
        $this->classroom = ClassRoom::factory()->create();

        // Create session
        $this->session = PresenceSession::create([
            'class_room_id' => $this->classroom->id,
            'created_by_user_id' => $this->teacher->id,
            'session_name' => 'Test Session',
            'session_type' => 'class',
            'gps_latitude' => 40.7128,
            'gps_longitude' => -74.0060,
            'gps_radius_meters' => 50,
        ]);

        $this->session->activate();
    }

    /** @test */
    public function it_generates_qr_token_with_uuid()
    {
        $token = $this->service->generateQrToken($this->session, $this->teacher, 30);

        $this->assertNotNull($token->uuid);
        $this->assertFalse($token->is_used);
        $this->assertFalse($token->is_revoked);
        $this->assertEquals($this->session->id, $token->presence_session_id);
        $this->assertNull($token->used_by_user_id);
    }

    /** @test */
    public function it_validates_qr_token_correctly()
    {
        $token = $this->service->generateQrToken($this->session, $this->teacher, 30);

        $result = $this->service->validateQrToken($token, $this->student);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['flags']);
    }

    /** @test */
    public function it_flags_expired_qr_token()
    {
        $token = PresenceQrToken::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'presence_session_id' => $this->session->id,
            'created_by_user_id' => $this->teacher->id,
            'generated_at' => now(),
            'expires_at' => now()->subMinutes(1),
        ]);

        $result = $this->service->validateQrToken($token, $this->student);

        $this->assertFalse($result['valid']);
        $this->assertContains('expired_token', collect($result['flags'])->pluck('flag_type'));
    }

    /** @test */
    public function it_flags_already_used_qr_token()
    {
        $token = $this->service->generateQrToken($this->session, $this->teacher, 30);
        $token->markAsUsed($this->student);

        $result = $this->service->validateQrToken($token, $this->student);

        $this->assertFalse($result['valid']);
        $this->assertContains('duplicate_token', collect($result['flags'])->pluck('flag_type'));
    }

    /** @test */
    public function it_flags_revoked_qr_token()
    {
        $token = $this->service->generateQrToken($this->session, $this->teacher, 30);
        $token->revoke('Test revocation');

        $result = $this->service->validateQrToken($token, $this->student);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function it_calculates_haversine_distance_correctly()
    {
        // New York City coordinates
        $lat1 = 40.7128;
        $lon1 = -74.0060;

        // Approximately 1 mile away - proper calculation
        // 1 mile ≈ 1.609 km, at this latitude ~111 km/degree, so ~0.0145 degrees
        $lat2 = 40.7128 + 0.0145;
        $lon2 = -74.0060;

        $distance = $this->service->haversineDistance($lat1, $lon1, $lat2, $lon2);

        // Should be approximately 1609 meters (±250 meters tolerance)
        $this->assertGreaterThan(1350, $distance);
        $this->assertLessThan(1850, $distance);
    }

    /** @test */
    public function it_validates_gps_within_geofence()
    {
        $result = $this->service->validateGps(
            $this->session,
            40.7128, // Same location
            -74.0060,
            $this->student
        );

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['is_within_geofence']);
        $this->assertLessThan(10, $result['distance_meters']);
    }

    /** @test */
    public function it_flags_gps_outside_geofence()
    {
        $result = $this->service->validateGps(
            $this->session,
            40.7580, // ~5km away
            -73.9855,
            $this->student
        );

        $this->assertFalse($result['valid']);
        $this->assertFalse($result['is_within_geofence']);
        $this->assertGreaterThan(1000, $result['distance_meters']);
        $this->assertContains('outside_geofence', collect($result['flags'])->pluck('flag_type'));
    }

    /** @test */
    public function it_detects_impossible_velocity()
    {
        // Create first attendance record
        $presence1 = Presence::create([
            'qr_token_id' => $this->service->generateQrToken($this->session, $this->teacher, 30)->id,
            'presence_session_id' => $this->session->id,
            'user_id' => $this->student->id,
            'checked_in_at' => now(),
            'gps_latitude' => 40.7128,
            'gps_longitude' => -74.0060,
            'ip_address' => '127.0.0.1',
            'is_valid' => true,
        ]);

        // Check velocity from NYC to LA in 1 minute (impossible)
        $result = $this->service->checkImpossibleVelocity($this->student, 34.0522, -118.2437);

        $this->assertTrue($result['suspicious']);
        $this->assertGreaterThan(100, $result['velocity_kmh']); // > 100 km/h
    }

    /** @test */
    public function it_creates_presence_record_with_valid_data()
    {
        $token = $this->service->generateQrToken($this->session, $this->teacher, 30);

        $result = $this->service->checkIn(
            qrUuid: $token->uuid,
            user: $this->student,
            gpsLatitude: 40.7128,
            gpsLongitude: -74.0060,
            deviceFingerprint: [
                'user_agent' => 'Mozilla/5.0',
                'device_type' => 'mobile',
                'os_name' => 'iOS',
            ],
            ipAddress: '127.0.0.1',
            userAgent: 'Mozilla/5.0'
        );

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['presence_id']);
        $this->assertNotNull($result['checked_in_at']);
    }

    /** @test */
    public function it_prevents_duplicate_attendance()
    {
        $token1 = $this->service->generateQrToken($this->session, $this->teacher, 30);

        // First check-in succeeds
        $result1 = $this->service->checkIn(
            qrUuid: $token1->uuid,
            user: $this->student,
            gpsLatitude: 40.7128,
            gpsLongitude: -74.0060,
            deviceFingerprint: [],
            ipAddress: '127.0.0.1',
            userAgent: 'Mozilla/5.0'
        );

        $this->assertTrue($result1['success']);

        // Second check-in with different token fails
        $token2 = $this->service->generateQrToken($this->session, $this->teacher, 30);
        $result2 = $this->service->checkIn(
            qrUuid: $token2->uuid,
            user: $this->student,
            gpsLatitude: 40.7128,
            gpsLongitude: -74.0060,
            deviceFingerprint: [],
            ipAddress: '127.0.0.1',
            userAgent: 'Mozilla/5.0'
        );

        $this->assertFalse($result2['success']);
        $this->assertContains('duplicate_session_entry', collect($result2['flags'])->pluck('flag_type'));
    }

    /** @test */
    public function it_records_checkout_with_duration()
    {
        $token = $this->service->generateQrToken($this->session, $this->teacher, 30);

        $checkIn = $this->service->checkIn(
            qrUuid: $token->uuid,
            user: $this->student,
            gpsLatitude: 40.7128,
            gpsLongitude: -74.0060,
            deviceFingerprint: [],
            ipAddress: '127.0.0.1',
            userAgent: 'Mozilla/5.0'
        );

        $presence = Presence::find($checkIn['presence_id']);

        $result = $this->service->checkOut($presence, $this->student);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['checked_out_at']);
        $this->assertGreaterThanOrEqual(0, $result['duration_minutes']);
    }

    /** @test */
    public function it_generates_session_report()
    {
        // Create multiple check-ins with different students
        $students = [
            User::factory()->create(),
            User::factory()->create(),
            User::factory()->create(),
        ];

        foreach ($students as $student) {
            $token = $this->service->generateQrToken($this->session, $this->teacher, 30);

            $this->service->checkIn(
                qrUuid: $token->uuid,
                user: $student,
                gpsLatitude: 40.7128,
                gpsLongitude: -74.0060,
                deviceFingerprint: [],
                ipAddress: '127.0.0.1',
                userAgent: 'Mozilla/5.0'
            );
        }

        $report = $this->service->getSessionReport($this->session);

        $this->assertNotNull($report['session_id']);
        $this->assertEquals(3, $report['total_attendance']);
        $this->assertGreaterThanOrEqual(0, $report['valid_attendance']);
        $this->assertGreaterThanOrEqual(0, $report['attendance_rate']);
    }

    /** @test */
    public function it_generates_user_attendance_stats()
    {
        // Create multiple attendance records
        for ($i = 0; $i < 2; $i++) {
            $token = $this->service->generateQrToken($this->session, $this->teacher, 30);

            $this->service->checkIn(
                qrUuid: $token->uuid,
                user: $this->student,
                gpsLatitude: 40.7128,
                gpsLongitude: -74.0060,
                deviceFingerprint: [],
                ipAddress: '127.0.0.1',
                userAgent: 'Mozilla/5.0'
            );
        }

        $stats = $this->service->getUserAttendanceStats($this->student);

        $this->assertNotNull($stats);
        $this->assertGreaterThanOrEqual(1, $stats['total_valid_attendance']);
    }
}
