<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceSecurityFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'presence_id',
        'user_id',
        'flag_type',
        'flag_severity',
        'flag_description',
        'flag_metadata',
        'is_reviewed',
        'reviewed_by_user_id',
        'review_notes',
        'action_taken',
    ];

    protected $casts = [
        'is_reviewed' => 'boolean',
        'flag_metadata' => 'array',
    ];

    // Flag type constants
    const FLAG_DUPLICATE_TOKEN = 'duplicate_token';
    const FLAG_EXPIRED_TOKEN = 'expired_token';
    const FLAG_OUTSIDE_GEOFENCE = 'outside_geofence';
    const FLAG_IMPOSSIBLE_VELOCITY = 'impossible_velocity';
    const FLAG_DEVICE_MISMATCH = 'device_mismatch';
    const FLAG_DUPLICATE_SESSION_ENTRY = 'duplicate_session_entry';
    const FLAG_HIJACK_ATTEMPT = 'hijack_attempt';
    const FLAG_SUSPICIOUS_PATTERN = 'suspicious_pattern';

    // Severity constants
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Action constants
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_INVESTIGATE = 'investigate';

    /**
     * Get the attendance record this flag is for.
     */
    public function presence(): BelongsTo
    {
        return $this->belongsTo(Presence::class);
    }

    /**
     * Get the user who was flagged.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed this flag.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Mark flag as reviewed
     */
    public function markReviewed(User $reviewer, string $action, ?string $notes = null): bool
    {
        return $this->update([
            'is_reviewed' => true,
            'reviewed_by_user_id' => $reviewer->id,
            'action_taken' => $action,
            'review_notes' => $notes,
        ]);
    }

    /**
     * Approve flagged attendance
     */
    public function approve(User $reviewer, ?string $notes = null): bool
    {
        // Update presence to valid
        $this->presence->update(['is_valid' => true]);

        return $this->markReviewed($reviewer, self::ACTION_APPROVED, $notes);
    }

    /**
     * Reject flagged attendance
     */
    public function reject(User $reviewer, ?string $notes = null): bool
    {
        // Delete the presence record
        $this->presence->delete();

        return $this->markReviewed($reviewer, self::ACTION_REJECTED, $notes);
    }

    /**
     * Mark for investigation
     */
    public function investigate(User $reviewer, ?string $notes = null): bool
    {
        return $this->markReviewed($reviewer, self::ACTION_INVESTIGATE, $notes);
    }

    /**
     * Get severity as integer for sorting
     */
    public function getSeverityRank(): int
    {
        return match ($this->flag_severity) {
            self::SEVERITY_CRITICAL => 1,
            self::SEVERITY_HIGH => 2,
            self::SEVERITY_MEDIUM => 3,
            self::SEVERITY_LOW => 4,
            default => 5,
        };
    }
}
