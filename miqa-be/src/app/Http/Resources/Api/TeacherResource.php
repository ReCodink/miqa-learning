<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id'     => $this->id,
            'code'   => $this->code,
            'name'   => $this->name,
            'email'  => $this->email,
            'gender' => $this->gender,

            // CLEAN IMAGE PATH RESOLUTION:
            'photo'  => $this->photo
                ? (filter_var($this->photo, FILTER_VALIDATE_URL)
                    ? $this->photo
                    : asset('storage/' . $this->photo))
                : null,

            'subjects' => $this->whenLoaded('subjects'),
            'roles'    => $this->whenLoaded('roles'),
        ];
    }
}