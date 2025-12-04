<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Owner;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\User;
use App\Models\Referral;
use App\Traits\BranchDataScope; 

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;
    protected $table = 'tbl_pet';
    protected $primaryKey = 'pet_id'; 

    public $incrementing = true;        

    public $timestamps = false;

    /**
     * Override the BranchDataScope to include pets with active interbranch referrals
     * But EXCLUDE pets that have been referred OUT to another branch (they should only appear in destination branch)
     */
    protected static function booted()
    {
        static::addGlobalScope('branch_pet_scope', function (Builder $builder) {
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
                $branchUserIds = \App\Models\User::where('branch_id', $activeBranchId)->pluck('user_id');
                
                $builder->where(function($query) use ($branchUserIds, $activeBranchId) {
                    // Include pets created by users in this branch (but will be filtered later if referred out)
                    $query->whereIn('tbl_pet.user_id', $branchUserIds)
                          // OR pets that have active interbranch referrals TO this branch
                          ->orWhereExists(function($subQuery) use ($activeBranchId) {
                              $subQuery->select(DB::raw(1))
                                       ->from('tbl_ref')
                                       ->whereColumn('tbl_ref.pet_id', 'tbl_pet.pet_id')
                                       ->where('tbl_ref.ref_to', $activeBranchId)
                                       ->where('tbl_ref.ref_type', 'interbranch')
                                       ->whereIn('tbl_ref.ref_status', ['pending', 'attended']);
                          });
                })
                // EXCLUDE pets that have been referred OUT FROM this branch (active referrals)
                // These pets should NOT appear in the originating branch until referral is completed
                ->whereNotExists(function($subQuery) use ($activeBranchId) {
                    $subQuery->select(DB::raw(1))
                             ->from('tbl_ref')
                             ->whereColumn('tbl_ref.pet_id', 'tbl_pet.pet_id')
                             ->where('tbl_ref.ref_from', $activeBranchId)
                             ->where('tbl_ref.ref_type', 'interbranch')
                             ->whereIn('tbl_ref.ref_status', ['pending', 'attended']); // Only exclude active referrals, not completed
                });
            }
        });
    }

    protected $fillable = [
        'pet_weight',
        'pet_species',
        'pet_breed',
        'pet_birthdate',
        'pet_age',
        'pet_name',
        'pet_photo',
        'pet_gender',
        'pet_registration',
        'pet_temperature',
        'own_id',
        'user_id',
        'branch_id',
    ];

    /**
     * Get the owner of this pet.
     * Uses withoutGlobalScopes to ensure the owner is always accessible
     * regardless of branch scope restrictions (important for referrals).
     */
    public function owner()
    {
        return $this->belongsTo(Owner::class, 'own_id')->withoutGlobalScopes();
    }
    
    public function pets()
{
    return $this->hasMany(Pet::class, 'own_id');
}


    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'pet_id');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'pet_id', 'pet_id');
    }

    public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id');
}

public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class, 'pet_id', 'pet_id');
    }

    public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}

public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'pet_id', 'pet_id');
    }

    /**
     * Get active referrals (pending, attended) for this pet
     */
    public function activeReferrals()
    {
        return $this->hasMany(Referral::class, 'pet_id', 'pet_id')
            ->where('ref_type', 'interbranch')
            ->whereIn('ref_status', ['pending', 'attended']);
    }

    /**
     * Check if pet has an active outgoing referral FROM the current branch
     * Returns the referral if exists, null otherwise
     */
    public function getActiveOutgoingReferral()
    {
        $user = auth()->user();
        if (!$user) return null;

        $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
        $isInBranchMode = Session::get('branch_mode') === 'active';
        
        $activeBranchId = Session::get('active_branch_id');
        if (!$isSuperAdmin) {
            $activeBranchId = $user->branch_id;
        }

        if (!$activeBranchId) return null;

        return Referral::where('pet_id', $this->pet_id)
            ->where('ref_from', $activeBranchId)
            ->where('ref_type', 'interbranch')
            ->whereIn('ref_status', ['pending', 'attended'])
            ->with('refToBranch')
            ->first();
    }

    /**
     * Check if pet has an active incoming referral TO the current branch
     * Returns the referral if exists, null otherwise
     */
    public function getActiveIncomingReferral()
    {
        $user = auth()->user();
        if (!$user) return null;

        $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
        $isInBranchMode = Session::get('branch_mode') === 'active';
        
        $activeBranchId = Session::get('active_branch_id');
        if (!$isSuperAdmin) {
            $activeBranchId = $user->branch_id;
        }

        if (!$activeBranchId) return null;

        return Referral::where('pet_id', $this->pet_id)
            ->where('ref_to', $activeBranchId)
            ->where('ref_type', 'interbranch')
            ->whereIn('ref_status', ['pending', 'attended'])
            ->with('refFromBranch')
            ->first();
    }

    /**
     * Get referral status info for display
     * Returns array with 'type' (outgoing/incoming/none), 'label', 'branch_name', 'can_edit'
     */
    public function getReferralStatusInfo()
    {
        // Check for outgoing referral first (referred OUT from this branch)
        $outgoing = $this->getActiveOutgoingReferral();
        if ($outgoing) {
            return [
                'type' => 'outgoing',
                'label' => 'Referred to ' . ($outgoing->refToBranch->branch_name ?? 'Unknown Branch'),
                'branch_name' => $outgoing->refToBranch->branch_name ?? 'Unknown',
                'can_edit' => false,
                'status' => $outgoing->ref_status,
                'badge_class' => 'bg-orange-100 text-orange-800 border-orange-300'
            ];
        }

        // Check for incoming referral (referred IN to this branch)
        $incoming = $this->getActiveIncomingReferral();
        if ($incoming) {
            return [
                'type' => 'incoming',
                'label' => 'Referred from ' . ($incoming->refFromBranch->branch_name ?? 'Unknown Branch'),
                'branch_name' => $incoming->refFromBranch->branch_name ?? 'Unknown',
                'can_edit' => true, // Can edit in destination branch
                'status' => $incoming->ref_status,
                'badge_class' => 'bg-purple-100 text-purple-800 border-purple-300'
            ];
        }

        // No active referral
        return [
            'type' => 'none',
            'label' => null,
            'branch_name' => null,
            'can_edit' => true,
            'status' => null,
            'badge_class' => ''
        ];
    }

    /**
     * Check if this pet belongs to the current branch (created by users in this branch)
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
