<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;


class Visit extends Model
{
    protected $fillable = [
        'visit_date',
        'pet_id',
        'user_id',
        'weight',
        'temperature',
        'patient_type',
        'visit_status',
        'workflow_status',
        // add other fields as needed
    ];
    protected $table = 'tbl_visit_record';
    protected $primaryKey = 'visit_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $keyType = 'int';


    public function services()
    {
        return $this->belongsToMany(Service::class, 'tbl_visit_service', 'visit_id', 'serv_id')->withTimestamps();
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function getBranchIdColumn()
    {
        return 'user_id';
    }

    public function groomingAgreement()
    {
        return $this->hasOne(GroomingAgreement::class, 'visit_id');
    }
}
