<?php

namespace Whilesmart\Notifications\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'type' => $this->type,
            'level' => $this->level?->value,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->action_url,
            'action_label' => $this->action_label,
            'data' => $this->data ?? [],
            'metadata' => $this->metadata,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
