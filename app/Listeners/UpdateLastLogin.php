<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Login;
use Carbon\Carbon;

class UpdateLastLogin
{
    public function handle(Login $event)
    {
        $user = $event->user;
        $user->last_login_at = Carbon::now();
        $user->save();
    }
}