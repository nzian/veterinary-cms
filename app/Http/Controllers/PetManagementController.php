<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\MedicalHistory;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\Branch;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Traits\BranchFilterable;

class PetManagementController extends Controller
{
    use BranchFilterable;

    public function index(Request $request)
    {
        try {
            // Get pagination parameters for all tabs
            $ownersPerPage = $request->get('ownersPerPage', 10);
            $medicalPerPage = $request->get('medicalPerPage', 10);
            
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
            
            // Get active branch name
            $activeBranchName = $this->getActiveBranchName();

            return view('petManagement', compact(
                'pets', 
                'owners', 
                'medicalHistories', 
                'allOwners', 
                'allPets',
                'activeBranchName'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Pet Management Index Error: ' . $e->getMessage());
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

            // Gather appointments across all owner's pets
            $petIds = $owner->pets->pluck('pet_id')->all();
            $appointments = [];
            $lastVisit = 'Never';
            $appointmentsCount = 0;
            
            if (!empty($petIds)) {
                // Fetch appointments with 'user' (veterinarian/user) and 'pet' relationships
                $appointmentsE = Appointment::with(['user', 'pet']) 
                    ->whereIn('pet_id', $petIds)
                    ->orderBy('appoint_date', 'desc')
                    ->orderBy('appoint_time', 'desc')
                    ->limit(50)
                    ->get();
                    
                $appointments = $appointmentsE->map(function ($a) {
                    // Safely retrieve veterinarian name using optional helper
                    $veterinarianName = optional($a->user)->user_name ?? 'N/A';
                    
                    return [
                        'id' => $a->appoint_id,
                        'date' => Carbon::parse($a->appoint_date)->format('M d, Y'),
                        'time' => Carbon::parse($a->appoint_time)->format('h:i A'), 
                        'status' => $a->appoint_status,
                        'type' => $a->appoint_type,
                        'veterinarian' => $veterinarianName,
                        'pet_name' => optional($a->pet)->pet_name,
                    ];
                });
                
                $appointmentsCount = $appointmentsE->count();
                if ($appointmentsE->first()) {
                    $lastVisit = Carbon::parse($appointmentsE->first()->appoint_date)->format('M d, Y');
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
                'appointments' => $appointments, 
                'purchases' => $purchases,
                'stats' => [
                    'pets' => $owner->pets->count(),
                    'appointments' => $appointmentsCount,
                    'medicalRecords' => $medicalCount,
                    'lastVisit' => $lastVisit,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('getOwnerDetails Error: ' . $e->getMessage());
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
                    'visits' => $visits,
                    'lastVisit' => $lastVisit,
                ],
                'medicalHistory' => $medical,
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

            $validated['user_id'] = auth()->id();

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

            $validated['user_id'] = auth()->id();

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

            $validated['user_id'] = auth()->id();

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
            // Fetch pet details with owner and user (for context)
            $pet = \App\Models\Pet::with('owner', 'user')->findOrFail($id);
            
            $userBranchId = optional($pet->user)->branch_id ?? optional(auth()->user())->branch_id; 

            // 1. Fetch ALL Veterinarian users (for license lookup in one batch)
            $veterinarians = \App\Models\User::where('branch_id', $userBranchId)
                ->where('user_role', 'veterinarian')
                ->get(['user_id', 'user_name', 'user_licenseNum']) 
                ->keyBy('user_id'); // Key by user_id for direct access later

            // Define service ID for Vaccination
            $vaccinationServiceId = \App\Models\Service::where('serv_name', 'Vaccination')->value('serv_id'); 
            
            // --- 2. Process Vaccination Records (Guaranteed Data Fetch) ---
            
            $vaccinationAppointments = \App\Models\Appointment::with([
                'user', // Creator/Receptionist
                'services' => function ($q) use ($vaccinationServiceId) {
                    $q->where('tbl_appoint_serv.serv_id', $vaccinationServiceId)
                      ->withPivot('prod_id', 'vet_user_id', 'vacc_next_dose', 'vacc_batch_no', 'vacc_notes');
                }
            ])
            ->where('pet_id', $id)
            ->whereIn('appoint_status', ['completed', 'arrived']) 
            ->orderBy('appoint_date', 'asc')
            ->get();

            $vaccinations = collect();
            
            // --- 2A. Safely Pre-fetch all products required for vaccine names (FIX 1) ---
            $productIds = $vaccinationAppointments->flatMap(function ($appt) {
                // Use first() because the eager load was filtered to only include the vaccination service
                $service = $appt->services->first();
                
                // Safely check for the pivot object and the prod_id field
                $pivot = optional($service)->pivot;
                
                // Only return prod_id if the pivot object and the field exist
                return optional($pivot)->prod_id ? [$pivot->prod_id] : [];
            })->unique()->toArray();

            $products = \App\Models\Product::whereIn('prod_id', $productIds)->get()->keyBy('prod_id');

            // --- 2B. Start mapping with robust safety checks (FIX 2) ---
            foreach ($vaccinationAppointments as $appointment) {
                
                $vaccinationService = $appointment->services->first();
                
                // Use a defined variable for the pivot and check for its existence
                $pivot = optional($vaccinationService)->pivot; 
                
                // Proceed only if the pivot data (the vaccination record) exists and has a product ID
                if ($pivot && $pivot->prod_id) { 
                    
                    // --- VETERINARIAN LOOKUP ---
                    $vetUserId = $pivot->vet_user_id; // Get Vet ID from the pivot
                    $vetUser = $veterinarians->get($vetUserId); // Lookup Vet User
                    
                    $attendingVetName = optional($vetUser)->user_name ?? 'N/A (Vet Missing)';
                    $license = optional($vetUser)->user_licenseNum ?? '--';
                    
                    // --- PRODUCT NAME LOOKUP ---
                    $vaccineProduct = $products->get($pivot->prod_id);
                    $vaccineProductName = optional($vaccineProduct)->prod_name;
                    
                    // Medical History Fallback 
                    $medicalRecord = \App\Models\MedicalHistory::where('pet_id', $id)
                        ->where('visit_date', $appointment->appoint_date)
                        ->first();
                    $fallbackName = optional($medicalRecord)->treatment ?? 'Vaccine Details N/A';

                    $vaccinations->push((object) [
                        'visit_date' => $appointment->appoint_date,
                        'product_description' => $pivot->vacc_notes ?? 'No notes recorded',
                        'vaccine_name' => ($vaccineProductName !== null) 
                                            ? $vaccineProductName 
                                            : $fallbackName,
                        'batch_number' => $pivot->vacc_batch_no ?? '--',
                        'follow_up_date' => $pivot->vacc_next_dose, 
                        
                        // VETERINARIAN
                        'user_name' => $attendingVetName,
                        'user_licenseNum' => $license,
                    ]);
                }
            }
            
            // --- 3. Process Deworming Records (FIX 3: Expanded Search) ---
            $dewormingKeywords = ['deworm', 'heartworm', 'parasite', 'flea', 'tick', 'worm']; 
            $deworming = \App\Models\MedicalHistory::where('pet_id', $id)
                ->where(function($query) use ($dewormingKeywords) {
                    // Group the OR conditions across multiple columns
                    $query->where(function($q) use ($dewormingKeywords) {
                        foreach ($dewormingKeywords as $keyword) {
                            // Check multiple columns for the keyword
                            $q->orWhere('treatment', 'LIKE', '%' . $keyword . '%');
                            $q->orWhere('diagnosis', 'LIKE', '%' . $keyword . '%');
                            $q->orWhere('medication', 'LIKE', '%' . $keyword . '%');
                        }
                    });
                })
                ->orderBy('visit_date', 'asc')
                ->get()
                ->take(14);
            
            // --- Fetch dynamic branch information --- 
            $branches = \App\Models\Branch::all(); 
            
            $clinicInfo = [ 
                'name' => 'Your Veterinary Clinic', 
                'contact' => '0912-345-6789 / (088) 123-4567', 
                'logo_url' => asset('images/pets2go.png'),  
            ];

            // Return the view for printing the card, passing the enriched data
            return view('petHealthCard', compact('pet', 'vaccinations', 'deworming', 'clinicInfo', 'branches'));

        } catch (\Exception $e) {
            \Log::error('Health Card Generation Error: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate health card: ' . $e->getMessage());
        }
    }
}