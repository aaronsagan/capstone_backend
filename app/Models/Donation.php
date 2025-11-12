<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'donor_id','donor_name','donor_email','charity_id','campaign_id','amount','purpose','is_anonymous',
        'status','proof_path','proof_type','channel_used','reference_number','message','external_ref','receipt_no','donated_at',
        'is_recurring','recurring_type','recurring_end_date','next_donation_date','parent_donation_id',
        'is_refunded','refunded_at'
    ];

    /**
     * Boot method to add model-level validation and auto-update campaign totals
     */
    protected static function boot()
    {
        parent::boot();

        // Validate minimum amount
        static::saving(function ($donation) {
            if ($donation->amount < 1.00) {
                throw new \InvalidArgumentException('Donation amount must be at least 1 peso.');
            }
        });

        // After creating a donation, update campaign and charity totals if it's completed
        static::created(function ($donation) {
            if ($donation->status === 'completed') {
                if ($donation->campaign_id) {
                    self::updateCampaignTotals($donation->campaign_id);
                }
                if ($donation->charity_id) {
                    self::updateCharityTotals($donation->charity_id);
                }
            }
        });

        // After updating a donation, recalculate if status, amount, campaign, charity or refund changed
        static::updated(function ($donation) {
            $dirtyFields = $donation->getDirty();
            
            // Check if relevant fields changed
            $shouldUpdate = isset($dirtyFields['status']) || 
                           isset($dirtyFields['amount']) || 
                           isset($dirtyFields['campaign_id']) ||
                           isset($dirtyFields['charity_id']) ||
                           isset($dirtyFields['is_refunded']);
            
            if ($shouldUpdate) {
                // Update old campaign if campaign_id changed
                if (isset($dirtyFields['campaign_id']) && $donation->getOriginal('campaign_id')) {
                    self::updateCampaignTotals($donation->getOriginal('campaign_id'));
                }
                
                // Update current campaign
                if ($donation->campaign_id) {
                    self::updateCampaignTotals($donation->campaign_id);
                }

                // Update old charity if charity_id changed
                if (isset($dirtyFields['charity_id']) && $donation->getOriginal('charity_id')) {
                    self::updateCharityTotals($donation->getOriginal('charity_id'));
                }
                
                // Update current charity
                if ($donation->charity_id) {
                    self::updateCharityTotals($donation->charity_id);
                }
            }
        });

        // After deleting a donation, update campaign and charity totals
        static::deleted(function ($donation) {
            if ($donation->campaign_id) {
                self::updateCampaignTotals($donation->campaign_id);
            }
            if ($donation->charity_id) {
                self::updateCharityTotals($donation->charity_id);
            }
        });
    }

    /**
     * Update campaign's total_donations_received and donors_count
     */
    protected static function updateCampaignTotals($campaignId)
    {
        $campaign = Campaign::find($campaignId);
        
        if (!$campaign) {
            return;
        }

        // Calculate total from completed donations only, excluding refunded donations
        // where('status', 'completed') already excludes 'refunded' status
        // where('is_refunded', false) provides extra safety if status update failed
        $totals = self::where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->selectRaw('SUM(amount) as total, COUNT(DISTINCT donor_id) as donors')
            ->first();

        $campaign->total_donations_received = $totals->total ?? 0;
        $campaign->donors_count = $totals->donors ?? 0;
        
        // Use timestamps(false) to avoid updating updated_at
        $campaign->timestamps = false;
        $campaign->save();
        $campaign->timestamps = true;
    }

    /**
     * Update charity's total_donations_received and donors_count
     */
    protected static function updateCharityTotals($charityId)
    {
        $charity = Charity::find($charityId);
        
        if (!$charity) {
            return;
        }

        // Calculate total from completed donations only, excluding refunded donations
        // where('status', 'completed') already excludes 'refunded' status
        // where('is_refunded', false) provides extra safety if status update failed
        $totals = self::where('charity_id', $charityId)
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->selectRaw('SUM(amount) as total, COUNT(DISTINCT donor_id) as donors')
            ->first();

        $charity->total_donations_received = $totals->total ?? 0;
        $charity->donors_count = $totals->donors ?? 0;
        
        // Use timestamps(false) to avoid updating updated_at
        $charity->timestamps = false;
        $charity->save();
        $charity->timestamps = true;
    }

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_recurring' => 'boolean',
        'is_refunded'  => 'boolean',
        'donated_at'   => 'datetime',
        'refunded_at'  => 'datetime',
        'next_donation_date' => 'datetime',
        'recurring_end_date' => 'datetime',
        'amount'       => 'float',
    ];

    public function donor(){ return $this->belongsTo(User::class,'donor_id'); }
    public function charity(){ return $this->belongsTo(Charity::class); }
    public function campaign(){ return $this->belongsTo(Campaign::class); }
    public function parentDonation(){ return $this->belongsTo(Donation::class, 'parent_donation_id'); }
    public function recurringDonations(){ return $this->hasMany(Donation::class, 'parent_donation_id'); }
    public function refundRequest(){ return $this->hasOne(RefundRequest::class); }
}
