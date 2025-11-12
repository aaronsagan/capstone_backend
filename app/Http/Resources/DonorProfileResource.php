<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwner = $request->user() && $request->user()->id === $this->id;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $isOwner ? $this->email : $this->maskEmail($this->email),
            'avatar_url' => $this->profile_image 
                ? url('storage/' . $this->profile_image)
                : null,
            'cover_url' => $this->cover_image
                ? url('storage/' . $this->cover_image)
                : null,
            'bio' => $this->donorProfile->bio ?? null,
            'location' => $this->donorProfile->full_address ?? $this->address ?? null,
            'member_since' => $this->created_at->format('F Y'),
            'member_since_date' => $this->created_at->toDateString(),
            
            // Statistics
            'total_donated' => $this->getTotalDonated(),
            'campaigns_supported_count' => $this->getCampaignsSupportedCount(),
            'recent_donations_count' => $this->getRecentDonationsCount(),
            'liked_campaigns_count' => $this->getLikedCampaignsCount(),
            
            // Privacy
            'is_owner' => $isOwner,
            'email_visible' => $isOwner || $this->email_public === true,
            
            // Additional info
            'phone' => $isOwner ? $this->phone : null,
            'date_of_birth' => $isOwner && $this->donorProfile ? $this->donorProfile->date_of_birth : null,
            'gender' => $this->donorProfile->gender ?? null,
            'cause_preferences' => $this->donorProfile->cause_preferences ?? [],
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Mask email for privacy
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            return '***@' . $domain;
        }
        
        return substr($username, 0, 2) . '***@' . $domain;
    }

    /**
     * Get total donated amount (verified donations only, excluding refunded)
     */
    private function getTotalDonated(): float
    {
        return (float) $this->donations()
            ->whereIn('status', ['completed', 'auto_verified', 'manual_verified'])
            ->where('is_refunded', false)
            ->sum('amount');
    }

    /**
     * Get count of unique campaigns supported (excluding refunded)
     */
    private function getCampaignsSupportedCount(): int
    {
        return (int) $this->donations()
            ->whereIn('status', ['completed', 'auto_verified', 'manual_verified'])
            ->where('is_refunded', false)
            ->distinct('campaign_id')
            ->count('campaign_id');
    }

    /**
     * Get recent donations count (last 30 days, excluding refunded)
     */
    private function getRecentDonationsCount(): int
    {
        return (int) $this->donations()
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['completed', 'auto_verified', 'manual_verified'])
            ->where('is_refunded', false)
            ->count();
    }

    /**
     * Get liked campaigns count
     */
    private function getLikedCampaignsCount(): int
    {
        // Check if relationship is loaded
        if ($this->relationLoaded('savedItems')) {
            return $this->savedItems
                ->where('item_type', 'App\\Models\\Campaign')
                ->count();
        }
        
        // Otherwise query it
        return (int) $this->savedItems()
            ->where('item_type', 'App\\Models\\Campaign')
            ->count();
    }
}
