<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalHistory extends Model
{
    use HasFactory;

    protected $table = 'tbl_medical_history';

    protected $fillable = [
        'pet_id',
        'visit_date',
        'diagnosis',
        'treatment',
        'medication',
        'veterinarian_name',
        'follow_up_date',
        'notes'
    ];

    protected $casts = [
        'visit_date' => 'date',
        'follow_up_date' => 'date',
    ];

    /**
     * Get the pet that owns the medical history.
     */
    public function pet()
    {
        return $this->belongsTo(Pet::class, 'pet_id', 'pet_id');
    }
}