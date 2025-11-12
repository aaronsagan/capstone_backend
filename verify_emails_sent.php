<?php

/**
 * Verify Emails Sent
 * 
 * Checks the jobs table to see if test emails were processed
 * 
 * Run: php verify_emails_sent.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "==============================================\n";
echo "   VERIFYING EMAIL DELIVERY\n";
echo "==============================================\n\n";

// Check jobs table
echo "CHECKING QUEUE STATUS:\n";
echo "-------------------------------------------\n";

try {
    // Count pending jobs
    $pendingJobs = DB::table('jobs')->count();
    echo "Pending jobs: {$pendingJobs}\n";
    
    if ($pendingJobs > 0) {
        echo "⚠️  {$pendingJobs} email(s) still in queue\n";
        echo "   Queue worker may need more time\n\n";
        
        echo "WAITING JOBS:\n";
        $jobs = DB::table('jobs')->orderBy('created_at', 'desc')->take(10)->get();
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload);
            $mailClass = $payload->data->command ?? 'Unknown';
            
            // Extract mail class name
            if (preg_match('/App\\\\Mail\\\\(\w+)/', $mailClass, $matches)) {
                $mailName = $matches[1];
            } else {
                $mailName = 'Unknown';
            }
            
            echo "  - {$mailName}\n";
        }
        echo "\n";
    } else {
        echo "✅ No pending jobs - All emails processed!\n\n";
    }
    
    // Check failed jobs
    $failedJobs = DB::table('failed_jobs')->count();
    echo "Failed jobs: {$failedJobs}\n";
    
    if ($failedJobs > 0) {
        echo "❌ {$failedJobs} email(s) failed to send\n\n";
        
        echo "FAILED JOBS:\n";
        $failed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->take(5)->get();
        foreach ($failed as $job) {
            $payload = json_decode($job->payload);
            $mailClass = $payload->data->command ?? 'Unknown';
            
            if (preg_match('/App\\\\Mail\\\\(\w+)/', $mailClass, $matches)) {
                $mailName = $matches[1];
            } else {
                $mailName = 'Unknown';
            }
            
            echo "  - {$mailName}\n";
            echo "    Failed: " . date('M d, Y H:i:s', strtotime($job->failed_at)) . "\n";
            echo "    Exception: " . substr($job->exception, 0, 100) . "...\n\n";
        }
    } else {
        echo "✅ No failed jobs - All successful!\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error checking queue: {$e->getMessage()}\n\n";
}

// Summary
echo "==============================================\n";
echo "SUMMARY\n";
echo "==============================================\n\n";

if ($pendingJobs == 0 && $failedJobs == 0) {
    echo "🎉 ALL EMAILS SENT SUCCESSFULLY!\n\n";
    
    echo "WHAT TO CHECK:\n";
    echo "-------------------------------------------\n";
    echo "1. Check your inbox: bagunuaeron16@gmail.com\n";
    echo "2. Check spam/junk folder\n";
    echo "3. Look for these 9 emails:\n\n";
    
    echo "   DONOR EMAILS (7):\n";
    echo "   ✉️  1. Donation Confirmation\n";
    echo "   ✉️  2. Donation Verified\n";
    echo "   ✉️  3. Acknowledgment Letter (PDF)\n";
    echo "   ✉️  4. Donation Rejected\n";
    echo "   ✉️  5. Refund Request Confirmation\n";
    echo "   ✉️  6. Refund Approved\n";
    echo "   ✉️  7. Refund Denied\n\n";
    
    echo "   CHARITY EMAILS (2):\n";
    echo "   ✉️  8. New Donation Alert\n";
    echo "   ✉️  9. Refund Request Alert\n\n";
    
    echo "If emails not received:\n";
    echo "  - Wait 5-10 minutes (Gmail may delay)\n";
    echo "  - Check spam folder\n";
    echo "  - Check .env MAIL settings\n";
    echo "  - Run: php artisan queue:failed\n\n";
    
} elseif ($pendingJobs > 0) {
    echo "⏳ EMAILS ARE BEING PROCESSED\n\n";
    echo "Wait a few more minutes and run this script again:\n";
    echo "  php verify_emails_sent.php\n\n";
    
    echo "Or process queue manually:\n";
    echo "  php artisan queue:work --once\n\n";
    
} elseif ($failedJobs > 0) {
    echo "❌ SOME EMAILS FAILED\n\n";
    echo "To retry failed jobs:\n";
    echo "  php artisan queue:retry all\n\n";
    
    echo "To see details:\n";
    echo "  php artisan queue:failed\n\n";
    
    echo "Common issues:\n";
    echo "  - SMTP credentials incorrect\n";
    echo "  - Gmail app password needed\n";
    echo "  - Network/firewall blocking SMTP\n\n";
}

echo "==============================================\n";

// Check Laravel log for email errors
echo "\nCHECKING LARAVEL LOG FOR EMAIL ERRORS:\n";
echo "-------------------------------------------\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $emailErrors = [];
    
    // Look for mail-related errors in last 1000 lines
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -1000);
    
    foreach ($recentLines as $line) {
        if (stripos($line, 'mail') !== false && stripos($line, 'error') !== false) {
            $emailErrors[] = substr($line, 0, 200);
        }
    }
    
    if (empty($emailErrors)) {
        echo "✅ No email errors in recent logs\n\n";
    } else {
        echo "⚠️  Found " . count($emailErrors) . " email-related error(s):\n\n";
        foreach (array_slice($emailErrors, -5) as $error) {
            echo "  " . $error . "...\n\n";
        }
    }
} else {
    echo "⚠️  Log file not found\n\n";
}

echo "==============================================\n\n";
