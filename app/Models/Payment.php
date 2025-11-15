<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'tbl_pay';
    
    protected $primaryKey = 'pay_id';
    
    public $timestamps = true; // Enable timestamps for created_at and updated_at
    
    protected $fillable = [
        'pay_change',
        'payment_type',
        'pay_cashAmount',
        'pay_total',
        'bill_id',
        'ord_id',
        'payment_date',
        'transaction_id',
        'status'
    ];

    protected $casts = [
        'pay_change' => 'decimal:2',
        'pay_cashAmount' => 'decimal:2',
        'pay_total' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    // Default values
    protected $attributes = [
        'payment_type' => 'full',
        'status' => 'pending',
    ];

    /**
     * Relationship: Payment belongs to a Billing
     */
    public function billing()
    {
        return $this->belongsTo(Billing::class, 'bill_id', 'bill_id');
    }

    /**
     * Relationship: Payment belongs to an Order (if applicable)
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'ord_id', 'ord_id');
    }

    /**
     * Scope: Get only paid payments
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Get only pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get payments for a specific bill
     */
    public function scopeForBill($query, $billId)
    {
        return $query->where('bill_id', $billId);
    }

    /**
     * Accessor: Get formatted payment amount
     */
    public function getFormattedTotalAttribute()
    {
        return '₱' . number_format($this->pay_total, 2);
    }

    /**
     * Accessor: Get formatted change
     */
    public function getFormattedChangeAttribute()
    {
        return '₱' . number_format($this->pay_change, 2);
    }

    /**
     * Accessor: Check if this is a full payment
     */
    public function getIsFullPaymentAttribute()
    {
        return $this->payment_type === 'full';
    }

    /**
     * Accessor: Check if this is a partial payment
     */
    public function getIsPartialPaymentAttribute()
    {
        return $this->payment_type === 'partial';
    }
}