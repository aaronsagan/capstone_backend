<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharityReactivationRequest extends Model
{
    protected $fillable = [
        'charity_id', 'email', 'status', 'requested_at', 'reviewed_at', 'reviewed_by', 'admin_notes'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function charity()
    {
        return $this->belongsTo(Charity::class);
    }
}
