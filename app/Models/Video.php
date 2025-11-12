<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    protected $fillable = [
        'campaign_id',
        'update_post_id',
        'user_id',
        'original_filename',
        'filename',
        'path',
        'mime',
        'size',
        'duration',
        'width',
        'height',
        'thumbnail_path',
        'status',
    ];

    protected $casts = [
        'size' => 'integer',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['url', 'thumbnail_url', 'stream_url'];

    /**
     * Get the campaign that owns the video
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user who uploaded the video
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the update post that owns the video
     */
    public function updatePost()
    {
        return $this->belongsTo(\App\Models\Update::class, 'update_post_id');
    }

    /**
     * Get the parent entity (campaign or update)
     */
    public function getParentTypeAttribute()
    {
        if ($this->campaign_id) {
            return 'campaign';
        } elseif ($this->update_post_id) {
            return 'update';
        }
        return null;
    }

    /**
     * Get the parent entity
     */
    public function getParentAttribute()
    {
        if ($this->campaign_id) {
            return $this->campaign;
        } elseif ($this->update_post_id) {
            return $this->updatePost;
        }
        return null;
    }

    /**
     * Get the URL attribute for the video
     */
    public function getUrlAttribute()
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    /**
     * Get the thumbnail URL attribute
     */
    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    /**
     * Get the stream URL attribute
     */
    public function getStreamUrlAttribute()
    {
        return route('api.videos.stream', $this->id);
    }

    /**
     * Check if video is ready for playback
     */
    public function isReady()
    {
        return $this->status === 'ready';
    }

    /**
     * Check if video is still processing
     */
    public function isProcessing()
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Format file size in human-readable format
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Format duration in human-readable format (MM:SS)
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) {
            return '00:00';
        }
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
