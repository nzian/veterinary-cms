<?php
// app/Models/ServiceProduct.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProduct extends Model
{
    use HasFactory;
    protected $table = 'tbl_service_products';
    
    protected $fillable = [
        'serv_id',
        'prod_id',
        'quantity_used',
        'is_billable'
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'is_billable' => 'boolean'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'serv_id', 'serv_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'prod_id', 'prod_id');
    }
}