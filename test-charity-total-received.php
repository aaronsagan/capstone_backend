<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHARITY TOTAL RECEIVED TEST ===\n\n";

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

echo "💰 Total Received (Direct Query): ₱" . number_format($totalReceived, 2) . "\n\n";

// Test the model method (same as controller)
echo "🔗 Testing Model calculation:\n";
$charityModel = \App\Models\Charity::find($charity->id);

// Simulate the show method calculation
$totalFromModel = $charityModel->donations()
    ->where('status', 'completed')
    ->sum('amount');

echo "   Total from Model: ₱" . number_format($totalFromModel, 2) . "\n";

// Check if they match
if ($totalReceived == $totalFromModel) {
    echo "   ✅ MATCH! Backend calculation is correct\n";
    echo "   ✅ CharityController@show will return correct total_received\n";
} else {
    echo "   ❌ MISMATCH! There's an issue\n";
}

echo "\n=== TEST COMPLETE ===\n";
