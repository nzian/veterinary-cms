@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-yellow-50 to-amber-50 p-4 sm:p-6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800">Deworming Service Workspace</h2>
            <a href="{{ route('medical.index', ['tab' => 'deworming']) }}" 
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
                $__deworm = [];
                if (isset($serviceData) && $serviceData) {
                    $__deworm = [
                        'dewormer_name' => $serviceData->dewormer_name ?? null,
                        'service_id' => $serviceData->service_id ?? null,
                        'dosage' => $serviceData->dosage ?? null,
                        'manufacturer' => $serviceData->manufacturer ?? null,
                        'batch_no' => $serviceData->batch_no ?? null,
                        'next_due_date' => $serviceData->next_due_date,
                        'administered_by' => $serviceData->administered_by ?? (auth()->user()->user_name ?? null),
                        'remarks' => $serviceData->remarks ?? null,
                    ];
                }
                // Determine the currently selected service for pre-selection
                $selectedServiceId = $visit->services->where('serv_type', 'deworming')->first()->serv_id ?? old('service_id');
                
                // Get pet species for filtering deworming services
                $petSpecies = strtolower($visit->pet->pet_species ?? '');
            @endphp
            <form action="{{ route('medical.visits.deworming.save', $visit->visit_id) }}" method="POST" class="space-y-6" id="deworming_form">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
                <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
                <input type="hidden" name="service_type" id="service_type_hidden" value="">
                <input type="hidden" name="service_id" id="service_id_hidden" value="{{ old('service_id', $__deworm['service_id'] ?? '') }}">

                {{-- Deworming Record Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-flask-pill mr-2 text-green-600"></i> Administration Details</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        {{-- Deworming Service Type Selector --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Deworming Service <span class="text-red-500">*</span></label>
                            <select name="service_type" id="deworming_service_selector" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                <option value="">Select Deworming Service</option>
                                @forelse($availableServices ?? [] as $service)
                                    @php
                                        $productsData = $service->products->map(function($p) {
                                            return [
                                                'prod_id' => $p->prod_id,
                                                'prod_name' => $p->prod_name,
                                                'prod_stocks' => $p->current_stock,
                                                'prod_expiry' => $p->expired_date ? \Carbon\Carbon::parse($p->expired_date)->format('d-m-Y') : null,
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
                                            {{ (isset($__deworm['service_id']) && $__deworm['service_id'] == $service->serv_id) ? 'selected' : '' }}>
                                        {{ $service->serv_name }} - ₱{{ number_format($service->serv_price, 2) }}
                                    </option>
                                    @endif
                                @empty
                                    <option value="" disabled>No deworming services available</option>
                                @endforelse
                            </select>
                        </div>

                        {{-- Dewormer Product Selection --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Dewormer/Product Administered <span class="text-red-500">*</span></label>
                            <select name="dewormer_name" id="dewormer_name_select" class="w-full border border-gray-300 p-3 rounded-lg" required disabled>
                                <option value="">First select a deworming service</option>
                            </select>
                            <small class="text-xs text-gray-500 mt-1" id="dewormer_helper_text">
                                Select a deworming service first to see available dewormers.
                            </small>
                        </div>
                        
                        {{-- Dosage field --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Dosage</label>
                            <input type="text" name="dosage" id="dosage_input" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="Auto-filled from product (can be changed)" 
                                value="{{ old('dosage', $__deworm['dosage'] ?? '') }}" />
                            <small class="text-xs text-gray-500" id="dosage_info">Auto-filled from product dosage (can be changed)</small>
                        </div>
                        
                        {{-- Manufacturer field (auto-filled) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Manufacturer</label>
                            <input type="text" name="manufacturer" id="manufacturer_input" class="w-full border border-gray-300 p-3 rounded-lg bg-gray-50" 
                                value="{{ old('manufacturer', $__deworm['manufacturer'] ?? '') }}" placeholder="Auto-filled from product" readonly/>
                            <small class="text-xs text-gray-500">Auto-filled from selected dewormer product</small>
                        </div>
                        
                        {{-- Batch No. field (auto-filled with FEFO) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Batch No.</label>
                            <input type="text" name="batch_no" id="batch_no_input" class="w-full border border-gray-300 p-3 rounded-lg bg-gray-50" 
                                value="{{ old('batch_no', $__deworm['batch_no'] ?? '') }}" placeholder="Auto-filled (FEFO)" readonly/>
                            <small class="text-xs text-gray-500" id="batch_info">Auto-filled from earliest expiring batch</small>
                        </div>
                        
                        {{-- Administered By field remains the same --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Administered By</label>
                            <input type="text" name="administered_by" class="w-full border border-gray-300 p-3 rounded-lg" 
                                value="{{ old('administered_by', $__deworm['administered_by'] ?? (auth()->user()->user_name ?? '')) }}" placeholder="Staff Name"/>
                        </div>
                        
                        {{-- Next Schedule field remains the same --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Next Schedule (Reminder)</label>
                            <input type="date" name="next_due_date" class="w-full border border-gray-300 p-3 rounded-lg" 
                                value="{{ old('next_due_date', optional(\Carbon\Carbon::parse($__deworm['next_due_date'] ?? optional(\Carbon\Carbon::parse($visit->visit_date ?? now()))->addDays(14)))->format('Y-m-d')) }}" />
                        </div>
                        
                        {{-- Remarks field remains the same --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks / Notes</label>
                            <textarea name="remarks" rows="3" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="e.g. Pet refused tablet, gave liquid instead.">{{ old('remarks', $__deworm['remarks'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end items-center pt-4">
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Deworming Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODALS (Replaced placeholder includes with actual content to resolve parsing issue) --}}

{{-- Pet Profile Modal (Photo + Pet & Owner Info Only) --}}
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
            <img src="{{ asset('storage/'.$visit->pet->pet_photo) }}" alt="{{ $visit->pet->pet_name }}" class="w-full h-80 object-cover"/>
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

{{-- Medical History Modal (History Only) --}}
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

{{-- Initial Assessment Modal --}}
<div id="initialAssessmentModal" class="fixed inset-0 bg-black/60 z-50 hidden">
    <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closeInitialAssessmentModal()}">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-y-auto p-6" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6 border-b pb-3 sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-notes-medical mr-2 text-indigo-600"></i> Initial Assessment
                </h3>
                <button onclick="closeInitialAssessmentModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form id="initialAssessmentForm" action="{{ route('medical.initial_assessments.store') }}" method="POST" onsubmit="return handleInitialAssessmentSubmit(event)" class="space-y-4 border border-indigo-200 p-4 rounded-lg bg-indigo-50">
                @csrf
                <input type="hidden" name="pet_id" id="initial_pet_id" value="{{ $visit->pet_id ?? '' }}">
                <input type="hidden" name="visit_id" id="initial_visit_id" value="{{ $visit->visit_id ?? '' }}">
                <input type="hidden" name="active_tab" value="visits">

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-300 bg-white">
                        <tbody>
                            {{-- Full table content from the original assessment form --}}
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
    </div>
</div>

{{-- Prescription Modal --}}
<div id="prescriptionModal" class="fixed inset-0 bg-black/60 z-50 hidden">
    <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closePrescriptionModal()}">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-y-auto p-6" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6 border-b pb-3 sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-prescription mr-2 text-green-600"></i> Add New Prescription
                </h3>
                <button onclick="closePrescriptionModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form id="prescriptionForm" onsubmit="return handlePrescriptionSubmit(event)" class="space-y-4 border border-green-200 p-4 rounded-lg bg-green-50">
                @csrf
                <input type="hidden" name="pet_id" id="prescription_pet_id">
                <input type="hidden" name="medications_json" id="medications_json">
                <input type="hidden" name="active_tab" value="visits">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Prescription Date</label>
                    <input type="date" name="prescription_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                </div>

                <div id="medicationContainer" class="space-y-3">
                    {{-- Medication fields added by JS --}}
                </div>
                <button type="button" onclick="addMedicationField()" class="bg-indigo-500 text-white px-3 py-1 rounded text-sm hover:bg-indigo-600">
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
    </div>
</div>

{{-- Appointment Modal --}}
<div id="appointmentModal" class="fixed inset-0 bg-black/60 z-50 hidden">
    <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closeAppointmentModal()}">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[95vh] overflow-y-auto p-6" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6 border-b pb-3 sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-calendar-plus mr-2 text-blue-600"></i> Set Follow-up Appointment
                </h3>
                <button onclick="closeAppointmentModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form id="appointmentForm" action="{{ route('medical.appointments.store') }}" method="POST" class="space-y-4 border border-blue-200 p-4 rounded-lg bg-blue-50">
                @csrf
                <input type="hidden" name="pet_id" id="appoint_pet_id">
                <input type="hidden" name="appoint_status" value="Scheduled">
                <input type="hidden" name="active_tab" value="visits">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Follow-up Type</label>
                        <select name="appoint_type" id="appoint_type" class="w-full border border-gray-300 p-2 rounded-lg" required>
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
    </div>
</div>

{{-- Referral Modal --}}
<div id="referralModal" class="fixed inset-0 bg-black/60 z-50 hidden">
    <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closeReferralModal()}">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[95vh] overflow-y-auto p-6" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6 border-b pb-3 sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-share mr-2 text-red-600"></i> Create New Referral
                </h3>
                <button onclick="closeReferralModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form id="referralForm" action="{{ route('medical.referrals.store') }}" method="POST" class="space-y-4 border border-red-200 p-4 rounded-lg bg-red-50" onsubmit="return validateReferralFormDeworming()">
                @csrf
                <input type="hidden" name="visit_id" id="referral_visit_id" value="{{ $visit->visit_id ?? '' }}">
                <input type="hidden" name="pet_id" id="referral_pet_id" value="{{ $visit->pet_id ?? '' }}">
                <input type="hidden" name="active_tab" value="visits">
                <input type="hidden" name="ref_type" id="ref_type_deworming" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Referral Date</label>
                        <input type="date" name="ref_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Refer To <span class="text-red-500">*</span></label>
                        <select name="ref_to_select" id="ref_to_select_deworming" class="w-full border border-gray-300 p-2 rounded-lg" required onchange="toggleReferralFieldsDeworming()">
                            <option value="">Select Branch or External</option>
                            @foreach($allBranches as $branch)
                                <option value="branch_{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                            @endforeach
                            <option value="external">External Clinic</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="ref_to" id="ref_to_branch_deworming">

                <div id="externalFieldDeworming" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">External Clinic Name <span class="text-red-500">*</span></label>
                    <input type="text" name="external_clinic_name" id="external_clinic_name_deworming" class="w-full border border-gray-300 p-2 rounded-lg" placeholder="Enter clinic name">
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

<script>
    // Global Data
    let availablePrescriptionProducts = @json($allProducts ?? []);
    let activityMedicationCounter = 0;
    
    // Deworming Service and Product Selection Logic
    document.addEventListener('DOMContentLoaded', function() {
        const serviceSelector = document.getElementById('deworming_service_selector');
        const dewormerSelect = document.getElementById('dewormer_name_select');
        const dewormerHelperText = document.getElementById('dewormer_helper_text');
        const serviceTypeHidden = document.getElementById('service_type_hidden');
        
        // Listen for service selection changes
        if (serviceSelector) {
            serviceSelector.addEventListener('change', function() {
                updateDewormerOptions();
            });
        }

        // Listen for dewormer selection changes to show warnings and fetch product details
        if (dewormerSelect) {
            dewormerSelect.addEventListener('change', function() {
                checkStockWarning();
                fetchProductDetails();
            });
        }

        function updateDewormerOptions() {
            const selectedOption = serviceSelector.options[serviceSelector.selectedIndex];
            
            // Update hidden field with selected service type
            if (serviceTypeHidden) {
                serviceTypeHidden.value = selectedOption.value || '';
            }
            
            // Update hidden field with selected service_id
            const serviceIdHidden = document.getElementById('service_id_hidden');
            if (serviceIdHidden) {
                serviceIdHidden.value = selectedOption.getAttribute('data-service-id') || '';
            }
            
            // Clear existing options
            dewormerSelect.innerHTML = '<option value="">Select Dewormer Product</option>';
            
            // Clear dosage, manufacturer and batch fields when service changes
            document.getElementById('dosage_input').value = '';
            document.getElementById('dosage_info').textContent = 'Auto-filled from product dosage (can be changed)';
            document.getElementById('manufacturer_input').value = '';
            document.getElementById('batch_no_input').value = '';
            document.getElementById('batch_info').textContent = 'Auto-filled from earliest expiring batch';
            
            if (!selectedOption.value) {
                dewormerSelect.disabled = true;
                dewormerHelperText.textContent = 'Select a deworming service first to see available dewormers.';
                return;
            }

            // Get products from the selected service with error handling
            let products = [];
            try {
                const productsData = selectedOption.dataset.products;
                if (productsData) {
                    products = JSON.parse(productsData);
                }
            } catch (e) {
                console.error('Error parsing products JSON:', e);
                console.log('Raw data:', selectedOption.dataset.products);
            }
            
            if (products.length === 0) {
                dewormerSelect.innerHTML = '<option value="">No consumable products for this service</option>';
                dewormerSelect.disabled = true;
                dewormerHelperText.innerHTML = '<span class="text-red-600">⚠️ This service has no consumable products configured.</span>';
                return;
            }

            // Enable the select and populate with products
            dewormerSelect.disabled = false;
            dewormerHelperText.innerHTML = 'Available dewormers for the selected service. <strong>Only stock is displayed.</strong>';
            
            products.forEach(function(product) {
                const option = document.createElement('option');
                option.value = product.prod_name;
                option.dataset.stock = product.current_stock;
                option.dataset.prodId = product.prod_id;
                option.dataset.quantityUsed = product.quantity_used;
                
                // Build option text - ONLY SHOW STOCK, NO PRICE
                let optionText = `${product.prod_name} (Stock: ${product.current_stock})`;
                
                // Check for low stock or expiring
                if (product.current_stock <= 5) {
                    optionText += ' ⚠️ Low Stock';
                }
                
                if (product.expired_date) {
                    const expiryDate = new Date(product.expired_date);
                    const daysUntilExpiry = Math.ceil((expiryDate - new Date()) / (1000 * 60 * 60 * 24));
                    if (daysUntilExpiry <= 30 && daysUntilExpiry > 0) {
                        optionText += ' ⚠️ Expiring Soon';
                    }
                }
                
                option.textContent = optionText;
                
                // Disable if out of stock
                if (product.current_stock <= 0) {
                    option.disabled = true;
                    option.textContent += ' - OUT OF STOCK';
                }
                
                dewormerSelect.appendChild(option);
            });
            
            // Clear any existing warnings
            const existingWarning = dewormerSelect.parentElement.querySelector('.stock-warning');
            if (existingWarning) existingWarning.remove();
        }

        function checkStockWarning() {
            const selectedOption = dewormerSelect.options[dewormerSelect.selectedIndex];
            const stock = selectedOption.dataset.stock;
            
            // Remove any existing warnings
            const existingWarning = dewormerSelect.parentElement.querySelector('.stock-warning');
            if (existingWarning) existingWarning.remove();
            
            // Show warning for low stock
            if (stock && parseInt(stock) > 0 && parseInt(stock) <= 5) {
                const warning = document.createElement('div');
                warning.className = 'stock-warning mt-2 p-2 bg-yellow-50 border border-yellow-300 rounded text-sm text-yellow-800';
                warning.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> Low stock warning: Only ${stock} units remaining`;
                dewormerSelect.parentElement.appendChild(warning);
            } else if (stock && parseInt(stock) <= 0) {
                const warning = document.createElement('div');
                warning.className = 'stock-warning mt-2 p-2 bg-red-50 border border-red-300 rounded text-sm text-red-800';
                warning.innerHTML = `<i class="fas fa-times-circle mr-1"></i> Out of stock - cannot proceed`;
                dewormerSelect.parentElement.appendChild(warning);
            }
        }
        
        function fetchProductDetails() {
            const selectedOption = dewormerSelect.options[dewormerSelect.selectedIndex];
            const prodId = selectedOption ? selectedOption.dataset.prodId : null;
            const quantityUsed = selectedOption ? selectedOption.dataset.quantityUsed : null;
            
            const dosageInput = document.getElementById('dosage_input');
            const dosageInfo = document.getElementById('dosage_info');
            const manufacturerInput = document.getElementById('manufacturer_input');
            const batchNoInput = document.getElementById('batch_no_input');
            const batchInfo = document.getElementById('batch_info');
            
            // Clear fields if no product selected
            if (!prodId || !selectedOption.value) {
                dosageInput.value = '';
                dosageInfo.textContent = 'Auto-filled from product dosage (can be changed)';
                manufacturerInput.value = '';
                batchNoInput.value = '';
                batchInfo.textContent = 'Auto-filled from earliest expiring batch';
                return;
            }
            
            // Set dosage from quantity_used (the configured dosage for this product)
            if (quantityUsed) {
                dosageInput.value = quantityUsed + ' ml';
                dosageInfo.innerHTML = `<span class="text-green-600">Dosage: ${quantityUsed} ml (from product configuration)</span>`;
            } else {
                dosageInput.value = '';
                dosageInfo.textContent = 'Enter dosage (can be changed)';
            }
            
            // Show loading state
            manufacturerInput.value = 'Loading...';
            batchNoInput.value = 'Loading...';
            
            // Fetch product details from API
            fetch(`/products/${prodId}/details-for-service`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.product) {
                        // Set manufacturer
                        manufacturerInput.value = data.product.manufacturer_name || 'N/A';
                        
                        // Set batch number (FEFO - First Expire First Out)
                        if (data.product.batch_no) {
                            batchNoInput.value = data.product.batch_no;
                            
                            // Show expiry info
                            if (data.product.batch_expire_date) {
                                const expiryDate = new Date(data.product.batch_expire_date);
                                const formattedDate = expiryDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                batchInfo.innerHTML = `<span class="text-green-600">Batch expires: ${formattedDate} (Qty: ${data.product.batch_quantity})</span>`;
                            } else {
                                batchInfo.innerHTML = `<span class="text-gray-600">Batch quantity: ${data.product.batch_quantity}</span>`;
                            }
                        } else {
                            batchNoInput.value = 'No batch available';
                            batchInfo.innerHTML = '<span class="text-red-600">⚠️ No stock batch found for this product</span>';
                        }
                    } else {
                        manufacturerInput.value = 'N/A';
                        batchNoInput.value = 'N/A';
                        batchInfo.textContent = 'Could not fetch batch information';
                    }
                })
                .catch(error => {
                    console.error('Error fetching product details:', error);
                    manufacturerInput.value = 'Error';
                    batchNoInput.value = 'Error';
                    batchInfo.textContent = 'Error fetching batch information';
                });
        }
        
        // Initialize on page load - trigger if there's a pre-selected service
        if (serviceSelector && serviceSelector.value) {
            updateDewormerOptions();
            
            // After populating dewormer dropdown, restore saved dewormer selection
            setTimeout(function() {
                const savedDewormer = "{{ $__deworm['dewormer_name'] ?? '' }}";
                if (savedDewormer && dewormerSelect) {
                    dewormerSelect.value = savedDewormer;
                    checkStockWarning();
                }
            }, 100);
        }
    });
    
    // --- General Modals ---
    function openPetProfileModal() { 
        document.getElementById('petProfileModal').classList.remove('hidden'); 
    }
    function closePetProfileModal() { 
        document.getElementById('petProfileModal').classList.add('hidden'); 
    }
    function openMedicalHistoryModal() { 
        document.getElementById('medicalHistoryModal').classList.remove('hidden'); 
    }
    function closeMedicalHistoryModal() { 
        document.getElementById('medicalHistoryModal').classList.add('hidden'); 
    }
    
    // --- Action Modals ---
    
    function openInitialAssessmentModal(petId, visitId) {
        document.getElementById('initial_pet_id').value = petId;
        document.getElementById('initial_visit_id').value = visitId;
        // Optionally pre-fill radio buttons if data exists in $visit or $serviceData
        document.getElementById('initialAssessmentModal').classList.remove('hidden');
    }
    function closeInitialAssessmentModal() {
        document.getElementById('initialAssessmentModal').classList.add('hidden');
    }

    function openPrescriptionModal(petId) {
        document.getElementById('prescription_pet_id').value = petId;
        // Reset and populate the medication fields
        document.getElementById('prescriptionForm').reset();
        document.getElementById('medicationContainer').innerHTML = '';
        activityMedicationCounter = 0;
        addMedicationField(); // Ensure at least one field is present
        document.getElementById('prescriptionModal').classList.remove('hidden');
    }
    function closePrescriptionModal() {
        document.getElementById('prescriptionModal').classList.add('hidden');
    }

    function openAppointmentModal(petId, defaultType) {
        document.getElementById('appoint_pet_id').value = petId;
        document.getElementById('appoint_type').value = defaultType; // Use the dynamic follow-up type
        document.getElementById('appointmentForm').reset(); // Reset form content
        document.getElementById('appointmentModal').classList.remove('hidden');
    }
    function closeAppointmentModal() {
        document.getElementById('appointmentModal').classList.add('hidden');
    }
    
    function openReferralModal(visitId, petId) {
        document.getElementById('referral_visit_id').value = visitId;
        document.getElementById('referral_pet_id').value = petId;
        document.getElementById('referralForm').reset();
        // Re-set the values after reset
        document.getElementById('referral_visit_id').value = visitId;
        document.getElementById('referral_pet_id').value = petId;
        document.getElementById('referralModal').classList.remove('hidden');
    }
    function closeReferralModal() {
        document.getElementById('referralModal').classList.add('hidden');
    }
    
    function toggleReferralFieldsDeworming() {
        const refToSelect = document.getElementById('ref_to_select_deworming').value;
        const externalField = document.getElementById('externalFieldDeworming');
        const refToBranch = document.getElementById('ref_to_branch_deworming');
        const refType = document.getElementById('ref_type_deworming');
        const externalClinicName = document.getElementById('external_clinic_name_deworming');

        if (refToSelect.startsWith('branch_')) {
            // Interbranch referral
            const branchId = refToSelect.replace('branch_', '');
            externalField.style.display = 'none';
            externalClinicName.removeAttribute('required');
            externalClinicName.value = '';
            refToBranch.value = branchId;
            refType.value = 'interbranch';
        } else if (refToSelect === 'external') {
            // External clinic referral
            externalField.style.display = 'block';
            externalClinicName.setAttribute('required', 'required');
            refToBranch.value = '';
            refType.value = 'external';
        } else {
            // Nothing selected
            externalField.style.display = 'none';
            externalClinicName.removeAttribute('required');
            refToBranch.value = '';
            refType.value = '';
        }
    }

    function validateReferralFormDeworming() {
        const refType = document.getElementById('ref_type_deworming').value;
        if (!refType) {
            alert('Please select a referral destination');
            return false;
        }
        if (refType === 'external') {
            const clinicName = document.getElementById('external_clinic_name_deworming').value;
            if (!clinicName || clinicName.trim() === '') {
                alert('Please enter the external clinic name');
                return false;
            }
        }
        return true;
    }

    // --- Prescription Sub-Functions (Adapted from previous fix) ---

    function addMedicationField() {
        const container = document.getElementById('medicationContainer');
        const fieldId = ++activityMedicationCounter;
        
        const fieldHtml = `
            <div class="medication-field border border-gray-300 p-3 rounded-lg bg-white" data-field-id="${fieldId}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-medium text-gray-700">Medication ${fieldId}</h4>
                    ${fieldId > 1 ? `<button type="button" onclick="removeMedicationField(${fieldId})" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-trash"></i> Remove</button>` : ''}
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
        setupProductSearch(fieldId);
    }

    function removeMedicationField(fieldId) {
        const field = document.querySelector(`#medicationContainer .medication-field[data-field-id="${fieldId}"]`);
        if (field) {
            field.remove();
        }
    }

    function setupProductSearch(fieldId) {
        const searchInput = document.querySelector(`.medication-field[data-field-id="${fieldId}"] .product-search`);
        const suggestionsDiv = document.querySelector(`.medication-field[data-field-id="${fieldId}"] .product-suggestions`);
        const productIdInput = document.querySelector(`.medication-field[data-field-id="${fieldId}"] .selected-product-id`);

        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            clearTimeout(searchTimeout);
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
                        item.innerHTML = `<div>${product.prod_name}</div><div class="text-xs text-gray-500">Stock: ${product.current_stock} - ₱${parseFloat(product.prod_price || 0).toFixed(2)}</div>`;
                        
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

    function handlePrescriptionSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const medications = [];
        let isValid = true;
        
        document.querySelectorAll('#medicationContainer .medication-field').forEach(field => {
            const fieldId = field.dataset.fieldId;
            const searchInput = field.querySelector('.product-search');
            const productIdInput = field.querySelector('.selected-product-id');
            const instructionsTextarea = field.querySelector('.medication-instructions');
            
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
            showToast('error', 'Please ensure all medication fields are complete or empty.');
            return;
        }

        if (medications.length === 0) {
            showToast('error', 'Please add at least one medication.');
            return;
        }
        
        document.getElementById('medications_json').value = JSON.stringify(medications);
        form.action = "{{ route('medical.prescriptions.store') }}"; 
        form.method = 'POST';
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        
        form.submit();
    }

    function handleInitialAssessmentSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        
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
                showToast('success', data.message || 'Initial assessment saved successfully!');
                setTimeout(() => {
                    closeInitialAssessmentModal(); // Use specific closing function
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

    // Toast notification function
    function showToast(type, message) {
        const container = document.getElementById('toastContainer');
        if(!container) return; // Prevent error if container is missing
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type === 'success' ? 'toast-success' : 'toast-error'}`;
        
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icon} mr-2"></i>
                <span>${message}</span>
            </div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 500);
        }, 3000);
    }
</script>

{{-- Added the toast container div --}}
<div id="toastContainer" class="toast-container"></div>

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