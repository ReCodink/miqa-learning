<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubject extends Model
{
    use HasFactory, HasUlids;
    protected $fillable = [
        'class_room_id',
        'subject_id',
    ];

    protected static function booted()
    {
        static::creating(function ($classSubject) {
            $latestRecord = static::latest('created_at')->first();

            if ($latestRecord && $latestRecord->code) {
                $latestNumber = (int) str_replace('CS-', '', $latestRecord->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1;
            }

            $classSubject->code = 'CS-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
