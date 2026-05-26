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

class PresenceReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected User $student;
    protected PresenceSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'student']);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $classroom = ClassRoom::factory()->create();
        $this->session = PresenceSession::factory()->create([
            'class_room_id' => $classroom->id,
            'created_by_user_id' => $this->teacher->id,
        ]);

        // Create sample attendance records
        Presence::factory()->count(5)->create([
            'presence_session_id' => $this->session->id,
        ]);
    }

    /** @test */
    public function user_can_get_own_attendance_stats()
    {
        Presence::factory()->create([
            'user_id' => $this->student->id,
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->getJson('/api/presence/reports/user-stats');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
        ]);
    }

    /** @test */
    public function teacher_can_get_session_report()
    {
        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->getJson("/api/presence/reports/session/{$this->session->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'session_id',
                'total_attendance',
                'valid_attendance',
                'attendance_rate',
            ],
        ]);
    }

    /** @test */
    public function teacher_can_get_summary_statistics()
    {
        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->getJson('/api/presence/reports/summary');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_records',
                'valid_records',
                'validity_rate',
                'by_severity',
                'by_flag_type',
            ],
        ]);
    }
}
