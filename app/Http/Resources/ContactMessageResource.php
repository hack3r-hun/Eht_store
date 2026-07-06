<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
            'replies' => ContactMessageReplyResource::collection($this->whenLoaded('replies')),
        ];
    }
}
