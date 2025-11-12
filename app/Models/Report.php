<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reporter_id',
        'reporter_role',
        'reported_entity_type',
        'reported_entity_id',
        'target_type',
        'target_id',
        'report_type',
        'severity',
        'reason',
        'description',
        'details',
        'evidence_path',
        'status',
        'penalty_days',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'action_taken',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Polymorphic relationship to get the reported entity (old system)
    public function reportedEntity()
    {
        return $this->morphTo('reported_entity', 'reported_entity_type', 'reported_entity_id');
    }

    // New target relationship
    public function target()
    {
        if ($this->target_type === 'user') {
            return $this->belongsTo(User::class, 'target_id');
        } elseif ($this->target_type === 'charity') {
            return $this->belongsTo(Charity::class, 'target_id');
        } elseif ($this->target_type === 'campaign') {
            return $this->belongsTo(Campaign::class, 'target_id');
        } elseif ($this->target_type === 'donation') {
            return $this->belongsTo(Donation::class, 'target_id');
        }
        return null;
    }

    // Helper to get target user for suspension (user directly or charity owner)
    public function getTargetUser()
    {
        if ($this->target_type === 'user') {
            return User::find($this->target_id);
        } elseif ($this->target_type === 'charity') {
            $charity = Charity::find($this->target_id);
            return $charity ? $charity->owner : null;
        }
        return null;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
