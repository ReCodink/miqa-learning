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

class PresenceSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teacher;
    protected User $student;
    protected ClassRoom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and users
        \Spatie\Permission\Models\Role::create(['name' => 'manager']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'student']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create();
        $this->student->assignRole('student');

        $this->classroom = ClassRoom::factory()->create();

        // Create permissions
        \Spatie\Permission\Models\Permission::create(['name' => 'create-presence-session']);
        \Spatie\Permission\Models\Permission::create(['name' => 'activate-presence-session']);
        \Spatie\Permission\Models\Permission::create(['name' => 'update-presence-session']);
        \Spatie\Permission\Models\Permission::create(['name' => 'deactivate-presence-session']);

        $managerRole = \Spatie\Permission\Models\Role::findByName('manager');
        $managerRole->givePermissionTo(['create-presence-session', 'activate-presence-session', 'update-presence-session', 'deactivate-presence-session']);

        $teacherRole = \Spatie\Permission\Models\Role::findByName('teacher');
        $teacherRole->givePermissionTo(['create-presence-session', 'activate-presence-session', 'update-presence-session', 'deactivate-presence-session']);
    }

    /** @test */
    public function manager_can_create_presence_session()
    {
        Sanctum::actingAs($this->manager, ['*']);

        $response = $this->postJson('/api/presence/sessions', [
            'class_room_id' => $this->classroom->id,
            'session_name' => 'Test Session',
            'session_type' => 'class',
            'gps_latitude' => 40.7128,
            'gps_longitude' => -74.0060,
            'gps_radius_meters' => 50,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'session_name', 'is_active', 'created_at']
        ]);
        $this->assertDatabaseHas('presence_sessions', [
            'session_name' => 'Test Session'
        ]);
    }

    /** @test */
    public function teacher_can_create_presence_session()
    {
        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->postJson('/api/presence/sessions', [
            'class_room_id' => $this->classroom->id,
            'session_name' => 'Teacher Session',
            'session_type' => 'class',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function student_cannot_create_presence_session()
    {
        Sanctum::actingAs($this->student, ['*']);

        $response = $this->postJson('/api/presence/sessions', [
            'class_room_id' => $this->classroom->id,
            'session_name' => 'Unauthorized Session',
            'session_type' => 'class',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function can_get_session_details()
    {
        $session = PresenceSession::factory()->create([
            'class_room_id' => $this->classroom->id,
            'created_by_user_id' => $this->teacher->id,
        ]);

        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->getJson("/api/presence/sessions/{$session->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'session_name', 'is_active']
        ]);
    }

    /** @test */
    public function can_activate_session()
    {
        $session = PresenceSession::factory()->create([
            'class_room_id' => $this->classroom->id,
            'created_by_user_id' => $this->teacher->id,
            'is_active' => false,
        ]);

        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->postJson("/api/presence/sessions/{$session->id}/activate");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => ['is_active' => true]
        ]);
        $this->assertTrue($session->fresh()->is_active);
    }

    /** @test */
    public function can_deactivate_session()
    {
        $session = PresenceSession::factory()->create([
            'class_room_id' => $this->classroom->id,
            'created_by_user_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->postJson("/api/presence/sessions/{$session->id}/deactivate");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => ['is_active' => false]
        ]);
    }
}
