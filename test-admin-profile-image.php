<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ADMIN PROFILE IMAGE TEST ===\n\n";

// Get admin user
$admin = DB::table('users')->where('email', 'admin@example.com')->first();

if (!$admin) {
    echo "❌ Admin user not found!\n";
    exit(1);
}

echo "✅ Admin User Found:\n";
echo "   ID: {$admin->id}\n";
echo "   Name: {$admin->name}\n";
echo "   Email: {$admin->email}\n";
echo "   Phone: {$admin->phone}\n";
echo "   Address: {$admin->address}\n";
echo "   Profile Image: " . ($admin->profile_image ?? 'NULL') . "\n\n";

// Check if profile image exists
if ($admin->profile_image) {
    $imagePath = storage_path('app/public/' . $admin->profile_image);
    echo "Image Path: {$imagePath}\n";
    
    if (file_exists($imagePath)) {
        echo "✅ Image file EXISTS in storage\n";
        echo "   File Size: " . filesize($imagePath) . " bytes\n";
    } else {
        echo "❌ Image file DOES NOT EXIST in storage\n";
    }
    
    // Check symlink
    $publicLink = public_path('storage');
    if (is_link($publicLink)) {
        echo "✅ Storage symlink EXISTS\n";
        echo "   Links to: " . readlink($publicLink) . "\n";
    } else {
        echo "❌ Storage symlink DOES NOT EXIST\n";
    }
    
    // Expected URL
    $expectedUrl = env('APP_URL') . '/storage/' . $admin->profile_image;
    echo "\n📸 Expected Image URL:\n";
    echo "   {$expectedUrl}\n";
    
} else {
    echo "❌ No profile image set in database\n";
}

echo "\n=== TEST COMPLETE ===\n";
