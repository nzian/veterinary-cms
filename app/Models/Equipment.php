<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BranchDataScope;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory, BranchDataScope;
    protected $table = 'tbl_equipment';
    public $timestamps = false; // Disable timestamps
    
    protected $primaryKey = 'equipment_id';
    protected $fillable = [
        'equipment_name','equipment_description','equipment_quantity','equipment_category','equipment_image', 'branch_id', 'equipment_status', 'equipment_available', 'equipment_maintenance', 'equipment_out_of_service'
    ];

    public function branch() {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function getBranchIdColumn()
    {
        return 'user_id'; // We filter Pet records based on the user_id that created them
    }
}
