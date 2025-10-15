<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;



class Appointment extends Model
{
    protected $table = 'tbl_appoint';
     protected $primaryKey = 'appoint_id'; 
    public $incrementing = true; 
    protected $keyType = 'int';
    public $timestamps = true;

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


     public function services()
    {
        return $this->belongsToMany(
            Service::class, 
            'tbl_appoint_serv', 
            'appoint_id', 
            'serv_id'
        )
            ->using(\App\Models\AppointServ::class) // Tell Eloquent to use the custom pivot model
            ->withPivot([
                'prod_id',
                'vacc_next_dose',
                'vacc_batch_no',
                'vacc_notes'
            ]);
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



}
