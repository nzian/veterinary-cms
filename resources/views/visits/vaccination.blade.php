@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-50 p-4 sm:p-6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800">Vaccination Workspace</h2>
            <a href="{{ route('medical.index', ['tab' => 'vaccination']) }}" 
               class="px-4 py-2 bg-gray-200 border-2 border-gray-300 rounded-lg hover:bg-gray-300 font-medium shadow-sm transition">
                ← Back 
            </a>
        </div>
        <div class="space-y-6">

        {{-- Row 2: Pet Info (left) + Recent History (right) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openPetProfileModal()">
                <div class="font-semibold text-gray-900 mb-1">{{ $visit->pet->pet_name ?? 'Pet' }} <span class="text-gray-500">({{ $visit->pet->pet_species ?? '—' }})</span></div>
                <div class="text-sm text-gray-700">Owner: <span class="font-medium">{{ $visit->pet->owner->own_name ?? '—' }}</span></div>
                <div class="text-xs text-gray-500 mt-1">Breed: {{ $visit->pet->pet_breed ?? '—' }}</div>
                <div class="text-xs text-gray-500">Weight: {{ $visit->weight ? number_format($visit->weight, 2).' kg' : '—' }} • Temp: {{ $visit->temperature ? number_format($visit->temperature, 1).' °C' : '—' }}</div>
                <div class="mt-3 inline-flex items-center gap-2 text-indigo-600 text-sm font-medium">View Full Profile <i class="fas fa-arrow-right"></i></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openMedicalHistoryModal()">
                <div class="font-semibold text-gray-900 mb-2">Recent Medical History</div>
                <div class="space-y-2 max-h-40 overflow-y-auto text-xs">
                    @forelse($petMedicalHistory as $record)
                        <div class="border-l-2 pl-2 {{ $record->diagnosis ? 'border-red-400' : 'border-gray-300' }}">
                            <div class="font-medium">{{ \Carbon\Carbon::parse($record->visit_date)->format('M j, Y') }}</div>
                            <div class="text-gray-700 truncate">{{ $record->diagnosis ?? $record->treatment ?? 'Routine Visit' }}</div>
                        </div>
                    @empty
                        <p class="text-gray-500 italic">No history</p>
                    @endforelse
                </div>
                <div class="mt-3 inline-flex items-center gap-2 text-indigo-600 text-sm font-medium">View Full History <i class="fas fa-arrow-right"></i></div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border flex flex-wrap gap-4 justify-between sm:justify-start">
            <button type="button" 
                    onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Initial')"
                    class="flex items-center gap-2 px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 font-semibold shadow-md transition text-sm">
                <i class="fas fa-notes-medical"></i> **Initial Assessment**
            </button>
            <button type="button" 
                    onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Prescription')"
                    class="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-semibold shadow-md transition text-sm">
                <i class="fas fa-prescription"></i> **Prescription**
            </button>
            <button type="button" 
                    onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Appointment')"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-semibold shadow-md transition text-sm">
                <i class="fas fa-calendar-plus"></i> **Set Appointment**
            </button>
            <button type="button" 
                    onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Referral')"
                    class="flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-semibold shadow-md transition text-sm">
                <i class="fas fa-share"></i> **Referral**
            </button>
        </div>
        {{--- END ADDED ROW 3 ---}}

        {{-- Row 4+: Main Content (full width) --}}
        <div class="space-y-6">
            
            @php
                $__vacc = [];
                if (isset($serviceData) && $serviceData) {
                    $__vacc = [
                        'vaccine_name' => $serviceData->vaccine_name ?? null,
                        'dose' => $serviceData->dose ?? null,
                        'manufacturer' => $serviceData->manufacturer ?? null,
                        'batch_no' => $serviceData->batch_no ?? null,
                        'date_administered' => $serviceData->date_administered,
                        'next_due_date' => $serviceData->next_due_date,
                        'administered_by' => $serviceData->administered_by ?? (auth()->user()->user_name ?? null),
                        'remarks' => $serviceData->remarks ?? null,
                    ];
                }
            @endphp
            <form action="{{ route('medical.visits.vaccination.save', $visit->visit_id) }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
                <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">

                {{-- Vaccination Details Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-syringe mr-2 text-blue-600"></i> Administration Details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        
                        {{-- Service Type Selector --}}
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vaccination Service <span class="text-red-500">*</span></label>
                            <select name="service_type" id="service_type_selector" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                <option value="">Select Vaccination Service</option>
                                @forelse($availableServices as $service)
                                    <option value="{{ $service->serv_name }}" 
                                            data-price="{{ $service->serv_price }}"
                                            {{ ($__vacc['service_type'] ?? '') === $service->serv_name ? 'selected' : '' }}>
                                        {{ $service->serv_name }} - ₱{{ number_format($service->serv_price, 2) }}
                                    </option>
                                @empty
                                    <option value="" disabled>No vaccination services available</option>
                                @endforelse
                            </select>
                        </div>

                        {{-- Product Vaccine Selection --}}
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vaccine Product <span class="text-red-500">*</span></label>
                            <select name="vaccine_name" id="vaccine_name_select" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                <option value="">Select Vaccine Product</option>
                                @forelse($vaccines as $vaccine)
                                    <option value="{{ $vaccine->prod_name }}" 
                                            data-stock="{{ $vaccine->prod_stocks }}"
                                            data-price="{{ $vaccine->prod_price }}"
                                            {{ ($__vacc['vaccine_name'] ?? '') === $vaccine->prod_name ? 'selected' : '' }}>
                                        {{ $vaccine->prod_name }} 
                                        (Stock: {{ $vaccine->prod_stocks }}) - ₱{{ number_format($vaccine->prod_price, 2) }}
                                        @if($vaccine->prod_expiry && \Carbon\Carbon::parse($vaccine->prod_expiry)->diffInDays(now()) <= 30)
                                            ⚠️ Expiring Soon
                                        @endif
                                    </option>
                                @empty
                                    <option value="" disabled>No vaccine products in stock</option>
                                @endforelse
                            </select>
                            <small class="text-xs text-gray-500 mt-1">
                                Select from available vaccine products in inventory.
                            </small>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Dose / Volume</label>
                            <input type="text" name="dose" class="w-full border border-gray-300 p-3 rounded-lg" placeholder="e.g., 1 mL" value="{{ old('dose', $__vacc['dose'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Manufacturer</label>
                            <input type="text" name="manufacturer" class="w-full border border-gray-300 p-3 rounded-lg" value="{{ old('manufacturer', $__vacc['manufacturer'] ?? '') }}" placeholder="e.g. Pfizer, Zoetis"/>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Batch No.</label>
                            <input type="text" name="batch_no" class="w-full border border-gray-300 p-3 rounded-lg" value="{{ old('batch_no', $__vacc['batch_no'] ?? '') }}" placeholder="Lot/Batch Number"/>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Date Administered</label>
                            <input type="date" name="date_administered" class="w-full border border-gray-300 p-3 rounded-lg" 
                                value="{{ old('date_administered', optional(\Carbon\Carbon::parse($__vacc['date_administered'] ?? $visit->visit_date))->format('Y-m-d')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Next Due Date</label>
                            <input type="date" name="next_due_date" id="vacc_next_due_date" class="w-full border border-gray-300 p-3 rounded-lg" value="{{
                                old(
                                    'next_due_date', 
                                    optional(\Carbon\Carbon::parse(
                                        $__vacc['next_due_date'] 
                                        ?? optional(\Carbon\Carbon::parse($__vacc['date_administered'] ?? $visit->visit_date))->addDays(14)
                                    ))->format('Y-m-d')
                                )
                            }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Administered By <span class="text-red-500">*</span></label>
                            <select name="administered_by" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                <option value="">Select Veterinarian</option>
                                
                                {{-- Loop through the collection of Veterinarians --}}
                                @if(isset($veterinarians) && $veterinarians->count() > 0)
                                    @foreach($veterinarians as $vet)
                                        <option value="{{ $vet->user_name }}" 
                                            {{ old('administered_by', $__vacc['administered_by'] ?? (auth()->user()->user_name ?? '')) == $vet->user_name ? 'selected' : '' }}>
                                            {{ $vet->user_name }} ({{ $vet->user_licenseNum ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No Veterinarians registered</option>
                                @endif
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks / Observed Reactions</label>
                            <textarea name="remarks" rows="2" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="e.g. Minor lethargy after 2 hours, no local swelling.">{{ old('remarks', $__vacc['remarks'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end items-center pt-4">
                    {{-- REMOVED original "Service Actions" button --}}
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Vaccination Record
                    </button>
                </div>
            </form>
        </div>
        </div>
</div>

{{-- All Modals, Scripts, and Styles from the original file are kept below --}}
<div id="petProfileModal" class="fixed inset-0 bg-black/60 z-50 hidden">
    <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closePetProfileModal()}">
        <div class="bg-white rounded-xl shadow-2xl w-[600px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="font-bold text-lg text-gray-800">Pet Profile</h3>
                <button type="button" onclick="closePetProfileModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
                    @if(!empty($visit->pet->pet_photo))
                        <img src="{{ asset('storage/'.$visit->pet->pet_photo) }}" alt="{{ $visit->pet->pet_name ?? 'Pet' }}" class="w-full h-80 object-cover"/>
                    @else
                        <div class="h-80 w-full flex items-center justify-center text-gray-400 text-lg">
                            <i class="fas fa-paw text-6xl"></i>
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-lg border p-4">
                    <div class="font-semibold text-gray-800 text-lg mb-3 flex items-center gap-2">
                        <i class="fas fa-dog text-blue-600"></i> Pet Information
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500">Name:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->pet_name ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Species:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->pet_species ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Breed:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->pet_breed ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Gender:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->pet_gender ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Age:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->pet_age ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Weight:</span>
                            <div class="font-medium text-gray-800">{{ $visit->weight ? number_format($visit->weight, 2).' kg' : '—' }}</div>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">Temperature:</span>
                            <div class="font-medium text-gray-800">{{ $visit->temperature ? number_format($visit->temperature, 1).' °C' : '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg border p-4">
                    <div class="font-semibold text-gray-800 text-lg mb-3 flex items-center gap-2">
                        <i class="fas fa-user text-green-600"></i> Owner Information
                    </div>
                    <div class="space-y-2 text-sm">
                        <div>
                            <span class="text-gray-500">Name:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_name ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Contact:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_contactnum ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Location:</span>
                            <div class="font-medium text-gray-800">{{ $visit->pet->owner->own_location ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="medicalHistoryModal" class="fixed inset-0 bg-black/60 z-50 hidden">
    <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closeMedicalHistoryModal()}">
        <div class="bg-white rounded-xl shadow-2xl w-[900px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                    <i class="fas fa-history text-orange-600"></i> 
                    Complete Medical History - {{ $visit->pet->pet_name ?? 'Pet' }}
                </h3>
                <button type="button" onclick="closeMedicalHistoryModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
            </div>
            <div class="p-6">
                <div class="space-y-4 max-h-[75vh] overflow-y-auto">
                    @forelse($petMedicalHistory as $record)
                        <div class="border-l-4 pl-4 py-3 {{ $record->diagnosis ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-gray-50' }} rounded-r-lg">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-semibold text-gray-800 text-base">
                                    {{ \Carbon\Carbon::parse($record->visit_date)->format('F j, Y') }}
                                </div>
                                @if(!empty($record->service_type))
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">{{ $record->service_type }}</span>
                                @endif
                            </div>
                            
                            @if($record->diagnosis)
                                <div class="mb-2">
                                    <span class="text-xs font-semibold text-gray-600">Diagnosis:</span>
                                    <div class="text-sm text-gray-800">{{ $record->diagnosis }}</div>
                                </div>
                            @endif

                            @if($record->treatment)
                                <div class="mb-2">
                                    <span class="text-xs font-semibold text-gray-600">Treatment:</span>
                                    <div class="text-sm text-gray-800">{{ $record->treatment }}</div>
                                </div>
                            @endif

                            @if($record->medication)
                                <div class="mb-2">
                                    <span class="text-xs font-semibold text-gray-600">Medication:</span>
                                    <div class="text-sm text-blue-700">{{ $record->medication }}</div>
                                </div>
                            @endif

                            @if(!$record->diagnosis && !$record->treatment)
                                <div class="text-sm text-gray-600 italic">Routine Visit</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-3"></i>
                            <p class="text-gray-500 italic">No medical history on record.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelector = document.getElementById('service_type_selector');
        const vaccineSelect = document.getElementById('vaccine_name_select');
        const productOptGroup = document.getElementById('product_vaccines_optgroup');
        const serviceOptGroup = document.getElementById('service_vaccines_optgroup');
        const vaccineHelperText = document.getElementById('vaccine_helper_text');
        
        const allProductOptions = Array.from(vaccineSelect.querySelectorAll('.product-option'));
        const allServiceOptions = Array.from(vaccineSelect.querySelectorAll('.service-option'));
        
        function updateVaccineSelect(type) {
            // Hide all options initially
            allProductOptions.forEach(opt => opt.style.display = 'none');
            allServiceOptions.forEach(opt => opt.style.display = 'none');
            productOptGroup.style.display = 'none';
            serviceOptGroup.style.display = 'none';

            // Unselect previous selection and set default text
            vaccineSelect.selectedIndex = 0;
            
            if (type === 'product') {
                productOptGroup.style.display = 'block';
                allProductOptions.forEach(opt => opt.style.display = 'block');
                vaccineHelperText.innerHTML = 'Select from available **vaccines in inventory**. Stock levels are shown.';
            } else if (type === 'service') {
                serviceOptGroup.style.display = 'block';
                allServiceOptions.forEach(opt => opt.style.display = 'block');
                vaccineHelperText.innerHTML = 'Select a **general vaccination service** (no stock deduction).';
            }
        }

        // Initial setup
        updateVaccineSelect(typeSelector.value); 

        // Listener for Service Type change
        typeSelector.addEventListener('change', function() {
            updateVaccineSelect(this.value);
            // Re-run low stock warning logic in case a product was selected again
            checkStockWarning(); 
        });

        // Existing low stock warning logic updated to call a function
        if (vaccineSelect) {
            vaccineSelect.addEventListener('change', checkStockWarning);
        }

        function checkStockWarning() {
            const selectedOption = vaccineSelect.options[vaccineSelect.selectedIndex];
            const stock = selectedOption.dataset.stock;
            
            // Remove any existing warnings
            const existingWarning = vaccineSelect.parentElement.querySelector('.stock-warning');
            if (existingWarning) existingWarning.remove();
            
            // Only show warning for product-based items with low stock
            if (selectedOption.dataset.type === 'product' && stock && parseInt(stock) <= 5) {
                const warning = document.createElement('div');
                warning.className = 'stock-warning mt-2 p-2 bg-yellow-50 border border-yellow-300 rounded text-sm text-yellow-800';
                warning.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> Low stock warning: Only ${stock} units remaining`;
                vaccineSelect.parentElement.appendChild(warning);
            }
        }
    });

    // Modal functions... (kept as is)
    function openPetProfileModal() { 
        const m = document.getElementById('petProfileModal'); 
        if(m){ m.classList.remove('hidden'); } 
    }
    
    function closePetProfileModal() { 
        const m = document.getElementById('petProfileModal'); 
        if(m){ m.classList.add('hidden'); } 
    }

    function openMedicalHistoryModal() { 
        const m = document.getElementById('medicalHistoryModal'); 
        if(m){ m.classList.remove('hidden'); } 
    }
    
    function closeMedicalHistoryModal() { 
        const m = document.getElementById('medicalHistoryModal'); 
        if(m){ m.classList.add('hidden'); } 
    }
</script>

{{-- The rest of the modals and scripts are kept unchanged for brevity/completeness --}}
<div id="serviceActivityModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-6 border-b pb-3 sticky top-0 bg-white z-10">
            <h3 >
            </h3>
            <button onclick="closeActivityModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>

        {{-- Context Bar - Adjusted for a simpler layout in the modal --}}
        <div class="grid grid-cols-3 gap-6 mb-6">
            <div class="col-span-3 bg-gray-100 p-3 rounded-lg text-sm border-l-4 border-purple-500">
                <div class="flex flex-wrap gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500">Pet:</label>
                        <div id="activity_pet_name" class="font-medium text-gray-800">-</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500">Owner:</label>
                        <div id="activity_owner_id" class="font-medium text-gray-800">-</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500">Current Action:</label>
                        <div id="current_activity_tab_label" class="font-bold text-purple-600">-</div>
                    </div>
                </div>
            </div>
            
            {{-- Tab Buttons REMOVED: Now managed by the switchActivityTab function called from the outside buttons --}}
        </div>

        {{-- Scrollable Content Area --}}
        <div class="h-auto"> 
            
            {{-- Appointment Tab Content --}}
            <div id="activity_appointment_content" class="activity-tab-content space-y-4">
                <h4 class="text-lg font-semibold text-blue-600">Set Follow-up Appointment</h4>
                <form id="activityAppointmentForm" action="{{ route('medical.appointments.store') }}" method="POST" class="space-y-4 border border-blue-200 p-4 rounded-lg bg-blue-50">
                    @csrf
                    <input type="hidden" name="pet_id" id="activity_appoint_pet_id">
                    <input type="hidden" name="appoint_status" value="Scheduled">
                    <input type="hidden" name="active_tab" value="visits">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Follow-up Type</label>
                            <select name="appoint_type" id="activity_appoint_type" class="w-full border border-gray-300 p-2 rounded-lg" required>
                                <option value="Follow-up">General Follow-up</option>
                                <option value="Vaccination Follow-up">Vaccination Follow-up</option>
                                <option value="Deworming Follow-up">Deworming Follow-up</option>
                                <option value="Post-Surgical Recheck">Post-Surgical Recheck</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                            <input type="date" name="appoint_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Time</label>
                            <select name="appoint_time" class="w-full border border-gray-300 p-2 rounded-lg" required>
                                @foreach (['09:00:00','10:00:00','11:00:00','13:00:00','14:00:00','15:00:00','16:00:00'] as $time)
                                    <option value="{{ $time }}">{{ \Carbon\Carbon::parse($time)->format('h:i A') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                            <textarea name="appoint_description" rows="2" class="w-full border border-gray-300 p-2 rounded-lg" placeholder="Reason for follow-up"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                            <i class="fas fa-calendar-alt mr-1"></i> Create Appointment
                        </button>
                    </div>
                </form>
            </div>

            {{-- Initial Assessment Tab Content --}}
            <div id="activity_initial_content" class="activity-tab-content hidden space-y-4">
                <h4 class="text-lg font-semibold text-indigo-600">Initial Assessment</h4>
                <form id="activityInitialAssessmentForm" action="{{ route('medical.initial_assessments.store') }}" method="POST" onsubmit="return handleInitialAssessmentSubmit(event)" class="space-y-4 border border-indigo-200 p-4 rounded-lg bg-indigo-50">
                    @csrf
                    <input type="hidden" name="pet_id" id="activity_initial_pet_id" value="{{ $visit->pet_id ?? '' }}">
                    <input type="hidden" name="visit_id" id="activity_initial_visit_id" value="{{ $visit->visit_id ?? '' }}">
                    <input type="hidden" name="active_tab" value="visits">

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-gray-300 bg-white">
                            <tbody>
                                <tr class="border-b">
                                    <td class="p-3 align-top w-1/3">
                                        <label class="font-medium">Is your pet sick?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="is_sick" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="is_sick" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top w-1/3">
                                        <label class="font-medium">Has your pet been treated recently?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="been_treated" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="been_treated" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top w-1/3">
                                        <label class="font-medium">Does your pet get table food/human food?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="table_food" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="table_food" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">How many times per day do you feed?</label>
                                        <div class="flex flex-wrap gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Once"> <span>Once</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Twice"> <span>Twice</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Thrice"> <span>Thrice</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Is your pet on heartworm preventative?</label>
                                        <div class="flex gap-4 mt-2 flex-wrap">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="No"> <span>No</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="No Idea"> <span>No Idea</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Any injury or accident in the past 30 days?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="injury_accident" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="injury_accident" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Allergic To Any Medications/Vaccines?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="allergies" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="allergies" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Had any surgery for the past 30 days?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="surgery_past_30" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="surgery_past_30" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Currently on any medications/vitamins/OTC?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="current_meds" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="current_meds" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Appetite Normal?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="appetite_normal" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="appetite_normal" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Diarrhoea</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="diarrhoea" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="diarrhoea" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Vomiting</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="vomiting" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="vomiting" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Drinking more or less water than usual?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="drinking_unusual" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="drinking_unusual" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Weakness?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="weakness" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="weakness" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Gagging?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="gagging" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="gagging" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Coughing?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="coughing" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="coughing" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Sneezing?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="sneezing" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="sneezing" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Scratching?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scratching" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scratching" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Shaking Head?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="shaking_head" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="shaking_head" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Urinating more or less than usual?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="urinating_unusual" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="urinating_unusual" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Limping? Which Leg?</label>
                                        <div class="flex flex-wrap gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="None"> <span>None</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Front Left"> <span>Front Left</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Front Right"> <span>Front Right</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Back Left"> <span>Back Left</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Back Right"> <span>Back Right</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Scooting?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scooting" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scooting" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">History of seizures?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="seizures" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="seizures" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Unusually Bad Breath?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="bad_breath" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="bad_breath" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Unusual Discharge?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="discharge" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="discharge" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Did the pet eat this morning?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="ate_this_morning" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="ate_this_morning" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold">
                            <i class="fas fa-save mr-1"></i> Save Assessment
                        </button>
                    </div>
                </form>
            </div>

            {{-- Prescription Tab Content --}}
            <div id="activity_prescription_content" class="activity-tab-content hidden space-y-4">
                <h4 class="text-lg font-semibold text-green-600">Add New Prescription</h4>
                <form id="activityPrescriptionForm" onsubmit="return handleActivityPrescriptionSubmit(event)" class="space-y-4 border border-green-200 p-4 rounded-lg bg-green-50">
                    @csrf
                    <input type="hidden" name="pet_id" id="activity_prescription_pet_id">
                    <input type="hidden" name="medications_json" id="activity_medications_json">
                    <input type="hidden" name="active_tab" value="visits">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Prescription Date</label>
                        <input type="date" name="prescription_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                    </div>

                    <div id="activityMedicationContainer" class="space-y-3">
                        {{-- Medication fields added by JS --}}
                    </div>
                    <button type="button" onclick="addActivityMedicationField()" class="bg-indigo-500 text-white px-3 py-1 rounded text-sm hover:bg-indigo-600">
                        <i class="fas fa-plus"></i> Add Medication
                    </button>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Diagnosis / Reason</label>
                        <textarea name="differential_diagnosis" rows="2" class="w-full border border-gray-300 p-2 rounded-lg" placeholder="Diagnosis for this prescription"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">General Notes</label>
                        <textarea name="notes" rows="2" class="w-full border border-gray-300 p-2 rounded-lg" placeholder="Dietary instructions, follow-up recommendations"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                            <i class="fas fa-save mr-1"></i> Save Prescription
                        </button>
                    </div>
                </form>
            </div>

            {{-- Referral Tab Content --}}
            <div id="activity_referral_content" class="activity-tab-content hidden space-y-4">
                <h4 class="text-lg font-semibold text-red-600">Create New Referral</h4>
                <form id="activityReferralForm" action="{{ route('medical.referrals.store') }}" method="POST" class="space-y-4 border border-red-200 p-4 rounded-lg bg-red-50">
                    @csrf
                    <input type="hidden" name="appointment_id" id="activity_referral_appoint_id" value="{{ $visit->visit_id ?? '' }}">
                    <input type="hidden" name="active_tab" value="visits">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Referral Date</label>
                            <input type="date" name="ref_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Refer To Branch</label>
                            <select name="ref_to" class="w-full border border-gray-300 p-2 rounded-lg" required>
                                <option value="">Select Branch</option>
                                @foreach($allBranches as $branch)
                                    <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Reason for Referral</label>
                        <textarea name="ref_description" rows="3" class="w-full border border-gray-300 p-2 rounded-lg" placeholder="Detailed reason for referring the pet" required></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                            <i class="fas fa-share mr-1"></i> Submit Referral
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div id="toastContainer" class="toast-container"></div>

<script>
    // Global state
    let availablePrescriptionProducts = @json($allProducts);
    let activityMedicationCounter = 0;
    let currentActivityTab = 'appointment'; // Keep track of the currently active tab

    function switchActivityTab(tabName) {
        // Remove 'active' class from all tab contents
        document.querySelectorAll('.activity-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Add 'active' class to the target tab content and update the label
        document.getElementById(`activity_${tabName}_content`).classList.remove('hidden');
        
        const labelMap = {
            'appointment': 'Set Appointment',
            'prescription': 'Add New Prescription',
            'referral': 'Create New Referral',
            'initial': 'Initial Assessment'
        };
        document.getElementById('current_activity_tab_label').textContent = labelMap[tabName] || tabName;
        
        currentActivityTab = tabName; // Update global state
    }

    function openActivityModal(petId, ownerName, targetTab) {
        const modal = document.getElementById('serviceActivityModal');
        const petName = document.getElementById('activity_pet_name');
        
        // Find pet name from allPets global var or default
        const pet = @json($allPets).find(p => String(p.pet_id) === String(petId));

        // 1. Set Pet/Owner context
        petName.textContent = pet?.pet_name || 'N/A';
        document.getElementById('activity_owner_id').textContent = ownerName || pet?.owner.own_name || 'N/A'; // Use ownerName passed from button or lookup
        document.getElementById('activity_appoint_pet_id').value = petId;
        document.getElementById('activity_prescription_pet_id').value = petId;
        document.getElementById('activity_referral_appoint_id').value = String({{ $visit->visit_id ?? '""' }}); // Assuming appointment_id/visit_id is the current visit ID
        
        const initPetInput = document.getElementById('activity_initial_pet_id');
        const initVisitInput = document.getElementById('activity_initial_visit_id');
        if (initPetInput) initPetInput.value = petId;
        if (initVisitInput) initVisitInput.value = String({{ $visit->visit_id ?? '""' }});
        
        // 2. Pre-fill default values for Appointment Type
        document.getElementById('activity_appoint_type').value = 
            targetTab.includes('Vaccination') ? 'Vaccination Follow-up' : 
            targetTab.includes('Deworming') ? 'Deworming Follow-up' : 'Follow-up';

        // 3. Reset Prescription fields
        document.getElementById('activityPrescriptionForm').reset();
        document.getElementById('activityMedicationContainer').innerHTML = '';
        activityMedicationCounter = 0;
        addActivityMedicationField();
        
        // 4. Show the requested tab and modal
        const tabKey = targetTab.toLowerCase().includes('appointment') ? 'appointment' :
                        targetTab.toLowerCase().includes('prescription') ? 'prescription' :
                        targetTab.toLowerCase().includes('referral') ? 'referral' :
                        targetTab.toLowerCase().includes('initial') ? 'initial' : 'appointment';
                        
        switchActivityTab(tabKey);
        modal.classList.remove('hidden');
    }

    function closeActivityModal() {
        document.getElementById('serviceActivityModal').classList.add('hidden');
    }
    
    // --- Prescription Sub-Functions (Rest of the original functions unchanged) ---
    // (addActivityMedicationField, removeActivityMedicationField, setupActivityProductSearch, handleActivityPrescriptionSubmit, showToast, handleInitialAssessmentSubmit are all left as is)

    function addActivityMedicationField() {
        const container = document.getElementById('activityMedicationContainer');
        const fieldId = ++activityMedicationCounter;
        
        const fieldHtml = `
            <div class="medication-field border border-gray-300 p-3 rounded-lg bg-white" data-field-id="${fieldId}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-medium text-gray-700">Medication ${fieldId}</h4>
                    ${fieldId > 1 ? `<button type="button" onclick="removeActivityMedicationField(${fieldId})" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-trash"></i> Remove</button>` : ''}
                </div>
                <div class="relative mb-3">
                    <label class="block text-xs text-gray-600 mb-1">Product Name / Manual Entry</label>
                    <input type="text" class="product-search w-full border px-2 py-2 rounded-lg text-sm" placeholder="Search product or enter manually" data-field-id="${fieldId}">
                    <div class="product-suggestions absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-32 overflow-y-auto hidden" data-field-id="${fieldId}"></div>
                    <input type="hidden" class="selected-product-id" data-field-id="${fieldId}">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Instructions (Sig.)</label>
                    <textarea class="medication-instructions w-full border px-2 py-2 rounded-lg text-sm" rows="2" data-field-id="${fieldId}" placeholder="e.g., Take 1 capsule twice daily for 7 days" required></textarea>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', fieldHtml);
        setupActivityProductSearch(fieldId);
    }

    function removeActivityMedicationField(fieldId) {
        const field = document.querySelector(`#activityMedicationContainer .medication-field[data-field-id="${fieldId}"]`);
        if (field) {
            field.remove();
        }
    }

    function setupActivityProductSearch(fieldId) {
        const searchInput = document.querySelector(`.product-search[data-field-id="${fieldId}"]`);
        const suggestionsDiv = document.querySelector(`.product-suggestions[data-field-id="${fieldId}"]`);
        const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`);

        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            clearTimeout(searchTimeout);

            // Clear selected product ID on manual typing
            productIdInput.value = '';

            if (query.length < 2) {
                suggestionsDiv.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(() => {
                const filtered = availablePrescriptionProducts.filter(p => p.prod_name.toLowerCase().includes(query));
                suggestionsDiv.innerHTML = '';
                
                if (filtered.length > 0) {
                    filtered.forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'product-suggestion-item px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm';
                        item.innerHTML = `<div>${product.prod_name}</div><div class="text-xs text-gray-500">Stock: ${product.prod_stocks} - ₱${parseFloat(product.prod_price || 0).toFixed(2)}</div>`;
                        
                        item.onclick = function() {
                            productIdInput.value = product.prod_id;
                            searchInput.value = product.prod_name;
                            suggestionsDiv.classList.add('hidden');
                            searchInput.focus();
                        };
                        suggestionsDiv.appendChild(item);
                    });
                    suggestionsDiv.classList.remove('hidden');
                } else {
                    suggestionsDiv.innerHTML = '<div class="px-3 py-2 text-gray-500 text-xs">No matching products found.</div>';
                    suggestionsDiv.classList.remove('hidden');
                }
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.add('hidden');
            }
        });
    }

    function handleActivityPrescriptionSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const medications = [];
        let isValid = true;
        
        document.querySelectorAll('#activityMedicationContainer .medication-field').forEach(field => {
            const fieldId = field.dataset.fieldId;
            const searchInput = document.querySelector(`input.product-search[data-field-id="${fieldId}"]`);
            const productIdInput = document.querySelector(`input.selected-product-id[data-field-id="${fieldId}"]`);
            const instructionsTextarea = document.querySelector(`textarea.medication-instructions[data-field-id="${fieldId}"]`);
            
            const productName = searchInput.value.trim();
            const instructions = instructionsTextarea.value.trim();
            
            if (productName && instructions) {
                medications.push({
                    product_id: productIdInput.value || null,
                    product_name: productName,
                    instructions: instructions
                });
            } else if (productName || instructions) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            alert('Please ensure all medication fields are complete or empty.');
            return;
        }

        if (medications.length === 0) {
            alert('Please add at least one medication.');
            return;
        }
        
        // Finalize data
        document.getElementById('activity_medications_json').value = JSON.stringify(medications);
        form.action = "{{ route('medical.prescriptions.store') }}"; // Set the correct submission route
        form.method = 'POST';
        
        // Submit the form
        const submitBtn = form.querySelector('button[type="submit"]');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        
        form.submit();
    }

    // Toast notification function
    function showToast(type, message) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type === 'success' ? 'toast-success' : 'toast-error'}`;
        
        // Create toast content with icon
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icon} mr-2"></i>
                <span>${message}</span>
            </div>
        `;

        container.appendChild(toast);

        // Remove toast after 3 seconds with fade out animation
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 500);
        }, 3000);
    }

    function handleInitialAssessmentSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showToast('success', data.message || 'Initial assessment saved successfully!');
                
                // Close the modal after a delay
                setTimeout(() => {
                    closeActivityModal();
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to save initial assessment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', error.message || 'Failed to save initial assessment. Please try again.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    }
</script>

<style>
/* Add necessary styles for the toast container if not already in your CSS */
.toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 55; /* Higher than modals */
}

.toast-notification {
    padding: 12px 20px;
    margin-top: 10px;
    border-radius: 8px;
    color: white;
    font-size: 0.9rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    opacity: 1;
    animation: fadeIn 0.5s ease-out forwards;
}

.toast-success {
    background-color: #10B981; /* Tailwind green-500 */
}

.toast-error {
    background-color: #EF4444; /* Tailwind red-500 */
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(20px); }
}
</style>

@endsection