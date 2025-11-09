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

class CareContinuityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
            ->orderBy('appoint_date', 'desc')
            ->orderBy('appoint_time', 'desc');

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
        $referralsQuery = Referral::with(['appointment.pet.owner', 'refToBranch', 'refByBranch'])
            ->where(function($q) use ($activeBranchId) {
                $q->where('ref_to', $activeBranchId)
                  ->orWhere('ref_by', $activeBranchId);
            })
            ->orderBy('ref_date', 'desc')
            ->orderBy('ref_id', 'desc');

        $referrals = $referralPerPage === 'all' 
            ? $referralsQuery->get() 
            : $referralsQuery->paginate((int) $referralPerPage, ['*'], 'referralsPage');

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
        $validated['appoint_status'] = 'pending';

        Appointment::create($validated);

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

        // Rescheduling-only: update date/time and force status
        $appointment->appoint_date = $validated['appoint_date'];
        $appointment->appoint_time = $validated['appoint_time'];
        $appointment->appoint_status = 'rescheduled';
        $appointment->save();
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
            $referral = Referral::with(['appointment.pet.owner', 'refToBranch', 'refByBranch'])
                ->findOrFail($id);

            return response()->json([
                'ref_id' => $referral->ref_id,
                'ref_date' => Carbon::parse($referral->ref_date)->format('F d, Y'),
                'ref_description' => $referral->ref_description,
                'pet_name' => $referral->appointment->pet->pet_name,
                'owner_name' => $referral->appointment->pet->owner->own_name,
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
                'visit_status' => 'arrived',
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
}