<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductStock extends Model
{
    use HasFactory;

    protected $table = 'product_stock';

    protected $fillable = [
        'stock_prod_id',
        'batch',
        'quantity',
        'expire_date',
        'note',
        'created_by',
    ];

    protected $casts = [
        'expire_date' => 'date',
        'quantity' => 'integer',
    ];

    /**
     * Get the product that owns the stock
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'stock_prod_id', 'prod_id');
    }

    /**
     * Get the user who created this stock entry
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get all damage/pullout records for this stock
     */
    public function damagePullouts()
    {
        return $this->hasMany(ProductDamagePullout::class, 'stock_id');
    }

    /**
     * Get available quantity (original quantity minus damage and pullout)
     */
    public function getAvailableQuantityAttribute()
    {
        $damagePulloutTotal = $this->damagePullouts()
            ->sum(\DB::raw('pullout_quantity + damage_quantity'));
        
        return max(0, $this->quantity - $damagePulloutTotal);
    }

    /**
     * Check if this stock is expired
     */
    public function isExpired()
    {
        return $this->expire_date < Carbon::today();
    }

    /**
     * Scope to get only non-expired stock
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expire_date', '>=', Carbon::today());
    }

    /**
     * Scope to get expired stock
     */
    public function scopeExpired($query)
    {
        return $query->where('expire_date', '<', Carbon::today());
    }

    /**
     * Scope to get stock with available quantity
     */
    public function scopeWithAvailableQuantity($query)
    {
        return $query->select('product_stock.*')
            ->selectRaw('product_stock.quantity - COALESCE(SUM(product_damage_pullout.pullout_quantity + product_damage_pullout.damage_quantity), 0) as available_quantity')
            ->leftJoin('product_damage_pullout', 'product_stock.id', '=', 'product_damage_pullout.stock_id')
            ->groupBy('product_stock.id');
    }
}
