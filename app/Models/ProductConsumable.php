<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductConsumable extends Model
{
    use HasFactory;

    protected $table = 'tbl_product_consumables';

    protected $fillable = [
        'product_id',
        'consumable_product_id',
        'quantity',
    ];

    /**
     * Get the parent product (e.g., vaccine)
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'prod_id');
    }

    /**
     * Get the linked consumable product (e.g., syringe)
     */
    public function consumableProduct()
    {
        return $this->belongsTo(Product::class, 'consumable_product_id', 'prod_id');
    }
}
