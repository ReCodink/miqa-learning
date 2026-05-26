<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PresenceSession extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'class_room_id',
        'created_by_user_id',
        'session_name',
        'session_type',
        'scheduled_start_at',
        'scheduled_end_at',
        'actual_start_at',
        'actual_end_at',
        'gps_latitude',
        'gps_longitude',
        'gps_radius_meters',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'actual_start_at' => 'datetime',
        'actual_end_at' => 'datetime',
        'is_active' => 'boolean',
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
    ];

    /**
     * Get the classroom this session belongs to.
     */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    /**
     * Get the user who created this session.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all QR tokens generated for this session.
     */
    public function qrTokens(): HasMany
    {
        return $this->hasMany(PresenceQrToken::class);
    }

    /**
     * Get all attendance records for this session.
     */
    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    /**
     * Get count of valid attendances
     */
    public function validAttendancesCount(): int
    {
        return $this->presences()->where('is_valid', true)->count();
    }

    /**
     * Get count of flagged attendances
     */
    public function flaggedAttendancesCount(): int
    {
        return $this->presences()
            ->whereHas('securityFlags', function ($query) {
                $query->where('is_reviewed', false);
            })
            ->count();
    }

    /**
     * Activate session (start attendance)
     */
    public function activate(): bool
    {
        return $this->update([
            'is_active' => true,
            'actual_start_at' => now(),
        ]);
    }

    /**
     * Deactivate session (end attendance)
     */
    public function deactivate(): bool
    {
        return $this->update([
            'is_active' => false,
            'actual_end_at' => now(),
        ]);
    }
}
