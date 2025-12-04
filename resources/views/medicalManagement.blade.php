@extends('AdminBoard')

<script src="{{ asset('js/list-filter-new.js') }}"></script>

@php
    $userRole = strtolower(auth()->user()->user_role ?? '');
    
    // Define permissions for each role
    $permissions = [
        'superadmin' => [
            'view_appointments' => true,
            'add_appointment' => false,
            'edit_appointment' => false,
            'delete_appointment' => false,
        ],
        'veterinarian' => [
            'view_appointments' => true,
            'add_appointment' => false,
            'edit_appointment' => true,
            'delete_appointment' => false,
            'view_vaccinations' => true,
            'edit_vaccinations' => true,
        ],
        'receptionist' => [
            'view_appointments' => true,
            'add_appointment' => true,
            'edit_appointment' => true,
            'delete_appointment' => true,
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
<div class="min-h-screen bg-gray-50">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto bg-white p-3 sm:p-4 md:p-6 rounded-lg shadow-sm">
        
        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-4 sm:mb-6 overflow-x-auto">
            <nav class="-mb-px flex space-x-2 sm:space-x-4 md:space-x-6 items-center" style="min-width: max-content;">
                @if(in_array($userRole, ['receptionist', 'veterinarian', 'superadmin']))
                <button onclick="showTab('visits')" id="visits-tab" 
                    class="tab-button py-2 px-1 sm:px-2 border-b-2 font-medium text-sm sm:text-base active whitespace-nowrap">
                    <span class="font-bold text-lg sm:text-xl">Visits</span>
                </button>
                @endif
                
                @if($userRole === 'receptionist' || $userRole === 'superadmin')
                <button onclick="showTab('grooming')" id="grooming-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Grooming</h2>
                    @if(isset($pendingCounts['grooming']) && $pendingCounts['grooming'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['grooming'] }}
                        </span>
                    @endif
                </button>
                @endif
                
                <button onclick="showTab('boarding')" id="boarding-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Boarding</h2>
                    @if(isset($pendingCounts['boarding']) && $pendingCounts['boarding'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['boarding'] }}
                        </span>
                    @endif
                </button>
                
                @if($userRole === 'veterinarian')
                <button onclick="showTab('checkup')" id="checkup-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Check-up</h2>
                    @if(isset($pendingCounts['checkup']) && $pendingCounts['checkup'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['checkup'] }}
                        </span>
                    @endif
                </button>
                <button onclick="showTab('vaccination')" id="vaccination-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Vaccination</h2>
                    @if(isset($pendingCounts['vaccination']) && $pendingCounts['vaccination'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['vaccination'] }}
                        </span>
                    @endif
                </button>
                <button onclick="showTab('deworming')" id="deworming-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Deworming</h2>
                    @if(isset($pendingCounts['deworming']) && $pendingCounts['deworming'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['deworming'] }}
                        </span>
                    @endif
                </button>
                <button onclick="showTab('surgical')" id="surgical-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Surgical</h2>
                    @if(isset($pendingCounts['surgical']) && $pendingCounts['surgical'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['surgical'] }}
                        </span>
                    @endif
                </button>
                <button onclick="showTab('diagnostics')" id="diagnostics-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Diagnostics</h2>
                    @if(isset($pendingCounts['diagnostics']) && $pendingCounts['diagnostics'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['diagnostics'] }}
                        </span>
                    @endif
                </button>
                <button onclick="showTab('emergency')" id="emergency-tab" 
                    class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-600 hover:text-gray-900 hover:border-gray-300 relative">
                    <h2 class="font-bold text-xl">Emergency</h2>
                    @if(isset($pendingCounts['emergency']) && $pendingCounts['emergency'] > 0)
                        <span class="absolute -top-1 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center" style="border-radius: 50%;">
                            {{ $pendingCounts['emergency'] }}
                        </span>
                    @endif
                </button>
                @endif
            </nav>
        </div>

        <!-- ==================== APPOINTMENTS TAB ==================== -->
        <div id="appointmentsContent" class="tab-content hidden">
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
            </div>
            <br>

            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Type</th>
                            <th class="border px-4 py-2">Time</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Contact</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $index => $appointment)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($appointments, 'firstItem'))
                                        {{ $appointments->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($appointment->appoint_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">{{ $appointment->appoint_type }}</td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($appointment->appoint_time)->format('h:i A') }}</td>
                                <td class="border px-4 py-2">{{ $appointment->pet?->pet_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $appointment->pet?->owner?->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $appointment->pet?->owner?->own_contactnum }}</td>
                                <td class="border px-4 py-2">
                                    <span class="px-2 py-1 rounded text-xs
                                        @if(in_array(strtolower($appointment->appoint_status), ['complete','completed','arrive','arrived'])) bg-green-100 text-green-700
                                        @elseif(in_array(strtolower($appointment->appoint_status), ['pending'])) bg-yellow-100 text-yellow-700
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ ucfirst($appointment->appoint_status) }}
                                    </span>
                                </td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button onclick="viewAppointment({{ $appointment->appoint_id }})"
                                            class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs" title="view">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-gray-500 py-4">No appointments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($appointments) && method_exists($appointments, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $appointments->firstItem() ?? 0 }} to {{ $appointments->lastItem() ?? 0 }} of
                    {{ $appointments->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($appointments->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $appointments->appends(['active_tab' => 'appointments'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $appointments->lastPage(); $i++)
                        @if ($i == $appointments->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $appointments->appends(['active_tab' => 'appointments'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($appointments->hasMorePages())
                        <a href="{{ $appointments->appends(['active_tab' => 'appointments'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-500 text-white p-2 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded text-sm">
                <ul class="list-disc pl-5">
                    @if(is_object($errors) && method_exists($errors, 'all'))
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    @endif
                </ul>
            </div>
        @endif

        <div id="visitsContent" class="tab-content">
            <!-- Show Entries and Search -->
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="active_tab" value="visits">
                    <label for="visitPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="visitPerPage" id="visitPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('visitPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                    <select id="visitsStatus" class="border border-gray-400 rounded px-2 py-1 text-sm ml-2">
                        <option value="All">All Status</option>
                        <option value="Arrived">Arrived</option>
                        <option value="Billed">Billed</option>
                        <option value="Pending">Pending</option>
                        <option value="Complete">Complete</option>
                        <option value="Completed">Completed</option>
                    </select>
                </form>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <input type="search" id="visitsSearch" placeholder="Search visits..." class="border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                   
                </div>
                @if(auth()->check() && in_array(auth()->user()->user_role, ['receptionist']))
                <button onclick="openAddVisitModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86] whitespace-nowrap ml-2">
                    + Add Visit
                </button>
                @endif
            </div>
            <br>

            <div class="overflow-x-auto">
                <table id="visitsTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Species</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Weight</th>
                            <th class="border px-4 py-2">Temp</th>
                            <th class="border px-4 py-2">Patient Type</th>
                            <th class="border px-4 py-2">Service Type</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($visits ?? []) as $index => $visit)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($visits, 'firstItem'))
                                        {{ $visits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($visit->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $visit->pet->pet_name ?? 'N/A' }}
                                    @php
                                        $visitSource = $visit->visit_source ?? 'walk-in';
                                    @endphp
                                    @if($visitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                            <i class="fas fa-exchange-alt mr-1"></i> Referral Visit
                                        </span>
                                    @elseif($visitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment
                                        </span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">
                                    @php
                                        $s = strtolower($visit->pet->pet_species ?? '');
                                    @endphp
                                    @if($s === 'cat')
                                        <i class="fas fa-cat" title="Cat"></i>
                                    @elseif($s === 'dog')
                                        <i class="fas fa-dog" title="Dog"></i>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $visit->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $visit->weight ? number_format($visit->weight, 2) . ' kg' : 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $visit->temperature ? number_format($visit->temperature, 1) . ' °C' : 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ is_object($visit->patient_type) && method_exists($visit->patient_type, 'value') ? $visit->patient_type->value : $visit->patient_type }}</td>
                                <td class="border px-4 py-2">
                                    @php
                                        $__types = [];
                                        if ($visit->services && method_exists($visit->services, 'count') && $visit->services->count() > 0) {
                                            $__types = $visit->services->pluck('serv_type')->filter()->map(function($t){ return strtolower(trim($t)); })->unique()->values()->all();
                                        }
                                        $__summary = $visit->visit_service_type ?? ($visit->service_type ?? ($visit->serv_type ?? null));
                                    @endphp
                                    {{ !empty($__types) ? implode(', ', $__types) : ($__summary ?: '-') }}
                                </td>
                                <td class="border px-4 py-2">
                                    <span class="px-2 py-1 rounded text-xs
                                        @if(in_array(strtolower($visit->visit_status), ['complete','completed','arrive','arrived'])) bg-green-100 text-green-700
                                        @elseif(isset($visit->services) && $visit->services->where('serv_type', 'pending')->count() > 0) bg-yellow-100 text-yellow-700
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ ucfirst($visit->visit_status) ?? '-' }}
                                    </span>
                                </td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $visit->visit_id }})" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if(auth()->check() && in_array(auth()->user()->user_role, ['veterinarian']) && !in_array(auth()->user()->user_role, ['super_admin']))
                                        <a href="{{ route('medical.visits.perform', ['id' => $visit->visit_id]) }}" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        @endif
                                        <button type="button" onclick="openInitialAssessment({{ $visit->visit_id }}, {{ $visit->pet_id ?? 0 }}, '{{ $visit->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists for this visit - use pres_visit_id as primary link
                                            $visitPrescription = \App\Models\Prescription::where('pres_visit_id', $visit->visit_id)->first();
                                            // Fallback: check by pet_id and date if no visit_id link
                                            if (!$visitPrescription && $visit->pet_id) {
                                                $visitPrescription = \App\Models\Prescription::where('pet_id', $visit->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($visit->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($visitPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $visitPrescription->prescription_id }}"
                                            data-pet="{{ $visitPrescription->pet->pet_name }}"
                                            data-species="{{ $visitPrescription->pet->pet_species }}"
                                            data-breed="{{ $visitPrescription->pet->pet_breed }}"
                                            data-weight="{{ $visitPrescription->pet->pet_weight }}"
                                            data-age="{{ $visitPrescription->pet->pet_age }}"
                                            data-temp="{{ $visitPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $visitPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($visitPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $visitPrescription->medication }}"
                                            data-differential-diagnosis="{{ $visitPrescription->differential_diagnosis }}"
                                            data-notes="{{ $visitPrescription->notes }}"
                                            data-branch-name="{{ $visitPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $visitPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $visitPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                        @if(hasPermission('edit_appointment', $can))
                                        <button onclick="openEditVisitModal({{ $visit->visit_id }}, false)" class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] text-xs" title="edit">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        @endif
                                        @if(hasPermission('delete_appointment', $can))
                                        <form action="{{ route('medical.visits.destroy', $visit->visit_id) }}" method="POST" onsubmit="return confirm('Delete this visit?');" class="inline mb-0">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="active_tab" value="visits">
                                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs" title="delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-gray-500 py-4">No visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div id="visitsPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
        </div>

        <div id="checkupContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="active_tab" value="checkup">
                    <label for="checkupPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="checkupPerPage" id="checkupPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('checkupPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                    <select id="checkupStatus" class="border border-gray-400 rounded px-2 py-1 text-sm ml-2">
                        <option value="All">All Status</option>
                        <option value="Arrived">Arrived</option>
                        <option value="Billed">Billed</option>
                        <option value="Pending">Pending</option>
                        <option value="Complete">Complete</option>
                        <option value="Completed">Completed</option>
                    </select>
                </form>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative">
                        <input type="search" id="checkupSearch" placeholder="Search check-up visits..." class="border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                        <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="checkupTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $cList = $consultationVisits ?? collect(); @endphp
                        @forelse($cList as $index => $c)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($consultationVisits, 'firstItem'))
                                        {{ $consultationVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($c->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $c->pet->pet_name ?? 'N/A' }}
                                    @php $cVisitSource = $c->visit_source ?? 'walk-in'; @endphp
                                    @if($cVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($cVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $c->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $c->workflow_status ?? ($c->visit_status ?? '-') }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $c->visit_id }}, 'check-up')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $c->visit_id]) }}?type=check-up" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $c->visit_id }}, {{ $c->pet_id ?? 0 }}, '{{ $c->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $checkupPrescription = \App\Models\Prescription::where('pres_visit_id', $c->visit_id)->first();
                                            if (!$checkupPrescription && $c->pet_id) {
                                                $checkupPrescription = \App\Models\Prescription::where('pet_id', $c->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($c->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($checkupPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $checkupPrescription->prescription_id }}"
                                            data-pet="{{ $checkupPrescription->pet->pet_name }}"
                                            data-species="{{ $checkupPrescription->pet->pet_species }}"
                                            data-breed="{{ $checkupPrescription->pet->pet_breed }}"
                                            data-weight="{{ $checkupPrescription->pet->pet_weight }}"
                                            data-age="{{ $checkupPrescription->pet->pet_age }}"
                                            data-temp="{{ $checkupPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $checkupPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($checkupPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $checkupPrescription->medication }}"
                                            data-differential-diagnosis="{{ $checkupPrescription->differential_diagnosis }}"
                                            data-notes="{{ $checkupPrescription->notes }}"
                                            data-branch-name="{{ $checkupPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $checkupPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $checkupPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No consultation visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="checkupPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($consultationVisits) && method_exists($consultationVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $consultationVisits->firstItem() ?? 0 }} to {{ $consultationVisits->lastItem() ?? 0 }} of
                    {{ $consultationVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($consultationVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $consultationVisits->appends(['active_tab' => 'checkup'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $consultationVisits->lastPage(); $i++)
                        @if ($i == $consultationVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $consultationVisits->appends(['active_tab' => 'checkup'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($consultationVisits->hasMorePages())
                        <a href="{{ $consultationVisits->appends(['active_tab' => 'checkup'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="dewormingContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="deworming">
                    <label for="dewormingVisitsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="dewormingVisitsPerPage" id="dewormingVisitsPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('dewormingVisitsPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="dewormingSearch" placeholder="Search deworming visits..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="dewormingTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $dwList = $dewormingVisits ?? collect(); @endphp
                        @forelse($dwList as $index => $d)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($dewormingVisits, 'firstItem'))
                                        {{ $dewormingVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($d->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $d->pet->pet_name ?? 'N/A' }}
                                    @php $dVisitSource = $d->visit_source ?? 'walk-in'; @endphp
                                    @if($dVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($dVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $d->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $d->workflow_status ?? ($d->visit_status ?? '-') }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $d->visit_id }}, 'deworming')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $d->visit_id]) }}?type=deworming" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $d->visit_id }}, {{ $d->pet_id ?? 0 }}, '{{ $d->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $dewormPrescription = \App\Models\Prescription::where('pres_visit_id', $d->visit_id)->first();
                                            if (!$dewormPrescription && $d->pet_id) {
                                                $dewormPrescription = \App\Models\Prescription::where('pet_id', $d->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($d->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($dewormPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $dewormPrescription->prescription_id }}"
                                            data-pet="{{ $dewormPrescription->pet->pet_name }}"
                                            data-species="{{ $dewormPrescription->pet->pet_species }}"
                                            data-breed="{{ $dewormPrescription->pet->pet_breed }}"
                                            data-weight="{{ $dewormPrescription->pet->pet_weight }}"
                                            data-age="{{ $dewormPrescription->pet->pet_age }}"
                                            data-temp="{{ $dewormPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $dewormPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($dewormPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $dewormPrescription->medication }}"
                                            data-differential-diagnosis="{{ $dewormPrescription->differential_diagnosis }}"
                                            data-notes="{{ $dewormPrescription->notes }}"
                                            data-branch-name="{{ $dewormPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $dewormPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $dewormPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No deworming visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="dewormingPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($dewormingVisits) && method_exists($dewormingVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $dewormingVisits->firstItem() ?? 0 }} to {{ $dewormingVisits->lastItem() ?? 0 }} of
                    {{ $dewormingVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($dewormingVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $dewormingVisits->appends(['active_tab' => 'deworming'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $dewormingVisits->lastPage(); $i++)
                        @if ($i == $dewormingVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $dewormingVisits->appends(['active_tab' => 'deworming'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($dewormingVisits->hasMorePages())
                        <a href="{{ $dewormingVisits->appends(['active_tab' => 'deworming'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="diagnosticsContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="diagnostics">
                    <label for="diagnosticsVisitsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="diagnosticsVisitsPerPage" id="diagnosticsVisitsPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('diagnosticsVisitsPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="diagnosticsSearch" placeholder="Search diagnostics visits..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="diagnosticsTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $dxList = $diagnosticsVisits ?? collect(); @endphp
                        @forelse($dxList as $index => $d)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($diagnosticsVisits, 'firstItem'))
                                        {{ $diagnosticsVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($d->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $d->pet->pet_name ?? 'N/A' }}
                                    @php $dxVisitSource = $d->visit_source ?? 'walk-in'; @endphp
                                    @if($dxVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($dxVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $d->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $d->workflow_status ?? ($d->visit_status ?? '-') }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $d->visit_id }}, 'diagnostics')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $d->visit_id]) }}?type=diagnostic" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $d->visit_id }}, {{ $d->pet_id ?? 0 }}, '{{ $d->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $diagPrescription = \App\Models\Prescription::where('pres_visit_id', $d->visit_id)->first();
                                            if (!$diagPrescription && $d->pet_id) {
                                                $diagPrescription = \App\Models\Prescription::where('pet_id', $d->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($d->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($diagPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $diagPrescription->prescription_id }}"
                                            data-pet="{{ $diagPrescription->pet->pet_name }}"
                                            data-species="{{ $diagPrescription->pet->pet_species }}"
                                            data-breed="{{ $diagPrescription->pet->pet_breed }}"
                                            data-weight="{{ $diagPrescription->pet->pet_weight }}"
                                            data-age="{{ $diagPrescription->pet->pet_age }}"
                                            data-temp="{{ $diagPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $diagPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($diagPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $diagPrescription->medication }}"
                                            data-differential-diagnosis="{{ $diagPrescription->differential_diagnosis }}"
                                            data-notes="{{ $diagPrescription->notes }}"
                                            data-branch-name="{{ $diagPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $diagPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $diagPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No diagnostics visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="diagnosticsPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($diagnosticsVisits) && method_exists($diagnosticsVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $diagnosticsVisits->firstItem() ?? 0 }} to {{ $diagnosticsVisits->lastItem() ?? 0 }} of
                    {{ $diagnosticsVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($diagnosticsVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $diagnosticsVisits->appends(['active_tab' => 'diagnostics'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $diagnosticsVisits->lastPage(); $i++)
                        @if ($i == $diagnosticsVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $diagnosticsVisits->appends(['active_tab' => 'diagnostics'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($diagnosticsVisits->hasMorePages())
                        <a href="{{ $diagnosticsVisits->appends(['active_tab' => 'diagnostics'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="surgicalContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="surgical">
                    <label for="surgicalVisitsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="surgicalVisitsPerPage" id="surgicalVisitsPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('surgicalVisitsPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="surgicalSearch" placeholder="Search surgical visits..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="surgicalTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sxList = $surgicalVisits ?? collect(); @endphp
                        @forelse($sxList as $index => $s)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($surgicalVisits, 'firstItem'))
                                        {{ $surgicalVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($s->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $s->pet->pet_name ?? 'N/A' }}
                                    @php $sxVisitSource = $s->visit_source ?? 'walk-in'; @endphp
                                    @if($sxVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($sxVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $s->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $s->workflow_status ?? ($s->visit_status ?? '-') }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $s->visit_id }}, 'surgical')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $s->visit_id]) }}?type=surgical" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $s->visit_id }}, {{ $s->pet_id ?? 0 }}, '{{ $s->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $surgPrescription = \App\Models\Prescription::where('pres_visit_id', $s->visit_id)->first();
                                            if (!$surgPrescription && $s->pet_id) {
                                                $surgPrescription = \App\Models\Prescription::where('pet_id', $s->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($s->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($surgPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $surgPrescription->prescription_id }}"
                                            data-pet="{{ $surgPrescription->pet->pet_name }}"
                                            data-species="{{ $surgPrescription->pet->pet_species }}"
                                            data-breed="{{ $surgPrescription->pet->pet_breed }}"
                                            data-weight="{{ $surgPrescription->pet->pet_weight }}"
                                            data-age="{{ $surgPrescription->pet->pet_age }}"
                                            data-temp="{{ $surgPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $surgPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($surgPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $surgPrescription->medication }}"
                                            data-differential-diagnosis="{{ $surgPrescription->differential_diagnosis }}"
                                            data-notes="{{ $surgPrescription->notes }}"
                                            data-branch-name="{{ $surgPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $surgPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $surgPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No surgical visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="surgicalPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($surgicalVisits) && method_exists($surgicalVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $surgicalVisits->firstItem() ?? 0 }} to {{ $surgicalVisits->lastItem() ?? 0 }} of
                    {{ $surgicalVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($surgicalVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $surgicalVisits->appends(['active_tab' => 'surgical'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $surgicalVisits->lastPage(); $i++)
                        @if ($i == $surgicalVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $surgicalVisits->appends(['active_tab' => 'surgical'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($surgicalVisits->hasMorePages())
                        <a href="{{ $surgicalVisits->appends(['active_tab' => 'surgical'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="emergencyContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="emergency">
                    <label for="emergencyVisitsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="emergencyVisitsPerPage" id="emergencyVisitsPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('emergencyVisitsPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="emergencySearch" placeholder="Search emergency visits..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="emergencyTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $eList = $emergencyVisits ?? collect(); @endphp
                        @forelse($eList as $index => $e)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($emergencyVisits, 'firstItem'))
                                        {{ $emergencyVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($e->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $e->pet->pet_name ?? 'N/A' }}
                                    @php $emVisitSource = $e->visit_source ?? 'walk-in'; @endphp
                                    @if($emVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($emVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $e->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $e->workflow_status ?? ($e->visit_status ?? '-') }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $e->visit_id }}, 'emergency')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $e->visit_id]) }}?type=emergency" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $e->visit_id }}, {{ $e->pet_id ?? 0 }}, '{{ $e->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $emergPrescription = \App\Models\Prescription::where('pres_visit_id', $e->visit_id)->first();
                                            if (!$emergPrescription && $e->pet_id) {
                                                $emergPrescription = \App\Models\Prescription::where('pet_id', $e->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($e->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($emergPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $emergPrescription->prescription_id }}"
                                            data-pet="{{ $emergPrescription->pet->pet_name }}"
                                            data-species="{{ $emergPrescription->pet->pet_species }}"
                                            data-breed="{{ $emergPrescription->pet->pet_breed }}"
                                            data-weight="{{ $emergPrescription->pet->pet_weight }}"
                                            data-age="{{ $emergPrescription->pet->pet_age }}"
                                            data-temp="{{ $emergPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $emergPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($emergPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $emergPrescription->medication }}"
                                            data-differential-diagnosis="{{ $emergPrescription->differential_diagnosis }}"
                                            data-notes="{{ $emergPrescription->notes }}"
                                            data-branch-name="{{ $emergPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $emergPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $emergPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No emergency visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="emergencyPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($emergencyVisits) && method_exists($emergencyVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $emergencyVisits->firstItem() ?? 0 }} to {{ $emergencyVisits->lastItem() ?? 0 }} of
                    {{ $emergencyVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($emergencyVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $emergencyVisits->appends(['active_tab' => 'emergency'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $emergencyVisits->lastPage(); $i++)
                        @if ($i == $emergencyVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $emergencyVisits->appends(['active_tab' => 'emergency'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($emergencyVisits->hasMorePages())
                        <a href="{{ $emergencyVisits->appends(['active_tab' => 'emergency'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="vaccinationContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="vaccination">
                    <label for="vaccinationVisitsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="vaccinationVisitsPerPage" id="vaccinationVisitsPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('vaccinationVisitsPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="vaccinationSearch" placeholder="Search vaccination visits..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="vaccinationTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $vaccList = $vaccinationVisits ?? collect(); @endphp
                        @forelse($vaccList as $index => $v)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($vaccinationVisits, 'firstItem'))
                                        {{ $vaccinationVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($v->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $v->pet->pet_name ?? 'N/A' }}
                                    @php $vaccVisitSource = $v->visit_source ?? 'walk-in'; @endphp
                                    @if($vaccVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($vaccVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $v->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $v->workflow_status ?? ($v->visit_status ?? '-') }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $v->visit_id }}, 'vaccination')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $v->visit_id]) }}?type=vaccination" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $v->visit_id }}, {{ $v->pet_id ?? 0 }}, '{{ $v->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $vaccPrescription = \App\Models\Prescription::where('pres_visit_id', $v->visit_id)->first();
                                            if (!$vaccPrescription && $v->pet_id) {
                                                $vaccPrescription = \App\Models\Prescription::where('pet_id', $v->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($v->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($vaccPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $vaccPrescription->prescription_id }}"
                                            data-pet="{{ $vaccPrescription->pet->pet_name }}"
                                            data-species="{{ $vaccPrescription->pet->pet_species }}"
                                            data-breed="{{ $vaccPrescription->pet->pet_breed }}"
                                            data-weight="{{ $vaccPrescription->pet->pet_weight }}"
                                            data-age="{{ $vaccPrescription->pet->pet_age }}"
                                            data-temp="{{ $vaccPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $vaccPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($vaccPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $vaccPrescription->medication }}"
                                            data-differential-diagnosis="{{ $vaccPrescription->differential_diagnosis }}"
                                            data-notes="{{ $vaccPrescription->notes }}"
                                            data-branch-name="{{ $vaccPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $vaccPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $vaccPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No vaccination visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="vaccinationPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($vaccinationVisits) && method_exists($vaccinationVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $vaccinationVisits->firstItem() ?? 0 }} to {{ $vaccinationVisits->lastItem() ?? 0 }} of
                    {{ $vaccinationVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($vaccinationVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $vaccinationVisits->appends(['active_tab' => 'vaccination'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $vaccinationVisits->lastPage(); $i++)
                        @if ($i == $vaccinationVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $vaccinationVisits->appends(['active_tab' => 'vaccination'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($vaccinationVisits->hasMorePages())
                        <a href="{{ $vaccinationVisits->appends(['active_tab' => 'vaccination'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="groomingContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="grooming">
                    <label for="groomingVisitsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="groomingVisitsPerPage" id="groomingVisitsPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('groomingVisitsPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="groomingSearch" placeholder="Search grooming visits..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="groomingTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $gList = $groomingVisits ?? collect(); @endphp
                        @forelse($gList as $index => $g)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($groomingVisits, 'firstItem'))
                                        {{ $groomingVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($g->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $g->pet->pet_name ?? 'N/A' }}
                                    @php $groomVisitSource = $g->visit_source ?? 'walk-in'; @endphp
                                    @if($groomVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($groomVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $g->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">{{ $g->workflow_status ?? ($g->visit_status ?? '-') }}</td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $g->visit_id }}, 'grooming')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $g->visit_id]) }}?type=grooming" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $g->visit_id }}, {{ $g->pet_id ?? 0 }}, '{{ $g->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $groomPrescription = \App\Models\Prescription::where('pres_visit_id', $g->visit_id)->first();
                                            if (!$groomPrescription && $g->pet_id) {
                                                $groomPrescription = \App\Models\Prescription::where('pet_id', $g->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($g->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($groomPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $groomPrescription->prescription_id }}"
                                            data-pet="{{ $groomPrescription->pet->pet_name }}"
                                            data-species="{{ $groomPrescription->pet->pet_species }}"
                                            data-breed="{{ $groomPrescription->pet->pet_breed }}"
                                            data-weight="{{ $groomPrescription->pet->pet_weight }}"
                                            data-age="{{ $groomPrescription->pet->pet_age }}"
                                            data-temp="{{ $groomPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $groomPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($groomPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $groomPrescription->medication }}"
                                            data-differential-diagnosis="{{ $groomPrescription->differential_diagnosis }}"
                                            data-notes="{{ $groomPrescription->notes }}"
                                            data-branch-name="{{ $groomPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $groomPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $groomPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No grooming visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="groomingPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($groomingVisits) && method_exists($groomingVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $groomingVisits->firstItem() ?? 0 }} to {{ $groomingVisits->lastItem() ?? 0 }} of
                    {{ $groomingVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($groomingVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $groomingVisits->appends(['active_tab' => 'grooming'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $groomingVisits->lastPage(); $i++)
                        @if ($i == $groomingVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $groomingVisits->appends(['active_tab' => 'grooming'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($groomingVisits->hasMorePages())
                        <a href="{{ $groomingVisits->appends(['active_tab' => 'grooming'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="boardingContent" class="tab-content hidden">
            <div class="flex flex-nowrap items-center justify-between gap-3 mt-4 text-sm font-semibold text-black w-full overflow-x-auto pb-2">
                <form method="GET" action="{{ route('medical.index') }}" class="flex-shrink-0 flex items-center space-x-2">
                    <input type="hidden" name="tab" value="boarding">
                    <label for="boardingVisitsPerPage" class="whitespace-nowrap text-sm text-black">Show</label>
                    <select name="boardingVisitsPerPage" id="boardingVisitsPerPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1.5 text-sm">
                        @foreach ([10, 20, 50, 100, 'all'] as $limit)
                            <option value="{{ $limit }}" {{ request('boardingVisitsPerPage') == $limit ? 'selected' : '' }}>
                                {{ $limit === 'all' ? 'All' : $limit }}
                            </option>
                        @endforeach
                    </select>
                    <span class="whitespace-nowrap">entries</span>
                </form>
                <div class="relative flex-1 min-w-[200px] max-w-xs">
                    <input type="search" id="boardingSearch" placeholder="Search boarding visits..." class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm pl-8">
                    <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            <br>
            <div class="overflow-x-auto">
                <table id="boardingTable" class="w-full table-auto text-sm border text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-2">#</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Pet</th>
                            <th class="border px-4 py-2">Owner</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $bList = $boardingVisits ?? collect(); @endphp
                        @forelse($bList as $index => $b)
                            <tr>
                                <td class="border px-2 py-2">
                                    @if(method_exists($boardingVisits, 'firstItem'))
                                        {{ $boardingVisits->firstItem() + $index }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($b->visit_date)->format('F j, Y') }}</td>
                                <td class="border px-4 py-2">
                                    {{ $b->pet->pet_name ?? 'N/A' }}
                                    @php $boardVisitSource = $b->visit_source ?? 'walk-in'; @endphp
                                    @if($boardVisitSource === 'referral')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"><i class="fas fa-exchange-alt mr-1"></i> Referral Visit</span>
                                    @elseif($boardVisitSource === 'appointment')
                                        <br><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"><i class="fas fa-calendar-check mr-1"></i> Follow-up Appointment</span>
                                    @endif
                                </td>
                                <td class="border px-4 py-2">{{ $b->pet->owner->own_name ?? 'N/A' }}</td>
                                <td class="border px-4 py-2">
                                    @php
                                        $boardingStatus = null;
                                        if (isset($b->boardingRecord) && $b->boardingRecord && isset($b->boardingRecord->status)) {
                                            $boardingStatus = $b->boardingRecord->status;
                                        } elseif (isset($b->status)) {
                                            $boardingStatus = $b->status;
                                        }
                                    @endphp
                                    @if(strtolower($boardingStatus) === 'checked in')
                                        <span class="text-green-700 font-bold">Checked In</span>
                                    @elseif(strtolower($boardingStatus) === 'checked out')
                                        <span class="text-red-700 font-bold">Checked Out</span>
                                    @else
                                        {{ $b->workflow_status ?? $b->visit_status ?? '-' }}
                                    @endif
                                </td>
                                <td class="border px-2 py-1">
                                    <div class="flex justify-center items-center gap-1">
                                        <button type="button" onclick="openViewVisitModal({{ $b->visit_id }}, 'boarding')" class="bg-purple-500 text-white px-2 py-1 rounded hover:bg-purple-600 text-xs" title="view details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="{{ route('medical.visits.perform', ['id' => $b->visit_id]) }}?type=boarding" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs" title="attend">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                        <button type="button" onclick="openInitialAssessment({{ $b->visit_id }}, {{ $b->pet_id ?? 0 }}, '{{ $b->pet->owner->own_id ?? '' }}')" class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 text-xs" title="initial assessment">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                        @php
                                            // Check if prescription exists - use pres_visit_id as primary link
                                            $boardPrescription = \App\Models\Prescription::where('pres_visit_id', $b->visit_id)->first();
                                            if (!$boardPrescription && $b->pet_id) {
                                                $boardPrescription = \App\Models\Prescription::where('pet_id', $b->pet_id)
                                                    ->whereDate('prescription_date', \Carbon\Carbon::parse($b->visit_date))
                                                    ->first();
                                            }
                                        @endphp
                                        @if($boardPrescription)
                                        <button onclick="directPrintPrescription(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                            data-id="{{ $boardPrescription->prescription_id }}"
                                            data-pet="{{ $boardPrescription->pet->pet_name }}"
                                            data-species="{{ $boardPrescription->pet->pet_species }}"
                                            data-breed="{{ $boardPrescription->pet->pet_breed }}"
                                            data-weight="{{ $boardPrescription->pet->pet_weight }}"
                                            data-age="{{ $boardPrescription->pet->pet_age }}"
                                            data-temp="{{ $boardPrescription->pet->pet_temperature }}"
                                            data-gender="{{ $boardPrescription->pet->pet_gender }}"
                                            data-date="{{ \Carbon\Carbon::parse($boardPrescription->prescription_date)->format('F d, Y') }}"
                                            data-medication="{{ $boardPrescription->medication }}"
                                            data-differential-diagnosis="{{ $boardPrescription->differential_diagnosis }}"
                                            data-notes="{{ $boardPrescription->notes }}"
                                            data-branch-name="{{ $boardPrescription->branch->branch_name ?? 'Main Branch' }}"
                                            data-branch-address="{{ $boardPrescription->branch->branch_address ?? 'Branch Address' }}"
                                            data-branch-contact="{{ $boardPrescription->branch->branch_contactNum ?? 'Contact Number' }}" 
                                            title="print prescription">
                                            <i class="fas fa-prescription"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No boarding visits found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div id="boardingPagination" class="flex justify-between items-center mt-4">
                <!-- Pagination will be generated by JavaScript -->
            </div>
            @if(isset($boardingVisits) && method_exists($boardingVisits, 'links'))
            <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
                <div>
                    Showing {{ $boardingVisits->firstItem() ?? 0 }} to {{ $boardingVisits->lastItem() ?? 0 }} of
                    {{ $boardingVisits->total() }} entries
                </div>
                <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                    @if ($boardingVisits->onFirstPage())
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed border-r">Previous</button>
                    @else
                        <a href="{{ $boardingVisits->appends(['active_tab' => 'boarding'])->previousPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200 border-r">Previous</a>
                    @endif

                    @for ($i = 1; $i <= $boardingVisits->lastPage(); $i++)
                        @if ($i == $boardingVisits->currentPage())
                            <button class="px-3 py-1 bg-[#0f7ea0] text-white border-r">{{ $i }}</button>
                        @else
                            <a href="{{ $boardingVisits->appends(['active_tab' => 'boarding'])->url($i) }}"
                                class="px-3 py-1 hover:bg-gray-200 border-r">{{ $i }}</a>
                        @endif
                    @endfor

                    @if ($boardingVisits->hasMorePages())
                        <a href="{{ $boardingVisits->appends(['active_tab' => 'boarding'])->nextPageUrl() }}"
                            class="px-3 py-1 text-black hover:bg-gray-200">Next</a>
                    @else
                        <button disabled class="px-3 py-1 text-gray-400 cursor-not-allowed">Next</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div id="addVisitModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-5 my-8 mx-auto max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-center mb-4 flex-shrink-0">
                    <h3 class="text-lg font-bold">Add Visit</h3>
                    <button onclick="closeAddVisitModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
                </div>
                <form id="addVisitForm" method="POST" action="{{ route('medical.visits.store') }}" class="space-y-4 overflow-y-auto flex-grow pr-2">
                    @csrf
                    <input type="hidden" name="active_tab" value="visits">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Date</label>
                            <input type="date" name="visit_date" id="add_visit_date" value="{{ now()->format('Y-m-d') }}" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Owner</label>
                            <select id="add_owner_id" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="" selected disabled>Select owner</option>
                                @foreach(($filteredOwners ?? []) as $owner)
                                    <option value="{{ $owner->own_id }}">{{ $owner->own_name }} ({{ $owner->pets_count ?? 0 }} {{ ($owner->pets_count ?? 0) == 1 ? 'pet' : 'pets' }})</option>
                                @endforeach
                                    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
                                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                                    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                                    <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        // Custom matcher for substring search
                                        function matchCustom(params, data) {
                                            if ($.trim(params.term) === '') {
                                                return data;
                                            }
                                            if (typeof data.text === 'undefined') {
                                                return null;
                                            }
                                            if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                                                return data;
                                            }
                                            return null;
                                        }
                                        const ownerSelect = $('#add_owner_id');
                                        if (ownerSelect.length) {
                                            ownerSelect.select2({
                                                width: '100%',
                                                placeholder: 'Select owner',
                                                allowClear: true,
                                                matcher: matchCustom,
                                                minimumResultsForSearch: 0 // always show search box
                                            });
                                            // Prevent dropdown from opening on input focus (but allow arrow click and search)
                                            ownerSelect.on('select2:opening', function(e) {
                                                // Only block if focus triggered (not arrow or search)
                                                if (document.activeElement === this && !window._select2AllowOpen) {
                                                    e.preventDefault();
                                                }
                                                window._select2AllowOpen = false;
                                            });
                                            // Allow open on arrow click
                                            ownerSelect.next('.select2-container').find('.select2-selection__arrow').on('mousedown', function(e) {
                                                window._select2AllowOpen = true;
                                            });
                                            // Allow open on keyboard (Alt+Down, etc.)
                                            ownerSelect.on('keydown', function(e) {
                                                if ((e.key === 'ArrowDown' || e.key === 'Enter') && !ownerSelect.data('select2').isOpen()) {
                                                    window._select2AllowOpen = true;
                                                }
                                            });
                                        }
                                    });
                                    </script>
                                    <!-- Select2 JS and CSS -->
                                    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
                                    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                                    <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        if (window.jQuery && $('#add_owner_id').length) {
                                            $('#add_owner_id').select2({
                                                dropdownParent: $('#addVisitModal'),
                                                width: '100%',
                                                placeholder: 'Select owner',
                                                allowClear: true
                                            });
                                        }
                                    });
                                    </script>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium">Pets for selected owner</label>
                            <div id="add_owner_pets_container" class="space-y-3 border border-gray-200 rounded p-3 max-h-64 overflow-y-auto">
                                <div class="text-gray-500 text-sm">Select an owner to load their pets.</div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Tick the pets to include and set their weight, temperature and service type.</p>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium">Patient Type</label>
                            <select name="patient_type" id="add_patient_type" class="border border-gray-300 rounded px-3 py-2 w-full" required onchange="togglePatientTypeFields()">
                                @foreach(['Outpatient','Inpatient','Emergency'] as $pt)
                                    <option value="{{ $pt }}">{{ $pt }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1" id="patient_type_hint">Standard outpatient visit.</p>
                        </div>
                        
                        {{-- Inpatient Fields (shown when Inpatient is selected) --}}
                        <div id="inpatient_fields" class="col-span-2 hidden">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                                <div class="flex items-center gap-2 text-blue-700 font-medium">
                                    <i class="fas fa-procedures"></i>
                                    <span>Inpatient Admission Details</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Cage/Ward Number</label>
                                        <input type="text" name="cage_ward_number" id="add_cage_ward_number" 
                                            class="border border-gray-300 rounded px-3 py-2 w-full" 
                                            placeholder="e.g., Cage 1, Ward A">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Admission Date</label>
                                        <input type="datetime-local" name="admission_date" id="add_admission_date" 
                                            class="border border-gray-300 rounded px-3 py-2 w-full">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Admission Notes</label>
                                    <textarea name="admission_notes" id="add_admission_notes" rows="2" 
                                        class="border border-gray-300 rounded px-3 py-2 w-full" 
                                        placeholder="Reason for admission, initial observations, special care instructions..."></textarea>
                                </div>
                                <p class="text-xs text-blue-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Inpatient visits stay open and can have multiple services added during the stay (Surgical, Diagnostics, Check-up, Boarding, etc.)
                                </p>
                            </div>
                        </div>
                        
                        {{-- Emergency Fields (shown when Emergency is selected) --}}
                        <div id="emergency_fields" class="col-span-2 hidden">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 space-y-3">
                                <div class="flex items-center gap-2 text-red-700 font-medium">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Emergency Visit - High Priority</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Emergency Notes</label>
                                    <textarea name="admission_notes" id="add_emergency_notes" rows="2" 
                                        class="border border-gray-300 rounded px-3 py-2 w-full" 
                                        placeholder="Emergency situation description, symptoms, initial assessment..."></textarea>
                                </div>
                                <input type="hidden" name="is_priority" id="add_is_priority" value="0">
                                <p class="text-xs text-red-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Emergency visits are marked as high priority. You can add Emergency service, Diagnostics, Surgical, and other services as needed.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeAddVisitModal()" class="px-4 py-2 border rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded">Create</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="editVisitModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-5 my-8 mx-auto max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-center mb-4 flex-shrink-0">
                    <h3 class="text-lg font-bold" id="editVisitTitle">Edit Visit</h3>
                    <button onclick="closeEditVisitModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
                </div>
                <form id="editVisitForm" method="POST" action="#" class="space-y-4 overflow-y-auto flex-grow pr-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="active_tab" value="visits">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Date</label>
                            <input type="date" name="visit_date" id="edit_visit_date" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Pet</label>
                            <select name="pet_id" id="edit_pet_id" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                                @foreach(($filteredPets ?? []) as $pet)
                                    <option value="{{ $pet->pet_id }}">{{ $pet->pet_name }} ({{ $pet->owner->own_name ?? 'N/A' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Weight (kg)</label>
                            <input type="number" step="0.01" name="weight" id="edit_weight" class="border border-gray-300 rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature" id="edit_temperature" class="border border-gray-300 rounded px-3 py-2 w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Patient Type</label>
                            <select name="patient_type" id="edit_patient_type" class="border border-gray-300 rounded px-3 py-2 w-full" required>
                                @foreach(['Outpatient','Inpatient','Emergency'] as $pt)
                                    <option value="{{ $pt }}">{{ $pt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Status</label>
                            <input type="text" name="visit_status" id="edit_visit_status" class="border border-gray-300 rounded px-3 py-2 w-full bg-gray-100" readonly>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4 mt-4">
                        <label class="block text-sm font-medium mb-2">Service Type(s)</label>
                        <div class="grid grid-cols-4 gap-3" id="edit_services_list" data-service-group="edit">
                            @php
                                $editServiceTypes = ['boarding', 'check-up', 'deworming', 'diagnostics', 'emergency', 'grooming', 'surgical', 'vaccination'];
                            @endphp
                            @foreach($editServiceTypes as $type)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="service_type[]" value="{{ $type }}" class="edit-service-checkbox mr-1">
                                    <span>{{ ucfirst($type) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Select one or more services for this visit. Some services cannot be paired together.</p>
                    </div>
                    
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeEditVisitModal()" class="px-4 py-2 border rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded">Update</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- View Visit Details Modal --}}
        <div id="viewVisitModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl p-6 my-8 mx-auto max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-center mb-4 flex-shrink-0 border-b pb-3">
                    <h3 class="text-xl font-bold text-gray-800" id="viewVisitTitle">
                        <i class="fas fa-clipboard-list mr-2 text-[#0f7ea0]"></i>Visit Details
                    </h3>
                    <button onclick="closeViewVisitModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
                </div>
                
                <div id="viewVisitContent" class="overflow-y-auto flex-grow">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                        <p class="mt-2 text-gray-500">Loading visit details...</p>
                    </div>
                </div>
                
                <div class="flex justify-end gap-2 pt-4 border-t mt-4 flex-shrink-0">
                    <button type="button" onclick="closeViewVisitModal()" class="px-4 py-2 border rounded hover:bg-gray-100">Close</button>
                </div>
            </div>
        </div>

       @php
            $__petsPayload = [];
            foreach (($filteredPets ?? []) as $__p) {
                $__petsPayload[] = [
                    'pet_id' => $__p->pet_id,
                    'pet_name' => $__p->pet_name,
                    'pet_species' => $__p->pet_species,
                    'owner_id' => $__p->owner->own_id ?? null,
                    'owner_name' => $__p->owner->own_name ?? null,
                ];
            }
            $__petsJson = json_encode($__petsPayload);
        @endphp
        <script type="application/json" id="visit_pets_data">{!! $__petsJson !!}</script>
        <script type="application/json" id="visit_service_types">
            {!! json_encode($serviceTypes ?? []) !!}
        </script>
    </div>
</div>

<style>
/* Responsive Table Styles */
@media (max-width: 767px) {
    .responsive-table {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .responsive-table table {
        min-width: 640px;
    }
    
    /* Hide less important columns on mobile */
    .hidden-mobile {
        display: none;
    }
}

/* Tab Styles */
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
</style>

<script>
// Setup CSRF token
function setupCSRF() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.csrfToken = token.getAttribute('content');
    }
}

// Tab switching functionality
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

// ===== Visits Modals =====
function togglePatientTypeFields() {
    const patientType = document.getElementById('add_patient_type').value;
    const inpatientFields = document.getElementById('inpatient_fields');
    const emergencyFields = document.getElementById('emergency_fields');
    const patientTypeHint = document.getElementById('patient_type_hint');
    const isPriorityInput = document.getElementById('add_is_priority');
    
    // Hide all conditional fields first
    if (inpatientFields) inpatientFields.classList.add('hidden');
    if (emergencyFields) emergencyFields.classList.add('hidden');
    if (isPriorityInput) isPriorityInput.value = '0';
    
    if (patientType === 'Inpatient') {
        // Show inpatient fields
        if (inpatientFields) inpatientFields.classList.remove('hidden');
        if (patientTypeHint) patientTypeHint.textContent = 'Patient will be admitted. Status: Admitted (not arrived until checked in).';
        
        // Set default admission date to now
        const admissionDateInput = document.getElementById('add_admission_date');
        if (admissionDateInput && !admissionDateInput.value) {
            const now = new Date();
            admissionDateInput.value = now.toISOString().slice(0, 16);
        }
    } else if (patientType === 'Emergency') {
        // Show emergency fields
        if (emergencyFields) emergencyFields.classList.remove('hidden');
        if (patientTypeHint) patientTypeHint.textContent = 'High priority emergency case.';
        if (isPriorityInput) isPriorityInput.value = '1';
    } else {
        // Outpatient - default
        if (patientTypeHint) patientTypeHint.textContent = 'Standard outpatient visit.';
    }
}

function openAddVisitModal() {
    showTab('visits');
    const modal = document.getElementById('addVisitModal');
    if (!modal) return;
    
    const form = document.getElementById('addVisitForm');
    if (form) form.reset();
    
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('add_visit_date');
    if (dateInput) dateInput.value = today;
    
    // Reset patient type fields visibility
    togglePatientTypeFields();
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeAddVisitModal() {
    const modal = document.getElementById('addVisitModal');
    if (!modal) return;
    
    // Clear any validation errors
    document.querySelectorAll('.validation-error').forEach(el => el.remove());
    
    // Reset form
    const form = document.getElementById('addVisitForm');
    if (form) form.reset();
    
    // Clear pets container
    const petsContainer = document.getElementById('add_owner_pets_container');
    if (petsContainer) {
        petsContainer.innerHTML = '<div class="text-gray-500 text-sm">Select an owner to load their pets.</div>';
    }
    
    // Reset Select2 if initialized
    if (window.jQuery && $('#add_owner_id').data('select2')) {
        $('#add_owner_id').val(null).trigger('change');
    }
    
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openEditVisitModal(visitId, attending) {
    showTab('visits');
    // Using a relative URL here, assuming the route exists
    fetch(`/medical-management/visits/${visitId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(v => {
        const modal = document.getElementById('editVisitModal');
        const form = document.getElementById('editVisitForm');
        if (!modal || !form) return;
        
        form.action = `/medical-management/visits/${visitId}`;
        const title = document.getElementById('editVisitTitle');
        if (title) title.textContent = attending ? 'Attend Visit' : 'Edit Visit';

        const dateInput = document.getElementById('edit_visit_date');
        const petSelect = document.getElementById('edit_pet_id');
        const weightInput = document.getElementById('edit_weight');
        const tempInput = document.getElementById('edit_temperature');
        const typeSelect = document.getElementById('edit_patient_type');
        const statusSelect = document.getElementById('edit_visit_status');

        if (dateInput) dateInput.value = v.visit_date?.substring(0,10) || '';
        if (petSelect) petSelect.value = v.pet_id;
        if (weightInput) weightInput.value = v.weight ?? '';
        if (tempInput) tempInput.value = v.temperature ?? '';
        if (typeSelect) typeSelect.value = capitalizeFirstLetter(v.patient_type) ?? 'Outpatient';
        if (statusSelect) {
            statusSelect.value = capitalizeFirstLetter(v.visit_status || 'pending');
        }

        // Load service types
        const servicesList = document.getElementById('edit_services_list');
        if (servicesList) {
            // Initialize checkbox listeners
            initEditServiceCheckboxes();
            
            if (v.services && v.services.length > 0) {
                // Get unique service types from services
                const serviceTypesSet = new Set();
                v.services.forEach(service => {
                    const serviceType = (service.serv_type || service.serv_name || '').toLowerCase();
                    if (serviceType) {
                        serviceTypesSet.add(serviceType);
                    }
                });
                // Set checkboxes for the service types
                setEditServiceCheckboxes(Array.from(serviceTypesSet));
            } else {
                // Reset all checkboxes if no services
                setEditServiceCheckboxes([]);
            }
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    })
    .catch(() => alert('Failed to load visit details.'));
}
function capitalizeFirstLetter(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}


function closeEditVisitModal() {
    const modal = document.getElementById('editVisitModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ===== View Visit Details Functions =====
function openViewVisitModal(visitId, serviceType = null) {
    const modal = document.getElementById('viewVisitModal');
    const content = document.getElementById('viewVisitContent');
    const title = document.getElementById('viewVisitTitle');
    
    if (!modal || !content) return;
    
    // Show loading state
    content.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
            <p class="mt-2 text-gray-500">Loading visit details...</p>
        </div>
    `;
    
    // Update title based on service type
    if (title) {
        const typeLabel = serviceType ? capitalizeFirstLetter(serviceType) : 'Visit';
        title.innerHTML = `<i class="fas fa-clipboard-list mr-2 text-[#0f7ea0]"></i>${typeLabel} Details`;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Fetch visit details
    fetch(`/medical-management/visits/${visitId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(v => {
        renderVisitDetails(v, serviceType);
    })
    .catch(err => {
        console.error('Error loading visit:', err);
        content.innerHTML = `
            <div class="text-center py-8 text-red-500">
                <i class="fas fa-exclamation-circle text-3xl"></i>
                <p class="mt-2">Failed to load visit details.</p>
            </div>
        `;
    });
}

function renderVisitDetails(v, serviceType) {
    const content = document.getElementById('viewVisitContent');
    if (!content) return;
    
    // Format date
    const visitDate = v.visit_date ? new Date(v.visit_date).toLocaleDateString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric' 
    }) : 'N/A';
    
    // Get services list
    let servicesHtml = '-';
    if (v.services && v.services.length > 0) {
        const serviceTypes = [...new Set(v.services.map(s => s.serv_type || s.serv_name))];
        servicesHtml = serviceTypes.map(t => `<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1 mb-1">${capitalizeFirstLetter(t)}</span>`).join('');
    }
    
    // Get status badge color
    const status = v.visit_status || 'pending';
    let statusClass = 'bg-gray-100 text-gray-700';
    if (['complete', 'completed', 'arrive', 'arrived'].includes(status.toLowerCase())) {
        statusClass = 'bg-green-100 text-green-700';
    } else if (status.toLowerCase() === 'pending') {
        statusClass = 'bg-yellow-100 text-yellow-700';
    } else if (status.toLowerCase() === 'billed') {
        statusClass = 'bg-blue-100 text-blue-700';
    }
    
    // Patient type
    const patientType = v.patient_type || 'Outpatient';
    let patientTypeClass = 'bg-gray-100 text-gray-700';
    if (patientType.toLowerCase() === 'emergency') {
        patientTypeClass = 'bg-red-100 text-red-700';
    } else if (patientType.toLowerCase() === 'inpatient') {
        patientTypeClass = 'bg-purple-100 text-purple-700';
    }
    
    content.innerHTML = `
        <div class="space-y-6">
            <!-- Pet & Owner Information -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-paw mr-2 text-[#0f7ea0]"></i>Pet & Owner Information
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Pet Name</p>
                        <p class="font-medium">${v.pet?.pet_name || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Species</p>
                        <p class="font-medium">${v.pet?.pet_species || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Breed</p>
                        <p class="font-medium">${v.pet?.pet_breed || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Owner</p>
                        <p class="font-medium">${v.pet?.owner?.own_name || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Contact</p>
                        <p class="font-medium">${v.pet?.owner?.own_contactnum || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Email</p>
                        <p class="font-medium">${v.pet?.owner?.own_email || 'N/A'}</p>
                    </div>
                </div>
            </div>
            
            <!-- Visit Information -->
            <div class="bg-blue-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-calendar-check mr-2 text-[#0f7ea0]"></i>Visit Information
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Visit Date</p>
                        <p class="font-medium">${visitDate}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Patient Type</p>
                        <p><span class="px-2 py-1 rounded text-xs ${patientTypeClass}">${capitalizeFirstLetter(patientType)}</span></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Status</p>
                        <p><span class="px-2 py-1 rounded text-xs ${statusClass}">${capitalizeFirstLetter(status)}</span></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Weight</p>
                        <p class="font-medium">${v.weight ? parseFloat(v.weight).toFixed(2) + ' kg' : 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Temperature</p>
                        <p class="font-medium">${v.temperature ? parseFloat(v.temperature).toFixed(1) + ' °C' : 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Visit Source</p>
                        <p class="font-medium">${capitalizeFirstLetter(v.visit_source || 'Walk-in')}</p>
                    </div>
                </div>
            </div>
            
            <!-- Service Types -->
            <div class="bg-green-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-stethoscope mr-2 text-[#0f7ea0]"></i>Service Type(s)
                </h4>
                <div>${servicesHtml}</div>
            </div>
            
            ${v.patient_type?.toLowerCase() === 'inpatient' ? `
            <!-- Inpatient Information -->
            <div class="bg-purple-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-procedures mr-2 text-purple-600"></i>Inpatient Information
                </h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Admission Date</p>
                        <p class="font-medium">${v.admission_date ? new Date(v.admission_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Discharge Date</p>
                        <p class="font-medium">${v.discharge_date ? new Date(v.discharge_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'Not yet discharged'}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 uppercase">Reason for Admission</p>
                        <p class="font-medium">${v.admission_reason || 'N/A'}</p>
                    </div>
                </div>
            </div>
            ` : ''}
            
            ${v.notes || v.visit_notes ? `
            <!-- Notes -->
            <div class="bg-yellow-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-sticky-note mr-2 text-yellow-600"></i>Notes
                </h4>
                <p class="text-gray-700 whitespace-pre-wrap">${v.notes || v.visit_notes || 'No notes available.'}</p>
            </div>
            ` : ''}
            
            <!-- Visit ID -->
            <div class="text-right text-xs text-gray-400">
                Visit ID: #${v.visit_id}
            </div>
        </div>
    `;
}

function closeViewVisitModal() {
    const modal = document.getElementById('viewVisitModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Service management functions for edit visit
// 🛑 SERVICE PAIRING RULES for Edit (same as Add)
const editServicePairingRules = {
    'surgical': ['grooming', 'vaccination', 'deworming', 'boarding'],
    'emergency': ['check-up', 'grooming', 'vaccination', 'deworming', 'surgical', 'boarding'],
    'vaccination': ['surgical', 'grooming'],
    'boarding': ['surgical', 'emergency'],
    'diagnostics': ['grooming', 'deworming'],
    'deworming': ['vaccination'],
};

function updateEditServicePairing() {
    const servicesList = document.getElementById('edit_services_list');
    if (!servicesList) return;
    
    const checkboxes = servicesList.querySelectorAll('input[type="checkbox"]');
    const selectedServices = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);

    checkboxes.forEach(cb => {
        const service = cb.value;
        let shouldBeDisabled = false;

        // Check if any *selected* service makes the *current* service incompatible
        for (const selected of selectedServices) {
            if (selected in editServicePairingRules && editServicePairingRules[selected].includes(service)) {
                shouldBeDisabled = true;
                break;
            }
            if (service !== selected && service in editServicePairingRules && editServicePairingRules[service].includes(selected)) {
                if (!cb.checked) {
                    shouldBeDisabled = true;
                    break;
                }
            }
        }

        if (!cb.checked) {
            if (shouldBeDisabled) {
                cb.disabled = true;
                cb.title = `Cannot be paired with selected services: ${selectedServices.join(', ')}`;
                cb.parentElement.classList.add('opacity-50');
            } else {
                cb.disabled = false;
                cb.title = '';
                cb.parentElement.classList.remove('opacity-50');
            }
        } else {
            cb.disabled = false;
            cb.title = '';
            cb.parentElement.classList.remove('opacity-50');
        }
    });
}

function setEditServiceCheckboxes(serviceTypes) {
    const servicesList = document.getElementById('edit_services_list');
    if (!servicesList) return;
    
    // Reset all checkboxes
    const checkboxes = servicesList.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.checked = false;
        cb.disabled = false;
        cb.title = '';
        cb.parentElement.classList.remove('opacity-50');
    });
    
    // Normalize and check the matching service types
    const normalizedTypes = serviceTypes.map(t => t.toLowerCase().trim().replace(/\s+/g, '-'));
    checkboxes.forEach(cb => {
        const cbValue = cb.value.toLowerCase().trim();
        if (normalizedTypes.includes(cbValue) || normalizedTypes.includes(cbValue.replace('-', ' '))) {
            cb.checked = true;
        }
    });
    
    // Apply pairing rules
    updateEditServicePairing();
}

// Initialize edit service checkbox listeners
function initEditServiceCheckboxes() {
    const servicesList = document.getElementById('edit_services_list');
    if (!servicesList) return;
    
    const checkboxes = servicesList.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.removeEventListener('change', updateEditServicePairing);
        cb.addEventListener('change', updateEditServicePairing);
    });
}

// Advance workflow status inline for service tabs
async function advanceWorkflow(visitId, btn, type){
    try {
        btn && (btn.disabled = true);
        const res = await fetch(`/medical-management/visits/${visitId}/workflow`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (window.csrfToken || (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''))
            },
            body: JSON.stringify({ type: type || '' })
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            alert(data.error || 'Failed to update status');
        } else {
            const tr = btn ? btn.closest('tr') : null;
            if (tr){
                const tds = tr.querySelectorAll('td');
                // In service tabs, status is the 5th column (index 4)
                if (tds && tds.length >= 5){ tds[4].textContent = data.workflow_status || '-'; }
            }
        }
    } catch(e){
        alert('Network error updating status');
    } finally {
        btn && (btn.disabled = false);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupCSRF();
    
    // Default tab based on user role
    try { 
        // Read the 'tab' URL parameter, or default based on role
        const urlParams = new URLSearchParams(window.location.search);
        const userRole = '{{ $userRole }}';
        let defaultTab = 'grooming'; // default for all
        if (userRole === 'receptionist') {
            defaultTab = 'visits';
        } else if (userRole === 'veterinarian') {
            defaultTab = 'checkup';
        }
        const activeTab = urlParams.get('tab') || defaultTab;
        showTab(activeTab); 
    } catch(e) {
        // Fallback based on role
        const userRole = '{{ $userRole }}';
        if (userRole === 'receptionist') {
            showTab('visits');
        } else if (userRole === 'veterinarian') {
            showTab('checkup');
        } else {
            showTab('grooming');
        }
    }
    
    const ownerSelect = document.getElementById('add_owner_id');
    
    // Build pets dataset for rendering owner pets
    const allPets = (function(){
        try {
            return JSON.parse(document.getElementById('visit_pets_data').textContent);
        } catch { return []; }
    })();

    // 🛑 SERVICE PAIRING RULES (Cannot Be Paired With)
    const servicePairingRules = {
        'surgical': ['grooming', 'vaccination', 'deworming', 'boarding'],
        'emergency': ['check-up', 'grooming', 'vaccination', 'deworming', 'surgical', 'boarding'],
        'vaccination': ['surgical', 'grooming'],
        'boarding': ['surgical', 'emergency'],
        'diagnostics': ['grooming', 'deworming'],
        'deworming': ['vaccination'],
        // Explicitly allowed pairings are handled by NOT listing them here.
        // E.g., Check-up not listed means it can be paired with anything not listed in other rules.
    };

    function updateServicePairing(serviceGroupElement) {
        const checkboxes = serviceGroupElement.querySelectorAll('input[type="checkbox"]');
        const selectedServices = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        checkboxes.forEach(cb => {
            const service = cb.value;
            let shouldBeDisabled = false;

            // Check if any *selected* service makes the *current* service incompatible
            for (const selected of selectedServices) {
                // Check if the current service is in the restricted list of an *already selected* service
                if (selected in servicePairingRules && servicePairingRules[selected].includes(service)) {
                    shouldBeDisabled = true;
                    break;
                }
                // Check for reverse restriction (if the current service restricts the selected one)
                // This is generally covered by checking both services' rules, but explicit check adds robustness
                if (service !== selected && service in servicePairingRules && servicePairingRules[service].includes(selected)) {
                     // Only disable the currently unselected service if the conflict is with a *currently selected* service
                     // If the service is ALREADY selected, we only disable new conflicting services.
                    if (!cb.checked) {
                        shouldBeDisabled = true;
                        break;
                    }
                }
            }

            if (!cb.checked) {
                // Disable if it conflicts with any currently selected service
                if (shouldBeDisabled) {
                    cb.disabled = true;
                    cb.title = `Cannot be paired with selected services: ${selectedServices.join(', ')}`;
                } else {
                    cb.disabled = false;
                    cb.title = '';
                }
            } else {
                // Selected services should never disable themselves
                cb.disabled = false; 
                cb.title = '';
            }
        });
    }

    function renderOwnerPets(ownerId) {
        const container = document.getElementById('add_owner_pets_container');
        if (!container) return;
        
        const pets = allPets.filter(p => String(p.owner_id) === String(ownerId));
        if (pets.length === 0) {
            container.innerHTML = '<div class="text-gray-500 text-sm">No pets found for the selected owner.</div>';
            return;
        }
        
        container.innerHTML = pets.map(p => {
            // Fixed serv_type options (normalized to lowercase)
            const fixedTypes = ['boarding','check-up','deworming','diagnostics','emergency','grooming','surgical','vaccination'];
            const serviceCheckboxes = fixedTypes.map(type => `
                <label class='inline-flex items-center'>
                    <input type="checkbox" name="service_type[${p.pet_id}][]" value="${type}" class="service-checkbox mr-1"> ${capitalizeFirstLetter(type)}
                </label>
            `).join('');
            const species = String(p.pet_species || '').toLowerCase();
            const icon = species === 'cat' ? '<i class="fas fa-cat text-gray-600 mr-1" title="Cat"></i>' : (species === 'dog' ? '<i class="fas fa-dog text-gray-600 mr-1" title="Dog"></i>' : '');
            return `
            <div class="border border-gray-200 rounded p-3">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="pet_ids[]" value="${p.pet_id}" class="mt-1 pet-check" data-pet="${p.pet_id}">
                    <div class="flex-1">
                        <div class="font-medium">${icon}${p.pet_name}</div>
                        <div class="grid grid-cols-4 gap-3 mt-2 text-sm items-end">
                            <div>
                                <label class="block text-xs text-gray-600">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight[${p.pet_id}]" class="border border-gray-300 rounded px-2 py-1 w-full pet-weight-input" placeholder="e.g. 3.50" min="1" max="90" data-pet="${p.pet_id}">
                                <div class="error-message" style="display:none"></div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">Temperature (°C)</label>
                                <input type="number" step="0.1" name="temperature[${p.pet_id}]" class="border border-gray-300 rounded px-2 py-1 w-full" placeholder="e.g. 38.5">
                            </div>
                            <div></div>
                            <div></div>
                        </div>
                        <div class="grid grid-cols-4 gap-3 mt-2 text-sm">
                            <div class="col-span-4">
                                <label class="block text-xs text-gray-600">Service Type(s)</label>
                                <div class="grid grid-cols-4 gap-2" data-service-group="${p.pet_id}">${serviceCheckboxes}</div>
                                <p class="text-xs text-gray-400 mt-1">Select one or more services for this pet's visit.</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>`;
        }).join('');

        // Add event listeners for pairing logic and auto-check
        const groups = container.querySelectorAll('[data-service-group]');
        groups.forEach(g => {
            const pet = g.getAttribute('data-service-group');
            const petBox = container.querySelector(`input.pet-check[data-pet="${pet}"]`);
            
            const serviceCheckboxes = g.querySelectorAll('input[type="checkbox"]');

            serviceCheckboxes.forEach(cb => {
                // 1. Service Pairing Logic
                cb.addEventListener('change', () => {
                    updateServicePairing(g);
                });

                // 2. Auto-check the pet logic
                cb.addEventListener('change', () => {
                    if (cb.checked && petBox && !petBox.checked) {
                        petBox.checked = true;
                    }
                });
            });

            // Initial run of the pairing logic
            updateServicePairing(g);
        });
    }

    if (ownerSelect) {
        // For native select
        ownerSelect.addEventListener('change', function() {
            renderOwnerPets(this.value);
        });
        // For Select2
        if (window.jQuery && $(ownerSelect).data('select2')) {
            $(ownerSelect).on('select2:select', function(e) {
                renderOwnerPets(e.params.data.id);
            });
            $(ownerSelect).on('select2:clear', function(e) {
                renderOwnerPets('');
            });
        }
    }
});

// Initialize all ListFilter instances for medical management tabs
document.addEventListener('DOMContentLoaded', function() {
    // Main visits table with status filtering
    new ListFilter({
        tableSelector: '#visitsTable',
        searchInputId: 'visitsSearch',
        perPageSelectId: 'visitPerPage',
        paginationContainerId: 'visitsPagination',
        searchColumns: [1, 2, 3, 4, 7, 8, 9], // Date, Pet, Species, Owner, Patient Type, Service Type, Status columns
        filterSelects: [
            { selectId: 'visitsStatus', columnIndex: 9 } // Status column (0-based)
        ]
    });
    
    // Checkup table with pagination
    new ListFilter({
        tableSelector: '#checkupContent table',
        searchInputId: 'checkupSearch',
        perPageSelectId: 'checkupPerPage',
        paginationContainerId: 'checkupPagination',
        searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
    });
    
    // Deworming table with pagination
    new ListFilter({
        tableSelector: '#dewormingTable',
        searchInputId: 'dewormingSearch',
        perPageSelectId: 'dewormingVisitsPerPage',
        paginationContainerId: 'dewormingPagination',
        searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
    });
    
    // Vaccination table with pagination
    const vaccinationTable = document.querySelector('#vaccinationContent table');
    if (vaccinationTable) {
        new ListFilter({
            tableSelector: '#vaccinationContent table',
            searchInputId: 'vaccinationSearch',
            perPageSelectId: 'vaccinationPerPage',
            paginationContainerId: 'vaccinationPagination',
            searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
        });
    }
    
    // Grooming table with pagination
    new ListFilter({
        tableSelector: '#groomingTable',
        searchInputId: 'groomingSearch',
        perPageSelectId: 'groomingVisitsPerPage',
        paginationContainerId: 'groomingPagination',
        searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
    });
    
    // Boarding table with pagination
    new ListFilter({
        tableSelector: '#boardingTable',
        searchInputId: 'boardingSearch',
        perPageSelectId: 'boardingVisitsPerPage',
        paginationContainerId: 'boardingPagination',
        searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
    });
    
    // Diagnostics table with pagination
    new ListFilter({
        tableSelector: '#diagnosticsTable',
        searchInputId: 'diagnosticsSearch',
        perPageSelectId: 'diagnosticsVisitsPerPage',
        paginationContainerId: 'diagnosticsPagination',
        searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
    });
    
    // Surgical table with pagination
    new ListFilter({
        tableSelector: '#surgicalTable',
        searchInputId: 'surgicalSearch',
        perPageSelectId: 'surgicalVisitsPerPage',
        paginationContainerId: 'surgicalPagination',
        searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
    });
    
    // Emergency table with pagination
    new ListFilter({
        tableSelector: '#emergencyTable',
        searchInputId: 'emergencySearch',
        perPageSelectId: 'emergencyVisitsPerPage',
        paginationContainerId: 'emergencyPagination',
        searchColumns: [1, 2, 3, 4] // Date, Pet, Owner, Status
    });
});

// Add Visit Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const addVisitForm = document.getElementById('addVisitForm');
    const submitButton = addVisitForm.querySelector('button[type="submit"]');
    
    function validateAddVisitForm() {
        const errors = [];
        
        // 1. Check if at least one pet is selected
        const selectedPets = document.querySelectorAll('#add_owner_pets_container input[name="pet_ids[]"]:checked');
        if (selectedPets.length === 0) {
            errors.push('Please select at least one pet');
        }
        
        // 2. Check weight and temperature for selected pets
        selectedPets.forEach((checkbox, index) => {
            const petId = checkbox.value;
            const petContainer = checkbox.closest('.border');
            if (petContainer) {
                const weightInput = petContainer.querySelector(`input[name="weight[${petId}]"]`);
                const tempInput = petContainer.querySelector(`input[name="temperature[${petId}]"]`);
                const petNameElement = petContainer.querySelector('.font-medium');
                const petName = petNameElement ? petNameElement.textContent.replace(/^\s*[♦♠♥♣]\s*/, '').trim() : `Pet ${index + 1}`;
                
                if (weightInput && (!weightInput.value || weightInput.value.trim() === '')) {
                    errors.push(`Please enter weight for ${petName}`);
                }
                if (tempInput && (!tempInput.value || tempInput.value.trim() === '')) {
                    errors.push(`Please enter temperature for ${petName}`);
                }
                
                // 3. Check if at least one service type is selected for this pet
                const selectedServices = petContainer.querySelectorAll(`input[name="service_type[${petId}][]"]:checked`);
                if (selectedServices.length === 0) {
                    errors.push(`Please select at least one service type for ${petName}`);
                }
            }
        });
        
        return errors;
    }
    
    function updateSubmitButton() {
        const errors = validateAddVisitForm();
        if (errors.length > 0) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            submitButton.classList.remove('hover:bg-[#0d6d8a]');
        } else {
            submitButton.disabled = false;
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            submitButton.classList.add('hover:bg-[#0d6d8a]');
        }
    }
    
    function showValidationErrors(errors) {
        // Remove existing error messages
        document.querySelectorAll('.validation-error').forEach(el => el.remove());
        
        if (errors.length > 0) {
            const errorContainer = document.createElement('div');
            errorContainer.className = 'validation-error bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
            
            const errorList = document.createElement('ul');
            errorList.className = 'list-disc list-inside';
            
            errors.forEach(error => {
                const errorItem = document.createElement('li');
                errorItem.textContent = error;
                errorList.appendChild(errorItem);
            });
            
            errorContainer.appendChild(errorList);
            
            // Insert error container at the top of the form
            const firstElement = addVisitForm.querySelector('.space-y-4');
            firstElement.insertBefore(errorContainer, firstElement.firstChild);
        }
    }
    
    // Add event listeners for real-time validation
    function addValidationListeners() {
        // Listen for changes in the pets container
        const petsContainer = document.getElementById('add_owner_pets_container');
        if (petsContainer) {
            petsContainer.addEventListener('change', updateSubmitButton);
            petsContainer.addEventListener('input', updateSubmitButton);
        }
        
        // Listen for owner selection changes
        const ownerSelect = document.getElementById('add_owner_id');
        if (ownerSelect) {
            ownerSelect.addEventListener('change', function() {
                setTimeout(updateSubmitButton, 500); // Delay to allow pets to load
            });
            
            // For Select2
            if (window.jQuery && $(ownerSelect).data('select2')) {
                $(ownerSelect).on('select2:select select2:clear', function() {
                    setTimeout(updateSubmitButton, 500);
                });
            }
        }
    }
    
    // Initialize MutationObserver to watch for changes in pets container
    function initializePetContainerObserver() {
        const petsContainer = document.getElementById('add_owner_pets_container');
        if (petsContainer) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Pets were added/removed, update validation
                        setTimeout(updateSubmitButton, 100);
                    }
                });
            });
            
            observer.observe(petsContainer, {
                childList: true,
                subtree: true
            });
        }
    }
    
    // Initialize validation listeners and observer
    addValidationListeners();
    initializePetContainerObserver();
    updateSubmitButton();
    
    // Form submission validation
    addVisitForm.addEventListener('submit', function(e) {
        const errors = validateAddVisitForm();
        if (errors.length > 0) {
            e.preventDefault();
            showValidationErrors(errors);
            
            // Scroll to error message
            const errorElement = document.querySelector('.validation-error');
            if (errorElement) {
                errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return false;
        }
        
        // Remove any existing error messages on successful validation
        document.querySelectorAll('.validation-error').forEach(el => el.remove());
    });
});
</script>

{{-- Include Service Activity Modal so Initial Assessment is available from Visits page --}}
@include('modals.service_activity_modal', [
    'allPets' => $allPets,
    'allBranches' => $allBranches,
    'allProducts' => $allProducts,
])

<script>
    function openInitialAssessment(visitId, petId, ownerId){
        // Ensure modal is present and open with Initial Assessment tab
        if (typeof openActivityModal === 'function') {
            openActivityModal(String(petId), String(ownerId || ''), 'Initial Assessment');
            if (typeof switchActivityTab === 'function') {
                switchActivityTab('initial');
            }
            const v = document.getElementById('activity_initial_visit_id');
            const p = document.getElementById('activity_initial_pet_id');
            if (v) v.value = String(visitId);
            if (p) p.value = String(petId);
        } else {
            alert('Initial Assessment modal is not available.');
        }
    }
    window.openInitialAssessment = openInitialAssessment;

    // Prescription Print Functions
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
            branchContact: "Contact No: " + button.dataset.branchContact
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
                        <div class="doctor-name font-bold mb-1">JAN JERICK M. GO DVM</div>
                        <div class="license-info text-gray-600">License No.: 0012045</div>
                        <div class="license-info text-gray-600">Attending Veterinarian</div>
                    </div>
                </div>
            </div>
        `;
    }

    function directPrintPrescription(button) {
        const data = populatePrescriptionData(button);
        updatePrescriptionContent('printContent', data);
        
        const printContainer = document.getElementById('printContainer');
        printContainer.style.display = 'block';
        printContainer.classList.add('print-prescription');
        
        setTimeout(() => {
            window.print();
            printContainer.style.display = 'none';
            printContainer.classList.remove('print-prescription');
        }, 200);
    }
    window.directPrintPrescription = directPrintPrescription;
    
  </script>

  {{-- Print Container for Prescriptions --}}
  <div id="printContainer" style="display: none;">
    <div id="printContent" class="prescription-container bg-white p-10"></div>
  </div>

  <style>
    @media print {
        body * {
            visibility: hidden;
        }
        
        .print-prescription,
        .print-prescription * {
            visibility: visible;
        }
        
        .print-prescription {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            z-index: 9999;
        }
        
        .print-prescription .prescription-container {
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.5in;
        }
    }
  </style>

@endsection