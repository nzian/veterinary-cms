<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VisitService extends Pivot
{
    protected $table = 'tbl_visit_service';
    
    protected $casts = [
        'skin_issues' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'visit_id',
        'serv_id',
        'coat_condition',
        'skin_issues',
        'notes'
    ];
}