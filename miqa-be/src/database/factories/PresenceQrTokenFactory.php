<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PresenceQrToken;
use App\Models\PresenceSession;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PresenceQrToken>
 */
class PresenceQrTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PresenceQrToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'presence_session_id' => PresenceSession::factory(),
            'created_by_user_id' => User::factory(),
            'generated_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'is_used' => false,
            'is_revoked' => false,
            'used_by_user_id' => null,
            'used_at' => null,
            'revoked_at' => null,
            'revoke_reason' => null,
        ];
    }

    /**
     * Indicate that the token has been used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_used' => true,
            'used_by_user_id' => User::factory(),
            'used_at' => now(),
        ]);
    }

    /**
     * Indicate that the token has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoke_reason' => 'Manual revocation',
        ]);
    }

    /**
     * Indicate that the token has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }
}
