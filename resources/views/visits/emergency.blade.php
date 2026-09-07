@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50 p-4 sm:p-6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800">Emergency Workspace</h2>
            <a href="{{ route('medical.index', ['tab' => 'emergency']) }}" 
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

        

        {{-- Row 4+: Main Content (full width) --}}
        <div class="space-y-6">

            @php
                $__emerg = [];
                if (isset($serviceData) && $serviceData) {
                    $__emerg = [
                        'emergency_type' => $serviceData->case_type ?? null,
                        'arrival_time' => null, 
                        'vitals' => $serviceData->vital_signs ?? null,
                        'triage_notes' => $serviceData->remarks ?? null,
                        'procedures' => $serviceData->immediate_treatment ?? null,
                        'immediate_meds' => $serviceData->medications_administered ?? null,
                        'outcome' => $serviceData->outcome ?? null,
                        'attended_by' => $serviceData->attended_by ?? (auth()->user()->user_name ?? null),
                    ];
                }
            @endphp
            <form action="{{ route('medical.visits.emergency.save', $visit->visit_id) }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
                <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">

                {{-- Emergency Triage and Details Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-notes-medical mr-2 text-orange-600"></i> Initial Assessment</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Arrival Time</label>
                            <input type="datetime-local" name="arrival_time" class="w-full border border-gray-300 p-3 rounded-lg" 
                                value="{{ old('arrival_time', optional(\Carbon\Carbon::parse($visit->visit_date))->format('Y-m-d\TH:i')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Emergency Type</label>
                            <select name="emergency_type" id="emergency_type_select" class="w-full border border-gray-300 p-3 rounded-lg">
                                @php $selectedType = old('emergency_type', $__emerg['emergency_type'] ?? '') @endphp
                                <option value="">Select type</option>
                                @if(isset($availableServices) && $availableServices->count() > 0)
                                    @foreach($availableServices as $service)
                                        @php
                                          $serv_prod = $service->products->map(function($p) {
                                                    return [
                                                        "prod_id" => $p->prod_id,
                                                        "prod_name" => $p->prod_name,
                                                        "prod_stocks" => $p->prod_stocks,
                                                        "prod_expiry" => $p->prod_expiry,
                                                        "quantity_used" => $p->pivot->quantity_used ?? 1
                                                    ];
                                                });
                                        @endphp
                                        <option value="{{ $service->serv_name }}" 
                                                data-service-id="{{ $service->serv_id }}"
                                                data-products='@json($serv_prod)'
                                                {{ $selectedType === $service->serv_name ? 'selected' : '' }}>
                                            {{ $service->serv_name }}
                                        </option>
                                    @endforeach
                                @else
                                    {{-- Fallback options if no services are found --}}
                                    <option value="Trauma" {{ $selectedType === 'Trauma' ? 'selected' : '' }}>Trauma</option>
                                    <option value="Poisoning" {{ $selectedType === 'Poisoning' ? 'selected' : '' }}>Poisoning</option>
                                    <option value="Respiratory distress" {{ $selectedType === 'Respiratory distress' ? 'selected' : '' }}>Respiratory distress</option>
                                    <option value="Seizure" {{ $selectedType === 'Seizure' ? 'selected' : '' }}>Seizure</option>
                                @endif
                            </select>
                            <input type="hidden" name="service_id" id="service_id_hidden" value="{{ old('service_id', '') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Emergency Consumables</label>
                            <select name="consumable_product_id" id="emergency_product_select" class="w-full border border-gray-300 p-3 rounded-lg" disabled>
                                <option value="">First select an emergency type</option>
                            </select>
                            <small class="text-xs text-gray-500 mt-1" id="product_helper_text">
                                Select an emergency type first to see available consumable products.
                            </small>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vitals on Arrival</label>
                            <input type="text" name="vitals" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="Temp, HR, RR, CRT" value="{{ old('vitals', $__emerg['vitals'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Attending Vet / Staff</label>
                            <input type="text" name="attended_by" class="w-full border border-gray-300 p-3 rounded-lg" 
                                        value="{{ old('attended_by', $__emerg['attended_by'] ?? (auth()->user()->user_name ?? '')) }}" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Triage Notes / Arrival Condition</label>
                            <textarea name="triage_notes" rows="3" class="w-full border border-gray-300 p-3 rounded-lg" 
                                            placeholder="Initial status (ABCs), triage level, client concerns...">{{ old('triage_notes', $__emerg['triage_notes'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Treatment and Procedures Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-600">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-medkit mr-2 text-red-600"></i> Treatment & Procedures</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Immediate Intervention</label>
                            <input type="text" name="immediate_intervention" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="IV, oxygen, CPR, etc." value="{{ old('immediate_intervention', $__emerg['procedures'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Detailed Procedures Performed</label>
                            <textarea name="procedures" rows="3" class="w-full border border-gray-300 p-3 rounded-lg" 
                                            placeholder="Fluid therapy, catheter placement, intubation, wound management...">{{ old('procedures', $__emerg['procedures'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Immediate Medications Administered</label>
                            <textarea name="immediate_meds" rows="2" class="w-full border border-gray-300 p-3 rounded-lg" 
                                            placeholder="Drug, dosage, route, time.">{{ old('immediate_meds', $__emerg['immediate_meds'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Final Outcome</label>
                            <select name="outcome" class="w-full border border-gray-300 p-3 rounded-lg">
                                @php($selectedOutcome = old('outcome', $__emerg['outcome'] ?? ''))
                                <option value="Stabilized" {{ $selectedOutcome === 'Stabilized' ? 'selected' : '' }}>Stabilized</option>
                                <option value="Hospitalized" {{ $selectedOutcome === 'Hospitalized' ? 'selected' : '' }}>Hospitalized</option>
                                <option value="Discharged" {{ $selectedOutcome === 'Discharged' ? 'selected' : '' }}>Discharged</option>
                                <option value="Euthanized" {{ $selectedOutcome === 'Euthanized' ? 'selected' : '' }}>Euthanized</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end items-center pt-4">
                    {{-- REMOVED OLD SERVICE ACTIONS BUTTON --}}
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Emergency Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    // Emergency Product Selection Logic
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelector = document.getElementById('emergency_type_select');
        const productSelect = document.getElementById('emergency_product_select');
        const productHelperText = document.getElementById('product_helper_text');
        
        // Listen for service selection changes
        typeSelector.addEventListener('change', function() {
            updateProductOptions();
        });

        // Listen for product selection changes to show stock warnings
        productSelect.addEventListener('change', function() {
            checkStockWarning();
        });

        function updateProductOptions() {
            const selectedOption = typeSelector.options[typeSelector.selectedIndex];
            
            // Update hidden field with selected service_id
            const serviceIdHidden = document.getElementById('service_id_hidden');
            if (serviceIdHidden) {
                serviceIdHidden.value = selectedOption.getAttribute('data-service-id') || '';
            }
            
            // Clear existing options
            productSelect.innerHTML = '<option value="">Select Emergency Consumable</option>';
            
            if (!selectedOption.value) {
                productSelect.disabled = true;
                productHelperText.textContent = 'Select an emergency type first to see available consumable products.';
                return;
            }

            // Get products from the selected service
            const products = JSON.parse(selectedOption.dataset.products || '[]');
            
            if (products.length === 0) {
                productSelect.innerHTML = '<option value="">No consumable products for this emergency type</option>';
                productSelect.disabled = true;
                productHelperText.innerHTML = '<span class="text-red-600">⚠️ This emergency type has no consumable products configured.</span>';
                return;
            }

            // Enable the select and populate with products
            productSelect.disabled = false;
            productHelperText.innerHTML = 'Available consumable products for the selected emergency type. <strong>Only stock is displayed.</strong>';
            
            products.forEach(function(product) {
                const option = document.createElement('option');
                option.value = product.prod_id;
                option.dataset.stock = product.prod_stocks;
                option.dataset.prodId = product.prod_id;
                option.dataset.quantityUsed = product.quantity_used;
                
                // Build option text - ONLY SHOW STOCK, NO PRICE
                let optionText = `${product.prod_name} (Stock: ${product.prod_stocks})`;
                
                // Check for low stock or expiring
                if (product.prod_stocks <= 5) {
                    optionText += ' ⚠️ Low Stock';
                }
                
                if (product.prod_expiry) {
                    const expiryDate = new Date(product.prod_expiry);
                    const daysUntilExpiry = Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24));
                    if (daysUntilExpiry <= 30 && daysUntilExpiry > 0) {
                        optionText += ' ⚠️ Expiring Soon';
                    }
                }
                
                option.textContent = optionText;
                
                // Disable if out of stock
                if (product.prod_stocks <= 0) {
                    option.disabled = true;
                    option.textContent += ' - OUT OF STOCK';
                }
                
                productSelect.appendChild(option);
            });
            
            // Clear any existing warnings
            const existingWarning = productSelect.parentElement.querySelector('.stock-warning');
            if (existingWarning) existingWarning.remove();
        }

        function checkStockWarning() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const stock = selectedOption ? parseInt(selectedOption.dataset.stock) : 0;
            
            // Remove existing warning
            const existingWarning = productSelect.parentElement.querySelector('.stock-warning');
            if (existingWarning) existingWarning.remove();
            
            // Show warning for low stock
            if (selectedOption && selectedOption.value && stock <= 5) {
                const warningDiv = document.createElement('div');
                warningDiv.className = 'stock-warning text-xs mt-1 text-orange-600 font-medium';
                warningDiv.innerHTML = `⚠️ Warning: Only ${stock} units left in stock!`;
                productSelect.parentElement.appendChild(warningDiv);
            }
        }

        // Initialize on page load if there's already a selected emergency type
        if (typeSelector.value) {
            updateProductOptions();
        }
    });
</script>

@endsection