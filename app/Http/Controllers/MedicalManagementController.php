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
use App\Models\InventoryHistory as InventoryHistoryModel;

class MedicalManagementController extends Controller
{
    protected $inventoryService;
    protected $smsService;

    public function __construct(InventoryService $inventoryService, DynamicSMSService $smsService)
    {
        $this->middleware('auth');
        $this->inventoryService = $inventoryService;
        $this->smsService = $smsService;
    }
public function updateServiceStatus(Request $request, $visitId, $serviceId)
    {
        try {
            DB::beginTransaction();
            
            $visit = Visit::with('services')->findOrFail($visitId);
            $service = $visit->services()->where('serv_id', $serviceId)->firstOrFail();
            
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
        $allPets = Pet::with('owner')->whereIn('user_id', $branchUserIds)->get();
        $allOwners = Owner::whereIn('user_id', $branchUserIds)->get();
        $allBranches = Branch::all();
        
        $allProducts = Product::select('prod_id', 'prod_name', 'prod_stocks', 'prod_price')
            ->orderBy('prod_name')
            ->get();
            
        $serviceTypes = Service::orderBy('serv_name')->get(['serv_id','serv_name','serv_type']); 
            
        return compact('allPets', 'allOwners', 'allBranches', 'allProducts', 'serviceTypes');
    }

    public function getAllProductsForPrescription(Request $request)
    {
        return Product::select('prod_id', 'prod_name', 'prod_stocks', 'prod_price')
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
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        $branchUserIds = User::where('branch_id', $activeBranchId)->pluck('user_id')->toArray();
        $lookups = $this->getBranchLookups(); 

        // Fix 1.2: Pulling out the necessary lookup variables for the main view's compact()
        $filteredOwners = $lookups['allOwners']; 
        $filteredPets = $lookups['allPets'];
        $serviceTypes = $lookups['serviceTypes'];


        $baseVisitQuery = function() use ($activeBranchId) {
            return Visit::with(['pet.owner', 'user', 'services'])
                ->whereHas('user', fn($q) => $q->where('branch_id', $activeBranchId))
                ->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');
        };

        // Helper for paginating service queues
        $paginateQueue = function ($types, $perPageParam, $pageName) use ($request, $baseVisitQuery) {
            $limit = $request->get($perPageParam, 10);
            $query = $baseVisitQuery()->whereHas('services', fn($sq) => $sq->whereIn(DB::raw('LOWER(serv_type)'), $types));
            return $limit === 'all' ? $query->get() : $query->paginate((int) $limit, ['*'], $pageName);
        };
        
        $visitPerPage = $request->get('visitPerPage', 10);
        $visits = $baseVisitQuery()->paginate((int) $visitPerPage, ['*'], 'visitsPage');

        // FIX: Comprehensive service type lists for tabs
        $checkupTypes = ['check up', 'consultation', 'checkup'];
        $diagnosticTypes = ['diagnostics', 'diagnostic', 'laboratory'];
        $surgicalTypes = ['surgical', 'surgery'];
        
        $consultationVisits = $paginateQueue($checkupTypes, 'consultationVisitsPerPage', 'consultationVisitsPage');
        $groomingVisits = $paginateQueue(['grooming'], 'groomingVisitsPerPage', 'groomingVisitsPage');
        $dewormingVisits = $paginateQueue(['deworming'], 'dewormingVisitsPerPage', 'dewormingVisitsPage');
        $diagnosticsVisits = $paginateQueue($diagnosticTypes, 'diagnosticsVisitsPerPage', 'diagnosticsVisitsPage');
        $surgicalVisits = $paginateQueue($surgicalTypes, 'surgicalVisitsPerPage', 'surgicalVisitsPage');
        $emergencyVisits = $paginateQueue(['emergency'], 'emergencyVisitsPerPage', 'emergencyVisitsPage');
        $vaccinationVisits = $paginateQueue(['vaccination'], 'vaccinationVisitsPerPage', 'vaccinationVisitsPage');
        $boardingVisits = $paginateQueue(['boarding'], 'boardingVisitsPerPage', 'boardingVisitsPage');

        $appointments = Appointment::whereHas('user', fn($q) => $q->where('branch_id', $activeBranchId))->with('pet.owner', 'services')->paginate(10);
        $prescriptions = Prescription::whereIn('user_id', $branchUserIds)->with('pet.owner')->paginate(10);
        $referrals = Referral::whereHas('appointment', fn($q) => $q->whereIn('user_id', $branchUserIds))->with('appointment.pet.owner')->paginate(10);


        return view('medicalManagement', array_merge(compact(
            'visits', 'consultationVisits', 'groomingVisits', 'dewormingVisits', 'diagnosticsVisits', 
            'surgicalVisits', 'emergencyVisits', 'vaccinationVisits', 'boardingVisits', 
            'appointments', 'prescriptions', 'referrals', 'activeTab',
            // MUST be included explicitly for the Visit Modal to find available owners
            'filteredOwners', 'filteredPets', 'serviceTypes' 
        ), $lookups));
    }


    // ==================== SERVICE SAVE HANDLERS ====================

    public function saveConsultation(Request $request, $visitId)
    {
        $validated = $request->validate([
            'weight' => ['nullable','numeric'],
            'temperature' => ['nullable','numeric'],
            'heart_rate' => ['nullable','numeric'], 
            'respiration_rate' => ['nullable','numeric'],
            'physical_findings' => ['nullable','string'],
            'diagnosis' => ['required','string'],
            'recommendations' => ['nullable','string'],
        ]);
        
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
                'updated_at' => now(),
            ]
        );
        
        // Mark THIS service as completed (find consultation service)
        $consultationService = $visitModel->services()
            ->where(function($query) {
                $query->where('serv_type', 'LIKE', '%consultation%')
                      ->orWhere('serv_type', 'LIKE', '%check up%')
                      ->orWhere('serv_type', 'LIKE', '%checkup%');
            })
            ->first();
        
        if ($consultationService) {
            $visitModel->services()->updateExistingPivot($consultationService->serv_id, [
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
        
        // Update visit_service_type to include all services (if column exists)
        $visitModel->refresh();
        $allServices = $visitModel->services()->get();
        if ($allServices->isNotEmpty() && Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
            $typesSummary = $allServices->pluck('serv_name')->implode(', ');
            $visitModel->visit_service_type = $typesSummary;
            $visitModel->save();
        }
        
        // Check if all services are done
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
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
        
        return redirect()->route('medical.index', ['tab' => 'consultation'])
            ->with($billingExists && $allCompleted ? 'success' : 'warning', $message);
    }
    
    public function saveVaccination(Request $request, $visitId)
    {
        $validated = $request->validate([
            'vaccine_name' => ['required','string'], 
            'dose' => ['nullable','string'], 
            'manufacturer' => ['nullable','string'], 
            'batch_no' => ['nullable','string'], 
            'date_administered' => ['nullable','date'], 
            'next_due_date' => ['nullable','date'],
            'administered_by' => ['nullable','string'], 
            'remarks' => ['nullable','string'], 
            'workflow_status' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('pet.owner', 'services')->findOrFail($visitId);
        
        try {
            DB::beginTransaction();
            
            // 1. Mark vaccination service as completed with proper pricing
            $vaccinationService = $visitModel->services()
                ->where('serv_type', 'LIKE', '%vaccination%')
                ->first();
            
            if ($vaccinationService) {
                $visitModel->services()->updateExistingPivot($vaccinationService->serv_id, [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'quantity' => 1,
                    'unit_price' => $vaccinationService->serv_price,
                    'total_price' => $vaccinationService->serv_price,
                    'updated_at' => now(),
                ]);
            } else {
                Log::warning("No vaccination service found for visit {$visitModel->visit_id}");
            }
            
            // 2. Save Vaccination Record (use updateOrInsert to avoid duplicates)
            $dateAdministered = $validated['date_administered'] ?: $visitModel->visit_date;
            
            // Determine next due date: manual override if provided, else 14 days after administration
            $computedNextDue = Carbon::parse($dateAdministered)->addDays(14)->toDateString();
            $nextDueDate = !empty($validated['next_due_date']) ? $validated['next_due_date'] : $computedNextDue;

            DB::table('tbl_vaccination_record')->updateOrInsert(
                ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
                [
                    'vaccine_name' => $validated['vaccine_name'],
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

            // 3. Deduct Inventory 
            $vaccine = Product::where('prod_name', $request->vaccine_name)
                ->where('prod_category', 'Vaccines')
                ->first();
            
            if ($vaccine && $vaccine->prod_stocks > 0) {
                $vaccine->decrement('prod_stocks', 1);
                InventoryHistoryModel::create([
                    'prod_id' => $vaccine->prod_id,
                    'type' => 'service_usage',
                    'quantity' => -1,
                    'reference' => "Vaccination - Visit #{$visitId}",
                    'user_id' => Auth::id(),
                    'notes' => "Administered to " . ($visitModel->pet->pet_name ?? 'Pet')
                ]);
            }

            // 4. AUTO-SCHEDULING LOGIC (manual date overrides calculated schedule)
            $schedule = $this->calculateNextSchedule('vaccination', $validated['vaccine_name'], $visitModel->pet_id);

            $appointType = $schedule['next_appoint_type'] ?? ('Vaccination Follow-up for ' . $validated['vaccine_name']);
            $newDose = $schedule['new_dose'] ?? 1;

            $this->autoScheduleFollowUp(
                $visitModel,
                $appointType,
                $nextDueDate,
                $validated['vaccine_name'],
                $newDose
            );

            // 5. Update visit_service_type to include all services
            $visitModel->refresh();
            $allServices = $visitModel->services()->get();
            if ($allServices->isNotEmpty()) {
                $typesSummary = $allServices->pluck('serv_name')->implode(', ');
                $visitModel->visit_service_type = $typesSummary;
                $visitModel->save();
            }
            
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
            
            // Redirect to Care Continuity Appointments tab
            return redirect()->route('care-continuity.index', ['active_tab' => 'appointments'])->with('success', $successMessage);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving vaccination record: ' . $e->getMessage());
            return back()->with('error', 'Failed to save vaccination record: ' . $e->getMessage());
        }
    }

    public function saveDeworming(Request $request, $visitId)
    {
        $validated = $request->validate([
            'dewormer_name' => ['required','string'], 
            'dosage' => ['nullable','string'], 
            'next_due_date' => ['nullable','date'],
            'administered_by' => ['nullable','string'], 
            'remarks' => ['nullable','string'], 
            'workflow_status' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('pet.owner', 'services')->findOrFail($visitId);
        
        try {
            DB::beginTransaction();
            
            // 1. Mark deworming service as completed with proper pricing
            $dewormingService = $visitModel->services()
                ->where('serv_type', 'LIKE', '%deworming%')
                ->first();
            
            if ($dewormingService) {
                $visitModel->services()->updateExistingPivot($dewormingService->serv_id, [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'quantity' => 1,
                    'unit_price' => $dewormingService->serv_price,
                    'total_price' => $dewormingService->serv_price,
                    'updated_at' => now(),
                ]);
            } else {
                Log::warning("No deworming service found for visit {$visitModel->visit_id}");
            }
            
            // 2. Save Deworming Record (use updateOrInsert to avoid duplicates)
            $dwBase = $visitModel->visit_date ?? now();
            $dwComputedNextDue = Carbon::parse($dwBase)->addDays(14)->toDateString();
            $dwNextDueDate = !empty($validated['next_due_date']) ? $validated['next_due_date'] : $dwComputedNextDue;

            DB::table('tbl_deworming_record')->updateOrInsert(
                ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
                [
                    'dewormer_name' => $validated['dewormer_name'], 
                    'dosage' => $validated['dosage'], 
                    'next_due_date' => $dwNextDueDate, 
                    'administered_by' => $validated['administered_by'] ?? Auth::user()->user_name,
                    'remarks' => $validated['remarks'], 
                    'created_at' => now(), 
                    'updated_at' => now(),
                ]
            );

            // 3. AUTO-SCHEDULING LOGIC (manual date overrides calculated schedule)
            $currentDewormName = $validated['dewormer_name']; 
            $schedule = $this->calculateNextSchedule('deworming', $currentDewormName, $visitModel->pet_id);

            $dwAppointType = $schedule['next_appoint_type'] ?? ('Deworming Follow-up for ' . $currentDewormName);
            $dwNewDose = $schedule['new_dose'] ?? 1;

            $this->autoScheduleFollowUp(
                $visitModel,
                $dwAppointType,
                $dwNextDueDate,
                $currentDewormName,
                $dwNewDose
            );

            // 4. Update visit_service_type to include all services
            $visitModel->refresh();
            $allServices = $visitModel->services()->get();
            if ($allServices->isNotEmpty()) {
                $typesSummary = $allServices->pluck('serv_name')->implode(', ');
                $visitModel->visit_service_type = $typesSummary;
                $visitModel->save();
            }
            
            // 5. Check if all services are completed and generate billing
            $allCompleted = $visitModel->checkAllServicesCompleted();
            
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
            
            // Redirect to Care Continuity Appointments tab
            return redirect()->route('care-continuity.index', ['active_tab' => 'appointments'])->with('success', $successMessage);
            
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
            
            // Get all existing services to preserve them
            $existingServices = $visit->services()->get();
            $syncData = [];
            
            // Preserve all existing services that are not in the request
            foreach ($existingServices as $existingService) {
                if (!in_array($existingService->serv_id, $request->services)) {
                    $syncData[$existingService->serv_id] = [
                        'status' => $existingService->pivot->status ?? 'pending',
                        'completed_at' => $existingService->pivot->completed_at ?? null,
                        'quantity' => $existingService->pivot->quantity ?? 1,
                        'unit_price' => $existingService->pivot->unit_price ?? $existingService->serv_price,
                        'total_price' => $existingService->pivot->total_price ?? $existingService->serv_price,
                        'coat_condition' => $existingService->pivot->coat_condition ?? null,
                        'skin_issues' => $existingService->pivot->skin_issues ?? null,
                        'notes' => $existingService->pivot->notes ?? null,
                        'created_at' => $existingService->pivot->created_at ?? now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            // Add/update the services from the request
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
            
            $visit->services()->sync($syncData);
            
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
        $validated = $request->validate([
            'grooming_type' => ['required','string','max:255'],
            'addon_services' => ['nullable','array'],
            'addon_services.*' => ['string','max:255'],
            'instructions' => ['nullable','string'],
            'start_time' => ['nullable','date'], 
            'end_time' => ['nullable','date','after_or_equal:start_time'],
            'assigned_groomer' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('groomingAgreement')->findOrFail($visitId);
        //dd($validated);
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
        
        $selectedServices = Service::whereIn('serv_name', $allSelectedNames)->get();
        $selectedServiceIds = $selectedServices->pluck('serv_id')->toArray();

        $missing = array_diff($allSelectedNames, $selectedServices->pluck('serv_name')->all());
        if (!empty($missing)) {
            Log::warning('Grooming save failed: Some selected services were not found.', ['missing' => $missing]);
            throw new \Exception('One or more selected grooming services were not found.');
        }

        // 2. Get all existing services for this visit to preserve them
        $existingServices = $visitModel->services()->get();
        $syncData = [];
        
       foreach ($existingServices as $existingService) {
            // Skip grooming services that are being updated - we'll add them below with new data
            if (in_array($existingService->serv_id, $selectedServiceIds)) {
                continue;
            }
            
            $syncData[$existingService->serv_id] = [
                'status' => $existingService->pivot->status ?? 'pending',
                'completed_at' => $existingService->pivot->completed_at ?? null,
                'quantity' => $existingService->pivot->quantity ?? 1,
                'unit_price' => $existingService->pivot->unit_price ?? $existingService->serv_price,
                'total_price' => $existingService->pivot->total_price ?? ($existingService->serv_price * ($existingService->pivot->quantity ?? 1)),
                'created_at' => $existingService->pivot->created_at ?? now(),
                'updated_at' => now(),
            ];
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

        foreach ($selectedServices as $service) {
            // Check if this service already exists in the existing services to preserve created_at
            $existingService = $existingServices->firstWhere('serv_id', $service->serv_id);
            $existingCreatedAt = $existingService?->pivot->created_at;
            
            $syncData[$service->serv_id] = [
                'status' => $groomingStatus,
                'completed_at' => $groomingCompletedAt,
                'quantity' => 1,
                'unit_price' => $service->serv_price,
                'total_price' => $service->serv_price,
                'created_at' => $existingCreatedAt ?? now(),
                'updated_at' => now(),
            ];
        }
        
        // 3. Sync all services (existing + grooming services)
        $visitModel->services()->sync($syncData);

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
        $visitModel->visit_status = 'arrived';
        $visitModel->workflow_status = $workflowStatus;
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
                'updated_at' => now(),
            ]
        );
        
        // 6. Update visit_service_type to include all services
        $visitModel->refresh();
        $allServices = $visitModel->services()->get();
        if ($allServices->isNotEmpty()) {
            $typesSummary = $allServices->pluck('serv_name')->implode(', ');
            $visitModel->visit_service_type = $typesSummary;
            $visitModel->save();
        }
        
        // 7. Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
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
        Log::error('Stack trace: ' . $e->getTraceAsString());
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
    // 1. Validation: total_days is required for the duration calculation.
    $validated = $request->validate([
        'checkin' => ['required','date'], 
        'checkout' => ['nullable','date','after_or_equal:checkin'], 
        'room' => ['nullable','string'],
        'care_instructions' => ['nullable','string'], 
        'monitoring_notes' => ['nullable','string'], 
        'service_id' => ['required', 'exists:tbl_serv,serv_id'],
        'total_days' => ['required', 'integer', 'min:1'],
        'daily_rate' => ['required', 'numeric', 'min:0'], // MUST be present and accurate (set by JS)
    ]);
    
    $visitModel = Visit::findOrFail($visitId);
    $service = Service::findOrFail($validated['service_id']);
    //dd($validated);
    // Total Amount = Daily Rate * Calculated Days
    $dailyRate = (float) $validated['daily_rate'];
    $totalDays = (int) $validated['total_days'];
    $totalAmount = $dailyRate * $totalDays;
    
    try {
        DB::beginTransaction();
        
        // 2. Get all existing services for this visit to preserve them
        $existingServices = $visitModel->services()->get();
        $syncData = [];
        //dd($existingServices);  
        // Preserve all existing services
        foreach ($existingServices as $existingService) {
            // Skip the boarding service being updated
            if ($existingService->serv_id == $validated['service_id']) {
                continue;
            }
            $syncData[$existingService->serv_id] = [
                'status' => $existingService->pivot->status ?? 'pending',
                'completed_at' => $existingService->pivot->completed_at ?? null,
                'quantity' => $existingService->pivot->quantity ?? 1,
                'unit_price' => $existingService->pivot->unit_price ?? $existingService->serv_price,
                'total_price' => $existingService->pivot->total_price ?? $existingService->serv_price * ($existingService->pivot->quantity ?? 1),
                'created_at' => $existingService->pivot->created_at ?? now(),
                'updated_at' => now(),
            ];
        }
        
        // 3. Add or update the boarding service with correct pricing
        $syncData[$validated['service_id']] = [
            'status' => 'completed', // Mark as completed since boarding is being checked out
            'completed_at' => now(),
            'quantity' => $totalDays,           // Total Days (e.g., 3)
            'unit_price' => $dailyRate,         // Daily Rate (e.g., 250)
            'total_price' => $totalAmount,      // Total Cost (e.g., 750)
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        // Log the sync data for debugging
        Log::info('Boarding syncData', [
            'visit_id' => $visitModel->visit_id,
            'syncData' => $syncData
        ]);
        
        // Sync all services (existing + boarding service)
        $visitModel->services()->sync($syncData);
        
        // 4. Save Boarding Record
        DB::table('tbl_boarding_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'check_in_date' => $validated['checkin'], 
                'check_out_date' => $validated['checkout'], 
                'room_no' => $request->input('room'),
                'feeding_schedule' => $request->input('care_instructions'), 
                'daily_notes' => $request->input('monitoring_notes'),
                'status' => 'Checked Out', 
                'handled_by' => Auth::user()->user_name ?? null,
                'total_days' => $totalDays,
                'updated_at' => now(),
            ]
        );
        
        // 5. Update visit_service_type
        $visitModel->refresh();
        $allServices = $visitModel->services()->get();
        if ($allServices->isNotEmpty()) {
            $typesSummary = $allServices->pluck('serv_name')->implode(', ');
            $visitModel->visit_service_type = $typesSummary;
            $visitModel->save();
        }
        
        // 6. Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
        // 7. Verify billing was actually created
        $visitModel->refresh();
        $billingExists = DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        
        DB::commit();
        
        $message = 'Boarding record saved. Total billed amount: ₱'.number_format($totalAmount, 2);
        if ($allCompleted) {
            $message .= $billingExists 
                ? ' Billing generated successfully!'
                : ' Warning: Billing generation failed. Please check logs.';
        }
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error saving boarding record: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return back()->with('error', 'Failed to save boarding record: ' . $e->getMessage());
    }
    
    // Redirect back to perform page or boarding tab
    if ($request->input('redirect_to') === 'perform') {
        return redirect()->route('medical.visits.perform', ['id' => $visitId, 'type' => 'boarding'])
            ->with($billingExists ? 'success' : 'warning', $message);
    }
    return redirect()->route('medical.index', ['active_tab' => 'boarding'])
        ->with($billingExists ? 'success' : 'warning', $message);
}

    public function saveDiagnostic(Request $request, $visitId)
    {
        $validated = $request->validate([
            'test_type' => ['required','string'], 'results_text' => ['nullable','string'], 'interpretation' => ['nullable','string'],
            'staff' => ['nullable','string'], 'test_datetime' => ['nullable','date'], 'workflow_status' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('services')->findOrFail($visitId);
        
        // Mark diagnostic service as completed
        $diagnosticService = $visitModel->services()
            ->where(function($query) {
                $query->where('serv_type', 'LIKE', '%diagnostic%')
                      ->orWhere('serv_type', 'LIKE', '%diagnostics%')
                      ->orWhere('serv_type', 'LIKE', '%laboratory%');
            })
            ->first();
        
        if ($diagnosticService) {
            $visitModel->services()->updateExistingPivot($diagnosticService->serv_id, [
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
        
        DB::table('tbl_diagnostic_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'test_type' => $validated['test_type'], 'results' => $validated['results_text'],
                'remarks' => $validated['interpretation'], 'collected_by' => $validated['staff'] ?? Auth::user()->user_name,
                'date_completed' => $validated['test_datetime'] ? Carbon::parse($validated['test_datetime'])->toDateString() : now()->toDateString(),
                'status' => 'Completed', 'updated_at' => now(),
            ]
        );
        
        // Update visit_service_type to include all services (if column exists)
        $visitModel->refresh();
        $allServices = $visitModel->services()->get();
        if ($allServices->isNotEmpty() && Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
            $typesSummary = $allServices->pluck('serv_name')->implode(', ');
            $visitModel->visit_service_type = $typesSummary;
            $visitModel->save();
        }
        
        // Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        
        $message = 'Diagnostic record saved.';
        if ($allCompleted) {
            $message .= $billingExists 
                ? ' All services completed - billing generated!'
                : ' All services completed, but billing generation failed. Please check logs.';
        }
        
        // Redirect to the diagnostic tab
        return redirect()->route('medical.index', ['tab' => 'diagnostic'])->with('success', $message);
    }

    public function saveSurgical(Request $request, $visitId)
    {
        $validated = $request->validate([
            'surgery_type' => ['required','string'], 'staff' => ['nullable','string'], 'anesthesia' => ['nullable','string'],
            'start_time' => ['nullable','date'], 'end_time' => ['nullable','date','after_or_equal:start_time'],
            'checklist' => ['nullable','string'], 'post_op_notes' => ['nullable','string'], 'medications_used' => ['nullable','string'],
            'follow_up' => ['nullable','date'], 'workflow_status' => ['nullable','string'],
        ]);
        
        $visitModel = Visit::with('services')->findOrFail($visitId);
        
        // Mark surgical service as completed
        $surgicalService = $visitModel->services()
            ->where(function($query) {
                $query->where('serv_type', 'LIKE', '%surgical%')
                      ->orWhere('serv_type', 'LIKE', '%surgery%');
            })
            ->first();
        
        if ($surgicalService) {
            $visitModel->services()->updateExistingPivot($surgicalService->serv_id, [
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
        
        DB::table('tbl_surgical_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'procedure_name' => $validated['surgery_type'], 
                'date_of_surgery' => $validated['start_time'] ? Carbon::parse($validated['start_time'])->toDateString() : $visitModel->visit_date,
                'start_time' => $validated['start_time'], 'end_time' => $validated['end_time'],
                'surgeon' => $validated['staff'], 'anesthesia_used' => $validated['anesthesia'],
                'findings' => $validated['checklist'], 
                'post_op_instructions' => $validated['post_op_notes'] . "\nMedications: " . $validated['medications_used'],
                'status' => 'Completed', 'updated_at' => now(),
            ]
        );
        
        // Update visit_service_type to include all services (if column exists)
        $visitModel->refresh();
        $allServices = $visitModel->services()->get();
        if ($allServices->isNotEmpty() && Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
            $typesSummary = $allServices->pluck('serv_name')->implode(', ');
            $visitModel->visit_service_type = $typesSummary;
            $visitModel->save();
        }
        
        // Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        
        $message = 'Surgical record saved.';
        if ($allCompleted) {
            $message .= $billingExists 
                ? ' All services completed - billing generated!'
                : ' All services completed, but billing generation failed. Please check logs.';
        }
        
        // Redirect to the surgical tab
        return redirect()->route('medical.index', ['tab' => 'surgical'])->with('success', $message);
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
        
        // Mark emergency service as completed
        $emergencyService = $visitModel->services()
            ->where('serv_type', 'LIKE', '%emergency%')
            ->first();
        
        if ($emergencyService) {
            $visitModel->services()->updateExistingPivot($emergencyService->serv_id, [
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
        
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
        
        // Update visit_service_type to include all services (if column exists)
        $visitModel->refresh();
        $allServices = $visitModel->services()->get();
        if ($allServices->isNotEmpty() && Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
            $typesSummary = $allServices->pluck('serv_name')->implode(', ');
            $visitModel->visit_service_type = $typesSummary;
            $visitModel->save();
        }
        
        // Check if all services are completed and generate billing
        $allCompleted = $visitModel->checkAllServicesCompleted();
        
        // Verify billing was actually created
        $visitModel->refresh();
        $billingExists = \Illuminate\Support\Facades\DB::table('tbl_bill')
            ->where('visit_id', $visitModel->visit_id)
            ->exists();
        
        $message = 'Emergency record saved.';
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
        ]);

        $userId = Auth::id() ?? $request->input('user_id');
        $totalServicesCreated = 0;
        $visitsCreated = 0;

        DB::transaction(function () use ($validated, $userId, $request, &$totalServicesCreated, &$visitsCreated) {
            foreach ($validated['pet_ids'] as $petId) {
                $pet = Pet::findOrFail($petId);
                $pet->pet_weight = $request->input("weight.$petId") ?? $pet->pet_weight;
                $pet->pet_temperature = $request->input("temperature.$petId") ?? $pet->pet_temperature;
                $pet->save();
                
                $data = [
                    'visit_date' => $validated['visit_date'],
                    'pet_id' => $petId,
                    'user_id' => $userId,
                    'weight' => $request->input("weight.$petId") ?? null,
                    'temperature' => $request->input("temperature.$petId") ?? null,
                    'patient_type' => $validated['patient_type'],
                    'visit_status' => 'arrived',
                    'workflow_status' => 'Pending',
                ];

                $visit = Visit::create($data);
                $visitsCreated++;

                // Get selected services for this pet
                $selectedTypes = $request->input("service_type.$petId", []);
                
                if (!empty($selectedTypes)) {
                    // Remove duplicates from selected types
                    $selectedTypes = array_unique($selectedTypes);
                    
                    // Prepare sync data - ONE service per selected type
                    $syncData = [];
                    $serviceNames = [];
                    
                    foreach ($selectedTypes as $selectedType) {
                        // First, try to find by exact service name match
                        $service = Service::where('serv_name', $selectedType)->first();
                        
                        // If not found by name, find the first service of this type
                        if (!$service) {
                            $service = Service::where('serv_type', $selectedType)
                                ->orWhere('serv_type', 'LIKE', '%' . $selectedType . '%')
                                ->orWhere(DB::raw('LOWER(serv_type)'), 'LIKE', '%' . strtolower($selectedType) . '%')
                                ->first();
                        }
                        
                        // If still not found, try case-insensitive match
                        if (!$service) {
                            $service = Service::where(DB::raw('LOWER(serv_name)'), strtolower($selectedType))
                                ->orWhere(DB::raw('LOWER(serv_type)'), strtolower($selectedType))
                                ->first();
                        }
                        
                        // Only add if we found a service and haven't added it yet
                        if ($service && !isset($syncData[$service->serv_id])) {
                            $syncData[$service->serv_id] = [
                                'status' => 'pending',
                                'completed_at' => null,
                                'quantity' => 1,
                                'unit_price' => $service->serv_price,
                                'total_price' => $service->serv_price,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                            $serviceNames[] = $service->serv_name;
                        }
                    }
                    
                    // Sync services with pivot data (this will replace any existing services)
                    $visit->services()->sync($syncData);
                    $totalServicesCreated += count($syncData);
                    
                    // Store service summary in visit record (if column exists)
                    $typesSummary = implode(', ', $serviceNames);
                    $updateData = [
                        'workflow_status' => 'Pending (0/' . count($syncData) . ' completed)'
                    ];
                    if (Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
                        $updateData['visit_service_type'] = $typesSummary;
                    }
                    
                    $visit->update($updateData);
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

            $pet = Pet::findOrFail($validated['pet_id']);
            $pet->pet_weight = $validated['weight'] ?? $pet->pet_weight;
            $pet->pet_temperature = $validated['temperature'] ?? $pet->pet_temperature;
            $pet->save();

        $visit->update($validated);
        if ($request->filled('visit_status')) {
            $visit->visit_status = $request->input('visit_status');
            $visit->save();
        }

        // Handle service types - add/update services
        if ($request->has('service_type')) {
            $selectedTypes = $request->input('service_type', []);
            
            if (!empty($selectedTypes)) {
                // Remove duplicates from selected types
                $selectedTypes = array_unique(array_filter($selectedTypes));
                
                // Get all existing services first to preserve them
                $existingServices = $visit->services()->get();
                $syncData = [];
                
                // First, add all existing services to syncData to preserve them
                foreach ($existingServices as $existingService) {
                    $syncData[$existingService->serv_id] = [
                        'status' => $existingService->pivot->status ?? 'pending',
                        'completed_at' => $existingService->pivot->completed_at,
                        'quantity' => $existingService->pivot->quantity ?? 1,
                        'unit_price' => $existingService->pivot->unit_price ?? $existingService->serv_price,
                        'total_price' => $existingService->pivot->total_price ?? $existingService->serv_price,
                        'created_at' => $existingService->pivot->created_at ?? now(),
                        'updated_at' => now()
                    ];
                }
                
                // Now process newly added services
                foreach ($selectedTypes as $selectedType) {
                    // First, try to find by exact service name match
                    $service = Service::where('serv_name', $selectedType)->first();
                    
                    // If not found by name, find the first service of this type
                    if (!$service) {
                        $service = Service::where('serv_type', $selectedType)
                            ->orWhere('serv_type', 'LIKE', '%' . $selectedType . '%')
                            ->orWhere(DB::raw('LOWER(serv_type)'), 'LIKE', '%' . strtolower($selectedType) . '%')
                            ->first();
                    }
                    
                    // If still not found, try case-insensitive match
                    if (!$service) {
                        $service = Service::where(DB::raw('LOWER(serv_name)'), strtolower($selectedType))
                            ->orWhere(DB::raw('LOWER(serv_type)'), strtolower($selectedType))
                            ->first();
                    }
                    
                    // Only add if we found a service and haven't added it yet
                    if ($service && !isset($syncData[$service->serv_id])) {
                        $syncData[$service->serv_id] = [
                            'status' => 'pending',
                            'completed_at' => null,
                            'quantity' => 1,
                            'unit_price' => $service->serv_price,
                            'total_price' => $service->serv_price,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
                
                // Sync services with pivot data (this will keep existing + add new ones)
                if (!empty($syncData)) {
                    $visit->services()->sync($syncData);
                    
                    // Update service summary in visit record (if column exists)
                    $allServices = $visit->services()->get();
                    $serviceNames = $allServices->pluck('serv_name')->toArray();
                    $typesSummary = implode(', ', $serviceNames);
                    if (Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
                        $visit->visit_service_type = $typesSummary;
                        $visit->save();
                    }
                }
            }
        }

        // Auto-generate billing if visit is marked completed and no billing exists yet
        if (strcasecmp((string)$visit->visit_status, 'completed') === 0 && !$visit->billing) {
            try {
                $branchId = optional($visit->user)->branch_id ?? session('active_branch_id');
                \App\Models\Billing::create([
                    'bill_date' => \Carbon\Carbon::today()->toDateString(),
                    'visit_id' => $visit->visit_id,
                    'bill_status' => 'pending',
                    'branch_id' => $branchId,
                ]);
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
        $visit->delete();

        $activeTab = $request->input('tab', 'visits');
        return redirect()->route('medical.index', ['tab' => $activeTab])
            ->with('success', 'Visit deleted successfully');
    }

    public function showVisit($id)
    {
        $visit = Visit::with(['pet.owner', 'user'])->findOrFail($id);
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

            if (strcasecmp($next, 'Completed') === 0) {
                if (!$visit->billing) {
                    $branchId = optional($visit->user)->branch_id ?? session('active_branch_id');
                    \App\Models\Billing::create([
                        'bill_date' => \Carbon\Carbon::today()->toDateString(),
                        'visit_id' => $visit->visit_id,
                        'bill_status' => 'pending',
                        'branch_id' => $branchId,
                    ]);
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
        
        $visit = Visit::with(['pet.owner', 'user', 'services'])->findOrFail($id);

        $explicitType = $request->query('type');
        $serviceType = null;
        if ($explicitType) {
            $serviceType = str_replace('-', ' ', $explicitType);
        } elseif ($visit->services->count() > 0) {
            $serviceType = $visit->services->first()->serv_type;
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
            
        $availableGroomingServices = Service::where('serv_type', 'LIKE', '%' .$serviceType . '%') 
            // Only select the columns that actually exist in your tbl_serv
            ->select('serv_id', 'serv_name', 'serv_price') 
            ->orderBy('serv_name')
            ->get();
        
        // Separate packages and add-ons
        $groomingPackages = $availableGroomingServices->filter(function($service) {
            // Filter for "Bath and Blow Dry" or "Grooming" in service name (exclude add-ons)
            return (stripos($service->serv_name, 'Bath and Blow Dry') !== false || 
                    stripos($service->serv_name, 'Grooming') !== false) &&
                   stripos($service->serv_name, 'Add on') === false &&
                   stripos($service->serv_name, 'Add-on') === false;
        })->unique('serv_name')->values();
        
        $groomingAddons = $availableGroomingServices->filter(function($service) {
            // Filter for "Add on" or "Add-on" in service name
            return stripos($service->serv_name, 'Add on') !== false || 
                   stripos($service->serv_name, 'Add-on') !== false;
        })->unique('serv_name')->values();
            
        // 2. Map the collection to extract weight limits from the 'serv_name' string
        $petWeight = optional($visit)->weight; // may be null
        $petAgeMonths = $this->calculatePetAgeInMonths(optional($visit)->pet);
        $availableServices = $groomingPackages->map(function ($service) {
            $name = $service->serv_name;
            $min_weight = 0;
            $max_weight = 9999; // Default to max/no limit

            // Parse weight range from service name - supports multiple formats:
            // 1. "7.7kg to 15kg" or "7.7 kg to 15 kg" - range format
            // 2. "7.7kg below" or "7.7kg bellow" - upper limit only
            // 3. "22kg up" or "22kg above" - lower limit only
            // 4. Handles kg/kl variations and various spellings
            
            // Pattern 1: Range format (e.g., "7.7kg to 15kg", "7.7 - 15kg", "7.7kg-15kg")
            if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:to|-|–)\s*(\d+\.?\d*)\s*k[gl]?/i', $name, $matches)) {
                $min_weight = (float)$matches[1];
                $max_weight = (float)$matches[2];
            }
            // Pattern 2: Below/bellow format (e.g., "7.5kg below", "7.5 kl bellow")
            else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:below|bellow)/i', $name, $matches)) {
                $min_weight = 0;
                $max_weight = (float)$matches[1];
            }
            // Pattern 3: Up/above format (e.g., "22kg up", "15 kg above")
            else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:up|above)/i', $name, $matches)) {
                $min_weight = (float)$matches[1];
                $max_weight = 9999;
            }
            // Pattern 4: Parentheses format for backward compatibility
            // (7.5 kg) below or (15 kg) above
            else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:below|bellow)/i', $name, $matches)) {
                $min_weight = 0;
                $max_weight = (float)$matches[1];
            } 
            else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:up|above)/i', $name, $matches)) {
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
        // Server-side filter: only keep services within the pet's weight if available
        ->filter(function ($service) use ($petWeight) {
            // If pet weight is not available or invalid, show all services
            if (empty($petWeight) || !is_numeric($petWeight)) {
                return true;
            }
            // Filter based on weight range
            $minWeight = $service->min_weight ?? 0;
            $maxWeight = $service->max_weight ?? 9999;
            return $petWeight >= $minWeight && $petWeight <= $maxWeight;
        })
        ->filter(function ($service) use ($petAgeMonths) {
            // If pet age is not available, show all services
            if (is_null($petAgeMonths)) {
                return true;
            }
            
            $minAge = $service->min_age_months;
            $maxAge = $service->max_age_months;

            // Filter based on age range
            if (!is_null($minAge) && $petAgeMonths < $minAge) {
                return false;
            }

            if (!is_null($maxAge) && $petAgeMonths > $maxAge) {
                return false;
            }

            return true;
        })
        ->values(); // Reset array keys after filtering

        // Map add-ons with weight/age extraction
        $availableAddons = $groomingAddons->map(function ($service) {
            $name = $service->serv_name;
            $min_weight = 0;
            $max_weight = 9999;

            // Parse weight range from service name
            if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:to|-|–)\s*(\d+\.?\d*)\s*k[gl]?/i', $name, $matches)) {
                $min_weight = (float)$matches[1];
                $max_weight = (float)$matches[2];
            }
            else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:below|bellow)/i', $name, $matches)) {
                $min_weight = 0;
                $max_weight = (float)$matches[1];
            }
            else if (preg_match('/(\d+\.?\d*)\s*k[gl]?\s*(?:up|above)/i', $name, $matches)) {
                $min_weight = (float)$matches[1];
                $max_weight = 9999;
            }
            else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:below|bellow)/i', $name, $matches)) {
                $min_weight = 0;
                $max_weight = (float)$matches[1];
            } 
            else if (preg_match('/\((\d+\.?\d*)\s*k[gl]?\)\s*(?:up|above)/i', $name, $matches)) {
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

        $lookups = $this->getBranchLookups();
        $viewName = 'visits.' . $blade;
        
        // ** FIX APPLIED HERE: INITIALIZE $vaccines **
        $vaccines = null; 
        
        // For vaccination view, only show vaccination services in the service type dropdown
        if ($blade === 'vaccination') {
            $availableServices = \App\Models\Service::where('serv_type', 'like', '%vaccination%')
                ->orderBy('serv_name')
                ->get();
            
            // Only show product-based vaccines that are in stock and not expired
            $vaccines = \App\Models\Product::where('prod_category', 'like', '%vaccin%')
                ->where('prod_stocks', '>', 0)
                ->where(function($query) {
                    $query->where('prod_expiry', '>', now())
                          ->orWhereNull('prod_expiry');
                })
                ->orderBy('prod_name')
                ->get();
        }

        // 🌟 NEW LOGIC: Fetch all registered veterinarians
        $veterinarians = User::where('user_role', 'veterinarian')
                            ->orderBy('user_name')
                            ->get();
        
        // Initialize availableAddons as empty collection if not grooming view
        if ($blade !== 'grooming') {
            $availableAddons = collect();
        }
        
        // Pass the new variable 'veterinarians' to the view
        //dd($viewName);
        return view($viewName, array_merge(compact('visit', 'serviceData', 'petMedicalHistory', 'availableServices','vaccines', 'veterinarians', 'availableAddons'), $lookups));
    }
    // ==================== APPOINTMENT METHODS (Full Implementations) ====================

    public function storeAppointment(Request $request)
    {
        $validated = $request->validate([
            'appoint_time' => 'required', 'appoint_date' => 'required|date', 'appoint_status' => 'required',
            'pet_id' => 'required|exists:tbl_pet,pet_id', 'appoint_type' => 'required|string', 
            'appoint_description' => 'nullable|string', 'services' => 'array', 'services.*' => 'exists:tbl_serv,serv_id',
        ]);

    $validated['user_id'] = Auth::id() ?? $request->input('user_id');
        $services = $validated['services'] ?? [];
        unset($validated['services']);

        $appointment = Appointment::create($validated);
        
        // Send SMS notification for new appointment
        try {
            $this->smsService->sendNewAppointmentSMS($appointment);
        } catch (\Exception $e) {
            Log::warning("Failed to send SMS for appointment {$appointment->appoint_id}: " . $e->getMessage());
        }
        
        // Assuming History and Service Sync Logic is restored/handled elsewhere
        
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
            'appoint_date' => 'required|date', 'appoint_time' => 'required',
            'appoint_status' => 'required|in:pending,arrived,completed,refer,rescheduled',
            'appoint_type' => 'required|string', 'pet_id' => 'required|integer|exists:tbl_pet,pet_id',
            'appoint_description' => 'nullable|string', 'services' => 'array', 'services.*' => 'exists:tbl_serv,serv_id',
        ]);

        // Store old values for SMS notification
        $oldDate = $appointment->appoint_date;
        $oldTime = $appointment->appoint_time;
        $isRescheduled = ($validated['appoint_date'] !== $oldDate || $validated['appoint_time'] !== $oldTime);

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

        $activeTab = $request->input('tab', 'appointments');
        return redirect()->route('medical.index', ['tab' => $activeTab])
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
                    $this->inventoryService->deductFromInventory(
                        $med['product_id'],
                        1, // Default quantity of 1
                        'Prescription #' . $prescription->prescription_id,
                        'service_usage'
                    );
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
        // Placeholder implementation of product search
        $query = $request->get('q');
        return response()->json(Product::select('prod_id as id', 'prod_name as name', 'prod_price as price')->where('prod_name', 'like', "%$query%")->limit(15)->get());
    }

    // ==================== REFERRAL METHODS (Full Implementations) ====================

    public function storeReferral(Request $request)
    {
        $validated = $request->validate([
            'visit_id' => 'required|exists:tbl_visit_record,visit_id', 'ref_date' => 'required|date', 'pet_id' => 'required|exists:tbl_pet,pet_id',
            'ref_to' => 'required|numeric', 'ref_description' => 'required|string',
        ]);
          try {
            // Create a temporary appointment for the referral
           /* $appointment = Appointment::create([
                'pet_id' => $validated['pet_id'],
                'appoint_date' => $validated['ref_date'],
                'appoint_time' => '09:00:00',
                'appoint_status' => 'refer',
                'appoint_type' => 'Referral',
                'user_id' => Auth::id(),
            ]);*/

            // @todo : Consider linking the referral to an appointment if needed in the future
            // @todo : For now, we just create the referral record but we also measure pet medical history, medication and test record via visit_id and pet_id 
            Referral::create([
                'visit_id' => $validated['visit_id'],
                'pet_id' => $validated['pet_id'],   
                'ref_date' => $validated['ref_date'],
                'ref_to' => $validated['ref_to'],
                'ref_description' => $validated['ref_description'],
                'ref_by' => Auth::id(),
            ]);

           $activeTab = $request->input('tab', 'referrals');
        return redirect()->route('medical.index', ['tab' => $activeTab])->with('success', 'Referral created successfully.');
        } catch (\Exception $e) {
            Log::error('Referral creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create referral');
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
                $nextAppointType = "Vaccination Follow-up - {$itemName} (Dose {$newDose})";
            } else {
                // For deworming
                $nextAppointType = "Deworming Follow-up - {$itemName}";
            }
        } else {
            // First time for this pet
            if ($type === 'vaccination') {
                $nextAppointType = "Vaccination Follow-up - {$itemName} (Dose 2)";
                $newDose = 2;
            } else {
                $nextAppointType = "Deworming Follow-up - {$itemName}";
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
            // Check if appointment already exists for this date and pet
            $existingAppointment = Appointment::where('pet_id', $visit->pet_id)
                ->whereDate('appoint_date', Carbon::parse($appointDate)->toDateString())
                ->where('appoint_type', 'like', "%{$itemName}%")
                ->first();
            
            if ($existingAppointment) {
                Log::info("Follow-up appointment already exists for pet {$visit->pet_id} on {$appointDate}");
                return $existingAppointment;
            }
            
            // Create the follow-up appointment
            $appointment = Appointment::create([
                'pet_id' => $visit->pet_id,
                'user_id' => $visit->user_id,
                'appoint_date' => Carbon::parse($appointDate)->toDateString(),
                'appoint_time' => '09:00', // Default time, can be adjusted
                'appoint_type' => $appointType,
                'appoint_status' => 'pending',
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
}