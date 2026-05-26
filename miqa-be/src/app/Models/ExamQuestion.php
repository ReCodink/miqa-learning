<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamQuestion extends Model
{
    use HasFactory, HasUlids;
    protected $fillable = [
        'subject_exam_id',
        'name',
        'timer',
        'type',
        'points',
    ];

    protected static function booted()
    {
        static::created(function ($question) {
            $question->updateExamTotalPoints();
        });

        static::updated(function ($question) {
            $question->updateExamTotalPoints();
        });

        static::deleted(function ($question) {
            $question->updateExamTotalPoints();
        });
    }

    public function subjectExam(): BelongsTo
    {
        return $this->belongsTo(SubjectExam::class, 'subject_exam_id');
    }

    public function questionOptions(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'exam_question_id');
    }

    public function questionAnswers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class, 'exam_question_id');
    }

    /**
     * Update the total points for the associated exam
     */
    public function updateExamTotalPoints(): void
    {
        if ($this->subjectExam) {
            $this->subjectExam->calculateTotalPoints();
        }
    }
}
