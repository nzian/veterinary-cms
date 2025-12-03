<?php
// app/Models/ServiceEquipment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceEquipment extends Model
{
    use HasFactory;
    
    protected $table = 'tbl_service_equipment';
    
    protected $fillable = [
        'serv_id',
        'equipment_id',
        'quantity_used',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'quantity_used' => 'integer'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'serv_id', 'serv_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
