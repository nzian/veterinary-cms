<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $table = 'tbl_medical_history';

    protected $fillable = [
        'pet_id',
        'visit_date',
        'diagnosis',
        'treatment',
        'medication',
        'veterinarian_name',
        'follow_up_date',
        'notes',
        'differential_diagnosis',
        'user_id',
        'branch_id',
    ];

    /**
     * Override scope to include medical history for pets with active interbranch referrals
     */
    protected static function booted()
    {
        static::addGlobalScope('branch_medical_history_scope', function (Builder $builder) {
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
                    // Include medical history created by users in this branch
                    $query->whereIn('tbl_medical_history.user_id', $branchUserIds)
                          // OR medical history for pets that have active interbranch referrals to this branch
                          ->orWhereExists(function($subQuery) use ($activeBranchId) {
                              $subQuery->select(DB::raw(1))
                                       ->from('tbl_ref')
                                       ->whereColumn('tbl_ref.pet_id', 'tbl_medical_history.pet_id')
                                       ->where('tbl_ref.ref_to', $activeBranchId)
                                       ->where('tbl_ref.ref_type', 'interbranch')
                                       ->whereIn('tbl_ref.ref_status', ['pending', 'attended', 'completed']);
                          });
                });
            }
        });
    }

    protected $casts = [
        'visit_date' => 'date',
        'follow_up_date' => 'date',
    ];

    /**
     * Get the pet that owns the medical history.
     */
    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }

   public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}
}