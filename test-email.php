<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('This is a test email from CharityHub. If you received this, email configuration is working!', function($message) {
        $message->to('saganaarondave33@gmail.com')
                ->subject('CharityHub Email Test - ' . date('Y-m-d H:i:s'));
    });
    
    echo "✅ Email sent successfully to saganaarondave33@gmail.com\n";
    echo "Check your inbox (and spam folder)!\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Error sending email:\n";
    echo $e->getMessage() . "\n";
    echo "\nCheck your .env file:\n";
    echo "- MAIL_MAILER=smtp\n";
    echo "- MAIL_HOST=smtp.gmail.com\n";
    echo "- MAIL_PORT=587\n";
    echo "- MAIL_USERNAME=charityhub25@gmail.com\n";
    echo "- MAIL_PASSWORD=your-app-password\n";
    echo "- MAIL_ENCRYPTION=tls\n";
}
