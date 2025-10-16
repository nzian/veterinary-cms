<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;

class Product extends Model
{
    use BranchDataScope;
    protected $table = 'tbl_prod';
   protected $primaryKey = 'prod_id'; 
   public $timestamps = false;

    protected $fillable = [
        'prod_name',
        'prod_description',
        'prod_price',
        'prod_category',
        'prod_stocks',
        'prod_reorderlevel',
        'prod_image',
        'prod_damaged',
        'prod_pullout',
        'prod_expiry',
        'branch_id',
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

}
