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
use App\Services\NotificationService;
use App\Services\InventoryService;

class MedicalManagementController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display the unified medical management interface
     */
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10);
        $activeTab = $request->get('active_tab', 'appointments');
        $activeBranchId = session('active_branch_id');
        $user = auth()->user();
        
        // Non-super admins can only see their branch
        if ($user->user_role !== 'superadmin') {
            $activeBranchId = $user->branch_id;
        }

        // Get all user IDs from the same branch
        $branchUserIds = \App\Models\User::where('branch_id', $activeBranchId)
            ->pluck('user_id')
            ->toArray();

        // Filter appointments by branch through user relationship
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

        // Filter prescriptions by branch
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

        // Filter referrals - show referrals TO or FROM this branch
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

        // Get filtered owners and pets for dropdowns
        $filteredOwners = Owner::whereIn('user_id', $branchUserIds)->get();
        $filteredPets = Pet::whereIn('user_id', $branchUserIds)->get();

        return view('medicalManagement', compact(
            'appointments', 
            'prescriptions', 
            'referrals',
            'activeTab',
            'filteredOwners',
            'filteredPets'
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

        // ===== RETURN RESPONSE =====
        // Handle AJAX requests from dashboard
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