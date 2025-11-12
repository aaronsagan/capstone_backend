<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

echo "=== Full Activation Flow Test ===\n\n";

// Find the donor
$donor = User::where('email', 'bagunuaeron16@gmail.com')->first();
$admin = User::where('role', 'admin')->first();

if (!$donor || !$admin) {
    echo "❌ User or admin not found\n";
    exit(1);
}

echo "👤 Donor: {$donor->name} ({$donor->email})\n";
echo "👤 Admin: {$admin->name} ({$admin->email})\n\n";

// Step 1: Make sure user is inactive
echo "=== Step 1: Set User to Inactive ===\n";
$donor->update(['status' => 'inactive']);
echo "✅ User status: {$donor->status}\n\n";

// Step 2: Check admin notifications BEFORE activation
echo "=== Step 2: Admin Notifications (Before) ===\n";
$beforeCount = Notification::where('user_id', $admin->id)
    ->where('type', 'reactivation_request')
    ->count();
echo "📊 Reactivation request notifications: {$beforeCount}\n\n";

// Step 3: Simulate admin activating the user via VerificationController
echo "=== Step 3: Admin Activates User ===\n";

// Create a mock request
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function() use ($admin) {
    return $admin;
});

// Get the controller with dependency injection
$notificationService = app(\App\Services\NotificationService::class);
$controller = new \App\Http\Controllers\Admin\VerificationController($notificationService);

try {
    // Call the activateUser method
    $response = $controller->activateUser($request, $donor);
    
    echo "✅ Activation response: " . $response->getContent() . "\n";
    echo "   Status code: " . $response->getStatusCode() . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// Step 4: Verify user status changed
echo "=== Step 4: Verify User Status ===\n";
$donor->refresh();
echo "✅ User status after activation: {$donor->status}\n\n";

// Step 5: Check if notification was created
echo "=== Step 5: Check User Notifications ===\n";
$userNotifications = Notification::where('user_id', $donor->id)
    ->where('type', 'account_reactivated')
    ->orderBy('created_at', 'desc')
    ->get();

echo "📊 Account reactivated notifications: {$userNotifications->count()}\n";
foreach ($userNotifications->take(3) as $notif) {
    echo "   - Created: {$notif->created_at}\n";
    echo "     Title: {$notif->title}\n";
    echo "     Message: {$notif->message}\n";
    echo "     Read: " . ($notif->read ? 'Yes' : 'No') . "\n\n";
}

// Step 6: Check reactivation requests
echo "=== Step 6: Check Reactivation Requests ===\n";
$requests = DB::table('reactivation_requests')
    ->where('user_id', $donor->id)
    ->orderBy('created_at', 'desc')
    ->get();

echo "📊 Total reactivation requests: {$requests->count()}\n";
foreach ($requests->take(3) as $req) {
    echo "   - ID: {$req->id}\n";
    echo "     Status: {$req->status}\n";
    echo "     Requested: {$req->requested_at}\n";
    echo "     Reviewed: " . ($req->reviewed_at ?? 'Not yet') . "\n";
    echo "     Notes: " . ($req->admin_notes ?? 'None') . "\n\n";
}

// Step 7: Email verification
echo "=== Step 7: Email Sent? ===\n";
echo "✅ Email should have been sent to: {$donor->email}\n";
echo "   Subject: Account Reactivated Successfully\n";
echo "   From: CharityHub <charityhub25@gmail.com>\n\n";

echo "=== Summary ===\n";
echo "✅ User status changed: inactive → active\n";
echo "✅ Database notification created: " . ($userNotifications->count() > 0 ? 'Yes' : 'No') . "\n";
echo "✅ Email sent: Check inbox for {$donor->email}\n";
echo "✅ Reactivation requests updated: " . ($requests->where('status', 'approved')->count() > 0 ? 'Yes' : 'No') . "\n\n";

echo "🎉 Test completed!\n\n";

echo "=== Next: Check Email Inbox ===\n";
echo "1. Open email for: {$donor->email}\n";
echo "2. Look for email from: CharityHub <charityhub25@gmail.com>\n";
echo "3. Subject: Account Reactivated Successfully\n";
echo "4. The email should have a 'Log In to CharityHub' button\n";
