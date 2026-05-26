<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceQrTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'session_id' => $this->presence_session_id,
            'is_used' => $this->is_used,
            'is_revoked' => $this->is_revoked,
            'is_expired' => $this->isExpired(),
            'is_valid' => $this->isValid(),
            'generated_at' => $this->generated_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'used_at' => $this->used_at?->toIso8601String(),
            'used_by' => $this->usedBy?->name,
            'used_by_user_id' => $this->used_by_user_id,
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revoke_reason' => $this->revoke_reason,
            'created_by' => $this->createdBy?->name,
            'created_by_user_id' => $this->created_by_user_id,
        ];
    }
}
