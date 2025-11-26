<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\Visit;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\DynamicSMSService;

class CareContinuityController extends Controller
{
    protected $smsService;

    public function __construct(DynamicSMSService $smsService)
    {
        $this->middleware('auth');
        $this->smsService = $smsService;
    }
    /**
     * Display the Care Continuity Management page
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('active_tab', 'appointments');
        $activeBranchId = session('active_branch_id');
        $user = Auth::user();
        if($user === null){
            return redirect()->route('login');
        }
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        $branchUserIds = User::where('branch_id', $activeBranchId)->pluck('user_id')->toArray();

        // Follow-up Appointments Query
        $appointmentPerPage = $request->get('appointmentPerPage', 10);
        $appointmentsQuery = Appointment::with(['pet.owner', 'services', 'user'])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->where(function($q) {
                // Exact generic follow-up types
                $q->whereIn('appoint_type', ['Follow-up', 'Vaccination Follow-up', 'Deworming Follow-up', 'Post-Surgical Recheck'])
                  // Auto-scheduled detailed types like "Vaccination Follow-up for 5-in-1 (Dose 2)"
                  ->orWhere('appoint_type', 'like', 'Vaccination Follow-up%')
                  ->orWhere('appoint_type', 'like', 'Deworming Follow-up%')
                  ->orWhere('appoint_type', 'like', 'Post-Surgical Recheck%');
            })
            ->orderBy('appoint_date', 'asc')
            ->orderBy('appoint_time', 'asc');

        $appointments = $appointmentPerPage === 'all' 
            ? $appointmentsQuery->get() 
            : $appointmentsQuery->paginate((int) $appointmentPerPage, ['*'], 'appointmentsPage');

        // Prescriptions Query
        $prescriptionPerPage = $request->get('prescriptionPerPage', 10);
        $prescriptionsQuery = Prescription::with(['pet.owner', 'branch', 'user'])
            ->where('branch_id', $activeBranchId)
            ->orderBy('prescription_date', 'desc')
            ->orderBy('prescription_id', 'desc');

        $prescriptions = $prescriptionPerPage === 'all' 
            ? $prescriptionsQuery->get() 
            : $prescriptionsQuery->paginate((int) $prescriptionPerPage, ['*'], 'prescriptionsPage');

        // Referrals Query
        $referralPerPage = $request->get('referralPerPage', 10);
        $referralsQuery = Referral::with(['pet.owner', 'refToBranch', 'refFromBranch', 'refByBranch'])
            ->where(function($q) use ($activeBranchId) {
                $q->where('ref_to', $activeBranchId)
                  ->orWhere('ref_from', $activeBranchId);
            })
            ->orderBy('ref_date', 'desc')
            ->orderBy('ref_id', 'desc');

        $referrals = $referralPerPage === 'all' 
            ? $referralsQuery->get() 
            : $referralsQuery->paginate((int) $referralPerPage, ['*'], 'referralsPage');

        // Attach medical history data to each referral
        foreach ($referrals as $referral) {
            $this->attachMedicalHistoryToReferral($referral);
        }

        // Lookups for modals
        $filteredOwners = Owner::whereIn('user_id', $branchUserIds)->get();
        $filteredPets = Pet::whereIn('user_id', $branchUserIds)->with('owner')->get();
        $allBranches = Branch::all();

        $careContinuityModals = true;
        // Pet IDs that already have a visit today (to hide Add Visit button)
        $todayVisitPetIds = Visit::whereDate('visit_date', Carbon::today())
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->pluck('pet_id')
            ->unique()
            ->values();

        return view('care-continuity', compact(
            'appointments',
            'prescriptions',
            'referrals',
            'activeTab',
            'filteredOwners',
            'filteredPets',
            'allBranches',
            'careContinuityModals',
            'todayVisitPetIds',
            
        ));
    }

    /**
     * Store a new follow-up appointment
     */
    public function storeFollowUpAppointment(Request $request)
    {
        if(Auth::user() === null){
            return redirect()->route('login');
        }
        $validated = $request->validate([
            'pet_id' => 'required|exists:tbl_pet,pet_id',
            'appoint_date' => 'required|date',
            'appoint_time' => 'required',
            'appoint_type' => 'required|in:Follow-up,Vaccination Follow-up,Deworming Follow-up,Post-Surgical Recheck',
            'appoint_description' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['appoint_status'] = 'scheduled';

        $appointment = Appointment::create($validated);

        // Send SMS notification for new follow-up appointment
        try {
            $this->smsService->sendNewAppointmentSMS($appointment);
        } catch (\Exception $e) {
            Log::warning("Failed to send SMS for appointment {$appointment->appoint_id}: " . $e->getMessage());
        }

        $activeTab = $request->input('active_tab', 'appointments');
        return redirect()->route('care-continuity.index', ['active_tab' => $activeTab])
            ->with('success', 'Follow-up appointment created successfully');
    }

    /**
     * Update a follow-up appointment
     */
    public function updateFollowUpAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'appoint_date' => 'required|date',
            'appoint_time' => 'required',
        ]);

        // Store old values for SMS notification
        $oldDate = $appointment->appoint_date;
        $oldTime = $appointment->appoint_time;

        // Rescheduling-only: update date/time and force status
        $appointment->appoint_date = $validated['appoint_date'];
        $appointment->appoint_time = $validated['appoint_time'];
        $appointment->appoint_status = 'rescheduled';
        $appointment->save();

        // Send SMS notification for rescheduled appointment
        try {
            $this->smsService->sendRescheduleSMS($appointment);
        } catch (\Exception $e) {
            Log::warning("Failed to send reschedule SMS for appointment {$appointment->appoint_id}: " . $e->getMessage());
        }

        // If this is an AJAX/JSON request (e.g., from Dashboard), return JSON instead of redirect
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'appointment_id' => $appointment->appoint_id,
                'appoint_status' => $appointment->appoint_status,
            ]);
        }

        $activeTab = $request->input('active_tab', 'appointments');
        return redirect()->route('care-continuity.index', ['active_tab' => $activeTab])
            ->with('success', 'Follow-up appointment updated successfully');
    }

    /**
     * Delete a follow-up appointment
     */
    public function destroyFollowUpAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        $activeTab = $request->input('active_tab', 'appointments');
        return redirect()->route('care-continuity.index', ['active_tab' => $activeTab])
            ->with('success', 'Follow-up appointment deleted successfully');
    }

    /**
     * Store a new prescription
     */
    public function storeFollowUpPrescription(Request $request)
    {
        if(Auth::user() === null){
            return redirect()->route('login');
        }
        try {
            $request->validate([
                'pet_id' => 'required|exists:tbl_pet,pet_id',
                'prescription_date' => 'required|date',
                'medications_json' => 'required|string',
                'differential_diagnosis' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $medications = json_decode($request->medications_json, true);
            if (empty($medications)) {
                return redirect()->back()->with('error', 'At least one medication is required.');
            }

            $user = Auth::user();

            Prescription::create([
                'pet_id' => $request->pet_id,
                'prescription_date' => $request->prescription_date,
                'medication' => json_encode($medications),
                'differential_diagnosis' => $request->differential_diagnosis,
                'notes' => $request->notes,
                'branch_id' => $user->branch_id ?? 1,
                'user_id' => $user->user_id ?? null,
            ]);

            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('care-continuity.index', ['active_tab' => $activeTab])
                ->with('success', 'Prescription created successfully!');
        } catch (\Exception $e) {
            Log::error('Prescription creation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating prescription');
        }
    }

    /**
     * Show prescription details
     */
    public function showPrescription($id)
    {
        try {
            $prescription = Prescription::with(['pet.owner', 'branch'])->findOrFail($id);
            return response()->json($prescription);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Prescription not found'], 404);
        }
    }

    /**
     * Delete a prescription
     */
    public function destroyPrescription(Request $request, $id)
    {
        try {
            Prescription::findOrFail($id)->delete();
            
            $activeTab = $request->input('active_tab', 'prescriptions');
            return redirect()->route('care-continuity.index', ['active_tab' => $activeTab])
                ->with('success', 'Prescription deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting prescription');
        }
    }

    /**
     * Store a new referral
     */
    public function storeReferral(Request $request)
    {
        $validated = $request->validate([
            'pet_id' => 'required|exists:tbl_pet,pet_id',
            'ref_date' => 'required|date',
            'ref_to' => 'required|exists:tbl_branch,branch_id',
            'ref_description' => 'required|string',
        ]);

        try {
            // Create a temporary appointment for the referral
            $appointment = Appointment::create([
                'pet_id' => $validated['pet_id'],
                'appoint_date' => $validated['ref_date'],
                'appoint_time' => '09:00:00',
                'appoint_status' => 'refer',
                'appoint_type' => 'Referral',
                'user_id' => Auth::id(),
            ]);

            // Send SMS notification for referral appointment
            try {
                $this->smsService->sendNewAppointmentSMS($appointment);
            } catch (\Exception $e) {
                Log::warning("Failed to send SMS for referral appointment {$appointment->appoint_id}: " . $e->getMessage());
            }

            Referral::create([
                'appoint_id' => $appointment->appoint_id,
                'ref_date' => $validated['ref_date'],
                'ref_to' => $validated['ref_to'],
                'ref_description' => $validated['ref_description'],
                'ref_by' => Auth::id(),
            ]);

            $activeTab = $request->input('active_tab', 'referrals');
            return redirect()->route('care-continuity.index', ['active_tab' => $activeTab])
                ->with('success', 'Referral created successfully');
        } catch (\Exception $e) {
            Log::error('Referral creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create referral');
        }
    }

    /**
     * Show referral details
     */
    public function showReferral($id)
    {
        try {
            $referral = Referral::with(['appointment.pet.owner', 'pet.owner', 'refToBranch', 'refByBranch'])
                ->findOrFail($id);

            return response()->json([
                'ref_id' => $referral->ref_id,
                'ref_date' => Carbon::parse($referral->ref_date)->format('F d, Y'),
                'ref_description' => $referral->ref_description,
                'pet_name' => $referral->pet?->pet_name ?? $referral->appointment?->pet?->pet_name ?? 'N/A',
                'owner_name' => $referral->pet?->owner?->own_name ?? $referral->appointment?->pet?->owner?->own_name ?? 'N/A',
                'ref_to_branch' => $referral->refToBranch->branch_name ?? 'N/A',
                'ref_by_branch' => $referral->refByBranch->branch_name ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Referral not found'], 404);
        }
    }

    /**
     * Delete a referral
     */
    public function destroyReferral(Request $request, $id)
    {
        $referral = Referral::findOrFail($id);
        $referral->delete();
        $activeTab = $request->input('active_tab', 'referrals');
        return redirect()->route('care-continuity.index', ['active_tab' => $activeTab])
            ->with('success', 'Referral deleted successfully!');
    }

    /**
     * Create a Visit from an Arrived Appointment
     */
    public function createVisitFromAppointment(Request $request, $id)
    {
        try {
            $appointment = Appointment::with(['pet', 'user', 'services'])->findOrFail($id);

            if (strtolower($appointment->appoint_status) !== 'arrived') {
                $message = 'Visit can only be created when appointment status is Arrived.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->with('error', $message);
            }

            // Prevent duplicate visit creation (same pet, same day)
            $existing = Visit::whereDate('visit_date', Carbon::today())
                ->where('pet_id', $appointment->pet_id)
                ->first();
            if ($existing) {
                $redirect = route('medical.index') . '?active_tab=visits';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'visit_id' => $existing->visit_id,
                        'redirect' => $redirect,
                        'message' => 'Visit already exists for today. Redirecting to Visits.'
                    ]);
                }
                return redirect($redirect)->with('success', 'Visit already exists for today.');
            }

            $visit = Visit::create([
                'visit_date' => Carbon::today()->toDateString(),
                'pet_id' => $appointment->pet_id,
                'user_id' => $appointment->user_id,
                'patient_type' => 'outpatient',
                'visit_status' => 'pending',
                'workflow_status' => 'Pending',
            ]);

            // Determine services based on the most recent prior Visit for this pet (no dependency on appointment services)
            $serviceIds = [];
            {
                $priorVisit = Visit::with('services')
                    ->where('pet_id', $appointment->pet_id)
                    ->when(!empty($appointment->appoint_date), function($q) use ($appointment){
                        $q->whereDate('visit_date', '<=', Carbon::parse($appointment->appoint_date)->toDateString());
                    })
                    ->orderBy('visit_date', 'desc')
                    ->orderBy('visit_id', 'desc')
                    ->first();
                if (!$priorVisit) {
                    // Fallback: any most recent visit regardless of date
                    $priorVisit = Visit::with('services')
                        ->where('pet_id', $appointment->pet_id)
                        ->orderBy('visit_date', 'desc')
                        ->orderBy('visit_id', 'desc')
                        ->first();
                }
                if ($priorVisit && $priorVisit->services && $priorVisit->services->count() > 0) {
                    $serviceIds = $priorVisit->services->pluck('serv_id')->toArray();
                }

                // If prior visit had no explicit services, infer from clinical records
                if (empty($serviceIds) && $priorVisit) {
                    try {
                        $hadVacc = \Illuminate\Support\Facades\DB::table('tbl_vaccination_record')
                            ->where('visit_id', $priorVisit->visit_id)
                            ->exists();
                        $hadDeworm = !$hadVacc && \Illuminate\Support\Facades\DB::table('tbl_deworming_record')
                            ->where('visit_id', $priorVisit->visit_id)
                            ->exists();

                        if ($hadVacc) {
                            $svc = \App\Models\Service::whereRaw('LOWER(serv_type) = ?', ['vaccination'])->first();
                            if ($svc) { $serviceIds = [$svc->serv_id]; }
                        } elseif ($hadDeworm) {
                            $svc = \App\Models\Service::whereRaw('LOWER(serv_type) = ?', ['deworming'])->first();
                            if ($svc) { $serviceIds = [$svc->serv_id]; }
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Clinical-record inference failed: '.$e->getMessage());
                    }
                }

                // Final fallback: infer from appointment description/type (e.g., "Vaccination Follow-up for ...")
                if (empty($serviceIds)) {
                    $hay = strtolower(trim(($appointment->appoint_description ?? '') . ' ' . ($appointment->appoint_type ?? '')));
                    $map = [
                        'vaccination' => 'vaccination',
                        'deworm' => 'deworming',
                        'groom' => 'grooming',
                        'check' => 'check up',
                        'consult' => 'check up',
                        'diagnostic' => 'diagnostics',
                        'surg' => 'surgical',
                        'emergency' => 'emergency',
                        'board' => 'boarding',
                    ];
                    $match = null;
                    foreach ($map as $needle => $servType) {
                        if ($hay !== '' && str_contains($hay, $needle)) { $match = $servType; break; }
                    }
                    if ($match) {
                        $svc = \App\Models\Service::whereRaw('LOWER(serv_type) = ?', [$match])->first();
                        if ($svc) { $serviceIds = [$svc->serv_id]; }
                    }
                }
                \Log::info('CreateVisitFromAppointment serviceIds', ['appoint_id' => $appointment->appoint_id, 'visit_id' => $visit->visit_id, 'service_ids' => $serviceIds]);
            }

            if (!empty($serviceIds)) {
                $visit->services()->sync($serviceIds);
                // Optional denormalized summary for rendering fallbacks
                try {
                    $types = \App\Models\Service::whereIn('serv_id', $serviceIds)->pluck('serv_type')->filter()->unique()->values()->all();
                    if (\Illuminate\Support\Facades\Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
                        $visit->visit_service_type = !empty($types) ? implode(', ', $types) : null;
                        $visit->save();
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed to set visit_service_type summary: '.$e->getMessage());
                }
            }

            $redirect = route('medical.index') . '?active_tab=visits';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'visit_id' => $visit->visit_id,
                    'redirect' => $redirect,
                ]);
            }

            return redirect($redirect)->with('success', 'Visit created from appointment.');
        } catch (\Exception $e) {
            Log::error('Create visit from appointment failed: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create visit'], 500);
            }
            return back()->with('error', 'Failed to create visit from appointment.');
        }
    }

    /**
     * Create a Visit from a Referral
     */
    public function createVisitFromReferral(Request $request, $id)
    {
        // Define the target tab for redirects (both success and failure)
        $referralsTab = 'referrals';
        $visitsTab = 'visits';
        $referralsRedirect = route('care-continuity.index', ['active_tab' => $referralsTab]);
        $visitsRedirect = route('medical.index', ['active_tab' => $visitsTab]);

        try {
            $referral = Referral::with(['pet.owner', 'visit.services'])->findOrFail($id);
            $currentUser = Auth::user();
            $currentBranchId = $currentUser->user_role === 'superadmin' 
                ? session('active_branch_id') 
                : $currentUser->branch_id;
            
            // Check if current branch is the referred branch
            if ($referral->ref_to != $currentBranchId) {
                return redirect($referralsRedirect)->with('error', 'Only the referred branch can create a visit from this referral.');
            }

            // Check if referral is pending and interbranch
            if ($referral->ref_status !== 'pending' || $referral->ref_type !== 'interbranch') {
                return redirect($referralsRedirect)->with('error', 'Visit can only be created for pending interbranch referrals.');
            }

            $pet = $referral->pet;
            $originalVisit = $referral->visit;

            // Prevent duplicate visit creation (same pet, same day)
            $existing = Visit::whereDate('visit_date', Carbon::today())
                ->where('pet_id', $pet->pet_id)
                ->first();
                
            if ($existing) {
                // Already existing, redirect to visits tab
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'visit_id' => $existing->visit_id,
                        'redirect' => $visitsRedirect,
                        'message' => 'Visit already exists for today. Redirecting to Visits.'
                    ]);
                }
                return redirect($visitsRedirect)->with('success', 'Visit already exists for today.');
            }

            // Create the visit in the referred branch with data from original visit
            $visit = Visit::create([
                'visit_date' => $referral->ref_date,
                'pet_id' => $pet->pet_id,
                'user_id' => $currentUser->user_id,
                'weight' => $originalVisit->weight ?? null,
                'temperature' => $originalVisit->temperature ?? null,
                'patient_type' => 'outpatient',
                'visit_status' => 'arrived',
                'workflow_status' => 'Active',
            ]);

            // Update the referral status to attended
            $referral->update([
                'ref_status' => 'attended',
                'referred_visit_id' => $visit->visit_id
            ]);

            // Redirect to the medical management visits tab
            $redirect = route('medical.index', ['active_tab' => 'visits']);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'visit_id' => $visit->visit_id,
                    'redirect' => $redirect,
                    'message' => 'Visit created and marked as Catered.'
                ]);
            }

            return redirect($redirect)->with('success', 'Visit created and marked as Catered.');

        } catch (\Exception $e) {
            Log::error('Create visit from referral failed: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create visit'], 500);
            }
            return back()->with('error', 'Failed to create visit from referral: ' . $e->getMessage());
        }
    }

    /**
     * Attach medical history, tests conducted, and medications given to referral
     */
    private function attachMedicalHistoryToReferral($referral)
    {
        if (!$referral || !$referral->pet_id) {
            $referral->medical_history = '';
            $referral->tests_conducted = '';
            $referral->medications_given = '';
            $referral->ref_to_display = $referral->external_clinic_name ?? 
                                        ($referral->refToBranch?->branch_name ?? 'N/A');
            return;
        }

        $petId = $referral->pet_id;

        // Get medical history from completed services (excluding grooming and boarding)
        $completedVisits = Visit::with(['services' => function($q) {
                $q->wherePivot('status', 'completed')
                  ->whereNotIn(DB::raw('LOWER(tbl_serv.serv_type)'), ['grooming', 'boarding']);
            }])
            ->where('pet_id', $petId)
            ->whereHas('services', function($q) {
                $q->where('tbl_visit_service.status', 'completed')
                  ->whereNotIn(DB::raw('LOWER(tbl_serv.serv_type)'), ['grooming', 'boarding']);
            })
            ->orderBy('visit_date', 'desc')
            ->limit(5)
            ->get();

        $medicalHistoryItems = [];
        foreach ($completedVisits as $visit) {
            foreach ($visit->services as $service) {
                $serviceType = strtolower($service->serv_type);
                $details = $service->serv_name;
                
                if ($serviceType === 'vaccination') {
                    $vaccination = DB::table('tbl_vaccination_record')
                        ->where('visit_id', $visit->visit_id)
                        ->where('pet_id', $petId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($vaccination && isset($vaccination->vaccine_name)) {
                        $details .= ' - ' . $vaccination->vaccine_name;
                    }
                } elseif ($serviceType === 'deworming') {
                    $deworming = DB::table('tbl_deworming_record')
                        ->where('visit_id', $visit->visit_id)
                        ->where('pet_id', $petId)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($deworming && isset($deworming->dewormer_name)) {
                        $details .= ' - ' . $deworming->dewormer_name;
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
                
                $medicalHistoryItems[] = Carbon::parse($visit->visit_date)->format('M d, Y') . ': ' . $details;
            }
        }

        // Get diagnostic tests from completed services
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

        $diagnosticItems = [];
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
                
                $diagnosticItems[] = Carbon::parse($visit->visit_date)->format('M d, Y') . ': ' . $details;
            }
        }

        // Get prescriptions - medication from tbl_prescription
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

        $medicationItems = [];
        foreach ($prescriptions as $prescription) {
            if ($prescription->medication) {
                $dateFormatted = Carbon::parse($prescription->prescription_date)->format('M d, Y') . ': ';
                
                // Try to decode JSON medication data
                $medications = json_decode($prescription->medication, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($medications)) {
                    // JSON format - extract product names and instructions
                    $medList = [];
                    foreach ($medications as $med) {
                        $medStr = $med['product_name'] ?? '';
                        if (!empty($med['instructions'])) {
                            $medStr .= ' (' . $med['instructions'] . ')';
                        }
                        if ($medStr) {
                            $medList[] = $medStr;
                        }
                    }
                    if (!empty($medList)) {
                        $medicationItems[] = $dateFormatted . implode(', ', $medList);
                    }
                } else {
                    // Plain text format
                    $medicationItems[] = $dateFormatted . $prescription->medication;
                }
            }
        }

        // Set the attributes
        $referral->medical_history = implode('; ', $medicalHistoryItems);
        $referral->tests_conducted = implode('; ', $diagnosticItems);
        $referral->medications_given = implode('; ', $medicationItems);
        
        // Set display name for referred to (external_clinic_name or branch name)
        $referral->ref_to_display = $referral->external_clinic_name ?? 
                                    ($referral->refToBranch?->branch_name ?? 'N/A');
    }
}