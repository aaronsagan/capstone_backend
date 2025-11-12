<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Video;
use App\Jobs\ProcessVideoJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CampaignVideoController extends Controller
{
    /**
     * Upload a video for a campaign
     */
    public function store(Request $request, $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        // Check authorization - only campaign owner (charity admin) can upload
        $user = $request->user();
        if ($campaign->charity->owner_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only the campaign owner can upload videos.'
            ], 403);
        }

        // Get video config
        $maxSizeMB = config('videos.max_size_mb', 50);
        $maxSizeKB = $maxSizeMB * 1024;
        $allowedExtensions = explode(',', config('videos.allowed_extensions', 'mp4,webm,mov'));
        $maxPerCampaign = config('videos.max_per_campaign', 10);

        // Check campaign video count limit
        $currentCount = $campaign->videos()->count();
        if ($currentCount >= $maxPerCampaign) {
            return response()->json([
                'message' => "Maximum {$maxPerCampaign} videos per campaign. Please delete existing videos first."
            ], 422);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'video' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo',
                "max:{$maxSizeKB}"
            ]
        ], [
            'video.required' => 'Video file is required',
            'video.file' => 'Invalid file upload',
            'video.mimetypes' => 'Video must be MP4, WEBM, or MOV format',
            'video.max' => "Video size must not exceed {$maxSizeMB}MB"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('video');
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Validate extension
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return response()->json([
                'message' => 'Invalid video format. Allowed: ' . implode(', ', $allowedExtensions)
            ], 422);
        }

        try {
            // Generate unique filename
            $filename = Str::random(40) . '.' . $extension;
            $directory = "videos/{$campaignId}";

            // Store file
            $path = $file->storeAs($directory, $filename, 'public');

            if (!$path) {
                throw new \Exception('Failed to store video file');
            }

            // Create video record
            $video = Video::create([
                'campaign_id' => $campaignId,
                'user_id' => $user->id,
                'original_filename' => $originalFilename,
                'filename' => $filename,
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'status' => 'processing',
            ]);

            // Dispatch processing job
            ProcessVideoJob::dispatch($video);

            Log::info('Video uploaded successfully', [
                'video_id' => $video->id,
                'campaign_id' => $campaignId,
                'user_id' => $user->id,
                'filename' => $filename
            ]);

            return response()->json([
                'message' => 'Video uploaded successfully. Processing in background.',
                'video' => $video->fresh(),
                'status' => 'processing'
            ], 202);

        } catch (\Exception $e) {
            Log::error('Video upload failed', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to upload video',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all videos for a campaign
     */
    public function index(Request $request, $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        $videos = $campaign->videos()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'videos' => $videos,
            'count' => $videos->count(),
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title
            ]
        ]);
    }

    /**
     * Get a single video
     */
    public function show($videoId)
    {
        $video = Video::with(['campaign', 'user'])->findOrFail($videoId);
        
        return response()->json([
            'video' => $video
        ]);
    }

    /**
     * Stream video file
     */
    public function stream(Request $request, $videoId)
    {
        $video = Video::findOrFail($videoId);

        // Check if video is ready
        if (!$video->isReady()) {
            return response()->json([
                'message' => 'Video is still processing',
                'status' => $video->status
            ], 425); // Too Early
        }

        $path = storage_path('app/public/' . $video->path);

        if (!file_exists($path)) {
            return response()->json([
                'message' => 'Video file not found'
            ], 404);
        }

        // Return file response with proper headers for video streaming
        return response()->file($path, [
            'Content-Type' => $video->mime,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="' . $video->original_filename . '"'
        ]);
    }

    /**
     * Delete a video
     */
    public function destroy(Request $request, $videoId)
    {
        $video = Video::with('campaign.charity')->findOrFail($videoId);
        $user = $request->user();

        // Check authorization - only campaign owner or admin
        if ($video->campaign->charity->owner_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only the campaign owner can delete videos.'
            ], 403);
        }

        try {
            // Delete video file
            if ($video->path && Storage::disk('public')->exists($video->path)) {
                Storage::disk('public')->delete($video->path);
            }

            // Delete thumbnail
            if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }

            // Delete database record
            $video->delete();

            Log::info('Video deleted successfully', [
                'video_id' => $videoId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Video deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Video deletion failed', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to delete video',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update video metadata (admin only)
     */
    public function update(Request $request, $videoId)
    {
        $video = Video::findOrFail($videoId);
        $user = $request->user();

        // Only admin can update
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,processing,ready,rejected'
        ]);

        $video->update($validated);

        return response()->json([
            'message' => 'Video updated successfully',
            'video' => $video->fresh()
        ]);
    }
}
