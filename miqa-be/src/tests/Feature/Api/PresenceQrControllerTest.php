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

class PresenceQrControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected PresenceSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Permission::create(['name' => 'generate-qr-token']);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $classroom = ClassRoom::factory()->create();
        $this->session = PresenceSession::factory()->create([
            'class_room_id' => $classroom->id,
            'created_by_user_id' => $this->teacher->id,
            'is_active' => true,
        ]);

        $teacherRole = \Spatie\Permission\Models\Role::findByName('teacher');
        $teacherRole->givePermissionTo('generate-qr-token');
    }

    /** @test */
    public function teacher_can_generate_qr_token()
    {
        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->postJson('/api/presence/qr/generate', [
            'session_id' => $this->session->id,
            'expires_in_seconds' => 30,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => ['id', 'uuid', 'expires_at', 'qr_code_svg']
        ]);
        $this->assertDatabaseHas('presence_qr_tokens', [
            'presence_session_id' => $this->session->id,
        ]);
    }

    /** @test */
    public function cannot_generate_token_for_inactive_session()
    {
        $this->session->update(['is_active' => false]);

        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->postJson('/api/presence/qr/generate', [
            'session_id' => $this->session->id,
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function can_get_qr_token_details()
    {
        $token = PresenceQrToken::factory()->create([
            'presence_session_id' => $this->session->id,
        ]);

        Sanctum::actingAs($this->teacher, ['*']);

        $response = $this->getJson("/api/presence/qr/{$token->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ['uuid', 'is_used', 'is_expired']
        ]);
    }
}
