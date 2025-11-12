<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Mail\Security\AccountReactivatedMail;
use Illuminate\Support\Facades\Mail;

echo "=== Testing Email Activation ===\n\n";

// Find the inactive donor
$donor = User::where('email', 'bagunuaeron16@gmail.com')->first();

if (!$donor) {
    echo "❌ User not found\n";
    exit(1);
}

echo "✅ Found user: {$donor->name} ({$donor->email})\n";
echo "   Current status: {$donor->status}\n\n";

// Make sure user is inactive
if ($donor->status !== 'inactive') {
    echo "⚠️  Setting user to inactive for testing...\n";
    $donor->update(['status' => 'inactive']);
    echo "✅ User status set to inactive\n\n";
}

echo "=== Simulating Admin Activation ===\n\n";

// Get admin user
$admin = User::where('role', 'admin')->first();
if (!$admin) {
    echo "❌ No admin found\n";
    exit(1);
}

echo "✅ Admin: {$admin->name} ({$admin->email})\n\n";

// Store previous status
$previousStatus = $donor->status;

// Update status to active
$donor->update(['status' => 'active']);
echo "✅ User status updated to: active\n\n";

// Check if user was inactive
if ($previousStatus === 'inactive') {
    echo "📧 User was inactive, sending reactivation email...\n\n";
    
    try {
        // Send email immediately
        Mail::to($donor->email)->send(
            new AccountReactivatedMail($donor)
        );
        
        echo "✅ Email sent successfully!\n";
        echo "   To: {$donor->email}\n";
        echo "   Subject: Account Reactivated Successfully\n\n";
        
        // Send database notification
        \App\Services\NotificationHelper::accountReactivated($donor);
        echo "✅ Database notification created\n\n";
        
        // Update reactivation requests
        $updated = \App\Models\ReactivationRequest::where('user_id', $donor->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'admin_notes' => 'Approved via test script'
            ]);
        
        echo "✅ Updated {$updated} reactivation request(s)\n\n";
        
    } catch (\Exception $e) {
        echo "❌ Error sending email: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "⚠️  User was not inactive, no email sent\n";
}

echo "\n=== Verification ===\n\n";

// Check user notifications
$notifications = \App\Models\Notification::where('user_id', $donor->id)
    ->where('type', 'account_reactivated')
    ->orderBy('created_at', 'desc')
    ->first();

if ($notifications) {
    echo "✅ Notification found in database:\n";
    echo "   Type: {$notifications->type}\n";
    echo "   Title: {$notifications->title}\n";
    echo "   Message: {$notifications->message}\n";
    echo "   Created: {$notifications->created_at}\n";
} else {
    echo "❌ No notification found in database\n";
}

echo "\n=== Email Configuration ===\n";
echo "MAIL_MAILER: " . env('MAIL_MAILER') . "\n";
echo "MAIL_HOST: " . env('MAIL_HOST') . "\n";
echo "MAIL_PORT: " . env('MAIL_PORT') . "\n";
echo "MAIL_USERNAME: " . env('MAIL_USERNAME') . "\n";
echo "MAIL_FROM_ADDRESS: " . env('MAIL_FROM_ADDRESS') . "\n";
echo "MAIL_FROM_NAME: " . env('MAIL_FROM_NAME') . "\n";

echo "\n=== Check Your Email ===\n";
echo "Check the inbox for: {$donor->email}\n";
echo "Subject: Account Reactivated Successfully\n";
echo "From: " . env('MAIL_FROM_NAME') . " <" . env('MAIL_FROM_ADDRESS') . ">\n";

echo "\n✅ Test completed!\n";
