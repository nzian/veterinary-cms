<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    View::composer('*', function ($view) {
    $branches = Branch::all();
    $lowStockItems = Product::with('branch')
        ->whereColumn('prod_stocks', '<=', 'prod_reorderlevel')
        ->get();

    $user = Auth::user();

    if ($user) {
        $user->user_status = ($user->last_login_at && Carbon::parse($user->last_login_at)->gt(now()->subDays(7))) 
            ? 'active' 
            : 'inactive';
    }

    $view->with('branches', $branches)
         ->with('lowStockItems', $lowStockItems)
         ->with('user', $user);
});
}

    
}
