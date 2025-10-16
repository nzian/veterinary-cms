<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;
use App\Models\Pet;
use App\Models\Branch;

class Owner extends Model
{
    use HasFactory, BranchDataScope;
    protected $table = 'tbl_own'; // ✅ ensure it points to the correct table
    protected $primaryKey = 'own_id';
    public $timestamps = false;

    protected $fillable = [
        'own_name',
        'own_contactnum', 
        'own_location',
        'user_id',
        'branch_id',
    ];

    public function pets()
    {
        return $this->hasMany(Pet::class, 'own_id', 'own_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}
public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }
}
