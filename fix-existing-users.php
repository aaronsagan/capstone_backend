<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Update ALL existing users to mark them as verified
    // This will fix seeded accounts and any old accounts
    $updated = DB::table('users')
        ->whereNull('email_verified_at')
        ->update(['email_verified_at' => now()]);
    
    echo "✅ Updated {$updated} existing users to verified status\n";
    echo "All existing accounts are now marked as email verified.\n";
    echo "Future registrations will require email verification.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
