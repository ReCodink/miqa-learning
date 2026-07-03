<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presence extends Model
{
    use HasUlids;

    protected $fillable = [
        'presence_session_id',
        'user_id',
        'checked_in_at',
        'gps_latitude',
        'gps_longitude',
        'is_within_geofence',
        'ip_address',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'is_within_geofence' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PresenceSession::class, 'presence_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
