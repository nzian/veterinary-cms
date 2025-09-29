<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\MedicalHistory;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PetManagementController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get pets with pagination
            $perPage = $request->get('perPage', 10);
            $ownersPerPage = $request->get('ownersPerPage', 10);
            $medicalPerPage = $request->get('medicalPerPage', 10);
            $petsQuery = Pet::with('owner');
            
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

            // Get owners with pagination
            $ownersQuery = Owner::orderBy('own_name', 'asc');
            
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

            // Get medical histories with pagination
            $medicalQuery = MedicalHistory::with('pet');
            
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

            $allOwners = Owner::all();
            $allPets = Pet::all();

            return view('petManagement', compact('pets', 'owners', 'medicalHistories', 'allOwners', 'allPets'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load data.');
        }
    }

   // API Methods for Enhanced Modals
public function getOwnerDetails($id)
{
    try {
        $owner = Owner::find($id);
        
        if (!$owner) {
            return response()->json(['error' => 'Owner not found'], 404);
        }
        
        // Get all pets for this owner
        $pets = Pet::where('own_id', $id)->get();
        
        // Get all medical records for this owner's pets
        $petIds = $pets->pluck('pet_id');
        $medicalRecords = MedicalHistory::whereIn('pet_id', $petIds)
            ->orderBy('visit_date', 'desc')
            ->get();
        
        // Calculate stats
        $lastVisit = $medicalRecords->first() 
            ? Carbon::parse($medicalRecords->first()->visit_date)->format('M d, Y') 
            : 'Never';
        
        return response()->json([
            'owner' => [
                'own_id' => $owner->own_id,
                'own_name' => $owner->own_name,
                'own_contactnum' => $owner->own_contactnum,
                'own_location' => $owner->own_location
            ],
            'pets' => $pets->map(function($pet) {
                return [
                    'pet_id' => $pet->pet_id,
                    'pet_name' => $pet->pet_name,
                    'pet_species' => $pet->pet_species,
                    'pet_breed' => $pet->pet_breed,
                    'pet_age' => $pet->pet_age,
                    'pet_gender' => $pet->pet_gender,
                    'pet_photo' => $pet->pet_photo
                ];
            }),
            'appointments' => [], // Placeholder for appointments
            'products' => [], // Placeholder for products purchased
            'stats' => [
                'pets' => $pets->count(),
                'appointments' => 0,
                'medicalRecords' => $medicalRecords->count(),
                'lastVisit' => $lastVisit
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
}

public function getPetDetails($id)
{
    try {
        $pet = Pet::with('owner')->find($id);
        
        if (!$pet) {
            return response()->json(['error' => 'Pet not found'], 404);
        }
        
        // Get all medical history including weight/temperature records
        $medicalHistory = MedicalHistory::where('pet_id', $id)
            ->orderBy('visit_date', 'desc')
            ->get();
        
        // Separate vital signs history from regular medical records
        $vitalSignsHistory = $medicalHistory->filter(function($record) {
            return $record->weight !== null || $record->temperature !== null;
        });
        
        return response()->json([
            'pet' => [
                'pet_id' => $pet->pet_id,
                'pet_name' => $pet->pet_name,
                'pet_species' => $pet->pet_species,
                'pet_breed' => $pet->pet_breed,
                'pet_gender' => $pet->pet_gender,
                'pet_age' => $pet->pet_age,
                'pet_weight' => $pet->pet_weight,
                'pet_temperature' => $pet->pet_temperature,
                'pet_birthdate' => $pet->pet_birthdate,
                'pet_registration' => $pet->pet_registration,
                'pet_photo' => $pet->pet_photo,
                'owner' => $pet->owner ? [
                    'own_name' => $pet->owner->own_name,
                    'own_contactnum' => $pet->owner->own_contactnum,
                    'own_location' => $pet->owner->own_location
                ] : null
            ],
            'medicalHistory' => $medicalHistory->map(function($record) {
                return [
                    'id' => $record->id,
                    'visit_date' => Carbon::parse($record->visit_date)->format('Y-m-d'),
                    'diagnosis' => $record->diagnosis,
                    'treatment' => $record->treatment,
                    'medication' => $record->medication,
                    'veterinarian_name' => $record->veterinarian_name,
                    'follow_up_date' => $record->follow_up_date ? Carbon::parse($record->follow_up_date)->format('Y-m-d') : null,
                    'weight' => $record->weight,
                    'temperature' => $record->temperature,
                    'notes' => $record->notes
                ];
            }),
            'vitalSignsHistory' => $vitalSignsHistory->map(function($record) {
                return [
                    'visit_date' => Carbon::parse($record->visit_date)->format('M d, Y'),
                    'weight' => $record->weight,
                    'temperature' => $record->temperature
                ];
            }),
            'stats' => [
                'visits' => $medicalHistory->count(),
                'lastVisit' => $medicalHistory->first() ? Carbon::parse($medicalHistory->first()->visit_date)->format('M d, Y') : 'Never'
            ]
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
}