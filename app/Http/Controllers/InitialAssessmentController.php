<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InitialAssessmentController extends Model
{
    protected $table = 'initial_assessments';
    
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'visit_id',
        'pet_id',
        'is_sick',
        'been_treated',
        'table_food',
        'feeding_frequency',
        'heartworm_preventative',
        'injury_accident',
        'allergies',
        'surgery_past_30',
        'current_meds',
        'appetite_normal',
        'diarrhoea',
        'vomiting',
        'drinking_unusual',
        'weakness',
        'gagging',
        'coughing',
        'sneezing',
        'scratching',
        'shaking_head',
        'urinating_unusual',
        'limping',
        'scooting',
        'seizures',
        'bad_breath',
        'discharge',
        'ate_this_morning',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(\App\Models\VisitRecord::class, 'visit_id', 'visit_id');
    }

    public function pet()
    {
        return $this->belongsTo(\App\Models\Pet::class, 'pet_id', 'pet_id');
    }
}