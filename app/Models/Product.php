<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
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

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'tbl_ord_has_tbl_prod', 'tbl_prod_prod_id', 'tbl_ord_ord_id');
    }
    public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id');
}

}
