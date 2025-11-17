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
        'ref_to',
        'appoint_id',
        'visit_id',
        'pet_id',
        'medical_history',
        'tests_conducted',
        'medications_given',
    ];

    public $timestamps = false;


    // Optional alias: if you prefer to use $referral->referredTo instead of $referral->branch
    
    public function refByBranch()
    {
        return $this->belongsTo(User::class, 'ref_by', 'user_id');
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
    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }

}
