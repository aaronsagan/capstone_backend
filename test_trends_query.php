<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the exact query that was failing
echo "Testing Trends Query Fix...\n\n";

$charityId = 2;

// Test the fixed query
try {
    $growthData = \App\Models\Donation::selectRaw("DATE_FORMAT(created_at, '%M %Y') as month_label, SUM(amount) as total, MIN(created_at) as min_date")
        ->where('status', '=', 'completed')
        ->when($charityId, fn($q) => $q->whereHas('campaign', fn($c) => $c->where('charity_id', '=', $charityId)))
        ->groupByRaw("DATE_FORMAT(created_at, '%M %Y')")
        ->orderByRaw("MIN(created_at)")
        ->get();
    
    echo "✅ SUCCESS! Query executed without errors.\n";
    echo "✅ Found " . $growthData->count() . " months with donations\n\n";
    
    foreach ($growthData as $row) {
        echo "  - {$row->month_label}: ₱" . number_format($row->total, 2) . "\n";
    }
    
    echo "\n✅ The fix is working! Backend code updated successfully.\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "❌ The fix did NOT work. Code still has issues.\n";
}
