<?php

namespace App\Traits;

trait BranchFilterable
{
    /**
     * Get the appropriate branch ID for filtering data
     * 
     * @return int|null
     */
    public function getActiveBranchId()
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        $normalizedRole = strtolower(trim($user->user_role));
        
        // Super admin can switch branches
        if ($normalizedRole === 'superadmin') {
            // If in branch mode, return the selected branch
            if (session('branch_mode') === 'active' && session('active_branch_id')) {
                return session('active_branch_id');
            }
            // Otherwise, return null (see all branches)
            return null;
        }
        
        // Other roles use their assigned branch
        return $user->branch_id;
    }
    
    /**
     * Apply branch scope to a query
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $branchColumn The column name for branch_id (default: 'branch_id')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyBranchScope($query, $branchColumn = 'branch_id')
    {
        $branchId = $this->getActiveBranchId();
        
        if ($branchId !== null) {
            $query->where($branchColumn, $branchId);
        }
        
        return $query;
    }
    
    /**
     * Check if user is in branch-specific mode
     * 
     * @return bool
     */
    public function isInBranchMode()
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        $normalizedRole = strtolower(trim($user->user_role));
        
        if ($normalizedRole === 'superadmin') {
            return session('branch_mode') === 'active';
        }
        
        // Other roles are always in branch mode (their assigned branch)
        return true;
    }
    
    /**
     * Get the branch name for display
     * 
     * @return string
     */
    public function getActiveBranchName()
    {
        $user = auth()->user();
        
        if (!$user) {
            return 'Unknown';
        }
        
        $normalizedRole = strtolower(trim($user->user_role));
        
        if ($normalizedRole === 'superadmin') {
            if (session('branch_mode') === 'active') {
                return session('active_branch_name', 'Selected Branch');
            }
            return 'All Branches';
        }
        
        return $user->branch->branch_name ?? 'Unknown Branch';
    }
}