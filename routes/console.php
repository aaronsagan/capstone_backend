<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic cleanup of trashed updates older than 30 days
Schedule::command('cleanup:trashed-updates')->daily();

// Schedule recurring campaign processing - runs daily at midnight
Schedule::command('campaigns:process-recurring')->daily();

// Schedule automatic campaign closure - runs every hour to check for expired campaigns
Schedule::command('campaigns:close-expired')->hourly();
