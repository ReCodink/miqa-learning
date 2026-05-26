<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Subject extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'tagline',
        'photo',
        'content',
        'about',
        'topic_id',
        'teacher_id',
        // 'code' sengaja tidak dimasukkan ke fillable agar aman dari manipulasi input massal
    ];

    /**
     * Handle model event untuk mengisi atribut secara otomatis
     */
    protected static function booted()
    {
        static::creating(function ($subject) {
            // Mencari record subject terakhir berdasarkan ID ULID untuk menentukan urutan berikutnya
            $latestSubject = static::latest('id')->first();

            if ($latestSubject && $latestSubject->code) {
                $latestNumber = (int) str_replace('SBJ-', '', $latestSubject->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1; // Mulai dari 1 jika belum ada record sama sekali
            }

            $subject->code = 'SBJ-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Accessor untuk memformat URL foto subject
     */
    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Jika data berupa URL eksternal (misal dari Faker), langsung kembalikan nilainya
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return url(Storage::url($value));
    }

    /**
     * Accessor untuk memformat URL file content (misal: silabus PDF/E-book)
     */
    public function getContentAttribute($value)
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
     * Relasi ke Topic (Setiap subject berada di bawah 1 topik)
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    /**
     * Relasi ke User / Pengajar (Setiap subject diampu oleh 1 user/teacher)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relasi ke ClassSubject (Pivot/penghubung subject ke kelas-kelas)
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'subject_id');
    }

    /**
     * Relasi ke SubjectExam (Satu subject bisa memiliki banyak ujian)
     */
    public function subjectExams(): HasMany
    {
        return $this->hasMany(SubjectExam::class, 'subject_id');
    }
}
