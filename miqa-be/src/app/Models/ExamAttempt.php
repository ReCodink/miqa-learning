<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAttempt extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'subject_exam_id',
        'total_attempts',
        'is_completed',
        'total_questions',
        'answered_questions',
        'total_points',
        'points_earned',
        'has_passed',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'has_passed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'score_percentage',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subjectExam(): BelongsTo
    {
        return $this->belongsTo(SubjectExam::class, 'subject_exam_id');
    }

    /**
     * Calculate score percentage
     */
    public function getScorePercentageAttribute(): float
    {
        if ($this->total_points > 0) {
            return round(($this->points_earned / $this->total_points) * 100, 2);
        }
        return 0.0;
    }
}
