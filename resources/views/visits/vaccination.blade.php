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
            <div class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:shadow-md transition" onclick="openPetProfileModal(); setTimeout(function(){var el=document.getElementById('modalHistoryList'); if(el){ el.scrollIntoView({behavior:'smooth', block:'start'});}}, 60);">
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
            
            @php
                // Use default values for prefill or null if no serviceData
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
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Vaccine Name <span class="text-red-500">*</span></label>
                            <select name="vaccine_name" class="w-full border border-gray-300 p-3 rounded-lg" required>
                                <option value="">Select Vaccine</option>
                                @foreach($availableServices as $service)
                                    <option value="{{ $service->serv_name }}" {{ ($__vacc['vaccine_name'] ?? '') === $service->serv_name ? 'selected' : '' }}>
                                        {{ $service->serv_name }}
                                    </option>
                                @endforeach
                            </select>
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
                            <input type="date" name="next_due_date" class="w-full border border-gray-300 p-3 rounded-lg" value="{{ old('next_due_date', optional(\Carbon\Carbon::parse($__vacc['next_due_date'] ?? null))->format('Y-m-d')) }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Administered By</label>
                            <input type="text" name="administered_by" class="w-full border border-gray-300 p-3 rounded-lg" 
                                value="{{ old('administered_by', $__vacc['administered_by'] ?? (auth()->user()->user_name ?? '')) }}" placeholder="Staff Name"/>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Remarks / Observed Reactions</label>
                            <textarea name="remarks" rows="2" class="w-full border border-gray-300 p-3 rounded-lg" 
                                placeholder="e.g. Minor lethargy after 2 hours, no local swelling.">{{ old('remarks', $__vacc['remarks'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-between items-center pt-4">
                    <button type="button" 
                            onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Vaccination')"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fas fa-tasks"></i> Service Actions
                    </button>
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Vaccination Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Full Pet Profile Modal -->
<div id="petProfileModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target.id==='petProfileModal'){closePetProfileModal()}">
    <div class="bg-white rounded-xl shadow-2xl w-[1200px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800">Full Pet Profile</h3>
        <button type="button" onclick="closePetProfileModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-4 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="md:col-span-1 space-y-3">
            <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
              @if(!empty($visit->pet->photo_path))
                <img src="{{ asset('storage/'.$visit->pet->photo_path) }}" alt="{{ $visit->pet->pet_name }}" class="w-full h-64 object-cover"/>
              @else
                <div class="h-64 w-full flex items-center justify-center text-gray-400">No photo</div>
              @endif
            </div>
            <div class="bg-white rounded-lg border p-3 text-sm">
              <div class="font-semibold text-gray-800 text-base">{{ $visit->pet->pet_name ?? 'Pet' }}</div>
              <div class="text-gray-600">Species: {{ $visit->pet->pet_species ?? '—' }}</div>
              <div class="text-gray-600">Breed: {{ $visit->pet->pet_breed ?? '—' }}</div>
              <div class="text-gray-600">Gender: {{ $visit->pet->pet_gender ?? '—' }}</div>
              <div class="text-gray-600">Age: {{ $visit->pet->pet_age ?? '—' }}</div>
              <div class="text-gray-600">Weight: {{ $visit->weight ? number_format($visit->weight, 2).' kg' : '—' }}</div>
              <div class="text-gray-600">Temp: {{ $visit->temperature ? number_format($visit->temperature, 1).' °C' : '—' }}</div>
            </div>
            <div class="bg-white rounded-lg border p-3 text-sm">
              <div class="font-semibold text-gray-800">Owner</div>
              <div class="text-gray-600">{{ $visit->pet->owner->own_name ?? '—' }}</div>
              <div class="text-gray-600">{{ $visit->pet->owner->own_contactnum ?? '' }}</div>
              <div class="text-gray-600">{{ $visit->pet->owner->own_location ?? '' }}</div>
            </div>
          </div>
          <div class="md:col-span-2">
            <div class="bg-white rounded-lg border p-4">
              <div class="font-semibold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-history text-orange-600"></i> Overall Medical History</div>
              <div id="modalHistoryList" class="space-y-3 max-h-[65vh] overflow-y-auto text-sm">
                @forelse($petMedicalHistory as $record)
                  <div class="border-l-2 pl-3 {{ $record->diagnosis ? 'border-red-400' : 'border-gray-300' }}">
                    <div class="flex items-center justify-between">
                      <div class="font-medium">{{ \Carbon\Carbon::parse($record->visit_date)->format('M j, Y') }}</div>
                      @if(!empty($record->service_type))
                        <span class="text-xs text-gray-500">{{ $record->service_type }}</span>
                      @endif
                    </div>
                    <div class="text-gray-700">{{ $record->diagnosis ?? $record->treatment ?? 'Routine Visit' }}</div>
                    @if($record->medication)
                      <div class="text-xs text-blue-600">Meds: {{ Str::limit($record->medication, 120) }}</div>
                    @endif
                  </div>
                @empty
                  <p class="text-gray-500 italic">No medical history on record.</p>
                @endforelse
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    function openPetProfileModal(){ const m = document.getElementById('petProfileModal'); if(m){ m.classList.remove('hidden'); } }
    function closePetProfileModal(){ const m = document.getElementById('petProfileModal'); if(m){ m.classList.add('hidden'); } }
  </script>
</div>

@include('modals.service_activity_modal', [
    'allPets' => $allPets, 
    'allBranches' => $allBranches, 
    'allProducts' => $allProducts,
])
@endsection