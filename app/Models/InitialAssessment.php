<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InitialAssessment extends Model
{
    //
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
}
