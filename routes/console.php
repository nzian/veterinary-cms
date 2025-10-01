<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule auto-cancel old appointments command
Schedule::command('app:auto-cancel-old-appointments')
    ->daily()
    ->at('00:01')
    ->timezone('Asia/Manila'); // Adjust to your timezone