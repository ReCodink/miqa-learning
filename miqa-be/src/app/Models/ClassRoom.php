<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids; // Wajib di-import
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ClassRoom extends Model
{
    // 1. Tambahkan HasUlids di sini
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'photo',
        'protocol_id'
    ];

    /**
     * Handle model event untuk mengisi atribut code secara otomatis
     */
    protected static function booted()
    {
        static::creating(function ($classRoom) {
            $latestClassRoom = static::latest('id')->first();

            if ($latestClassRoom && $latestClassRoom->code) {
                $latestNumber = (int) str_replace('CR-', '', $latestClassRoom->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1; // Mulai dari 1 jika belum ada record sama sekali
            }

            $classRoom->code = 'CR-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Accessor untuk memformat URL foto ruangan kelas
     */
    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Jika data berupa URL eksternal (misal dari Faker/Seeder), kembalikan langsung nilainya
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return url(Storage::url($value));
    }

    public function protocol(): BelongsTo
    {
        return $this->belongsTo(Protocols::class, 'protocol_id');
    }

    /**
     * Relasi ke ClassStudent
     */
    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'class_room_id');
    }

    /**
     * Relasi ke ClassSubject
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_room_id');
    }

    /**
     * Get all attendance sessions for this classroom
     */
    public function presenceSessions(): HasMany
    {
        return $this->hasMany(PresenceSession::class, 'class_room_id');
    }
}
