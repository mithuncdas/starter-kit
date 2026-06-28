<?php

namespace App\Http\Resources\Admin;

use Chronicle\Entry\Entry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Entry
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actor' => [
                'type' => $this->actor_type,
                'id' => $this->actor_id,
            ],
            'subject' => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
            ],
            'metadata' => $this->metadata,
            'diff' => $this->diff,
            'tags' => $this->tags,
            'context' => $this->context,
            'correlation_id' => $this->correlation_id,
            'created_at' => $this->created_at?->format('d/m/Y h:i A'),
        ];
    }
}
