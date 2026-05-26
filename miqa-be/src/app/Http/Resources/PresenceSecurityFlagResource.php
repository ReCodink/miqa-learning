<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresenceSecurityFlagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'presence_id' => $this->presence_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'flag_type' => $this->flag_type,
            'flag_severity' => $this->flag_severity,
            'flag_description' => $this->flag_description,
            'flag_metadata' => $this->flag_metadata,
            'is_reviewed' => $this->is_reviewed,
            'reviewed_by' => $this->reviewedBy?->name,
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'review_notes' => $this->review_notes,
            'action_taken' => $this->action_taken,
            'severity_rank' => $this->getSeverityRank(),
            'presence' => new PresenceResource($this->presence),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
