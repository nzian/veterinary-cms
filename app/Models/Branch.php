<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'tbl_branch';
 public $timestamps = false;
    protected $fillable = [
        'branch_address',
        'branch_contactNum',
        'branch_name',
    ];

    

    protected $primaryKey = 'branch_id';
public $incrementing = true;
public $keyType = 'int';



    public function users()
{
    return $this->hasMany(User::class, 'branch_id', 'branch_id');
}

    // In your Branch model
public function products()
{
    return $this->hasMany(Product::class, 'branch_id', 'branch_id');
}

public function services() 
{
    return $this->hasMany(Service::class, 'branch_id', 'branch_id');
}

public function equipment()
{
    return $this->hasMany(Equipment::class, 'branch_id', 'branch_id');
}

    
}
