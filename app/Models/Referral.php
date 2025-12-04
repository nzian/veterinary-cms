<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Referral extends Model
{
    use HasFactory;
    protected $table = 'tbl_ref';
    protected $primaryKey = 'ref_id';

    protected $fillable = [
        'ref_date',
        'ref_description',
        'ref_by',
        'ref_from',
        'ref_to',
        'appoint_id',
        'visit_id',
        'pet_id',
        'medical_history',
        'tests_conducted',
        'medications_given',
        'ref_status',
        'ref_type',
        'ref_company_id',
        'referred_visit_id',
        'external_clinic_name',
    ];

    public $timestamps = false;


    // Optional alias: if you prefer to use $referral->referredTo instead of $referral->branch
    
    public function refByBranch()
    {
        return $this->belongsTo(User::class, 'ref_by', 'user_id');
    }

    public function refFromBranch()
    {
        return $this->belongsTo(Branch::class, 'ref_from', 'branch_id');
    }

    public function refToBranch()
    {
        return $this->belongsTo(Branch::class, 'ref_to', 'branch_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appoint_id', 'appoint_id');
    }
    
    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id', 'visit_id');
    }
    
    /**
     * Get the pet for this referral.
     * Uses withoutGlobalScopes to ensure the pet is always loaded
     * regardless of branch scope restrictions (important for viewing referrals).
     */
    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id')->withoutGlobalScopes();
    }

    public function referralCompany()
    {
        return $this->belongsTo(ReferralCompany::class, 'ref_company_id', 'id');
    }

    public function referredVisit()
    {
        return $this->belongsTo(Visit::class, 'referred_visit_id', 'visit_id');
    }

}
