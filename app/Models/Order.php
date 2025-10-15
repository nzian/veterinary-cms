<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'tbl_ord';
    protected $primaryKey = 'ord_id';
    public $timestamps = true;

    protected $fillable = [
        'ord_quantity',
        'ord_date',
        'user_id',
        'prod_id',
        'ord_price',
        'ord_total'
        
    ];

    protected $dates = [
        'ord_date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
    'ord_date' => 'datetime', 
];

    /**
     * Get the user who created this order
     */
public function owner()
    {
        // Be explicit about the relationship
        return $this->belongsTo(Owner::class, 'own_id', 'own_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the product for this order
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'prod_id');
    }

    /**
     * Get the billing record for this order (if exists)
     */
    public function billing()
    {
        return $this->hasOne(Billing::class, 'ord_id', 'ord_id');
    }

    /**
     * Get the payment record for this order (for POS sales)
     */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'bill_id', 'ord_id');
    }

    /**
     * Scope for products only (not services)
     */
    public function scopeProducts($query)
    {
        return $query->whereHas('product', function($q) {
            $q->where('prod_category', 'Product');
        });
    }

    /**
     * Scope for services only
     */
    public function scopeServices($query)
    {
        return $query->whereHas('product', function($q) {
            $q->where('prod_category', 'Service');
        });
    }

    /**
     * Get total amount for this order
     */
    public function getTotalAttribute()
    {
        if ($this->ord_total) {
            return $this->ord_total;
        }
        
        if ($this->product) {
            return $this->ord_quantity * $this->product->prod_price;
        }
        
        return 0;
    }

    
}