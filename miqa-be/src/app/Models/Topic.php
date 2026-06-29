<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Topic extends Model
{
    use HasFactory, HasUlids;
    protected $fillable = [
        'name',
        'about',
        'photo',
    ];

    protected static function booted()
    {
        static::creating(function ($subject) {
            // Mencari record subject terakhir berdasarkan ID ULID untuk menentukan urutan berikutnya
            $latestSubject = static::latest('created_at')->first();

            if ($latestSubject && $latestSubject->code) {
                $latestNumber = (int) str_replace('TPC-', '', $latestSubject->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1; // Mulai dari 1 jika belum ada record sama sekali
            }

            $subject->code = 'TPC-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return url(Storage::url($value));
    }


    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
