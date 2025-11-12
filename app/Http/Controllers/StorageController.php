<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageController extends Controller
{
    /**
     * Serve storage files (CORS headers added by middleware)
     * 
     * @param Request $request
     * @param string $path
     * @return BinaryFileResponse
     */
    public function serve(Request $request, string $path)
    {
        // Decode the path
        $path = urldecode($path);
        
        // Check if file exists in public storage
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }
        
        // Get file path
        $filePath = Storage::disk('public')->path($path);
        
        // Get mime type
        $mimeType = Storage::disk('public')->mimeType($path);
        
        // Create response with caching headers
        $response = response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
        
        return $response;
    }
}
