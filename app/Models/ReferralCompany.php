<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCompany extends Model
{
    //
    protected $table = 'tbl_referral_companies';
    protected $fillable = [
        'name',
        'contact_number',
        'address',
        'email',
        'website',
        'description',
        'contact_person',
        'contact_person_number',
        'branch_id',
        'is_active',
    ];

    public function branch()
    {
        return $this->hasOne(Branch::class, 'branch_id', 'branch_id');
    }
}
