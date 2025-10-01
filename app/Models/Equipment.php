<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'tbl_equipment';
    public $timestamps = false; // Disable timestamps
    
    protected $primaryKey = 'equipment_id';
    protected $fillable = [
        'equipment_name','equipment_description','equipment_quantity','equipment_category','equipment_image', 'branch_id'
    ];

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
