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
     */
    protected static function booted()
    {
        static::addGlobalScope('branch_pet_scope', function (Builder $builder) {
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
                    // Include pets created by users in this branch
                    $query->whereIn('tbl_pet.user_id', $branchUserIds)
                          // OR pets that have active interbranch referrals to this branch
                          ->orWhereExists(function($subQuery) use ($activeBranchId) {
                              $subQuery->select(DB::raw(1))
                                       ->from('tbl_ref')
                                       ->whereColumn('tbl_ref.pet_id', 'tbl_pet.pet_id')
                                       ->where('tbl_ref.ref_to', $activeBranchId)
                                       ->where('tbl_ref.ref_type', 'interbranch')
                                       ->whereIn('tbl_ref.ref_status', ['pending', 'attended', 'completed']);
                          });
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

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'own_id');
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
}
