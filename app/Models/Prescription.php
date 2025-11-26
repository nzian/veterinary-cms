<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Traits\BranchDataScope;
class Prescription extends Model
{
    use HasFactory, BranchDataScope;
    protected $table = 'tbl_prescription'; // make sure your table name is correct
    protected $primaryKey = 'prescription_id';
    protected $fillable = ['pet_id', 'prescription_date', 'medication', 'notes',  'user_id', 'branch_id', 'differential_diagnosis'];

    /**
     * Override the BranchDataScope to include prescriptions for pets with active interbranch referrals
     */
    protected static function booted()
    {
        static::addGlobalScope('branch_prescription_scope', function (Builder $builder) {
            $user = auth()->user();
            $isSuperAdmin = $user && strtolower(trim($user->user_role)) === 'superadmin';
            $isInBranchMode = Session::get('branch_mode') === 'active';
            $activeBranchId = Session::get('active_branch_id');

            // Super Admin in Global Mode: no filter
            if ($isSuperAdmin && !$isInBranchMode) {
                return;
            }

            // Apply filter for normal users or Super Admin in branch mode
            if ($activeBranchId) {
                $branchUserIds = \App\Models\User::where('branch_id', $activeBranchId)->pluck('user_id');
                
                $builder->where(function($query) use ($branchUserIds, $activeBranchId) {
                    // Include prescriptions created by users in this branch
                    $query->whereIn('tbl_prescription.user_id', $branchUserIds)
                          // OR prescriptions for pets that have active interbranch referrals to this branch
                          ->orWhereExists(function($subQuery) use ($activeBranchId) {
                              $subQuery->select(DB::raw(1))
                                       ->from('tbl_ref')
                                       ->whereColumn('tbl_ref.pet_id', 'tbl_prescription.pet_id')
                                       ->where('tbl_ref.ref_to', $activeBranchId)
                                       ->where('tbl_ref.ref_type', 'interbranch')
                                       ->whereIn('tbl_ref.ref_status', ['pending', 'attended', 'completed']);
                          });
                });
            }
        });
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }
  
public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id'); // adjust 'branch_id' if your column name differs
}

public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}

public function getVeterinarianAttribute()
{
    // Get the first veterinarian from this prescription's branch
    if ($this->branch) {
        return $this->branch->users()
            ->where('user_role', 'veterinarian')
            ->first();
    }
    return null;
}

public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }

}
