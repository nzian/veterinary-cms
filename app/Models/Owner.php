<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Pet;
use App\Models\Branch;
use App\Models\Referral;
use App\Models\User;

class Owner extends Model
{
    use HasFactory;
    protected $table = 'tbl_own';
    protected $primaryKey = 'own_id';
    public $timestamps = false;

    protected $fillable = [
        'own_name',
        'own_contactnum', 
        'own_location',
        'user_id',
        'branch_id',
    ];

    /**
     * Custom scope to include owners whose pets have active interbranch referrals
     * This replaces BranchDataScope trait to properly handle referrals
     */
    protected static function booted()
    {
        static::addGlobalScope('branch_owner_scope', function (Builder $builder) {
            $user = auth()->user();
            if (!$user) return; // No user logged in
            
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            $isInBranchMode = Session::get('branch_mode') === 'active';
            
            // Get active branch ID - use session for superadmin in branch mode, otherwise use user's branch
            $activeBranchId = Session::get('active_branch_id');
            if (!$isSuperAdmin) {
                // For non-superadmin, always use their assigned branch
                $activeBranchId = $user->branch_id;
            }

            // Super Admin in Global Mode: no filter
            if ($isSuperAdmin && !$isInBranchMode) {
                return;
            }

            // Apply filter for normal users or Super Admin in branch mode
            if ($activeBranchId) {
                $branchUserIds = User::where('branch_id', $activeBranchId)->pluck('user_id');
                
                $builder->where(function($query) use ($branchUserIds, $activeBranchId) {
                    // Include owners created by users in this branch
                    $query->whereIn('tbl_own.user_id', $branchUserIds)
                          // OR owners whose pets have active interbranch referrals TO this branch
                          ->orWhereExists(function($subQuery) use ($activeBranchId) {
                              $subQuery->select(DB::raw(1))
                                       ->from('tbl_pet')
                                       ->join('tbl_ref', 'tbl_pet.pet_id', '=', 'tbl_ref.pet_id')
                                       ->whereColumn('tbl_pet.own_id', 'tbl_own.own_id')
                                       ->where('tbl_ref.ref_to', $activeBranchId)
                                       ->where('tbl_ref.ref_type', 'interbranch')
                                       ->whereIn('tbl_ref.ref_status', ['pending', 'attended', 'completed']);
                          });
                });
            }
        });
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'own_id', 'own_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get referral status info for this owner based on their pets' referrals
 * Returns array with 'type' (outgoing/incoming/none), 'label', 'can_edit', etc.
 */
public function getReferralStatusInfo()
{
    $user = auth()->user();
    if (!$user) {
        return ['type' => 'none', 'label' => null, 'can_edit' => true, 'badge_class' => ''];
    }

    $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
    $isInBranchMode = Session::get('branch_mode') === 'active';
    
    $activeBranchId = Session::get('active_branch_id');
    if (!$isSuperAdmin) {
        $activeBranchId = $user->branch_id;
    }

    if (!$activeBranchId) {
        return ['type' => 'none', 'label' => null, 'can_edit' => true, 'badge_class' => ''];
    }

    // Get pet IDs for this owner
    $petIds = Pet::withoutGlobalScope('branch_pet_scope')
        ->where('own_id', $this->own_id)
        ->pluck('pet_id')
        ->toArray();

    if (empty($petIds)) {
        return ['type' => 'none', 'label' => null, 'can_edit' => true, 'badge_class' => ''];
    }

    // Check for outgoing referrals (from current branch to another)
    $outgoingReferral = Referral::whereIn('pet_id', $petIds)
        ->where('ref_from', $activeBranchId)
        ->where('ref_type', 'interbranch')
        ->whereIn('ref_status', ['pending', 'attended'])
        ->with('refToBranch')
        ->first();

    if ($outgoingReferral) {
        return [
            'type' => 'outgoing',
            'label' => 'Pet referred to ' . ($outgoingReferral->refToBranch->branch_name ?? 'Unknown Branch'),
            'branch_name' => $outgoingReferral->refToBranch->branch_name ?? 'Unknown',
            'can_edit' => false,
            'status' => $outgoingReferral->ref_status,
            'badge_class' => 'bg-orange-100 text-orange-800 border-orange-300'
        ];
    }

    // Check for incoming referrals (to current branch from another)
    $incomingReferral = Referral::whereIn('pet_id', $petIds)
        ->where('ref_to', $activeBranchId)
        ->where('ref_type', 'interbranch')
        ->whereIn('ref_status', ['pending', 'attended'])
        ->with('refFromBranch')
        ->first();

    if ($incomingReferral) {
        return [
            'type' => 'incoming',
            'label' => 'Pet referred from ' . ($incomingReferral->refFromBranch->branch_name ?? 'Unknown Branch'),
            'branch_name' => $incomingReferral->refFromBranch->branch_name ?? 'Unknown',
            'can_edit' => true, // Can view/use in destination branch
            'status' => $incomingReferral->ref_status,
            'badge_class' => 'bg-purple-100 text-purple-800 border-purple-300'
        ];
    }

    return ['type' => 'none', 'label' => null, 'can_edit' => true, 'badge_class' => ''];
}

/**
 * Check if this owner belongs to the current branch (created by users in this branch)
 */
public function belongsToCurrentBranch()
{
    $user = auth()->user();
    if (!$user) return false;

    $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
    $isInBranchMode = Session::get('branch_mode') === 'active';
    
    $activeBranchId = Session::get('active_branch_id');
    if (!$isSuperAdmin) {
        $activeBranchId = $user->branch_id;
    }

    if (!$activeBranchId) return true; // No filter applied

    $branchUserIds = User::where('branch_id', $activeBranchId)->pluck('user_id')->toArray();
    return in_array($this->user_id, $branchUserIds);
}
}
