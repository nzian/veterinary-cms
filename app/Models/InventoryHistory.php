<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryHistory extends Model
{
    use HasFactory;

    // If your DB table uses a different name, update this accordingly
    protected $table = 'tbl_inventory_history';

    protected $primaryKey = 'id';

    protected $fillable = [
        'prod_id',
        'type',
        'quantity',
        'reference',
        'user_id',
        'notes',
    ];

    public $timestamps = true;
}
