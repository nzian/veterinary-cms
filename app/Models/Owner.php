<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Traits\BranchDataScope;
use App\Models\Pet;
use App\Models\Branch;

class Owner extends Model
{
    use HasFactory, BranchDataScope;
    protected $table = 'tbl_own'; // ✅ ensure it points to the correct table
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
     * Override the BranchDataScope to include owners whose pets have active interbranch referrals
     */
    protected static function booted()
    {
        static::addGlobalScope('branch_owner_scope', function (Builder $builder) {
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
                    // Include owners created by users in this branch
                    $query->whereIn('tbl_own.user_id', $branchUserIds)
                          // OR owners whose pets have active interbranch referrals to this branch
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
public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }
}
