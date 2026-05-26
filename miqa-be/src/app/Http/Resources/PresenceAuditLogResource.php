<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceAuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'presence_id' => $this->presence_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'action' => $this->action,
            'action_type' => $this->action_type,
            'action_details' => $this->action_details,
            'actor_user_id' => $this->actor_user_id,
            'actor_name' => $this->actor?->name,
            'actor_role' => $this->actor_role,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
