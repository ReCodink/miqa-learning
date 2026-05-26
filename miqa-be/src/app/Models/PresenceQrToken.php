<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PresenceQrToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'presence_session_id',
        'created_by_user_id',
        'generated_at',
        'expires_at',
        'is_used',
        'is_revoked',
        'used_by_user_id',
        'used_at',
        'revoked_at',
        'revoke_reason',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'is_revoked' => 'boolean',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the session this QR token belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PresenceSession::class, 'presence_session_id');
    }

    /**
     * Get the user who created this token.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who used this token.
     */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    /**
     * Get the attendance record(s) using this token.
     */
    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class, 'qr_token_id');
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if token is valid for use
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->is_revoked && !$this->isExpired();
    }

    /**
     * Mark token as used
     */
    public function markAsUsed(User $user): bool
    {
        return $this->update([
            'is_used' => true,
            'used_by_user_id' => $user->id,
            'used_at' => now(),
        ]);
    }

    /**
     * Revoke token before use
     */
    public function revoke(string $reason = null): bool
    {
        return $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }

    /**
     * Generate new QR token for session
     */
    public static function generateForSession(PresenceSession $session, User $creator, int $expiresInSeconds = 30): self
    {
        return self::create([
            'uuid' => Str::uuid(),
            'presence_session_id' => $session->id,
            'created_by_user_id' => $creator->id,
            'generated_at' => now(),
            'expires_at' => now()->addSeconds($expiresInSeconds),
            'is_used' => false,
            'is_revoked' => false,
        ]);
    }
}
