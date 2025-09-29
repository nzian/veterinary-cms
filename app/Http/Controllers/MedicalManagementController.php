<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\Pet;
use App\Models\Service;
use App\Models\Owner;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MedicalManagementController extends Controller
{
    /**
     * Display the unified medical management interface
     */
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $activeTab = $request->get('active_tab', 'appointments'); // Get active tab

        // Get appointments with pagination
        $appointmentsQuery = Appointment::with(['pet.owner', 'services', 'user']);
        if ($perPage === 'all') {
            $appointments = $appointmentsQuery->get();
        } else {
            $appointments = $appointmentsQuery->paginate((int) $perPage);
        }

        // Get prescriptions with pagination
        $prescriptionPerPage = $request->get('prescriptionPerPage', 10);
        $prescriptionsQuery = Prescription::with(['pet.owner', 'branch']);
        if ($prescriptionPerPage === 'all') {
            $prescriptions = $prescriptionsQuery->get();
        } else {
            $prescriptions = $prescriptionsQuery->paginate((int) $prescriptionPerPage);
        }

        // Get referrals with pagination
        $referralPerPage = $request->get('referralPerPage', 10);
        $referralsQuery = Referral::with([
            'appointment.pet.owner',
            'refToBranch',
            'refByBranch'
        ]);
        if ($referralPerPage === 'all') {
            $referrals = $referralsQuery->get();
        } else {
            $referrals = $referralsQuery->paginate((int) $referralPerPage);
        }

        return view('medicalManagement', compact(
            'appointments', 
            'prescriptions', 
            'referrals',
            'activeTab'
        ));
    }

    // ==================== APPOINTMENT METHODS ====================

    /**
     * Store a new appointment
     */
    public function storeAppointment(Request $request)
{
    $validated = $request->validate([
        'appoint_time'        => 'required',
        'appoint_date'        => 'required|date',
        'appoint_status'      => 'required',
        'pet_id'              => 'required|exists:tbl_pet,pet_id',
        'appoint_type'        => 'nullable|string',
        'appoint_description' => 'nullable|string',
        'services'            => 'array',
        'services.*'          => 'exists:tbl_serv,serv_id',
    ]);

    $validated['user_id'] = auth()->id() ?? $request->input('user_id');
    $services = $validated['services'] ?? [];
    unset($validated['services']);

    $appointment = Appointment::create($validated);

    if (!empty($services)) {
        $appointment->services()->sync($services);
        
        // Auto-generate billing when appointment has services
        $this->generateBillingForAppointment($appointment);
    } 
     if (strtolower($validated['appoint_type']) === 'follow-up') {
        try {
            $smsService = new \App\Services\DynamicSMSService();
            $smsResult = $smsService->sendFollowUpSMS($appointment);
            
            if ($smsResult) {
                Log::info("SMS sent successfully for appointment {$appointment->appoint_id}");
            } else {
                Log::warning("SMS failed to send for appointment {$appointment->appoint_id}");
            }
        } catch (\Exception $e) {
            Log::error("SMS notification failed for appointment {$appointment->appoint_id}: " . $e->getMessage());
            // Don't fail the appointment creation, just log the error
        }
    }
    

    $activeTab = $request->input('active_tab', 'appointments');
    
    
    return redirect()->route('medical.index', ['active_tab' => $activeTab])
                   ->with('success', 'Appointment added successfully');
}

     /**
     * Update an existing appointment
     */
    public function updateAppointment(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'appoint_date' => 'required|date',
            'appoint_time' => 'required',
            'appoint_status' => 'required|string',
            'appoint_type' => 'required|string',
            'pet_id' => 'required|integer|exists:tbl_pet,pet_id',
            'appoint_description' => 'nullable|string',
            'services' => 'array',
            'services.*' => 'exists:tbl_serv,serv_id',
        ]);

        // Check if date or time has changed for SMS notification
        $dateChanged = $appointment->appoint_date !== $validated['appoint_date'];
        $timeChanged = $appointment->appoint_time !== $validated['appoint_time'];
        $isRescheduled = $dateChanged || $timeChanged;

        // Store original values for logging
        $originalDate = $appointment->appoint_date;
        $originalTime = $appointment->appoint_time;

        // Update the appointment
        $appointment->update($validated);

        // Sync services
        if ($request->has('services')) {
            $appointment->services()->sync($request->services);
        } else {
            $appointment->services()->sync([]);
        }

        // Send reschedule SMS if date or time changed
        if ($isRescheduled) {
            try {
                // Load the pet and owner relationships
                $appointment->load('pet.owner');
                
                $smsService = new \App\Services\DynamicSMSService();
                $smsResult = $smsService->sendRescheduleSMS($appointment);
                
                if ($smsResult) {
                    Log::info("Reschedule SMS sent successfully for appointment {$appointment->appoint_id}", [
                        'owner' => $appointment->pet->owner->own_name ?? 'Unknown',
                        'pet' => $appointment->pet->pet_name ?? 'Unknown',
                        'original_date' => $originalDate,
                        'original_time' => $originalTime,
                        'new_date' => $validated['appoint_date'],
                        'new_time' => $validated['appoint_time']
                    ]);
                } else {
                    Log::warning("Reschedule SMS failed to send for appointment {$appointment->appoint_id}");
                }
            } catch (\Exception $e) {
                Log::error("Reschedule SMS notification failed for appointment {$appointment->appoint_id}: " . $e->getMessage());
                // Don't fail the appointment update, just log the error
            }
        }
        
        $activeTab = $request->input('active_tab', 'appointments');
        
        $successMessage = 'Appointment updated successfully';
        if ($isRescheduled) {
            $successMessage .= ' and reschedule notification sent';
        }
        
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
                       ->with('success', $successMessage);
    }

    /**
     * Delete an appointment
     */
    public function destroyAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->services()->detach();
        $appointment->delete();

        $activeTab = $request->input('active_tab', 'appointments');
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
                       ->with('success', 'Appointment deleted successfully');
    }

    /**
     * Get appointment details for referral
     */
    public function getAppointmentDetails($id)
    {
        try {
            $appointment = Appointment::with([
                'pet' => function($query) {
                    $query->with('owner');
                },
                'services'
            ])->findOrFail($id);

            $medicalHistory = [];
            $recentPrescriptions = Prescription::where('pet_id', $appointment->pet_id)
                ->orderBy('prescription_date', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentPrescriptions as $prescription) {
                $medications = json_decode($prescription->medication, true) ?? [];
                $medicalHistory[] = "Date: " . Carbon::parse($prescription->prescription_date)->format('M d, Y') . 
                    " - Medications: " . implode(', ', array_column($medications, 'product_name'));
            }

            $currentMedications = "";
            if ($recentPrescriptions->isNotEmpty()) {
                $latestPrescription = $recentPrescriptions->first();
                $latestMeds = json_decode($latestPrescription->medication, true) ?? [];
                $currentMedications = implode(', ', array_column($latestMeds, 'product_name'));
            }

            return response()->json([
                'pet' => $appointment->pet,
                'owner' => $appointment->pet->owner,
                'services' => $appointment->services,
                'medical_history' => implode("\n", $medicalHistory),
                'recent_tests' => "Tests conducted during appointment: " . $appointment->services->pluck('serv_name')->join(', '),
                'current_medications' => $currentMedications
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }
    }

    /**
     * Get appointment for prescription (new method)
     */
    public function getAppointmentForPrescription($id)
    {
        try {
            $appointment = Appointment::with(['pet.owner', 'services'])->findOrFail($id);
            
            return response()->json([
                'pet_id' => $appointment->pet_id,
                'pet_name' => $appointment->pet->pet_name,
                'appointment_date' => $appointment->appoint_date,
                'services' => $appointment->services->pluck('serv_name')->join(', ')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }
    }

    /**
     * Generate billing for appointment
     */
    private function generateBillingForAppointment($appointment)
    {
        if (!$appointment->services || $appointment->services->count() === 0) {
            return;
        }

        $existingBilling = \App\Models\Billing::where('appoint_id', $appointment->appoint_id)->first();
        if ($existingBilling) {
            return;
        }

        \App\Models\Billing::create([
            'bill_date' => $appointment->appoint_date,
            'appoint_id' => $appointment->appoint_id,
            'bill_status' => 'Pending',
        ]);
    }

    // ==================== PRESCRIPTION METHODS ====================

    public function storePrescription(Request $request)
    {
        try {
            $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'prescription_date' => 'required|date',
                'medications_json' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $medications = json_decode($request->medications_json, true);
            if (empty($medications)) {
                $activeTab = $request->input('active_tab', 'prescriptions');
                return redirect()->route('medical.index', ['active_tab' => $activeTab])
                               ->with('error', 'At least one medication is required');
            }

            Prescription::create([
                'pet_id' => $request->pet_id,
                'prescription_date' => $request->prescription_date,
                'medication' => json_encode($medications),
                'notes' => $request->notes,
                'branch_id' => auth()->user()->branch_id ?? 1
            ]);

            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('medical.index', ['active_tab' => $activeTab])
                           ->with('success', 'Prescription created successfully!');
        } catch (\Exception $e) {
            Log::error('Prescription creation error: ' . $e->getMessage());
            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('medical.index', ['active_tab' => $activeTab])
                           ->with('error', 'Error creating prescription. Please try again.');
        }
    }

    public function editPrescription($id)
    {
        try {
            $prescription = Prescription::with('pet')->findOrFail($id);
            $medications = json_decode($prescription->medication, true) ?? [];
            
            return response()->json([
                'prescription_id' => $prescription->prescription_id,
                'pet_id' => $prescription->pet_id,
                'prescription_date' => $prescription->prescription_date,
                'medications' => $medications,
                'notes' => $prescription->notes
            ]);
        } catch (\Exception $e) {
            Log::error('Prescription edit error: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading prescription data'], 500);
        }
    }

    public function updatePrescription(Request $request, $id)
    {
        try {
            $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'prescription_date' => 'required|date',
                'medications_json' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $prescription = Prescription::findOrFail($id);
            $medications = json_decode($request->medications_json, true);

            if (empty($medications)) {
                $activeTab = $request->input('active_tab', 'prescriptions');
                return redirect()->route('medical.index', ['active_tab' => $activeTab])
                               ->with('error', 'At least one medication is required');
            }

            $prescription->update([
                'pet_id' => $request->pet_id,
                'prescription_date' => $request->prescription_date,
                'medication' => json_encode($medications),
                'notes' => $request->notes
            ]);

            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('medical.index', ['active_tab' => $activeTab])
                           ->with('success', 'Prescription updated successfully!');
        } catch (\Exception $e) {
            Log::error('Prescription update error: ' . $e->getMessage());
            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('medical.index', ['active_tab' => $activeTab])
                           ->with('error', 'Error updating prescription. Please try again.');
        }
    }

    public function destroyPrescription(Request $request, $id)
    {
        try {
            $prescription = Prescription::findOrFail($id);
            $prescription->delete();

            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('medical.index', ['active_tab' => $activeTab])
                           ->with('success', 'Prescription deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Prescription deletion error: ' . $e->getMessage());
            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('medical.index', ['active_tab' => $activeTab])
                           ->with('error', 'Error deleting prescription. Please try again.');
        }
    }

    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }
        
        try {
            $products = DB::table('tbl_prod')
                ->where(function($q) use ($query) {
                    $q->where('prod_name', 'LIKE', "%{$query}%")
                      ->orWhere('prod_description', 'LIKE', "%{$query}%")
                      ->orWhere('prod_category', 'LIKE', "%{$query}%");
                })
                ->select(
                    'prod_id as id',
                    'prod_name as name', 
                    'prod_price as price',
                    'prod_category as type',
                    'prod_description as description'
                )
                ->limit(15)
                ->get();
            
            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Product search error: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    // ==================== REFERRAL METHODS ====================

    public function storeReferral(Request $request)
    {
        $request->validate([
            'ref_date' => 'required|date',
            'ref_description' => 'required|string',
            'ref_to' => 'required|exists:tbl_branch,branch_id',
            'appointment_id' => 'required|exists:tbl_appoint,appoint_id',
            'medical_history' => 'nullable|string',
            'tests_conducted' => 'nullable|string',
            'medications_given' => 'nullable|string',
        ]);

        $appointment = Appointment::findOrFail($request->appointment_id);
        $appointment->update(['appoint_status' => 'refer']);

        Referral::create([
            'ref_date' => $request->ref_date,
            'ref_description' => $request->ref_description,
            'appoint_id' => $request->appointment_id,
            'ref_to' => $request->ref_to,
            'ref_by' => auth()->user()->branch_id ?? null,
            'medical_history' => $request->medical_history,
            'tests_conducted' => $request->tests_conducted,
            'medications_given' => $request->medications_given,
        ]);

        $activeTab = $request->input('active_tab', 'referrals');
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
                       ->with('success', 'Referral submitted successfully.');
    }

    public function editReferral($id)
    {
        try {
            $referral = Referral::with(['appointment.pet.owner', 'refToBranch', 'refByBranch'])
                ->findOrFail($id);

            return response()->json([
                'ref_id' => $referral->ref_id,
                'appointment_id' => $referral->appoint_id,
                'ref_date' => $referral->ref_date,
                'ref_to' => $referral->ref_to,
                'ref_description' => $referral->ref_description,
                'medical_history' => $referral->medical_history,
                'tests_conducted' => $referral->tests_conducted,
                'medications_given' => $referral->medications_given,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Referral not found'], 404);
        }
    }

    public function updateReferral(Request $request, $id)
    {
        $referral = Referral::findOrFail($id);

        $request->validate([
            'ref_date' => 'required|date',
            'ref_description' => 'required|string',
            'ref_to' => 'required|exists:tbl_branch,branch_id',
            'medical_history' => 'nullable|string',
            'tests_conducted' => 'nullable|string',
            'medications_given' => 'nullable|string',
        ]);

        $referral->update([
            'ref_date'          => $request->ref_date,
            'ref_description'   => $request->ref_description,
            'ref_to'            => $request->ref_to,
            'medical_history'   => $request->medical_history,
            'tests_conducted'   => $request->tests_conducted,
            'medications_given' => $request->medications_given,
        ]);

        $activeTab = $request->input('active_tab', 'referrals');
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
                       ->with('success', 'Referral updated successfully.');
    }

    public function showReferral($id)
    {
        try {
            $referral = Referral::with([
                'appointment.pet.owner', 
                'refToBranch', 
                'refByBranch'
            ])->findOrFail($id);

            return response()->json([
                'ref_id' => $referral->ref_id,
                'ref_date' => Carbon::parse($referral->ref_date)->format('F d, Y'),
                'ref_description' => $referral->ref_description,
                'medical_history' => $referral->medical_history,
                'tests_conducted' => $referral->tests_conducted,
                'medications_given' => $referral->medications_given,
                'pet_name' => $referral->appointment->pet->pet_name,
                'pet_species' => $referral->appointment->pet->pet_species,
                'pet_breed' => $referral->appointment->pet->pet_breed,
                'pet_gender' => $referral->appointment->pet->pet_gender,
                'pet_dob' => $referral->appointment->pet->pet_birthdate ? 
                    Carbon::parse($referral->appointment->pet->pet_birthdate)->format('F d, Y') : 'Not specified',
                'pet_weight' => $referral->appointment->pet->pet_weight ? 
                    $referral->appointment->pet->pet_weight . ' kg' : 'Not specified',
                'owner_name' => $referral->appointment->pet->owner->own_name,
                'owner_contact' => $referral->appointment->pet->owner->own_contactnum,
                
                'ref_to_branch' => $referral->refToBranch->branch_name ?? 'N/A',
                'ref_by_branch' => $referral->refByBranch->branch_name ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Referral not found'], 404);
        }
    }

    public function destroyReferral(Request $request, $id)
    {
        $referral = Referral::findOrFail($id);
        
        if ($referral->appoint_id) {
            $appointment = Appointment::find($referral->appoint_id);
            if ($appointment && $appointment->appoint_status === 'refer') {
                $appointment->appoint_status = 'completed';
                $appointment->save();
            }
        }
        
        $referral->delete();

        $activeTab = $request->input('active_tab', 'referrals');
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
                       ->with('success', 'Referral deleted successfully!');
    }

    // ==================== HELPER METHODS ====================

    public function printPrescription($id)
    {
        $prescription = Prescription::with(['pet.owner', 'branch'])->findOrFail($id);
        return view('prescription-print', compact('prescription'));
    }

    public function showAppointment($id)
    {
        $appointment = Appointment::with(['pet.owner', 'services', 'user'])->findOrFail($id);
        return response()->json($appointment);
    }
}