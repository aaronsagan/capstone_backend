<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail;

try {
    $testData = [
        'name' => 'Test User',
        'email' => 'saganaarondave33@gmail.com',
        'code' => '123456',
        'token' => 'test-token-' . uniqid(),
        'expires_in' => 5,
    ];
    
    Mail::to('saganaarondave33@gmail.com')->send(new VerifyEmailMail($testData));
    
    echo "✅ VERIFICATION EMAIL sent successfully!\n";
    echo "To: saganaarondave33@gmail.com\n";
    echo "Code: 123456\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "\nCheck your inbox for the verification email with the 6-digit code!\n";
    
} catch (Exception $e) {
    echo "❌ Error sending verification email:\n";
    echo $e->getMessage() . "\n";
    echo "\nFull error:\n";
    echo $e->getTraceAsString() . "\n";
}
