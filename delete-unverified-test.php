<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Delete the unverified test accounts
    $deleted = DB::table('users')
        ->where('email', 'LIKE', '%saganaarondave%')
        ->whereNull('email_verified_at')
        ->delete();
    
    echo "✅ Deleted {$deleted} unverified test account(s)\n";
    
    // Also clean up verification records
    $verifications = DB::table('email_verifications')
        ->where('email', 'LIKE', '%saganaarondave%')
        ->delete();
    
    echo "✅ Deleted {$verifications} verification record(s)\n";
    echo "\nNow try registering again - the account won't show until you verify!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
