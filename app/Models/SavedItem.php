<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'savable_id',
        'savable_type',
        'reminded_at',
    ];

    protected $casts = [
        'reminded_at' => 'datetime',
    ];

    /**
     * Get the user who saved this item
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the savable model (Campaign, Charity, or Post)
     */
    public function savable()
    {
        return $this->morphTo();
    }

    /**
     * Legacy relationship for backwards compatibility
     * @deprecated Use savable() instead
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'savable_id')
            ->where('savable_type', Campaign::class);
    }
}
