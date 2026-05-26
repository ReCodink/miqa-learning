<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'subject_exam_id' => $this->subject_exam_id,
            'total_attempts' => $this->total_attempts,
            'is_completed' => $this->is_completed,
            'total_questions' => $this->total_questions,
            'answered_questions' => $this->answered_questions,
            'total_points' => $this->total_points,
            'points_earned' => $this->points_earned,
            'has_passed' => $this->has_passed,
            'score_percentage' => $this->score_percentage,
            'completion_percentage' => $this->total_questions > 0
                ? round(($this->answered_questions / $this->total_questions) * 100, 2)
                : 0,
            'completed_at' => $this->completed_at?->toISOString(),
            'student' => new UserResource($this->whenLoaded('student')),
            'subject_exam' => new SubjectExamResource($this->whenLoaded('subjectExam')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
