<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;

class ContinuityCare extends Model
{
    use HasFactory, BranchDataScope;
    protected $table = 'continuity_care';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'patient_id',
        'care_plan',
        'status',
        'branch_id',
        // add other fields as needed
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function getBranchIdColumn()
    {
        return 'branch_id';
    }
}
