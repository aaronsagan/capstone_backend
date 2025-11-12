<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking testdonor1 data...\n\n";

$user = App\Models\User::where('email', 'testdonor1@charityhub.com')->first();
if ($user) {
    echo "✅ User found! ID: {$user->id}, Role: {$user->role}\n";
    
    $donations = App\Models\Donation::where('donor_id', $user->id)->get();
    echo "Total donations: " . $donations->count() . "\n\n";
    
    if ($donations->count() > 0) {
        foreach ($donations as $donation) {
            echo "- Donation #{$donation->id}: ₱{$donation->amount} to Charity #{$donation->charity_id}\n";
            echo "  Status: {$donation->status}\n";
            echo "  Date: " . ($donation->donated_at ?? 'N/A') . "\n";
            
            $charity = App\Models\Charity::find($donation->charity_id);
            if ($charity) {
                echo "  Charity: {$charity->name}\n";
            }
            
            if ($donation->campaign_id) {
                $campaign = App\Models\Campaign::find($donation->campaign_id);
                if ($campaign) {
                    echo "  Campaign: {$campaign->title}\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "❌ NO DONATIONS FOUND - THIS IS THE PROBLEM!\n";
        echo "The donor needs donations to generate a report.\n";
    }
} else {
    echo "❌ User not found!\n";
}
