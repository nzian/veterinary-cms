<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB; 

trait BranchDataScope
{
    /**
     * Apply the branch filter to the query unless the user is a superadmin 
     * AND not currently in branch view mode.
     */
    protected static function bootBranchDataScope()
    {
        static::addGlobalScope('branch_data_scope', function (Builder $builder) {
            $user = auth()->user();
            $isSuperAdmin = $user && strtolower(trim($user->user_role)) === 'superadmin';
            $isInBranchMode = Session::get('branch_mode') === 'active';
            $activeBranchId = Session::get('active_branch_id');
            $table = $builder->getModel()->getTable();

            // 1. Super Admin in Global Mode: Return immediately (no filter)
            if ($isSuperAdmin && !$isInBranchMode) {
                return;
            }

            // 2. Normal User OR Super Admin in Branch View Mode: Apply Filter
            if ($activeBranchId) {
                
                // --- Determine Filtering Column (Defaulting to user_id is the main problem) ---
                $branchIdColumn = method_exists($builder->getModel(), 'getBranchIdColumn') 
                                ? $builder->getModel()->getBranchIdColumn() 
                                : 'branch_id'; // Safe default for inventory/services

                // A. Direct Association (Inventory, Services, Equipment, Prescriptions)
                if ($branchIdColumn === 'branch_id' || in_array($table, ['tbl_prod', 'tbl_serv', 'tbl_equipment', 'tbl_prescription'])) {
                    if (DB::getSchemaBuilder()->hasColumn($table, 'branch_id')) {
                        $builder->where($table . '.branch_id', $activeBranchId);
                    }
                } 
                
                // B. Indirect Association (Pets, Owners, Appointments)
                else if ($branchIdColumn === 'user_id' || in_array($table, ['tbl_pet', 'tbl_own', 'tbl_appoint'])) {
                    // This is for records managed by users within the branch.
                    $branchUserIds = \App\Models\User::where('branch_id', $activeBranchId)->pluck('user_id');
                    
                    if (DB::getSchemaBuilder()->hasColumn($table, 'user_id')) {
                        $builder->whereIn($table . '.user_id', $branchUserIds);
                    }
                }
                
                // C. Special Cases: Referral table (tbl_ref) 
                else if ($table === 'tbl_ref') {
                    // Super Admin in Branch Mode should see ALL referrals TO or FROM this branch.
                    $builder->where(function ($q) use ($activeBranchId) {
                        $q->where('ref_to', $activeBranchId)
                          ->orWhere('ref_by', $activeBranchId);
                    });
                }
            }
        });
    }

    // Keep the default helper method
    public function getBranchIdColumn()
    {
        return 'branch_id'; 
    }
}