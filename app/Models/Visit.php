<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;
use App\Enums\PatientType;


class Visit extends Model
{
    use HasFactory;
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

    // We'll handle patient_type normalization in accessors/mutators to
    // tolerate different casings/values in the database (e.g. 'outpatient').
    protected $casts = [
        // keep other casts here if needed
    ];

    /**
     * Accessor: return PatientType enum instance when possible, otherwise raw value.
     */
    public function getPatientTypeAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return PatientType::from($value);
        } catch (\ValueError $e) {
            // Try a normalized form (capitalize first letter)
            $normalized = ucfirst(strtolower($value));
            try {
                return PatientType::from($normalized);
            } catch (\ValueError $e) {
                // Last resort: return raw string so views/controllers can handle it
                return $value;
            }
        }
    }

    /**
     * Mutator: accept enum instance or string; normalize to enum backing value when possible.
     */
    public function setPatientTypeAttribute($value)
    {
        if ($value instanceof PatientType) {
            $this->attributes['patient_type'] = $value->value;
            return;
        }

        $val = (string) $value;
        foreach (PatientType::cases() as $case) {
            if (strcasecmp($case->value, $val) === 0 || strcasecmp($case->name, $val) === 0) {
                $this->attributes['patient_type'] = $case->value;
                return;
            }
        }

        // fallback to raw value
        $this->attributes['patient_type'] = $val;
    }


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

    public function billing()
    {
        return $this->hasOne(Billing::class, 'visit_id', 'visit_id');
    }

    public function groomingAgreement()
    {
        return $this->hasOne(GroomingAgreement::class, 'visit_id');
    }

    public function initialAssessment()
{
    return $this->hasOne(\App\Models\InitialAssessment::class, 'visit_id', 'visit_id');
}
}
