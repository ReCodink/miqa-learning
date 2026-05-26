<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PresenceSession;
use App\Models\ClassRoom;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PresenceSession>
 */
class PresenceSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PresenceSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'class_room_id' => ClassRoom::factory(),
            'created_by_user_id' => User::factory(),
            'session_name' => $this->faker->sentence(3),
            'session_type' => $this->faker->randomElement(['class', 'event', 'exam_preparation']),
            'scheduled_start_at' => $this->faker->dateTimeThisMonth(),
            'scheduled_end_at' => $this->faker->dateTimeThisMonth(),
            'actual_start_at' => null,
            'actual_end_at' => null,
            'gps_latitude' => $this->faker->latitude(),
            'gps_longitude' => $this->faker->longitude(),
            'gps_radius_meters' => $this->faker->numberBetween(10, 100),
            'is_active' => false,
            'notes' => $this->faker->sentence(),
        ];
    }

    /**
     * Indicate that the session should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'actual_start_at' => now(),
        ]);
    }

    /**
     * Indicate that the session should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'actual_end_at' => now(),
        ]);
    }
}
