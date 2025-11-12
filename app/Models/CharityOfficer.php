<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityOfficer extends Model
{
    use HasFactory;

    protected $fillable = [
        'charity_id',
        'name',
        'position',
        'email',
        'phone',
        'profile_image_path',
        'bio',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the charity that owns this officer
     */
    public function charity()
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Scope for active officers only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by display_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at');
    }
}
