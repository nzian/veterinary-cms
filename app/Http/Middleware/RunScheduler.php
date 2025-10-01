<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunScheduler
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('RunScheduler middleware triggered'); // ADD THIS
        
        $lastRun = Cache::get('scheduler_last_run');
        $now = now();
        
        Log::info('Last run: ' . ($lastRun ? $lastRun : 'never')); // ADD THIS
        Log::info('Current time: ' . $now); // ADD THIS
        
        if (!$lastRun || $now->diffInMinutes($lastRun) >= 60) {
            try {
                Log::info('Attempting to run scheduler...'); // ADD THIS
                Artisan::call('schedule:run');
                Log::info('Scheduler run result: ' . Artisan::output()); // ADD THIS
                
                Cache::put('scheduler_last_run', $now, now()->addHour());
                Log::info('Scheduler executed at ' . $now);
            } catch (\Exception $e) {
                Log::error('Scheduler execution failed: ' . $e->getMessage());
            }
        }
        
        return $next($request);
    }
}