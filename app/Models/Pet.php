<?php

namespace App\Models;
use App\Models\Branch;
use App\Models\Owner;
use App\Models\Appointment;



use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $table = 'tbl_pet';
    protected $primaryKey = 'pet_id'; 

    public $incrementing = true;        

    public $timestamps = false;

    protected $fillable = [
        'pet_weight',
        'pet_species',
        'pet_breed',
        'pet_birthdate',
        'pet_age',
        'pet_name',
        'pet_photo',
        'pet_gender',
        'pet_registration',
        'pet_temperature',
        'own_id'
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'own_id');
    }
    
    public function pets()
{
    return $this->hasMany(Pet::class, 'own_id');
}


    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'pet_id');
    }

    public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id');
}

public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class, 'pet_id', 'pet_id');
    }


}
