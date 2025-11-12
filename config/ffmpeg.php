<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    */

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),
    
    // For Windows, you might need full paths like:
    // 'ffmpeg_path' => 'C:\\ffmpeg\\bin\\ffmpeg.exe',
    // 'ffprobe_path' => 'C:\\ffmpeg\\bin\\ffprobe.exe',
];
