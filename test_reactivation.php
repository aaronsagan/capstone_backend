<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ReactivationRequest;
use Illuminate\Support\Facades\DB;

echo "=== Reactivation System Test ===\n\n";

// Find a donor user
$donor = User::where('role', 'donor')->first();

if (!$donor) {
    echo "❌ No donor found in database\n";
    exit(1);
}

echo "✅ Found donor: {$donor->name} ({$donor->email})\n";
echo "   Current status: {$donor->status}\n\n";

// Check if user is inactive
if ($donor->status !== 'inactive') {
    echo "⚠️  User is not inactive. Setting to inactive...\n";
    $donor->update(['status' => 'inactive']);
    echo "✅ User status set to inactive\n\n";
}

// Check for existing reactivation requests
$existingRequests = ReactivationRequest::where('user_id', $donor->id)->get();
echo "📋 Existing reactivation requests: {$existingRequests->count()}\n";
foreach ($existingRequests as $req) {
    echo "   - ID: {$req->id}, Status: {$req->status}, Created: {$req->created_at}\n";
}
echo "\n";

// Check admin notifications
$admins = User::where('role', 'admin')->get();
echo "👥 Admins in system: {$admins->count()}\n";
foreach ($admins as $admin) {
    echo "   - {$admin->name} ({$admin->email})\n";
    $notifications = DB::table('notifications')
        ->where('user_id', $admin->id)
        ->get();
    echo "     Notifications: {$notifications->count()}\n";
    
    foreach ($notifications->take(5) as $notif) {
        echo "     - Type: " . $notif->type . "\n";
        echo "       Title: " . $notif->title . "\n";
        echo "       Message: " . substr($notif->message, 0, 50) . "...\n";
        echo "       Read: " . ($notif->read ? 'Yes' : 'No') . "\n";
    }
}
echo "\n";

echo "=== Test Instructions ===\n";
echo "1. Try to login with: {$donor->email}\n";
echo "2. You should see: 'Your account is deactivated...'\n";
echo "3. Check admin notifications at: http://localhost:8080/admin/notifications\n";
echo "4. Check Laravel log for: 'Inactive user login attempt'\n\n";

echo "=== Database Check ===\n";
echo "Run this query to check notifications:\n";
echo "SELECT * FROM notifications WHERE JSON_EXTRACT(data, '$.type') = 'reactivation_request';\n\n";

echo "=== Manual Test ===\n";
echo "To manually create a reactivation request:\n";
echo "php artisan tinker\n";
echo "\$user = User::find({$donor->id});\n";
echo "\$admins = User::where('role', 'admin')->get();\n";
echo "foreach (\$admins as \$admin) {\n";
echo "    \$admin->notify(new \\App\\Notifications\\ReactivationRequestNotification(\$user));\n";
echo "}\n";
