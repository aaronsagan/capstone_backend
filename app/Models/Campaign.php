<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    /**
     * Boot method for model-level validation
     */
    protected static function boot()
    {
        parent::boot();

        // Validate before saving
        static::saving(function ($campaign) {
            // Validate recurring campaign data
            if ($campaign->is_recurring) {
                if (empty($campaign->recurrence_type)) {
                    throw new \InvalidArgumentException('Recurring campaigns must have a recurrence_type');
                }
                if (empty($campaign->recurrence_start_date)) {
                    throw new \InvalidArgumentException('Recurring campaigns must have a recurrence_start_date');
                }
            }

            // Validate target amount is positive
            if ($campaign->target_amount !== null && $campaign->target_amount < 0) {
                throw new \InvalidArgumentException('Target amount cannot be negative');
            }

            // Validate total_donations_received is not negative
            if ($campaign->total_donations_received < 0) {
                throw new \InvalidArgumentException('Total donations received cannot be negative');
            }

            // Validate donors_count is not negative
            if ($campaign->donors_count < 0) {
                throw new \InvalidArgumentException('Donors count cannot be negative');
            }

            // Validate child campaign has parent
            if ($campaign->parent_campaign_id !== null) {
                if ($campaign->parent_campaign_id == $campaign->id) {
                    throw new \InvalidArgumentException('Campaign cannot be its own parent');
                }
            }

            // Validate dates
            if ($campaign->end_date && $campaign->start_date) {
                if ($campaign->end_date < $campaign->start_date) {
                    throw new \InvalidArgumentException('End date must be after start date');
                }
            }

            if ($campaign->recurrence_end_date && $campaign->recurrence_start_date) {
                if ($campaign->recurrence_end_date < $campaign->recurrence_start_date) {
                    throw new \InvalidArgumentException('Recurrence end date must be after recurrence start date');
                }
            }
        });
    }

    protected $fillable = [
        'charity_id',
        'category_id',
        'title',
        'description',
        'problem',
        'solution',
        'expected_outcome',
        'beneficiary',
        'beneficiary_category',
        'region',
        'province',
        'city',
        'barangay',
        'target_amount',
        'total_donations_received',
        'donors_count',
        'deadline_at',
        'cover_image_path',
        'status',
        'donation_type',
        'campaign_type',
        'start_date',
        'end_date',
        'is_recurring',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_start_date',
        'recurrence_end_date',
        'next_occurrence_date',
        'auto_publish',
        'parent_campaign_id',
        'occurrence_number',
        'requires_completion_report',
        'completion_report_submitted',
        'completion_report_submitted_at',
        'has_fund_usage_logs',
        'ended_at',
        'is_volunteer_based',
        'requires_target_amount',
        'volunteers_needed',
        'volunteer_description',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'target_amount' => 'decimal:2',
        'total_donations_received' => 'decimal:2',
        'donors_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'beneficiary_category' => 'array',
        'is_recurring' => 'boolean',
        'recurrence_start_date' => 'date',
        'recurrence_end_date' => 'date',
        'next_occurrence_date' => 'date',
        'auto_publish' => 'boolean',
        'recurrence_interval' => 'integer',
        'occurrence_number' => 'integer',
        'requires_completion_report' => 'boolean',
        'completion_report_submitted' => 'boolean',
        'completion_report_submitted_at' => 'datetime',
        'has_fund_usage_logs' => 'boolean',
        'ended_at' => 'datetime',
        'is_volunteer_based' => 'boolean',
        'requires_target_amount' => 'boolean',
        'volunteers_needed' => 'integer',
    ];

    protected $appends = ['current_amount', 'display_status'];

    // Relationships
    public function charity()
    {
        return $this->belongsTo(Charity::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function videos()
    {
        return $this->hasMany(\App\Models\Video::class);
    }

    public function volunteers()
    {
        return $this->hasMany(\App\Models\CampaignVolunteer::class);
    }

    public function approvedVolunteers()
    {
        return $this->hasMany(\App\Models\CampaignVolunteer::class)->where('status', 'approved');
    }

    // Accessors
    public function getCurrentAmountAttribute()
    {
        // Use the database column for better performance
        // If column doesn't exist yet (during migration), calculate dynamically
        if (isset($this->attributes['total_donations_received'])) {
            return (float) $this->attributes['total_donations_received'];
        }
        
        // Fallback to dynamic calculation
        return (float) $this->donations()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Manually recalculate and update the total from donations
     * Useful for data integrity checks or fixing discrepancies
     * Excludes refunded donations from totals
     */
    public function recalculateTotals()
    {
        $totals = $this->donations()
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->selectRaw('SUM(amount) as total, COUNT(DISTINCT donor_id) as donors')
            ->first();

        $this->total_donations_received = $totals->total ?? 0;
        $this->donors_count = $totals->donors ?? 0;
        $this->save();

        return $this;
    }

    public function getDisplayStatusAttribute()
    {
        // Map database status to display status
        switch ($this->status) {
            case 'draft':
            case 'paused':
                return 'pending';
            case 'published':
                return 'active';
            case 'closed':
                return 'completed';
            default:
                return $this->status;
        }
    }

    public function fundUsageLogs()
    {
        return $this->hasMany(FundUsageLog::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(CampaignComment::class);
    }

    public function approvedComments()
    {
        return $this->hasMany(CampaignComment::class)->where('status', 'approved');
    }

    public function donationChannels()
    {
        return $this->belongsToMany(DonationChannel::class, 'campaign_donation_channel')
            ->withTimestamps();
    }

    public function campaignUpdates()
    {
        return $this->hasMany(CampaignUpdate::class);
    }

    // Recurring campaign relationships
    public function parentCampaign()
    {
        return $this->belongsTo(Campaign::class, 'parent_campaign_id');
    }

    public function childCampaigns()
    {
        return $this->hasMany(Campaign::class, 'parent_campaign_id');
    }

    // Scopes for recurring campaigns
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeParentCampaigns($query)
    {
        return $query->whereNull('parent_campaign_id');
    }

    public function scopeChildCampaigns($query)
    {
        return $query->whereNotNull('parent_campaign_id');
    }

    // Campaign completion methods
    public function isEnded()
    {
        return $this->ended_at !== null || 
               ($this->end_date && $this->end_date->isPast()) ||
               $this->status === 'closed';
    }

    public function hasReceivedDonations()
    {
        return $this->donations()->where('status', 'completed')->exists();
    }

    public function needsCompletionReport()
    {
        return $this->isEnded() && 
               $this->hasReceivedDonations() && 
               $this->requires_completion_report && 
               !$this->completion_report_submitted;
    }

    public function needsFundUsageLogs()
    {
        return $this->isEnded() && 
               $this->hasReceivedDonations() && 
               !$this->has_fund_usage_logs;
    }

    public function getTotalFundUsageAttribute()
    {
        return (float) $this->fundUsageLogs()->sum('amount');
    }

    public function getFundUsagePercentageAttribute()
    {
        $currentAmount = $this->current_amount;
        if ($currentAmount == 0) return 0;
        return ($this->total_fund_usage / $currentAmount) * 100;
    }

    public function scopeDueForRecurrence($query)
    {
        return $query->where('is_recurring', true)
            ->whereNotNull('next_occurrence_date')
            ->whereDate('next_occurrence_date', '<=', now())
            ->where(function($q) {
                $q->whereNull('recurrence_end_date')
                  ->orWhereDate('recurrence_end_date', '>=', now());
            });
    }
}
