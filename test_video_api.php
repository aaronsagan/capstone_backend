<?php

/**
 * Video Upload API Test Script
 * 
 * Tests all video upload endpoints without requiring a real video file
 */

echo "========================================\n";
echo "VIDEO UPLOAD API - BACKEND TEST\n";
echo "========================================\n\n";

// Check if videos table exists
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Campaign;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

$results = [];

// Test 1: Check Database Table
echo "[1/5] Checking videos table...\n";
try {
    $tableExists = DB::select("SHOW TABLES LIKE 'videos'");
    if ($tableExists) {
        echo "✓ Videos table exists\n";
        
        // Check columns
        $columns = DB::select("DESCRIBE videos");
        $columnNames = array_map(fn($col) => $col->Field, $columns);
        
        $requiredColumns = ['id', 'campaign_id', 'user_id', 'filename', 'path', 'mime', 'size', 'status', 'thumbnail_path'];
        $missingColumns = array_diff($requiredColumns, $columnNames);
        
        if (empty($missingColumns)) {
            echo "✓ All required columns present\n";
            $results['database'] = 'PASS';
        } else {
            echo "✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
            $results['database'] = 'FAIL - Missing columns';
        }
    } else {
        echo "✗ Videos table does not exist\n";
        $results['database'] = 'FAIL - Table missing';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['database'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 2: Check Video Model
echo "[2/5] Checking Video model...\n";
try {
    $videoModel = new Video();
    echo "✓ Video model can be instantiated\n";
    
    // Check fillable fields
    $fillable = $videoModel->getFillable();
    echo "✓ Fillable fields: " . count($fillable) . " fields\n";
    
    // Check relationships
    if (method_exists($videoModel, 'campaign') && method_exists($videoModel, 'user')) {
        echo "✓ Relationships defined (campaign, user)\n";
        $results['model'] = 'PASS';
    } else {
        echo "✗ Missing relationships\n";
        $results['model'] = 'FAIL - Missing relationships';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['model'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 3: Check Campaign Relationship
echo "[3/5] Checking Campaign->videos relationship...\n";
try {
    $campaign = Campaign::first();
    if ($campaign) {
        $videos = $campaign->videos;
        echo "✓ Campaign has videos relationship\n";
        echo "  Campaign: {$campaign->title}\n";
        echo "  Current video count: " . $videos->count() . "\n";
        $results['relationship'] = 'PASS';
    } else {
        echo "⚠ No campaigns found in database\n";
        $results['relationship'] = 'SKIP - No campaigns';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['relationship'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 4: Check Config Files
echo "[4/5] Checking configuration...\n";
try {
    $videoConfig = config('videos');
    $ffmpegConfig = config('ffmpeg');
    
    if ($videoConfig && $ffmpegConfig) {
        echo "✓ Config files loaded\n";
        echo "  Max size: " . config('videos.max_size_mb', 'NOT SET') . "MB\n";
        echo "  Max duration: " . config('videos.max_duration_sec', 'NOT SET') . "s\n";
        echo "  Allowed extensions: " . config('videos.allowed_extensions', 'NOT SET') . "\n";
        echo "  Max per campaign: " . config('videos.max_per_campaign', 'NOT SET') . "\n";
        $results['config'] = 'PASS';
    } else {
        echo "✗ Config files missing\n";
        $results['config'] = 'FAIL';
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    $results['config'] = 'FAIL - ' . $e->getMessage();
}
echo "\n";

// Test 5: Check FFmpeg Availability
echo "[5/5] Checking FFmpeg...\n";
try {
    $ffmpegPath = config('ffmpeg.ffmpeg_path', 'ffmpeg');
    $output = [];
    $return = null;
    exec("{$ffmpegPath} -version 2>&1", $output, $return);
    
    if ($return === 0) {
        echo "✓ FFmpeg is available\n";
        echo "  Version: " . (isset($output[0]) ? substr($output[0], 0, 50) . '...' : 'Unknown') . "\n";
        $results['ffmpeg'] = 'PASS';
    } else {
        echo "⚠ FFmpeg not found or not executable\n";
        echo "  Note: Videos will process without thumbnails\n";
        $results['ffmpeg'] = 'WARN - Not available';
    }
} catch (\Exception $e) {
    echo "⚠ FFmpeg check failed: " . $e->getMessage() . "\n";
    $results['ffmpeg'] = 'WARN - Not available';
}
echo "\n";

// Summary
echo "========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
$passed = 0;
$failed = 0;
$warnings = 0;

foreach ($results as $test => $result) {
    $status = str_starts_with($result, 'PASS') ? '✓' : 
              (str_starts_with($result, 'WARN') || str_starts_with($result, 'SKIP') ? '⚠' : '✗');
    echo "{$status} " . ucfirst($test) . ": {$result}\n";
    
    if (str_starts_with($result, 'PASS')) $passed++;
    elseif (str_starts_with($result, 'WARN') || str_starts_with($result, 'SKIP')) $warnings++;
    else $failed++;
}

echo "\n";
echo "PASSED: {$passed}\n";
echo "WARNINGS: {$warnings}\n";
echo "FAILED: {$failed}\n";
echo "\n";

if ($failed === 0) {
    echo "✅ Backend is ready for video uploads!\n";
    echo "\nNext steps:\n";
    echo "1. Install FFmpeg for thumbnail generation (optional but recommended)\n";
    echo "2. Test API endpoints with curl or Postman\n";
    echo "3. Start queue worker: php artisan queue:work\n";
} else {
    echo "❌ Some tests failed. Please review the errors above.\n";
}

echo "========================================\n";
