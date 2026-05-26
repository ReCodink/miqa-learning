<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class PresenceDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_fingerprint_hash',
        'device_name',
        'device_type',
        'os_name',
        'os_version',
        'app_version',
        'is_trusted',
        'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the user this device belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create or update device record
     */
    public static function updateOrCreateFromFingerprint(User $user, array $fingerprint): self
    {
        $hash = self::hashFingerprint($fingerprint);

        return self::updateOrCreate(
            ['user_id' => $user->id, 'device_fingerprint_hash' => $hash],
            [
                'device_name' => $fingerprint['device_name'] ?? null,
                'device_type' => $fingerprint['device_type'] ?? null,
                'os_name' => $fingerprint['os_name'] ?? null,
                'os_version' => $fingerprint['os_version'] ?? null,
                'app_version' => $fingerprint['app_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );
    }

    /**
     * Hash device fingerprint for comparison
     */
    public static function hashFingerprint(array $fingerprint): string
    {
        $data = [
            $fingerprint['user_agent'] ?? '',
            $fingerprint['device_id'] ?? '',
            $fingerprint['os_name'] ?? '',
            $fingerprint['os_version'] ?? '',
            $fingerprint['app_version'] ?? '',
        ];

        return hash('sha256', implode('|', $data));
    }

    /**
     * Match fingerprint to this device
     */
    public function matchesFingerprint(array $fingerprint): bool
    {
        return $this->device_fingerprint_hash === self::hashFingerprint($fingerprint);
    }

    /**
     * Trust this device
     */
    public function trust(): bool
    {
        return $this->update(['is_trusted' => true]);
    }

    /**
     * Untrust this device
     */
    public function untrust(): bool
    {
        return $this->update(['is_trusted' => false]);
    }

    /**
     * Update last seen timestamp
     */
    public function recordSeen(): bool
    {
        return $this->update(['last_seen_at' => now()]);
    }
}
