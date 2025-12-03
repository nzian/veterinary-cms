<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Traits\BranchDataScope;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory, BranchDataScope;
    protected $table = 'tbl_appoint';
     protected $primaryKey = 'appoint_id'; 
    public $incrementing = true; 
    protected $keyType = 'int';
    public $timestamps = true;

    /**
     * Override the BranchDataScope to include appointments for pets with active interbranch referrals
     */
    protected static function booted()
    {
        static::addGlobalScope('branch_appointment_scope', function (Builder $builder) {
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
                    // Include appointments created by users in this branch
                    $query->whereIn('tbl_appoint.user_id', $branchUserIds)
                          // OR appointments for pets that have active interbranch referrals to this branch
                          ->orWhereExists(function($subQuery) use ($activeBranchId) {
                              $subQuery->select(DB::raw(1))
                                       ->from('tbl_ref')
                                       ->whereColumn('tbl_ref.pet_id', 'tbl_appoint.pet_id')
                                       ->where('tbl_ref.ref_to', $activeBranchId)
                                       ->where('tbl_ref.ref_type', 'interbranch')
                                       ->whereIn('tbl_ref.ref_status', ['pending', 'attended', 'completed']);
                          });
                });
            }
        });
    }

protected $casts = [
    'change_history' => 'array',
];

    protected $fillable = [
        'appoint_time',
        'appoint_status',
        'appoint_date',
        'appoint_description',
        'appoint_type',
        'pet_id',
        'ref_id',
        'serv_id',
        'user_id',
        'tbl_bill_bill_id',
        'tbl_bill_pet_pay_id',
        'tbl_pet_pet_id',
    ];


    // Inside Appointment Model

public function services()
{
    return $this->belongsToMany(
        \App\Models\Service::class, 
        'tbl_appoint_serv', 
        'appoint_id', 
        'serv_id'
    )
        ->using(\App\Models\AppointServ::class)
        // KEEP ONLY THE PIVOT FIELDS HERE
        ->withPivot([
            'prod_id',
            'vet_user_id',
            'vacc_next_dose',
            'vacc_batch_no',
            'vacc_notes'
        ]); 
        // !!! REMOVE ->with('product') here !!!
}

    
    public function pet()
{
    return $this->belongsTo(Pet::class, 'pet_id', 'pet_id'); // ✅ correct reference
}
//public function service(){return $this->belongsTo(Service::class, 'serv_id', 'serv_id');}

    public function referral()
{
    return $this->hasOne(Referral::class, 'appoint_id', 'appoint_id');
}


    public function bill()
    {
        return $this->belongsTo(Billing::class, 'tbl_bill_bill_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'tbl_bill_pet_pay_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function appointment()
{
    return $this->belongsTo(Appointment::class,  'appoint_id');
}

public function products()
{
    return $this->hasMany(Order::class, 'appoint_id', 'appoint_id');
}

public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }

}
