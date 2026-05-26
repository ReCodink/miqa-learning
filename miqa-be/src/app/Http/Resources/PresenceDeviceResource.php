<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'device_fingerprint_hash' => $this->device_fingerprint_hash,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'os_name' => $this->os_name,
            'os_version' => $this->os_version,
            'app_version' => $this->app_version,
            'is_trusted' => $this->is_trusted,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
