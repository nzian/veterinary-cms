<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;

class BranchSwitchController extends Controller
{
    /**
     * Switch to a specific branch and store it in session
     */
    public function switch($id)
    {
        $user = auth()->user();
        
        // Verify user is super admin
        if (strtolower(trim($user->user_role)) !== 'superadmin') {
            return redirect()->back()->with('error', 'Unauthorized access');
        }
        
        // Verify branch exists
        $branch = Branch::findOrFail($id);
        
        // Store the active branch in session
        session([
            'active_branch_id' => $branch->branch_id,
            'active_branch_name' => $branch->branch_name,
            'branch_mode' => 'active' // Flag to show we're in branch-specific mode
        ]);
        
        return redirect()->route('dashboard-index')
            ->with('success', "Switched to {$branch->branch_name}");
    }
    
    /**
     * Clear branch selection and return to super admin view
     */
    public function clearBranch()
    {
        session()->forget(['active_branch_id', 'active_branch_name', 'branch_mode']);
        
        return redirect()->route('dashboard-index')
            ->with('success', 'Returned to Super Admin view');
    }
}