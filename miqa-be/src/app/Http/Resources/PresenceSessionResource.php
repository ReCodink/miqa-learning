<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_room_id' => $this->class_room_id,
            'session_name' => $this->session_name,
            'session_type' => $this->session_type,
            'scheduled_start_at' => $this->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $this->scheduled_end_at?->toIso8601String(),
            'actual_start_at' => $this->actual_start_at?->toIso8601String(),
            'actual_end_at' => $this->actual_end_at?->toIso8601String(),
            'gps_latitude' => $this->gps_latitude,
            'gps_longitude' => $this->gps_longitude,
            'gps_radius_meters' => $this->gps_radius_meters,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_by' => $this->createdBy?->name,
            'created_by_user_id' => $this->created_by_user_id,
            'qr_tokens_count' => $this->whenCounted('qrTokens'),
            'presences_count' => $this->whenCounted('presences'),
            'qr_tokens' => PresenceQrTokenResource::collection($this->whenLoaded('qrTokens')),
            'presences' => PresenceResource::collection($this->whenLoaded('presences')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
