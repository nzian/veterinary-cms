<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\Referral;
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

        return view('care-continuity', compact(
            'appointments',
            'prescriptions',
            'referrals',
            'activeTab',
            'filteredOwners',
            'filteredPets',
            'allBranches',
            'careContinuityModals',
            
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
            'appoint_status' => 'required|in:pending,arrived,completed,cancelled',
            'appoint_description' => 'nullable|string',
        ]);

        $appointment->update($validated);

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
}