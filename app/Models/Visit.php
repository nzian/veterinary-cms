<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchDataScope;
use App\Enums\PatientType;
use App\Services\VisitBillingService;


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

public function services()
{
    return $this->belongsToMany(Service::class, 'tbl_visit_service', 'visit_id', 'serv_id')
                ->using(VisitService::class)
                ->withPivot(['status', 'completed_at', 'coat_condition', 'skin_issues', 'notes'])
                ->withTimestamps();
}

public function pendingServices()
{
    // Include services with NULL status or 'pending' status
    return $this->services()->where(function($query) {
        $query->wherePivot('status', 'pending')
              ->orWhereRaw('tbl_visit_service.status IS NULL');
    });
}

public function completedServices()
{
    return $this->services()->wherePivot('status', 'completed');
}

public function checkAllServicesCompleted()
{
    // Refresh to get latest service statuses
    $this->refresh();
    
    // Get all services for this visit directly from pivot table to ensure fresh data
    $pivotRecords = \Illuminate\Support\Facades\DB::table('tbl_visit_service')
        ->where('visit_id', $this->visit_id)
        ->get();
    
    // If no services, return false
    if ($pivotRecords->isEmpty()) {
        return false;
    }
    
    // Check if ALL services are completed
    $allCompleted = $pivotRecords->every(function($pivot) {
        $status = $pivot->status ?? null;
        return $status === 'completed';
    });
    
    if ($allCompleted) {
        // Update workflow status to Completed
        $this->workflow_status = 'Completed';
        $this->visit_status = 'arrived'; // Keep as arrived until payment
        $this->save();
        
        // Refresh to check if billing already exists
        $this->refresh();
        
        // Check if billing exists by querying directly
        $existingBilling = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $this->visit_id)
            ->first();
        
        // Generate billing if not exists using VisitBillingService
        if (!$existingBilling) {
            try {
                $billing = (new \App\Services\VisitBillingService())->createFromVisit($this);
                if ($billing && $billing->bill_id) {
                    \Illuminate\Support\Facades\Log::info("Billing created for visit {$this->visit_id}: Bill ID {$billing->bill_id}");
                    // Reload the relationship
                    $this->load('billing');
                } else {
                    \Illuminate\Support\Facades\Log::error("Billing creation returned null for visit {$this->visit_id}");
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Billing creation failed for visit ' . $this->visit_id . ': ' . $e->getMessage());
                \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());
                // Return false to indicate billing was not created
                return false;
            }
        } else {
            \Illuminate\Support\Facades\Log::info("Billing already exists for visit {$this->visit_id}: Bill ID {$existingBilling->bill_id}");
        }
        return true;
    }
    return false;
}

public function generateBilling()
{
    return \App\Models\Billing::create([
        'bill_date' => now()->toDateString(),
        'visit_id' => $this->visit_id,
        'bill_status' => 'pending',
        'branch_id' => $this->user->branch_id ?? session('active_branch_id'),
        'total_amount' => $this->calculateTotal(),
    ]);
}

protected function calculateTotal()
{
    return $this->services->sum('price'); // Assuming price is stored in services table
}
}
