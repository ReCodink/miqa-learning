<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectExam extends Model
{
    use HasFactory;
    protected $fillable = [
        'subject_id',
        'name',
        'about',
        'total_points',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function examQuestions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'subject_exam_id');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'subject_exam_id');
    }

    /**
     * Calculate and update total points from all exam questions
     */
    public function calculateTotalPoints(): int
    {
        $totalPoints = $this->examQuestions()->sum('points');
        $this->update(['total_points' => $totalPoints]);
        return $totalPoints;
    }

    /**
     * Get total points, calculating if needed
     */
    public function getTotalPointsAttribute($value): int
    {
        // If total_points is 0 or null, calculate it
        if (!$value) {
            return $this->calculateTotalPoints();
        }
        return $value;
    }
}
