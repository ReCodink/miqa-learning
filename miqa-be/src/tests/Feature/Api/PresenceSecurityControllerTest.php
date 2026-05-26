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

class PresenceSecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'manager']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Permission::create(['name' => 'review-security-flags']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $managerRole = \Spatie\Permission\Models\Role::findByName('manager');
        $managerRole->givePermissionTo('review-security-flags');
    }

    /** @test */
    public function manager_can_list_unreviewed_flags()
    {
        Sanctum::actingAs($this->manager, ['*']);

        $response = $this->getJson('/api/presence/security/flags?is_reviewed=false');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'pagination',
        ]);
    }

    /** @test */
    public function manager_can_review_security_flag()
    {
        $flag = \App\Models\PresenceSecurityFlag::factory()->create();

        Sanctum::actingAs($this->manager, ['*']);

        $response = $this->putJson("/api/presence/security/flags/{$flag->id}", [
            'action' => 'approved',
            'review_notes' => 'Valid attendance confirmed',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($flag->fresh()->is_reviewed);
    }
}
