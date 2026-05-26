<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presence extends Model
{
    use HasFactory;

    protected $fillable = [
        'qr_token_id',
        'presence_session_id',
        'user_id',
        'checked_in_at',
        'checked_out_at',
        'duration_minutes',
        'gps_latitude',
        'gps_longitude',
        'gps_distance_meters',
        'is_within_geofence',
        'device_fingerprint_json',
        'ip_address',
        'user_agent',
        'is_valid',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'is_within_geofence' => 'boolean',
        'is_valid' => 'boolean',
        'device_fingerprint_json' => 'array',
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
        'gps_distance_meters' => 'decimal:2',
    ];

    /**
     * Get the QR token used for this check-in.
     */
    public function qrToken(): BelongsTo
    {
        return $this->belongsTo(PresenceQrToken::class);
    }

    /**
     * Get the session this attendance belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PresenceSession::class, 'presence_session_id');
    }

    /**
     * Get the user who checked in.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get security flags associated with this attendance.
     */
    public function securityFlags(): HasMany
    {
        return $this->hasMany(PresenceSecurityFlag::class);
    }

    /**
     * Get audit logs for this attendance.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(PresenceAuditLog::class);
    }

    /**
     * Mark check-out time and calculate duration
     */
    public function checkOut(): bool
    {
        $checkedOutAt = now();
        $durationMinutes = (int) $this->checked_in_at->diffInMinutes($checkedOutAt);

        return $this->update([
            'checked_out_at' => $checkedOutAt,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Has security flags that are not reviewed
     */
    public function hasUnreviewedFlags(): bool
    {
        return $this->securityFlags()->where('is_reviewed', false)->exists();
    }

    /**
     * Get count of unreviewed flags
     */
    public function unreviewedFlagsCount(): int
    {
        return $this->securityFlags()->where('is_reviewed', false)->count();
    }

    /**
     * Get severity of highest unreviewed flag
     */
    public function highestFlagSeverity(): ?string
    {
        $severityOrder = ['critical', 'high', 'medium', 'low'];

        $flag = $this->securityFlags()
            ->where('is_reviewed', false)
            ->orderByRaw("CASE flag_severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                END")
            ->first();

        return $flag?->flag_severity;
    }
}
