<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'tbl_pay';
      protected $primaryKey = 'pay_id'; // Add this line
 public $timestamps = false; // <<< Add this line
     protected $fillable = [
        'bill_id',
        'pay_total',
        'pay_cashAmount', 
        'pay_change',
    ];


    // In Payment.php
public function bill()
{
    return $this->belongsTo(Billing::class, 'bill_id', 'bill_id');
}


}
