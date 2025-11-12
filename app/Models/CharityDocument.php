<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharityDocument extends Model
{
    protected $fillable = [
        'charity_id',
        'doc_type',
        'file_path',
        'sha256',
        'uploaded_by',
        'verification_status',
        'rejection_reason',
        'verified_at',
        'verified_by',
        'rejected_at',
        'can_resubmit_at',
        'expires',
        'expiry_date',
        'renewal_reminder_sent_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
        'can_resubmit_at' => 'datetime',
        'expiry_date' => 'date',
        'renewal_reminder_sent_at' => 'date',
        'expires' => 'boolean',
    ];

    protected $attributes = [
        'verification_status' => 'pending',
        'expires' => false,
    ];

    protected $appends = ['document_type', 'file_url', 'uploaded_at'];

    // Relationships
    public function charity()
    {
        return $this->belongsTo(Charity::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Accessor for document_type (for frontend compatibility)
    public function getDocumentTypeAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->doc_type));
    }

    // Accessor for file URL
    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return url('storage/' . $this->file_path);
        }
        return null;
    }

    // Accessor for uploaded_at (maps to created_at)
    public function getUploadedAtAttribute()
    {
        return $this->created_at;
    }

    /**
     * Check if document can be resubmitted
     */
    public function canResubmit(): bool
    {
        if ($this->verification_status !== 'rejected') {
            return false;
        }

        if (!$this->can_resubmit_at) {
            return true; // No restriction set
        }

        return now()->gte($this->can_resubmit_at);
    }

    /**
     * Get days until resubmission is allowed
     */
    public function getDaysUntilResubmission(): ?int
    {
        if (!$this->can_resubmit_at || $this->canResubmit()) {
            return null;
        }

        return now()->diffInDays($this->can_resubmit_at, false);
    }

    /**
     * Reject document with 3-5 day waiting period
     */
    public function reject(string $reason, ?int $waitDays = null): void
    {
        $waitDays = $waitDays ?? rand(3, 5); // Random 3-5 days if not specified

        $this->update([
            'verification_status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'can_resubmit_at' => now()->addDays($waitDays),
        ]);
    }

    /**
     * Approve document
     */
    public function approve(?int $verifiedBy = null): void
    {
        $this->update([
            'verification_status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
            'rejection_reason' => null,
            'rejected_at' => null,
            'can_resubmit_at' => null,
        ]);
    }
}
