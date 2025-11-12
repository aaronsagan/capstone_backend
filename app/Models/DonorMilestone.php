<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'key',
        'title',
        'description',
        'icon',
        'achieved_at',
        'meta',
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Get the donor that owns the milestone.
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    /**
     * Check if milestone is achieved.
     */
    public function isAchieved(): bool
    {
        return !is_null($this->achieved_at);
    }

    /**
     * Get progress percentage from meta.
     */
    public function getProgressAttribute(): ?int
    {
        if (!$this->meta || !isset($this->meta['progress'])) {
            return null;
        }
        return min(100, max(0, (int) $this->meta['progress']));
    }

    /**
     * Scope: Only achieved milestones
     */
    public function scopeAchieved($query)
    {
        return $query->whereNotNull('achieved_at');
    }

    /**
     * Scope: Only unachieved milestones
     */
    public function scopeUnachieved($query)
    {
        return $query->whereNull('achieved_at');
    }

    /**
     * Scope: For specific donor
     */
    public function scopeForDonor($query, $donorId)
    {
        return $query->where('donor_id', $donorId);
    }
}
