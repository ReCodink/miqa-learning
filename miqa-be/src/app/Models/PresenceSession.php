<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresenceSession extends Model
{
    use HasUlids;

    protected $fillable = [
        'class_room_id',
        'created_by_user_id',
        'session_name',
        'session_type',
        'start_at',
        'end_at',
        'gps_latitude',
        'gps_longitude',
        'gps_radius_meters',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
        'gps_radius_meters' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function qrTokens(): HasMany
    {
        return $this->hasMany(PresenceQrToken::class);
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }
}
