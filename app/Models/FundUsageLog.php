<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundUsageLog extends Model
{
    protected $fillable = [
        'charity_id',
        'campaign_id',
        'amount',
        'category',          // supplies|staffing|transport|operations|other
        'description',
        'spent_at',
        'attachment_path',
        'is_verified',
        'receipt_number',
    ];

    /**
     * Boot method to add model-level validation
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($fundLog) {
            if ($fundLog->amount < 1.00) {
                throw new \InvalidArgumentException('Fund usage amount must be at least 1 peso.');
            }
        });
    }

    protected $casts = [
        'amount'   => 'decimal:2',
        'spent_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    // Relationships
    public function charity()
    {
        return $this->belongsTo(Charity::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
