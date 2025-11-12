<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RecurringDonation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'charity_id',
        'amount',
        'interval',
        'status',
        'next_charge_at',
        'last_charged_at',
        'started_at',
        'paused_at',
        'cancelled_at',
        'total_donations',
        'total_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'next_charge_at' => 'datetime',
        'last_charged_at' => 'datetime',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the user (donor) for this recurring donation
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the charity
     */
    public function charity()
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Calculate next charge date based on interval
     */
    public function calculateNextChargeDate()
    {
        $base = $this->last_charged_at ?? $this->started_at ?? now();
        
        return match($this->interval) {
            'weekly' => Carbon::parse($base)->addWeek(),
            'monthly' => Carbon::parse($base)->addMonth(),
            'quarterly' => Carbon::parse($base)->addMonths(3),
            'yearly' => Carbon::parse($base)->addYear(),
            default => Carbon::parse($base)->addMonth(),
        };
    }

    /**
     * Check if recurring donation is due
     */
    public function isDue()
    {
        return $this->status === 'active' 
            && $this->next_charge_at 
            && now()->gte($this->next_charge_at);
    }

    /**
     * Pause recurring donation
     */
    public function pause()
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);
    }

    /**
     * Resume recurring donation
     */
    public function resume()
    {
        $this->update([
            'status' => 'active',
            'paused_at' => null,
            'next_charge_at' => $this->calculateNextChargeDate(),
        ]);
    }

    /**
     * Cancel recurring donation
     */
    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
