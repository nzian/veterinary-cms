@extends('AdminBoard')

@section('content')
{{-- Initialize $__surg safely at the top level --}}
@php
    $__surg = [];
    if (isset($serviceData) && $serviceData) {
        $__surg = [
            'surgery_type' => $serviceData->procedure_name ?? null,
            'service_id' => $serviceData->service_id ?? null,
            'start_time' => $serviceData->start_time,
            'end_time' => $serviceData->end_time,
            'checklist' => $serviceData->findings ?? null,
            'post_op_notes' => $serviceData->post_op_instructions ?? null,
            'staff' => $serviceData->surgeon ?? null,
            'anesthesia' => $serviceData->anesthesia_used ?? null,
        ];
    }
    
    // Get pet species for filtering surgical services
    $petSpecies = strtolower($visit->pet->pet_species ?? '');
@endphp
<div class="min-h-screen bg-gradient-to-br from-rose-50 to-red-50 p-4 sm:p-6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800">Surgical Services Workspace</h2>
            <a href="{{ route('medical.index', ['tab' => 'surgical']) }}" 
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

        {{-- Row 3+: Main Content (full width) --}}
        <div class="space-y-6">
            <form action="{{ route('medical.visits.surgical.save', $visit->visit_id) }}" method="POST" class="space-y-6" id="surgical_form">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
                <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
                <input type="hidden" name="service_id" id="service_id_hidden" value="{{ old('service_id', $__surg['service_id'] ?? '') }}">

                {{-- Surgical Record Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-file-medical mr-2 text-green-600"></i> Procedure Details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Service Type Selector --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Surgery Type / Procedure Name <span class="text-red-500">*</span></label>
                            <select name="surgery_type" id="surgery_type_selector" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                <option value="">Select Surgical Service</option>
                                @forelse($availableServices as $service)
                                    @php
                                        $productsData = $service->products->map(function($p) {
                                            return [
                                                'prod_id' => $p->prod_id,
                                                'prod_name' => $p->prod_name,
                                                'prod_stocks' => $p->prod_stocks,
                                                'prod_expiry' => $p->prod_expiry,
                                                'quantity_used' => $p->pivot->quantity_used ?? 1
                                            ];
                                        })->toArray();
                                        
                                        // Check if service matches pet species
                                        $serviceName = strtolower($service->serv_name ?? '');
                                        $matchesSpecies = false;
                                        
                                        if ($petSpecies === 'dog' && (str_contains($serviceName, 'dog') || str_contains($serviceName, 'canine'))) {
                                            $matchesSpecies = true;
                                        } elseif ($petSpecies === 'cat' && (str_contains($serviceName, 'cat') || str_contains($serviceName, 'feline'))) {
                                            $matchesSpecies = true;
                                        } elseif (!str_contains($serviceName, 'dog') && !str_contains($serviceName, 'cat') && !str_contains($serviceName, 'canine') && !str_contains($serviceName, 'feline')) {
                                            // Show services that don't specify species (generic services)
                                            $matchesSpecies = true;
                                        }
                                    @endphp
                                    @if($matchesSpecies)
                                    <option value="{{ $service->serv_name }}" 
                                            data-service-id="{{ $service->serv_id }}"
                                            data-price="{{ $service->serv_price }}"
                                            data-products='@json($productsData)'
                                            {{ ($__surg['surgery_type'] ?? '') === $service->serv_name ? 'selected' : '' }}>
                                        {{ $service->serv_name }} - ₱{{ number_format($service->serv_price, 2) }}
                                    </option>
                                    @endif
                                @empty
                                    <option value="" disabled>No surgical services available</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Surgeon / Assistant(s)</label>
                            <input type="text" name="staff" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="Lead Vet & Tech/Assistants" value="{{ old('staff', $__surg['staff'] ?? (auth()->user()->user_name ?? '')) }}" />
                        </div>
                        {{-- Anesthesia Product Selection --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Anesthesia Used</label>
                            <select name="anesthesia" id="anesthesia_select" class="w-full border border-gray-300 p-3 rounded-lg" disabled>
                                <option value="">First select a surgical service</option>
                            </select>
                            <small class="text-xs text-gray-500 mt-1" id="anesthesia_helper_text">
                                Select a surgical service first to see available anesthesia products.
                            </small>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:col-span-2">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
                                <input type="datetime-local" name="start_time" class="w-full border border-gray-300 p-3 rounded-lg"
                                       value="{{ old('start_time', $__surg['start_time'] ?? null) }}"/>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
                                <input type="datetime-local" name="end_time" class="w-full border border-gray-300 p-3 rounded-lg"
                                       value="{{ old('end_time', $__surg['end_time'] ?? null) }}"/>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pre-op Checklist / Findings</label>
                            <textarea name="checklist" rows="3" class="w-full border border-gray-300 p-3 rounded-lg" 
                                      placeholder="Consent signed, blood work WNL, surgical findings.">{{ old('checklist', $__surg['checklist'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-between items-center pt-4">
                   
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Surgical Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pet Profile Modal (Photo + Pet & Owner Info Only) -->
<div id="petProfileModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closePetProfileModal()}">
    <div class="bg-white rounded-xl shadow-2xl w-[600px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800">Pet Profile</h3>
        <button type="button" onclick="closePetProfileModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-6 space-y-4">
        <!-- Pet Photo -->
        <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
          @if(!empty($visit->pet->pet_photo))
            <img src="{{ asset('storage/'.$visit->pet->pet_photo) }}" alt="{{ $visit->pet->pet_name ?? 'Pet' }}" class="w-full h-80 object-cover"/>
          @else
            <div class="h-80 w-full flex items-center justify-center text-gray-400 text-lg">
              <i class="fas fa-paw text-6xl"></i>
            </div>
          @endif
        </div>

        <!-- Pet Information -->
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

        <!-- Owner Information -->
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

<!-- Medical History Modal (History Only) -->
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

  // Handle surgical service selection and populate anesthesia products
  document.addEventListener('DOMContentLoaded', function() {
    const surgeryTypeSelector = document.getElementById('surgery_type_selector');
    const anesthesiaSelect = document.getElementById('anesthesia_select');
    const serviceIdHidden = document.getElementById('service_id_hidden');
    const anesthesiaHelperText = document.getElementById('anesthesia_helper_text');

    if (surgeryTypeSelector) {
      surgeryTypeSelector.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const serviceId = selectedOption.getAttribute('data-service-id');
        const productsData = selectedOption.getAttribute('data-products');

        // Update hidden service_id field
        if (serviceIdHidden) {
          serviceIdHidden.value = serviceId || '';
        }

        // Clear and populate anesthesia select
        anesthesiaSelect.innerHTML = '<option value="">Select Anesthesia Product</option>';
        
        if (productsData) {
          try {
            const products = JSON.parse(productsData);
            
            if (products.length > 0) {
              anesthesiaSelect.disabled = false;
              anesthesiaHelperText.textContent = 'Select anesthesia product from the list';
              anesthesiaHelperText.classList.remove('text-red-500');
              anesthesiaHelperText.classList.add('text-gray-500');
              
              products.forEach(function(product) {
                const option = document.createElement('option');
                option.value = product.prod_name;
                option.setAttribute('data-prod-id', product.prod_id);
                
                let optionText = product.prod_name;
                if (product.prod_stocks !== undefined) {
                  optionText += ` (Stock: ${product.prod_stocks})`;
                }
                if (product.prod_expiry) {
                  optionText += ` - Exp: ${product.prod_expiry}`;
                }
                
                option.textContent = optionText;
                
                // Disable if out of stock
                if (product.prod_stocks <= 0) {
                  option.disabled = true;
                  option.textContent += ' - OUT OF STOCK';
                }
                
                anesthesiaSelect.appendChild(option);
              });
            } else {
              anesthesiaSelect.disabled = true;
              anesthesiaSelect.innerHTML = '<option value="">No anesthesia products linked to this service</option>';
              anesthesiaHelperText.textContent = 'No products available. Please add products to this service in the inventory.';
              anesthesiaHelperText.classList.remove('text-gray-500');
              anesthesiaHelperText.classList.add('text-red-500');
            }
          } catch (e) {
            console.error('Error parsing products data:', e);
            anesthesiaSelect.disabled = true;
            anesthesiaHelperText.textContent = 'Error loading products';
          }
        } else {
          anesthesiaSelect.disabled = true;
          anesthesiaSelect.innerHTML = '<option value="">No anesthesia products available</option>';
        }
      });

      // Trigger change event if there's a pre-selected value
      if (surgeryTypeSelector.value) {
        surgeryTypeSelector.dispatchEvent(new Event('change'));
        
        // After populating anesthesia dropdown, restore saved anesthesia value
        setTimeout(function() {
          const savedAnesthesia = "{{ $__surg['anesthesia'] ?? '' }}";
          if (savedAnesthesia && anesthesiaSelect) {
            anesthesiaSelect.value = savedAnesthesia;
          }
        }, 100);
      }
    }
  });
</script>
@endsection