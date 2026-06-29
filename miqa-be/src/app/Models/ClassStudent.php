<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClassStudent extends Model
{
    use HasFactory, HasUlids;
    protected $fillable = [
        'student_id',
        'class_room_id',
        'has_passed',
        'rapport',
    ];

    protected $casts = [
        'has_passed' => 'boolean',
    ];

    /**
     * Handle model event untuk mengisi atribut secara otomatis
     */
    protected static function booted()
    {
        static::creating(function ($classStudent) {
            $latestRecord = static::latest('created_at')->first();

            if ($latestRecord && $latestRecord->code) {
                $latestNumber = (int) str_replace('CS-', '', $latestRecord->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $classStudent->code = 'CST-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    public function getRapportAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        return url(Storage::url($value));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }
}
