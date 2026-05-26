<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Presence;
use App\Models\PresenceSession;
use App\Models\PresenceQrToken;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Presence>
 */
class PresenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Presence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'qr_token_id' => PresenceQrToken::factory(),
            'presence_session_id' => PresenceSession::factory(),
            'user_id' => User::factory(),
            'checked_in_at' => now(),
            'checked_out_at' => null,
            'duration_minutes' => null,
            'gps_latitude' => $this->faker->latitude(),
            'gps_longitude' => $this->faker->longitude(),
            'gps_distance_meters' => $this->faker->numberBetween(0, 50),
            'is_within_geofence' => true,
            'device_fingerprint_json' => $this->faker->word(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'is_valid' => true,
        ];
    }

    /**
     * Indicate that the presence record includes a check-out.
     */
    public function withCheckOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_out_at' => now()->addMinutes($this->faker->numberBetween(15, 120)),
            'duration_minutes' => $this->faker->numberBetween(15, 120),
        ]);
    }

    /**
     * Indicate that the presence is invalid.
     */
    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_valid' => false,
        ]);
    }

    /**
     * Indicate that the presence is outside geofence.
     */
    public function outsideGeofence(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_within_geofence' => false,
            'gps_distance_meters' => $this->faker->numberBetween(100, 500),
            'is_valid' => false,
        ]);
    }
}
