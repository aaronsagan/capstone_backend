<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'admin@example.com')->first();

if (!$user) {
    echo "User not found!\n";
    exit;
}

echo "User: " . $user->email . "\n";
echo "Password Hash: " . $user->password . "\n";
echo "Password Hash Length: " . strlen($user->password) . "\n\n";

// Test common passwords
$testPasswords = [
    'password',
    'password123',
    'Password123',
    'admin123',
    'Admin123',
    '12345678',
];

echo "Testing common passwords:\n";
foreach ($testPasswords as $pwd) {
    $matches = \Illuminate\Support\Facades\Hash::check($pwd, $user->password);
    echo "$pwd: " . ($matches ? "✓ MATCH" : "✗ No match") . "\n";
}
