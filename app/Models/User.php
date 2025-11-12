<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        // Basic Info
        'name',
        'display_name',
        'email',
        'phone',
        'address',
        'location',
        'bio',
        'interests',
        'password',
        'profile_image',
        
        // Account Info
        'role',
        'status',
        
        // Suspension Info
        'suspended_until',
        'suspension_reason',
        'suspension_level',
        
        // Location Info
        'region',
        'province',
        'city',
        'barangay',
        
        // Notification Settings
        'sms_notifications_enabled',
        'sms_notification_types',
        
        // Security & Login
        'is_locked',
        'locked_until',
        'failed_login_count',
        'last_failed_login',
        
        // Two-Factor Authentication
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_enabled',
        'two_factor_enabled_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'sms_notifications_enabled' => 'boolean',
        'sms_notification_types' => 'array',
        'interests' => 'array',
        'is_locked' => 'boolean',
        'locked_until' => 'datetime',
        'suspended_until' => 'datetime',
        'last_failed_login' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'two_factor_enabled_at' => 'datetime',
    ];

    public function charities(){ return $this->hasMany(Charity::class, 'owner_id'); }
    public function charity(){ return $this->hasOne(Charity::class, 'owner_id'); }
    public function donations(){ return $this->hasMany(Donation::class, 'donor_id'); }
    public function recurringDonations(){ return $this->hasMany(RecurringDonation::class); }
    public function charityFollows(){ return $this->hasMany(CharityFollow::class, 'donor_id'); }
    public function savedItems(){ return $this->hasMany(SavedItem::class); }
    public function supportTickets(){ return $this->hasMany(SupportTicket::class); }
    public function sentMessages(){ return $this->hasMany(Message::class, 'sender_id'); }
    public function receivedMessages(){ return $this->hasMany(Message::class, 'receiver_id'); }
    public function notifications(){ return $this->hasMany(Notification::class); }
    public function unreadNotifications(){ return $this->hasMany(Notification::class)->where('read', false); }
    public function submittedReports(){ return $this->hasMany(Report::class, 'reporter_id'); }
    public function reviewedReports(){ return $this->hasMany(Report::class, 'reviewed_by'); }
    public function campaignComments(){ return $this->hasMany(CampaignComment::class); }
    public function reports(){ return $this->morphMany(Report::class, 'reported_entity'); }
    public function donorProfile(){ return $this->hasOne(DonorProfile::class); }
    public function followedCharities(){ return $this->belongsToMany(Charity::class, 'charity_follows', 'donor_id', 'charity_id')->withTimestamps(); }
}
