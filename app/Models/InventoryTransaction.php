<?php
// app/Models/InventoryTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;
    protected $table = 'tbl_inventory_transactions';
    protected $primaryKey = 'transaction_id';
    
    protected $fillable = [
        'prod_id',
        'appoint_id',
        'serv_id',
        'quantity_change',
        'transaction_type',
        'reference',
        'notes',
        'batch_id',
        'performed_by'
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'prod_id', 'prod_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appoint_id', 'appoint_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'serv_id', 'serv_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');
    }
}