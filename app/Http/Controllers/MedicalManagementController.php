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
use App\Models\Product; // Ensure this model exists
use App\Models\ServiceProduct; // Ensure this model exists
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Services\NotificationService;
use App\Services\InventoryService;

class MedicalManagementController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    // ==================== SERVICE SAVE HANDLERS ====================
    public function saveConsultation(Request $request, $visit)
    {
        $validated = $request->validate([
            'weight' => ['nullable','numeric'],
            'temperature' => ['nullable','numeric'],
            'heart_rate' => ['nullable','numeric'],
            'respiratory_rate' => ['nullable','numeric'], // legacy name
            'respiration_rate' => ['nullable','numeric'], // preferred name
            'physical_findings' => ['nullable','string'],
            'diagnosis' => ['required','string'],
            'prescriptions' => ['nullable','string'],
            'recommendations' => ['nullable','string'],
            'next_appointment' => ['nullable','date'],
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->has('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        // Persist details if a suitable column exists
        $rr = $request->input('respiration_rate');
        if ($rr === null) { $rr = $request->input('respiratory_rate'); }
        $payload = [
            'weight' => $request->input('weight'),
            'temperature' => $request->input('temperature'),
            'heart_rate' => $request->input('heart_rate'),
            'respiration_rate' => $rr,
            'physical_findings' => $request->input('physical_findings'),
            'diagnosis' => $request->input('diagnosis'),
            'prescriptions' => $request->input('prescriptions'),
            'recommendations' => $request->input('recommendations'),
            'next_appointment' => $request->input('next_appointment'),
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Consultation: '.json_encode($payload));
        }
        $visitModel->save();
        // Upsert into tbl_checkup_record (tables already exist)
        DB::table('tbl_checkup_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'weight' => $request->input('weight'),
                'temperature' => $request->input('temperature'),
                'heart_rate' => $request->input('heart_rate'),
                'respiration_rate' => $rr,
                'symptoms' => $request->input('physical_findings'),
                'findings' => $request->input('diagnosis'),
                'treatment_plan' => $request->input('recommendations'),
                'next_visit' => $request->input('next_appointment'),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Consultation saved');
    }

    public function saveVaccination(Request $request, $visit)
    {
        $validated = $request->validate([
            'vaccine' => ['required','string'],
            'dose' => ['nullable','string'],
            'batch_no' => ['nullable','string'],
            'expiry_date' => ['nullable','date'], // legacy
            'next_schedule' => ['nullable','date'], // legacy
            'date_administered' => ['nullable','date'], // new
            'next_due_date' => ['nullable','date'], // new
            'administered_by' => ['nullable','string'],
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->has('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        $dateAdmin = $request->input('date_administered') ?: $request->input('expiry_date');
        $nextDue = $request->input('next_due_date') ?: $request->input('next_schedule');
        $payload = [
            'vaccine' => $request->input('vaccine'),
            'dose' => $request->input('dose'),
            'batch_no' => $request->input('batch_no'),
            'date_administered' => $dateAdmin,
            'next_due_date' => $nextDue,
            'administered_by' => $request->input('administered_by'),
            'remarks' => $request->input('remarks'),
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Vaccination: '.json_encode($payload));
        }
        $visitModel->save();
        DB::table('tbl_vaccination_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'vaccine_name' => $request->input('vaccine'),
                'manufacturer' => $request->input('manufacturer'),
                'batch_no' => $request->input('batch_no'),
                'date_administered' => $dateAdmin ?: now()->toDateString(),
                'next_due_date' => $nextDue,
                'administered_by' => $request->input('administered_by'),
                'remarks' => $request->input('remarks'),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Vaccination recorded');
    }

    public function saveDeworming(Request $request, $visit)
    {
        $validated = $request->validate([
            'product' => ['required','string'],
            'dosage' => ['nullable','string'],
            'next_reminder' => ['nullable','date'],
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->has('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        $payload = [
            'product' => $request->input('product'),
            'dosage' => $request->input('dosage'),
            'next_reminder' => $request->input('next_reminder'),
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Deworming: '.json_encode($payload));
        }
        $visitModel->save();
        DB::table('tbl_deworming_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'dewormer_name' => $request->input('product'),
                'dosage' => $request->input('dosage'),
                'route' => null,
                'next_due_date' => $request->input('next_reminder'),
                'remarks' => null,
                'administered_by' => auth()->user()->user_name ?? null,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Deworming recorded');
    }

    public function saveGrooming(Request $request, $visit)
    {
        $validated = $request->validate([
            'grooming_type' => ['nullable','string'],
            'additional_services' => ['nullable','string'],
            'instructions' => ['nullable','string'],
            'workflow_status' => ['nullable','in:Waiting,In Grooming,Bathing,Drying,Finishing,Completed,Picked Up'],
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->filled('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        $payload = [
            'grooming_type' => $request->input('grooming_type'),
            'additional_services' => $request->input('additional_services'),
            'instructions' => $request->input('instructions'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'products_used' => $request->input('products_used'),
            'total_price' => $request->input('total_price'),
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Grooming: '.json_encode($payload));
        }
        $visitModel->save();
        DB::table('tbl_grooming_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'service_package' => $request->input('grooming_type'),
                'add_ons' => $request->input('additional_services'),
                'groomer_name' => auth()->user()->user_name ?? null,
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'status' => $request->input('workflow_status') ?: ($visitModel->workflow_status ?? null),
                'remarks' => $request->input('instructions'),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Grooming recorded');
    }

    public function saveBoarding(Request $request, $visit)
    {
        $validated = $request->validate([
            'checkin' => ['required','date'],
            'checkout' => ['nullable','date','after_or_equal:checkin'],
            'room' => ['nullable','string'],
            'care_instructions' => ['nullable','string'],
            'monitoring_notes' => ['nullable','string'],
            'billing_basis' => ['nullable','in:hour,day'],
            'rate' => ['nullable','numeric'],
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->has('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        $payload = [
            'checkin' => $request->input('checkin'),
            'checkout' => $request->input('checkout'),
            'room' => $request->input('room'),
            'care_instructions' => $request->input('care_instructions'),
            'monitoring_notes' => $request->input('monitoring_notes'),
            'billing_basis' => $request->input('billing_basis'),
            'rate' => $request->input('rate'),
            'total_days' => $request->input('total_days'),
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Boarding: '.json_encode($payload));
        }
        $visitModel->save();
        DB::table('tbl_boarding_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'check_in_date' => $request->input('checkin'),
                'check_out_date' => $request->input('checkout'),
                'room_no' => $request->input('room'),
                'feeding_schedule' => $request->input('care_instructions'),
                'medications' => null,
                'daily_notes' => $request->input('monitoring_notes'),
                'status' => $request->input('workflow_status') ?: ($visitModel->workflow_status ?? null),
                'handled_by' => auth()->user()->user_name ?? null,
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Boarding saved');
    }

    public function saveDiagnostic(Request $request, $visit)
    {
        $validated = $request->validate([
            'test_type' => ['required','string'],
            'interpretation' => ['nullable','string'], // legacy
            'results' => ['nullable','string'], // preferred name
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->has('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        $diagResults = $request->input('results') ?: $request->input('interpretation');
        $payload = [
            'test_type' => $request->input('test_type'),
            'results' => $diagResults,
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Diagnostic: '.json_encode($payload));
        }
        $visitModel->save();
        DB::table('tbl_diagnostic_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'test_type' => $request->input('test_type'),
                'sample_collected' => null,
                'collected_by' => auth()->user()->user_name ?? null,
                'results' => $diagResults,
                'remarks' => null,
                'date_completed' => now()->toDateString(),
                'status' => $request->input('workflow_status') ?: ($visitModel->workflow_status ?? null),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Diagnostic recorded');
    }

    public function saveSurgical(Request $request, $visit)
    {
        $validated = $request->validate([
            'checklist' => ['nullable','string'],
            'surgery_type' => ['required','string'],
            'start_time' => ['nullable','date'],
            'end_time' => ['nullable','date','after_or_equal:start_time'],
            'medications_used' => ['nullable','string'],
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->has('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        $payload = [
            'checklist' => $request->input('checklist'),
            'surgery_type' => $request->input('surgery_type'),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'medications_used' => $request->input('medications_used'),
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Surgical: '.json_encode($payload));
        }
        $visitModel->save();
        DB::table('tbl_surgical_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'procedure_name' => $request->input('surgery_type'),
                'date_of_surgery' => now()->toDateString(),
                'start_time' => $request->input('start_time'),
                'end_time' => $request->input('end_time'),
                'surgeon' => auth()->user()->user_name ?? null,
                'assistants' => null,
                'anesthesia_used' => null,
                'findings' => $request->input('checklist'),
                'post_op_instructions' => $request->input('medications_used'),
                'status' => $request->input('workflow_status') ?: ($visitModel->workflow_status ?? null),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Surgical record saved');
    }

    public function saveEmergency(Request $request, $visit)
    {
        $validated = $request->validate([
            'triage_notes' => ['nullable','string'],
            'procedures' => ['nullable','string'],
            'immediate_meds' => ['nullable','string'],
        ]);
        $visitModel = \App\Models\Visit::findOrFail($visit);
        if ($request->has('workflow_status')) {
            $visitModel->workflow_status = $request->input('workflow_status');
        }
        $payload = [
            'triage_notes' => $request->input('triage_notes'),
            'procedures' => $request->input('procedures'),
            'immediate_meds' => $request->input('immediate_meds'),
            'service_items' => (function($json){ $arr = json_decode($json, true); return is_array($arr) ? $arr : []; })($request->input('service_items_json')),
        ];
        if (Schema::hasColumn('tbl_visit_record', 'details_json')) {
            $visitModel->details_json = json_encode($payload);
        } elseif (Schema::hasColumn('tbl_visit_record', 'notes')) {
            $visitModel->notes = trim(($visitModel->notes ? ($visitModel->notes."\n") : '') . 'Emergency: '.json_encode($payload));
        }
        $visitModel->save();
        DB::table('tbl_emergency_record')->updateOrInsert(
            ['visit_id' => $visitModel->visit_id, 'pet_id' => $visitModel->pet_id],
            [
                'case_type' => null,
                'arrival_condition' => null,
                'vital_signs' => null,
                'immediate_treatment' => $request->input('procedures'),
                'medications_administered' => $request->input('immediate_meds'),
                'outcome' => null,
                'status' => $request->input('workflow_status') ?: ($visitModel->workflow_status ?? null),
                'attended_by' => auth()->user()->user_name ?? null,
                'remarks' => $request->input('triage_notes'),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );
        return redirect()->route('medical.visits.perform', $visit)->with('success', 'Emergency record saved');
    }

    // 🎯 FIXED: Define the explicit list of vaccination service names
    const VACCINATION_SERVICE_NAMES = [
        'Vaccination',
        'Vaccination - Kennel Cough',
        'Vaccination - Kennel Cough (one dose)',
        'Vaccination - Anti Rabies',
    ];

    /**
     * Display the unified medical management interface
     */
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $activeTab = $request->get('active_tab', 'appointments');
        $activeBranchId = session('active_branch_id');
        $user = auth()->user();
        
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        $branchUserIds = \App\Models\User::where('branch_id', $activeBranchId)->pluck('user_id')->toArray();

        // --- Appointments Query ---
        $appointmentsQuery = Appointment::with(['pet.owner', 'services', 'user'])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->orderBy('appoint_date', 'desc')
            ->orderBy('appoint_time', 'desc');

        if ($perPage === 'all') {
            $appointments = $appointmentsQuery->get();
        } else {
            $appointments = $appointmentsQuery->paginate((int) $perPage);
        }

        // --- Prescriptions Query ---
        $prescriptionPerPage = $request->get('prescriptionPerPage', 10);
        $prescriptionsQuery = Prescription::with(['pet.owner', 'branch', 'user'])
            ->where('branch_id', $activeBranchId)
            ->orderBy('prescription_date', 'desc')
            ->orderBy('prescription_id', 'desc');

        if ($prescriptionPerPage === 'all') {
            $prescriptions = $prescriptionsQuery->get();
        } else {
            $prescriptions = $prescriptionsQuery->paginate((int) $prescriptionPerPage);
        }

        // --- Referrals Query ---
        $referralPerPage = $request->get('referralPerPage', 10);
        $referralsQuery = Referral::with([
            'appointment.pet.owner',
            'refToBranch',
            'refByBranch'
        ])->where(function($q) use ($activeBranchId) {
            $q->where('ref_to', $activeBranchId)
              ->orWhere('ref_by', $activeBranchId);
        })
        ->orderBy('ref_date', 'desc')
        ->orderBy('ref_id', 'desc');

        if ($referralPerPage === 'all') {
            $referrals = $referralsQuery->get();
        } else {
            $referrals = $referralsQuery->paginate((int) $referralPerPage);
        }

        $filteredOwners = Owner::whereIn('user_id', $branchUserIds)->get();
        $filteredPets = Pet::whereIn('user_id', $branchUserIds)->with('owner')->get();

        // --- Visits Query ---
        $visitPerPage = $request->get('visitPerPage', 10);
        $visitsQuery = \App\Models\Visit::with(['pet.owner', 'user', 'services'])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            ->orderBy('visit_date', 'desc')
            ->orderBy('visit_id', 'desc');

        if ($visitPerPage === 'all') {
            $visits = $visitsQuery->get();
        } else {
            $visits = $visitsQuery->paginate((int) $visitPerPage);
        }

        // ===== VACCINATION APPOINTMENTS QUERY (FIXED) =====
        $vaccinationPerPage = $request->get('vaccinationPerPage', 10);
        
        // 1. Standardize the service names for a case-insensitive check
        $lowerCaseNames = array_map('strtolower', self::VACCINATION_SERVICE_NAMES);

        // 2. Retrieve Service IDs by converting the database column to lowercase
        $vaccinationServiceIds = \App\Models\Service::whereIn(DB::raw('LOWER(serv_name)'), $lowerCaseNames)
            ->pluck('serv_id');

        // Optional: Log the IDs found (Check storage/logs/laravel.log)
        Log::info('Vaccination Service IDs Found:', $vaccinationServiceIds->toArray());


        $vaccinationAppointmentsQuery = Appointment::with([
            'pet.owner', 
            // Load services and pivot data needed by the view
            'services' => function ($query) {
                $query->withPivot('prod_id', 'vacc_next_dose', 'vacc_batch_no', 'vacc_notes');
            },
            'user'
        ])
            ->whereHas('user', function($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            })
            // Filter appointments that have ANY of the listed Vaccination Services attached
            ->whereHas('services', function ($q) use ($vaccinationServiceIds) {
                $q->whereIn('tbl_appoint_serv.serv_id', $vaccinationServiceIds);
            })
            ->orderBy('appoint_date', 'desc')
            ->orderBy('appoint_id', 'desc');


        if ($vaccinationPerPage === 'all') {
            $vaccinationAppointments = $vaccinationAppointmentsQuery->get();
        } else {
            $vaccinationAppointments = $vaccinationAppointmentsQuery->paginate((int) $vaccinationPerPage, ['*'], 'vaccinationsPage');
        }

        // Services list for per-pet selection in Visits modal (use real services)
        $serviceTypes = Service::orderBy('serv_name')->get(['serv_id','serv_name','serv_type']);

        // --- Per-service-type Visits datasets (8 tabs) ---
        $vaccinationVisitsPerPage = $request->get('vaccinationVisitsPerPage', 10);
        $groomingVisitsPerPage = $request->get('groomingVisitsPerPage', 10);
        $boardingVisitsPerPage = $request->get('boardingVisitsPerPage', 10);
        $consultationVisitsPerPage = $request->get('consultationVisitsPerPage', 10); // check-up/consultation
        $dewormingVisitsPerPage = $request->get('dewormingVisitsPerPage', 10);
        $diagnosticsVisitsPerPage = $request->get('diagnosticsVisitsPerPage', 10);
        $surgicalVisitsPerPage = $request->get('surgicalVisitsPerPage', 10);
        $emergencyVisitsPerPage = $request->get('emergencyVisitsPerPage', 10);

        $base = function() use ($activeBranchId) {
            return Visit::with(['pet.owner', 'user', 'services'])
                ->whereHas('user', function($q) use ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId);
                });
        };

        $vaccinationVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->whereRaw('LOWER(serv_type) = ?', ['vaccination']);
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        $groomingVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->whereRaw('LOWER(serv_type) = ?', ['grooming']);
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        $boardingVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->whereRaw('LOWER(serv_type) = ?', ['boarding']);
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        // Consultation / Check-up variants
        $consultationVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->where(function($q){
                $q->whereRaw('LOWER(serv_type) = ?', ['check up'])
                  ->orWhereRaw('LOWER(serv_type) = ?', ['check-up'])
                  ->orWhereRaw('LOWER(serv_type) = ?', ['checkup'])
                  ->orWhereRaw('LOWER(serv_type) = ?', ['consultation']);
            });
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        $dewormingVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->whereRaw('LOWER(serv_type) = ?', ['deworming']);
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        $diagnosticsVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->where(function($q){
                $q->whereRaw('LOWER(serv_type) = ?', ['diagnostics'])
                  ->orWhereRaw('LOWER(serv_type) = ?', ['diagnostic'])
                  ->orWhereRaw('LOWER(serv_type) = ?', ['laboratory']);
            });
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        $surgicalVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->where(function($q){
                $q->whereRaw('LOWER(serv_type) = ?', ['surgical'])
                  ->orWhereRaw('LOWER(serv_type) = ?', ['surgery']);
            });
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        $emergencyVisitsQuery = $base()->whereHas('services', function($sq) {
            $sq->whereRaw('LOWER(serv_type) = ?', ['emergency']);
        })->orderBy('visit_date', 'desc')->orderBy('visit_id', 'desc');

        $vaccinationVisits = $vaccinationVisitsPerPage === 'all' ? $vaccinationVisitsQuery->get() : $vaccinationVisitsQuery->paginate((int) $vaccinationVisitsPerPage, ['*'], 'vaccinationVisitsPage');
        $groomingVisits = $groomingVisitsPerPage === 'all' ? $groomingVisitsQuery->get() : $groomingVisitsQuery->paginate((int) $groomingVisitsPerPage, ['*'], 'groomingVisitsPage');
        $boardingVisits = $boardingVisitsPerPage === 'all' ? $boardingVisitsQuery->get() : $boardingVisitsQuery->paginate((int) $boardingVisitsPerPage, ['*'], 'boardingVisitsPage');
        $consultationVisits = $consultationVisitsPerPage === 'all' ? $consultationVisitsQuery->get() : $consultationVisitsQuery->paginate((int) $consultationVisitsPerPage, ['*'], 'consultationVisitsPage');
        $dewormingVisits = $dewormingVisitsPerPage === 'all' ? $dewormingVisitsQuery->get() : $dewormingVisitsQuery->paginate((int) $dewormingVisitsPerPage, ['*'], 'dewormingVisitsPage');
        $diagnosticsVisits = $diagnosticsVisitsPerPage === 'all' ? $diagnosticsVisitsQuery->get() : $diagnosticsVisitsQuery->paginate((int) $diagnosticsVisitsPerPage, ['*'], 'diagnosticsVisitsPage');
        $surgicalVisits = $surgicalVisitsPerPage === 'all' ? $surgicalVisitsQuery->get() : $surgicalVisitsQuery->paginate((int) $surgicalVisitsPerPage, ['*'], 'surgicalVisitsPage');
        $emergencyVisits = $emergencyVisitsPerPage === 'all' ? $emergencyVisitsQuery->get() : $emergencyVisitsQuery->paginate((int) $emergencyVisitsPerPage, ['*'], 'emergencyVisitsPage');

        return view('medicalManagement', compact(
            'appointments', 
            'prescriptions', 
            'referrals',
            'vaccinationAppointments',
            'activeTab',
            'filteredOwners',
            'filteredPets',
            'visits',
            'serviceTypes',
            'vaccinationVisits',
            'groomingVisits',
            'boardingVisits',
            'consultationVisits',
            'dewormingVisits',
            'diagnosticsVisits',
            'surgicalVisits',
            'emergencyVisits'
        ));
    }

    /**
     * Record vaccination details for an appointment (Logic remains the same)
     */
    public function recordVaccineDetails(Request $request, $appointmentId)
    {
        $appointment = Appointment::select('appoint_date')->findOrFail($appointmentId);
        
        $request->merge([
            'appoint_id' => $appointmentId,
            'appointment_date_reference' => $appointment->appoint_date,
             'vet_user_id' => auth()->id(),
        ]);

        $validated = $request->validate([
            'appoint_id' => 'required|exists:tbl_appoint,appoint_id', 
            'active_tab' => 'required|string', 
             'vet_user_id' => 'required|exists:tbl_user,user_id', 
            'service_id' => 'required|exists:tbl_serv,serv_id',
            'prod_id' => 'required|exists:tbl_prod,prod_id',
            'vacc_next_dose' => 'nullable|date|after_or_equal:appointment_date_reference', 
            'vacc_batch_no' => 'nullable|string|max:255',
            'vacc_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            $fullAppointment = Appointment::findOrFail($appointmentId);
            $serviceId = $validated['service_id'];

            $isAttached = $fullAppointment->services()->where('tbl_appoint_serv.serv_id', $serviceId)->exists();
            if (!$isAttached) {
                DB::rollBack();
                return redirect()->back()->with('error', 'The selected service is not linked to this appointment.');
            }

            $fullAppointment->services()->updateExistingPivot($serviceId, [
                'prod_id' => $validated['prod_id'],
                'vacc_next_dose' => $validated['vacc_next_dose'],
                'vacc_batch_no' => $validated['vacc_batch_no'],
                'vacc_notes' => $validated['vacc_notes'],
            ]);
            
            if ($fullAppointment->appoint_status !== 'completed') {
                $fullAppointment->update(['appoint_status' => 'completed']);
            }

            $this->inventoryService->deductSpecificProduct($validated['prod_id'], 1, $appointmentId, $serviceId);
            
            DB::commit();

            return redirect()->route('medical.index', ['active_tab' => 'vaccinations'])
                            ->with('success', 'Vaccination details recorded successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to record vaccine details for Appt {$appointmentId}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Database Error: Failed to save vaccination details. Check logs for details.');
        }
    }

    public function getServiceProductsForVaccination($serviceId)
    {
        try {
            // FIX: Ensure you are using the correct model (ServiceProduct)
            $serviceProducts = \App\Models\ServiceProduct::where('serv_id', $serviceId)
                ->with('product') 
                ->get()
                ->map(function($sp) {
                    $productName = $sp->product->prod_name ?? 'Product Missing/Deleted';
                    $currentStock = $sp->product->prod_stocks ?? 0;
                    
                    if (!$sp->product) {
                         Log::warning("Missing product link for ServiceProduct ID {$sp->id} (prod_id: {$sp->prod_id}).");
                    }
                    
                    return [
                        'prod_id' => $sp->prod_id,
                        'product_name' => $productName,
                        'current_stock' => $currentStock,
                    ];
                });

            return response()->json([
                'success' => true,
                'products' => $serviceProducts
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching service products (AJAX) for service ID {$serviceId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Database/Model Error: Could not load product links. Check server logs.'
            ], 500);
        }
    }
    // ... (All other methods remain the same)

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

        // Initialize history
        $history = [];
        $history[] = [
            'change_type' => 'created',
            'old_data' => null,
            'new_data' => [
                'date' => $appointment->appoint_date,
                'time' => $appointment->appoint_time,
                'status' => $appointment->appoint_status,
                'type' => $appointment->appoint_type,
            ],
            'notes' => 'Appointment created',
            'changed_by' => auth()->check() ? auth()->user()->user_name : 'System',
            'changed_at' => now()->toDateTimeString(),
        ];
        
        $appointment->change_history = $history;
        $appointment->save();
        
        Log::info("History initialized for new appointment {$appointment->appoint_id}");

        // Sync services
        if (!empty($services)) {
            $appointment->services()->sync($services);
            
            // Refresh to get services relationship
            $appointment->refresh();
            
            // If appointment is created with 'completed' or 'arrived' status, deduct inventory immediately
            if (in_array(strtolower($appointment->appoint_status), ['completed', 'arrived'])) {
                try {
                    Log::info("🆕 New appointment {$appointment->appoint_id} created with status '{$appointment->appoint_status}' - deducting inventory");
                    
                    // Check product availability
                    $serviceIds = $appointment->services->pluck('serv_id')->toArray();
                    
                    if (!empty($serviceIds)) {
                        $unavailable = $this->inventoryService->checkServiceProductAvailability($serviceIds);
                        
                        if (!empty($unavailable)) {
                            $warnings = [];
                            foreach ($unavailable as $item) {
                                $warnings[] = "{$item['product']} for {$item['service']}: Need {$item['required']}, Available {$item['available']}";
                            }
                            Log::warning("⚠️ Low stock for new appointment {$appointment->appoint_id}: " . implode('; ', $warnings));
                        }
                        
                        // Deduct products from inventory
                        $deductionResult = $this->inventoryService->deductServiceProducts($appointment);
                        
                        if ($deductionResult) {
                            Log::info("✅ Inventory deducted successfully for new appointment {$appointment->appoint_id}");
                        } else {
                            Log::error("❌ Inventory deduction failed for new appointment {$appointment->appoint_id}");
                        }
                    }
                    
                    // Generate billing
                    $this->generateBillingForAppointment($appointment);
                    
                } catch (\Exception $e) {
                    Log::error("❌ Error processing new appointment {$appointment->appoint_id}: " . $e->getMessage());
                    Log::error("Stack trace: " . $e->getTraceAsString());
                }
            }
        }
        
        // Send SMS for new appointments
        try {
            $appointment->load('pet.owner');
            $smsService = new \App\Services\DynamicSMSService();
            $smsResult = $smsService->sendNewAppointmentSMS($appointment);
            
            if ($smsResult) {
                Log::info("New appointment SMS sent successfully for appointment {$appointment->appoint_id}");
            } else {
                Log::warning("New appointment SMS failed to send for appointment {$appointment->appoint_id}");
            }
        } catch (\Exception $e) {
            Log::error("SMS notification failed for appointment {$appointment->appoint_id}: " . $e->getMessage());
        }
        
        $activeTab = $request->input('active_tab', 'appointments');
        
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
                       ->with('success', 'Appointment added successfully');
    }

    public function showAppointment($id)
    {
        try {
            $appointment = Appointment::with([
                'pet.owner', 
                'services',
                'user.branch'
            ])->findOrFail($id);
            
            return response()->json([
                'appointment' => $appointment,
                'history' => $appointment->change_history ?? [],
                'veterinarian' => [
                    'name' => $appointment->user->user_name ?? 'N/A',
                    'license' => $appointment->user->user_license ?? 'N/A'
                ],
                'branch' => [
                    'name' => $appointment->user->branch->branch_name ?? 'N/A',
                    'address' => $appointment->user->branch->branch_address ?? 'N/A',
                    'contact' => $appointment->user->branch->branch_contactNum ?? 'N/A'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Appointment not found',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update an existing appointment
     */
    public function updateAppointment(Request $request, Appointment $appointment)
    {
        Log::info("🔄 DEBUG: updateAppointment called for appointment {$appointment->appoint_id}");
        
        $validated = $request->validate([
            'appoint_date' => 'required|date',
            'appoint_time' => 'required',
            'appoint_status' => 'required|in:pending,arrived,completed,refer,rescheduled',
            'appoint_type' => 'required|string',
            'pet_id' => 'required|integer|exists:tbl_pet,pet_id',
            'appoint_description' => 'nullable|string',
            'services' => 'array',
            'services.*' => 'exists:tbl_serv,serv_id',
        ]);

        // ===== TRACK CHANGES BEFORE UPDATE =====
        $changeType = 'updated';
        $old = [];
        $new = [];
        
        // Store OLD status BEFORE any updates
        $oldStatus = $appointment->appoint_status;
        
        Log::info("🔍 DEBUG: Old status: {$oldStatus}, New status: {$validated['appoint_status']}");
        
        if ($appointment->appoint_date !== $validated['appoint_date']) {
            $changeType = 'rescheduled';
            $old['date'] = $appointment->appoint_date;
            $new['date'] = $validated['appoint_date'];
        }
        
        if ($appointment->appoint_time !== $validated['appoint_time']) {
            $changeType = 'rescheduled';
            $old['time'] = $appointment->appoint_time;
            $new['time'] = $validated['appoint_time'];
        }
        
        if ($appointment->appoint_status !== $validated['appoint_status']) {
            if ($changeType !== 'rescheduled') {
                $changeType = 'status_changed';
            }
            $old['status'] = $appointment->appoint_status;
            $new['status'] = $validated['appoint_status'];
        }
        
        if ($appointment->appoint_type !== $validated['appoint_type']) {
            $old['type'] = $appointment->appoint_type;
            $new['type'] = $validated['appoint_type'];
        }

        // Check if rescheduled (for SMS)
        $dateChanged = $appointment->appoint_date !== $validated['appoint_date'];
        $timeChanged = $appointment->appoint_time !== $validated['appoint_time'];
        $isRescheduled = $dateChanged || $timeChanged;
        $originalDate = $appointment->appoint_date;
        $originalTime = $appointment->appoint_time;

        // ===== UPDATE THE APPOINTMENT =====
        $appointment->update($validated);

        // ===== SAVE HISTORY =====
        if (!empty($old) || !empty($new)) {
            $history = $appointment->change_history ?? [];
            
            $history[] = [
                'change_type' => $changeType,
                'old_data' => $old,
                'new_data' => $new,
                'notes' => 'Appointment updated',
                'changed_by' => auth()->check() ? auth()->user()->user_name : 'System',
                'changed_at' => now()->toDateTimeString(),
            ];
            
            $appointment->change_history = $history;
            $appointment->save();
            
            Log::info("History tracked for appointment {$appointment->appoint_id}", [
                'change_type' => $changeType,
                'old_data' => $old,
                'new_data' => $new
            ]);
        }

        // ===== SYNC SERVICES =====
        if ($request->has('services')) {
            $appointment->services()->sync($request->services);
        } else {
            $appointment->services()->sync([]);
        }

        // Refresh appointment data to get services relationship
        $appointment->refresh();
        
        // Track what changed
        $statusChanged = $oldStatus !== $validated['appoint_status'];
        $newStatus = $validated['appoint_status'];

        Log::info("📊 Status tracking - Changed: " . ($statusChanged ? 'YES' : 'NO') . ", Old: {$oldStatus}, New: {$newStatus}");

        // ⭐ INVENTORY DEDUCTION: When appointment status changes to completed or arrived
        if ($statusChanged && in_array($newStatus, ['arrived', 'completed'])) {
            // Only deduct if status JUST changed (wasn't already arrived/completed)
            if (!in_array($oldStatus, ['arrived', 'completed'])) {
                try {
                    Log::info("🔄 Starting inventory deduction process for appointment {$appointment->appoint_id}");
                    
                    // Get service IDs
                    $serviceIds = $appointment->services->pluck('serv_id')->toArray();
                    
                    Log::info("📋 Services in appointment: " . json_encode($serviceIds));
                    
                    if (!empty($serviceIds)) {
                        // Check product availability first
                        $unavailable = $this->inventoryService->checkServiceProductAvailability($serviceIds);
                        
                        if (!empty($unavailable)) {
                            $warnings = [];
                            foreach ($unavailable as $item) {
                                $warnings[] = "{$item['product']} for {$item['service']}: Need {$item['required']}, Available {$item['available']}";
                            }
                            Log::warning("⚠️ Low stock for appointment {$appointment->appoint_id}: " . implode('; ', $warnings));
                        }
                        
                        // ✅ DEDUCT PRODUCTS FROM INVENTORY
                        Log::info("🔧 Calling deductServiceProducts...");
                        $deductionResult = $this->inventoryService->deductServiceProducts($appointment);
                        
                        if ($deductionResult) {
                            Log::info("✅ Inventory deducted successfully for appointment {$appointment->appoint_id}");
                        } else {
                            Log::error("❌ Inventory deduction returned false for appointment {$appointment->appoint_id}");
                        }
                    } else {
                        Log::info("ℹ️ No services with products for appointment {$appointment->appoint_id}");
                    }
                    
                    // Generate billing
                    $this->generateBillingForAppointment($appointment);
                    
                } catch (\Exception $e) {
                    Log::error("❌ Inventory deduction failed for appointment {$appointment->appoint_id}: " . $e->getMessage());
                    Log::error("Stack trace: " . $e->getTraceAsString());
                    // Continue with appointment update even if inventory fails
                }
            } else {
                Log::info("ℹ️ Skipping inventory deduction - appointment {$appointment->appoint_id} was already {$oldStatus}");
            }
        } else {
            Log::info("ℹ️ No inventory deduction needed. Status changed: " . ($statusChanged ? 'YES' : 'NO') . ", New status: {$newStatus}");
        }

        // ===== SMS NOTIFICATIONS =====
        $successMessage = 'Appointment updated successfully';
        
        try {
            // Load relationships - CRITICAL for SMS
            $appointment->load(['pet.owner']);
            
            // Verify we have necessary data
            if (!$appointment->pet || !$appointment->pet->owner) {
                Log::error("Cannot send SMS for appointment {$appointment->appoint_id}: Missing pet or owner relationship");
            } else {
                $smsService = new \App\Services\DynamicSMSService();
                $source = $request->expectsJson() ? 'Dashboard' : 'Medical Management';
                
                // PRIORITY 1: Completion SMS (highest priority - skip all others)
                if ($statusChanged && $newStatus === 'completed' && $oldStatus !== 'completed') {
                    $smsResult = $smsService->sendCompletionSMS($appointment);
                    if ($smsResult) {
                        Log::info("✅ Completion SMS sent for appointment {$appointment->appoint_id} from {$source}");
                        $successMessage = 'Appointment completed and notification sent';
                    } else {
                        Log::warning("⚠️ Completion SMS failed for appointment {$appointment->appoint_id}");
                    }
                }
                // PRIORITY 2: Reschedule SMS (only if NOT completing)
                elseif ($isRescheduled && $newStatus !== 'completed') {
                    $smsResult = $smsService->sendRescheduleSMS($appointment);
                    if ($smsResult) {
                        Log::info("✅ Reschedule SMS sent for appointment {$appointment->appoint_id} from {$source}", [
                            'owner' => $appointment->pet->owner->own_name,
                            'pet' => $appointment->pet->pet_name,
                            'old_date' => $originalDate,
                            'old_time' => $originalTime,
                            'new_date' => $validated['appoint_date'],
                            'new_time' => $validated['appoint_time']
                        ]);
                        $successMessage = 'Appointment rescheduled and notification sent';
                    } else {
                        Log::warning("⚠️ Reschedule SMS failed for appointment {$appointment->appoint_id}");
                    }
                }
                
                // In-app notification for arrival (no SMS, just internal notification)
                if ($statusChanged && $newStatus === 'arrived' && $oldStatus !== 'arrived') {
                    $notificationService = new NotificationService();
                    $notificationService->notifyAppointmentArrived($appointment);
                    Log::info("📢 Arrival notification sent for appointment {$appointment->appoint_id}");
                }
            }
            
        } catch (\Exception $e) {
            Log::error("❌ SMS/Notification failed for appointment {$appointment->appoint_id}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
        }

    if ($request->expectsJson() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
    return response()->json([
        'success' => true,
        'message' => $successMessage,
                'appointment' => [
                    'id' => $appointment->appoint_id,
                    'pet_id' => $appointment->pet_id,
                    'pet_name' => $appointment->pet->pet_name ?? 'Unknown Pet',
                    'owner_name' => $appointment->pet->owner->own_name ?? 'Unknown Owner',
                    'date' => $appointment->appoint_date,
                    'time' => $appointment->appoint_time,
                    'status' => strtolower($appointment->appoint_status),
                    'notes' => $appointment->appoint_description ?? '',
                    'type' => $appointment->appoint_type,
                ]
            ]);
        }

        if ($statusChanged && in_array($newStatus, ['arrived', 'completed']) && !in_array($oldStatus, ['arrived', 'completed'])) {
    // Redirect to the Inventory page's products tab so it loads fresh stock data
    return redirect()->route('prodServEquip.index', ['tab' => 'products']) 
                     ->with('success', 'Appointment finalized, inventory deducted, and status updated.');
}

        // Regular form submission redirect
        $activeTab = $request->input('active_tab', 'appointments');
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

    // ==================== VISIT METHODS ====================
    public function storeVisit(Request $request)
    {
        $validated = $request->validate([
            'visit_date' => 'required|date',
            'pet_ids' => 'required|array|min:1',
            'pet_ids.*' => 'exists:tbl_pet,pet_id',
            'weight' => 'nullable',
            'temperature' => 'nullable',
            'service_type' => 'nullable', // now array of arrays
            'patient_type' => 'required|string|max:100',
        ]);

        $userId = auth()->id() ?? $request->input('user_id');

        DB::transaction(function () use ($validated, $userId, $request) {
            foreach ($validated['pet_ids'] as $petId) {
                $data = [
                    'visit_date' => $validated['visit_date'],
                    'pet_id' => $petId,
                    'user_id' => $userId,
                    'weight' => $request->input("weight.$petId") ?? null,
                    'temperature' => $request->input("temperature.$petId") ?? null,
                    'patient_type' => $validated['patient_type'],
                    // 'service_type' => null, // no longer used for multi
                ];

                if (Schema::hasColumn('tbl_visit_record', 'visit_status')) {
                    $data['visit_status'] = 'arrived';
                }

                $visit = Visit::create($data);

                // Attach selected services (array of IDs, serv_type strings, or names)
                $selected = $request->input("service_type.$petId", []);
                if (is_array($selected) && !empty($selected)) {
                    $serviceIds = [];
                    $selectedTypes = [];

                    // 1) Use numeric IDs directly
                    $numericIds = array_values(array_filter(array_map(function($v){
                        return is_numeric($v) ? (int)$v : null;
                    }, $selected)));
                    if (!empty($numericIds)) {
                        $serviceIds = array_merge($serviceIds, $numericIds);
                    }

                    // 2) Map serv_type strings to a representative service ID per type
                    $stringVals = array_values(array_filter($selected, function($v){ return !is_numeric($v); }));
                    if (!empty($stringVals)) {
                        $normalized = array_map(function($s){
                            $s = strtolower(trim($s));
                            if ($s === 'diagnostic') { $s = 'diagnostics'; }
                            if ($s === 'checkup') { $s = 'check up'; }
                            return $s;
                        }, $stringVals);

                        $foundTypeIds = [];
                        $foundTypes = [];
                        foreach (array_unique($normalized) as $type) {
                            // Try exact serv_type match first
                            $svc = \App\Models\Service::whereRaw('LOWER(serv_type) = ?', [$type])
                                ->orderBy('serv_id')
                                ->first(['serv_id','serv_type','serv_name']);
                            if (!$svc) {
                                // Try LIKE on serv_type and serv_name
                                $svc = \App\Models\Service::whereRaw('LOWER(serv_type) LIKE ?', ['%'.$type.'%'])
                                    ->orWhereRaw('LOWER(serv_name) LIKE ?', ['%'.$type.'%'])
                                    ->orderBy('serv_id')
                                    ->first(['serv_id','serv_type','serv_name']);
                            }
                            if ($svc) {
                                $foundTypeIds[] = (int)$svc->serv_id;
                                $foundTypes[] = strtolower($svc->serv_type);
                                continue;
                            }
                            // Last fallback: any service whose name contains the type token
                            $svc = \App\Models\Service::whereRaw('LOWER(serv_name) LIKE ?', ['%'.$type.'%'])
                                ->orderBy('serv_id')
                                ->first(['serv_id','serv_type','serv_name']);
                            if ($svc) {
                                $foundTypeIds[] = (int)$svc->serv_id;
                                $foundTypes[] = strtolower($svc->serv_type);
                            }
                        }
                        if (!empty($foundTypeIds)) {
                            $serviceIds = array_merge($serviceIds, $foundTypeIds);
                            $selectedTypes = array_merge($selectedTypes, $foundTypes);
                        }

                        // Fallback: map by service names if provided (original values)
                        $byName = \App\Models\Service::whereIn('serv_name', $stringVals)->pluck('serv_id')->toArray();
                        if (!empty($byName)) {
                            $serviceIds = array_merge($serviceIds, $byName);
                        }
                    }

                    $serviceIds = array_values(array_unique($serviceIds));
                    if (!empty($serviceIds)) {
                        $visit->services()->sync($serviceIds);
                    }

                    // Optionally persist types summary into visit record if a column exists
                    if (!empty($selectedTypes)) {
                        $typesSummary = implode(', ', array_values(array_unique($selectedTypes)));
                        $saved = false;
                        if (\Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
                            $visit->visit_service_type = $typesSummary;
                            $saved = true;
                        } elseif (\Schema::hasColumn('tbl_visit_record', 'service_type')) {
                            $visit->service_type = $typesSummary;
                            $saved = true;
                        } elseif (\Schema::hasColumn('tbl_visit_record', 'serv_type')) {
                            $visit->serv_type = $typesSummary;
                            $saved = true;
                        }
                        if ($saved) { $visit->save(); }
                    }
                }
            }
        });

        $activeTab = $request->input('active_tab', 'visits');
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
            ->with('success', 'Visit recorded successfully');
    }

    public function updateVisit(Request $request, Visit $visit)
    {
        $validated = $request->validate([
            'visit_date' => 'required|date',
            'pet_id' => 'required|exists:tbl_pet,pet_id',
            'weight' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'patient_type' => 'required|string|max:100',
        ]);

        $visit->update($validated);
        if (Schema::hasColumn('tbl_visit_record', 'visit_status') && $request->filled('visit_status')) {
            $visit->visit_status = $request->input('visit_status');
            $visit->save();
        }

        $activeTab = $request->input('active_tab', 'visits');
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
            ->with('success', 'Visit updated successfully');
    }

    public function destroyVisit(Request $request, $id)
    {
        $visit = Visit::findOrFail($id);
        $visit->delete();

        $activeTab = $request->input('active_tab', 'visits');
        return redirect()->route('medical.index', ['active_tab' => $activeTab])
            ->with('success', 'Visit deleted successfully');
    }

    public function showVisit($id)
    {
        $visit = Visit::with(['pet.owner', 'user'])->findOrFail($id);
        return response()->json($visit);
    }

    /**
     * Update or advance a visit's workflow_status. Accepts optional 'type' and 'to' (target status).
     * If 'to' is not provided, advances to the next stage using grooming-style stages.
     */
    public function updateWorkflowStatus(Request $request, $id)
    {
        try {
            $visit = Visit::findOrFail($id);

            $type = strtolower(trim($request->input('type', '')));
            $target = $request->input('to');

            // Per-service workflow maps (exact strings as requested)
            $map = [
                // aliases for diagnostics
                'diagnostic' => ['Waiting','Sample Collection','Testing','Results Encoding','Completed'],
                'diagnostics' => ['Waiting','Sample Collection','Testing','Results Encoding','Completed'],
                // grooming
                'grooming' => ['Waiting','In Grooming','Bathing','Drying','Finishing','Completed','Picked Up'],
                // boarding
                'boarding' => ['Reserved','Checked In','In Boarding','Ready for Pick-up','Checked Out'],
                // surgical
                'surgical' => ['Waiting','Pre-op','Surgery Ongoing','Recovery','Completed'],
                'surgery' => ['Waiting','Pre-op','Surgery Ongoing','Recovery','Completed'],
                // emergency
                'emergency' => ['Triage','Stabilization','Treatment','Observation','Completed'],
                // deworming
                'deworming' => ['Waiting','Deworming Ongoing','Observation','Completed'],
                // check-up
                'check-up' => ['Waiting','Consultation Ongoing','Completed'],
                'checkup' => ['Waiting','Consultation Ongoing','Completed'],
                'check up' => ['Waiting','Consultation Ongoing','Completed'],
                // vaccination
                'vaccination' => ['Waiting','Consultation','Vaccination Ongoing','Observation','Completed'],
            ];

            $stages = $map[$type] ?? $map['grooming'];

            // Default start status
            $defaultStart = ($type === 'boarding') ? 'Reserved' : 'Waiting';

            $current = $visit->workflow_status ?: $defaultStart;
            if ($target) {
                // Validate provided target
                if (!in_array($target, $stages, true)) {
                    return response()->json(['success' => false, 'error' => 'Invalid status'], 422);
                }
                $next = $target;
            } else {
                // Advance to next stage
                $idx = array_search($current, $stages, true);
                if ($idx === false) { $idx = -1; }
                $next = $stages[min($idx + 1, count($stages) - 1)];
            }

            $visit->workflow_status = $next;
            $visit->save();

            return response()->json(['success' => true, 'workflow_status' => $next]);
        } catch (\Exception $e) {
            \Log::error('updateWorkflowStatus error: '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update status'], 500);
        }
    }

    // ==================== VISIT WORKSPACE (PERFORM SERVICE) ====================
    public function performVisit(Request $request, $id)
    {
        $visit = Visit::with(['pet.owner', 'user'])->findOrFail($id);

        // Determine active workspace tab: prefer explicit 'type' from query, else from visit record
        $explicitType = $request->query('type');
        $serviceType = null;
        if ($explicitType) {
            $serviceType = $explicitType;
        } else {
            if (\Illuminate\Support\Facades\Schema::hasColumn('tbl_visit_record', 'visit_service_type')) {
                $serviceType = $visit->visit_service_type;
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('tbl_visit_record', 'service_type')) {
                $serviceType = $visit->service_type;
            }
        }

        // Map prodServEquip service types to blade names
        $map = [
            'preventive care' => 'preventive',
            'diagnostic services' => 'diagnostic',
            'surgical services' => 'surgical',
            'emergency & critical care' => 'emergency',
            'reproductive & breeding' => 'reproductive',
            'grooming & hygiene' => 'grooming',
            'wellness & nutrition' => 'wellness',
            'boarding & daycare' => 'boarding',
            'additional fees' => 'additional',
            'other' => 'other',
            // Fallbacks for common specific types if used
            'consultation' => 'consultation',
            'check-up' => 'consultation',
            'checkup' => 'consultation',
            'vaccination' => 'vaccination',
            'deworming' => 'deworming',
            'grooming' => 'grooming',
            'boarding' => 'boarding',
            'laboratory' => 'diagnostic',
            'diagnostic' => 'diagnostic',
            'surgery' => 'surgical',
            'surgical' => 'surgical',
            'emergency' => 'emergency',
        ];
        $key = $serviceType ? strtolower($serviceType) : null;
        // Normalize common variations
        if ($key === 'diagnostics') { $key = 'diagnostic'; }
        if ($key === 'checkup' || $key === 'check-up' || $key === 'check up') { $key = 'consultation'; }
        $blade = $map[$key] ?? 'consultation';

        // Load per-service record for prefill
        $tableByBlade = [
            'consultation' => 'tbl_checkup_record',
            'vaccination' => 'tbl_vaccination_record',
            'deworming' => 'tbl_deworming_record',
            'grooming' => 'tbl_grooming_record',
            'boarding' => 'tbl_boarding_record',
            'diagnostic' => 'tbl_diagnostic_record',
            'surgical' => 'tbl_surgical_record',
            'emergency' => 'tbl_emergency_record',
        ];
        $serviceData = null;
        if (isset($tableByBlade[$blade])) {
            try {
                $serviceData = DB::table($tableByBlade[$blade])->where('visit_id', $id)->first();
            } catch (\Throwable $th) {
                $serviceData = null;
            }
        }

        // Render specific blade per service type
        $viewName = 'visits.' . $blade;
        return view($viewName, compact('visit','serviceData'));
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
     * Get appointment for prescription
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
            Log::error('Error in getAppointmentForPrescription: ' . $e->getMessage());
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
        
        Log::info("💰 Billing generated for appointment {$appointment->appoint_id}");
    }

    // ==================== PRESCRIPTION METHODS ====================

    public function storePrescription(Request $request)
    {
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

            $user = auth()->user();

            $prescription = Prescription::create([
                'pet_id' => $request->pet_id,
                'prescription_date' => $request->prescription_date,
                'medication' => json_encode($medications),
                'differential_diagnosis' => $request->differential_diagnosis,
                'notes' => $request->notes,
                'branch_id' => $user->branch_id ?? 1,
                'user_id' => $user->user_id ?? null,
            ]);

            Log::info('Prescription saved by user_id: ' . ($user->user_id ?? 'N/A') . 
                       ' for branch_id: ' . ($user->branch_id ?? 'N/A') . 
                       ' with differential diagnosis: ' . $request->differential_diagnosis);

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
        $validated = $request->validate([
            'appointment_id' => 'required|exists:tbl_appoint,appoint_id',
            'ref_date' => 'required|date',
            'ref_to' => 'required|exists:tbl_branch,branch_id',
            'ref_description' => 'required|string',
        ]);

        try {
            $appointment = Appointment::with(['pet.owner'])->findOrFail($validated['appointment_id']);
            
            // Create referral in tbl_ref
            $referral = Referral::create([
                'appoint_id' => $validated['appointment_id'],
                'ref_date' => $validated['ref_date'],
                'ref_to' => $validated['ref_to'],
                'ref_description' => $validated['ref_description'],
                'ref_by' => auth()->id(),
            ]);
            
            // Load the referral relationships for SMS
            $referral->load(['refToBranch', 'refByBranch']);
            
            // Update appointment status
            $appointment->appoint_status = 'refer';
            $appointment->save();
            
            // Send SMS using DynamicSMSService
            $smsService = new \App\Services\DynamicSMSService();
            $smsSent = $smsService->sendReferralSMS($appointment, $referral);
            
            $activeTab = $request->input('active_tab', 'referrals');
            
            if ($smsSent) {
                return redirect()->route('medical.index', ['active_tab' => $activeTab])
                    ->with('success', 'Referral created successfully and SMS notification sent');
            } else {
                return redirect()->route('medical.index', ['active_tab' => $activeTab])
                    ->with('success', 'Referral created successfully (SMS notification failed)');
            }
                
        } catch (\Exception $e) {
            Log::error('Referral creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create referral')
                ->withInput();
        }
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
}