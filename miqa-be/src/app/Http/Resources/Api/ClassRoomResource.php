<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassRoomResource extends JsonResource
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
            'photo' => $this->photo,
            'protocol_id' => $this->protocol_id,
            'protocol' => new ProtocolsResource($this->whenLoaded('protocol')),
            'class_students_count' => $this->whenCounted('classStudents'),
            'class_subjects_count' => $this->whenCounted('classSubjects'),
            'class_students' => ClassStudentResource::collection($this->whenLoaded('classStudents')),
            'class_subjects' => ClassSubjectResource::collection($this->whenLoaded('classSubjects')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
