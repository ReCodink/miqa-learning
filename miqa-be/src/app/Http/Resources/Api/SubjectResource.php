<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'about' => $this->about,
            'photo' => $this->photo,
            'content' => $this->content,
            'topic_id' => $this->topic_id,
            'teacher_id' => $this->teacher_id,
            'topic' => new TopicResource($this->whenLoaded('topic')),
            'teacher' => new UserResource($this->whenLoaded('teacher')),
            'subject_exams_count' => $this->whenCounted('subjectExams'),
            'class_subjects_count' => $this->whenCounted('classSubjects'),
            'subject_exams' => SubjectExamResource::collection($this->whenLoaded('subjectExams')),
            'class_subjects' => ClassSubjectResource::collection($this->whenLoaded('classSubjects')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}