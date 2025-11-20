<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'tbl_bill';
    
    // ✅ Specify the primary key (adjust if different)
    protected $primaryKey = 'bill_id';
    
    public $timestamps = false; // Set to true if you have created_at/updated_at columns
    public $incrementing = true;
    protected $fillable = [
        'bill_date',
        'ord_id',
        'visit_id',
        'bill_status',
        'branch_id'
    ];

    // Add default values
    protected $attributes = [
        'bill_status' => 'Pending',
    ];

     public function payments()
    {
        return $this->hasMany(Payment::class, 'bill_id', 'bill_id');
    }

    public function order()
{
    return $this->belongsTo(Order::class, 'ord_id', 'ord_id');
}

    public function orders()
    {
        return $this->hasMany(Order::class, 'bill_id', 'bill_id'); // all product orders linked to this bill
    }


    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id', 'visit_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function payment()
{
    return $this->hasOne(Payment::class, 'bill_id', 'bill_id');
}



}
