<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceQrToken extends Model
{
    use HasUlids;

    protected $fillable = [
        'presence_session_id',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(PresenceSession::class, 'presence_session_id');
    }

    /**
     * Scope untuk mengecek apakah token QR masih valid/belum expired
     */
    public function scopeIsUnexpired($query)
    {
        return $query->where('expires_at', '>', now());
    }
}
