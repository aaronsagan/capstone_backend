<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'email',
        'push',
        'sms',
        'frequency',
    ];

    protected $casts = [
        'email' => 'boolean',
        'push' => 'boolean',
        'sms' => 'boolean',
    ];

    /**
     * Get the user that owns the preference
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Default categories
     */
    public static function getDefaultCategories()
    {
        return [
            'donations' => ['email' => true, 'push' => true, 'sms' => false, 'frequency' => 'instant'],
            'campaigns' => ['email' => true, 'push' => false, 'sms' => false, 'frequency' => 'daily'],
            'charities' => ['email' => true, 'push' => true, 'sms' => false, 'frequency' => 'instant'],
            'support' => ['email' => true, 'push' => true, 'sms' => false, 'frequency' => 'instant'],
            'security' => ['email' => true, 'push' => true, 'sms' => true, 'frequency' => 'instant'],
            'marketing' => ['email' => false, 'push' => false, 'sms' => false, 'frequency' => 'weekly'],
        ];
    }
}
