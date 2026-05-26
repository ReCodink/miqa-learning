<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'gender',
        // 'code' sengaja tidak dimasukkan agar aman dari manipulasi mass-assignment
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Handle model event untuk mengisi atribut secara otomatis
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $currentYear = date('Y');

            // Mengambil kode terbesar murni dari string kolom 'code'
            $latestUser = static::where('code', 'like', 'USR-' . $currentYear . '-' . '%')
                ->orderBy('code', 'desc')
                ->first();

            if ($latestUser) {
                // Mengambil angka murni di bagian belakang string
                $latestNumber = (int) str_replace("USR-{$currentYear}-", '', $latestUser->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1;
            }

            // Pad dengan 4 digit (0001) agar urutan string desc di database tetap konsisten up to 9999
            $user->code = 'USR-' . $currentYear . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Accessor untuk memformat URL foto profil
     */
    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Memastikan jika value sudah berupa URL (misal link eksternal/faker), tidak di-wrap ulang
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return url(Storage::url($value));
    }

    /**
     * Akademik & Exam Relationships
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'teacher_id');
    }

    public function classStudents(): HasMany
    {
        return $this->hasMany(ClassStudent::class, 'student_id');
    }

    public function questionAnswers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class, 'student_id');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'student_id');
    }

    /**
     * Presence & Attendance Relationships
     */
    public function generatedQrTokens(): HasMany
    {
        return $this->hasMany(PresenceQrToken::class, 'created_by_user_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(Presence::class, 'user_id');
    }

    public function registeredDevices(): HasMany
    {
        return $this->hasMany(PresenceDevice::class, 'user_id');
    }

    public function securityFlags(): HasMany
    {
        return $this->hasMany(PresenceSecurityFlag::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PresenceAuditLog::class, 'user_id');
    }

    public function createdSessions(): HasMany
    {
        return $this->hasMany(PresenceSession::class, 'created_by_user_id');
    }
}
