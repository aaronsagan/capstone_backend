<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'old_email',
        'new_email',
        'token',
        'status',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user associated with the email change
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the email change request has expired
     */
    public function isExpired()
    {
        return now()->isAfter($this->expires_at);
    }
}
