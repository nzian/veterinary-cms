<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDamagePullout extends Model
{
    use HasFactory;

    protected $table = 'product_damage_pullout';

    protected $fillable = [
        'pd_prod_id',
        'stock_id',
        'pullout_quantity',
        'damage_quantity',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'pullout_quantity' => 'integer',
        'damage_quantity' => 'integer',
    ];

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'pd_prod_id', 'prod_id');
    }

    /**
     * Get the stock batch
     */
    public function stock()
    {
        return $this->belongsTo(ProductStock::class, 'stock_id');
    }

    /**
     * Get the user who created this record
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get total affected quantity (damage + pullout)
     */
    public function getTotalAffectedAttribute()
    {
        return $this->pullout_quantity + $this->damage_quantity;
    }
}
