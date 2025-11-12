<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHARITY STATS TEST ===\n\n";

// Get first charity
$charity = DB::table('charities')->first();

if (!$charity) {
    echo "❌ No charity found!\n";
    exit(1);
}

echo "✅ Testing Charity:\n";
echo "   ID: {$charity->id}\n";
echo "   Name: {$charity->name}\n\n";

// Calculate total received
$totalReceived = DB::table('donations')
    ->where('charity_id', $charity->id)
    ->where('status', 'completed')
    ->sum('amount');

echo "💰 Total Received: ₱" . number_format($totalReceived, 2) . "\n";

// Count followers (table is charity_follows, not charity_followers)
$followersCount = DB::table('charity_follows')
    ->where('charity_id', $charity->id)
    ->count();

echo "👥 Total Followers: {$followersCount}\n";

// List followers
if ($followersCount > 0) {
    echo "\n📋 Follower Details:\n";
    $followers = DB::table('charity_follows')
        ->join('users', 'charity_follows.donor_id', '=', 'users.id')
        ->where('charity_follows.charity_id', $charity->id)
        ->select('users.name', 'users.email', 'charity_follows.created_at')
        ->get();
    
    foreach ($followers as $follower) {
        echo "   - {$follower->name} ({$follower->email})\n";
        echo "     Followed at: {$follower->created_at}\n";
    }
}

// Count campaigns
$campaignsCount = DB::table('campaigns')
    ->where('charity_id', $charity->id)
    ->count();

echo "\n📢 Total Campaigns: {$campaignsCount}\n";

// Count updates (table is 'updates', not 'charity_updates')
$updatesCount = DB::table('updates')
    ->where('charity_id', $charity->id)
    ->whereNull('deleted_at')
    ->count();

echo "📝 Total Updates: {$updatesCount}\n";

echo "\n=== TEST COMPLETE ===\n";
