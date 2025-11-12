<?php

namespace App\Jobs;

use App\Models\Video;
use App\Mail\VideoProcessedMail;
use App\Mail\VideoRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFProbe;

class ProcessVideoJob implements ShouldQueue
{
    use Queueable;

    public $video;
    public $tries = 3;
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing video', ['video_id' => $this->video->id]);

            $videoPath = storage_path('app/public/' . $this->video->path);

            if (!file_exists($videoPath)) {
                throw new \Exception('Video file not found');
            }

            // Check if FFmpeg is available
            $ffmpegAvailable = $this->checkFFmpegAvailable();

            if ($ffmpegAvailable) {
                $this->processWithFFmpeg($videoPath);
            } else {
                $this->processWithoutFFmpeg($videoPath);
            }

            // Send success email
            if ($this->video->user) {
                Mail::to($this->video->user->email)->queue(
                    new VideoProcessedMail($this->video)
                );
            }

        } catch (\Exception $e) {
            Log::error('Video processing failed', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->video->update([
                'status' => 'rejected'
            ]);

            // Send rejection email
            if ($this->video->user) {
                Mail::to($this->video->user->email)->queue(
                    new VideoRejectedMail($this->video, $e->getMessage())
                );
            }

            throw $e;
        }
    }

    /**
     * Process video with FFmpeg
     */
    protected function processWithFFmpeg($videoPath)
    {
        try {
            // Initialize FFmpeg
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => config('ffmpeg.ffmpeg_path', '/usr/bin/ffmpeg'),
                'ffprobe.binaries' => config('ffmpeg.ffprobe_path', '/usr/bin/ffprobe'),
                'timeout'          => 300,
                'ffmpeg.threads'   => 1,
            ]);

            $ffprobe = FFProbe::create([
                'ffprobe.binaries' => config('ffmpeg.ffprobe_path', '/usr/bin/ffprobe'),
            ]);

            // Get video metadata
            $duration = (int) $ffprobe->format($videoPath)->get('duration');
            $videoStream = $ffprobe->streams($videoPath)->videos()->first();
            $width = $videoStream ? $videoStream->get('width') : null;
            $height = $videoStream ? $videoStream->get('height') : null;

            // Check duration limit
            $maxDuration = config('videos.max_duration_sec', 300);
            if ($duration > $maxDuration) {
                $this->video->update(['status' => 'rejected']);
                
                if ($this->video->user) {
                    Mail::to($this->video->user->email)->queue(
                        new VideoRejectedMail(
                            $this->video,
                            "Video duration ({$duration}s) exceeds maximum allowed ({$maxDuration}s)"
                        )
                    );
                }
                
                Log::warning('Video rejected - duration too long', [
                    'video_id' => $this->video->id,
                    'duration' => $duration,
                    'max_duration' => $maxDuration
                ]);
                
                return;
            }

            // Generate thumbnail at 2 seconds (or at 10% of duration)
            $thumbnailTime = min(2, $duration * 0.1);
            $video = $ffmpeg->open($videoPath);
            $frame = $video->frame(TimeCode::fromSeconds($thumbnailTime));
            
            $thumbnailFilename = 'thumb_' . pathinfo($this->video->filename, PATHINFO_FILENAME) . '.jpg';
            
            // Determine thumbnail path based on context
            if ($this->video->campaign_id) {
                $thumbnailPath = "videos/campaigns/{$this->video->campaign_id}/{$thumbnailFilename}";
            } else {
                $thumbnailPath = "videos/updates/{$this->video->update_post_id}/{$thumbnailFilename}";
            }
            
            $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);

            // Ensure directory exists
            $dir = dirname($thumbnailFullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $frame->save($thumbnailFullPath);

            // Update video record
            $this->video->update([
                'duration' => $duration,
                'width' => $width,
                'height' => $height,
                'thumbnail_path' => $thumbnailPath,
                'status' => 'ready'
            ]);

            Log::info('Video processed successfully with FFmpeg', [
                'video_id' => $this->video->id,
                'duration' => $duration,
                'dimensions' => "{$width}x{$height}"
            ]);

        } catch (\Exception $e) {
            Log::error('FFmpeg processing failed', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process video without FFmpeg (fallback - mark as ready)
     */
    protected function processWithoutFFmpeg($videoPath)
    {
        Log::warning('FFmpeg not available - video marked as ready without thumbnail', [
            'video_id' => $this->video->id
        ]);

        // Get basic file info
        $filesize = filesize($videoPath);

        // Update video record without thumbnail
        $this->video->update([
            'size' => $filesize,
            'status' => 'ready'
        ]);

        Log::info('Video processed without FFmpeg', [
            'video_id' => $this->video->id,
            'size' => $filesize
        ]);
    }

    /**
     * Check if FFmpeg is available
     */
    protected function checkFFmpegAvailable()
    {
        try {
            $ffmpegPath = config('ffmpeg.ffmpeg_path', 'ffmpeg');
            
            // Try to execute ffmpeg version command
            $output = [];
            $return = null;
            exec("{$ffmpegPath} -version 2>&1", $output, $return);
            
            return $return === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error('ProcessVideoJob failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception->getMessage()
        ]);

        $this->video->update(['status' => 'rejected']);
    }
}
