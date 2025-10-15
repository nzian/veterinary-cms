<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointServ extends Pivot
{
    // Define the actual pivot table name
    protected $table = 'tbl_appoint_serv'; 
    
    // Set the primary key since the default is a composite key, but your table has appoint_serv_id
    protected $primaryKey = 'appoint_serv_id';
    public $incrementing = true;
    public $timestamps = false; // Your pivot table likely lacks timestamps based on schema

    protected $fillable = [
        'appoint_id', 
        'serv_id', 
        // These fields MUST exist in your database's tbl_appoint_serv table (as per migration earlier)
        'prod_id', 
         'vet_user_id',
        'vacc_next_dose', 
        'vacc_batch_no', 
        'vacc_notes' 
    ];

    /**
     * Define the relationship to the Product model using the pivot's prod_id.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'prod_id', 'prod_id');
    }

    public function veterinarian()
    {
        return $this->belongsTo(User::class, 'vet_user_id', 'user_id');
    }
    
}