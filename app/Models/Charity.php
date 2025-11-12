<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Charity extends Model
{
    protected $fillable = [
        'owner_id','name','acronym','legal_trading_name','reg_no','tax_id',
        'mission','vision','goals','website','services','description',
        'contact_email','contact_phone',
        'total_donations_received','donors_count','campaigns_count',
        'first_name','middle_initial','last_name',
        'primary_first_name','primary_middle_initial','primary_last_name','primary_position',
        'primary_email','primary_phone',
        'address','street_address','barangay','city','province','region','municipality','full_address','category',
        'operating_hours',
        'facebook_url','instagram_url','twitter_url','linkedin_url','youtube_url',
        'logo_path','cover_image',
        'verification_status','verified_at','verification_notes','rejection_reason',
        'status'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'total_donations_received' => 'decimal:2',
        'donors_count' => 'integer',
        'campaigns_count' => 'integer',
    ];

    /**
     * Recalculate and update donation totals from database
     * Excludes refunded donations from totals
     */
    public function recalculateTotals()
    {
        $totals = $this->donations()
            ->where('status', 'completed')
            ->where('is_refunded', false)
            ->selectRaw('SUM(amount) as total, COUNT(DISTINCT donor_id) as donors')
            ->first();

        $campaignsCount = $this->campaigns()
            ->whereIn('status', ['published', 'closed'])
            ->count();

        $this->total_donations_received = $totals->total ?? 0;
        $this->donors_count = $totals->donors ?? 0;
        $this->campaigns_count = $campaignsCount;
        $this->save();

        return $this;
    }

    public function owner(){ return $this->belongsTo(User::class,'owner_id'); }
    public function documents(){ return $this->hasMany(CharityDocument::class); }
    public function channels(){ return $this->hasMany(DonationChannel::class); }
    public function campaigns(){ return $this->hasMany(Campaign::class); }
    public function donations(){ return $this->hasMany(Donation::class); }
    public function fundUsageLogs(){ return $this->hasMany(FundUsageLog::class); }
    public function posts(){ return $this->hasMany(CharityPost::class); }
    public function followers(){ return $this->hasMany(CharityFollow::class); }
    public function activeFollowers(){ return $this->hasMany(CharityFollow::class)->where('is_following', true); }
    public function volunteers(){ return $this->hasMany(Volunteer::class); }
    public function activeVolunteers(){ return $this->hasMany(Volunteer::class)->where('status', 'active'); }
    public function officers(){ return $this->hasMany(CharityOfficer::class); }
    public function activeOfficers(){ return $this->hasMany(CharityOfficer::class)->where('is_active', true)->orderBy('display_order'); }
    public function reports(){ return $this->morphMany(Report::class, 'reported_entity'); }
}
