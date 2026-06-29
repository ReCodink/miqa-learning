<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ClassRoom extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'photo',
        'protocol_id'
    ];

    protected static function booted()
    {
        static::creating(function ($classRoom) {
            // Menggunakan created_at karena ID sudah berupa string ULID
            $latestClassRoom = static::latest('created_at')->first();

            if ($latestClassRoom && $latestClassRoom->code) {
                $latestNumber = (int) str_replace('CR-', '', $latestClassRoom->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $classRoom->code = 'CR-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return url(Storage::url($value));
    }

    /**
     * Relasi Tunggal (Utama untuk Resource & Repository)
     */
    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocols::class, 'protocol_id');
    }

    /**
     * Relasi Jamak (Alias untuk menjaga kompatibilitas kode lama)
     */
    public function protocols(): BelongsTo
    {
        return $this->belongsTo(Protocols::class, 'protocol_id');
    }

    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'class_room_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_room_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subjects',
            'class_room_id',
            'subject_id'
        )->withTimestamps();
    }

    public function presenceSessions(): HasMany
    {
        return $this->hasMany(PresenceSession::class, 'class_room_id');
    }
}
