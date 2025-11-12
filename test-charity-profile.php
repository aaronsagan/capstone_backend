<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHARITY PROFILE DATA TEST ===\n\n";

// Get a charity admin user
$charityAdmin = DB::table('users')->where('role', 'charity_admin')->first();

if (!$charityAdmin) {
    echo "❌ No charity admin found!\n";
    exit(1);
}

echo "✅ Charity Admin Found:\n";
echo "   ID: {$charityAdmin->id}\n";
echo "   Name: {$charityAdmin->name}\n";
echo "   Email: {$charityAdmin->email}\n\n";

// Get charity (charities table has owner_id column)
$charity = DB::table('charities')->where('owner_id', $charityAdmin->id)->first();

if (!$charity) {
    echo "❌ No charity found for this admin!\n";
    exit(1);
}

echo "✅ Charity Found:\n";
echo "   ID: {$charity->id}\n";
echo "   Name: {$charity->name}\n";
echo "   Logo: " . ($charity->logo_path ?? 'NULL') . "\n";
echo "   Cover: " . ($charity->cover_image ?? 'NULL') . "\n\n";

// Check donations for this charity
$donations = DB::table('donations')
    ->where('charity_id', $charity->id)
    ->where('status', 'completed')
    ->get();

$totalRaised = $donations->sum('amount');
$donationCount = $donations->count();

echo "💰 Donation Stats:\n";
echo "   Total Donations: {$donationCount}\n";
echo "   Total Raised: ₱" . number_format($totalRaised, 2) . "\n\n";

// Check campaigns
$campaigns = DB::table('campaigns')
    ->where('charity_id', $charity->id)
    ->get();

echo "📢 Campaign Stats:\n";
echo "   Total Campaigns: " . $campaigns->count() . "\n";
echo "   Active Campaigns: " . $campaigns->where('status', 'active')->count() . "\n\n";

// Check if logo file exists
if ($charity->logo_path) {
    $logoPath = storage_path('app/public/' . $charity->logo_path);
    echo "🖼️  Logo File Check:\n";
    echo "   Path: {$logoPath}\n";
    if (file_exists($logoPath)) {
        echo "   ✅ File EXISTS\n";
        echo "   Size: " . filesize($logoPath) . " bytes\n";
    } else {
        echo "   ❌ File DOES NOT EXIST\n";
    }
    echo "\n";
}

// Check if cover file exists
if ($charity->cover_image) {
    $coverPath = storage_path('app/public/' . $charity->cover_image);
    echo "🖼️  Cover Image Check:\n";
    echo "   Path: {$coverPath}\n";
    if (file_exists($coverPath)) {
        echo "   ✅ File EXISTS\n";
        echo "   Size: " . filesize($coverPath) . " bytes\n";
    } else {
        echo "   ❌ File DOES NOT EXIST\n";
    }
    echo "\n";
}

// Test API endpoint
echo "🔗 Testing API Endpoint:\n";
echo "   GET /api/charities/{$charity->id}\n\n";

echo "=== TEST COMPLETE ===\n";
