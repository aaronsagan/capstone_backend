<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorMilestoneResource extends JsonResource
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
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon ?? 'Award',
            'is_achieved' => $this->isAchieved(),
            'achieved_at' => $this->achieved_at?->toISOString(),
            'achieved_at_formatted' => $this->achieved_at?->format('F j, Y'),
            'progress' => $this->progress,
            'meta' => $this->meta,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
