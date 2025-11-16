<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VisitService extends Pivot
{
    protected $table = 'tbl_visit_service';
    
    protected $casts = [
        'skin_issues' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $fillable = [
        'visit_id',
        'serv_id',
        'coat_condition',
        'skin_issues',
        'notes',
        'status',
        'completed_at'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'serv_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();

        // Check if all services are completed
        $this->visit->checkAllServicesCompleted();
    }
}