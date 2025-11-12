<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Debugging SQL Generation...\n\n";

$charityId = 2;

// Enable query logging
\DB::enableQueryLog();

try {
    $query = \App\Models\Donation::select(
            \DB::raw("DATE_FORMAT(created_at, '%Y-%m') as year_month"),
            \DB::raw("DATE_FORMAT(created_at, '%M %Y') as month_label"),
            \DB::raw('SUM(amount) as total')
        )
        ->where('status', '=', 'completed')
        ->when($charityId, fn($q) => $q->whereHas('campaign', fn($c) => $c->where('charity_id', '=', $charityId)))
        ->groupBy(\DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), \DB::raw("DATE_FORMAT(created_at, '%M %Y')"))
        ->orderBy(\DB::raw("DATE_FORMAT(created_at, '%Y-%m')"));
    
    echo "Generated SQL:\n";
    echo $query->toSql() . "\n\n";
    
    echo "Bindings:\n";
    print_r($query->getBindings());
    
    echo "\n\nAttempting to execute...\n";
    $result = $query->get();
    echo "✅ Success!\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "\n\nQuery Log:\n";
print_r(\DB::getQueryLog());
