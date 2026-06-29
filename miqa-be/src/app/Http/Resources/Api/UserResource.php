<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
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
            'email' => $this->email,

            // Fix: Return the absolute public URL instead of the raw storage path
            'photo' => $this->photo ? Storage::disk('public')->url($this->photo) : null,

            'gender' => $this->gender,

            // Excellent practice: keeps the payload light unless explicitly requested
            'roles' => $this->when($this->relationLoaded('roles'), function () {
                return $this->roles->pluck('name');
            }),

            // Alternative: use ->toIso8601String() for a reliable, standard datetime presentation
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
