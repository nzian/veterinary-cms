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
        'branch_id'
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

    /**
     * Get total stock from all batches (including expired)
     */
    public function getTotalStockFromBatchesAttribute()
    {
        return $this->stockBatches()->sum('quantity');
    }

}
