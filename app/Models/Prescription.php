<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;
class Prescription extends Model
{
    use BranchDataScope;
    protected $table = 'tbl_prescription'; // make sure your table name is correct
    protected $primaryKey = 'prescription_id';
    protected $fillable = ['pet_id', 'prescription_date', 'medication', 'notes',  'user_id', 'branch_id', 'differential_diagnosis'];

    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }
  
public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id'); // adjust 'branch_id' if your column name differs
}

public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}

public function getVeterinarianAttribute()
{
    // Get the first veterinarian from this prescription's branch
    if ($this->branch) {
        return $this->branch->users()
            ->where('user_role', 'veterinarian')
            ->first();
    }
    return null;
}

public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }

}
