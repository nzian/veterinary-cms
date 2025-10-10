<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBranchAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        // Superadmin can access all reports
        if ($user->user_role === 'superadmin') {
            return $next($request);
        }
        
        // Non-superadmin must have a branch assigned
        if (!$user->branch_id) {
            return redirect()->back()->with('error', 'No branch assigned to your account');
        }
        
        return $next($request);
    }
}