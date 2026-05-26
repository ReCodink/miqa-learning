<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\ClassRoom;
use App\Models\PresenceSession;
use App\Models\PresenceQrToken;
use App\Models\Presence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PresenceCheckInControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $teacher;
    protected PresenceSession $session;
    protected PresenceQrToken $qrToken;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'student']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $classroom = ClassRoom::factory()->create();
        $this->session = PresenceSession::factory()->create([
            'class_room_id' => $classroom->id,
            'created_by_user_id' => $this->teacher->id,
            'is_active' => true,
            'gps_latitude' => 40.7128,
            'gps_longitude' => -74.0060,
            'gps_radius_meters' => 50,
        ]);

        $this->qrToken = PresenceQrToken::factory()->create([
            'presence_session_id' => $this->session->id,
            'created_by_user_id' => $this->teacher->id,
            'expires_at' => now()->addMinutes(1),
        ]);
    }

    /** @test */
    public function student_can_check_in_with_valid_qr()
    {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->postJson('/api/presence/attendance/check-in', [
            'qr_token' => $this->qrToken->uuid,
            'session_id' => $this->session->id,
            'gps_latitude' => 40.7128,
            'gps_longitude' => -74.0060,
            'device_fingerprint' => [
                'user_agent' => 'Mozilla/5.0',
                'device_type' => 'mobile',
                'os_name' => 'iOS',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'presence_id',
            'checked_in_at',
            'validation_results',
            'flags',
        ]);
        $this->assertDatabaseHas('presences', [
            'user_id' => $this->student->id,
            'presence_session_id' => $this->session->id,
        ]);
    }

    /** @test */
    public function check_in_with_invalid_uuid_fails()
    {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->postJson('/api/presence/attendance/check-in', [
            'qr_token' => 'invalid-uuid',
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function check_in_with_expired_token_fails()
    {
        $expiredToken = PresenceQrToken::factory()->create([
            'presence_session_id' => $this->session->id,
            'created_by_user_id' => $this->teacher->id,
            'expires_at' => now()->subMinutes(1),
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->postJson('/api/presence/attendance/check-in', [
            'qr_token' => $expiredToken->uuid,
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function student_can_check_out()
    {
        // Check in first
        $presence = Presence::factory()->create([
            'user_id' => $this->student->id,
            'presence_session_id' => $this->session->id,
            'checked_in_at' => now(),
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->postJson("/api/presence/attendance/{$presence->id}/check-out");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'checked_out_at',
            'duration_minutes',
        ]);
        $this->assertNotNull($presence->fresh()->checked_out_at);
    }

    /** @test */
    public function can_get_my_attendance()
    {
        Presence::factory()->create([
            'user_id' => $this->student->id,
            'presence_session_id' => $this->session->id,
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/presence/attendance/my-attendance');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination',
        ]);
    }
}
