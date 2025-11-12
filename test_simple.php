<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Simple Query...\n\n";

$charityId = 2;

// Test simplest possible query
try {
    $result = \DB::select("
        SELECT 
            DATE_FORMAT(created_at, '%M %Y') as month_label,
            SUM(amount) as total
        FROM donations
        WHERE status = 'completed'
        AND EXISTS (
            SELECT 1 FROM campaigns 
            WHERE donations.campaign_id = campaigns.id 
            AND charity_id = ?
        )
        GROUP BY DATE_FORMAT(created_at, '%M %Y')
        ORDER BY MIN(created_at)
    ", [$charityId]);
    
    echo "✅ SUCCESS! Raw SQL works.\n";
    echo "✅ Found " . count($result) . " months\n\n";
    
    foreach ($result as $row) {
        echo "  - {$row->month_label}: ₱" . number_format($row->total, 2) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
