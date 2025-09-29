<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
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

   

    // Service.php
public function appointments()
{
    return $this->belongsToMany(Appointment::class, 'tbl_appoint_serv', 'serv_id', 'appoint_id');
}

    public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id');
}

}
