<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonorDonationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwner = $request->user() && $request->user()->id === $this->donor_id;

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method ?? 'Unknown',
            'is_anonymous' => $this->is_anonymous ?? false,
            'message' => $this->message,
            'created_at' => $this->created_at->toISOString(),
            'created_at_formatted' => $this->created_at->format('F j, Y'),
            'created_at_human' => $this->created_at->diffForHumans(),
            
            // Campaign info
            'campaign' => $this->when($this->campaign, [
                'id' => $this->campaign?->id,
                'title' => $this->campaign?->title,
                'slug' => $this->campaign?->slug,
                'image_url' => $this->campaign?->cover_image_path 
                    ? url('storage/' . $this->campaign->cover_image_path)
                    : null,
                'charity' => [
                    'id' => $this->campaign?->charity?->id,
                    'name' => $this->campaign?->charity?->name,
                ],
            ]),
            
            // Charity info (if direct donation)
            'charity' => $this->when($this->charity && !$this->campaign, [
                'id' => $this->charity?->id,
                'name' => $this->charity?->name,
                'logo_url' => $this->charity?->logo_path
                    ? url('storage/' . $this->charity->logo_path)
                    : null,
            ]),
            
            // Receipt (only for owner)
            'receipt_url' => $isOwner && $this->receipt_path 
                ? url('storage/' . $this->receipt_path)
                : null,
                
            // Verification status details
            'verified_at' => $this->verified_at?->toISOString(),
            'verified_by' => $this->verifiedBy?->name,
        ];
    }
}
