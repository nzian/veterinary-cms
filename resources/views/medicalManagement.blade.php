@extends('AdminBoard')
@php
    $userRole = strtolower(auth()->user()->user_role ?? '');
    
    // Define permissions for each role
    $permissions = [
        'superadmin' => [
            // Appointments
            'view_appointments' => true,
            'add_appointment' => false,
            'edit_appointment' => false,
            'delete_appointment' => false,
            'prescribe_appointment' => false,
            'refer_appointment' => false,
            
            // Prescriptions
            'view_prescriptions' => true,
            'add_prescription' => false,
            'edit_prescription' => false,
            'delete_prescription' => true,
            'print_prescription' => true,
            
            // Referrals
            'view_referrals' => true,
            'add_referral' => false,
            'edit_referral' => false,
            'delete_referral' => false,
            'print_referral' => true,
        ],
        'veterinarian' => [
            // Appointments
            'view_appointments' => true,
            'add_appointment' => false,
            'edit_appointment' => true,
            'delete_appointment' => false,
            'prescribe_appointment' => true,
            'refer_appointment' => true,
            
            // Prescriptions - FULL ACCESS
            'view_prescriptions' => true,
            'add_prescription' => true,
            'edit_prescription' => true,
            'delete_prescription' => true,
            'print_prescription' => true,
            
            // Referrals - FULL ACCESS
            'view_referrals' => true,
            'add_referral' => true,
            'edit_referral' => true,
            'delete_referral' => true,
            'print_referral' => true,
        ],
        'receptionist' => [
            // Appointments
            'view_appointments' => true,
            'add_appointment' => true,
            'edit_appointment' => true,
            'delete_appointment' => true,
            'prescribe_appointment' => false,
            'refer_appointment' => false,
            
            // Prescriptions - VIEW AND PRINT ONLY
            'view_prescriptions' => true,
            'add_prescription' => false,
            'edit_prescription' => false,
            'delete_prescription' => false,
            'print_prescription' => true,
            
            // Referrals - VIEW AND PRINT ONLY
            'view_referrals' => true,
            'add_referral' => false,
            'edit_referral' => false,
            'delete_referral' => false,
            'print_referral' => true,
        ],
    ];
    
    // Get permissions for current user
    $can = $permissions[$userRole] ?? $permissions['receptionist'];
    
    // Helper function to check permission
    function hasPermission($permission, $can) {
        return $can[$permission] ?? false;
    }
@endphp

@section('content')
<div class="min-h-screen">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        
        {{-- Tab Navigation --}}
<div class="border-b border-gray-200 mb-6">
    <nav class="-mb-px flex space-x-8">
        <button onclick="showTab('appointments')" id="appointments-tab" 
            class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab', 'appointments') == 'appointments' ? 'active' : '' }}">
            <h2 class="font-bold text-xl">Appointments</h2>
        </button>
        <button onclick="showTab('prescriptions')" id="prescriptions-tab" 
            class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab') == 'prescriptions' ? 'active' : '' }}">
            <h2 class="font-bold text-xl">Prescriptions</h2>
        </button>
        <button onclick="showTab('referrals')" id="referrals-tab" 
            class="tab-button py-2 px-1 border-b-2 font-medium text-sm {{ request('tab') == 'referrals' ? 'active' : '' }}">
            <h2 class="font-bold text-xl">Referrals</h2>
        </button>
    </nav>
</div>

        

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ==================== APPOINTMENTS TAB ==================== -->
        <div id="appointmentsContent" class="tab-content">

            <!-- Show Entries Dropdown -->
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <form method="GET" action="{{ route('medical.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="active_tab" value="appointments">
                    <label for="perPage" class="text-sm text-black">Show</label>
                    <select name="perPage" id="perPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
               @if(auth()->check() && in_array(auth()->user()->user_role, [ 'receptionist']))
    <button onclick="openAddModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
        + Add Appointment
    </button>
@endif
            </div>
            <br>

            <!-- Appointments Table -->
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Type</th>
                            <th class="border px-4 py-2">Time</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Services</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Contact</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $index => $appointment)
                            <tr>
                                <td class="border px-2 py-2">{{ $appointments->firstItem() + $index }}</td>
                                <td class="border px-4 py-2">
                                    {{ \Carbon\Carbon::parse($appointment->appoint_date)->format('F j, Y') }}
                                </td>
                                <td class="border px-4 py-2">{{ $appointment->appoint_type }}</td>
                                <td class="border px-4 py-2">
                                    {{ \Carbon\Carbon::parse($appointment->appoint_time)->format('h:i A') }}
                                </td>
                                <td class="border px-4 py-2">{{ $appointment->pet?->pet_name ?? 'N/A' }}</td>
                                <td class="border px-2 py-1 text-left">
                                    @if($appointment->services->count())
                                        {{ $appointment->services->pluck('serv_name')->join(', ') }}
                                    @else
                                        <em>No Service Assigned</em>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $appointment->pet?->owner?->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $appointment->pet?->owner?->own_contactnum}}</td>
                                <td class="border px-4 py-2">
                                    <span class="px-2 py-1 rounded text-xs 
                                        {{ $appointment->appoint_status == 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($appointment->appoint_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($appointment->appoint_status == 'refer' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800')) }}">
                                        {{ ucfirst($appointment->appoint_status) }}
                                    </span>
                                </td>
                               <td class="border px-2 py-1">
    <div class="flex justify-center items-center gap-1">
         <button onclick="viewAppointment({{ $appointment->appoint_id }})"
            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs" title="view">
            <i class="fas fa-eye"></i> 
        </button>
        @if(hasPermission('edit_appointment', $can))
            <button onclick='openEditModal(@json($appointment))'
                class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs" title="edit">
                <i class="fas fa-pen"></i> 
            </button>
        @endif
        
        @if(hasPermission('refer_appointment', $can) && $appointment->appoint_status != 'refer')
            <button onclick="openReferralModal({{ $appointment->appoint_id }})"
                class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 flex items-center gap-1 text-xs" title="refer">
                <i class="fas fa-share"></i>
            </button>
        @endif

        @if(hasPermission('prescribe_appointment', $can))
            <button onclick="openPrescriptionFromAppointment({{ $appointment->appoint_id }}, '{{ $appointment->pet?->pet_name ?? '' }}', '{{ $appointment->appoint_date }}')"
                class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 flex items-center gap-1 text-xs" title="prescribe">
                <i class="fas fa-prescription"></i>
            </button>
        @endif

        @if(hasPermission('delete_appointment', $can))
            <form action="{{ route('medical.appointments.destroy', $appointment->appoint_id) }}" method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                @csrf
                @method('DELETE')
                <input type="hidden" name="active_tab" value="appointments">
                <button type="submit"
                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
                    <i class="fas fa-trash"></i> 
                </button>
            </form>
        @endif
    </div>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-gray-500 py-4">No appointments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(method_exists($appointments, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $appointments->firstItem() }} to {{ $appointments->lastItem() }} of
                    {{ $appointments->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    {{ $appointments->appends(['active_tab' => 'appointments'])->links() }}
                </div>
            </div>
            @endif
        </div>

        <!-- ==================== PRESCRIPTIONS TAB ==================== -->
        <div id="prescriptionsContent" class="tab-content hidden">
            <!-- Show Entries Dropdown for Prescriptions -->
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <form method="GET" action="{{ route('medical.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="active_tab" value="prescriptions">
                    <label for="prescriptionPerPage" class="text-sm text-black">Show</label>
                    <select name="prescriptionPerPage" id="prescriptionPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('prescriptionPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
                <!--<button onclick="openPrescriptionModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                    + Add Prescription
                </button>-->
            </div>
            <br>

            <!-- Prescriptions Table -->
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-2 py-2">Pet</th>
                            <th class="border px-2 py-2">Date</th>
                            <th class="border px-2 py-2">Medications</th>
                            <th class="border px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($prescriptions ?? [] as $index => $prescription)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-2 py-2">
                                @if(method_exists($prescriptions, 'firstItem'))
                                    {{ $prescriptions->firstItem() + $index }}
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </td>
                            <td class="border px-2 py-2">{{ $prescription->pet->pet_name }}</td>
                            <td class="border px-2 py-2">{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('F d, Y') }}</td>
                            <td class="border px-2 py-2">
                                @if($prescription->medication)
                                    @php
                                        $medications = json_decode($prescription->medication, true) ?? [];
                                    @endphp
                                    {{ count($medications) }} medication(s)
                                @else
                                    No medications
                                @endif
                            </td>
                            <td class="border px-2 py-1 flex justify-center gap-1">
                               @if(hasPermission('view_prescriptions', $can))
                            <button onclick="viewPrescription(this)" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
    data-id="{{ $prescription->prescription_id }}"
    data-pet="{{ $prescription->pet->pet_name }}"
    data-species="{{ $prescription->pet->pet_species }}"
    data-breed="{{ $prescription->pet->pet_breed }}"
    data-weight="{{ $prescription->pet->pet_weight }}"
    data-age="{{ $prescription->pet->pet_age }}"
    data-temp="{{ $prescription->pet->pet_temperature }}"
    data-gender="{{ $prescription->pet->pet_gender }}"
    data-date="{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('F d, Y') }}"
    data-medication="{{ $prescription->medication }}"
    data-differential-diagnosis="{{ $prescription->differential_diagnosis }}"
    data-notes="{{ $prescription->notes }}"
    data-branch-name="{{ $prescription->branch->branch_name ?? 'Main Branch' }}"
    data-branch-address="{{ $prescription->branch->branch_address ?? 'Branch Address' }}"
    data-branch-contact="{{ $prescription->branch->branch_contactNum ?? 'Contact Number' }}"
    data-vet-name="{{ $prescription->user->user_name ?? 'N/A' }}"
    data-vet-license="{{ $prescription->user->user_licenseNum ?? 'N/A' }}"
    title="View Prescription">
    <i class="fas fa-eye"></i>
</button>
                                 @endif
                                @if(hasPermission('print_prescription', $can))
                                <!-- Direct Print Button -->
                               <button onclick="directPrint(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                    data-id="{{ $prescription->prescription_id }}"
                                    data-pet="{{ $prescription->pet->pet_name }}"
                                    data-species="{{ $prescription->pet->pet_species }}"
                                    data-breed="{{ $prescription->pet->pet_breed }}"
                                    data-weight="{{ $prescription->pet->pet_weight }}"
                                    data-age="{{ $prescription->pet->pet_age }}"
                                    data-temp="{{ $prescription->pet->pet_temperature }}"
                                    data-gender="{{ $prescription->pet->pet_gender }}"
                                    data-date="{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('F d, Y') }}"
                                    data-medication="{{ $prescription->medication }}"
                                     data-differential-diagnosis="{{ $prescription->differential_diagnosis }}"
                                    data-notes="{{ $prescription->notes }}"
                                    data-branch-name="{{ $prescription->branch->branch_name ?? 'Main Branch' }}"
                                    data-branch-address="{{ $prescription->branch->branch_address ?? 'Branch Address' }}"
                                    data-branch-contact="{{ $prescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                    data-vet-name="{{ $prescription->user->user_name ?? 'N/A' }}"
    data-vet-license="{{ $prescription->user->user_licenseNum ?? 'N/A' }}"
                                    title="print">
                                    <i class="fas fa-print"></i>
                                </button> 
                                @endif
                                @if(hasPermission('edit_prescription', $can))
                                <!-- Edit -->
                                <button onclick="editPrescription({{ $prescription->prescription_id }})" class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] text-xs" title="edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                @endif
                                @if(hasPermission('edit_prescription', $can))
                                <!-- Delete -->
                                <form action="{{ route('medical.prescriptions.destroy', $prescription->prescription_id) }}" method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this prescription?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="active_tab" value="prescriptions">
                                    <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs" title="delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                 @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-gray-500 py-4">No prescriptions found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Prescriptions -->
            @if(method_exists($prescriptions, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $prescriptions->firstItem() }} to {{ $prescriptions->lastItem() }} of
                    {{ $prescriptions->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    {{ $prescriptions->appends(['active_tab' => 'prescriptions'])->links() }}
                </div>
            </div>
            @endif
        </div>

        <!-- ==================== REFERRALS TAB ==================== -->
        <div id="referralsContent" class="tab-content hidden">
            <!-- Show Entries Dropdown for Referrals -->
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <form method="GET" action="{{ route('medical.index') }}" class="flex items-center space-x-2">
                    <input type="hidden" name="active_tab" value="referrals">
                    <label for="referralPerPage" class="text-sm text-black">Show</label>
                    <select name="referralPerPage" id="referralPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('referralPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span>entries</span>
                </form>
                <!--<button onclick="openReferralModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                    + Add Referral
                </button>-->
            </div>
            <br>

            <!-- Referrals Table -->
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-2 py-2">Date</th>
                            <th class="border px-2 py-2">Pet</th>
                            <th class="border px-2 py-2">Owner</th>
                            <th class="border px-2 py-2">Referred To</th>
                            <th class="border px-2 py-2">Description</th>
                            <th class="border px-2 py-2">Status</th>
                            <th class="border px-2 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($referrals ?? [] as $index => $referral)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($referrals, 'firstItem'))
                                        {{ $referrals->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-2 py-2">{{ \Carbon\Carbon::parse($referral->ref_date)->format('F j, Y') }}</td>
                                <td class="border px-2 py-2">{{ $referral->appointment?->pet?->pet_name ?? 'N/A' }}</td>
                                <td class="border px-2 py-2">{{ $referral->appointment?->pet?->owner?->own_name ?? 'N/A' }}</td>
                                <td class="border px-2 py-2">{{ $referral->refToBranch?->branch_name ?? 'N/A' }}</td>
                                <td class="border px-2 py-2">{{ Str::limit($referral->ref_description, 50) }}</td>
                                <td class="border px-2 py-2">
                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">
                                        Referred
                                    </span>
                                </td>
                                <td class="border px-2 py-1">
    <div class="flex justify-center items-center gap-1">
        @if(hasPermission('view_referrals', $can))
            <button onclick="viewReferral({{ $referral->ref_id }})"
                class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs" title="view">
                <i class="fas fa-eye"></i>
            </button>
        @endif
        
        @if(hasPermission('print_referral', $can))
            <button onclick="printReferral({{ $referral->ref_id }})"
                class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 flex items-center gap-1 text-xs" title="print">
                <i class="fas fa-print"></i>
            </button>
        @endif
        
        @if(hasPermission('edit_referral', $can))
            <button onclick="editReferral({{ $referral->ref_id }})"
                class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs" title="edit">
                <i class="fas fa-pen"></i>
            </button>
        @endif
        
        @if(hasPermission('delete_referral', $can))
            <form action="{{ route('medical.referrals.destroy', $referral->ref_id) }}" method="POST"
                onsubmit="return confirm('Are you sure you want to delete this referral?');" class="inline">
                @csrf
                @method('DELETE')
                <input type="hidden" name="active_tab" value="referrals">
                <button type="submit"
                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs" title="delete">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        @endif
    </div>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-gray-500 py-4">No referrals found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Referrals -->
            @if(method_exists($referrals, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $referrals->firstItem() }} to {{ $referrals->lastItem() }} of
                    {{ $referrals->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    {{ $referrals->appends(['active_tab' => 'referrals'])->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- ==================== APPOINTMENT MODALS ==================== -->
<!-- Add Appointment Modal -->
<div id="addModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white p-6 rounded shadow-md w-full max-w-3xl">
        <h2 class="text-lg font-bold text-[#0f7ea0] mb-4">Add Appointment</h2>
        <form id="addForm" action="{{ route('medical.appointments.store') }}" method="POST">
            @csrf
            <input type="hidden" name="active_tab" value="appointments">

            <!-- Row 1: Pet Owner -->
            <div class="mb-3">
                <label class="block text-sm mb-1">Pet Owner</label>
                <select id="owner_id" class="w-full border rounded px-3 py-2 text-sm" required onchange="populateOwnerDetails(this)">
                    <option disabled selected>Select Pet Owner</option>
                    @foreach(\App\Models\Owner::with('pets')->get() as $owner)
                        <option value="{{ $owner->own_id }}" data-contact="{{ $owner->own_contactnum }}" data-pets='@json($owner->pets->map(fn($p) => ["id"=>$p->pet_id,"name"=>$p->pet_name]))'>
                            {{ $owner->own_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Row 2: Contact Number & Pet -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Contact Number</label>
                    <input type="text" name="appoint_contactNum" id="appoint_contactNum" required class="w-full border rounded px-3 py-2 text-sm" readonly />
                </div>
                <div>
                    <label class="block text-sm mb-1">Pet</label>
                    <select name="pet_id" id="pet_id" required class="w-full border rounded px-3 py-2 text-sm">
                        <option disabled selected>Select Pet</option>
                    </select>
                </div>
            </div>

                    <!-- Row 3: Appointment Type & Appointment Date -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Type</label>
                    <select name="appoint_type" required class="w-full border rounded px-3 py-2 text-sm">
                        <option value="Walk-in" selected>Walk-in</option>
                        <option value="Referral">Referral</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Date</label>
                    <input type="date" 
                        name="appoint_date" 
                        value="{{ date('Y-m-d') }}" 
                        required 
                        class="w-full border rounded px-3 py-2 text-sm" />
                </div>
            </div>

            <!-- Row 4: Appointment Time & Status -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Time</label>
                    <select name="appoint_time" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="" disabled selected>Select a time</option>
                        @foreach ([
                            '09:00 AM','10:00 AM','11:00 AM','01:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM'
                        ] as $label)
                            <option value="{{ date('H:i', strtotime($label)) }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Status</label>
                    <select name="appoint_status" required class="w-full border rounded px-3 py-2 text-sm">
                        <option value="pending">Pending</option>
                        <option value="arrived">Arrived</option>
                        <option value="completed">Complete</option>
                    </select>
                </div>
            </div>

            <!-- Row 5: Services & Description -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Selected Services</label>
                    <input type="text" id="selectedServicesDisplay" class="w-full border rounded px-3 py-2 text-sm" readonly placeholder="Click 'Select Services' to choose services" />
                    <button type="button" onclick="openServiceSelectionModal('add')" class="mt-2 bg-[#0f7ea0] text-white px-3 py-1 rounded hover:bg-[#0c6a86] text-sm">Select Services</button>
                </div>
                <div>
                    <label class="block text-sm mb-1">Description</label>
                    <textarea name="appoint_description" rows="3" class="w-full border rounded px-3 py-2 text-sm" placeholder="Add description..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeAddModal()" class="bg-gray-300 text-sm px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0d6b85]">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-30">
    <div class="bg-white w-full max-w-3xl p-6 rounded shadow">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4">Update Appointment</h2>
        <form method="POST" id="editForm" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" value="appointments">

            <input type="hidden" id="edit_appoint_id" name="appoint_id">

            <!-- Row 1: Pet Owner -->
            <div class="mb-3">
                <label class="block text-sm mb-1">Pet Owner</label>
                <select id="edit_owner_id" class="w-full border rounded px-3 py-2 text-sm" required onchange="populateOwnerDetailsEdit(this)">
                    <option disabled selected>Select Pet Owner</option>
                    @foreach(\App\Models\Owner::with('pets')->get() as $owner)
                        <option value="{{ $owner->own_id }}" data-contact="{{ $owner->own_contactnum }}" data-pets='@json($owner->pets->map(fn($p) => ["id"=>$p->pet_id,"name"=>$p->pet_name]))'>
                            {{ $owner->own_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Row 2: Contact Number & Pet -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Contact Number</label>
                    <input type="text" id="edit_appoint_contactNum" name="appoint_contactNum" class="w-full border rounded px-3 py-2 text-sm" readonly />
                </div>
                <div>
                    <label class="block text-sm mb-1">Pet</label>
                    <select id="edit_pet_id" name="pet_id" required class="w-full border rounded px-3 py-2 text-sm">
                    </select>
                </div>
            </div>

            <!-- Row 3: Appointment Type & Appointment Date -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Type</label>
                    <select id="edit_appoint_type" name="appoint_type" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="Walk-in">Walk-in</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Date</label>
                    <input type="date" id="edit_appoint_date" name="appoint_date" class="w-full border rounded px-3 py-2 text-sm" />
                </div>
            </div>

            <!-- Row 4: Appointment Time & Status -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Time</label>
                    <select id="edit_appoint_time" name="appoint_time" class="w-full border rounded px-3 py-2 text-sm">
                        @foreach ([
                            '09:00 AM','10:00 AM','11:00 AM','01:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM'
                        ] as $label)
                            <option value="{{ date('H:i', strtotime($label)) }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Status</label>
                    <select id="edit_appoint_status" name="appoint_status" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="pending">Pending</option>
                        <option value="arrived">Arrived</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <!-- Row 5: Services & Description -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Selected Services</label>
                    <input type="text" id="edit_selectedServicesDisplay" class="w-full border rounded px-3 py-2 text-sm" readonly placeholder="Click 'Select Services' to choose services" />
                    <button type="button" onclick="openServiceSelectionModal('edit')" class="mt-2 bg-[#0f7ea0] text-white px-3 py-1 rounded hover:bg-[#0c6a86] text-sm">Select Services</button>
                </div>
                <div>
                    <label class="block text-sm mb-1">Description</label>
                    <textarea id="edit_appoint_description" name="appoint_description" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-sm px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0d6b85]">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Service Selection Modal -->
<div id="serviceSelectionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-30">
    <div class="bg-white w-full max-w-lg p-6 rounded shadow">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4">Select Services</h2>
        <div id="serviceOptions" class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto mb-4">
            @foreach(\App\Models\Service::all() as $service)
                <label class="flex items-center gap-2 border p-2 rounded cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" value="{{ $service->serv_id }}" data-name="{{ $service->serv_name }}" class="service-checkbox">
                    {{ $service->serv_name }}
                </label>
            @endforeach
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeServiceSelectionModal()" class="bg-gray-300 text-sm px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
            <button type="button" onclick="saveSelectedServices()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">Save</button>
        </div>
    </div>
</div>

<!-- ==================== PRESCRIPTION MODALS ==================== -->
<!-- Add/Edit Prescription Modal -->
<div id="prescriptionModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
    <div class="bg-white w-full max-w-4xl p-6 rounded shadow-lg max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4" id="prescriptionModalTitle">Add Prescription</h2>
        <form id="prescriptionForm" method="POST">
            @csrf
            <input type="hidden" name="_method" id="prescriptionFormMethod" value="POST">
            <input type="hidden" name="prescription_id" id="prescription_id">
            <input type="hidden" name="active_tab" value="prescriptions">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm">Pet</label>
                    <select name="pet_id" id="prescription_pet_id" class="w-full border px-2 py-1 rounded" required>
                        <option value="">Select Pet</option>
                        @foreach (\App\Models\Pet::with('owner')->get() as $pet)
                            <option value="{{ $pet->pet_id }}">{{ $pet->pet_name }} ({{ $pet->pet_species }}) - {{ $pet->owner->own_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm">Date</label>
                    <input type="date" name="prescription_date" id="prescription_date" class="w-full border px-2 py-1 rounded" required>
                </div>
            </div>

            <!-- Medications Section -->
            <div class="mb-4">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-sm font-medium">Medications</label>
                    <button type="button" onclick="addMedicationField()" class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600">
                        <i class="fas fa-plus"></i> Add Medication
                    </button>
                </div>
                
                <div id="medicationContainer" class="space-y-3">
                    <!-- Initial medication field will be added by JavaScript -->
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium">Differential Diagnosis</label>
                <textarea name="differential_diagnosis" id="differential_diagnosis" rows="3" 
                          class="w-full border px-2 py-1 rounded" 
                          placeholder="Enter differential diagnosis (conditions being considered)"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm">Notes/Recommendations</label>
                <textarea name="notes" id="prescription_notes" rows="3" class="w-full border px-2 py-1 rounded" placeholder="Additional notes or recommendations"></textarea>
            </div>

            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400" onclick="closePrescriptionModal()">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0d6b85]">Save Prescription</button>
            </div>
        </form>
    </div>
</div>

<!-- View Prescription Modal -->
<div id="viewPrescriptionModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden no-print">
    <div class="bg-white w-full max-w-2xl p-0 rounded-lg shadow-lg relative max-h-[100vh] overflow-y-auto">
        <div id="prescriptionContent" class="prescription-container bg-white p-10">
            <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
                <!-- Left side: Logo -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
                </div>
                
                <!-- Right side: Clinic Information -->
                <div class="flex-grow text-center">
                    <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                        PETS 2GO VETERINARY CLINIC
                    </div>
                    <div class="branch-name text-lg font-bold underline text-center mt-1" id="branch_name">
                    
                    </div>
                    <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                        <div id="branch_address"></div>
                        <div id="branch_contactNum"></div>
                    </div>
                </div>
            </div>

            <div class="prescription-body">
                <div class="patient-info mb-6">
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <div class="mb-2"><strong>DATE:</strong> <span id="viewDate"></span></div>
                            <div class="mb-2"><strong>NAME OF PET:</strong> <span id="viewPet"></span></div>
                        </div>
                        <div class="text-center">
                            <div><strong>WEIGHT:</strong> <span id="viewWeight"></span></div>
                            <div><strong>TEMP:</strong> <span id="viewTemp"></span></div>
                        </div>
                        <div class="text-right">
                            <div><strong>AGE:</strong> <span id="viewAge"></span></div>
                            <div><strong>GENDER:</strong> <span id="viewGender"></span></div>
                        </div>
                    </div>
                </div>

                <div class="rx-symbol text-left my-8 text-6xl font-bold text-gray-800">℞</div>

                <div class="medication-section mb-8">
                    <div class="section-title text-base font-bold mb-4">MEDICATION</div>
                    <div id="medicationsList" class="space-y-3"></div>
                </div>

                <div class="differential-diagnosis mb-6">
                    <h3 class="text-base font-bold mb-2">DIFFERENTIAL DIAGNOSIS:</h3>
                    <div id="viewDifferentialDiagnosis" class="text-sm bg-blue-50 p-3 rounded border-l-4 border-blue-500"></div>
                </div>

                <div class="recommendations mb-8">
                    <h3 class="text-base font-bold mb-4">RECOMMENDATION/REMINDER:</h3>
                    <div id="viewNotes" class="text-sm"></div>
                </div>

                <div class="footer text-right pt-8 border-t-2 border-black">
    <div class="doctor-info text-sm">
        <div class="doctor-name font-bold mb-1" id="viewVetName">Loading...</div>
        <div class="license-info text-gray-600">License No.: <span id="viewVetLicense">Loading...</span></div>
        <div class="license-info text-gray-600">Attending Veterinarian</div>
    </div>
</div>
            </div>
        </div>
        <button onclick="document.getElementById('viewPrescriptionModal').classList.add('hidden')" 
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-2xl no-print">&times;</button>
    </div>
</div>

<!-- Hidden Print Container -->
<div id="printContainer" style="display: none;">
    <div id="printContent" class="prescription-container bg-white p-10">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<!-- ==================== REFERRAL MODALS ==================== -->
<!-- Enhanced Referral Modal -->
<div id="referralModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white w-full max-w-4xl p-6 rounded shadow-lg max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4" id="referralModalTitle">Create Referral</h2>
        <form id="referralForm" method="POST">
            @csrf
            <input type="hidden" name="_method" id="referralFormMethod" value="POST">
            <input type="hidden" name="ref_id" id="ref_id">
            <input type="hidden" name="active_tab" value="referrals">

            <!-- Appointment Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Appointment</label>
                <select name="appointment_id" id="appointment_id" class="w-full border px-3 py-2 rounded" required onchange="loadAppointmentDetails(this.value)">
                    <option value="">Select Appointment</option>
                    @foreach(\App\Models\Appointment::with(['pet.owner'])->where('appoint_status', '!=', 'refer')->get() as $appointment)
                        <option value="{{ $appointment->appoint_id }}">
                            {{ \Carbon\Carbon::parse($appointment->appoint_date)->format('M d, Y') }} - 
                            {{ $appointment->pet->pet_name ?? 'N/A' }} ({{ $appointment->pet->owner->own_name ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Patient Information Section -->
            <div class="bg-gray-50 p-4 rounded mb-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Patient Information</h3>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <label class="block text-gray-600">Pet Name:</label>
                        <span id="ref_pet_name" class="font-medium">-</span>
                    </div>
                    <div>
                        <label class="block text-gray-600">Gender:</label>
                        <span id="ref_pet_gender" class="font-medium">-</span>
                    </div>
                    <div>
                        <label class="block text-gray-600">Date of Birth:</label>
                        <span id="ref_pet_dob" class="font-medium">-</span>
                    </div>
                    <div>
                        <label class="block text-gray-600">Species:</label>
                        <span id="ref_pet_species" class="font-medium">-</span>
                    </div>
                    <div>
                        <label class="block text-gray-600">Breed:</label>
                        <span id="ref_pet_breed" class="font-medium">-</span>
                    </div>
                    <div>
                        <label class="block text-gray-600">Weight:</label>
                        <span id="ref_pet_weight" class="font-medium">-</span>
                    </div>
                </div>
            </div>

            <!-- Owner Information Section -->
            <div class="bg-gray-50 p-4 rounded mb-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Owner Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-gray-600">Owner Name:</label>
                        <span id="ref_owner_name" class="font-medium">-</span>
                    </div>
                    <div>
                        <label class="block text-gray-600">Contact Number:</label>
                        <span id="ref_owner_contact" class="font-medium">-</span>
                    </div>
                </div>
            </div>

            <!-- Medical History -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Medical History</label>
                <textarea name="medical_history" id="medical_history" rows="3" class="w-full border px-3 py-2 rounded text-sm" 
                          placeholder="Previous treatments, conditions, allergies, etc."></textarea>
            </div>

            <!-- Tests Conducted -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tests Conducted</label>
                <textarea name="tests_conducted" id="tests_conducted" rows="3" class="w-full border px-3 py-2 rounded text-sm" 
                          placeholder="Blood tests, X-rays, examinations performed, etc."></textarea>
            </div>

            <!-- Medications Given -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Medications Given</label>
                <textarea name="medications_given" id="medications_given" rows="3" class="w-full border px-3 py-2 rounded text-sm" 
                          placeholder="Current medications, dosages, treatment plan, etc."></textarea>
            </div>

            <!-- Referral Details -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referral Date</label>
                    <input type="date" name="ref_date" id="ref_date" class="w-full border px-3 py-2 rounded" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refer To Branch</label>
                    <select name="ref_to" id="ref_to" class="w-full border px-3 py-2 rounded" required>
                        <option value="">Select Branch</option>
                        @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Reason for Referral -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Referral</label>
                <textarea name="ref_description" id="ref_description" rows="4" class="w-full border px-3 py-2 rounded" 
                          placeholder="Detailed reason for referral, specialist needed, urgency level, etc." required></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeReferralModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0d6b85]">Submit Referral</button>
            </div>
        </form>
    </div>
</div>

<!-- View Referral Modal -->
<div id="viewReferralModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white w-full max-w-4xl p-0 rounded-lg shadow-lg max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center p-6 border-b">
            <h2 class="text-lg font-semibold text-[#0f7ea0]">Referral Details</h2>
            <button onclick="closeViewReferralModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        <div id="referralFormContent" class="referral-container bg-white p-8">
            <div class="header mb-8">
                <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" class="w-full h-auto object-contain">
            </div>
            <br><br>
            
            <!-- Basic Information Section -->
            <div class="patient-info mb-6">
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div>
                        <div class="mb-2"><strong>DATE:</strong> <span id="view_ref_date">-</span></div>
                        <div class="mb-2"><strong>NAME OF PET:</strong> <span id="view_pet_name">-</span></div>
                    </div>
                    <div class="text-center">
                        <div class="mb-2"><strong>OWNER:</strong> <span id="view_owner_name">-</span></div>
                        <div class="mb-2"><strong>CONTACT #:</strong> <span id="view_owner_contact">-</span></div>
                    </div>
                    <div class="text-right">
                        <div class="mb-2"><strong>DOB:</strong> <span id="view_pet_dob">-</span></div>
                        <div class="mb-2"><strong>GENDER:</strong> <span id="view_pet_gender">-</span></div>
                    </div>
                </div>
            </div>
            
            <!-- History Section -->
            <div class="form-section mb-6">
                <div class="section-title font-bold text-base text-gray-800 mb-4 border-b border-gray-300 pb-2">HISTORY:</div>
                <div id="view_medical_history" class="text-sm text-gray-700 mb-4">No medical history provided</div>
            </div>
            
            <!-- Test Conducted Section -->
            <div class="form-section mb-6">
                <div class="section-title font-bold text-base text-gray-800 mb-4 border-b border-gray-300 pb-2">TEST CONDUCTED:</div>
                <div id="view_tests_conducted" class="text-sm text-gray-700 mb-4">No tests documented</div>
                <div class="test-note text-center font-bold text-gray-600 mt-4 text-sm italic">***NO FURTHER TESTS WERE PERFORMED***</div>
            </div>
            
            <!-- Medications Given Section -->
            <div class="form-section mb-6">
                <div class="section-title font-bold text-base text-gray-800 mb-4 border-b border-gray-300 pb-2">MEDS GIVEN:</div>
                <div id="view_medications_given" class="text-sm text-gray-700 mb-4">No medications documented</div>
                <div class="med-note text-center font-bold text-gray-600 mt-4 text-sm italic">***NO OTHER MEDICATIONS GIVEN***</div>
            </div>
            
            <!-- Reason for Referral Section -->
            <div class="form-section mb-6">
                <div class="section-title font-bold text-base text-gray-800 mb-4 border-b border-gray-300 pb-2">REASON FOR REFERRAL:</div>
                <div id="view_ref_description" class="text-sm text-gray-700 mb-4">-</div>
            </div>
            
            <!-- Referring Information Section -->
            <div class="form-section">
                <div class="referral-info bg-gray-100 p-5 rounded-lg border-l-4 border-[#ff8c42]">
                    <div class="section-title font-bold text-base text-gray-800 mb-4">REFERRING VETERINARIAN:</div>
                    <div class="vet-name text-lg font-bold text-gray-800 mb-2">DR. JAN JERICK M. GO</div>
                    <div class="clinic-details text-gray-600 mb-1">LIC. NO. 0012045</div>
                    <div class="clinic-details text-gray-600 mb-1" id="view_ref_from_branch">PETS 2GO VETERINARY CLINIC</div>
                    <div class="clinic-details text-gray-600">0906-765-9732</div>
                </div>
            </div>
            
            <!-- Referred To Section -->
            <div class="form-section mt-6">
                <div class="referral-info bg-blue-50 p-5 rounded-lg border-l-4 border-blue-500">
                    <div class="section-title font-bold text-base text-gray-800 mb-4">REFERRED TO:</div>
                    <div class="clinic-details text-lg font-bold text-gray-800 mb-2" id="view_ref_to_branch">-</div>
                    <div class="clinic-details text-gray-600 mb-1">Specialist Veterinary Care</div>
                    <div class="clinic-details text-gray-600">For specialized treatment and consultation</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Container for Referral -->
<div id="printReferralContainer" style="display: none;">
    <div id="printReferralContent" class="referral-container bg-white p-8">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<!-- View Appointment Modal -->
<div id="viewAppointmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white w-full max-w-4xl p-6 rounded-lg shadow-lg max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h2 class="text-xl font-bold text-[#0f7ea0]">
                <i class="fas fa-calendar-check mr-2"></i>
                Appointment Details
            </h2>
            <button onclick="closeViewAppointmentModal()" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
        </div>
        
        <!-- Current Appointment Information -->
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                <h3 class="font-semibold text-gray-700 mb-3 pb-2 border-b border-blue-300 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                    Appointment Information
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex">
                        <span class="font-medium w-32 text-gray-600">
                            <i class="fas fa-calendar mr-1"></i>Date:
                        </span>
                        <span id="view_appoint_date" class="text-gray-800 font-medium">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-medium w-32 text-gray-600">
                            <i class="fas fa-clock mr-1"></i>Time:
                        </span>
                        <span id="view_appoint_time" class="text-gray-800 font-medium">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-medium w-32 text-gray-600">
                            <i class="fas fa-tag mr-1"></i>Type:
                        </span>
                        <span id="view_appoint_type" class="text-gray-800 font-medium">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-medium w-32 text-gray-600">
                            <i class="fas fa-flag mr-1"></i>Status:
                        </span>
                        <span id="view_appoint_status">-</span>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                <h3 class="font-semibold text-gray-700 mb-3 pb-2 border-b border-green-300 flex items-center">
                    <i class="fas fa-paw mr-2 text-green-600"></i>
                    Pet & Owner Details
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex">
                        <span class="font-medium w-32 text-gray-600">
                            <i class="fas fa-dog mr-1"></i>Pet Name:
                        </span>
                        <span id="view_pet_name_appt" class="text-gray-800 font-medium">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-medium w-32 text-gray-600">
                            <i class="fas fa-user mr-1"></i>Owner:
                        </span>
                        <span id="view_owner_name_appt" class="text-gray-800 font-medium">-</span>
                    </div>
                    <div class="flex">
                        <span class="font-medium w-32 text-gray-600">
                            <i class="fas fa-phone mr-1"></i>Contact:
                        </span>
                        <span id="view_owner_contact_appt" class="text-gray-800 font-medium">-</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Services -->
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-briefcase-medical mr-2 text-purple-600"></i>
                Services
            </h3>
            <div id="view_services" class="bg-purple-50 p-3 rounded-lg text-sm text-gray-700 border border-purple-200">-</div>
        </div>
        
        <!-- Description -->
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-file-alt mr-2 text-orange-600"></i>
                Description
            </h3>
            <div id="view_description" class="bg-orange-50 p-3 rounded-lg text-sm text-gray-700 border border-orange-200">No description provided</div>
        </div>
        
        <!-- History Timeline -->
        <div>
            <h3 class="font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-history mr-2 text-indigo-600"></i>
                Change History Timeline
            </h3>
            <div id="appointment_history" class="space-y-3 bg-gray-50 p-4 rounded-lg">
                <!-- History items will be inserted here -->
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button onclick="closeViewAppointmentModal()" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                Close
            </button>
        </div>
    </div>
</div>

<style>
/* Referral-specific styles */
.referral-container {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    background-color: white;
}

.referral-container .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 20px;
}

.referral-container .clinic-logo {
    background-color: #ff8c42;
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    font-size: 18px;
}

.referral-container .form-title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.referral-container .form-section {
    margin-bottom: 25px;
}

.referral-container .form-row {
    display: flex;
    margin-bottom: 15px;
    align-items: center;
}

.referral-container .form-label {
    font-weight: bold;
    min-width: 120px;
    color: #555;
}

.referral-container .form-value {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.referral-container .section-title {
    font-weight: bold;
    font-size: 16px;
    color: #333;
    margin-bottom: 15px;
    padding-bottom: 5px;
}

.referral-container .test-note, 
.referral-container .med-note {
    text-align: center;
    font-weight: bold;
    color: #666;
    margin: 15px 0;
    font-style: italic;
}

.referral-container .referral-info {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid #ff8c42;
}

.referral-container .vet-name {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
}

.referral-container .clinic-details {
    color: #666;
    margin-bottom: 5px;
}

/* Print styles for referral - consolidated below */
</style>

<style>
/* Tab Styles */
/* Tab styles - matching pet management */
.tab-button {
    border-bottom-color: transparent;
    color: #6B7280;
}

.tab-button.active {
    border-bottom-color: #0f7ea0;
    color: #0f7ea0;
}

.tab-content {
    display: block;
}

.tab-content.hidden {
    display: none;
}

/* Prescription Styles */
.prescription-container {
    font-family: Arial, sans-serif;
    max-width: 700px;
    margin: 0 auto;
    border: 1px solid #000;
    background-color: white;
}

.medication-item {
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 12px;
    padding: 8px;
    border-left: 3px solid #dc2626;
    background-color: #fef2f2;
}

.medication-field {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    background-color: #f9fafb;
}

.product-suggestions {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
}

.product-suggestion-item:hover {
    background-color: #f3f4f6;
}

.product-suggestion-item:last-child {
    border-bottom: none;
}

.rx-symbol {
    text-align: left !important;
    margin: 20px 0 !important;
}

/* Print Styles */
@media print {
    @page {
        margin: 0.3in;
        size: A4;
    }
    
    body * {
        visibility: hidden;
    }
    
    /* Only show the active print container */
    .print-prescription,
    .print-prescription *,
    .print-referral,
    .print-referral * {
        visibility: visible !important;
    }
    
    .print-prescription {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: block !important;
        background: white !important;
    }
    
    .print-prescription .prescription-container {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: 2px solid #000 !important;
        padding: 30px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        page-break-inside: avoid;
    }
    
    .print-referral {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: block !important;
        background: white !important;
    }
    
    .print-referral .referral-container {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: 1px solid #000 !important;
        padding: 15px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        page-break-inside: auto;
    }
    
    /* Referral specific print optimizations */
    .print-referral .header {
        margin-bottom: 15px !important;
    }
    
    .print-referral .header img {
        width: 100% !important;
        height: auto !important;
        max-height: 100px !important;
        min-height: 80px !important;
        object-fit: contain !important;
        object-position: center !important;
    }
    
    .print-referral .header div[style*="background-color: #f88e28"] {
        background-color: #f88e28 !important;
        padding: 16px !important;
        border-radius: 8px !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    .print-referral .header {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .print-referral .patient-info {
        margin-bottom: 12px !important;
    }
    
    .print-referral .form-section {
        margin-bottom: 10px !important;
        page-break-inside: avoid;
    }
    
    .print-referral .section-title {
        font-size: 14px !important;
        margin-bottom: 6px !important;
        padding-bottom: 3px !important;
    }
    
    .print-referral .text-sm {
        font-size: 12px !important;
        line-height: 1.3 !important;
    }
    
    .print-referral .text-xs {
        font-size: 11px !important;
        line-height: 1.3 !important;
    }
    
    .print-referral .grid {
        gap: 6px !important;
    }
    
    .print-referral .referral-info {
        padding: 10px !important;
        margin-bottom: 8px !important;
    }
    
    .print-referral .mb-1 {
        margin-bottom: 3px !important;
    }
    
    .print-referral .mb-2 {
        margin-bottom: 6px !important;
    }
    
    .print-referral .mb-3 {
        margin-bottom: 8px !important;
    }
    
    .print-referral .mb-4 {
        margin-bottom: 10px !important;
    }
    
    .no-print {
        display: none !important;
        visibility: hidden !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .clinic-name {
        color: #a86520 !important;
    }
    
    .medication-item {
        border-left: 3px solid #dc2626 !important;
        background-color: #fef2f2 !important;
    }
}
</style>

<script>
// Global variables
let currentForm = null;
let selectedServices = [];
let medicationCounter = 0;
let currentPrescriptionId = null;
let currentReferralId = null;

// Setup CSRF token
function setupCSRF() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.csrfToken = token.getAttribute('content');
    }
}

// Tab functionality
// Tab switching functionality - matching pet management
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Add active class to selected tab button
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Update URL parameter without page reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}


// ==================== APPOINTMENT FUNCTIONS ====================

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addModal').classList.add('flex');
    const ids = getSelectedFromForm('addForm');
    updateDisplayAndInternal('add', ids);
}

function closeAddModal() {
    document.getElementById('addModal').classList.remove('flex');
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(appointment) {
    document.getElementById('editForm').action = `/medical-management/appointments/${appointment.appoint_id}`;
    document.getElementById('edit_appoint_id').value = appointment.appoint_id ?? '';
    document.getElementById('edit_appoint_date').value = appointment.appoint_date ?? '';

     let appointTime = appointment.appoint_time ?? '';
    if (appointTime && appointTime.length > 5) {
        appointTime = appointTime.substring(0, 5); // Extract HH:MM from HH:MM:SS
    }
    document.getElementById('edit_appoint_contactNum').value = appointment.appoint_contactNum ?? '';
    document.getElementById('edit_appoint_status').value = appointment.appoint_status ?? '';
    document.getElementById('edit_appoint_type').value = appointment.appoint_type ?? '';
    document.getElementById('edit_appoint_description').value = appointment.appoint_description ?? '';

    document.getElementById('edit_owner_id').value = appointment.pet?.owner?.own_id ?? '';
    populateOwnerDetailsEdit(document.getElementById('edit_owner_id'), appointment.pet_id ?? null);

    const serviceIds = (appointment.services || []).map(s => String(s.serv_id));
    createHiddenServiceInputs('editForm', serviceIds);
    const names = (appointment.services || []).map(s => s.serv_name).join(', ');
    document.getElementById('edit_selectedServicesDisplay').value = names;

    const m = document.getElementById('editModal'); 
    m.classList.remove('hidden'); 
    m.classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('flex');
    document.getElementById('editModal').classList.add('hidden');
}

function getSelectedFromForm(formId) {
    return Array.from(document.querySelectorAll(`#${formId} input[name="services[]"]`)).map(i => String(i.value));
}

function createHiddenServiceInputs(formId, ids) {
    const existingInputs = document.querySelectorAll(`#${formId} input[name="services[]"]`);
    existingInputs.forEach(n => n.remove());
    
    const form = document.getElementById(formId);
    if (!form) return;
    
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'services[]';
        input.value = id;
        form.appendChild(input);
    });
}

function openServiceSelectionModal(formType) {
    currentForm = formType;
    const formId = formType === 'add' ? 'addForm' : 'editForm';
    selectedServices = getSelectedFromForm(formId);

    const checkboxes = document.querySelectorAll('#serviceOptions .service-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectedServices.includes(String(cb.value));
    });

    const modal = document.getElementById('serviceSelectionModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function saveSelectedServices() {
    const checkedBoxes = Array.from(document.querySelectorAll('#serviceOptions .service-checkbox:checked'));
    selectedServices = checkedBoxes.map(cb => String(cb.value));
    const names = checkedBoxes.map(cb => cb.dataset.name);

    const formId = currentForm === 'add' ? 'addForm' : 'editForm';
    createHiddenServiceInputs(formId, selectedServices);

    if (currentForm === 'add') {
        document.getElementById('selectedServicesDisplay').value = names.join(', ');
    } else {
        document.getElementById('edit_selectedServicesDisplay').value = names.join(', ');
    }

    closeServiceSelectionModal();
}

function closeServiceSelectionModal() {
    const modal = document.getElementById('serviceSelectionModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function updateDisplayAndInternal(formType, ids) {
    const names = [];
    ids.forEach(id => {
        const checkbox = document.querySelector(`#serviceOptions .service-checkbox[value="${id}"]`);
        if (checkbox) names.push(checkbox.dataset.name);
    });
    if (formType === 'add') {
        document.getElementById('selectedServicesDisplay').value = names.join(', ');
    } else {
        document.getElementById('edit_selectedServicesDisplay').value = names.join(', ');
    }
}

function populateOwnerDetails(select) {
    const petSelect = document.getElementById('pet_id');
    petSelect.innerHTML = '<option disabled selected>Select Pet</option>';
    const pets = JSON.parse(select.selectedOptions[0].dataset.pets || '[]');
    pets.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        petSelect.appendChild(opt);
    });
    document.getElementById('appoint_contactNum').value = select.selectedOptions[0].dataset.contact || '';
}

function populateOwnerDetailsEdit(select, petId = null) {
    const petSelect = document.getElementById('edit_pet_id');
    petSelect.innerHTML = '<option disabled selected>Select Pet</option>';
    const pets = JSON.parse(select.selectedOptions[0].dataset.pets || '[]');
    pets.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        if (String(p.id) === String(petId)) opt.selected = true;
        petSelect.appendChild(opt);
    });
    document.getElementById('edit_appoint_contactNum').value = select.selectedOptions[0].dataset.contact || '';
}

// ==================== PRESCRIPTION FUNCTIONS ====================

function openPrescriptionModal() {
    const form = document.getElementById('prescriptionForm');
    form.reset();
    form.action = "{{ route('medical.prescriptions.store') }}";
    document.getElementById('prescriptionFormMethod').value = 'POST';
    document.getElementById('prescriptionModalTitle').textContent = 'Add Prescription';
    document.getElementById('prescription_id').value = '';
    document.getElementById('medicationContainer').innerHTML = '';
    
    medicationCounter = 0;
    addMedicationField();

    document.getElementById('differential_diagnosis').value = ''; 
    
    document.getElementById('prescriptionModal').classList.remove('hidden');
}

function openPrescriptionFromAppointment(appointmentId, petName = null, appointDate = null) {
    // First, open the modal and reset form
    const form = document.getElementById('prescriptionForm');
    form.reset();
    form.action = "{{ route('medical.prescriptions.store') }}";
    document.getElementById('prescriptionFormMethod').value = 'POST';
    document.getElementById('prescriptionModalTitle').textContent = 'Add Prescription';
    document.getElementById('prescription_id').value = '';
    document.getElementById('medicationContainer').innerHTML = '';
    
    medicationCounter = 0;
    addMedicationField();
    document.getElementById('differential_diagnosis').value = ''; 
    
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('prescription_date').value = today;
    
    // Show the modal
    document.getElementById('prescriptionModal').classList.remove('hidden');
    
    // Then fetch and populate appointment data if appointmentId is provided
    if (appointmentId) {
        fetch(`/medical-management/appointments/${appointmentId}/for-prescription`, {
            headers: {
                'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received prescription data:', data);
            
            // Auto-populate the pet
            if (data.pet_id) {
                document.getElementById('prescription_pet_id').value = data.pet_id;
            }
            
            // Auto-populate the date from appointment
            if (data.appointment_date) {
                document.getElementById('prescription_date').value = data.appointment_date;
            }
            
            // Optional: Add appointment info to notes
            if (data.services) {
                const notesField = document.getElementById('prescription_notes');
                notesField.value = `Prescription for appointment on ${data.appointment_date}\nServices: ${data.services}`;
            }
        })
        .catch(error => {
            console.error('Error loading appointment data:', error);
            // If fetch fails but we have the data from parameters, use that
            if (petName && appointDate) {
                // Find the pet in the dropdown by name
                const petSelect = document.getElementById('prescription_pet_id');
                const options = petSelect.options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].text.includes(petName)) {
                        petSelect.value = options[i].value;
                        break;
                    }
                }
                document.getElementById('prescription_date').value = appointDate;
            }
        });
    }
}

function closePrescriptionModal() {
    document.getElementById('prescriptionModal').classList.add('hidden');
}

function addMedicationField() {
    const container = document.getElementById('medicationContainer');
    const fieldId = ++medicationCounter;
    
    const fieldHtml = `
        <div class="medication-field" data-field-id="${fieldId}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-sm font-medium text-gray-700">Medication ${fieldId}</h4>
                ${fieldId > 1 ? `<button type="button" onclick="removeMedicationField(${fieldId})" class="text-red-500 hover:text-red-700 text-sm"><i class="fas fa-trash"></i> Remove</button>` : ''}
            </div>
            
            <div class="grid grid-cols-1 gap-3 mb-3">
                <div class="relative">
                    <label class="block text-xs text-gray-600 mb-1">Search Product or Enter Manually</label>
                    <input type="text" 
                           class="product-search w-full border px-2 py-2 rounded text-sm" 
                           placeholder="Type product name or search from database..."
                           data-field-id="${fieldId}">
                    <div class="product-suggestions hidden" data-field-id="${fieldId}"></div>
                    <input type="hidden" class="selected-product-id" data-field-id="${fieldId}">
                    <input type="hidden" class="selected-product-name" data-field-id="${fieldId}">
                </div>
                
                <div class="bg-gray-50 p-2 rounded text-xs">
                    <div class="selected-product-display" data-field-id="${fieldId}">
                        Manual entry or select from search results above
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs text-gray-600 mb-1">Instructions (Sig.) - Use semicolon (;) to separate multiple instructions</label>
                <textarea class="medication-instructions w-full border px-2 py-2 rounded text-sm" 
                          rows="2" 
                          data-field-id="${fieldId}"
                          placeholder="e.g., Use it everyday; Apply twice daily; Take with food" required></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', fieldHtml);
    setupProductSearch(fieldId);
}

function removeMedicationField(fieldId) {
    const field = document.querySelector(`[data-field-id="${fieldId}"]`);
    if (field && document.querySelectorAll('.medication-field').length > 1) {
        field.remove();
    }
}

function setupProductSearch(fieldId) {
    const searchInput = document.querySelector(`input[data-field-id="${fieldId}"].product-search`);
    const suggestionsDiv = document.querySelector(`.product-suggestions[data-field-id="${fieldId}"]`);
    const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`);
    const productNameInput = document.querySelector(`.selected-product-name[data-field-id="${fieldId}"]`);
    const displayDiv = document.querySelector(`.selected-product-display[data-field-id="${fieldId}"]`);
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        productNameInput.value = query;
        if (query) {
            displayDiv.innerHTML = `<span class="text-blue-700 font-medium">Manual Entry: ${query}</span>`;
            displayDiv.classList.remove('bg-gray-100');
            displayDiv.classList.add('bg-blue-100');
        } else {
            displayDiv.innerHTML = 'Manual entry or select from search results above';
            displayDiv.classList.remove('bg-blue-100');
            displayDiv.classList.add('bg-gray-100');
        }
        
        if (query.length < 2) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-gray-500">Searching...</div>';
            suggestionsDiv.classList.remove('hidden');
            
            fetch(`{{ route('medical.prescriptions.search-products') }}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(products => {
                    if (products.length > 0) {
                        suggestionsDiv.innerHTML = products.map(product => `
                            <div class="product-suggestion-item" data-product-id="${product.id}" data-product-name="${product.name}">
                                <div class="font-medium">${product.name}</div>
                                <div class="text-xs text-gray-500">₱${parseFloat(product.price || 0).toFixed(2)} - ${product.type || 'Product'}</div>
                            </div>
                        `).join('');
                        
                        suggestionsDiv.classList.remove('hidden');
                        
                        suggestionsDiv.querySelectorAll('.product-suggestion-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const productId = this.dataset.productId;
                                const productName = this.dataset.productName;
                                
                                productIdInput.value = productId;
                                productNameInput.value = productName;
                                displayDiv.innerHTML = `<span class="text-green-700 font-medium">Selected: ${productName}</span>`;
                                displayDiv.classList.remove('bg-gray-100', 'bg-blue-100');
                                displayDiv.classList.add('bg-green-100');
                                
                                searchInput.value = productName;
                                suggestionsDiv.classList.add('hidden');
                            });
                        });
                    } else {
                        suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-gray-500">No products found in database. You can still type manually above.</div>';
                        suggestionsDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error searching products:', error);
                    suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-orange-500">Search temporarily unavailable. You can still type manually above.</div>';
                    suggestionsDiv.classList.remove('hidden');
                });
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (!searchInput.parentElement.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}
function directPrint(button) {
    const data = populatePrescriptionData(button);
    updatePrescriptionContent('printContent', data);
    
    // Remove all print classes first
    document.getElementById('printContainer').classList.remove('print-prescription', 'print-referral');
    document.getElementById('printReferralContainer').classList.remove('print-prescription', 'print-referral');
    
    // Hide referral container
    document.getElementById('printReferralContainer').style.display = 'none';
    
    // Show prescription container with print class
    const printContainer = document.getElementById('printContainer');
    printContainer.style.display = 'block';
    printContainer.classList.add('print-prescription');
    
    setTimeout(() => {
        window.print();
        printContainer.style.display = 'none';
        printContainer.classList.remove('print-prescription');
    }, 200);
}


// Form submission handler for prescriptions
document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const medications = [];
    document.querySelectorAll('.medication-field').forEach(field => {
        const fieldId = field.dataset.fieldId;
        const productId = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`).value;
        const productName = document.querySelector(`.selected-product-name[data-field-id="${fieldId}"]`).value || 
                           document.querySelector(`input[data-field-id="${fieldId}"].product-search`).value;
        const instructions = document.querySelector(`.medication-instructions[data-field-id="${fieldId}"]`).value;
        
        if (productName && instructions) {
            medications.push({
                product_id: productId || null,
                product_name: productName,
                instructions: instructions
            });
        }
    });
    
    if (medications.length === 0) {
        alert('Please add at least one medication with instructions');
        return;
    }
    
    const petId = document.getElementById('prescription_pet_id').value;
    const prescriptionDate = document.getElementById('prescription_date').value;
    
    if (!petId) {
        alert('Please select a pet');
        return;
    }
    
    if (!prescriptionDate) {
        alert('Please select a date');
        return;
    }
    
    const existingHiddenInput = this.querySelector('input[name="medications_json"]');
    if (existingHiddenInput) {
        existingHiddenInput.remove();
    }
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'medications_json';
    hiddenInput.value = JSON.stringify(medications);
    this.appendChild(hiddenInput);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    this.submit();
});

function editPrescription(id) {
    fetch(`/medical-management/prescriptions/${id}/edit`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            document.getElementById('prescriptionForm').reset();
            document.getElementById('prescription_id').value = id;
            document.getElementById('prescription_pet_id').value = data.pet_id;
            document.getElementById('prescription_date').value = data.prescription_date;
            document.getElementById('prescription_notes').value = data.notes || '';

            document.getElementById('medicationContainer').innerHTML = '';
            medicationCounter = 0;

            if (data.medications && data.medications.length > 0) {
                data.medications.forEach(med => {
                    addMedicationField();
                    const currentFieldId = medicationCounter;
                    
                    const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${currentFieldId}"]`);
                    const productNameInput = document.querySelector(`.selected-product-name[data-field-id="${currentFieldId}"]`);
                    const searchInput = document.querySelector(`input[data-field-id="${currentFieldId}"].product-search`);
                    const displayDiv = document.querySelector(`.selected-product-display[data-field-id="${currentFieldId}"]`);
                    const instructionsTextarea = document.querySelector(`.medication-instructions[data-field-id="${currentFieldId}"]`);
                    
                    productIdInput.value = med.product_id || '';
                    productNameInput.value = med.product_name || '';
                    searchInput.value = med.product_name || '';
                    displayDiv.innerHTML = `<span class="text-green-700 font-medium">Selected: ${med.product_name || 'Unknown Product'}</span>`;
                    displayDiv.classList.remove('bg-gray-100');
                    displayDiv.classList.add('bg-green-100');
                    instructionsTextarea.value = med.instructions || '';
                });
            } else {
                addMedicationField();
            }

            document.getElementById('prescriptionForm').action = `/medical-management/prescriptions/${id}`;
            document.getElementById('prescriptionFormMethod').value = 'PUT';
            document.getElementById('prescriptionModalTitle').textContent = 'Edit Prescription';
            document.getElementById('prescriptionModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error fetching prescription data:', error);
            alert('Error loading prescription data: ' + error.message);
        });
}
function populatePrescriptionData(button) {
    let medications = [];
    try {
        medications = JSON.parse(button.dataset.medication || '[]');
    } catch (e) {
        if (button.dataset.medication) {
            const oldMeds = button.dataset.medication.split(';');
            medications = oldMeds.map(med => ({
                product_name: med.trim(),
                instructions: '[Instructions will be added here]'
            }));
        }
    }
    
    const prescriptionData = {
        id: button.dataset.id,
        pet: button.dataset.pet,
        weight: button.dataset.weight || 'N/A',
        temp: button.dataset.temp || 'N/A',
        age: button.dataset.age || 'N/A',
        gender: button.dataset.gender || 'N/A',
        date: button.dataset.date,
        medications: medications,
        differentialDiagnosis: button.dataset.differentialDiagnosis || 'Not specified',
        notes: button.dataset.notes || 'No specific recommendations',
        branchName: button.dataset.branchName.toUpperCase(),
        branchAddress: 'Address: ' + button.dataset.branchAddress,
        branchContact: "Contact No: " + button.dataset.branchContact,
        vetName: button.dataset.vetName || 'N/A',
        vetLicense: button.dataset.vetLicense || 'N/A'
    };
    return prescriptionData;
}

function updatePrescriptionContent(targetId, data) {
    const container = document.getElementById(targetId);
    
    container.innerHTML = `
        <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
            <div class="flex-shrink-0">
                <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
            </div>
            <div class="flex-grow text-center">
                <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                    PETS 2GO VETERINARY CLINIC
                </div>
                <div class="branch-name text-lg font-bold underline text-center mt-1">
                    ${data.branchName}
                </div>
                <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                    <div>${data.branchAddress}</div>
                    <div>${data.branchContact}</div>
                </div>
            </div>
        </div>

        <div class="prescription-body">
            <div class="patient-info mb-6">
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div>
                        <div class="mb-2"><strong>DATE:</strong> ${data.date}</div>
                        <div class="mb-2"><strong>NAME OF PET:</strong> ${data.pet}</div>
                    </div>
                    <div class="text-center">
                        <div><strong>WEIGHT:</strong> ${data.weight}</div>
                        <div><strong>TEMP:</strong> ${data.temp}</div>
                    </div>
                    <div class="text-right">
                        <div><strong>AGE:</strong> ${data.age}</div>
                        <div><strong>GENDER:</strong> ${data.gender}</div>
                    </div>
                </div>
            </div>

            <div class="rx-symbol text-left my-8 text-6xl font-bold text-gray-800">℞</div>

            <div class="medication-section mb-8">
                <div class="section-title text-base font-bold mb-4">MEDICATION</div>
                <div class="space-y-3">
                    ${data.medications && data.medications.length > 0 ? data.medications.map((med, index) => `
                        <div class="medication-item">
                            <div class="text-sm font-medium text-red-600 mb-1">${index+1}. ${med.product_name || med.name || 'Unknown medication'}</div>
                            <div class="text-sm text-gray-700 ml-4"><strong>SIG.</strong> ${med.instructions || '[Instructions will be added here]'}</div>
                        </div>
                    `).join('') : '<div class="medication-item text-gray-500">No medications prescribed</div>'}
                </div>
            </div>

            <div class="differential-diagnosis mb-6">
                <h3 class="text-base font-bold mb-2">DIFFERENTIAL DIAGNOSIS:</h3>
                <div class="text-sm bg-blue-50 p-3 rounded border-l-4 border-blue-500">${data.differentialDiagnosis || 'Not specified'}</div>
            </div>

            <div class="recommendations mb-8">
                <h3 class="text-base font-bold mb-4">RECOMMENDATION/REMINDER:</h3>
                <div class="text-sm">${data.notes}</div>
            </div>

            <div class="footer text-right pt-8 border-t-2 border-black">
                <div class="doctor-info text-sm">
                    <div class="doctor-name font-bold mb-1">${data.vetName.toUpperCase()} DVM</div>
                    <div class="license-info text-gray-600">License No.: ${data.vetLicense}</div>
                    <div class="license-info text-gray-600">Attending Veterinarian</div>
                </div>
            </div>
        </div>
    `;
}

function viewPrescription(button) {
    currentPrescriptionId = button.dataset.id;
    
    // Get differential diagnosis
    let diffDiagnosis = button.dataset.differentialDiagnosis || 'Not specified';
    
    // Set all the basic fields
    document.getElementById('viewPet').innerText = button.dataset.pet || 'N/A';
    document.getElementById('viewWeight').innerText = button.dataset.weight || 'N/A';
    document.getElementById('viewTemp').innerText = button.dataset.temp || 'N/A';
    document.getElementById('viewAge').innerText = button.dataset.age || 'N/A';
    document.getElementById('viewGender').innerText = button.dataset.gender || 'N/A';
    document.getElementById('viewDate').innerText = button.dataset.date || 'N/A';
    
    // Set branch information
    document.getElementById('branch_name').innerText = (button.dataset.branchName || 'Main Branch').toUpperCase();
    document.getElementById('branch_address').innerText = button.dataset.branchAddress || 'Branch Address';
    document.getElementById('branch_contactNum').innerText = button.dataset.branchContact || 'Contact Number';
    
    // Set veterinarian information
    const vetName = button.dataset.vetName || 'N/A';
    const vetLicense = button.dataset.vetLicense || 'N/A';
    
    document.getElementById('viewVetName').innerText = vetName.toUpperCase() + ' DVM';
    document.getElementById('viewVetLicense').innerText = vetLicense;
    
    // Set differential diagnosis
    document.getElementById('viewDifferentialDiagnosis').innerText = diffDiagnosis;
    
    // Parse and display medications
    let medications = [];
    try {
        if (button.dataset.medication) {
            medications = JSON.parse(button.dataset.medication);
        }
    } catch (e) {
        console.error('Error parsing medications:', e);
    }
    
    const medsContainer = document.getElementById('medicationsList');
    medsContainer.innerHTML = '';
    
    if (medications && medications.length > 0) {
        medications.forEach((med, index) => {
            const medDiv = document.createElement('div');
            medDiv.classList.add('medication-item');
            medDiv.innerHTML = `
                <div class="text-sm font-medium text-red-600 mb-1">${index+1}. ${med.product_name || 'Unknown medication'}</div>
                <div class="text-sm text-gray-700 ml-4"><strong>SIG.</strong> ${med.instructions || 'No instructions'}</div>
            `;
            medsContainer.appendChild(medDiv);
        });
    } else {
        medsContainer.innerHTML = '<div class="medication-item text-gray-500">No medications prescribed</div>';
    }
    
    // Set notes
    document.getElementById('viewNotes').innerText = button.dataset.notes || 'No recommendations';
    
    // Show modal
    document.getElementById('viewPrescriptionModal').classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {

    const printButton = document.getElementById('btnPrint');

    if (printButton) {
        printButton.addEventListener('click', function() {

            const b = this.dataset;

            // Safely parse medications JSON
            let medications = [];
            try {
                medications = JSON.parse(b.medication || '[]');
            } catch(e) {
                console.error('Error parsing medications JSON', e);
            }

            // Build content for print
            let content = `
                <h2 style="text-align:center;">Prescription</h2>
                <p><strong>Date:</strong> ${b.date}</p>
                <p><strong>Pet:</strong> ${b.pet} (${b.species} - ${b.breed})</p>
                <p><strong>Gender:</strong> ${b.gender} | <strong>Age:</strong> ${b.age} | <strong>Weight:</strong> ${b.weight}kg | <strong>Temp:</strong> ${b.temp}°C</p>
                <p><strong>Differential Diagnosis:</strong> ${b.differentialDiagnosis || 'N/A'}</p>
                <p><strong>Medications:</strong></p>
                <ul>
            `;
            medications.forEach(m => {
                content += `<li>${m.product_name || ''} - ${m.dosage || ''}</li>`;
            });
            content += `</ul>
                <p><strong>Notes:</strong> ${b.notes || ''}</p>
                <hr>
                <p><strong>Vet:</strong> ${b.vetName} (${b.vetLicense})</p>
                <p><strong>Branch:</strong> ${b.branchName}, ${b.branchAddress} (${b.branchContact})</p>
            `;

            // Open print window
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Prescription</title></head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();

        });
    }

});

// ==================== REFERRAL FUNCTIONS ====================

function openReferralModal(appointmentId = null) {
    const form = document.getElementById('referralForm');
    form.reset();
    form.action = "{{ route('medical.referrals.store') }}";
    document.getElementById('referralFormMethod').value = 'POST';
    document.getElementById('referralModalTitle').textContent = 'Create Referral';
    document.getElementById('ref_id').value = '';
    
    // Set today's date as default
    document.getElementById('ref_date').value = new Date().toISOString().split('T')[0];
    
    // If appointment ID is provided, select it
    if (appointmentId) {
        document.getElementById('appointment_id').value = appointmentId;
        loadAppointmentDetails(appointmentId);
    } else {
        // Clear patient information
        clearPatientInfo();
    }
    
    document.getElementById('referralModal').classList.remove('hidden');
}

function closeReferralModal() {
    document.getElementById('referralModal').classList.add('hidden');
}

function loadAppointmentDetails(appointmentId) {
    if (!appointmentId) {
        clearPatientInfo();
        return;
    }
    
    fetch(`/medical-management/appointments/${appointmentId}/details`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.pet) {
            document.getElementById('ref_pet_name').textContent = data.pet.pet_name || '-';
            document.getElementById('ref_pet_gender').textContent = data.pet.pet_gender || '-';
            document.getElementById('ref_pet_dob').textContent = data.pet.pet_birthdate || '-';
            document.getElementById('ref_pet_species').textContent = data.pet.pet_species || '-';
            document.getElementById('ref_pet_breed').textContent = data.pet.pet_breed || '-';
            document.getElementById('ref_pet_weight').textContent = (data.pet.pet_weight || '-') + (data.pet.pet_weight ? ' kg' : '');
        }
        
        if (data.owner) {
            document.getElementById('ref_owner_name').textContent = data.owner.own_name || '-';
            document.getElementById('ref_owner_contact').textContent = data.owner.own_contactnum || '-';
        }
        
        if (data.medical_history) {
            document.getElementById('medical_history').value = data.medical_history;
        }
        
        if (data.recent_tests) {
            document.getElementById('tests_conducted').value = data.recent_tests;
        }
        
        if (data.current_medications) {
            document.getElementById('medications_given').value = data.current_medications;
        }
    })
    .catch(error => {
        console.error('Error loading appointment details:', error);
        clearPatientInfo();
    });
}

function clearPatientInfo() {
    document.getElementById('ref_pet_name').textContent = '-';
    document.getElementById('ref_pet_gender').textContent = '-';
    document.getElementById('ref_pet_dob').textContent = '-';
    document.getElementById('ref_pet_species').textContent = '-';
    document.getElementById('ref_pet_breed').textContent = '-';
    document.getElementById('ref_pet_weight').textContent = '-';
    document.getElementById('ref_owner_name').textContent = '-';
    document.getElementById('ref_owner_contact').textContent = '-';
    
    document.getElementById('medical_history').value = '';
    document.getElementById('tests_conducted').value = '';
    document.getElementById('medications_given').value = '';
}

// Referral form submission
document.getElementById('referralForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Validate required fields
    if (!formData.get('appointment_id')) {
        alert('Please select an appointment');
        return;
    }
    
    if (!formData.get('ref_date')) {
        alert('Please select a referral date');
        return;
    }
    
    if (!formData.get('ref_to')) {
        alert('Please select a branch to refer to');
        return;
    }
    
    if (!formData.get('ref_description')) {
        alert('Please provide a reason for referral');
        return;
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Submitting...';
    submitBtn.disabled = true;
    
    this.submit();
});

function editReferral(referralId) {
    fetch(`/medical-management/referrals/${referralId}/edit`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('referralForm').reset();
        document.getElementById('ref_id').value = referralId;
        document.getElementById('appointment_id').value = data.appointment_id;
        document.getElementById('ref_date').value = data.ref_date;
        document.getElementById('ref_to').value = data.ref_to;
        document.getElementById('ref_description').value = data.ref_description;
        document.getElementById('medical_history').value = data.medical_history || '';
        document.getElementById('tests_conducted').value = data.tests_conducted || '';
        document.getElementById('medications_given').value = data.medications_given || '';
        
        loadAppointmentDetails(data.appointment_id);
        
        document.getElementById('referralForm').action = `/medical-management/referrals/${referralId}`;
        document.getElementById('referralFormMethod').value = 'PUT';
        document.getElementById('referralModalTitle').textContent = 'Edit Referral';
        document.getElementById('referralModal').classList.remove('hidden');
    })
    .catch(error => {
        console.error('Error fetching referral data:', error);
        alert('Error loading referral data');
    });
}

function viewReferral(referralId) {
    fetch(`/medical-management/referrals/${referralId}`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Populate the form-style fields
        document.getElementById('view_ref_date').textContent = formatReferralDate(data.ref_date) || '-';
        document.getElementById('view_pet_name').textContent = (data.pet_name || '-').toUpperCase();
        document.getElementById('view_pet_gender').textContent = (data.pet_gender || '-').toUpperCase();
        document.getElementById('view_pet_dob').textContent = formatReferralDate(data.pet_dob) || '-';
        document.getElementById('view_owner_name').textContent = (data.owner_name || '-').toUpperCase();
        document.getElementById('view_owner_contact').textContent = data.owner_contact || '-';
        
        // Format medical history as a list
        document.getElementById('view_medical_history').innerHTML = formatListContent(data.medical_history, 'No medical history provided');
        
        // Format tests conducted
        document.getElementById('view_tests_conducted').innerHTML = formatListContent(data.tests_conducted, 'No tests documented');
        
        // Format medications given as a list
        document.getElementById('view_medications_given').innerHTML = formatListContent(data.medications_given, 'No medications documented');
        
        // Reason for referral
        document.getElementById('view_ref_description').textContent = data.ref_description || '-';
        
        // Branch information
        document.getElementById('view_ref_from_branch').textContent = (data.ref_by_branch || 'PETS 2GO VETERINARY CLINIC').toUpperCase();
        document.getElementById('view_ref_to_branch').textContent = (data.ref_to_branch || '-').toUpperCase();
        
        currentReferralId = referralId;
        document.getElementById('viewReferralModal').classList.remove('hidden');
    })
    .catch(error => {
        console.error('Error fetching referral details:', error);
        alert('Error loading referral details');
    });
}

function formatReferralDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).toUpperCase();
    } catch (e) {
        return dateString;
    }
}

function formatListContent(content, defaultText) {
    if (!content || content.trim() === '') {
        return `<em class="text-gray-500">${defaultText}</em>`;
    }
    
    // Split by common delimiters and create a list
    const items = content.split(/[;,\n]/).map(item => item.trim()).filter(item => item.length > 0);
    
    if (items.length > 1) {
        return '<ul class="history-list pl-0 list-none">' + 
               items.map(item => `<li class="mb-2 pl-5 relative before:content-['-'] before:absolute before:left-0 before:font-bold">${item}</li>`).join('') + 
               '</ul>';
    } else {
        return `<p class="mb-0">${content}</p>`;
    }
}

function printReferral(referralId) {
    // Fetch referral data for printing
    fetch(`/medical-management/referrals/${referralId}`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Create print content with the referral data
        const printContent = createReferralPrintContent(data);
        document.getElementById('printReferralContent').innerHTML = printContent;
        
        // Remove all print classes first
        document.getElementById('printContainer').classList.remove('print-prescription', 'print-referral');
        document.getElementById('printReferralContainer').classList.remove('print-prescription', 'print-referral');
        
        // Hide prescription container
        document.getElementById('printContainer').style.display = 'none';
        
        // Show referral container with print class
        const printContainer = document.getElementById('printReferralContainer');
        printContainer.style.display = 'block';
        printContainer.classList.add('print-referral');
        
        setTimeout(() => {
            window.print();
            printContainer.style.display = 'none';
            printContainer.classList.remove('print-referral');
        }, 200);
    })
    .catch(error => {
        console.error('Error fetching referral for printing:', error);
        alert('Error loading referral for printing');
    });
}

function createReferralPrintContent(data) {
    return `
        <!-- Header Section with Full Width Orange Background Container -->
        <div class="header mb-4 w-full">
            <div class="p-4 rounded-lg w-full" style="background-color: #f88e28;">
                <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" class="w-full h-auto object-contain" style="max-height: 120px; min-height: 80px;">
            </div>
        </div>
        
        <!-- Basic Information Section -->
        <div class="patient-info mb-3">
            <div class="grid grid-cols-3 gap-2 text-sm">
                <div>
                    <div class="mb-1"><strong>DATE:</strong> ${formatReferralDate(data.ref_date) || '-'}</div>
                    <div class="mb-1"><strong>NAME OF PET:</strong> ${(data.pet_name || '-').toUpperCase()}</div>
                </div>
                <div class="text-center">
                    <div><strong>OWNER:</strong> ${(data.owner_name || '-').toUpperCase()}</div>
                    <div><strong>CONTACT #:</strong> ${data.owner_contact || '-'}</div>
                </div>
                <div class="text-right">
                    <div><strong>DOB:</strong> ${formatReferralDate(data.pet_dob) || '-'}</div>
                    <div><strong>GENDER:</strong> ${(data.pet_gender || '-').toUpperCase()}</div>
                </div>
            </div>
        </div>
        
        <!-- History Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">HISTORY:</div>
            <div class="text-sm text-gray-700 mb-2">${formatListContent(data.medical_history, 'No medical history provided')}</div>
        </div>
        
        <!-- Test Conducted Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">TEST CONDUCTED:</div>
            <div class="text-sm text-gray-700 mb-2">${formatListContent(data.tests_conducted, 'No tests documented')}</div>
            <div class="test-note text-center font-bold text-gray-600 mt-2 text-sm italic">***NO FURTHER TESTS WERE PERFORMED***</div>
        </div>
        
        <!-- Medications Given Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">MEDS GIVEN:</div>
            <div class="text-sm text-gray-700 mb-2">${formatListContent(data.medications_given, 'No medications documented')}</div>
            <div class="med-note text-center font-bold text-gray-600 mt-2 text-sm italic">***NO OTHER MEDICATIONS GIVEN***</div>
        </div>
        
        <!-- Reason for Referral Section -->
        <div class="form-section mb-3">
            <div class="section-title font-bold text-sm text-gray-800 mb-2 border-b border-gray-300 pb-1">REASON FOR REFERRAL:</div>
            <div class="text-sm text-gray-700 mb-2">${data.ref_description || '-'}</div>
        </div>
        
        <!-- Referring Information Section -->
        <div class="form-section mb-3">
            <div class="referral-info bg-gray-100 p-3 rounded border-l-4 border-orange-500">
                <div class="section-title font-bold text-sm text-gray-800 mb-2">REFERRING VETERINARIAN:</div>
                <div class="vet-name text-base font-bold text-gray-800 mb-1">DR. JAN JERICK M. GO</div>
                <div class="clinic-details text-sm text-gray-600 mb-1">LIC. NO. 0012045</div>
                <div class="clinic-details text-sm text-gray-600 mb-1">${(data.ref_by_branch || 'PETS 2GO VETERINARY CLINIC').toUpperCase()}</div>
                <div class="clinic-details text-sm text-gray-600">0906-765-9732</div>
            </div>
        </div>
        
        <!-- Referred To Section -->
        <div class="form-section">
            <div class="referral-info bg-blue-50 p-3 rounded border-l-4 border-blue-500">
                <div class="section-title font-bold text-sm text-gray-800 mb-2">REFERRED TO:</div>
                <div class="clinic-details text-base font-bold text-gray-800 mb-1">${(data.ref_to_branch || '-').toUpperCase()}</div>
                <div class="clinic-details text-sm text-gray-600 mb-1">Specialist Veterinary Care</div>
                <div class="clinic-details text-sm text-gray-600">For specialized treatment and consultation</div>
            </div>
        </div>
    `;
}

function closeViewReferralModal() {
    document.getElementById('viewReferralModal').classList.add('hidden');
}

// Form submission debugging for appointments
document.getElementById('addForm').addEventListener('submit', function(e) {
    const formData = new FormData(this);
    const services = formData.getAll('services[]');
    
    if (services.length === 0) {
        console.warn('No services found in form data!');
    }
});

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupCSRF();
    
    // Set active tab based on server-side data or default to appointments
    const activeTab = '{{ $activeTab ?? "appointments" }}';
    showTab(activeTab);
    
    // Set today's date as default for referral date
    const today = new Date().toISOString().split('T')[0];
    if (document.getElementById('ref_date')) {
        document.getElementById('ref_date').value = today;
    }
    if (document.getElementById('prescription_date')) {
        document.getElementById('prescription_date').value = today;
    }
    
    // Add form validation for appointment form
    const appointmentForm = document.getElementById('addForm');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(e) {
            const petId = document.getElementById('pet_id').value;
            const appointDate = document.querySelector('input[name="appoint_date"]').value;
            const appointTime = document.querySelector('select[name="appoint_time"]').value;
            const appointStatus = document.querySelector('select[name="appoint_status"]').value;
            
            if (!petId) {
                e.preventDefault();
                alert('Please select a pet');
                return false;
            }
            
            if (!appointDate) {
                e.preventDefault();
                alert('Please select an appointment date');
                return false;
            }
            
            if (!appointTime) {
                e.preventDefault();
                alert('Please select an appointment time');
                return false;
            }
            
            if (!appointStatus) {
                e.preventDefault();
                alert('Please select an appointment status');
                return false;
            }
        });
    }
});

// Utility function to format dates
function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (e) {
        return dateString;
    }
}

// Utility function to show loading state
function showLoading(buttonElement, loadingText = 'Loading...') {
    if (!buttonElement) return;
    buttonElement.dataset.originalText = buttonElement.textContent;
    buttonElement.textContent = loadingText;
    buttonElement.disabled = true;
}

function hideLoading(buttonElement) {
    if (!buttonElement) return;
    buttonElement.textContent = buttonElement.dataset.originalText || 'Submit';
    buttonElement.disabled = false;
}

// Enhanced error handling for AJAX requests
function handleAjaxError(error, userMessage = 'An error occurred') {
    console.error('Error:', error);
    
    if (error.response) {
        error.response.json().then(data => {
            alert(data.message || userMessage);
        }).catch(() => {
            alert(userMessage);
        });
    } else {
        alert(userMessage);
    }
}

// Function to validate form data
function validateAppointmentForm(formData) {
    const required = ['pet_id', 'appoint_date', 'appoint_time', 'appoint_status', 'appoint_type'];
    const missing = required.filter(field => !formData.get(field));
    
    if (missing.length > 0) {
        alert(`Please fill in the following required fields: ${missing.join(', ')}`);
        return false;
    }
    
    return true;
}

function validatePrescriptionForm(medications, petId, prescriptionDate) {
    if (!petId) {
        alert('Please select a pet');
        return false;
    }
    
    if (!prescriptionDate) {
        alert('Please select a prescription date');
        return false;
    }
    
    if (medications.length === 0) {
        alert('Please add at least one medication');
        return false;
    }
    
    // Validate each medication has required fields
    for (let i = 0; i < medications.length; i++) {
        const med = medications[i];
        if (!med.product_name || !med.instructions) {
            alert(`Medication ${i + 1} is missing required information`);
            return false;
        }
    }
    
    return true;
}

function validateReferralForm(formData) {
    const required = ['appointment_id', 'ref_date', 'ref_to', 'ref_description'];
    const missing = required.filter(field => !formData.get(field));
    
    if (missing.length > 0) {
        alert(`Please fill in the following required fields: ${missing.join(', ')}`);
        return false;
    }
    
    return true;
}

// Auto-save functionality for forms (optional enhancement)
function enableAutoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    // Save form data to localStorage on input
    form.addEventListener('input', function(e) {
        if (e.target.type !== 'password') {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            localStorage.setItem(storageKey, JSON.stringify(data));
        }
    });
    
    // Restore form data on load
    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input && input.type !== 'password') {
                    input.value = data[key];
                }
            });
        } catch (e) {
            console.error('Error restoring form data:', e);
        }
    }
    
    // Clear saved data on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
    });
}

// Enhanced search functionality for modals
function setupModalSearch(inputSelector, resultsSelector, searchFunction) {
    const input = document.querySelector(inputSelector);
    const results = document.querySelector(resultsSelector);
    
    if (!input || !results) return;
    
    let searchTimeout;
    
    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            results.classList.add('hidden');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            searchFunction(query, results);
        }, 300);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.add('hidden');
        }
    });
}

// Keyboard navigation for modals
function setupKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Close any open modals
            const modals = [
                'addModal', 'editModal', 'serviceSelectionModal', 
                'prescriptionModal', 'viewPrescriptionModal', 
                'referralModal', 'viewReferralModal'
            ];
            
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                    if (modal.classList.contains('flex')) {
                        modal.classList.remove('flex');
                    }
                }
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    setupKeyboardNavigation();
    
});

// ==================== VIEW APPOINTMENT FUNCTIONS ====================

function viewAppointment(appointmentId) {
    const url = `/medical-management/appointments/${appointmentId}/view`;
    console.log('Fetching URL:', url);
    
    fetch(url, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response:', response);
        
        // Get the error text if response is not ok
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response:', text);
                throw new Error(`Server error: ${response.status} - ${text.substring(0, 100)}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Received data:', data);
        const appt = data.appointment;
        
        // ... rest of your existing code
        document.getElementById('view_appoint_date').textContent = 
            new Date(appt.appoint_date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        
        document.getElementById('view_appoint_time').textContent = 
            new Date('2000-01-01 ' + appt.appoint_time).toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        
        document.getElementById('view_appoint_type').textContent = appt.appoint_type || '-';
        
        const statusBadge = `<span class="px-3 py-1 rounded-full text-xs font-medium ${
            appt.appoint_status === 'completed' ? 'bg-green-100 text-green-800' : 
            appt.appoint_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
            appt.appoint_status === 'arrived' ? 'bg-blue-100 text-blue-800' :
            appt.appoint_status === 'refer' ? 'bg-purple-100 text-purple-800' : 
            'bg-gray-100 text-gray-800'
        }">
            ${appt.appoint_status.charAt(0).toUpperCase() + appt.appoint_status.slice(1)}
        </span>`;
        document.getElementById('view_appoint_status').innerHTML = statusBadge;
        
        document.getElementById('view_pet_name_appt').textContent = appt.pet?.pet_name || 'N/A';
        document.getElementById('view_owner_name_appt').textContent = appt.pet?.owner?.own_name || 'N/A';
        document.getElementById('view_owner_contact_appt').textContent = appt.pet?.owner?.own_contactnum || 'N/A';
        
        const servicesText = appt.services && appt.services.length > 0 
            ? appt.services.map(s => s.serv_name).join(', ')
            : 'No services assigned';
        document.getElementById('view_services').textContent = servicesText;
        
        document.getElementById('view_description').textContent = appt.appoint_description || 'No description provided';
        
        populateAppointmentHistory(data.history || []);
        
        document.getElementById('viewAppointmentModal').classList.remove('hidden');
    })
    .catch(error => {
        console.error('Full error:', error);
        alert('Error: ' + error.message);
    });
}

function populateAppointmentHistory(history) {
    const container = document.getElementById('appointment_history');
    
    if (!history || history.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-info-circle text-3xl mb-2"></i>
                <p>No history available for this appointment</p>
            </div>
        `;
        return;
    }
    
    // Reverse to show newest first
    const reversedHistory = [...history].reverse();
    
    container.innerHTML = reversedHistory.map((item, index) => {
        const isLast = index === reversedHistory.length - 1;
        const changeIcon = getChangeIcon(item.change_type);
        const changeColor = getChangeColor(item.change_type);
        
        return `
            <div class="relative ${isLast ? '' : 'pl-8 pb-6'}">
                ${!isLast ? '<div class="absolute left-3 top-8 bottom-0 w-0.5 bg-gray-300"></div>' : ''}
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full ${changeColor} flex items-center justify-center text-white shadow-md">
                        <i class="fas ${changeIcon} text-xs"></i>
                    </div>
                    <div class="flex-grow bg-white border rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2 flex-wrap gap-2">
                            <span class="font-semibold text-sm ${changeColor.replace('bg-', 'text-').replace('-500', '-700')} flex items-center gap-2">
                                ${formatChangeType(item.change_type)}
                            </span>
                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                <i class="fas fa-clock"></i>
                                ${formatDateTime(item.changed_at)}
                            </span>
                        </div>
                        ${formatChanges(item)}
                        <div class="mt-3 pt-3 border-t text-xs text-gray-600 flex items-center gap-2">
                            <i class="fas fa-user-circle"></i>
                            <span>Changed by: <strong>${item.changed_by}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getChangeIcon(changeType) {
    switch(changeType) {
        case 'created': return 'fa-plus-circle';
        case 'rescheduled': return 'fa-calendar-alt';
        case 'status_changed': return 'fa-exchange-alt';
        default: return 'fa-edit';
    }
}

function getChangeColor(changeType) {
    switch(changeType) {
        case 'created': return 'bg-green-500';
        case 'rescheduled': return 'bg-blue-500';
        case 'status_changed': return 'bg-purple-500';
        default: return 'bg-gray-500';
    }
}

function formatChangeType(changeType) {
    const types = {
        'created': 'Appointment Created',
        'rescheduled': 'Schedule Changed',
        'status_changed': 'Status Updated',
        'updated': 'Information Updated'
    };
    return types[changeType] || changeType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    try {
        return new Date(dateTimeString).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateTimeString;
    }
}

function formatChanges(item) {
    let html = '';
    
    if (item.old_data && Object.keys(item.old_data).length > 0) {
        html += '<div class="space-y-2">';
        
        // Date change
        if (item.old_data.date && item.new_data.date) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-calendar text-gray-400"></i>
                    <span class="text-red-600 line-through">${formatDate(item.old_data.date)}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium">${formatDate(item.new_data.date)}</span>
                </div>
            `;
        }
        
        // Time change
        if (item.old_data.time && item.new_data.time) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span class="text-red-600 line-through">${formatTime(item.old_data.time)}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium">${formatTime(item.new_data.time)}</span>
                </div>
            `;
        }
        
        // Status change
        if (item.old_data.status && item.new_data.status) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-flag text-gray-400"></i>
                    <span class="text-red-600 line-through capitalize">${item.old_data.status}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium capitalize">${item.new_data.status}</span>
                </div>
            `;
        }
        
        // Type change
        if (item.old_data.type && item.new_data.type) {
            html += `
                <div class="flex items-center gap-2 text-sm bg-gray-50 p-2 rounded">
                    <i class="fas fa-tag text-gray-400"></i>
                    <span class="text-red-600 line-through capitalize">${item.old_data.type}</span>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                    <span class="text-green-600 font-medium capitalize">${item.new_data.type}</span>
                </div>
            `;
        }
        
        html += '</div>';
    } else if (item.new_data) {
        // Initial creation
        html += '<div class="text-sm text-gray-700 space-y-1 bg-green-50 p-3 rounded">';
        html += '<div class="font-medium text-green-700 mb-2">Initial Appointment Details:</div>';
        
        if (item.new_data.date) {
            html += `<div><i class="fas fa-calendar mr-2 text-gray-500"></i>Date: <strong>${formatDate(item.new_data.date)}</strong></div>`;
        }
        if (item.new_data.time) {
            html += `<div><i class="fas fa-clock mr-2 text-gray-500"></i>Time: <strong>${formatTime(item.new_data.time)}</strong></div>`;
        }
        if (item.new_data.status) {
            html += `<div><i class="fas fa-flag mr-2 text-gray-500"></i>Status: <strong class="capitalize">${item.new_data.status}</strong></div>`;
        }
        if (item.new_data.type) {
            html += `<div><i class="fas fa-tag mr-2 text-gray-500"></i>Type: <strong class="capitalize">${item.new_data.type}</strong></div>`;
        }
        
        html += '</div>';
    } else {
        html += `<div class="text-sm text-gray-600 italic">${item.notes || 'No details available'}</div>`;
    }
    
    return html;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        return new Date(dateString).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    } catch (e) {
        return dateString;
    }
}

function formatTime(timeString) {
    if (!timeString) return '-';
    try {
        return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    } catch (e) {
        return timeString;
    }
}

function closeViewAppointmentModal() {
    document.getElementById('viewAppointmentModal').classList.add('hidden');
}
</script>

@endsection