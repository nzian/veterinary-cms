<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\MedicalHistory;
use App\Models\Visit;
use App\Models\Order;
use App\Models\Branch;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Traits\BranchFilterable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PetManagementController extends Controller
{
    use BranchFilterable;
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        try {
            // Get pagination parameters for all tabs
            $ownersPerPage = $request->get('ownersPerPage', 10);
            $medicalPerPage = $request->get('medicalPerPage', 10);
            $visitPerPage = $request->get('visitPerPage', 10);
            
            // Determine pet pagination limit based on active tab
            $activeTab = $request->get('tab', 'owners');
            $petPageParam = ($activeTab == 'health-card') ? 'healthCardPerPage' : 'perPage';
            $perPage = $request->get($petPageParam, 10);
            
            // Get active branch ID using trait
            $activeBranchId = $this->getActiveBranchId();
            
            // Get all user IDs from the active branch
            $branchUserIds = \App\Models\User::where('branch_id', $activeBranchId)
                ->pluck('user_id')
                ->toArray();
            
            // --- Pets Pagination (Used by 'Pets' and 'Health Card' tabs) ---
            $petsQuery = Pet::with('owner')
                ->whereIn('user_id', $branchUserIds)
                ->orderBy('pet_id', 'desc'); 
            
            if ($perPage === 'all') {
                $pets = $petsQuery->get();
                $pets = new \Illuminate\Pagination\LengthAwarePaginator(
                    $pets,
                    $pets->count(),
                    $pets->count(),
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                $pets = $petsQuery->paginate((int)$perPage);
            }
            // ----------------------------------------------------------------
            
            // Filter owners by branch users
            $ownersQuery = Owner::whereIn('user_id', $branchUserIds)
                ->orderBy('own_id', 'desc'); 
            
            if ($ownersPerPage === 'all') {
                $owners = $ownersQuery->get();
                $owners = new \Illuminate\Pagination\LengthAwarePaginator(
                    $owners,
                    $owners->count(),
                    $owners->count(),
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                $owners = $ownersQuery->paginate((int)$ownersPerPage);
            }

            // Filter medical histories by branch users
            $medicalQuery = MedicalHistory::with('pet')
                ->whereIn('user_id', $branchUserIds)
                ->orderBy('id', 'desc'); 
            
            if ($medicalPerPage === 'all') {
                $medicalHistories = $medicalQuery->get();
                $medicalHistories = new \Illuminate\Pagination\LengthAwarePaginator(
                    $medicalHistories,
                    $medicalHistories->count(),
                    $medicalHistories->count(),
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                $medicalHistories = $medicalQuery->paginate((int)$medicalPerPage);
            }

            // Get all owners and pets from same branch for dropdowns
            $allOwners = Owner::whereIn('user_id', $branchUserIds)->get();
            $allPets = Pet::whereIn('user_id', $branchUserIds)->get();
            
            // Build Visit Records (server-side for Visit Record tab)
            $visitQuery = \DB::table('tbl_visit_record as vr')
                ->leftJoin('tbl_pet as p', 'p.pet_id', '=', 'vr.pet_id')
                ->leftJoin('tbl_own as o', 'o.own_id', '=', 'p.own_id')
                ->select(
                    'vr.visit_id', 'vr.visit_date', 'vr.patient_type', 'vr.weight', 'vr.temperature',
                    'p.pet_id', 'p.pet_name', 'p.pet_species',
                    'o.own_id', 'o.own_name'
                )
                ->orderByDesc('vr.visit_date')
                ->orderByDesc('vr.visit_id');

            if (!empty($branchUserIds)) {
                // Use vr.user_id when available, otherwise fall back to p.user_id
                $visitQuery->whereIn(\DB::raw('COALESCE(vr.user_id, p.user_id)'), $branchUserIds);
            }

            if ($visitPerPage === 'all') {
                $visitAll = $visitQuery->get();
                $visitRecords = new \Illuminate\Pagination\LengthAwarePaginator(
                    $visitAll,
                    $visitAll->count(),
                    $visitAll->count(),
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                $visitRecords = $visitQuery->paginate((int)$visitPerPage, ['*'], 'visitPage');
            }

            // Get active branch name
            $activeBranchName = $this->getActiveBranchName();

            return view('petManagement', compact(
                'pets', 
                'owners', 
                'medicalHistories', 
                'allOwners', 
                'allPets',
                'activeBranchName',
                'visitRecords'
            ));
            
        } catch (\Exception $e) {
            Log::error('Pet Management Index Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load data: ' . $e->getMessage());
        }
    }

    public function getOwnerDetails($id)
    {
        try {
            $owner = Owner::with(['pets'])->find($id);
            if (!$owner) {
                return response()->json(['error' => 'Owner not found'], 404);
            }

            // Gather visits across all owner's pets
            $petIds = $owner->pets->pluck('pet_id')->all();
            $visits = [];
            $lastVisit = 'Never';
            $visitsCount = 0;
            
            if (!empty($petIds)) {
                // Fetch visits with 'user' (veterinarian/user) and 'pet' relationships
                $visitsE = Visit::with(['user', 'pet'])
                    ->whereIn('pet_id', $petIds)
                    ->orderBy('visit_date', 'desc')
                    ->limit(50)
                    ->get();

                $visits = $visitsE->map(function ($a) {
                    // Safely retrieve veterinarian name using optional helper
                    $veterinarianName = optional($a->user)->user_name ?? 'N/A';
                    
                    return [
                        'id' => $a->visit_id,
                        'date' => Carbon::parse($a->visit_date)->format('M d, Y'),
                        'status' => $a->visit_status,
                        'type' => $a->visit_type,
                        'veterinarian' => $veterinarianName,
                        'pet_name' => optional($a->pet)->pet_name,
                        'pet_species' => optional($a->pet)->pet_species,
                        'weight' => $a->weight,
                        'temperature' => $a->temperature,
                        'patient_type' => $a->patient_type,
                        'service_type' => $a->service_type,
                        'workflow_status' => $a->workflow_status
                    ];
                });
                
                $visitsCount = $visitsE->count();
                if ($visitsE->first()) {
                    $lastVisit = Carbon::parse($visitsE->first()->visit_date)->format('M d, Y');
                }
            }

            // Purchases and Medical Count logic remains the same
            $purchasesE = Order::with(['product']) 
                ->where('own_id', $owner->own_id)
                ->orderBy('ord_date', 'desc')
                ->limit(50)
                ->get();
            
            $purchases = $purchasesE->map(function ($o) {
                $productName = optional($o->product)->prod_name;
                $unitPrice = optional($o->product)->prod_price ?? 0;
                $itemTotal = $o->ord_quantity * $unitPrice;
                $dateSource = $o->ord_date ?: $o->created_at; 
                $purchaseDate = optional($dateSource)->format('Y-m-d') ?? 'N/A';
                
                return [
                    'id' => $o->ord_id,
                    'date' => $purchaseDate,
                    'product' => $productName ?? 'N/A',
                    'quantity' => $o->ord_quantity,
                    'price' => (float)$unitPrice,
                    'total' => (float)$itemTotal,
                ];
            });

            // Medical records count across owner pets
            $medicalCount = 0;
            if (!empty($petIds)) {
                $medicalCount = MedicalHistory::whereIn('pet_id', $petIds)->count();
            }

            return response()->json([
                'owner' => [
                    'own_id' => $owner->own_id,
                    'own_name' => $owner->own_name,
                    'own_contactnum' => $owner->own_contactnum,
                    'own_location' => $owner->own_location,
                ],
                'pets' => $owner->pets->map(function ($p) {
                    return [
                        'pet_id' => $p->pet_id,
                        'pet_name' => $p->pet_name,
                        'pet_species' => $p->pet_species,
                        'pet_breed' => $p->pet_breed,
                        'pet_age' => $p->pet_age,
                        'pet_gender' => $p->pet_gender,
                        'pet_photo' => $p->pet_photo,
                    ];
                }),
                'visits' => $visits, 
                'purchases' => $purchases,
                'stats' => [
                    'pets' => $owner->pets->count(),
                    'visits' => $visitsCount,
                    'medicalRecords' => $medicalCount,
                    'lastVisit' => $lastVisit,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('getOwnerDetails Error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error while fetching owner details.'], 500);
        }
    }

    public function getPetDetails($id)
    {
        try {
            $pet = Pet::with(['owner'])->find($id);
            if (!$pet) {
                return response()->json(['error' => 'Pet not found'], 404);
            }

            $medical = MedicalHistory::where('pet_id', $pet->pet_id)
                ->orderBy('visit_date', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'visit_date' => Carbon::parse($m->visit_date)->format('M d, Y'),
                        'diagnosis' => $m->diagnosis,
                        'treatment' => $m->treatment,
                        'medication' => $m->medication,
                        'veterinarian_name' => $m->veterinarian_name,
                        'weight' => $m->weight,
                        'temperature' => $m->temperature,
                    ];
                });

            $visits = $medical->count();
            $lastVisit = $visits > 0 ? $medical->first()['visit_date'] : 'Never';

            // --- Visits: gather visit-based records (services + service-specific tables) ---
            $visitRecords = \App\Models\Visit::with('services')
                ->where('pet_id', $pet->pet_id)
                ->orderBy('visit_date', 'desc')
                ->limit(50)
                ->get();

            $visitsDetailed = $visitRecords->map(function ($v) use ($pet) {
                $visitDateStr = $v->visit_date;

                // services on the visit
                $services = $v->services->map(function ($s) {
                    return [
                        'serv_id' => $s->serv_id,
                        'serv_name' => $s->serv_name,
                        'serv_type' => $s->serv_type,
                    ];
                })->values()->toArray();

                // service-specific records (if present)
                $checkup = DB::table('tbl_checkup_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();
                $vaccination = DB::table('tbl_vaccination_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();
                $deworming = DB::table('tbl_deworming_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();
                $grooming = DB::table('tbl_grooming_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();
                $boarding = DB::table('tbl_boarding_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();
                $diagnostic = DB::table('tbl_diagnostic_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();
                $surgical = DB::table('tbl_surgical_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();
                $emergency = DB::table('tbl_emergency_record')->where('visit_id', $v->visit_id)->where('pet_id', $pet->pet_id)->first();

                // prescriptions matching visit date (if any)
                $prescriptions = \App\Models\Prescription::where('pet_id', $pet->pet_id)
                    ->whereDate('prescription_date', Carbon::parse($visitDateStr)->toDateString())
                    ->get()
                    ->map(function ($p) {
                        return [
                            'prescription_id' => $p->prescription_id,
                            'prescription_date' => $p->prescription_date,
                            'medication' => $p->medication,
                            'notes' => $p->notes,
                        ];
                    })->values()->toArray();

                // related medical history record for visit date
                $medicalRecord = \App\Models\MedicalHistory::where('pet_id', $pet->pet_id)
                    ->where('visit_date', $visitDateStr)
                    ->first();

                return [
                    'visit_id' => $v->visit_id,
                    'visit_date' => Carbon::parse($visitDateStr)->format('M d, Y'),
                    'weight' => $v->weight,
                    'temperature' => $v->temperature,
                    'patient_type' => is_object($v->patient_type) && method_exists($v->patient_type, 'value') ? $v->patient_type->value : $v->patient_type,
                    'workflow_status' => $v->workflow_status,
                    'visit_service_type' => $v->visit_service_type,
                    'services' => $services,
                    'checkup' => $checkup ? (array) $checkup : null,
                    'vaccination' => $vaccination ? (array) $vaccination : null,
                    'deworming' => $deworming ? (array) $deworming : null,
                    'grooming' => $grooming ? (array) $grooming : null,
                    'boarding' => $boarding ? (array) $boarding : null,
                    'diagnostic' => $diagnostic ? (array) $diagnostic : null,
                    'surgical' => $surgical ? (array) $surgical : null,
                    'emergency' => $emergency ? (array) $emergency : null,
                    'prescriptions' => $prescriptions,
                    'medical_history' => $medicalRecord ? $medicalRecord->toArray() : null,
                ];
            })->values();

            return response()->json([
                'pet' => [
                    'pet_id' => $pet->pet_id,
                    'pet_name' => $pet->pet_name,
                    'pet_species' => $pet->pet_species,
                    'pet_breed' => $pet->pet_breed,
                    'pet_age' => $pet->pet_age,
                    'pet_gender' => $pet->pet_gender,
                    'pet_photo' => $pet->pet_photo,
                    'pet_weight' => $pet->pet_weight,
                    'pet_temperature' => $pet->pet_temperature,
                    'owner' => $pet->owner ? [
                        'own_id' => $pet->owner->own_id,
                        'own_name' => $pet->owner->own_name,
                    ] : null,
                ],
                'stats' => [
                    'visits' => $visitsDetailed->count(),
                    'lastVisit' => $lastVisit,
                ],
                'medicalHistory' => $medical,
                'visits' => $visitsDetailed,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function getMedicalDetails($id)
    {
        try {
            $medical = MedicalHistory::with('pet.owner')->find($id);
            
            if (!$medical) {
                return response()->json(['error' => 'Medical record not found'], 404);
            }
            
            $timeline = MedicalHistory::where('pet_id', $medical->pet_id)
                ->orderBy('visit_date', 'desc')
                ->limit(10)
                ->get()
                ->map(function($visit) {
                    return [
                        'id' => $visit->id,
                        'visit_date' => Carbon::parse($visit->visit_date)->format('M d, Y'),
                        'diagnosis' => $visit->diagnosis,
                        'treatment' => $visit->treatment,
                        'medication' => $visit->medication,
                        'veterinarian_name' => $visit->veterinarian_name,
                        'weight' => $visit->weight,
                        'temperature' => $visit->temperature
                    ];
                });
            
            return response()->json([
                'medical' => [
                    'id' => $medical->id,
                    'visit_date' => Carbon::parse($medical->visit_date)->format('M d, Y'),
                    'diagnosis' => $medical->diagnosis,
                    'treatment' => $medical->treatment,
                    'medication' => $medical->medication,
                    'veterinarian_name' => $medical->veterinarian_name,
                    'follow_up_date' => $medical->follow_up_date ? Carbon::parse($medical->follow_up_date)->format('M d, Y') : null,
                    'notes' => $medical->notes,
                    'weight' => $medical->weight,
                    'temperature' => $medical->temperature,
                    'pet' => $medical->pet ? [
                        'pet_id' => $medical->pet->pet_id,
                        'pet_name' => $medical->pet->pet_name,
                        'pet_species' => $medical->pet->pet_species,
                        'pet_breed' => $medical->pet->pet_breed,
                        'pet_age' => $medical->pet->pet_age,
                        'pet_gender' => $medical->pet->pet_gender,
                        'pet_photo' => $medical->pet->pet_photo,
                        'owner' => $medical->pet->owner ? [
                            'own_name' => $medical->pet->owner->own_name,
                            'own_contactnum' => $medical->pet->owner->own_contactnum,
                            'own_location' => $medical->pet->owner->own_location
                        ] : null
                    ] : null
                ],
                'timeline' => $timeline
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    // Pet Management Methods
    public function storePet(Request $request)
    {
        try {
            $validated = $request->validate([
                'pet_name' => 'required|string|max:100',
                'pet_weight' => 'required|numeric|min:0|max:200',
                'pet_species' => 'required|string|in:Dog,Cat',
                'pet_breed' => 'required|string|max:100',
                'pet_birthdate' => 'required|date|before_or_equal:today',
                'pet_age' => 'required|string|max:50',
                'pet_gender' => 'required|in:Male,Female',
                'pet_temperature' => 'required|numeric|min:30|max:45',
                'pet_registration' => 'required|date',
                'own_id' => 'required|exists:tbl_own,own_id',
                'pet_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
            ]);

            $validated['user_id'] = Auth::id();

            if ($request->hasFile('pet_photo')) {
                $validated['pet_photo'] = $request->file('pet_photo')->store('pets', 'public');
            }

            Pet::create($validated);
            return back()->with('success', 'Pet saved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save pet.');
        }
    }

    public function updatePet(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'pet_name' => 'required|string|max:100',
                'pet_weight' => 'nullable|numeric|min:0|max:200',
                'pet_species' => 'required|string|in:Dog,Cat',
                'pet_breed' => 'required|string|max:100',
                'pet_birthdate' => 'required|date|before_or_equal:today',
                'pet_age' => 'required|string|max:50',
                'pet_gender' => 'required|in:Male,Female',
                'pet_temperature' => 'nullable|numeric|min:30|max:45',
                'pet_registration' => 'required|date',
                'own_id' => 'required|exists:tbl_own,own_id',
                'pet_photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
            ]);

            $pet = Pet::findOrFail($id);

            // Check if weight or temperature changed - save old values to medical history
            if (($validated['pet_weight'] && $validated['pet_weight'] != $pet->pet_weight) || 
                ($validated['pet_temperature'] && $validated['pet_temperature'] != $pet->pet_temperature)) {
                
                MedicalHistory::create([
                    'pet_id' => $pet->pet_id,
                    'weight' => $pet->pet_weight,
                    'temperature' => $pet->pet_temperature,
                    'visit_date' => now()->format('Y-m-d'),
                    'diagnosis' => 'Routine Check - Weight/Temperature Update',
                    'treatment' => 'Weight: ' . $pet->pet_weight . ' kg, Temperature: ' . $pet->pet_temperature . '°C',
                    'veterinarian_name' => 'System Auto-Record',
                    'notes' => 'Automated record of vital signs before update'
                ]);
            }

            if ($request->hasFile('pet_photo')) {
                if ($pet->pet_photo) {
                    Storage::disk('public')->delete($pet->pet_photo);
                }
                $validated['pet_photo'] = $request->file('pet_photo')->store('pets', 'public');
            }

            $pet->update($validated);
            return back()->with('success', 'Pet updated successfully. Previous vital signs saved to medical history.');
        } catch (\Exception $e) {
            return back()->with('error', 'Update unsuccessful: ' . $e->getMessage());
        }
    }

    public function destroyPet($id)
    {
        try {
            $pet = Pet::findOrFail($id);
            if ($pet->pet_photo) {
                Storage::disk('public')->delete($pet->pet_photo);
            }
            $pet->delete();
            return back()->with('success', 'Pet deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete unsuccessful.');
        }
    }

    // Owner Management Methods
    public function storeOwner(Request $request)
    {
        try {
            $validated = $request->validate([
                'own_name' => 'required|string|max:255',
                'own_contactnum' => 'required|string|max:20',
                'own_location' => 'required|string|max:255'
            ]);

            $validated['user_id'] = Auth::id();

            Owner::create($validated);
            return back()->with('success', 'Pet Owner added successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add Pet Owner.');
        }
    }

    public function updateOwner(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'own_name' => 'required|string|max:255',
                'own_contactnum' => 'required|string|max:20',
                'own_location' => 'required|string|max:255'
            ]);

            $owner = Owner::findOrFail($id);
            $owner->update($validated);
            return back()->with('success', 'Pet owner updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Update unsuccessful.');
        }
    }

    public function destroyOwner($id)
    {
        try {
            Owner::destroy($id);
            return back()->with('success', 'Pet owner deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete unsuccessful.');
        }
    }

    // Medical History Management Methods
    public function storeMedicalHistory(Request $request)
    {
        try {
            $validated = $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'visit_date' => 'required|date',
                'diagnosis' => 'required|string|max:500',
                'treatment' => 'required|string|max:500',
                'medication' => 'nullable|string|max:300',
                'veterinarian_name' => 'required|string|max:100',
                'follow_up_date' => 'nullable|date|after:visit_date',
                'notes' => 'nullable|string|max:1000'
            ]);

            $validated['user_id'] = Auth::id();

            MedicalHistory::create($validated);
            return back()->with('success', 'Medical history added successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to add medical history.');
        }
    }

    public function updateMedicalHistory(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'visit_date' => 'required|date',
                'diagnosis' => 'required|string|max:500',
                'treatment' => 'required|string|max:500',
                'medication' => 'nullable|string|max:300',
                'veterinarian_name' => 'required|string|max:100',
                'follow_up_date' => 'nullable|date|after:visit_date',
                'notes' => 'nullable|string|max:1000'
            ]);

            $medicalHistory = MedicalHistory::findOrFail($id);
            $medicalHistory->update($validated);
            return back()->with('success', 'Medical history updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Update unsuccessful.');
        }
    }

    public function destroyMedicalHistory($id)
    {
        try {
            MedicalHistory::destroy($id);
            return back()->with('success', 'Medical history deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Delete unsuccessful.');
        }
    }

   public function healthCard($id)
{
    try {
        // Fetch pet details with owner and user
        $pet = \App\Models\Pet::with('owner', 'user')->findOrFail($id);
        
    $userBranchId = optional($pet->user)->branch_id ?? optional(Auth::user())->branch_id;

        // Fetch all veterinarians for license lookup
        $veterinarians = \App\Models\User::where('branch_id', $userBranchId)
            ->where('user_role', 'veterinarian')
            ->get(['user_id', 'user_name', 'user_licenseNum']) 
            ->keyBy('user_id');

        // ===== VACCINATION RECORDS =====
        $vaccinations = collect();
        
        // Strategy 1: Fetch from tbl_vaccination_record (Direct Service Records)
        $vaccinationRecords = DB::table('tbl_vaccination_record')
            ->where('pet_id', $id)
            ->orderBy('date_administered', 'asc')
            ->get();
        
        foreach ($vaccinationRecords as $record) {
            // Try to get the visit for veterinarian info
            $visit = \App\Models\Visit::find($record->visit_id);
            $vetUserId = optional($visit)->user_id;
            $vetUser = $veterinarians->get($vetUserId);
            
            $vaccinations->push((object) [
                'visit_date' => $record->date_administered ?? $record->created_at,
                'product_description' => $record->remarks ?? 'Vaccination administered',
                'vaccine_name' => $record->vaccine_name ?? 'N/A',
                'manufacturer' => $record->manufacturer ?? '--',
                'batch_number' => $record->batch_no ?? '--',
                'follow_up_date' => $record->next_due_date,
                'user_name' => $record->administered_by ?? optional($vetUser)->user_name ?? 'N/A',
                'user_licenseNum' => optional($vetUser)->user_licenseNum ?? '--',
            ]);
        }

        
        // Strategy 2: Fetch from Appointments (for legacy data)
        $vaccinationServiceNames = [
            'Vaccination', 
            'Vaccination - Kennel Cough',
            'Vaccination - Kennel Cough (one dose)', 
            'Vaccination - Anti Rabies',
        ];
        
        $vaccinationServiceIds = \App\Models\Service::whereIn('serv_name', $vaccinationServiceNames)
            ->orWhere('serv_type', 'LIKE', '%vaccination%')
            ->pluck('serv_id')
            ->toArray();
        
        if (!empty($vaccinationServiceIds)) {
            $vaccinationAppointments = \App\Models\Appointment::with([
                'user',
                'services' => function ($q) use ($vaccinationServiceIds) {
                    $q->whereIn('tbl_appoint_serv.serv_id', $vaccinationServiceIds)
                      ->withPivot('prod_id', 'vet_user_id', 'vacc_next_dose', 'vacc_batch_no', 'vacc_notes');
                }
            ])
            ->where('pet_id', $id)
            ->whereIn('appoint_status', ['completed', 'arrived'])
            ->whereHas('services', function($q) use ($vaccinationServiceIds) {
                $q->whereIn('tbl_appoint_serv.serv_id', $vaccinationServiceIds);
            })
            ->orderBy('appoint_date', 'asc')
            ->get();

            // Fetch products for vaccine names
            $productIds = $vaccinationAppointments->flatMap(function ($appt) {
                return $appt->services->pluck('pivot.prod_id')->filter();
            })->unique()->toArray();

            $products = \App\Models\Product::whereIn('prod_id', $productIds)->get()->keyBy('prod_id');

            foreach ($vaccinationAppointments as $appointment) {
                $vaccinationService = $appointment->services->first();
                $pivot = optional($vaccinationService)->pivot;
                if ($pivot) {
                    $vetUserId = $pivot->vet_user_id;
                    $vetUser = $veterinarians->get($vetUserId);
                    
                    $vaccineProduct = $products->get($pivot->prod_id);
                    $vaccineProductName = optional($vaccineProduct)->prod_name ?? null;

                    // Prefer explicit pivot notes/remarks over product description
                    $productDescription = $pivot->vacc_notes ?? $pivot->vacc_description ?? optional($vaccineProduct)->prod_description ?? 'Vaccination record';

                    // Check if this vaccination is already in the collection (avoid duplicates)
                    $exists = $vaccinations->contains(function($vacc) use ($appointment, $vaccineProductName) {
                        return $vacc->visit_date == $appointment->appoint_date && 
                               $vacc->vaccine_name == $vaccineProductName;
                    });

                    if (!$exists) {
                        $vaccinations->push((object) [
                            'visit_date' => $appointment->appoint_date,
                            'product_description' => $productDescription,
                            'vaccine_name' => $vaccineProductName ?? 'Vaccine',
                            'batch_number' => $pivot->vacc_batch_no ?? '--',
                            'follow_up_date' => $pivot->vacc_next_dose,
                            'user_name' => optional($vetUser)->user_name ?? optional($appointment->user)->user_name ?? 'N/A',
                            'user_licenseNum' => optional($vetUser)->user_licenseNum ?? '--',
                        ]);
                    }
                }
            }
        }
        
        // Sort vaccinations by date
        $vaccinations = $vaccinations->sortBy('visit_date')->values();
        
        // ===== DEWORMING RECORDS =====
        $deworming = collect();
        
        // Strategy 1: Fetch from tbl_deworming_record (Direct Service Records)
        $dewormingRecords = DB::table('tbl_deworming_record')
            ->where('pet_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
        
        foreach ($dewormingRecords as $record) {
            // Try to get the visit for weight and date info
            $visit = \App\Models\Visit::find($record->visit_id);
            
            $deworming->push((object) [
                'visit_date' => optional($visit)->visit_date ?? $record->created_at,
                'weight' => optional($visit)->weight ?? null,
                'due_date' => $record->next_due_date,
                'treatment' => $record->dewormer_name . ($record->dosage ? ' (' . $record->dosage . ')' : ''),
            ]);
        }
        
        // Strategy 2: Fetch from Medical History (for legacy data)
        $dewormingKeywords = ['deworm', 'heartworm', 'parasite', 'flea', 'tick', 'worm', 'anthelmintic'];
        $dewormingMedical = \App\Models\MedicalHistory::where('pet_id', $id)
            ->where(function($query) use ($dewormingKeywords) {
                $query->where(function($q) use ($dewormingKeywords) {
                    foreach ($dewormingKeywords as $keyword) {
                        $q->orWhere('treatment', 'LIKE', '%' . $keyword . '%');
                        $q->orWhere('diagnosis', 'LIKE', '%' . $keyword . '%');
                        $q->orWhere('medication', 'LIKE', '%' . $keyword . '%');
                        $q->orWhere('notes', 'LIKE', '%' . $keyword . '%');
                    }
                });
            })
            ->orderBy('visit_date', 'asc')
            ->get();
        
        foreach ($dewormingMedical as $record) {
            // Check if this deworming is already in the collection (avoid duplicates)
            $exists = $deworming->contains(function($dw) use ($record) {
                return $dw->visit_date == $record->visit_date;
            });
            
            if (!$exists) {
                $deworming->push((object) [
                    'visit_date' => $record->visit_date,
                    'weight' => $record->weight,
                    'due_date' => $record->follow_up_date,
                    'treatment' => $record->treatment ?? $record->medication ?? 'Deworming',
                ]);
            }
        }
        
        // Sort deworming by date and limit to 14 records
        $deworming = $deworming->sortBy('visit_date')->values()->take(14);
        
        // Fetch branch information
        $branches = \App\Models\Branch::all(); 

        $clinicInfo = [ 
            'name' => 'Your Veterinary Clinic', 
            'contact' => '0912-345-6789 / (088) 123-4567', 
            'logo_url' => asset('images/pets2go.png'),  
        ];

        return view('petHealthCard', compact('pet', 'vaccinations', 'deworming', 'clinicInfo', 'branches'));

    } catch (\Exception $e) {
        Log::error('Health Card Generation Error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return back()->with('error', 'Failed to generate health card: ' . $e->getMessage());
    }
}
}