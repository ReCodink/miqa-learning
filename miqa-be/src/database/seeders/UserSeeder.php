<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('Creating users in batches...');

        $batchSize = 50;
        $totalUsers = 200;
        $batches = ceil($totalUsers / $batchSize);

        // 1. Super Admin
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@jawara.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'photo' => fake()->imageUrl(400, 400, 'people'),
                'gender' => 'male',
                'email_verified_at' => now(),
            ]
        );
        $adminUser->syncRoles(['manager']);

        // 2. Sample Teacher
        $teacherUser = User::updateOrCreate(
            ['email' => 'teacher@jawara.com'],
            [
                'name' => 'Sample Teacher',
                'password' => bcrypt('password'),
                'photo' => fake()->imageUrl(400, 400, 'people'),
                'gender' => 'female',
                'email_verified_at' => now(),
            ]
        );
        $teacherUser->syncRoles(['teacher']);

        // 3. Sample Student
        $studentUser = User::updateOrCreate(
            ['email' => 'student@jawara.com'],
            [
                'name' => 'Sample Student',
                'password' => bcrypt('password'),
                'photo' => fake()->imageUrl(400, 400, 'people'),
                'gender' => 'male',
                'email_verified_at' => now(),
            ]
        );
        $studentUser->syncRoles(['student']);

        // Bulk create remaining users (Tanpa withoutEvents agar booted() model aktif kembali)
        for ($i = 1; $i <= $batches; $i++) {
            $currentBatchSize = ($i == $batches) ? $totalUsers % $batchSize : $batchSize;
            if ($currentBatchSize == 0) $currentBatchSize = $batchSize;

            // Model booted() akan otomatis mengisi code: USR-2026-0004, USR-2026-0005, dst
            $users = User::factory($currentBatchSize)->create();

            // Assign roles
            $users->each(function ($user, $index) {
                if ($index % 10 == 0) {
                    $user->assignRole('manager');
                } elseif ($index % 5 == 0) {
                    $user->assignRole('teacher');
                } else {
                    $user->assignRole('student');
                }
            });

            $this->command->info("Batch {$i}/{$batches} completed - {$currentBatchSize} users created");

            unset($users);
            gc_collect_cycles();
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('User seeding completed successfully!');
    }
}
