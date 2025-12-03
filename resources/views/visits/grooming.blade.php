@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-4 sm:p-6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800">Grooming Service Workspace</h2>
            <a href="{{ route('medical.index', ['tab' => 'grooming']) }}" 
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
            {{-- Grooming Agreement Card (Fixed Structure) --}}
            <div id="agreement-card" class="bg-white rounded-xl shadow-lg p-6 border-l-4 {{ $visit->groomingAgreement ? 'border-green-500' : 'border-red-500' }} print:border-0 print:shadow-none print:p-0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Grooming Agreement Consent</h3>
                    @if($visit->groomingAgreement)
                        <span class="text-sm px-3 py-1 rounded-full bg-green-100 text-green-800 font-semibold flex items-center gap-1">
                            <i class="fas fa-check-circle"></i> **Signed**
                        </span>
                    @else
                        <span class="text-sm px-3 py-1 rounded-full bg-red-100 text-red-800 font-semibold flex items-center gap-1">
                            <i class="fas fa-exclamation-triangle"></i> **PENDING**
                        </span>
                    @endif
                </div>
                
                {{-- AGREEMENT PENDING: Show the form to be signed --}}
                @if(!$visit->groomingAgreement)
                
                    <div class="p-4 border border-red-300 bg-red-50 rounded-lg text-sm mb-4">
                        <p class="font-semibold text-red-800">Action Required:</p>
                        <p class="text-gray-700">The Grooming Agreement must be viewed and electronically signed by the owner/representative before proceeding.</p>
                        <button type="button" onclick="openAgreementModal()" class="mt-3 px-4 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                            <i class="fas fa-signature mr-1"></i> Open and Sign Agreement
                        </button>
                    </div>

                    {{-- Hidden Form: This is the actual form that gets populated and submitted --}}
                    <form id="agreement-form-data" action="{{ route('medical.visits.grooming.agreement.store', $visit->visit_id) }}" method="POST" class="hidden">
                        @csrf
                        <input type="hidden" name="signature_data" id="signature_data">
                        <input type="hidden" name="signer_name" id="signer_name_hidden">
                        <input type="hidden" name="color_markings" id="color_markings_hidden">
                        <input type="hidden" name="history_before" id="history_before_hidden">
                        <input type="hidden" name="history_after" id="history_after_hidden">
                        <input type="hidden" name="checkbox_acknowledge" value="1">
                        <button type="submit" id="finalSubmitAgreementBtn"></button>
                    </form>
                
                @else
                    {{-- AGREEMENT SIGNED: Display the signed details --}}
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-4 items-start print:gap-2 border p-4 rounded-lg bg-green-50 border-green-300">
                            <div>
                                <div class="text-sm text-gray-600">Signer</div>
                                <div class="font-semibold">{{ $visit->groomingAgreement->signer_name }}</div>
                                <div class="text-xs text-gray-500">{{ optional($visit->groomingAgreement->signed_at)->format('M d, Y h:i A') }}</div>
                            </div>
                            <div class="col-span-2">
                                <div class="text-sm text-gray-600">Signature:</div>
                                {{-- Use optional URL if the path exists --}}
                                @if($visit->groomingAgreement->signature_path)
                                    <img src="{{ asset('storage/'.$visit->groomingAgreement->signature_path) }}" alt="Signature" class="h-24 border rounded bg-white"/>
                                @else
                                    <div class="h-24 border rounded bg-white flex items-center justify-center text-gray-500 text-xs">Signature not available</div>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-700 font-semibold mb-1">Before Grooming Notes</div>
                                <div class="min-h-24 border rounded p-3 whitespace-pre-wrap bg-gray-50">{{ $visit->groomingAgreement->history_before ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-700 font-semibold mb-1">After Grooming Notes</div>
                                <div class="min-h-24 border rounded p-3 whitespace-pre-wrap bg-gray-50">{{ $visit->groomingAgreement->history_after ?? '—' }}</div>
                            </div>
                        </div>

                       <button type="button"
                               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                               onclick="openPrintWindow('{{ route('medical.visits.grooming.agreement.print', ['visit' => $visit->visit_id]) }}')">
                           <i class="fas fa-print mr-2"></i> Print Agreement
                       </button>
    </div>
                    </div>
                @endif
            </div>

            @php
                $__groom = [
                    'grooming_type' => [],
                    'addon_services' => [],
                    'instructions' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'assigned_groomer' => auth()->user()->user_name ?? null,
                ];

                if (isset($serviceData) && $serviceData) {
                    // For single package select, just get the first package or the entire string
                    $presetService = trim($serviceData->service_package ?? '');
                    
                    $presetAddons = collect(explode(',', $serviceData->add_ons ?? ''))
                        ->map(fn ($name) => trim($name))
                        ->filter()
                        ->values()
                        ->all();

                    $__groom = [
                        'grooming_type' => [$presetService], // Wrap in array for compatibility
                        'addon_services' => $presetAddons,
                        'instructions' => $serviceData->remarks ?? null,
                        'start_time' => $serviceData->start_time ? \Carbon\Carbon::parse($serviceData->start_time)->format('Y-m-d\TH:i') : null,
                        'end_time' => $serviceData->end_time ? \Carbon\Carbon::parse($serviceData->end_time)->format('Y-m-d\TH:i') : null,
                        'assigned_groomer' => $serviceData->groomer_name ?? (auth()->user()->user_name ?? null),
                    ];
                }
            @endphp
            
            {{--------------------------------------------------}}
            {{-- START: Service Record (Visible only if signed) --}}
            {{--------------------------------------------------}}
            @if($visit->groomingAgreement)
                <form action="{{ route('medical.visits.grooming.save', $visit->visit_id) }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
                    <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
                    <input type="hidden" name="weight" value="{{ $visit->weight }}">
                    <input type="hidden" name="redirect_to" value="perform">

                    {{-- 🌟 FIX: Define $currentWeight and display strings once for all HTML/JS references --}}
                    @php
                        $currentWeight = $visit->weight ?? 0;
                        $weightNumeric = (float) $currentWeight; 
                        
                        // Formatted strings for HTML/JS display:
                        $weightNote = $currentWeight !== 0 ? number_format($weightNumeric, 2).' kg' : 'N/A (Weight missing)';
                        $weightDisplayString = $currentWeight !== 0 ? number_format($weightNumeric, 2) . ' kg' : 'N/A (Weight missing)';
                    @endphp
                    
                    {{-- Grooming Details Card --}}
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-file-medical-alt mr-2 text-blue-600"></i> Service Record</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            {{-- Assigned Groomer Input --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Assigned Groomer</label>
                                <input type="text" name="assigned_groomer" value="{{ old('assigned_groomer', $__groom['assigned_groomer'] ?? (auth()->user()->user_name ?? '')) }}" 
                                    class="w-full border border-gray-300 p-3 rounded-lg" required/>
                            </div>
                            
                            {{-- Grooming Package Select (weight & age aware) --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">
                                    Grooming Package <span class="text-red-500 font-bold">*</span> <span class="text-red-600 text-xs font-normal">(required)</span>
                                </label>
                                @php
                                    $preselectedService = old('grooming_type', $__groom['grooming_type'][0] ?? '');
                                    if (is_array($preselectedService)) {
                                        $preselectedService = $preselectedService[0] ?? '';
                                    }
                                @endphp
                                <select name="grooming_type" id="grooming_type_select" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                    <option value="">-- Select a grooming package --</option>
                                    {{-- Store service data in data attributes for JS to read --}}
                                    @forelse($availableServices as $service)
                                        <option 
                                            value="{{ $service->serv_name }}" 
                                            data-min-weight="{{ $service->min_weight ?? 0 }}"
                                            data-max-weight="{{ $service->max_weight ?? 9999 }}"
                                            data-min-age="{{ $service->min_age_months ?? '' }}"
                                            data-max-age="{{ $service->max_age_months ?? '' }}"
                                            {{ $preselectedService == $service->serv_name ? 'selected' : '' }}
                                            class="grooming-option"
                                        >
                                            {{ $service->serv_name }} (₱{{ number_format($service->serv_price, 2) }})
                                        </option>
                                    @empty
                                        <option disabled>No grooming packages match the pet's age/weight.</option>
                                    @endforelse
                                </select>
                                
                                {{-- Display Pet's Weight --}}
                                <p class="text-xs text-gray-500 mt-1">
                                    Pet's Current Weight: 
                                    <strong id="pet_current_weight" data-weight="{{ $weightNumeric }}">
                                        {{ $weightNote }}
                                    </strong>
                                </p>
                                <p id="weight_warning" class="text-xs text-red-500 mt-1 font-semibold hidden">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Some packages are disabled because of weight or age restrictions.
                                </p>
                            </div>
                        </div>

                        {{-- Row 2: Add-on Services, Start Time, End Time (3 columns) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                            {{-- Add-ons Multi-select Dropdown --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">
                                    Add-on Services <span class="text-xs text-gray-500 font-normal">(optional)</span>
                                </label>
                                @php
                                    $preselectedAddons = old('addon_services', $__groom['addon_services'] ?? []);
                                    if (!is_array($preselectedAddons)) {
                                        $preselectedAddons = array_filter(array_map('trim', explode(',', (string) $preselectedAddons)));
                                    }
                                @endphp
                                
                                {{-- Custom Multi-Select Dropdown --}}
                                <div class="relative" id="addon_dropdown_container">
                                    {{-- Dropdown Toggle Button --}}
                                    <button type="button" id="addon_dropdown_toggle" 
                                        class="w-full border border-gray-300 p-3 rounded-lg bg-white text-left flex items-center justify-between hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">
                                        <span id="addon_dropdown_text" class="text-gray-500">Select add-on services...</span>
                                        <i class="fas fa-chevron-down text-gray-400 transition-transform" id="addon_dropdown_icon"></i>
                                    </button>
                                    
                                    {{-- Dropdown Menu --}}
                                    <div id="addon_dropdown_menu" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        @forelse($availableAddons as $addon)
                                            @php
                                                $isAddonSelected = in_array($addon->serv_name, $preselectedAddons);
                                            @endphp
                                            <label class="flex items-center px-4 py-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 addon-checkbox-label"
                                                data-min-weight="{{ $addon->min_weight ?? 0 }}"
                                                data-max-weight="{{ $addon->max_weight ?? 9999 }}"
                                                data-min-age="{{ $addon->min_age_months ?? '' }}"
                                                data-max-age="{{ $addon->max_age_months ?? '' }}">
                                                <input type="checkbox" name="addon_services[]" value="{{ $addon->serv_name }}" 
                                                    class="addon-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                    {{ $isAddonSelected ? 'checked' : '' }}>
                                                <span class="ml-3 text-sm text-gray-700">{{ $addon->serv_name }}</span>
                                                <span class="ml-auto text-sm font-medium text-green-600">₱{{ number_format($addon->serv_price, 2) }}</span>
                                            </label>
                                        @empty
                                            <div class="px-4 py-3 text-gray-500 text-sm">No add-on services available.</div>
                                        @endforelse
                                    </div>
                                </div>
                                
                                {{-- Selected Items Display --}}
                                <div id="addon_selected_tags" class="flex flex-wrap gap-2 mt-2"></div>
                                
                                <p class="text-xs text-gray-500 mt-1">
                                    Click to select multiple add-on services.
                                </p>
                            </div>

                            {{-- Start Time --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
                                <input type="datetime-local" name="start_time" class="w-full border border-gray-300 p-3 rounded-lg" 
                                    value="{{ old('start_time', $__groom['start_time'] ?? '') }}"/>
                            </div>
                            
                            {{-- End Time --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
                                <input type="datetime-local" name="end_time" class="w-full border border-gray-300 p-3 rounded-lg" 
                                    value="{{ old('end_time', $__groom['end_time'] ?? '') }}"/>
                            </div>
                        </div>
                        
                        {{-- Row 3: Notes/Observations (full width) --}}
                        <div class="mt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Notes / Observations</label>
                            <textarea name="instructions" rows="3" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="Matting status, behavior during groom, any adverse findings.">{{ old('instructions', $__groom['instructions'] ?? '') }}</textarea>
                        </div>
                            
                        
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex justify-between items-center pt-4">
                      
                        
                        <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                            <i class="fas fa-save mr-1"></i> Save Grooming Record
                        </button>
                    </div>
                </form>
            @else
                {{-- Display message if not signed, to block the form above --}}
                <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded-xl text-yellow-800 shadow-lg">
                    <div class="flex items-center">
                        <i class="fas fa-lock text-2xl mr-3"></i>
                        <h4 class="font-bold">Service Record Blocked</h4>
                    </div>
                    <p class="mt-2 text-sm">The grooming record section is locked until the **Grooming Agreement Consent** is signed by the owner or representative above.</p>
                </div>
            @endif
            {{--------------------------------------------------}}
            {{-- END: Service Record (Visible only if signed) --}}
            {{--------------------------------------------------}}
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
</script>

{{-- Replace the entire Grooming Agreement Modal section with this fixed version --}}

{{-- Grooming Agreement Modal (FIXED DOCUMENT FORMAT) --}}
<div id="groomingAgreementModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black bg-opacity-70 p-4">
    <div class="bg-gray-100 rounded-xl shadow-2xl w-full max-w-5xl max-h-[95vh] overflow-y-auto relative">
        {{-- Modal Header (Sticky) --}}
        <div class="sticky top-0 bg-white z-10 px-6 py-4 border-b-2 border-gray-300 flex justify-between items-center rounded-t-xl">
            <h3 class="text-xl font-bold text-red-600 flex items-center gap-2">
                <i class="fas fa-file-signature"></i>
                Grooming Agreement & Liability Waiver
            </h3>
            <button type="button" onclick="closeAgreementModal()" class="text-gray-500 hover:text-gray-800 text-3xl leading-none px-2">&times;</button>
        </div>
        
        {{-- Document Container with Proper Paper Look --}}
        <div class="p-6">
            <div class="bg-white shadow-lg border-2 border-gray-800 mx-auto" style="max-width: 850px;">
                {{-- Clinic Header --}}
                <div class="p-4" style="background-color: #f88e28;">
                    <img src="{{ asset('images/header.jpg') }}" alt="Pets2GO Veterinary Clinic Header" class="w-full h-auto object-contain" style="max-height: 100px;">
                </div>

                {{-- Document Content --}}
                <div class="p-8">
                    {{-- Document Title --}}
                    <div class="text-center border-b-2 border-gray-800 pb-4 mb-6">
                        <h1 class="text-2xl font-extrabold tracking-wide uppercase">Grooming Agreement Consent</h1>
                    </div>

                    <form id="agreementForm" class="space-y-4">
                        @csrf
                        {{-- Date and Time --}}
                        <div class="grid grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Date and Time</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ now()->format('F j, Y g:i A') }}</div>
                            </div>
                        </div>

                        {{-- Owner Information --}}
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Owner's Name</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->owner->own_name ?? '' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Address</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->owner->own_location ?? '' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Phone Number</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->owner->own_contactnum ?? '' }}</div>
                            </div>
                        </div>

                        {{-- Pet Information --}}
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Name of Pet</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->pet_name ?? '' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Species</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->pet_species ?? '' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Gender</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->pet_gender ?? '' }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Pet Age</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->pet_age ?? '' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Breed</label>
                                <div class="border-b border-gray-400 pb-1 font-mono text-sm">{{ $visit->pet->pet_breed ?? '' }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Color Markings</label>
                                <input type="text" name="color_markings" id="modal_color_markings" 
                                            class="w-full border-b border-gray-400 focus:border-blue-600 outline-none pb-1 font-mono text-sm bg-transparent" 
                                            placeholder="e.g. Black with white paws">
                            </div>
                        </div>

                        {{-- History Section --}}
                        <div class="mb-6">
                            <div class="text-center font-extrabold text-sm uppercase mb-3 text-gray-800">History</div>
                            <table class="w-full border-2 border-gray-800">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-800 p-3 text-left text-xs font-bold uppercase">Before Grooming (Notes)</th>
                                        <th class="border border-gray-800 p-3 text-left text-xs font-bold uppercase">After Grooming (Notes)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-gray-800 p-2">
                                            <textarea name="history_before" id="modal_history_before" rows="6" 
                                                        class="w-full outline-none resize-vertical text-sm p-2" 
                                                        placeholder="E.g., Severe matting, aggressive behavior, pre-existing warts/lumps"></textarea>
                                        </td>
                                        <td class="border border-gray-800 p-2">
                                            <textarea name="history_after" id="modal_history_after" rows="6" 
                                                        class="w-full outline-none resize-vertical text-sm p-2" 
                                                        placeholder="E.g., No reaction, shaved cleanly"></textarea>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Terms and Conditions --}}
                        <div class="space-y-3 text-justify text-sm leading-relaxed mb-6">
                            <p><strong>1.</strong> I certify that I am the owner of (or person responsible for) the pet described above.</p>
                            
                            <p><strong>2.</strong> I understand that grooming entails hair trimming, bathing, nail clipping and ear cleaning. No physical examination or check up is included in the process of grooming. All pets shall be presented healthy and regular handling procedures will be instituted, unless I inform the staff beforehand of any pre-existing medical conditions.</p>
                            
                            <p><strong>3.</strong> Grooming can be stressful to animals; however, the grooming staff will use reasonable precautions against injury, escape or death of my pet. I am aware that sometimes skin reactions may arise due to my pet's skin sensitivity. Therefore, the establishment shall not be held liable for any problem that may transpire from either stress or reaction brought about by grooming of my pet, provided reasonable care and precautions were strictly followed. I understand that any problem that may develop with my pet will be treated as deemed best by the staff veterinarian and I assume full responsibility for the treatment expense involved.</p>
                            
                            <p><strong>4.</strong> The groomers make no claim of expertise in grooming any particular breed. Groomers will make reasonable effort to conform to my grooming requests; however, no guarantees are made that the exact grooming cut can be followed.</p>
                            
                            <p><strong>5.</strong> Grooming may take a few hours to complete and pets will be served on a FIRST COME FIRST SERVED basis.</p>
                            
                            <p class="text-center font-extrabold mt-6">After carefully reading the above, I have signed an agreement.</p>
                        </div>

                        {{-- Signature Section --}}
                        <div class="border-t-2 border-gray-800 pt-6 grid grid-cols-2 gap-8">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-700 mb-2">Signature</label>
                                <div class="border-2 border-gray-800 bg-white rounded">
                                    <canvas id="modal-signature-pad" class="w-full" style="height: 160px;"></canvas>
                                </div>
                                <div class="text-center text-xs font-bold mt-2 uppercase">Signature of Owner/Representative</div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Signer Name</label>
                                    <input type="text" id="modal_signer_name" 
                                                value="{{ $visit->pet->owner->own_name ?? '' }}" 
                                                class="w-full border-b-2 border-gray-400 focus:border-blue-600 outline-none pb-1 font-mono text-sm bg-transparent" 
                                                placeholder="Owner / Representative" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase text-gray-700 mb-1">Date</label>
                                    <div class="border-b-2 border-gray-400 pb-1 font-mono text-sm">{{ now()->format('F j, Y') }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Acknowledgment Checkbox --}}
                        <div class="flex items-start gap-3 mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                            <input type="checkbox" name="checkbox_acknowledge" id="modal_checkbox_acknowledge" 
                                        value="1" required class="mt-1 w-5 h-5">
                            <label for="modal_checkbox_acknowledge" class="text-sm font-medium text-gray-800">
                                I have carefully read and understood all the terms and conditions stated above, and I agree to them.
                            </label>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex justify-between items-center mt-6 max-w-[850px] mx-auto">
                <button type="button" onclick="closeAgreementModal()" 
                        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold transition">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button type="button" id="modal-sig-clear" 
                        class="px-6 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-semibold transition">
                    <i class="fas fa-eraser mr-1"></i> Clear Signature
                </button>
                <button type="button" id="submitAgreementBtn" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition shadow-lg">
                    <i class="fas fa-signature mr-1"></i> Sign Agreement
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Print Styles for Agreement */
@media print {
    /* --- 1. SET LEGAL PAPER SIZE --- */
    /* This rule is crucial to ensure legal sizing in the print dialog */
    @page {
        size: legal; /* 8.5 in x 14 in */
        margin: 0.5in; /* Set margins for professional look */
    }

    /* --- 2. HIDE EVERYTHING ON THE PAGE EXCEPT THE MODAL --- */
    body > *:not(#groomingAgreementModal) {
        display: none !important;
    }
    
    /* --- 3. SHOW AND FORMAT THE MODAL CONTENT FOR PRINTING --- */
    #groomingAgreementModal {
        /* Override fixed positioning for printing context */
        position: static !important;
        display: block !important; 
        background: none !important; /* Hide background overlay */
        padding: 0 !important;
        margin: 0 auto; /* Center content on the legal page */
        overflow: visible !important; /* Allow content to flow across pages */
        box-shadow: none !important;
        max-height: none !important;
    }
    
    /* Ensure the inner document wrapper is visible and positioned */
    #groomingAgreementModal > div { /* The w-full max-w-5xl div */
        position: static !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        box-shadow: none !important;
        overflow: visible !important;
        background: none !important;
    }
    
    /* Hide modal controls (header/footer) inside the printed output */
    #groomingAgreementModal .sticky {
        display: none; 
    }
    #groomingAgreementModal > div:first-child > div:last-child > div:last-child {
        display: none; /* Hide action buttons */
    }

    /* Fix image in signed section to show signature instead of canvas */
    #modal-signature-pad {
        display: none !important; /* Hide canvas placeholder */
    }
}
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    window.openPrintWindow = function(url) {
        const printWindow = window.open(url, '_blank', 'noopener');
        if (!printWindow) {
            alert('Please allow pop-ups to print the grooming agreement. Opening in the current tab instead.');
            window.location.href = url;
        }
    };

    // Grooming agreement modal + validation logic
    document.addEventListener('DOMContentLoaded', function() {
        // ... (All your existing DOMContentLoaded logic for validation, signing, etc.)
        const agreementModal = document.getElementById('groomingAgreementModal');
        const submitBtn = document.getElementById('submitAgreementBtn');
        const canvas = document.getElementById('modal-signature-pad');
        const petWeightElement = document.getElementById('pet_current_weight');
        const groomingSelect = document.getElementById('grooming_type_select');
        const weightWarning = document.getElementById('weight_warning');

        // --- Custom Add-on Dropdown Logic ---
        const addonDropdownToggle = document.getElementById('addon_dropdown_toggle');
        const addonDropdownMenu = document.getElementById('addon_dropdown_menu');
        const addonDropdownText = document.getElementById('addon_dropdown_text');
        const addonDropdownIcon = document.getElementById('addon_dropdown_icon');
        const addonSelectedTags = document.getElementById('addon_selected_tags');
        const addonCheckboxes = document.querySelectorAll('.addon-checkbox');

        if (addonDropdownToggle && addonDropdownMenu) {
            // Toggle dropdown
            addonDropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const isOpen = !addonDropdownMenu.classList.contains('hidden');
                if (isOpen) {
                    addonDropdownMenu.classList.add('hidden');
                    addonDropdownIcon.classList.remove('rotate-180');
                } else {
                    addonDropdownMenu.classList.remove('hidden');
                    addonDropdownIcon.classList.add('rotate-180');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!addonDropdownToggle.contains(e.target) && !addonDropdownMenu.contains(e.target)) {
                    addonDropdownMenu.classList.add('hidden');
                    addonDropdownIcon.classList.remove('rotate-180');
                }
            });

            // Update display when checkboxes change
            function updateAddonDisplay() {
                const selectedAddons = [];
                addonCheckboxes.forEach(function(checkbox) {
                    if (checkbox.checked) {
                        selectedAddons.push(checkbox.value);
                    }
                });

                // Update toggle button text
                if (selectedAddons.length === 0) {
                    addonDropdownText.textContent = 'Select add-on services...';
                    addonDropdownText.classList.add('text-gray-500');
                    addonDropdownText.classList.remove('text-gray-800');
                } else {
                    addonDropdownText.textContent = selectedAddons.length + ' add-on(s) selected';
                    addonDropdownText.classList.remove('text-gray-500');
                    addonDropdownText.classList.add('text-gray-800');
                }

                // Update tags display
                addonSelectedTags.innerHTML = '';
                selectedAddons.forEach(function(addon) {
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full';
                    tag.innerHTML = addon + ' <button type="button" class="hover:text-red-600 font-bold" data-addon="' + addon + '">&times;</button>';
                    addonSelectedTags.appendChild(tag);
                });

                // Add click handlers for tag remove buttons
                addonSelectedTags.querySelectorAll('button').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const addonToRemove = this.getAttribute('data-addon');
                        addonCheckboxes.forEach(function(checkbox) {
                            if (checkbox.value === addonToRemove) {
                                checkbox.checked = false;
                            }
                        });
                        updateAddonDisplay();
                    });
                });
            }

            // Add change listeners to checkboxes
            addonCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateAddonDisplay);
            });

            // Initial display update
            updateAddonDisplay();
        }

        let signaturePad = null;

        // --- Signature Pad Setup ---
        if (canvas) {
            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                if (signaturePad) {
                    signaturePad.clear();
                    const ctx = canvas.getContext('2d');
                    ctx.scale(ratio, ratio);
                }
            }
            
            window.initializeSignaturePad = function() {
                if (!signaturePad) {
                    signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255,255,255,1)',
                        throttle: 10
                    });
                } else {
                    signaturePad.clear();
                }
                resizeCanvas();
            };

            document.getElementById('modal-sig-clear').addEventListener('click', function() {
                if (signaturePad) signaturePad.clear();
            });
            
            const observer = new MutationObserver((mutationsList, observer) => {
                if (!agreementModal.classList.contains('hidden')) {
                    window.requestAnimationFrame(() => {
                        setTimeout(() => {
                            initializeSignaturePad();
                        }, 50);
                    });
                }
            });
            observer.observe(agreementModal, { attributes: true, attributeFilter: ['class'] });
            window.addEventListener('resize', resizeCanvas);
            initializeSignaturePad();
        }

        // --- Modal Control Functions ---
        window.openAgreementModal = function() {
            agreementModal.classList.remove('hidden');
            agreementModal.classList.add('flex');
            const notesTextarea = document.querySelector('textarea[name="instructions"]');
            document.getElementById('modal_history_before').value = notesTextarea ? notesTextarea.value : '';
            document.getElementById('modal_signer_name').value = '{{ $visit->pet->owner->own_name ?? 'Guest Owner' }}';
        };

        window.closeAgreementModal = function() {
            agreementModal.classList.add('hidden');
            agreementModal.classList.remove('flex');
            if (signaturePad) signaturePad.clear();
        };

        // --- Agreement Submission Logic ---
        const finalSubmitForm = document.getElementById('agreement-form-data'); 
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (signaturePad && signaturePad.isEmpty()) {
                    alert('Please provide a signature before submitting the agreement.');
                    return;
                }
                if (!document.getElementById('modal_checkbox_acknowledge').checked) {
                    alert('You must acknowledge and agree to the terms.');
                    return;
                }
                finalSubmitForm.querySelector('#signature_data').value = signaturePad.toDataURL('image/png');
                finalSubmitForm.querySelector('#signer_name_hidden').value = document.getElementById('modal_signer_name').value;
                finalSubmitForm.querySelector('#history_before_hidden').value = document.getElementById('modal_history_before').value;
                finalSubmitForm.querySelector('#history_after_hidden').value = document.getElementById('modal_history_after').value;
                finalSubmitForm.querySelector('#color_markings_hidden').value = document.getElementById('modal_color_markings').value;
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                finalSubmitForm.submit(); 
            });
        }

        // --- Weight Validation Logic ---
        if (petWeightElement && groomingSelect) {
            const petWeight = parseFloat(petWeightElement.getAttribute('data-weight'));
            const weightDisplay = petWeightElement.innerText.trim(); 

            function validateServiceOptions(selectElement) {
                let disabledCount = 0;
                let enabledCount = 0;
                const weightUnavailable = isNaN(petWeight) || petWeight <= 0;

                if (!selectElement) return { disabledCount, enabledCount };

                Array.from(selectElement.options).forEach(option => {
                    if (option.value === "" || option.disabled) return; 
                    const minWeight = parseFloat(option.getAttribute('data-min-weight'));
                    const maxWeight = parseFloat(option.getAttribute('data-max-weight'));

                    let isOutsideRange = false;
                    if (!weightUnavailable) {
                        isOutsideRange = (petWeight < minWeight || petWeight > maxWeight);
                    }

                    option.disabled = isOutsideRange;

                    if (isOutsideRange) {
                        option.style.backgroundColor = '#fca5a5'; 
                        option.style.color = '#7f1d1d';
                        disabledCount++;
                    } else {
                        option.style.backgroundColor = '';
                        option.style.color = '';
                        enabledCount++;
                    }
                });
                
                Array.from(selectElement.selectedOptions).forEach(option => {
                    if (option.disabled) {
                        option.selected = false;
                    }
                });

                return { disabledCount, enabledCount };
            }

            function validateAddonCheckboxes() {
                let disabledCount = 0;
                let enabledCount = 0;
                const weightUnavailable = isNaN(petWeight) || petWeight <= 0;
                const addonLabels = document.querySelectorAll('.addon-checkbox-label');

                addonLabels.forEach(label => {
                    const checkbox = label.querySelector('.addon-checkbox');
                    if (!checkbox) return;

                    const minWeight = parseFloat(label.getAttribute('data-min-weight'));
                    const maxWeight = parseFloat(label.getAttribute('data-max-weight'));

                    let isOutsideRange = false;
                    if (!weightUnavailable) {
                        isOutsideRange = (petWeight < minWeight || petWeight > maxWeight);
                    }

                    checkbox.disabled = isOutsideRange;

                    if (isOutsideRange) {
                        label.classList.add('opacity-50', 'cursor-not-allowed');
                        label.classList.remove('hover:bg-blue-50', 'cursor-pointer');
                        if (checkbox.checked) checkbox.checked = false;
                        disabledCount++;
                    } else {
                        label.classList.remove('opacity-50', 'cursor-not-allowed');
                        label.classList.add('hover:bg-blue-50', 'cursor-pointer');
                        enabledCount++;
                    }
                });

                return { disabledCount, enabledCount };
            }

            function validateAllGroomingServices() {
                const weightUnavailable = isNaN(petWeight) || petWeight <= 0;
                let totalDisabled = 0;
                let totalEnabled = 0;

                // Validate grooming packages
                if (groomingSelect) {
                    const packagesResult = validateServiceOptions(groomingSelect);
                    totalDisabled += packagesResult.disabledCount;
                    totalEnabled += packagesResult.enabledCount;
                }

                // Validate add-ons (checkbox version)
                const addonsResult = validateAddonCheckboxes();
                totalDisabled += addonsResult.disabledCount;
                totalEnabled += addonsResult.enabledCount;

                // Update addon display after validation
                if (typeof updateAddonDisplay === 'function') {
                    updateAddonDisplay();
                }

                if (weightUnavailable) {
                    weightWarning.innerText = 'Pet weight is not recorded. Please record the pet\'s weight for accurate service selection.';
                    weightWarning.classList.remove('hidden');
                } else if (totalEnabled === 0) {
                    weightWarning.innerText = `No services available for the current weight (${weightDisplay}).`;
                    weightWarning.classList.remove('hidden');
                } else if (totalDisabled > 0) {
                    weightWarning.innerText = `Some services are disabled as the pet's weight (${weightDisplay}) falls outside their allowed range.`;
                    weightWarning.classList.remove('hidden');
                } else {
                    weightWarning.classList.add('hidden');
                }
            }
            
            validateAllGroomingServices();
        }
    });
</script>
@endpush
@endsection