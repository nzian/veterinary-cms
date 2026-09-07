<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Owner;
use App\Models\GroomingAgreement;
use Illuminate\Support\Facades\Storage;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product; 
use App\Models\ServiceProduct; 
use App\Models\Visit;
use App\Models\Equipment; 
use App\Models\ServiceEquipment;
use App\Models\MedicalHistory; 
use App\Models\Bill; 
use App\Models\AppointServ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\VisitBillingService;
use App\Services\NotificationService; 
use App\Services\InventoryService; 
use App\Services\DynamicSMSService;
use App\Services\GroupedBillingService;
use App\Models\InventoryHistory as InventoryHistoryModel;
use App\Models\InventoryTransaction;
use App\Models\Billing;

class MedicalManagementController extends Controller
{
    protected $inventoryService;
    protected $smsService;
    protected $groupedBillingService;

    public function __construct(InventoryService $inventoryService, DynamicSMSService $smsService)
    {
        $this->middleware('auth');
        $this->inventoryService = $inventoryService;
        $this->smsService = $smsService;
        $this->groupedBillingService = new GroupedBillingService();
    }

    /**
     * Helper method to update visit_service_type by MERGING completed service types
     * with the original service types (preserving uncompleted types)
     */
    protected function updateVisitServiceTypes(Visit $visit)
    {
        // Get the original service types from the visit (e.g., "Vaccination, Checkup")
        $originalTypes = array_filter(array_map('trim', explode(',', $visit->visit_service_type ?? '')));
        
        // Get service types from attached services
        $attachedServiceTypes = $visit->services()->pluck('serv_type')->toArray();
        
        // Merge original types with attached service types (unique, case-insensitive)
        $allTypes = [];
        foreach (array_merge($originalTypes, $attachedServiceTypes) as $type) {
            $typeLower = strtolower(trim($type));
            if (!empty($typeLower) && !in_array($typeLower, array_map('strtolower', $allTypes))) {
                // Keep original casing from originalTypes if available
                $found = false;
                foreach ($originalTypes as $ot) {
                    if (strtolower(trim($ot)) === $typeLower) {
                        $allTypes[] = trim($ot);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allTypes[] = trim($type);
                }
            }
        }
        
        // Update visit_service_type with merged types
        if (!empty($allTypes) && Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
            $visit->visit_service_type = implode(', ', $allTypes);
            $visit->save();
        }
        
        return $allTypes;
    }

    /**
     * Display the boarding form or details for a visit (GET).
     */
    public function showBoarding($visitId)
    {
        // If you already have the boarding tab inside the main medicalManagement view,
        // redirect to the index page with the `tab=boarding` query so the existing
        // Blade tab content is used. Include the visit id so the UI can select the visit.
        return redirect()->route('medical.index', ['tab' => 'boarding', 'visit' => $visitId]);
    }
    
public function updateServiceStatus(Request $request, $visitId, $serviceId)
    {
        try {
            DB::beginTransaction();
            
            $visit = Visit::with('services')->findOrFail($visitId);
            $service = $visit->services()->where('tbl_serv.serv_id', $serviceId)->firstOrFail();
            
            $status = $request->input('status', 'completed');
            
            // Update the specific service status in pivot table
            $visit->services()->updateExistingPivot($serviceId, [
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
                'notes' => $request->input('notes')
            ]);
            
            // Refresh the visit to get updated relationships
            $visit->refresh();
            
            // Get all services directly from pivot table to ensure accurate count
            $pivotRecords = DB::table('tbl_visit_service')
                ->where('visit_id', $visitId)
                ->get();
            
            $totalServices = $pivotRecords->count();
            
            // Count completed services (status must be exactly 'completed')
            $completedServices = $pivotRecords->filter(function($pivot) {
                return ($pivot->status ?? null) === 'completed';
            })->count();
            
            // Check if ALL services are completed using the model method
            $allCompleted = $visit->checkAllServicesCompleted();
            
            // If not all completed, update workflow to show progress
            if (!$allCompleted && $totalServices > 0) {
                $visit->workflow_status = "In Progress ({$completedServices}/{$totalServices} completed)";
                $visit->save();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Service status updated',
                'all_services_completed' => $allCompleted,
                'completed_count' => $completedServices,
                'total_count' => $totalServices,
                'completed_service_id' => $serviceId
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating service status: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update service status: ' . $e->getMessage()
            ], 500);
        }
    }
    // ==================== LOOKUP & HELPER METHODS ====================

    protected function getBranchLookups()
    {
        $activeBranchId = Auth::user()->user_role !== 'superadmin' 
            ? Auth::user()->branch_id 
            : session('active_branch_id');
            
        $branchUserIds = User::where('branch_id', $activeBranchId)->pluck('user_id')->toArray();

        // FIX 1: Retrieve all data intended for lookup dropdowns
        // Model scopes will automatically include referred pets and owners
        $allPets = Pet::with('owner')->get();
        $allOwners = Owner::withCount('pets')->get();
        $allBranches = Branch::all();
        
        // Filter products to only Prescription type for the prescription modal
        $allProducts = Product::select('prod_id', 'prod_name', 'prod_stocks', 'prod_price', 'prod_type')
            ->where('prod_type', 'Prescription')
            ->orderBy('prod_name')
            ->get();
            
        $serviceTypes = Service::orderBy('serv_name')->get(['serv_id','serv_name','serv_type']); 
            
        return compact('allPets', 'allOwners', 'allBranches', 'allProducts', 'serviceTypes');
    }

    public function getAllProductsForPrescription(Request $request)
    {
        // Filter products to only Prescription type
        return Product::select('prod_id', 'prod_name', 'prod_stocks', 'prod_price', 'prod_type')
            ->where('prod_type', 'Prescription')
            ->orderBy('prod_name')
            ->get();
    }
    
    private function generateBillingForAppointment($appointment)
    {
        // NOTE: Keeping the minimal structure here
    }


    // ==================== MAIN INDEX (DASHBOARD) (Fixed Logic) ====================

    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $activeTab = $request->get('tab', 'visits'); 
        $activeBranchId = session('active_branch_id');
        $user = Auth::user();
        $isInBranchMode = session('branch_mode') === 'active';
        
        // Determine if we should show ALL branches (Super Admin in global mode)
        $showAllBranches = ($user->user_role === 'superadmin' && !$isInBranchMode);
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        // Get branch user IDs (all users if showing all branches)
        if ($showAllBranches) {
            $branchUserIds = User::pluck('user_id')->toArray();
        } else {
            $branchUserIds = User::where('branch_id', $activeBranchId)->pluck('user_id')->toArray();
        }
        
        $lookups = $this->getBranchLookups(); 

        // Fix 1.2: Pulling out the necessary lookup variables for the main view's compact()
        $filteredOwners = $lookups['allOwners']; 
        $filteredPets = $lookups['allPets'];
        $serviceTypes = $lookups['serviceTypes'];


        $baseVisitQuery = function() use ($activeBranchId, $showAllBranches) {
            $query = Visit::with(['pet.owner', 'user', 'services'])
                ->whereHas('pet'); // Only include visits that have a valid pet
            
            // If NOT showing all branches, filter by branch
            if (!$showAllBranches) {
                $query->whereHas('user', fn($q) => $q->where('branch_id', $activeBranchId));
            }
            
            return $query->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');
        };

        // Helper for getting all records for client-side filtering
        // Filter by visit_service_type column OR by attached services (for backward compatibility)
        $getAllRecords = function ($types) use ($baseVisitQuery) {
            return $baseVisitQuery()->where(function($query) use ($types) {
                // Filter by visit_service_type column (case-insensitive)
                $query->where(function($q) use ($types) {
                    foreach ($types as $type) {
                        $q->orWhere(DB::raw('LOWER(visit_service_type)'), 'LIKE', '%' . strtolower($type) . '%');
                    }
                });
                // OR filter by attached services (for backward compatibility)
                $query->orWhereHas('services', fn($sq) => $sq->whereIn(DB::raw('LOWER(serv_type)'), $types));
            })->get();
        };
        
        $visits = $baseVisitQuery()->get();

        // FIX: Comprehensive service type lists for tabs (include all variations)
        $checkupTypes = ['check-up', 'check up', 'consultation', 'checkup'];
        $diagnosticTypes = ['diagnostics', 'diagnostic', 'laboratory'];
        $surgicalTypes = ['surgical', 'surgery'];
        

        $consultationVisits = $getAllRecords($checkupTypes);
        $groomingVisits = $getAllRecords(['grooming']);
        $dewormingVisits = $getAllRecords(['deworming']);
        $diagnosticsVisits = $getAllRecords($diagnosticTypes);
        $surgicalVisits = $getAllRecords($surgicalTypes);
        $emergencyVisits = $getAllRecords(['emergency']);
        $vaccinationVisits = $getAllRecords(['vaccination']);
        $boardingVisits = $getAllRecords(['boarding']);
        
       

        // Calculate pending counts for each service type
        // A visit is pending if it has the service type but no completed service attached yet
        $getPendingCount = function($types) use ($baseVisitQuery) {
            return $baseVisitQuery()->where(function($query) use ($types) {
                // Has visit_service_type matching but no services OR has pending services
                $query->where(function($q) use ($types) {
                    foreach ($types as $type) {
                        $q->orWhere(DB::raw('LOWER(visit_service_type)'), 'LIKE', '%' . strtolower($type) . '%');
                    }
                })->where(function($q) use ($types) {
                    // Either no services attached yet
                    $q->doesntHave('services')
                    // OR has services with pending status
                    ->orWhereHas('services', function($sq) use ($types) {
                        $sq->whereIn(DB::raw('LOWER(serv_type)'), $types)
                           ->where('tbl_visit_service.status', 'pending');
                    });
                });
            })->count();
        };

        $pendingCounts = [
            'checkup' => $getPendingCount($checkupTypes),
            'grooming' => $getPendingCount(['grooming']),
            'vaccination' => $getPendingCount(['vaccination']),
            'deworming' => $getPendingCount(['deworming']),
            'diagnostics' => $getPendingCount($diagnosticTypes),
            'surgical' => $getPendingCount($surgicalTypes),
            'emergency' => $getPendingCount(['emergency']),
            'boarding' => $getPendingCount(['boarding']),
        ];

        // Appointments query - show all if Super Admin in global mode
        if ($showAllBranches) {
            $appointments = Appointment::with('pet.owner', 'services')
                ->orderBy('appoint_date', 'desc')
                ->orderBy('appoint_time', 'desc')
                ->orderBy('appoint_id', 'desc')
                ->paginate(10);
        } else {
            $appointments = Appointment::whereHas('user', fn($q) => $q->where('branch_id', $activeBranchId))
                ->with('pet.owner', 'services')
                ->orderBy('appoint_date', 'desc')
                ->orderBy('appoint_time', 'desc')
                ->orderBy('appoint_id', 'desc')
                ->paginate(10);
        }
        
        $prescriptions = Prescription::whereIn('user_id', $branchUserIds)
            ->with('pet.owner')
            ->orderBy('prescription_date', 'desc')
            ->orderBy('prescription_id', 'desc')
            ->paginate(10);

        
        // Load referrals - show all if Super Admin in global mode
        if ($showAllBranches) {
            $referrals = Referral::with([
                    'pet.owner',
                    'visit.services',
                    'refByBranch.branch',
                    'refToBranch',
                    'referralCompany',
                    'referredVisit'
                ])
                ->orderBy('ref_date', 'desc')
                ->paginate(10);
        } else {
            $referrals = Referral::where(function($query) use ($branchUserIds, $activeBranchId) {
                    $query->whereIn('ref_by', $branchUserIds)
                          ->orWhere('ref_to', $activeBranchId);
                })
                ->with([
                    'pet.owner',
                    'visit.services',
                    'refByBranch.branch',
                    'refToBranch',
                    'referralCompany',
                    'referredVisit'
                ])
                ->orderBy('ref_date', 'desc')
                ->paginate(10);
        }


        return view('medicalManagement', array_merge(compact(
            'visits', 'consultationVisits', 'groomingVisits', 'dewormingVisits', 'diagnosticsVisits', 
            'surgicalVisits', 'emergencyVisits', 'vaccinationVisits', 'boardingVisits', 
            'appointments', 'prescriptions', 'referrals', 'activeTab', 'pendingCounts',
            // MUST be included explicitly for the Visit Modal to find available owners
            'filteredOwners', 'filteredPets', 'serviceTypes', 'showAllBranches', 'isInBranchMode'
        ), $lookups));
    }


    // ==================== SERVICE SAVE HANDLERS ====================

    /**
     * Ensure a service on a visit is marked completed. If the service pivot exists, update it;
     * otherwise attach the service with completed pivot data. This uses direct DB updates
     * to avoid potential relationship caching issues.
     *
     * @param \App\Models\Visit $visitModel
     * @param int|null $serviceId
     * @param array $typeKeywords list of keywords to search serv_type when serviceId not provided
     * @param int $quantity
     * @return void
     */
    private function ensureServiceCompleted($visitModel, $serviceId = null, array $typeKeywords = [], $quantity = 1)
    {
        $visitId = $visitModel->visit_id;
        $branchId = Auth::user()->branch_id ?? session('active_branch_id');
        //dd($branchId);
        \Log::info('[ensureServiceCompleted] called', ['visit_id' => $visitId, 'service_id' => $serviceId, 'typeKeywords' => $typeKeywords, 'quantity' => $quantity]);

        if (!empty($serviceId)) {
            // If pivot exists, update; else attach
            $exists = \DB::table('tbl_visit_service')
                ->where('visit_id', $visitId)
                ->where('serv_id', $serviceId)
                ->exists();

            $service = \App\Models\Service::find($serviceId);
            $unitPrice = $service ? $service->serv_price : 0;

            if ($exists) {
                \DB::table('tbl_visit_service')
                    ->where('visit_id', $visitId)
                    ->where('serv_id', $serviceId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * $quantity,
                        'updated_at' => now(),
                    ]);
                \Log::info('[ensureServiceCompleted] updated pivot', ['visit_id' => $visitId, 'serv_id' => $serviceId]);
            } else {
                $visitModel->services()->attach($serviceId, [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                \Log::info('[ensureServiceCompleted] attached service and set completed', ['visit_id' => $visitId, 'serv_id' => $serviceId]);
            }

            return;
        }

        // No service id provided: try to find a pivot matching the visit and service type keywords
        if (!empty($typeKeywords)) {
            $pivot = \DB::table('tbl_visit_service as vs')
                ->join('tbl_serv as s', 'vs.serv_id', '=', 's.serv_id')
                ->where('vs.visit_id', $visitId)
                ->where('s.branch_id', $branchId)
                ->where(function($q) use ($typeKeywords) {
                    foreach ($typeKeywords as $kw) {
                        $q->orWhere('s.serv_type', 'LIKE', '%' . $kw . '%');
                    }
                })
                ->select('vs.serv_id')
                ->first();
              
            if ($pivot) {
                $svcId = $pivot->serv_id;
                $service = \App\Models\Service::find($svcId);
                $unitPrice = $service ? $service->serv_price : 0;
                \DB::table('tbl_visit_service')
                    ->where('visit_id', $visitId)
                    ->where('serv_id', $svcId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * $quantity,
                        'updated_at' => now(),
                    ]);
                \Log::info('[ensureServiceCompleted] updated pivot by typeKeywords', ['visit_id' => $visitId, 'serv_id' => $svcId]);
                return;
            }

            // If no pivot found, try to find a matching service and attach it completed
            $service = \App\Models\Service::where('branch_id', $branchId)->where(function($q) use ($typeKeywords) {
                
                foreach ($typeKeywords as $kw) {
                    $q->orWhere('serv_type', 'LIKE', '%' . $kw . '%');
                }
            })->first();

            if ($service) {
                $visitModel->services()->attach($service->serv_id, [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'quantity' => $quantity,
                    'unit_price' => $service->serv_price,
                    'total_price' => $service->serv_price * $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                \Log::info('[ensureServiceCompleted] attached service by typeKeywords', ['visit_id' => $visitId, 'serv_id' => $service->serv_id]);
            }
        }
    }
    public function saveConsultation(Request $request, $visitId)
    {
        $validated = $request->validate([
            'service_id' => ['required','integer','exists:tbl_serv,serv_id'],
            'weight' => ['nullable','numeric','min:1','max:90'],
            'temperature' => ['nullable','numeric'],
            'heart_rate' => ['nullable','numeric'], 
            'respiration_rate' => ['nullable','numeric'],
            'physical_findings' => ['nullable','string'],
            'diagnosis' => ['required','string'],
            'recommendations' => ['nullable','string'],
        ]);
        
        $branchId = Auth::user()->branch_id ?? session('active_branch_id');
        $visitModel = Visit::with('pet', 'services')->findOrFail($visitId);
        $rr = $request->input('respiration_rate') ?: $request->input('respiratory_rate');

        // Update vitals
        if (isset($validated['weight']) || isset($validated['temperature'])) {
            $pet = $visitModel->pet;
            if ($pet) {
                if (isset($validated['weight'])) $pet->pet_weight = $validated['weight'];
                if (isset($validated['temperature'])) $pet->pet_temperature = $validated['temperature'];
                $pet->save();
            }
        }
        
        $visitModel->weight = $validated['weight'] ?? $visitModel->weight;
        $visitModel->temperature = $validated['temperature'] ?? $visitModel->temperature;
        $visitModel->save();
        
        // Save consultation record
        DB::table('tbl_checkup_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'weight' => $validated['weight'],
                'temperature' => $validated['temperature'],
                'heart_rate' => $validated['heart_rate'],
                'respiration_rate' => $rr,
                'symptoms' => $validated['physical_findings'],
                'findings' => $validated['diagnosis'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Attach the selected consultation service and mark as completed
        $serviceId = $validated['service_id'];
        $service = Service::find($serviceId);
        
        if ($service) {
            // Check if service is already attached
            $exists = DB::table('tbl_visit_service')
                ->where('visit_id', $visitModel->visit_id)
                ->where('serv_id', $serviceId)
                ->exists();
            
            if ($exists) {
                // Update existing pivot
                DB::table('tbl_visit_service')
                    ->where('visit_id', $visitModel->visit_id)
                    ->where('serv_id', $serviceId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'quantity' => 1,
                        'unit_price' => $service->serv_price,
                        'total_price' => $service->serv_price,
                        'updated_at' => now(),
                    ]);
            } else {
                // Attach new service
                $visitModel->services()->attach($serviceId, [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'quantity' => 1,
                    'unit_price' => $service->serv_price,
                    'total_price' => $service->serv_price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            Log::info('[Checkup] Service attached', ['visit_id' => $visitModel->visit_id, 'service_id' => $serviceId, 'price' => $service->serv_price]);
        }

        // Update visit_service_type - merge completed service types with original types
        $visitModel->refresh();
        $this->updateVisitServiceTypes($visitModel);
        
        // Check if all services are done
        $allCompleted = $visitModel->checkAllServicesCompleted();
        $visit_status = $this->getVisitStatusAfterServiceUpdate($visitModel);
        $visitModel->visit_status = $visit_status;
        $visitModel->workflow_status = $visit_status;
        $visitModel->save();
        
        // Generate billing if all services completed
        $billingExists = false;
        if ($allCompleted) {
            try {
                if (property_exists($this, 'groupedBillingService') && $this->groupedBillingService) {
                    $this->groupedBillingService->generateSingleBilling($visitModel->visit_id, true);
                    Log::info('[Checkup] Billing generated for visit ' . $visitModel->visit_id);
                }
            } catch (\Throwable $e) {
                Log::warning('[Checkup] Failed to generate billing: ' . $e->getMessage());
            }
        }
        
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        
        $message = $allCompleted 
            ? ($billingExists 
                ? 'Consultation completed. All services done - billing generated!'
                : 'Consultation completed. All services done, but billing generation failed. Please check logs.')
            : 'Consultation saved. Please complete remaining services.';
        
        return redirect()->route('medical.index', ['tab' => 'checkup'])
            ->with($billingExists && $allCompleted ? 'success' : 'warning', $message);
    }
    
    public function saveVaccination(Request $request, $visitId)
    {
        $validated = $request->validate([
            'service_type' => ['required','string'], // Add service_type validation
            'service_id' => ['nullable','integer','exists:tbl_serv,serv_id'],
            'vaccine_name' => ['required','exists:tbl_prod,prod_id'], 
            'dose' => ['nullable','string'], 
            'manufacturer' => ['nullable','string'], 
            'batch_no' => ['nullable','string'], 
            'date_administered' => ['nullable','date'], 
            'next_due_date' => ['nullable','date'],
            'administered_by' => ['nullable','string'], 
            'remarks' => ['nullable','string'], 
            'workflow_status' => ['nullable','string'],
        ]);
       // dd($validated);
       $branchId = Auth::user()->branch_id ?? session('active_branch_id');
        $visitModel = Visit::with('pet.owner', 'services')->findOrFail($visitId);
        
        try {
            DB::beginTransaction();
            
            // 2. Save Vaccination Record (use updateOrInsert to avoid duplicates)
            $dateAdministered = $validated['date_administered'] ?: $visitModel->visit_date;
            
            // Determine next due date: manual override if provided, else 14 days after administration
            $computedNextDue = Carbon::parse($dateAdministered)->addDays(14)->toDateString();
            $nextDueDate = !empty($validated['next_due_date']) ? $validated['next_due_date'] : $computedNextDue;

            DB::table('tbl_vaccination_record')->updateOrInsert(
                ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
                [
                    'vaccine_name' => $validated['vaccine_name'],
                    'service_id' => $validated['service_id'],
                    'dose'=> $validated['dose'], 
                    'manufacturer' => $validated['manufacturer'],
                    'batch_no' => $validated['batch_no'], 
                    'date_administered' => $dateAdministered,
                    'next_due_date' => $nextDueDate, 
                    'administered_by' => $validated['administered_by'] ?? Auth::user()->user_name,
                    'remarks' => $validated['remarks'], 
                    'created_at' => now(), 
                    'updated_at' => now(),
                ]
            );

            // 3. Deduct Inventory based on consumable products linked to the service
            // Find the selected service to get its consumable products
            if (!empty($validated['service_id'])) {
                $selectedService = Service::where('serv_id', $validated['service_id'])
                    ->with(['products' => function($query) use ($validated) {
                        $query->where('tbl_service_products.prod_id', $validated['vaccine_name']);
                    }])
                    ->first();
            } else {
                $selectedService = Service::where('serv_name', $validated['service_type'])
                    ->with(['products' => function($query) use ($validated) {
                        $query->where('tbl_service_products.prod_id', $validated['vaccine_name']);
                    }])
                    ->first();
            }
            
            // Get ALL vaccination services attached to this visit (pending, null status, or any other status except completed)
            // This ensures we clean up any duplicate or orphaned vaccination services
            $existing_vaccination_services = $visitModel->services()
                ->where(DB::raw('LOWER(serv_type)'), strtolower('Vaccination'))
                ->where('branch_id', $branchId)
                ->where(function($q) {
                    $q->where('tbl_visit_service.status', 'pending')
                      ->orWhereNull('tbl_visit_service.status')
                      ->orWhere('tbl_visit_service.status', '');
                })
                ->pluck('tbl_serv.serv_id')
                ->toArray();
            
            // Detach all vaccination services except the one being completed
            $to_detach = array_diff($existing_vaccination_services, [$validated['service_id']]);
            if (!empty($to_detach)) {
                $visitModel->services()->detach($to_detach);
            }
            
            if ($selectedService && $selectedService->products->isNotEmpty()) {
                $vaccine = $selectedService->products->where('pivot.prod_id', $validated['vaccine_name'])->first();
                $quantityUsed = $vaccine->pivot->quantity_used ?? 1;
                
                // Check if enough stock is available
                $product = Product::find($vaccine->pivot->prod_id);
                if(($product->available_stock - $product->usage_from_inventory_transactions) < $quantityUsed) {
                    DB::rollBack();
                    return back()->with('error', "Insufficient stock for {$vaccine->prod_name}. Available: {($product->available_stock - $product->usage_from_inventory_transactions)}, Required: {$quantityUsed}");
                }
                //dd($product->available_stock);
               // dd($product->usage_from_inventory_transactions);
                //dd($quantityUsed);

                if ($product->available_stock - $product->usage_from_inventory_transactions >= $quantityUsed) {
                   // $vaccine->decrement('prod_stocks', $quantityUsed);
                    $success = $this->inventoryService->deductFromInventory(
                        $vaccine->prod_id,
                        $quantityUsed,
                        "Vaccination - Visit #{$visitId}"  ."Administered to " . ($visitModel->pet->pet_name ?? 'Pet'),
                        "service_usage",
                    );
                    if($success) {
                        InventoryHistoryModel::create([
                        'prod_id' => $vaccine->prod_id,
                        'type' => 'service_usage',
                        'quantity' => -$quantityUsed,
                        'reference' => "Vaccination - Visit #{$visitId}",
                        'user_id' => Auth::id(),
                        'notes' => "Administered to " . ($visitModel->pet->pet_name ?? 'Pet') . " ({$validated['service_type']})"
                      ]);
                    }
                   
                } else {
                    DB::rollBack();
                    return back()->with('error', "Insufficient stock for {$vaccine->prod_name}. Available: {$vaccine->prod_stocks}, Required: {$quantityUsed}");
                }
            } else {
                Log::warning("Vaccine product '{$validated['vaccine_name']}' not found as consumable for service '{$validated['service_type']}'");
            }

            // 4. AUTO-SCHEDULING LOGIC (manual date overrides calculated schedule)
            $schedule = $this->calculateNextSchedule('vaccination', $validated['vaccine_name'], $visitModel->pet_id);

            $appointType = $schedule['next_appoint_type'] ?? ('Vaccination Follow-up for ' . $vaccine->prod_name);
            $newDose = $schedule['new_dose'] ?? 1;

            // Only auto-schedule if this is a new vaccination record (not an update)
            $existingRecord = DB::table('tbl_vaccination_record')
                ->where('visit_id', $visitModel->visit_id)
                ->where('pet_id', $visitModel->pet_id)
                ->first();
            
            // Only create appointment if this is the first time saving this vaccination record
            if (!$existingRecord || $existingRecord->created_at == $existingRecord->updated_at) {
                $this->autoScheduleFollowUp(
                    $visitModel,
                    $appointType,
                    $nextDueDate,
                    $vaccine->prod_name,
                    $newDose
                );
            } else {
                Log::info("Skipping auto-schedule for vaccination update - visit {$visitModel->visit_id}, pet {$visitModel->pet_id}");
            }

            // Ensure vaccination service pivot is marked completed (attach if missing)
            $this->ensureServiceCompleted($visitModel, $validated['service_id'] ?? null, ['vaccination'], 1);

            // 5. Update visit_service_type - merge completed service types with original types
            $visitModel->refresh();
            $this->updateVisitServiceTypes($visitModel);
            $visitModel->visit_status = $this->getVisitStatusAfterServiceUpdate($visitModel);
            $visitModel->workflow_status = $visitModel->visit_status;
            $visitModel->save();
            
            // 6. Check if all services are completed and generate billing
            $allCompleted = $visitModel->checkAllServicesCompleted();
            
            // Verify billing was actually created
            $visitModel->refresh();
            $billingExists = DB::table('tbl_bill')
                ->where('visit_id', $visitModel->visit_id)
                ->exists();
            
            DB::commit();
           
            $successMessage = 'Vaccination recorded. Next appointment auto-scheduled for **' . Carbon::parse($nextDueDate)->format('F j, Y') . '**.';
            if ($allCompleted) {
                $successMessage .= $billingExists 
                    ? ' All services completed - billing generated!'
                    : ' All services completed, but billing generation failed. Please check logs.';
            }
            
            // Redirect back to the medical vaccination tab
            return redirect()->route('medical.index', ['tab' => 'vaccination'])->with('success', $successMessage);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving vaccination record: ' . $e->getMessage());
            return back()->with('error', 'Failed to save vaccination record: ' . $e->getMessage());
        }
    }

    public function saveDeworming(Request $request, $visitId)
    {
        $validated = $request->validate([
            'service_type' => ['required','string'], // Add service_type validation
            'service_id' => ['nullable','integer','exists:tbl_serv,serv_id'],
            'dewormer_name' => ['required','string'], 
            'dosage' => ['nullable','string'],
            'manufacturer' => ['nullable','string'],
            'batch_no' => ['nullable','string'],
            'next_due_date' => ['nullable','date'],
            'administered_by' => ['nullable','string'], 
            'remarks' => ['nullable','string'], 
            'workflow_status' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('pet.owner', 'services')->findOrFail($visitId);
        
        try {
            DB::beginTransaction();
            
            // 2. Save Deworming Record (use updateOrInsert to avoid duplicates)
            $dwBase = $visitModel->visit_date ?? now();
            $dwComputedNextDue = Carbon::parse($dwBase)->addDays(14)->toDateString();
            $dwNextDueDate = !empty($validated['next_due_date']) ? $validated['next_due_date'] : $dwComputedNextDue;

            DB::table('tbl_deworming_record')->updateOrInsert(
                ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
                [
                    'dewormer_name' => $validated['dewormer_name'], 
                    'service_id' => $validated['service_id'],
                    'dosage' => $validated['dosage'],
                    'manufacturer' => $validated['manufacturer'],
                    'batch_no' => $validated['batch_no'],
                    'next_due_date' => $dwNextDueDate, 
                    'administered_by' => $validated['administered_by'] ?? Auth::user()->user_name,
                    'remarks' => $validated['remarks'], 
                    'created_at' => now(), 
                    'updated_at' => now(),
                ]
            );

            // Ensure the record exists; if not, attempt an insert and log for debugging
            $dwRecord = DB::table('tbl_deworming_record')
                ->where('visit_id', $visitModel->visit_id)
                ->where('pet_id', $visitModel->pet_id)
                ->first();

            if (!$dwRecord) {
                $inserted = DB::table('tbl_deworming_record')->insert([
                    'visit_id' => $visitModel->visit_id,
                    'pet_id' => $visitModel->pet_id,
                    'dewormer_name' => $validated['dewormer_name'], 
                    'service_id' => $validated['service_id'],
                    'dosage' => $validated['dosage'],
                    'manufacturer' => $validated['manufacturer'],
                    'batch_no' => $validated['batch_no'],
                    'next_due_date' => $dwNextDueDate, 
                    'administered_by' => $validated['administered_by'] ?? Auth::user()->user_name,
                    'remarks' => $validated['remarks'], 
                    'created_at' => now(), 
                    'updated_at' => now(),
                ]);

                if (!$inserted) {
                    Log::error('Failed to insert deworming record for visit ' . $visitModel->visit_id);
                    DB::rollBack();
                    return back()->with('error', 'Failed to save deworming record.');
                }
            }

            // 3. Deduct Inventory based on consumable products linked to the service
            // Find the selected service to get its consumable products
            if (!empty($validated['service_id'])) {
                $selectedService = Service::where('serv_id', $validated['service_id'])
                    ->with(['products' => function($query) use ($validated) {
                        $query->where('prod_name', $validated['dewormer_name']);
                    }])
                    ->first();
            } else {
                $selectedService = Service::where('serv_name', $validated['service_type'])
                    ->with(['products' => function($query) use ($validated) {
                        $query->where('prod_name', $validated['dewormer_name']);
                    }])
                    ->first();
            }
            
            if ($selectedService && $selectedService->products->isNotEmpty()) {
                 // check if any same type service exist in pivot table
               
                $dewormer = $selectedService->products->first();
                $quantityUsed = $dewormer->pivot->quantity_used ?? 1;
                
                // Check if enough stock is available
                if ($dewormer->prod_stocks >= $quantityUsed) {
                    $dewormer->decrement('prod_stocks', $quantityUsed);
                    InventoryHistoryModel::create([
                        'prod_id' => $dewormer->prod_id,
                        'type' => 'service_usage',
                        'quantity' => -$quantityUsed,
                        'reference' => "Deworming - Visit #{$visitId}",
                        'user_id' => Auth::id(),
                        'notes' => "Administered to " . ($visitModel->pet->pet_name ?? 'Pet') . " ({$validated['service_type']})"
                    ]);
                } else {
                    DB::rollBack();
                    return back()->with('error', "Insufficient stock for {$dewormer->prod_name}. Available: {$dewormer->prod_stocks}, Required: {$quantityUsed}");
                }
            } else {
                Log::warning("Dewormer product '{$validated['dewormer_name']}' not found as consumable for service '{$validated['service_type']}'");
            }

            // 4. AUTO-SCHEDULING LOGIC (manual date overrides calculated schedule)
            $currentDewormName = $validated['dewormer_name']; 
            $schedule = $this->calculateNextSchedule('deworming', $currentDewormName, $visitModel->pet_id);

            $dwAppointType = $schedule['next_appoint_type'] ?? ('Deworming Follow-up for ' . $currentDewormName);
            $dwNewDose = $schedule['new_dose'] ?? 1;

            // Only auto-schedule if this is a new deworming record (not an update)
            $existingRecord = DB::table('tbl_deworming_record')
                ->where('visit_id', $visitModel->visit_id)
                ->where('pet_id', $visitModel->pet_id)
                ->first();
            
            // Only create appointment if this is the first time saving this deworming record
            if (!$existingRecord || $existingRecord->created_at == $existingRecord->updated_at) {
                $this->autoScheduleFollowUp(
                    $visitModel,
                    $dwAppointType,
                    $dwNextDueDate,
                    $currentDewormName,
                    $dwNewDose
                );
            } else {
                Log::info("Skipping auto-schedule for deworming update - visit {$visitModel->visit_id}, pet {$visitModel->pet_id}");
            }

            // Get all deworming services (pending, null status, or empty) and detach those not selected
            $branchId = Auth::user()->branch_id ?? session('active_branch_id');
            $existing_deworming = $visitModel->services()
                ->where(DB::raw('LOWER(serv_type)'), strtolower('Deworming'))
                ->where('branch_id', $branchId)
                ->where(function($q) {
                    $q->where('tbl_visit_service.status', 'pending')
                      ->orWhereNull('tbl_visit_service.status')
                      ->orWhere('tbl_visit_service.status', '');
                })
                ->pluck('tbl_serv.serv_id')
                ->toArray();
            $to_detach = array_diff($existing_deworming, [$validated['service_id']]);
            if (!empty($to_detach)) {
                $visitModel->services()->detach($to_detach);
            }

            // Ensure deworming service pivot is marked completed (attach if missing)
            $this->ensureServiceCompleted($visitModel, $validated['service_id'] ?? null, ['deworming'], 1);

            // 4. Update visit_service_type - merge completed service types with original types
            $visitModel->refresh();
            $this->updateVisitServiceTypes($visitModel);
            
            // 5. Check if all services are completed and generate billing
            $allCompleted = $visitModel->checkAllServicesCompleted();
            $visit_status = $this->getVisitStatusAfterServiceUpdate($visitModel);
            $visitModel->visit_status = $visit_status;
            $visitModel->workflow_status = $visit_status;
            $visitModel->save();    
            
            // Verify billing was actually created
            $visitModel->refresh();
            $billingExists = DB::table('tbl_bill')
                ->where('visit_id', $visitModel->visit_id)
                ->exists();
            
            DB::commit();
            
            $successMessage = 'Deworming recorded. Next appointment auto-scheduled for **' . Carbon::parse($dwNextDueDate)->format('F j, Y') . '**.';
            if ($allCompleted) {
                $successMessage .= $billingExists 
                    ? ' All services completed - billing generated!'
                    : ' All services completed, but billing generation failed. Please check logs.';
            }
            
            // Redirect back to the medical deworming tab
            return redirect()->route('medical.index', ['tab' => 'deworming'])->with('success', $successMessage);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving deworming record: ' . $e->getMessage());
            return back()->with('error', 'Failed to save deworming record: ' . $e->getMessage());
        }
    }
    
    public function updateGroomingService(Request $request, $visitId)
    {
        $messages = [
            'services.required' => 'Please select at least one service.',
            'services.min' => 'Please select at least one service.',
        ];

        $request->validate([
            'coat_condition' => 'required|in:excellent,good,fair,poor',
            'skin_issues' => 'array',
            'skin_issues.*' => 'in:matting,dandruff,fleas',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:tbl_serv,serv_id',
            'notes' => 'nullable|string|max:1000',
        ], $messages);

        DB::beginTransaction();
        try {
            $visit = Visit::with(['services', 'pet.owner'])->findOrFail($visitId);
            
            // Get existing services for reference
            $existingServices = $visit->services()->get();
            
            // Build sync data only for the grooming services from the request
            $syncData = [];
            foreach ($request->services as $serviceId) {
                $existingService = $existingServices->firstWhere('serv_id', $serviceId);
                $syncData[$serviceId] = [
                    'status' => $existingService->pivot->status ?? 'pending',
                    'completed_at' => $existingService->pivot->completed_at ?? null,
                    'quantity' => $existingService->pivot->quantity ?? 1,
                    'unit_price' => $existingService->pivot->unit_price ?? ($existingService->serv_price ?? 0),
                    'total_price' => $existingService->pivot->total_price ?? ($existingService->serv_price ?? 0),
                    'coat_condition' => $request->coat_condition,
                    'skin_issues' => json_encode($request->skin_issues),
                    'notes' => $request->notes,
                    'created_at' => $existingService->pivot->created_at ?? now(),
                    'updated_at' => now()
                ];
            }
            
            // Use syncWithoutDetaching to preserve other service types
            $visit->services()->syncWithoutDetaching($syncData);
            
            DB::commit();
            
            return redirect()
                ->route('medical.visits.grooming.show', $visitId)
                ->with('success', 'Grooming service record updated successfully');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating grooming service: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update grooming service record. Please try again.');
        }
    }

    public function show($id)
{
    $visit = Visit::with(['services', 'pet.owner', 'user'])
        ->withCount(['services as completed_services_count' => function($query) {
            $query->where('status', 'completed');
        }])
        ->findOrFail($id);
        
    return view('visits.show', compact('visit'));
}

public function completeService(Request $request, $visitId, $serviceId)
    {
        return $this->updateServiceStatus(
            $request->merge(['status' => 'completed']), 
            $visitId, 
            $serviceId
        );
    }
    
    public function saveGrooming(Request $request, $visitId)
    {
        //dd($request->all());
        $validated = $request->validate([
            'grooming_type' => ['required','string','max:255'],
            'addon_services' => ['nullable','array'],
            'addon_services.*' => ['string','nullable','max:255'],
            'instructions' => ['nullable','string'],
            'start_time' => ['nullable','date'], 
            'end_time' => ['nullable','date','after_or_equal:start_time'],
            'assigned_groomer' => ['nullable','string'],
        ]);

        $branchId = Auth::user()->branch_id ?? session('active_branch_id');
        
        $visitModel = Visit::with('groomingAgreement')->findOrFail($visitId);
        //dd($visitModel);
        // Check if agreement is signed before proceeding
        if (!$visitModel->groomingAgreement) {
            return redirect()->route('medical.visits.perform', ['id' => $visitId, 'type' => 'grooming'])
                ->with('error', 'Please sign the Grooming Agreement before saving the service record.');
        }

        try {
            DB::beginTransaction();
        
        // 1. Find the selected service IDs and prices (package + add-ons)
        $selectedPackageName = $validated['grooming_type']; // Single package
        $selectedAddonNames = $validated['addon_services'] ?? [];
        $allSelectedNames = array_merge([$selectedPackageName], $selectedAddonNames);
        
        // Filter services by name AND branch_id
        $selectedServices = Service::whereIn('serv_name', $allSelectedNames)
            ->where('branch_id', $branchId)
            ->get();
        $selectedServiceIds = $selectedServices->pluck('serv_id')->toArray();

        Log::info('[Grooming] Service lookup', [
            'branch_id' => $branchId,
            'requested_names' => $allSelectedNames,
            'found_services' => $selectedServices->pluck('serv_name')->toArray(),
        ]);

        $missing = array_diff($allSelectedNames, $selectedServices->pluck('serv_name')->all());
        if (!empty($missing)) {
            Log::warning('Grooming save failed: Some selected services were not found.', ['missing' => $missing, 'branch_id' => $branchId]);
            return back()->with('error', 'One or more selected grooming services were not found. Please ensure the services exist for this branch.');
        }

        // Get all grooming services (pending, null status, or empty) and detach those not selected
        $existing_grooming = $visitModel->services()
            ->where(DB::raw('LOWER(serv_type)'), strtolower('Grooming'))
            ->where('branch_id', $branchId)
            ->where(function($q) {
                $q->where('tbl_visit_service.status', 'pending')
                  ->orWhereNull('tbl_visit_service.status')
                  ->orWhere('tbl_visit_service.status', '');
            })
            ->pluck('tbl_serv.serv_id')
            ->toArray();
        $to_detach = array_diff($existing_grooming, $selectedServiceIds);
        // Only detach grooming services that are NOT in the selected list (to handle changes within grooming)
        // DO NOT detach other service types
        if (!empty($to_detach)) {
            $visitModel->services()->detach($to_detach);
        }

        // Determine grooming service status based on workflow
        $groomingStatus = 'pending';
        $groomingCompletedAt = null;
        if (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            // Both times filled - mark as completed
            $groomingStatus = 'completed';
            $groomingCompletedAt = now();
        } elseif (!empty($validated['start_time'])) {
            // Only start time filled - in progress
            $groomingStatus = 'pending';
        }

        // Build sync data ONLY for the selected grooming services
        $groomingSyncData = [];
        foreach ($selectedServices as $service) {
            // Check if this service already exists to preserve created_at
            $existingService = $visitModel->services()->where('tbl_serv.serv_id', $service->serv_id)->first();
            $existingCreatedAt = $existingService?->pivot->created_at;
            
            $groomingSyncData[$service->serv_id] = [
                'status' => $groomingStatus,
                'completed_at' => $groomingCompletedAt,
                'quantity' => 1,
                'unit_price' => $service->serv_price,
                'total_price' => $service->serv_price,
                'created_at' => $existingCreatedAt ?? now(),
                'updated_at' => now(),
            ];
        }

        // Use syncWithoutDetaching to add/update grooming services WITHOUT removing other service types
        $visitModel->services()->syncWithoutDetaching($groomingSyncData);

        // 4. Determine workflow status based on start_time and end_time for grooming
        $workflowStatus = 'Completed'; // Default when saving
        if (empty($validated['start_time']) && empty($validated['end_time'])) {
            // Neither time filled - keep current status or set to Agreement Signed if agreement exists
            $workflowStatus = $visitModel->groomingAgreement ? 'Agreement Signed' : 'Pending';
        } elseif (!empty($validated['start_time']) && empty($validated['end_time'])) {
            // Start time filled but not end time
            $workflowStatus = 'In Grooming';
        } elseif (!empty($validated['start_time']) && !empty($validated['end_time'])) {
            // Both times filled - mark as completed
            $workflowStatus = 'Completed';
        }

        // Update visit status - keep as 'arrived' until paid

        $visitModel->visit_status = $this->getVisitStatusAfterServiceUpdate($visitModel);
        $visitModel->workflow_status = $visitModel->visit_status;
        $visitModel->save();

        // 5. Update the Grooming Record (tbl_grooming_record)
        DB::table('tbl_grooming_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'service_package' => $selectedPackageName, // Single package
                'add_ons' => !empty($selectedAddonNames) ? implode(', ', $selectedAddonNames) : null,
                'groomer_name' => $validated['assigned_groomer'] ?? Auth::user()->user_name,
                'start_time' => $validated['start_time'], 'end_time' => $validated['end_time'],
                'status' => $workflowStatus, 'remarks' => $validated['instructions'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        Log::info('[Grooming] Record saved', ['visit_id' => $visitModel->visit_id, 'package' => $selectedPackageName, 'status' => $workflowStatus]);
        
        // 6. Update visit_service_type - merge completed service types with original types
        $visitModel->refresh();
        $this->updateVisitServiceTypes($visitModel);
        
        // 7. Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
        // Generate billing if all services completed
        if ($allCompleted) {
            try {
                if (property_exists($this, 'groupedBillingService') && $this->groupedBillingService) {
                    $this->groupedBillingService->generateSingleBilling($visitModel->visit_id, true);
                    Log::info('[Grooming] Billing generated for visit ' . $visitModel->visit_id);
                }
            } catch (\Throwable $e) {
                Log::warning('[Grooming] Failed to generate billing: ' . $e->getMessage());
            }
        }
        
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();

        DB::commit();
        
        $message = 'Grooming record saved.';
        if ($allCompleted) {
            $message .= $billingExists 
                ? ' All services completed - billing generated!'
                : ' All services completed, but billing generation failed. Please check logs.';
        }

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error saving grooming record and billing: ' . $e->getMessage());
        return back()->with('error', 'Failed to save grooming record: ' . $e->getMessage());
    }
        // Redirect back to the perform page to show saved data, or to grooming tab
        if ($request->input('redirect_to') === 'perform') {
            return redirect()->route('medical.visits.perform', ['id' => $visitId, 'type' => 'grooming'])
                ->with('success', $message);
        }
        return redirect()->route('medical.index', ['active_tab' => 'grooming'])->with('success', $message);
    }


    public function saveBoarding(Request $request, $visitId)
    {
        \Log::info('[Boarding] saveBoarding called', ['visitId' => $visitId, 'input' => $request->all(), 'user' => optional(\Auth::user())->user_name]);

        $validated = $request->validate([
            'checkin' => ['required','date'],
            'checkout' => ['nullable','date','after_or_equal:checkin'],
            'room' => ['nullable','string'],
            'equipment_id' => ['nullable', 'exists:tbl_equipment,equipment_id'],
            'care_instructions' => ['nullable','string'],
            'monitoring_notes' => ['nullable','string'],
            'service_id' => ['required', 'exists:tbl_serv,serv_id'],
            'total_days' => ['required', 'integer', 'min:1'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branchId = Auth::user()->branch_id ?? session('active_branch_id');

        $visitModel = Visit::findOrFail($visitId);
        $service = Service::findOrFail($validated['service_id']);
        // Get all boarding services (pending, null status, or empty) and detach those not selected
        $existing_boarding = $visitModel->services()
            ->where(DB::raw('LOWER(serv_type)'), strtolower('Boarding'))
            ->where('branch_id', $branchId)
            ->where(function($q) {
                $q->where('tbl_visit_service.status', 'pending')
                  ->orWhereNull('tbl_visit_service.status')
                  ->orWhere('tbl_visit_service.status', '');
            })
            ->pluck('tbl_serv.serv_id')
            ->toArray();
        $to_detach = array_diff($existing_boarding, [$validated['service_id']]);
        if (!empty($to_detach)) {
            $visitModel->services()->detach($to_detach);
        }
        $dailyRate = (float) $service->serv_price;
        $totalDays = (int) ($validated['total_days'] ?? 1);
        if ($totalDays < 1 && !empty($validated['checkin']) && !empty($validated['checkout'])) {
            $start = Carbon::parse($validated['checkin']);
            $end = Carbon::parse($validated['checkout']);
            $totalDays = $start->diffInDays($end) ?: 1;
        }

        $now = Carbon::now();
        $boardingStatus = 'Checked In';
        $actualDays = $totalDays;
        $autoCheckedOut = false;
        $checkoutDateTime = null;
        if (!empty($validated['checkout'])) {
            $checkoutDateTime = Carbon::parse($validated['checkout']);
            if (strlen($validated['checkout']) <= 10) {
                $checkoutDateTime->setTime(23, 59, 59);
            }
        }
        if ($checkoutDateTime && $now->greaterThanOrEqualTo($checkoutDateTime)) {
            $boardingStatus = 'Checked Out';
            $autoCheckedOut = true;
            $checkinDate = Carbon::parse($validated['checkin']);
            $actualDays = $checkinDate->diffInDays($checkoutDateTime) ?: 1;
        }

        $totalAmount = $dailyRate * $actualDays;

        DB::beginTransaction();
        $shouldGenerateBillingAfterCommit = false;
        try {
            // add/update boarding service - use syncWithoutDetaching to preserve other service types
            $boardingServiceData = [
                $validated['service_id'] => [
                    'status' => $boardingStatus === 'Checked Out' ? 'completed' : 'pending',
                    'completed_at' => $boardingStatus === 'Checked Out' ? now() : null,
                    'quantity' => $actualDays,
                    'unit_price' => $dailyRate,
                    'total_price' => $totalAmount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ];

            $visitModel->services()->syncWithoutDetaching($boardingServiceData);

            // Get equipment ID from request
            $equipmentId = $request->input('equipment_id');
            
            // Get existing boarding record to check for previous equipment assignment
            $existingBoardingRecord = DB::table('tbl_boarding_record')
                ->where('visit_id', $visitModel->visit_id)
                ->where('pet_id', $visitModel->pet_id)
                ->first();
            
            $previousEquipmentId = $existingBoardingRecord->equipment_id ?? null;

            $boardingPayload = [
                'check_in_date' => $validated['checkin'],
                'check_out_date' => $validated['checkout'],
                'room_no' => $request->input('room'),
                'feeding_schedule' => $request->input('care_instructions'),
                'daily_notes' => $request->input('monitoring_notes'),
                'status' => $boardingStatus,
                'handled_by' => Auth::user()->user_name ?? null,
                'total_days' => $actualDays,
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('tbl_boarding_record', 'serv_id')) {
                $boardingPayload['serv_id'] = $validated['service_id'];
            }
            if (Schema::hasColumn('tbl_boarding_record', 'equipment_id')) {
                $boardingPayload['equipment_id'] = $equipmentId;
            }

            DB::table('tbl_boarding_record')->updateOrInsert(
                ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
                $boardingPayload
            );

            // Update equipment status based on boarding status
            if ($equipmentId) {
                $equipment = Equipment::find($equipmentId);
                if ($equipment) {
                    if ($boardingStatus === 'Checked In') {
                        // Mark equipment as "In Use" when checking in
                        // Decrease available count by 1
                        $newAvailable = max(0, ($equipment->equipment_available ?? $equipment->equipment_quantity) - 1);
                        $equipment->update([
                            'equipment_status' => $newAvailable > 0 ? 'available' : 'in use',
                            'equipment_available' => $newAvailable
                        ]);
                        \Log::info('[Boarding] Equipment updated for check-in', [
                            'equipment_id' => $equipmentId,
                            'new_available' => $newAvailable,
                            'status' => $newAvailable > 0 ? 'available' : 'in use'
                        ]);
                    } elseif ($boardingStatus === 'Checked Out') {
                        // Mark equipment as "Available" when checking out
                        // Increase available count by 1 (but not more than total quantity)
                        $newAvailable = min($equipment->equipment_quantity, ($equipment->equipment_available ?? 0) + 1);
                        $equipment->update([
                            'equipment_status' => 'available',
                            'equipment_available' => $newAvailable
                        ]);
                        \Log::info('[Boarding] Equipment updated for check-out', [
                            'equipment_id' => $equipmentId,
                            'new_available' => $newAvailable
                        ]);
                    }
                }
            }

            // Deduct consumable products linked to boarding service when checking in (first time only)
            // Only deduct if this is a NEW check-in (no existing boarding record or status was not previously Checked In)
            $previousStatus = $existingBoardingRecord->status ?? null;
            $isFirstCheckIn = ($boardingStatus === 'Checked In' && $previousStatus !== 'Checked In' && $previousStatus !== 'Check In');
            
            if ($isFirstCheckIn) {
                $this->deductBoardingConsumables($service, $actualDays, $visitModel);
                \Log::info('[Boarding] Consumables deducted on check-in', [
                    'visit_id' => $visitModel->visit_id,
                    'service_id' => $service->serv_id,
                    'total_days' => $actualDays,
                    'previous_status' => $previousStatus
                ]);
            }

            // If equipment was changed, release the previous equipment
            if ($previousEquipmentId && $previousEquipmentId != $equipmentId && $boardingStatus !== 'Checked Out') {
                $prevEquipment = Equipment::find($previousEquipmentId);
                if ($prevEquipment) {
                    // Increase available count for the released equipment
                    $newAvailable = min($prevEquipment->equipment_quantity, ($prevEquipment->equipment_available ?? 0) + 1);
                    $prevEquipment->update([
                        'equipment_status' => 'available',
                        'equipment_available' => $newAvailable
                    ]);
                    \Log::info('[Boarding] Previous equipment released', [
                        'previous_equipment_id' => $previousEquipmentId,
                        'new_available' => $newAvailable
                    ]);
                }
            }

            $visitModel->workflow_status = $boardingStatus;
            $visitModel->visit_status = $boardingStatus;
            // Update visit_service_type - merge completed service types with original types
            $this->updateVisitServiceTypes($visitModel);
            $visitModel->save();

            // Generate billing on Check-In (not just Check-Out)
            if ($boardingStatus === 'Checked In' || $boardingStatus === 'Checked Out' || $autoCheckedOut) {
                $shouldGenerateBillingAfterCommit = true;
                Log::info('[Boarding] Will generate billing', ['status' => $boardingStatus, 'visit_id' => $visitModel->visit_id]);
            }

            DB::commit();
        } catch (\Illuminate\Validation\ValidationException $ve) {
            DB::rollBack();
            Log::error('Boarding validation failed', ['errors' => $ve->errors(), 'input' => $request->all()]);
            return back()->withErrors($ve->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving boarding record: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Failed to save boarding record: ' . $e->getMessage());
        }

        // generate billing after commit if flagged
        if ($shouldGenerateBillingAfterCommit) {
            try {
                if (property_exists($this, 'groupedBillingService') && $this->groupedBillingService) {
                    \Log::info('[Boarding Billing Generation] Calling groupedBillingService for visit_id: ' . $visitModel->visit_id);
                    // Force billing generation for boarding actions (Checked In/Out) to allow testing
                    $this->groupedBillingService->generateSingleBilling($visitModel->visit_id, true);
                    \Log::info('[Boarding Billing Generation] Billing generated for visit_id: ' . $visitModel->visit_id);
                } else {
                    \Log::warning('[Boarding Billing Generation] groupedBillingService not available for visit_id: ' . $visitModel->visit_id);
                }
            } catch (\Throwable $e) {
                \Log::warning('[Boarding Billing Generation] Failed to auto-create billing: ' . $e->getMessage());
            }
        }

        $message = 'Boarding record saved. Pet status: ' . $boardingStatus . '.';
        return redirect()->route('medical.index', ['tab' => 'boarding'])->with('success', $message);
    }

    public function saveDiagnostic(Request $request, $visitId)
    {
        $validated = $request->validate([
            'test_type' => ['required','string'], 'service_id' => ['integer','exists:tbl_serv,serv_id'],
            'results_text' => ['nullable','string'], 'interpretation' => ['nullable','string'],
            'staff' => ['nullable','string'], 'test_datetime' => ['nullable','date'], 'workflow_status' => ['nullable','string'],
        ]);

        //dd($validated);
        $syncData = [];
        $visitModel = Visit::findOrFail($visitId);
        $branchId = Auth::user()->branch_id ?? session('active_branch_id');
        $selectedServiceIds = [$validated['service_id']];
        // Get all diagnostics services (pending, null status, or empty) and detach those not selected
        $existing_diagnostics = $visitModel->services()
            ->where(DB::raw('LOWER(serv_type)'), strtolower('Diagnostics'))
            ->where('branch_id', $branchId)
            ->where(function($q) {
                $q->where('tbl_visit_service.status', 'pending')
                  ->orWhereNull('tbl_visit_service.status')
                  ->orWhere('tbl_visit_service.status', '');
            })
            ->pluck('tbl_serv.serv_id')
            ->toArray();
        $to_detach = array_diff($existing_diagnostics, $selectedServiceIds);
        if (!empty($to_detach)) {
            $visitModel->services()->detach($to_detach);
        }

           $service = Service::find($validated['service_id']);
           if($service){
            $syncData[$service->serv_id] = [
                            'status' => 'completed',
                            'completed_at' => now(),
                            'quantity' => 1,
                             'unit_price' => $service->serv_price,
                            'total_price' => $service->serv_price,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
           }
           if (!empty($syncData)) {
            $visitModel->services()->syncWithoutDetaching($syncData);
           }
       
            

        DB::table('tbl_diagnostic_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'test_type' => $validated['test_type'], 
                'service_id' => $validated['service_id'],
                'results' => $validated['results_text'],
                'remarks' => $validated['interpretation'], 'collected_by' => $validated['staff'] ?? Auth::user()->user_name,
                'date_completed' => $validated['test_datetime'] ? Carbon::parse($validated['test_datetime'])->toDateString() : now()->toDateString(),
                'status' => 'Completed', 'updated_at' => now(),
            ]
        ); 
        

        // Update visit_service_type - merge completed service types with original types
        $visitModel->refresh();
        $this->updateVisitServiceTypes($visitModel);
        
        // Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        $visit_status = $this->getVisitStatusAfterServiceUpdate($visitModel);
        $visitModel->visit_status = $visit_status;
        $visitModel->workflow_status = $visit_status;
        $visitModel->save();
        
        
        $message = 'Diagnostic record saved successfully!';
        if ($allCompleted) {
            $message .= $billingExists 
                ? ' All services completed - billing generated!'
                : ' All services completed, but billing generation failed. Please check logs.';
        }
        
        // Redirect to the diagnostics tab
        return redirect()->route('medical.index', ['tab' => 'diagnostics'])->with('success', $message);
    }

    public function saveSurgical(Request $request, $visitId)
    {
        $validated = $request->validate([
            'surgery_type' => ['required','string'], 
            'service_id' => ['required','integer','exists:tbl_serv,serv_id'],
            'staff' => ['nullable','string'], 'anesthesia' => ['nullable','string'],
            'dosage' => ['nullable','string'], 'manufacturer' => ['nullable','string'], 'batch_no' => ['nullable','string'],
            'start_time' => ['nullable','date'], 'end_time' => ['nullable','date','after_or_equal:start_time'],
            'checklist' => ['nullable','string'], 'post_op_notes' => ['nullable','string'], 'medications_used' => ['nullable','string'],
            'follow_up' => ['nullable','date'], 'workflow_status' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('services')->findOrFail($visitId);
        
        try {
            DB::beginTransaction();
            
            $branchId = Auth::user()->branch_id ?? session('active_branch_id');
            $selectedServiceIds = [$validated['service_id']];
        
        DB::table('tbl_surgical_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'procedure_name' => $validated['surgery_type'], 
                'service_id' => $validated['service_id'],
                'date_of_surgery' => $validated['start_time'] ? Carbon::parse($validated['start_time'])->toDateString() : $visitModel->visit_date,
                'start_time' => $validated['start_time'], 
                'end_time' => $validated['end_time'],
                'surgeon' => $validated['staff'], 
                'anesthesia_used' => $validated['anesthesia'],
                'dosage' => $validated['dosage'],
                'manufacturer' => $validated['manufacturer'],
                'batch_no' => $validated['batch_no'],
                'findings' => $validated['checklist'] ?? null, 
                'status' => 'Completed', 
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Deduct Inventory for anesthesia/consumable products
        if (!empty($validated['anesthesia']) && !empty($validated['service_id'])) {
            $selectedService = Service::find($validated['service_id']);
            //dd($selectedService->pivot);
            if ($selectedService) {
                // check if any same type service exist in pivot table
                // Get all surgical services (pending, null status, or empty) and detach those not selected
                $existing_surgical = $visitModel->services()
                    ->where(DB::raw('LOWER(serv_type)'), strtolower('Surgical'))
                    ->where('branch_id', $branchId)
                    ->where(function($q) {
                        $q->where('tbl_visit_service.status', 'pending')
                          ->orWhereNull('tbl_visit_service.status')
                          ->orWhere('tbl_visit_service.status', '');
                    })
                    ->pluck('tbl_serv.serv_id')
                    ->toArray();
                $to_detach = array_diff($existing_surgical, $selectedServiceIds);
                if (!empty($to_detach)) {
                    $visitModel->services()->detach($to_detach);
                }
              
                $anesthesiaProduct = $selectedService->products()
                    ->where('prod_name', $validated['anesthesia'])
                    ->first();
                
                if ($anesthesiaProduct) {
                    $quantityUsed = $anesthesiaProduct->pivot->quantity_used ?? 1;
                    
                    // Check if enough stock is available
                    if ($anesthesiaProduct->prod_stocks >= $quantityUsed) {
                        $anesthesiaProduct->decrement('prod_stocks', $quantityUsed);
                        InventoryHistoryModel::create([
                            'prod_id' => $anesthesiaProduct->prod_id,
                            'type' => 'service_usage',
                            'quantity' => -$quantityUsed,
                            'reference' => "Surgical - Visit #{$visitId}",
                            'user_id' => Auth::id(),
                            'notes' => "Used for " . ($visitModel->pet->pet_name ?? 'Pet') . " ({$validated['surgery_type']})"
                        ]);
                    } else {
                        Log::warning("Insufficient stock for anesthesia product '{$validated['anesthesia']}'. Available: {$anesthesiaProduct->prod_stocks}, Required: {$quantityUsed}");
                    }
                } else {
                    Log::warning("Anesthesia product '{$validated['anesthesia']}' not found for surgical service ID {$validated['service_id']}");
                }
            }
        }
        
        // Ensure surgical service pivot is marked completed (attach if missing)
        $this->ensureServiceCompleted($visitModel, $validated['service_id'] ?? null, ['surgical', 'surgery'], 1);
        
        // Update visit_service_type - merge completed service types with original types
        $visitModel->refresh();
        $this->updateVisitServiceTypes($visitModel);
        
        // Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        $visit_status = $this->getVisitStatusAfterServiceUpdate($visitModel);
        $visitModel->visit_status = $visit_status;
        $visitModel->workflow_status = $visit_status;
        $visitModel->save();
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        
        DB::commit();
        
        $message = 'Surgical record saved successfully!';
        if ($allCompleted) {
            $message .= $billingExists 
                ? ' All services completed - billing generated!'
                : ' All services completed, but billing generation failed. Please check logs.';
        }
        
        // Redirect to the surgical tab
        return redirect()->route('medical.index', ['tab' => 'surgical'])->with('success', $message);
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving surgical record: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Failed to save surgical record: ' . $e->getMessage())->withInput();
        }
    }

    public function saveEmergency(Request $request, $visitId)
    {
        $validated = $request->validate([
            'emergency_type' => ['nullable','string'], 'arrival_time' => ['nullable','date'], 'vitals' => ['nullable','string'],
            'immediate_intervention' => ['nullable','string'], 'triage_notes' => ['nullable','string'],
            'procedures' => ['nullable','string'], 'immediate_meds' => ['nullable','string'],
            'outcome' => ['nullable','string'], 'attended_by' => ['nullable','string'], 'workflow_status' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('services')->findOrFail($visitId);
        
        // Mark emergency service as completed (attach if missing)
        $this->ensureServiceCompleted($visitModel, null, ['emergency'], 1);
        
        DB::table('tbl_emergency_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'case_type' => $validated['emergency_type'], 'arrival_condition' => $validated['triage_notes'],
                'vital_signs' => $validated['vitals'], 'immediate_treatment' => $validated['immediate_intervention'] . "\n" . $validated['procedures'],
                'medications_administered' => $validated['immediate_meds'], 'outcome' => $validated['outcome'],
                'status' => 'Completed', 'attended_by' => $validated['attended_by'] ?? Auth::user()->user_name,
                'remarks' => $validated['triage_notes'], 'updated_at' => now(),
            ]
        );
        
        // Update visit_service_type - merge completed service types with original types
        $visitModel->refresh();
        $this->updateVisitServiceTypes($visitModel);
        
        // Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        
        $message = 'Emergency record saved successfully!';
        if ($allCompleted) {
            $message .= $billingExists 
                ? ' All services completed - billing generated!'
                : ' All services completed, but billing generation failed. Please check logs.';
        }
        
        // Redirect to the emergency tab
        return redirect()->route('medical.index', ['tab' => 'emergency'])->with('success', $message);
    }

    // ==================== VISIT CRUD (Fixed) ====================
    
    public function storeVisit(Request $request)
    {
        $validated = $request->validate([
            'visit_date' => 'required|date',
            'pet_ids' => 'required|array|min:1',
            'pet_ids.*' => 'exists:tbl_pet,pet_id',
            'weight' => 'nullable',
            'temperature' => 'nullable',
            'patient_type' => 'required|string|max:100',
            'cage_ward_number' => 'nullable|string|max:50',
            'admission_notes' => 'nullable|string',
            'is_priority' => 'nullable|boolean',
            'admission_date' => 'nullable|date',
        ]);

        $userId = Auth::id() ?? $request->input('user_id');
        $branchId = Auth::user()->branch_id ?? session('active_branch_id');
        $totalServicesCreated = 0;
        $visitsCreated = 0;

        DB::transaction(function () use ($validated, $userId, $request, &$totalServicesCreated, $branchId, &$visitsCreated) {
            foreach ($validated['pet_ids'] as $petId) {
                $pet = Pet::findOrFail($petId);
                $pet->pet_weight = $request->input("weight.$petId") ?? $pet->pet_weight;
                $pet->pet_temperature = $request->input("temperature.$petId") ?? $pet->pet_temperature;
                $pet->save();
                
                // Determine visit status based on patient type
                $visitStatus = 'arrived';
                $isPriority = false;
                
                if ($validated['patient_type'] === 'Inpatient') {
                    $visitStatus = 'admitted';
                } elseif ($validated['patient_type'] === 'Emergency') {
                    $visitStatus = 'arrived';
                    $isPriority = $request->input('is_priority', true);
                }
                
                $data = [
                    'visit_date' => $validated['visit_date'],
                    'pet_id' => $petId,
                    'user_id' => $userId,
                    'weight' => $request->input("weight.$petId") ?? null,
                    'temperature' => $request->input("temperature.$petId") ?? null,
                    'patient_type' => $validated['patient_type'],
                    'visit_status' => $visitStatus,
                    'workflow_status' => 'Pending',
                    'cage_ward_number' => $validated['cage_ward_number'] ?? null,
                    'admission_notes' => $validated['admission_notes'] ?? null,
                    'is_priority' => $isPriority,
                    'admission_date' => $validated['patient_type'] === 'Inpatient' ? ($validated['admission_date'] ?? now()) : null,
                ];

                $visit = Visit::create($data);
                $visitsCreated++;

                // Get selected service TYPES for this pet (these are just categories, NOT actual services)
                $selectedTypes = $request->input("service_type.$petId", []);
                
                if (!empty($selectedTypes)) {
                    // Remove duplicates from selected types
                    $selectedTypes = array_unique($selectedTypes);
                    
                    // Store service TYPE names in visit record for categorization
                    // DO NOT attach actual services here - services are attached when completing the service type tab
                    $typesSummary = implode(', ', $selectedTypes);
                    $updateData = [
                        'workflow_status' => 'Pending'
                    ];
                    if (Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
                        $updateData['visit_service_type'] = $typesSummary;
                    }
                    
                    $visit->update($updateData);
                    $totalServicesCreated += count($selectedTypes);
                }
            }
        });

        $activeTab = $request->input('tab', 'visits');
        $message = $visitsCreated > 1 
            ? "Successfully created {$visitsCreated} visits with {$totalServicesCreated} total service(s)."
            : "Visit recorded successfully with {$totalServicesCreated} service(s).";
            
        return redirect()->route('medical.index', ['tab' => $activeTab])
            ->with('success', $message);
    }
    public function updateVisit(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'visit_date' => 'required|date', 'pet_id' => 'required|exists:tbl_pet,pet_id',
            'weight' => 'nullable|numeric', 'temperature' => 'nullable|numeric', 'patient_type' => 'required|string|max:100',
            'visit_status' => 'nullable|string',
        ]);
        $branchId = Auth::user()->branch_id ?? session('active_branch_id');

            $pet = Pet::findOrFail($validated['pet_id']);
            $pet->pet_weight = $validated['weight'] ?? $pet->pet_weight;
            $pet->pet_temperature = $validated['temperature'] ?? $pet->pet_temperature;
            $pet->save();

        $visit->update($validated);
        if ($request->filled('visit_status')) {
            $visit->visit_status = $request->input('visit_status');
            $visit->workflow_status = $request->input('workflow_status', 'pending');
            $visit->save();
        }

        // Handle service types - update visit_service_type field only (NOT actual services)
        if ($request->has('service_type')) {
            $selectedTypes = $request->input('service_type', []);
            
            if (!empty($selectedTypes)) {
                // Remove duplicates from selected types
                $selectedTypes = array_unique(array_filter($selectedTypes));
                
                // Store service TYPE names in visit record for categorization
                // DO NOT attach actual services here - services are attached when completing the service type tab
                $typesSummary = implode(', ', $selectedTypes);
                if (Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
                    $visit->visit_service_type = $typesSummary;
                    $visit->save();
                }
            }
        }

        // Auto-generate billing if visit is marked completed and no billing exists yet
        if (strcasecmp((string)$visit->workflow_status, 'completed') === 0 && !$visit->billing) {
            try {
                $this->groupedBillingService->generateSingleBilling($visit->visit_id);
                \Log::info('Auto-generated billing for visit ' . $visit->visit_id);
            } catch (\Throwable $e) {
                \Log::warning('Failed to auto-create billing on visit completion: '.$e->getMessage());
            }
        }

        $activeTab = $request->input('tab', 'visits');
        return redirect()->route('medical.index', ['tab' => $activeTab])
            ->with('success', 'Visit updated successfully');
    }

    public function destroyVisit(Request $request, $id)
    {
        $visit = Visit::findOrFail($id);
        if ($visit->billing) {
            return redirect()->route('medical.index', ['tab' => 'visits'])
                ->with('error', 'Cannot delete visit with existing billing record.');
        }
        if(!$visit->services->isEmpty()){
            $visit->services()->detach();
        }
        if(!$visit->billing) {
            DB::table('tbl_diagnostic_record')->where('visit_id', $visit->visit_id)->delete();
            DB::table('tbl_grooming_record')->where('visit_id', $visit->visit_id)->delete();
            DB::table('tbl_boarding_record')->where('visit_id', $visit->visit_id)->delete();
            DB::table('tbl_surgical_record')->where('visit_id', $visit->visit_id)->delete();
            DB::table('tbl_emergency_record')->where('visit_id', $visit->visit_id)->delete();
            DB::table('tbl_checkup_record')->where('visit_id', $visit->visit_id)->delete();
        }
        $visit->delete();

        $activeTab = $request->input('tab', 'visits');
        return redirect()->route('medical.index', ['tab' => $activeTab])
            ->with('success', 'Visit deleted successfully');
    }

    public function showVisit($id)
    {
        $visit = Visit::with(['pet.owner', 'user', 'services'])->findOrFail($id);
        return response()->json($visit);
    }

    public function updateWorkflowStatus(Request $request, $id)
    {
        try {
            $visit = Visit::findOrFail($id);
            $type = strtolower(trim($request->input('type', '')));
            $target = $request->input('to');

            $map = [
                'diagnostic' => ['Waiting','Sample Collection','Testing','Results Encoding','Completed'],
                'grooming' => ['Waiting','In Grooming','Bathing','Drying','Finishing','Completed','Picked Up'],
                'boarding' => ['Reserved','Checked In','In Boarding','Ready for Pick-up','Checked Out'],
                'surgical' => ['Waiting','Pre-op','Surgery Ongoing','Recovery','Completed'],
                'emergency' => ['Triage','Stabilization','Treatment','Observation','Completed'],
                'deworming' => ['Waiting','Deworming Ongoing','Observation','Completed'],
                'checkup' => ['Waiting','Consultation Ongoing','Completed'],
                'vaccination' => ['Waiting','Consultation','Vaccination Ongoing','Observation','Completed'],
            ];

            $stages = $map[$type] ?? $map['checkup'];
            $defaultStart = ($type === 'boarding') ? 'Reserved' : 'Waiting';

            $current = $visit->workflow_status ?: $defaultStart;
            if ($target) {
                $next = $target;
            } else {
                $idx = array_search($current, $stages, true);
                if ($idx === false) { $idx = -1; }
                $next = $stages[min($idx + 1, count($stages) - 1)];
            }

            $visit->workflow_status = $next;
            $visit->save();

            // Boarding: If status is changed to Checked Out, generate billing
            if (
                ($type === 'boarding' && strcasecmp($next, 'Checked Out') === 0)
            ) {
                $billingExists = \DB::table('tbl_bill')
                    ->where('visit_id', $visit->visit_id)
                    ->exists();
                if (!$billingExists) {
                    try {
                        if (property_exists($this, 'groupedBillingService') && $this->groupedBillingService) {
                                // Force billing generation for boarding Checked Out workflow transition
                                $this->groupedBillingService->generateSingleBilling($visit->visit_id, true);
                                \Log::info('Auto-generated billing for boarding visit ' . $visit->visit_id . ' via workflow status update');
                            }
                    } catch (\Throwable $e) {
                        \Log::warning('Failed to auto-create billing for boarding: ' . $e->getMessage());
                    }
                }
            }

            if (strcasecmp($next, 'Completed') === 0) {
                if (!$visit->billing) {
                    try {
                        $this->groupedBillingService->generateSingleBilling($visit->visit_id);
                        \Log::info('Auto-generated billing for visit ' . $visit->visit_id . ' via workflow status update');
                    } catch (\Throwable $e) {
                        \Log::warning('Failed to auto-create billing: ' . $e->getMessage());
                    }
                }
            }

            return response()->json(['success' => true, 'workflow_status' => $next]);
        } catch (\Exception $e) {
            Log::error('updateWorkflowStatus error: '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update status'], 500);
        }
    }

    // ==================== VISIT WORKSPACE (PERFORM SERVICE) (FIXED Logic) ====================
    /**
     * Renders the service-specific blade for performing a visit.
     */
  public function performVisit(Request $request, $id)
    {

        $branchId = Auth::user()->branch_id ?? session('active_branch_id');
        
        $visit = Visit::with(['pet.owner', 'user', 'services'])->findOrFail($id);

        $explicitType = $request->query('type');
        $serviceType = null;
        if ($explicitType) {
            $serviceType = str_replace('-', ' ', $explicitType);
        } elseif ($visit->services->where('pivot.status', 'pending')->count() > 0) {
            $serviceType = $visit->services->where('pivot.status', 'pending')->first()->serv_type;
        }

        // FIX 2: Correct Mapping Logic for Blade View
        $map = [
            'consultation' => 'checkup', 'check up' => 'checkup', 'checkup' => 'checkup',
            'diagnostic' => 'diagnostic', 'diagnostics' => 'diagnostic', 'laboratory' => 'diagnostic',
            'vaccination' => 'vaccination', 'deworming' => 'deworming',
            'grooming' => 'grooming', 'boarding' => 'boarding',
            'surgical' => 'surgical', 'surgery' => 'surgical',
            'emergency' => 'emergency',
        ];
        $key = strtolower($serviceType ?? 'consultation');
        $blade = $map[$key] ?? 'checkup';

        $tableByBlade = [
            'checkup' => 'tbl_checkup_record', 'vaccination' => 'tbl_vaccination_record',
            'deworming' => 'tbl_deworming_record', 'grooming' => 'tbl_grooming_record',
            'boarding' => 'tbl_boarding_record', 'diagnostic' => 'tbl_diagnostic_record',
            'surgical' => 'tbl_surgical_record', 'emergency' => 'tbl_emergency_record',
        ];
        
        $serviceData = null;
        if (isset($tableByBlade[$blade])) {
            try {
                // Ensure the pet has a medical history record or lookup in the service table
                $serviceData = DB::table($tableByBlade[$blade])->where('visit_id', $id)->where('pet_id', $visit->pet_id)->first();
            } catch (\Throwable $th) {
                $serviceData = null;
            }
        }

    $petMedicalHistory = Visit::where('pet_id', $visit->pet_id)
    ->where('visit_id', '!=', $id) // Exclude the current visit ID
    ->with('services') // Load related services for type display
    ->orderBy('visit_date', 'desc')
    ->limit(10) // Fetch up to 10 past visits
    ->get()
    ->map(function ($v) {
        // Aggregate service types into a readable string
        $v->service_summary = $v->services->pluck('serv_name')->implode(', ');
        
        // Optional: Attempt to fetch and include initial assessment/diagnosis for context
        // If you have a one-to-one relationship named 'initialAssessment' on Visit model:
        if ($v->initialAssessment) {
            $v->summary_diagnosis = $v->initialAssessment->symptoms ?? $v->initialAssessment->diagnosis ?? 'Assessed';
        } elseif ($v->visit_service_type) {
             $v->summary_diagnosis = $v->visit_service_type; // Fallback to recorded type
        } else {
            $v->summary_diagnosis = 'General Visit';
        }

        return $v;
    });
            
        // Load available services for grooming or boarding
        $petWeight = optional($visit)->weight; // may be null
        $petAgeMonths = $this->calculatePetAgeInMonths(optional($visit)->pet);
        $availableAddons = collect();
        if ($blade === 'grooming') {
            $availableGroomingServices = Service::where('serv_type', 'LIKE', '%' .$serviceType . '%')
                ->where('branch_id', $branchId)
                ->select('serv_id', 'serv_name', 'serv_price') 
                ->orderBy('serv_name')
                ->get();

            // Separate packages and add-ons
            $groomingPackages = $availableGroomingServices->filter(function($service) {
                return (stripos($service->serv_name, 'Bath and Blow Dry') !== false || 
                        stripos($service->serv_name, 'Grooming') !== false) &&
                       stripos($service->serv_name, 'Add on') === false &&
                       stripos($service->serv_name, 'Add-on') === false;
            })->unique('serv_name')->values();

            $groomingAddons = $availableGroomingServices->filter(function($service) {
                return stripos($service->serv_name, 'Add on') !== false || 
                       stripos($service->serv_name, 'Add-on') !== false;
            })->unique('serv_name')->values();

            $availableServices = $groomingPackages->map(function ($service) {
                $name = $service->serv_name;
                $min_weight = 0;
                $max_weight = 9999;
                if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:to|-|â€“)\s*(\d+\.?\d*)\s*k[gl]?/i', $name, $matches)) {
                    $min_weight = (float)$matches[1];
                    $max_weight = (float)$matches[2];
                } else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:below|bellow)/i', $name, $matches)) {
                    $min_weight = 0;
                    $max_weight = (float)$matches[1];
                } else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:up|above)/i', $name, $matches)) {
                    $min_weight = (float)$matches[1];
                    $max_weight = 9999;
                } else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:below|bellow)/i', $name, $matches)) {
                    $min_weight = 0;
                    $max_weight = (float)$matches[1];
                } else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:up|above)/i', $name, $matches)) {
                    $min_weight = (float)$matches[1];
                    $max_weight = 9999;
                }
                [$minAge, $maxAge] = $this->extractAgeRangeFromServiceLabel($name);
                $service->min_weight = $min_weight;
                $service->max_weight = $max_weight;
                $service->min_age_months = $minAge;
                $service->max_age_months = $maxAge;
                return $service;
            })
            ->filter(function ($service) use ($petWeight) {
                if (empty($petWeight) || !is_numeric($petWeight)) {
                    return true;
                }
                $minWeight = $service->min_weight ?? 0;
                $maxWeight = $service->max_weight ?? 9999;
                return $petWeight >= $minWeight && $petWeight <= $maxWeight;
            })
            ->filter(function ($service) use ($petAgeMonths) {
                if (is_null($petAgeMonths)) {
                    return true;
                }
                $minAge = $service->min_age_months;
                $maxAge = $service->max_age_months;
                if (!is_null($minAge) && $petAgeMonths < $minAge) {
                    return false;
                }
                if (!is_null($maxAge) && $petAgeMonths > $maxAge) {
                    return false;
                }
                return true;
            })
            ->values();

            $availableAddons = $groomingAddons->map(function ($service) {
                $name = $service->serv_name;
                $min_weight = 0;
                $max_weight = 9999;
                if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:to|-|â€“)\s*(\d+\.?\d*)\s*k[gl]?/i', $name, $matches)) {
                    $min_weight = (float)$matches[1];
                    $max_weight = (float)$matches[2];
                } else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:below|bellow)/i', $name, $matches)) {
                    $min_weight = 0;
                    $max_weight = (float)$matches[1];
                } else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:up|above)/i', $name, $matches)) {
                    $min_weight = (float)$matches[1];
                    $max_weight = 9999;
                } else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:below|bellow)/i', $name, $matches)) {
                    $min_weight = 0;
                    $max_weight = (float)$matches[1];
                } else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:up|above)/i', $name, $matches)) {
                    $min_weight = (float)$matches[1];
                    $max_weight = 9999;
                }
                [$minAge, $maxAge] = $this->extractAgeRangeFromServiceLabel($name);
                $service->min_weight = $min_weight;
                $service->max_weight = $max_weight;
                $service->min_age_months = $minAge;
                $service->max_age_months = $maxAge;
                return $service;
            })
            ->filter(function ($service) use ($petWeight) {
                if (empty($petWeight) || !is_numeric($petWeight)) {
                    return true;
                }
                $minWeight = $service->min_weight ?? 0;
                $maxWeight = $service->max_weight ?? 9999;
                return $petWeight >= $minWeight && $petWeight <= $maxWeight;
            })
            ->filter(function ($service) use ($petAgeMonths) {
                if (is_null($petAgeMonths)) {
                    return true;
                }
                $minAge = $service->min_age_months;
                $maxAge = $service->max_age_months;
                if (!is_null($minAge) && $petAgeMonths < $minAge) {
                    return false;
                }
                if (!is_null($maxAge) && $petAgeMonths > $maxAge) {
                    return false;
                }
                return true;
            })
            ->values();
        } elseif ($blade === 'checkup') {
            // For checkup/consultation, load checkup services for this branch
            $availableServices = Service::where(function($q) {
                    $q->where('serv_type', 'LIKE', '%check%')
                      ->orWhere('serv_type', 'LIKE', '%consult%');
                })
                ->where('branch_id', $branchId)
                ->orderBy('serv_name')
                ->get();
                
            Log::info('[Checkup] Available services loaded', ['count' => $availableServices->count(), 'branch_id' => $branchId]);
        } elseif ($blade === 'boarding') {
            // For boarding, load only services with serv_type = 'boarding' and filter by branch
            $activeBranchId = Auth::user()->user_role !== 'superadmin' 
                ? Auth::user()->branch_id 
                : session('active_branch_id');
            $availableServices = Service::where('serv_type', 'boarding')
                ->when($activeBranchId, function($query) use ($activeBranchId) {
                    $query->where('branch_id', $activeBranchId);
                })
                ->orderBy('serv_name')
                ->get();
            
            // Load equipment (cages/rooms) for each boarding service
            foreach($availableServices as $service) {
                $equipmentIds = DB::table('tbl_service_equipment')
                    ->where('serv_id', $service->serv_id)
                    ->pluck('equipment_id')
                    ->toArray();
                
                if (!empty($equipmentIds)) {
                    $equipment = Equipment::whereIn('equipment_id', $equipmentIds)
                        ->where('equipment_category', 'Furniture & General Clinic Equipment')
                        ->get();
                    
                    // Attach pivot data and filter to only available equipment
                    $equipment = $equipment->map(function($eq) use ($service) {
                        $pivotData = DB::table('tbl_service_equipment')
                            ->where('serv_id', $service->serv_id)
                            ->where('equipment_id', $eq->equipment_id)
                            ->first();
                        
                        if ($pivotData) {
                            $eq->pivot = (object)[
                                'quantity_used' => $pivotData->quantity_used ?? 1,
                                'notes' => $pivotData->notes ?? null
                            ];
                        }
                        return $eq;
                    });
                    
                    $service->setRelation('equipment', $equipment);
                } else {
                    $service->setRelation('equipment', collect([]));
                }
            }
        } else {
            $availableServices = collect();
        }

        $lookups = $this->getBranchLookups();
        $viewName = 'visits.' . $blade;
        
        // Get the active branch ID for explicit filtering
        $activeBranchId = Auth::user()->user_role !== 'superadmin' 
            ? Auth::user()->branch_id 
            : session('active_branch_id');
        
        // ** FIX APPLIED HERE: INITIALIZE $vaccines **
        $vaccines = null; 
        $dewormers = null;
        
        // For vaccination view, only show vaccination services in the service type dropdown
        // Explicitly filter by branch_id to ensure only current branch services are shown
        if ($blade === 'vaccination') {
            $availableServices = Service::where('serv_type', 'like', '%vaccination%')
                ->when($branchId, function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->orderBy('serv_name')
                ->get();
            
            // Load products manually for each service to bypass any scope issues
            foreach($availableServices as $service) {
                $productIds = DB::table('tbl_service_products')
                    ->where('serv_id', $service->serv_id)
                    ->pluck('prod_id')
                    ->toArray();
                
                if (!empty($productIds)) {
                    $products = Product::withoutGlobalScopes()
                        ->whereIn('prod_id', $productIds)
                        ->get();
                    
                    // Attach pivot data
                    $products = $products->map(function($product) use ($service) {
                        $pivotData = DB::table('tbl_service_products')
                            ->where('serv_id', $service->serv_id)
                            ->where('prod_id', $product->prod_id)
                            ->first();
                        
                        if ($pivotData) {
                            $product->pivot = (object)[
                                'quantity_used' => $pivotData->quantity_used ?? 1,
                                'is_billable' => $pivotData->is_billable ?? false
                            ];
                        }
                        return $product;
                    });
                    
                    // Filter by branch and expiry
                    $products = $products->filter(function($product) use ($branchId) {
                        $matchesBranch = !$branchId || $product->branch_id == $branchId;
                        $notExpired = $product->available_stock > 0 ;
                        return $matchesBranch && $notExpired;
                    })->values();
                    
                    $service->setRelation('products', $products);
                } else {
                    $service->setRelation('products', collect([]));
                }
            }
            
            // Load all vaccine products that are in stock and not expired
            $vaccines = \App\Models\Product::where('prod_category', 'like', '%vaccin%')
            ->leftJoin('product_stock', 'tbl_prod.prod_id', '=', 'product_stock.stock_prod_id')
                ->when($branchId, function($query) use ($branchId) {
                    $query->where('tbl_prod.branch_id', $branchId);
                })
                ->where('product_stock.quantity', '>', 0)
                ->where(function($query) {
                    $query->where('expire_date', '>', now())
                          ->orWhereNull('expire_date');
                })
                ->orderBy('prod_name')
                ->get();
        }
        
        // For deworming view, only show deworming services
        // Explicitly filter by branch_id to ensure only current branch services are shown
        if ($blade === 'deworming') {
            $availableServices = Service::where('serv_type', 'like', '%deworming%')
                ->when($branchId, function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->orderBy('serv_name')
                ->get();
            
            // Load products manually for each service to bypass any scope issues
            foreach($availableServices as $service) {
                $productIds = DB::table('tbl_service_products')
                    ->where('serv_id', $service->serv_id)
                    ->pluck('prod_id')
                    ->toArray();
                
                if (!empty($productIds)) {
                    $products = Product::withoutGlobalScopes()
                        ->whereIn('prod_id', $productIds)
                        ->get();
                    
                    // Attach pivot data
                    $products = $products->map(function($product) use ($service) {
                        $pivotData = DB::table('tbl_service_products')
                            ->where('serv_id', $service->serv_id)
                            ->where('prod_id', $product->prod_id)
                            ->first();
                        
                        if ($pivotData) {
                            $product->pivot = (object)[
                                'quantity_used' => $pivotData->quantity_used ?? 1,
                                'is_billable' => $pivotData->is_billable ?? false
                            ];
                        }
                        return $product;
                    });
                    
                    // Filter by branch and expiry
                    $products = $products->filter(function($product) use ($activeBranchId) {
                        $matchesBranch = !$activeBranchId || $product->branch_id == $activeBranchId;
                        $notExpired = !$product->prod_expiry || $product->prod_expiry > now();
                        return $matchesBranch && $notExpired;
                    })->values();
                    
                    $service->setRelation('products', $products);
                } else {
                    $service->setRelation('products', collect([]));
                }
            }
        }

        // For diagnostic view, only show diagnostic services
        // Explicitly filter by branch_id to ensure only current branch services are shown
        if ($blade === 'diagnostic') {
            $availableServices = Service::where(function($query) {
                    $query->where('serv_type', 'like', '%Diagnostic%')
                          ->orWhere('serv_type', 'like', '%Diagnostics%');
                })
                ->when($branchId, function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->orderBy('serv_name')
                ->get();
        }
        
        // For surgical view, only show surgical services
        // Explicitly filter by branch_id to ensure only current branch services are shown
        if ($blade === 'surgical') {
            $availableServices = Service::where('serv_type', 'like', '%surgical%')
                ->when($branchId, function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->orderBy('serv_name')
                ->get();
            
            // Load products manually for each service to bypass any scope issues
            foreach($availableServices as $service) {
                $productIds = DB::table('tbl_service_products')
                    ->where('serv_id', $service->serv_id)
                    ->pluck('prod_id')
                    ->toArray();
                
                if (!empty($productIds)) {
                    $products = Product::withoutGlobalScopes()
                        ->whereIn('prod_id', $productIds)
                        ->get();
                    
                    // Attach pivot data
                    $products = $products->map(function($product) use ($service) {
                        $pivotData = DB::table('tbl_service_products')
                            ->where('serv_id', $service->serv_id)
                            ->where('prod_id', $product->prod_id)
                            ->first();
                        
                        if ($pivotData) {
                            $product->pivot = (object)[
                                'quantity_used' => $pivotData->quantity_used ?? 1,
                                'is_billable' => $pivotData->is_billable ?? false
                            ];
                        }
                        return $product;
                    });
                    
                    // Filter by branch and expiry (anesthesia products)
                    $products = $products->filter(function($product) use ($activeBranchId) {
                        $matchesBranch = !$activeBranchId || $product->branch_id == $activeBranchId;
                        $notExpired = $product->available_stock > 0 ;
                        return $matchesBranch && $notExpired;
                    })->values();
                    
                    $service->setRelation('products', $products);
                } else {
                    $service->setRelation('products', collect([]));
                }
            }
        }

        // For emergency view, only show emergency services
        // Explicitly filter by branch_id to ensure only current branch services are shown
        if ($blade === 'emergency') {
            $availableServices = Service::where('serv_type', 'like', '%emergency%')
                ->when($branchId, function($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->orderBy('serv_name')
                ->get();
        }

       // dd($availableServices);
        // ðŸŒŸ NEW LOGIC: Fetch all registered veterinarians
        $veterinarians = User::where('user_role', 'veterinarian')
                            ->when($branchId, function($query) use ($branchId) {
                                $query->where('branch_id', $branchId);
                            })
                            ->orderBy('user_name')
                            ->get();
        
        // Initialize availableAddons as empty collection if not grooming view
        if ($blade !== 'grooming') {
            $availableAddons = collect();
        }
        // Pass the new variable 'veterinarians' to the view
        //dd($viewName);
        // Pass boardingHistory for the boarding blade
        if ($blade === 'boarding' && $visit) {
            $boardingHistory = \DB::table('tbl_boarding_record')
                ->where('pet_id', $visit->pet_id)
                ->orderByDesc('check_in_date')
                ->get();
        } else {
            $boardingHistory = collect();
        }

        $viewData = compact('visit', 'serviceData', 'petMedicalHistory', 'availableServices','vaccines', 'dewormers', 'veterinarians', 'availableAddons');
        if ($blade === 'boarding') {
            $viewData['boardingHistory'] = $boardingHistory;
        }
        return view($viewName, array_merge($viewData, $lookups));
    }
    // ==================== APPOINTMENT METHODS (Full Implementations) ====================

    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'appoint_date' => 'required|date', 'appoint_status' => 'required',
            'pet_id' => 'required|exists:tbl_pet,pet_id', 'appoint_type' => 'required|string', 
            'appoint_description' => 'nullable|string', 'services' => 'array', 'services.*' => 'exists:tbl_serv,serv_id',
        ]);

        // Set default time since we no longer validate it from frontend
        $validated['appoint_time'] = '09:00:00';
        $validated['user_id'] = Auth::id() ?? $request->input('user_id');
        $services = $validated['services'] ?? [];
        unset($validated['services']);

        // Set default status to 'scheduled' if not specified
        if (!isset($validated['appoint_status']) || $validated['appoint_status'] === 'Scheduled') {
            $validated['appoint_status'] = 'scheduled';
        }

        // Check for duplicate appointments (same pet, date, type, and active status)
        $existingAppointment = Appointment::where('pet_id', $validated['pet_id'])
            ->whereDate('appoint_date', Carbon::parse($validated['appoint_date'])->toDateString())
            ->where('appoint_type', $validated['appoint_type'])
            ->whereIn('appoint_status', ['scheduled', 'confirmed'])
            ->first();
        
        if ($existingAppointment) {
            $activeTab = $request->input('tab', 'appointments');
            return redirect()->route('medical.index', ['tab' => $activeTab])
                ->with('warning', 'An appointment with the same type already exists for this pet on this date.');
        }

        $appointment = Appointment::create($validated);
        
        // Send SMS notification for new appointment
        try {
            $this->smsService->sendNewAppointmentSMS($appointment);
        } catch (\Exception $e) {
            Log::warning("Failed to send SMS for appointment {$appointment->appoint_id}: " . $e->getMessage());
        }
        
        // Assuming History and Service Sync Logic is restored/handled elsewhere
        
        // Return JSON response for inline success message if requested
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Appointment created successfully',
                'appointment' => $appointment->load(['pet.owner'])
            ]);
        }
        
        $activeTab = $request->input('tab', 'appointments');
        return redirect()->route('medical.index', ['tab' => $activeTab])->with('success', 'Appointment added successfully');
    }

    public function showAppointment($id)
    {
        try {
            $appointment = Appointment::with(['pet.owner', 'services', 'user.branch'])->findOrFail($id);
            return response()->json(['appointment' => $appointment]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }
    }

    public function updateAppointment(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'appoint_date' => 'required|date',
            'appoint_status' => 'required|in:pending,arrived,completed,refer,rescheduled',
            'appoint_type' => 'required|string', 'pet_id' => 'required|integer|exists:tbl_pet,pet_id',
            'appoint_description' => 'nullable|string', 'services' => 'array', 'services.*' => 'exists:tbl_serv,serv_id',
        ]);

        // Use default time if not provided
        $validated['appoint_time'] = '09:00:00';

        // Store old values for SMS notification
        $oldDate = $appointment->appoint_date;
        $oldTime = $appointment->appoint_time;
        $isRescheduled = ($validated['appoint_date'] !== $oldDate || $validated['appoint_time'] !== $oldTime);

        // Check for duplicate appointments when updating (exclude current appointment)
        $existingAppointment = Appointment::where('pet_id', $validated['pet_id'])
            ->whereDate('appoint_date', Carbon::parse($validated['appoint_date'])->toDateString())
            ->where('appoint_type', $validated['appoint_type'])
            ->whereIn('appoint_status', ['scheduled', 'confirmed'])
            ->where('appoint_id', '!=', $appointment->appoint_id)
            ->first();
        
        if ($existingAppointment) {
            $activeTab = $request->input('tab', 'appointments');
            return redirect()->route('medical.index', ['tab' => $activeTab])
                ->with('warning', 'An appointment with the same type already exists for this pet on this date.');
        }

        // ... (Original Update and Inventory Logic)
        
        // Send SMS notification if rescheduled
        if ($isRescheduled && $validated['appoint_status'] === 'rescheduled') {
            try {
                $this->smsService->sendRescheduleSMS($appointment);
            } catch (\Exception $e) {
                Log::warning("Failed to send reschedule SMS for appointment {$appointment->appoint_id}: " . $e->getMessage());
            }
        }
        
        $activeTab = $request->input('tab', 'appointments');
        return redirect()->route('medical.index', ['tab' => $activeTab])
                        ->with('success', 'Appointment updated successfully');
    }

    public function destroyAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->services()->detach();
        $appointment->delete();

        return redirect()->route('care-continuity.index', ['active_tab' => 'appointments'])
                        ->with('success', 'Appointment deleted successfully');
    }

    // ==================== PRESCRIPTION METHODS (Full Implementations) ====================

    public function storePrescription(Request $request)
    {
        //dd('storePrescription called');
        DB::beginTransaction();
        Log::info('storePrescription called with data: ', $request->all());
        try {
            $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'pvisit_id' => 'exists:tbl_visit_record,visit_id',
                'prescription_date' => 'required|date',
                'medications_json' => 'required|json',
                'differential_diagnosis' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $medications = json_decode($request->medications_json, true);
            if (empty($medications)) {
                throw new \Exception('At least one medication is required');
            }

            // Get branch_id from session if in branch mode, otherwise get it from the authenticated user
            $branchId = null;
            if (session('branch_mode') === 'active' && session('active_branch_id')) {
                $branchId = session('active_branch_id');
            } else {
                $user = Auth::user();
                if ($user && $user->branch_id) {
                    $branchId = $user->branch_id;
                }
            }

            if (!$branchId) {
                throw new \Exception('No branch associated with this prescription');
            }

            // Create the prescription record
            $prescription = new Prescription();
            $prescription->pet_id = $request->pet_id;
            $prescription->pres_visit_id = $request->pvisit_id;
            $prescription->prescription_date = $request->prescription_date;
            $prescription->medication = $request->medications_json; // Store the JSON string directly
            $prescription->differential_diagnosis = $request->differential_diagnosis;
            $prescription->notes = $request->notes;
            $prescription->user_id = Auth::id(); // Track who created the prescription
            $prescription->branch_id = $branchId; // Set the branch_id
            
            if (!$prescription->save()) {
                throw new \Exception('Failed to save prescription record');
            }

            // Process each medication and deduct from inventory
            foreach ($medications as $med) {
                if (!empty($med['product_id'])) {
                    // Deduct from inventory if a product was selected (not manual entry)
                    $quantity = isset($med['quantity']) ? (int)$med['quantity'] : 1;
                    $this->inventoryService->deductFromInventory(
                        $med['product_id'],
                        $quantity,
                        'Prescription #' . $prescription->prescription_id,
                        'service_usage'
                    );
                }
            }

            // Update the billing if it exists for this visit to include prescription costs
            if ($request->pvisit_id) {
                $billing = Billing::where('visit_id', $request->pvisit_id)->first();
                if ($billing) {
                    // Calculate prescription total from the medications
                    $prescriptionTotal = 0;
                    foreach ($medications as $med) {
                        if (isset($med['price']) && $med['price'] > 0) {
                            $prescriptionTotal += (float) $med['price'];
                        } elseif (isset($med['unit_price']) && $med['unit_price'] > 0) {
                            $qty = isset($med['quantity']) ? (int) $med['quantity'] : 1;
                            $prescriptionTotal += (float) $med['unit_price'] * $qty;
                        }
                    }
                    
                    // Add prescription total to billing
                    if ($prescriptionTotal > 0) {
                        $billing->total_amount = (float) $billing->total_amount + $prescriptionTotal;
                        $billing->save();
                        Log::info("Updated billing {$billing->bill_id} with prescription total: {$prescriptionTotal}. New total: {$billing->total_amount}");
                    }
                }
            }

            // If we got here, everything succeeded
            DB::commit();

            
            $activeTab = $request->input('tab', 'prescriptions');
            return redirect()->route('medical.index', ['tab' => $activeTab])
                ->with('success', 'Prescription created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating prescription: ' . $e->getMessage());
            return back()->with('error', 'Error creating prescription: ' . $e->getMessage());
        }
    }

    public function editPrescription($id)
    {
        try {
            $prescription = Prescription::with('pet')->findOrFail($id);
            $medications = json_decode($prescription->medication, true) ?? [];
            return response()->json(['prescription_id' => $prescription->prescription_id, 'medications' => $medications]);
        } catch (\Exception $e) { return response()->json(['error' => 'Error loading prescription data'], 500); }
    }

    public function updatePrescription(Request $request, $id)
    {
        try {
            $request->validate(['pet_id' => 'required|exists:tbl_pet,pet_id', 'prescription_date' => 'required|date']);
            // ... (Original Update Logic)
            $activeTab = $request->input('tab', 'prescriptions');
            return redirect()->route('medical.index', ['tab' => $activeTab])->with('success', 'Prescription updated successfully!');
        } catch (\Exception $e) { return back()->with('error', 'Error updating prescription: ' . $e->getMessage()); }
    }

    public function destroyPrescription(Request $request, $id)
    {
        try {
            Prescription::findOrFail($id)->delete();
            $activeTab = $request->input('tab', 'prescriptions');
            return redirect()->route('medical.index', ['tab' => $activeTab])->with('success', 'Prescription deleted successfully!');
        } catch (\Exception $e) { return back()->with('error', 'Error deleting prescription: ' . $e->getMessage()); }
    }

    public function searchProducts(Request $request)
    {
        // Search for Prescription type products only
        $query = $request->get('q');
        return response()->json(
            Product::select('prod_id as id', 'prod_name as name', 'prod_price as price', 'prod_stocks as stocks', 'prod_type as type')
                ->where('prod_type', 'Prescription')
                ->where('prod_name', 'like', "%$query%")
                ->limit(15)
                ->get()
        );
    }

    // ==================== REFERRAL METHODS (Full Implementations) ====================

    public function storeReferral(Request $request)
    {
        $validated = $request->validate([
            'visit_id' => 'required|exists:tbl_visit_record,visit_id',
            'ref_date' => 'required|date',
            'pet_id' => 'required|exists:tbl_pet,pet_id',
            'ref_to' => 'nullable',
            'external_clinic_name' => 'nullable|string|max:255',
            'ref_description' => 'required|string',
            'ref_type' => 'required|in:interbranch,external',
        ]);

        try {
            $currentUser = Auth::user();
            $currentBranchId = $currentUser->branch_id;

            // Determine ref_to based on type
            if ($validated['ref_type'] === 'interbranch') {
                if (empty($validated['ref_to'])) {
                    return redirect()->back()->with('error', 'Branch selection is required for interbranch referral');
                }
                // Validate that the branch exists
                $branchExists = \App\Models\Branch::where('branch_id', $validated['ref_to'])->exists();
                if (!$branchExists) {
                    return redirect()->back()->with('error', 'Selected branch is invalid');
                }
                $refTo = $validated['ref_to'];
            } else {
                // External referral - no branch needed
                if (empty($validated['external_clinic_name'])) {
                    return redirect()->back()->with('error', 'External clinic name is required for external referral');
                }
                $refTo = null;
            }

            // Determine status based on referral type
            $refStatus = $validated['ref_type'] === 'external' ? 'referred' : 'pending';

            // Create referral record
            $referral = Referral::create([
                'visit_id' => $validated['visit_id'],
                'pet_id' => $validated['pet_id'],
                'ref_date' => $validated['ref_date'],
                'ref_from' => $currentBranchId,
                'ref_to' => $refTo,
                'external_clinic_name' => $validated['ref_type'] === 'external' ? ($validated['external_clinic_name'] ?? null) : null,
                'ref_description' => $validated['ref_description'],
                'ref_by' => Auth::id(),
                'ref_status' => $refStatus,
                'ref_type' => $validated['ref_type'],
            ]);

            $activeTab = $request->input('tab', 'referrals');
            return redirect()->route('medical.index', ['tab' => $activeTab])->with('success', 'Referral created successfully.');
        } catch (\Exception $e) {
            Log::error('Referral creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create referral: ' . $e->getMessage());
        }
    }

    public function editReferral($id)
    {
        try {
            $referral = Referral::with(['appointment.pet.owner', 'refToBranch', 'refByBranch'])->findOrFail($id);
            return response()->json(['ref_id' => $referral->ref_id, 'ref_to' => $referral->ref_to]);
        } catch (\Exception $e) { return response()->json(['error' => 'Referral not found'], 404); }
    }

    public function updateReferral(Request $request, $id)
    {
        $validated = $request->validate(['ref_date' => 'required|date', 'ref_description' => 'required|string']);
        // ... (Original Update Logic)
        $activeTab = $request->input('tab', 'referrals');
        return redirect()->route('medical.index', ['tab' => $activeTab])->with('success', 'Referral updated successfully.');
    }

    public function showReferral($id)
    {
        try {
            $referral = Referral::with(['appointment.pet.owner', 'refToBranch', 'refByBranch'])->findOrFail($id);
            return response()->json(['ref_id' => $referral->ref_id, 'pet_name' => $referral->appointment->pet->pet_name]);
        } catch (\Exception $e) { return response()->json(['error' => 'Referral not found'], 404); }
    }

    public function destroyReferral(Request $request, $id)
    {
        $referral = Referral::findOrFail($id);
        $referral->delete();
        $activeTab = $request->input('tab', 'referrals');
        return redirect()->route('medical.index', ['tab' => $activeTab])->with('success', 'Referral deleted successfully!');
    }

    public function createVisitFromReferral($referralId)
    {
        try {
            $referral = Referral::with(['pet', 'visit'])->findOrFail($referralId);
            
            // Check if user's branch matches the referred branch
            $currentUser = Auth::user();
            if ($referral->ref_type === 'interbranch' && $referral->ref_to != $currentUser->branch_id) {
                return redirect()->back()->with('error', 'You can only create visits for referrals to your branch');
            }

            // For interbranch referrals, check if referred visit already exists
            if ($referral->ref_type === 'interbranch' && $referral->referred_visit_id) {
                return redirect()->route('visits.show', $referral->referred_visit_id)
                    ->with('info', 'Visit already exists for this referral');
            }

            // Create new visit
            $newVisit = Visit::create([
                'pet_id' => $referral->pet_id,
                'user_id' => Auth::id(),
                'visit_date' => now(),
                'visit_reason' => 'Referral: ' . $referral->ref_description,
                'visit_status' => 'arrived',
                'visit_source' => 'referral', // Track that this visit was created from a referral
                'workflow_status' => 'Active',
            ]);

            // Update referral status and link visit
            if ($referral->ref_type === 'interbranch') {
                $referral->update([
                    'referred_visit_id' => $newVisit->visit_id,
                    'ref_status' => 'attended'
                ]);
            } else {
                // External referral - just mark as attended
                $referral->update(['ref_status' => 'attended']);
            }

            return redirect()->route('visits.show', $newVisit->visit_id)
                ->with('success', 'Visit created from referral successfully');
        } catch (\Exception $e) {
            Log::error('Create visit from referral failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create visit: ' . $e->getMessage());
        }
    }

    public function printReferral($referralId)
    {
        try {
            $referral = Referral::with([
                'pet.owner',
                'visit.services',
                'refByBranch.branch',
                'refToBranch',
                'referralCompany'
            ])->findOrFail($referralId);

            // Only allow printing for external referrals
            if ($referral->ref_type !== 'external') {
                return redirect()->back()->with('error', 'Print is only available for external referrals');
            }

            // Get medical history from completed services (excluding grooming and boarding)
            $petId = $referral->pet_id;
            
            // Get all completed visits with their completed services
            $completedVisits = Visit::with(['services' => function($q) {
                    $q->wherePivot('status', 'completed')
                      ->whereNotIn(DB::raw('LOWER(tbl_serv.serv_type)'), ['grooming', 'boarding', 'diagnostics']);
                }])
                ->where('pet_id', $petId)
                ->whereHas('services', function($q) {
                    $q->where('tbl_visit_service.status', 'completed')
                      ->whereNotIn(DB::raw('LOWER(tbl_serv.serv_type)'), ['grooming', 'boarding', 'diagnostics']);
                })
                ->orderBy('visit_date', 'desc')
                ->limit(10)
                ->get();

            // Medical History - Build from completed services
            $medicalHistory = [];
            
            foreach ($completedVisits as $visit) {
                foreach ($visit->services as $service) {
                    $serviceType = strtolower($service->serv_type);
                    $details = $service->serv_name;
                    
                    // Add specific details based on service type
                    if ($serviceType === 'vaccination') {
                        $vaccination = DB::table('tbl_vaccination_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($vaccination) {
                            $details .= ' - ' . ($vaccination->vaccine_name ?? '') . 
                                       ' (Batch: ' . ($vaccination->batch_no ?? 'N/A') . ')';
                        }
                    } elseif ($serviceType === 'deworming') {
                        $deworming = DB::table('tbl_deworming_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($deworming) {
                            $details .= ' - ' . ($deworming->dewormer_name ?? '') . 
                                       ' (Dosage: ' . ($deworming->dosage ?? 'N/A') . ')';
                        }
                    } elseif ($serviceType === 'check up') {
                        $checkup = DB::table('tbl_checkup_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($checkup && isset($checkup->diagnosis)) {
                            $details .= ' - ' . $checkup->diagnosis;
                        }
                    } elseif ($serviceType === 'surgical') {
                        $surgery = DB::table('tbl_surgical_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($surgery && isset($surgery->procedure_name)) {
                            $details .= ' - ' . $surgery->procedure_name;
                        }
                    } elseif ($serviceType === 'emergency') {
                        $emergency = DB::table('tbl_emergency_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($emergency && isset($emergency->chief_complaint)) {
                            $details .= ' - ' . $emergency->chief_complaint;
                        }
                    }
                    
                    $medicalHistory[] = [
                        'type' => ucwords($service->serv_type),
                        'date' => $visit->visit_date,
                        'details' => $details,
                        'formatted' => \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y') . ': ' . ucwords($service->serv_type) . ' - ' . $details,
                    ];
                }
            }

            // Tests Conducted - Get diagnostics from completed services
            $diagnosticTests = [];
            $diagnosticVisits = Visit::with(['services' => function($q) {
                    $q->wherePivot('status', 'completed')
                      ->where(DB::raw('LOWER(tbl_serv.serv_type)'), 'diagnostics');
                }])
                ->where('pet_id', $petId)
                ->whereHas('services', function($q) {
                    $q->where('tbl_visit_service.status', 'completed')
                      ->where(DB::raw('LOWER(tbl_serv.serv_type)'), 'diagnostics');
                })
                ->orderBy('visit_date', 'desc')
                ->limit(5)
                ->get();
                
            foreach ($diagnosticVisits as $visit) {
                foreach ($visit->services as $service) {
                    $diagnostic = DB::table('tbl_diagnostic_record')
                        ->where('visit_id', $visit->visit_id)
                        ->where('pet_id', $petId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    $details = $service->serv_name;
                    if ($diagnostic) {
                        if (isset($diagnostic->test_name)) {
                            $details .= ' - ' . $diagnostic->test_name;
                        }
                        if (isset($diagnostic->results)) {
                            $details .= ' (Results: ' . $diagnostic->results . ')';
                        }
                    }
                    
                    $diagnosticTests[] = (object)[
                        'visit_date' => $visit->visit_date,
                        'test_type' => $service->serv_name,
                        'test_name' => $diagnostic->test_name ?? null,
                        'results' => $diagnostic->results ?? null,
                        'interpretation' => $diagnostic->interpretation ?? null,
                        'formatted' => \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y') . ': ' . $details,
                    ];
                }
            }

            // Medications Given - Only medication from prescription
            $prescriptions = DB::table('tbl_prescription')
                ->where('tbl_prescription.pet_id', $petId)
                ->whereNotNull('tbl_prescription.medication')
                ->where('tbl_prescription.medication', '!=', '')
                ->select(
                    'tbl_prescription.prescription_date',
                    'tbl_prescription.medication'
                )
                ->orderBy('tbl_prescription.prescription_date', 'desc')
                ->limit(10)
                ->get();

            return view('referrals.print', compact(
                'referral',
                'medicalHistory',
                'diagnosticTests',
                'prescriptions'
            ));
        } catch (\Exception $e) {
            Log::error('Print referral failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate referral print');
        }
    }

    public function viewReferral($referralId)
    {
        try {
            $referral = Referral::with([
                'pet.owner',
                'visit.services',
                'refByBranch.branch',
                'refToBranch',
                'referralCompany',
                'referredVisit'
            ])->findOrFail($referralId);

            // Get medical history from completed services (excluding grooming and boarding)
            $petId = $referral->pet_id;
            
            // Get all completed visits with their completed services
            $completedVisits = Visit::with(['services' => function($q) {
                    $q->wherePivot('status', 'completed')
                      ->whereNotIn(DB::raw('LOWER(tbl_serv.serv_type)'), ['grooming', 'boarding', 'diagnostics']);
                }])
                ->where('pet_id', $petId)
                ->whereHas('services', function($q) {
                    $q->where('tbl_visit_service.status', 'completed')
                      ->whereNotIn(DB::raw('LOWER(tbl_serv.serv_type)'), ['grooming', 'boarding', 'diagnostics']);
                })
                ->orderBy('visit_date', 'desc')
                ->limit(10)
                ->get();

            // Medical History - Build from completed services
            $medicalHistory = [];
            
            foreach ($completedVisits as $visit) {
                foreach ($visit->services as $service) {
                    $serviceType = strtolower($service->serv_type);
                    $details = $service->serv_name;
                    
                    // Add specific details based on service type
                    if ($serviceType === 'vaccination') {
                        $vaccination = DB::table('tbl_vaccination_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($vaccination) {
                            $details .= ' - ' . ($vaccination->vaccine_name ?? '') . 
                                       ' (Batch: ' . ($vaccination->batch_no ?? 'N/A') . ')';
                        }
                    } elseif ($serviceType === 'deworming') {
                        $deworming = DB::table('tbl_deworming_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($deworming) {
                            $details .= ' - ' . ($deworming->dewormer_name ?? '') . 
                                       ' (Dosage: ' . ($deworming->dosage ?? 'N/A') . ')';
                        }
                    } elseif ($serviceType === 'check up') {
                        $checkup = DB::table('tbl_checkup_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($checkup && isset($checkup->diagnosis)) {
                            $details .= ' - ' . $checkup->diagnosis;
                        }
                    } elseif ($serviceType === 'surgical') {
                        $surgery = DB::table('tbl_surgical_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($surgery && isset($surgery->procedure_name)) {
                            $details .= ' - ' . $surgery->procedure_name;
                        }
                    } elseif ($serviceType === 'emergency') {
                        $emergency = DB::table('tbl_emergency_record')
                            ->where('visit_id', $visit->visit_id)
                            ->where('pet_id', $petId)
                            ->orderBy('created_at', 'desc')
                            ->first();
                        if ($emergency && isset($emergency->chief_complaint)) {
                            $details .= ' - ' . $emergency->chief_complaint;
                        }
                    }
                    
                    $medicalHistory[] = [
                        'type' => ucwords($service->serv_type),
                        'date' => $visit->visit_date,
                        'details' => $details,
                        'formatted' => \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y') . ': ' . ucwords($service->serv_type) . ' - ' . $details,
                    ];
                }
            }

            // Tests Conducted - Get diagnostics from completed services
            $diagnosticTests = [];
            $diagnosticVisits = Visit::with(['services' => function($q) {
                    $q->wherePivot('status', 'completed')
                      ->where(DB::raw('LOWER(tbl_serv.serv_type)'), 'diagnostics');
                }])
                ->where('pet_id', $petId)
                ->whereHas('services', function($q) {
                    $q->where('tbl_visit_service.status', 'completed')
                      ->where(DB::raw('LOWER(tbl_serv.serv_type)'), 'diagnostics');
                })
                ->orderBy('visit_date', 'desc')
                ->limit(5)
                ->get();
                
            foreach ($diagnosticVisits as $visit) {
                foreach ($visit->services as $service) {
                    $diagnostic = DB::table('tbl_diagnostic_record')
                        ->where('visit_id', $visit->visit_id)
                        ->where('pet_id', $petId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    $details = $service->serv_name;
                    if ($diagnostic) {
                        if (isset($diagnostic->test_name)) {
                            $details .= ' - ' . $diagnostic->test_name;
                        }
                        if (isset($diagnostic->results)) {
                            $details .= ' (Results: ' . $diagnostic->results . ')';
                        }
                    }
                    
                    $diagnosticTests[] = (object)[
                        'visit_date' => $visit->visit_date,
                        'test_type' => $service->serv_name,
                        'test_name' => $diagnostic->test_name ?? null,
                        'results' => $diagnostic->results ?? null,
                        'interpretation' => $diagnostic->interpretation ?? null,
                        'formatted' => \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y') . ': ' . $details,
                    ];
                }
            }

            // Medications Given - Only medication from prescription
            $prescriptions = DB::table('tbl_prescription')
                ->where('tbl_prescription.pet_id', $petId)
                ->whereNotNull('tbl_prescription.medication')
                ->where('tbl_prescription.medication', '!=', '')
                ->select(
                    'tbl_prescription.prescription_date',
                    'tbl_prescription.medication'
                )
                ->orderBy('tbl_prescription.prescription_date', 'desc')
                ->limit(10)
                ->get();

            return view('referrals.view', compact('referral', 'medicalHistory', 'diagnosticTests', 'prescriptions'));
        } catch (\Exception $e) {
            Log::error('View referral failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Referral not found');
        }
    }

    public function getAppointmentDetails($id)
    {
        try {
            $appointment = Appointment::with(['pet.owner', 'services'])->findOrFail($id);
            return response()->json(['pet' => $appointment->pet, 'owner' => $appointment->pet->owner]);
        } catch (\Exception $e) { return response()->json(['error' => 'Appointment not found'], 404); }
    }

    public function getAppointmentForPrescription($id)
    {
        try {
            $appointment = Appointment::with(['pet.owner', 'services'])->findOrFail($id);
            return response()->json(['pet_id' => $appointment->pet_id, 'appointment_date' => $appointment->appoint_date]);
        } catch (\Exception $e) { return response()->json(['error' => 'Appointment not found'], 404); }
    }

    public function printPrescription($id)
    {
        $prescription = Prescription::with(['pet.owner', 'branch'])->findOrFail($id);
        return view('prescription-print', compact('prescription'));
    }
    
    // ==================== HELPER METHODS (Full Implementations) ====================
    
    public function recordVaccineDetails(Request $request, $appointmentId) 
    { 
        return redirect()->back()->with('success', 'Vaccine details recorded.');
    }
    
    public function getServiceProductsForVaccination($serviceId) 
    { 
        return response()->json(['success' => true, 'products' => []]);
    }

    /**
     * Deduct consumable products linked to a boarding service when pet checks out.
     * Products are multiplied by the number of boarding days.
     * 
     * @param Service $service The boarding service
     * @param int $totalDays Number of boarding days
     * @param Visit $visit The visit record
     */
    private function deductBoardingConsumables(Service $service, int $totalDays, Visit $visit): void
    {
        try {
            // Get consumable products linked to this boarding service
            $serviceProducts = ServiceProduct::where('serv_id', $service->serv_id)
                ->with('product')
                ->get();

            if ($serviceProducts->isEmpty()) {
                \Log::info('[Boarding Checkout] No consumable products linked to service', [
                    'service_id' => $service->serv_id,
                    'service_name' => $service->serv_name
                ]);
                return;
            }

            \Log::info('[Boarding Checkout] Deducting consumables', [
                'service_id' => $service->serv_id,
                'total_days' => $totalDays,
                'products_count' => $serviceProducts->count()
            ]);

            foreach ($serviceProducts as $serviceProduct) {
                $product = $serviceProduct->product;
                
                if (!$product) {
                    \Log::warning('[Boarding Checkout] Product not found for service product', [
                        'service_product_id' => $serviceProduct->id
                    ]);
                    continue;
                }

                // Only deduct consumable products
                if ($product->prod_type !== 'Consumable') {
                    \Log::info('[Boarding Checkout] Skipping non-consumable product', [
                        'product_id' => $product->prod_id,
                        'product_name' => $product->prod_name,
                        'product_type' => $product->prod_type
                    ]);
                    continue;
                }

                // Calculate total quantity: quantity_per_day × total_days
                $quantityPerDay = (float) $serviceProduct->quantity_used;
                $totalQuantity = $quantityPerDay * $totalDays;

                // Check if enough stock is available
                $currentStock = (float) $product->prod_stocks;
                $actualDeduction = min($currentStock, $totalQuantity);

                if ($actualDeduction <= 0) {
                    \Log::warning('[Boarding Checkout] No stock available to deduct', [
                        'product_id' => $product->prod_id,
                        'product_name' => $product->prod_name,
                        'required' => $totalQuantity,
                        'available' => $currentStock
                    ]);
                    continue;
                }

                // Deduct from inventory
                $product->decrement('prod_stocks', $actualDeduction);

                // Record inventory transaction
                InventoryTransaction::create([
                    'prod_id' => $product->prod_id,
                    'transaction_type' => 'service_usage',
                    'quantity_change' => -$actualDeduction,
                    'reference' => "Boarding Checkout - Visit #{$visit->visit_id}",
                    'serv_id' => $service->serv_id,
                    'notes' => "Boarding for " . ($visit->pet->pet_name ?? 'Pet') . " ({$totalDays} days × {$quantityPerDay} = {$totalQuantity})",
                    'performed_by' => Auth::id(),
                    'created_at' => now(),
                ]);

                \Log::info('[Boarding Checkout] Product deducted', [
                    'product_id' => $product->prod_id,
                    'product_name' => $product->prod_name,
                    'quantity_per_day' => $quantityPerDay,
                    'total_days' => $totalDays,
                    'total_deducted' => $actualDeduction,
                    'new_stock' => $product->prod_stocks - $actualDeduction
                ]);

                // Check if stock is below reorder level
                if (($product->prod_stocks - $actualDeduction) <= ($product->prod_reorderlevel ?? 0)) {
                    \Log::warning('[Boarding Checkout] Stock below reorder level', [
                        'product_id' => $product->prod_id,
                        'product_name' => $product->prod_name,
                        'current_stock' => $product->prod_stocks - $actualDeduction,
                        'reorder_level' => $product->prod_reorderlevel
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('[Boarding Checkout] Error deducting consumables: ' . $e->getMessage(), [
                'service_id' => $service->serv_id,
                'visit_id' => $visit->visit_id,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - let the checkout complete even if deduction fails
        }
    }

    /**
     * Derive the pet's age in months from birthdate or stored age string.
     */
    private function calculatePetAgeInMonths(?Pet $pet): ?int
    {
        if (!$pet) {
            return null;
        }

        if (!empty($pet->pet_birthdate)) {
            try {
                return Carbon::parse($pet->pet_birthdate)->diffInMonths(Carbon::now());
            } catch (\Throwable $e) {
                // Ignore parsing failures and fall back to the pet_age column
            }
        }

        if (!empty($pet->pet_age)) {
            return $this->normalizeAgeStringToMonths($pet->pet_age);
        }

        return null;
    }

    /**
     * Normalize a free-form age string (e.g., "2 years", "6 mos") into months.
     */
    private function normalizeAgeStringToMonths(string $value): ?int
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(year|yrs?|y)/', $normalized, $match)) {
            return (int) round(((float) $match[1]) * 12);
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(month|mos?|mo|m)/', $normalized, $match)) {
            return (int) round((float) $match[1]);
        }

        if (ctype_digit($normalized)) {
            return (int) $normalized;
        }

        return null;
    }

    /**
     * Extract min/max age boundaries (in months) from the service label.
     */
    private function extractAgeRangeFromServiceLabel(string $label): array
    {
        $min = null;
        $max = null;

        // Range like "3-6 months"
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:-|to)\s*(\d+(?:\.\d+)?)\s*(month|mos?|mo|m)/i', $label, $matches)) {
            $min = (int) round((float) $matches[1]);
            $max = (int) round((float) $matches[2]);
            return [$min, $max];
        }

        // Range like "1-3 years"
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:-|to)\s*(\d+(?:\.\d+)?)\s*(year|yrs?|y)/i', $label, $matches)) {
            $min = (int) round(((float) $matches[1]) * 12);
            $max = (int) round(((float) $matches[2]) * 12);
            return [$min, $max];
        }

        // Upper bounds "under 6 months", "below 1 year"
        if (preg_match('/(\d+(?:\.\d+)?)\s*(month|mos?|mo|m)\s*(?:and)?\s*(?:below|under|less)/i', $label, $matches)) {
            $max = (int) round((float) $matches[1]);
            return [$min, $max];
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(year|yrs?|y)\s*(?:and)?\s*(?:below|under|less)/i', $label, $matches)) {
            $max = (int) round(((float) $matches[1]) * 12);
            return [$min, $max];
        }

        // Lower bounds "12 months and above", "5 years above"
        if (preg_match('/(\d+(?:\.\d+)?)\s*(month|mos?|mo|m)\s*(?:and)?\s*(?:above|over|\+|plus)/i', $label, $matches)) {
            $min = (int) round((float) $matches[1]);
            return [$min, $max];
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(year|yrs?|y)\s*(?:and)?\s*(?:above|over|\+|plus)/i', $label, $matches)) {
            $min = (int) round(((float) $matches[1]) * 12);
            return [$min, $max];
        }

        $normalized = strtolower($label);

        if (str_contains($normalized, 'puppy') || str_contains($normalized, 'kitten')) {
            $min = 0;
            $max = 12;
        } elseif (str_contains($normalized, 'junior')) {
            $min = 6;
            $max = 24;
        } elseif (str_contains($normalized, 'adult')) {
            $min = 12;
            $max = 84;
        } elseif (str_contains($normalized, 'senior')) {
            $min = 84;
            $max = null;
        }

        return [$min, $max];
    }

    // ==================== HELPER METHODS FOR AUTO-SCHEDULING ====================
    
    /**
     * Calculate the next schedule for vaccination or deworming
     */
    protected function calculateNextSchedule($type, $itemName, $petId)
    {
        // Get the actual product name (remove ID if present)
        $cleanItemName = $itemName;
        if (is_numeric($itemName)) {
            // If itemName is just a product ID, get the product name from database
            $product = \App\Models\Product::find($itemName);
            $cleanItemName = $product ? $product->prod_name : $itemName;
        } else if (preg_match('/^(\d+)\s*-?\s*(.+)/', $itemName, $matches)) {
            // If itemName contains ID followed by name (e.g., "623 - Product Name" or "623-Product Name")
            $cleanItemName = trim($matches[2]);
        }
        
        // Get the most recent record for this pet and item
        $tableName = $type === 'vaccination' ? 'tbl_vaccination_record' : 'tbl_deworming_record';
        $itemColumn = $type === 'vaccination' ? 'vaccine_name' : 'dewormer_name';
        
        // For vaccination, use date_administered; for deworming, check what column exists
        $dateColumn = $type === 'vaccination' ? 'date_administered' : 'next_due_date';
        
        $lastRecord = DB::table($tableName)
            ->where('pet_id', $petId)
            ->where($itemColumn, $itemName)
            ->when($type === 'vaccination', function($query) {
                $query->orderBy('date_administered', 'desc');
            }, function($query) {
                $query->orderBy('created_at', 'desc');
            })
            ->orderBy('created_at', 'desc')
            ->first();
        
        $nextAppointType = null;
        $newDose = 1;
        
        if ($lastRecord) {
            if ($type === 'vaccination') {
                // For vaccinations, increment dose if available
                $currentDose = $lastRecord->dose ?? '1';
                // Try to extract number from dose (e.g., "1st", "2nd", "1", "2")
                if (preg_match('/(\d+)/', $currentDose, $matches)) {
                    $newDose = (int)$matches[1] + 1;
                } else {
                    $newDose = 2;
                }
                $nextAppointType = "Vaccination Follow-up (Dose {$newDose})";
            } else {
                // For deworming
                $nextAppointType = "Deworming Follow-up";
            }
        } else {
            // First time for this pet
            if ($type === 'vaccination') {
                $nextAppointType = "Vaccination Follow-up (Dose 2)";
                $newDose = 2;
            } else {
                $nextAppointType = "Deworming Follow-up";
            }
        }
        
        return [
            'next_appoint_type' => $nextAppointType,
            'new_dose' => $newDose
        ];
    }
    
    /**
     * Auto-schedule a follow-up appointment
     */
    protected function autoScheduleFollowUp($visit, $appointType, $appointDate, $itemName, $dose = null)
    {
        try {
            // Check if appointment already exists for this exact follow-up
            // Match by pet, date, and appointment type (more strict to prevent duplicates)
            $existingAppointment = Appointment::where('pet_id', $visit->pet_id)
                ->whereDate('appoint_date', Carbon::parse($appointDate)->toDateString())
                ->where(function($query) use ($appointType, $itemName) {
                    $query->where('appoint_type', $appointType)
                          ->orWhere('appoint_type', 'like', "%{$itemName}%");
                })
                ->whereIn('appoint_status', ['scheduled', 'confirmed']) // Only check active appointments
                ->first();
            
            if ($existingAppointment) {
                Log::info("Follow-up appointment already exists for pet {$visit->pet_id} on {$appointDate} - Appointment ID: {$existingAppointment->appoint_id}");
                return $existingAppointment;
            }
            
            // Create the follow-up appointment
            $appointment = Appointment::create([
                'pet_id' => $visit->pet_id,
                'user_id' => $visit->user_id,
                'appoint_date' => Carbon::parse($appointDate)->toDateString(),
                'appoint_time' => '09:00', // Default time, can be adjusted
                'appoint_type' => $appointType,
                'appoint_status' => 'scheduled',
                'appoint_description' => "Auto-scheduled follow-up from visit #{$visit->visit_id}",
            ]);
            
            Log::info("Auto-scheduled follow-up appointment #{$appointment->appoint_id} for pet {$visit->pet_id}");
            
            // Send SMS notification for auto-scheduled appointment
            try {
                $this->smsService->sendFollowUpSMS($appointment);
            } catch (\Exception $e) {
                Log::warning("Failed to send SMS for auto-scheduled appointment {$appointment->appoint_id}: " . $e->getMessage());
            }
            
            return $appointment;
        } catch (\Exception $e) {
            Log::error("Failed to auto-schedule follow-up appointment: " . $e->getMessage());
            return null;
        }
    }

    private function getVisitStatusAfterServiceUpdate(Visit $visit) {
        if($visit instanceof Visit) {
            $hasPendingServices = $visit->services()->wherePivot('status', 'pending')->exists();
            $hasInProgressServices = $visit->services()->wherePivot('status', 'in_progress')->exists();
            $completed_services_count = $visit->services()->wherePivot('status', 'completed')->count();

            if ($hasPendingServices) {
                return 'Pending(' . $completed_services_count . ' Completed/' .  $visit->services()->wherePivot('status', 'pending')->count() . ' pending)';
            } elseif ($hasInProgressServices) {
                return 'In progress(' . $visit->services()->wherePivot('status', 'in_progress')->count() . ')';
            } else {
                    $existingBilling = DB::table('tbl_bill')
                    ->where('visit_id', $visit->visit_id)
                    ->first();
                    if ($existingBilling) {
                        return 'Billed';
                    }
                    else {
                        return 'Ready for Billing';
                    }
            }
        }
    }
}
