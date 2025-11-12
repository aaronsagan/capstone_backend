<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::select('id', 'email', 'role', 'status')->get();

echo "Total Users: " . $users->count() . "\n\n";
echo str_pad("ID", 5) . str_pad("Email", 35) . str_pad("Role", 15) . "Status\n";
echo str_repeat("-", 70) . "\n";

foreach ($users as $user) {
    echo str_pad($user->id, 5) . 
         str_pad($user->email, 35) . 
         str_pad($user->role, 15) . 
         $user->status . "\n";
}
