<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PresencePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Get or create roles
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $teacher = Role::firstOrCreate(['name' => 'teacher']);
        $student = Role::firstOrCreate(['name' => 'student']);

        // Assign permissions to manager role (all permissions)
        $manager->syncPermissions([
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

        // Assign permissions to teacher role
        $teacher->syncPermissions([
            'create-presence-session',
            'update-presence-session',
            'activate-presence-session',
            'deactivate-presence-session',
            'generate-qr-token',
            'revoke-qr-token',
        ]);

        // Student role has no explicit permissions (can only check-in without permissions check)
        $student->syncPermissions([]);

        $this->command->info('Presence permissions and roles configured successfully!');
        $this->command->line('Manager: All permissions assigned');
        $this->command->line('Teacher: Session and QR token management');
        $this->command->line('Student: Can check-in (no explicit permissions required)');
    }
}
