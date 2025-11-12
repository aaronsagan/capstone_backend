<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Upload Configuration
    |--------------------------------------------------------------------------
    */

    'max_size_mb' => env('VIDEO_MAX_SIZE_MB', 50),
    'max_duration_sec' => env('VIDEO_MAX_DURATION_SEC', 300), // 5 minutes
    'allowed_extensions' => env('VIDEO_ALLOWED_EXT', 'mp4,webm,mov'),
    'max_per_campaign' => env('VIDEO_MAX_PER_CAMPAIGN', 10),
];
