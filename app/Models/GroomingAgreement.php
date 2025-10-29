<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroomingAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'owner_id',
        'pet_id',
        'signer_name',
        'signature_path',
        'color_markings',
        'history_before',
        'history_after',
        'consent_text_version',
        'ip_address',
        'user_agent',
        'signed_at',
        'checkbox_acknowledge',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'checkbox_acknowledge' => 'boolean',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }
}
