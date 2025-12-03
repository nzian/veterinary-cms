<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:auto-cancel-old-appointments')
             ->daily()
             ->withoutOverlapping();
             
        // Send follow-up appointment reminders daily at 9:00 AM
        $schedule->command('app:send-followup-reminders')
             ->dailyAt('09:00')
             ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}