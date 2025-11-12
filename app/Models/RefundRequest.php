<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'donation_id',
        'user_id',
        'charity_id',
        'reason',
        'proof_url',
        'status',
        'charity_notes',
        'charity_response',
        'reviewed_by',
        'reviewed_at',
        'refund_amount',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['requested_at'];

    /**
     * Get requested_at (alias for created_at)
     */
    public function getRequestedAtAttribute()
    {
        return $this->created_at;
    }

    /**
     * Get the donation
     */
    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }

    /**
     * Get the donor (requester)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for user (donor)
     */
    public function donor()
    {
        return $this->user();
    }

    /**
     * Get the charity
     */
    public function charity()
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the reviewer (charity admin)
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending refunds
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved refunds
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for denied refunds
     */
    public function scopeDenied($query)
    {
        return $query->where('status', 'denied');
    }
}
