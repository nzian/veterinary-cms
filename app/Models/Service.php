<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BranchDataScope;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, BranchDataScope;

    protected $table = 'tbl_serv';
    protected $primaryKey = 'serv_id'; // <-- tell Laravel the real primary key

    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';

    protected $fillable = [
        'serv_name',
        'serv_description',
        'serv_price',
        'serv_type',
        'branch_id',
    ];

    // Only allow these service types
    public static $allowedTypes = [
        'boarding',
        'check-up',
        'deworming',
        'diagnostics',
        'emergency',
        'grooming',
        'surgical',
        'vaccination',
    ];

    public static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (!in_array(strtolower($model->serv_type), self::$allowedTypes)) {
                throw new \InvalidArgumentException('Invalid service type.');
            }
        });
    }

    

     public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'tbl_service_products',
            'serv_id',
            'prod_id'
        )->withPivot('quantity_used', 'is_billable')
         ->withTimestamps();
    }

    public function serviceProducts()
    {
        return $this->hasMany(ServiceProduct::class, 'serv_id', 'serv_id');
    }

    public function servicesWithProduct()
{
    // This loads the Service, and for each attached service (pivot), it tries to load 
    // the Product specified by the pivot's prod_id column.
    return $this->belongsToMany(Service::class, 'tbl_appoint_serv', 'appoint_id', 'serv_id')
                ->withPivot('prod_id', 'vacc_next_dose', 'vacc_batch_no', 'vacc_notes')
                ->with([
                    'vaccineProduct' => function ($query) {
                        // This ensures the custom 'vaccineProduct' relation (defined on the Service model in step 2) is loaded.
                        // However, since the pivot column is named 'prod_id', we use that directly.
                    }
                ]);
}

    // Service.php
public function appointments()
{
    return $this->belongsToMany(Appointment::class, 'tbl_appoint_serv', 'serv_id', 'appoint_id');
}

    public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id');
}

 public function getBranchIdColumn()
    {
        return 'branch_id'; // It should use branch_id directly, not user_id
    }
}
