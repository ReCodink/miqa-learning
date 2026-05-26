<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->presence_session_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'qr_token_id' => $this->qr_token_id,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'checked_out_at' => $this->checked_out_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'gps_latitude' => $this->gps_latitude,
            'gps_longitude' => $this->gps_longitude,
            'gps_distance_meters' => $this->gps_distance_meters,
            'is_within_geofence' => $this->is_within_geofence,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'device_fingerprint_json' => $this->device_fingerprint_json,
            'is_valid' => $this->is_valid,
            'has_unreviewed_flags' => $this->hasUnreviewedFlags(),
            'unreviewed_flags_count' => $this->unreviewedFlagsCount(),
            'highest_flag_severity' => $this->highestFlagSeverity(),
            'flags' => PresenceSecurityFlagResource::collection($this->securityFlags),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
