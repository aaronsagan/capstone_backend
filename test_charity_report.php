<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking test charity data...\n\n";

// Check if charities exist
$charities = App\Models\Charity::all();
echo "Total charities: " . $charities->count() . "\n";

foreach ($charities as $charity) {
    echo "\nCharity: {$charity->name} (ID: {$charity->id})\n";
    echo "  Owner ID: {$charity->owner_id}\n";
    
    $owner = App\Models\User::find($charity->owner_id);
    if ($owner) {
        echo "  Owner Email: {$owner->email}\n";
        echo "  Owner Role: {$owner->role}\n";
    } else {
        echo "  Owner: NOT FOUND!\n";
    }
    
    $donations = App\Models\Donation::where('charity_id', $charity->id)->count();
    echo "  Donations: {$donations}\n";
}

echo "\n\nChecking testcharity1@charityhub.com...\n";
$user = App\Models\User::where('email', 'testcharity1@charityhub.com')->first();
if ($user) {
    echo "User found! ID: {$user->id}, Role: {$user->role}\n";
    $charity = App\Models\Charity::where('owner_id', $user->id)->first();
    if ($charity) {
        echo "✅ Charity found: {$charity->name} (ID: {$charity->id})\n";
        $donations = App\Models\Donation::where('charity_id', $charity->id)->count();
        echo "   Has {$donations} donations\n";
    } else {
        echo "❌ NO CHARITY FOUND FOR THIS USER!\n";
        echo "   This is the problem - the user doesn't have a charity!\n";
    }
} else {
    echo "❌ User not found!\n";
}
