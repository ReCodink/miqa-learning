<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'presence_id',
        'user_id',
        'action',
        'action_type',
        'action_details',
        'actor_user_id',
        'actor_role',
        'ip_address',
    ];

    protected $casts = [
        'action_details' => 'array',
    ];

    // Action type constants
    const ACTION_QR_GENERATED = 'qr_generated';
    const ACTION_QR_SCANNED = 'qr_scanned';
    const ACTION_ATTENDANCE_RECORDED = 'attendance_recorded';
    const ACTION_ATTENDANCE_VERIFIED = 'attendance_verified';
    const ACTION_FRAUD_DETECTED = 'fraud_detected';
    const ACTION_FLAG_REVIEWED = 'flag_reviewed';
    const ACTION_DEVICE_TRUSTED = 'device_trusted';
    const ACTION_SESSION_STARTED = 'session_started';
    const ACTION_SESSION_ENDED = 'session_ended';

    /**
     * Get the attendance record this log is for.
     */
    public function presence(): BelongsTo
    {
        return $this->belongsTo(Presence::class);
    }

    /**
     * Get the user this log is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Create audit log entry
     */
    public static function log(
        string $action,
        string $actionType,
        User $user = null,
        Presence $presence = null,
        User $actor = null,
        string $actorRole = null,
        string $ipAddress = null,
        array $details = []
    ): self {
        return self::create([
            'presence_id' => $presence?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'action_type' => $actionType,
            'action_details' => $details,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actorRole,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Get audit trail for a user
     */
    public static function forUser(User $user)
    {
        return self::where('user_id', $user->id)
            ->orWhere('actor_user_id', $user->id)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get audit trail for a presence
     */
    public static function forPresence(Presence $presence)
    {
        return self::where('presence_id', $presence->id)
            ->orderBy('created_at', 'desc');
    }
}
