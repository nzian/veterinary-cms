<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;
use Illuminate\Support\Facades\DB;
class Product extends Model
{
    use HasFactory, BranchDataScope;
    protected $table = 'tbl_prod';
   protected $primaryKey = 'prod_id'; 
   public $timestamps = false;

    protected $fillable = [
        'prod_name',
        'prod_description',
        'prod_price',
        'prod_category',
        'prod_type',
        'prod_stocks',
        'prod_reorderlevel',
        'prod_image',
        'prod_damaged',
        'prod_pullout',
        'prod_expiry',
        'branch_id',
        'manufacturer_id'
    ];

    public function services()
    {
        return $this->belongsToMany(
            Service::class,
            'tbl_service_products',
            'prod_id',
            'serv_id'
        )->withPivot('quantity_used', 'is_billable')
         ->withTimestamps();
    }

    public function serviceProducts()
    {
        return $this->hasMany(ServiceProduct::class, 'prod_id', 'prod_id');
    }


    public function orders()
    {
        return $this->belongsToMany(Order::class, 'tbl_ord_has_tbl_prod', 'tbl_prod_prod_id', 'tbl_ord_ord_id');
    }
    public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id');
}

public function manufacturer()
{
    return $this->belongsTo(Manufacturer::class, 'manufacturer_id', 'manufacturer_id');
}

public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }

    /**
     * Get all stock batches for this product
     */
    public function stockBatches()
    {
        return $this->hasMany(ProductStock::class, 'stock_prod_id', 'prod_id');
    }

    /**
     * Get all damage/pullout records
     */
    public function damagePullouts()
    {
        return $this->hasMany(ProductDamagePullout::class, 'pd_prod_id', 'prod_id');
    }

    /**
     * Get available stock (non-expired stock minus damage and pullout)
     */
    public function getAvailableStockAttribute()
    {
        $totalAvailable = $this->stockBatches()
            ->notExpired()
            ->get()
            ->sum('available_quantity');
        
        return max(0, $totalAvailable);
    }
    public function getCurrentStockAttribute()
    {
        $availableStock = $this->available_stock - $this->usage_from_inventory_transactions;
        
        return max(0, $availableStock);
    }
   /**
     * Get total stock from all batches (including expired)
     */
    public function getUsageFromInventoryTransactionsAttribute()
    {
        // Assumes tbl_inventory_transactions has columns: prod_id, transaction_type, quantity_changed
        // and that usage is indicated by a specific transaction_type, e.g., 'used' or similar
        // Adjust 'used' to your actual usage type if different
        if($this->prod_type === 'Consumable'){
            return -1 * DB::table('tbl_inventory_transactions')
            ->where('prod_id', $this->prod_id)
            ->where('transaction_type', 'service_usage') // Adjust this as needed        
            ->sum('quantity_change') ?? 0;
        }
        else {
            return -1 * DB::table('tbl_inventory_transactions')
            ->where('prod_id', $this->prod_id)
            ->where('transaction_type', 'order_usage') // Adjust this as needed        
            ->sum('quantity_change') ?? 0;
        }
        
    } 

    public function getExpiredDateAttribute()
    {
        return DB::table('product_stock')
            ->where('stock_prod_id', $this->prod_id)
            ->where('quantity', '>', 0)
            ->where('expire_date', '>', now())
            ->orderBy('expire_date', 'asc')
            ->first()?->expire_date;
    }

    /**
     * Get total stock from all batches (including expired)
     */
    public function getTotalStockFromBatchesAttribute()
    {
        return $this->stockBatches()->sum('quantity');
    }

    /**
     * Check if all stock batches are expired
     */
    public function getAllExpiredAttribute()
    {
        $totalBatches = $this->stockBatches()->count();
        if ($totalBatches === 0) {
            return false; // No batches means not expired (just no stock)
        }
        
        $expiredBatches = $this->stockBatches()->expired()->count();
        return $totalBatches > 0 && $totalBatches === $expiredBatches;
    }

    /**
     * Check if product is out of stock (available stock is 0 or less)
     */
    public function getIsOutOfStockAttribute()
    {
        return ($this->available_stock - $this->usage_from_inventory_transactions) <= 0;
    }

    /**
     * Check if product should be disabled (expired or out of stock)
     */
    public function getIsDisabledAttribute()
    {
        return $this->is_out_of_stock || $this->all_expired;
    }

    /**
     * Get the status label for the product
     */
    public function getStockStatusLabelAttribute()
    {
        if ($this->all_expired) {
            return 'Expired';
        }
        if ($this->is_out_of_stock) {
            return 'Out of Stock';
        }
        return 'Available';
    }

    /**
     * Get linked consumable products (e.g., syringe linked to vaccine)
     * These consumables will be auto-deducted when this product is used
     */
    public function linkedConsumables()
    {
        return $this->belongsToMany(
            Product::class,
            'tbl_product_consumables',
            'product_id',
            'consumable_product_id'
        )->withPivot('quantity')->withTimestamps();
    }

    /**
     * Get products that use this product as a consumable
     * (Inverse relationship - e.g., which vaccines use this syringe)
     */
    public function usedByProducts()
    {
        return $this->belongsToMany(
            Product::class,
            'tbl_product_consumables',
            'consumable_product_id',
            'product_id'
        )->withPivot('quantity')->withTimestamps();
    }

    /**
     * Get the product consumable links directly
     */
    public function productConsumables()
    {
        return $this->hasMany(ProductConsumable::class, 'product_id', 'prod_id');
    }

}
