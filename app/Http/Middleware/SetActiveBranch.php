<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetActiveBranch
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        // Super admin can switch branches, others use their assigned branch
        if ($user->user_role === 'superadmin') {
            $activeBranchId = session('active_branch_id', $user->branch_id);
        } else {
            $activeBranchId = $user->branch_id;
            session(['active_branch_id' => $activeBranchId]);
        }
        
        // Make it available globally
        view()->share('currentBranchId', $activeBranchId);
        
        return $next($request);
    }
}