<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $table = 'tbl_prescription'; // make sure your table name is correct
    protected $primaryKey = 'prescription_id';
    protected $fillable = ['pet_id', 'prescription_date', 'medication', 'notes'];

    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }
  
public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id'); // adjust 'branch_id' if your column name differs
}

}
