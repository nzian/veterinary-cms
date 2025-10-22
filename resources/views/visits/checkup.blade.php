@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-4 sm:p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
            <h2 class="text-3xl font-bold text-gray-800">Consultation Workspace</h2>
            <a href="{{ route('medical.index', ['tab' => 'checkup']) }}" 
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
                // Unified data retrieval logic
                $__checkup = [];
                if (isset($serviceData) && $serviceData) {
                    $__checkup = [
                        'weight' => $serviceData->weight ?? $visit->weight ?? null,
                        'temperature' => $serviceData->temperature ?? $visit->temperature ?? null,
                        'heart_rate' => $serviceData->heart_rate ?? null,
                        'respiration_rate' => $serviceData->respiration_rate ?? null,
                        'physical_findings' => $serviceData->symptoms ?? null,
                        'diagnosis' => $serviceData->findings ?? null,
                        'recommendations' => $serviceData->treatment_plan ?? null,
                        'next_appointment' => $serviceData->next_visit ?? null,
                    ];
                }
            @endphp
            
            <form action="{{ route('medical.visits.consultation.save', $visit->visit_id) }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
                <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Physical Exam Card --}}
                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-file-medical-alt text-green-600 mr-2"></i> Physical Examination / Vitals
                        </h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Weight (kg) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.01" name="weight" value="{{ old('weight', $__checkup['weight'] ?? '') }}"
                                           class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                           placeholder="Enter weight" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Temperature (°C) <span class="text-red-500">*</span></label>
                                    <input type="number" step="0.1" name="temperature" value="{{ old('temperature', $__checkup['temperature'] ?? '') }}"
                                           class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                           placeholder="Enter temperature" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Heart Rate (bpm)</label>
                                    <input type="number" name="heart_rate" value="{{ old('heart_rate', $__checkup['heart_rate'] ?? '') }}"
                                           class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                           placeholder="Enter heart rate">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Respiratory Rate (breaths/min)</label>
                                    <input type="number" name="respiration_rate" value="{{ old('respiration_rate', $__checkup['respiration_rate'] ?? '') }}"
                                           class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                           placeholder="Enter respiratory rate">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Physical Findings / Symptoms</label>
                                <textarea name="physical_findings" 
                                          class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                          rows="5"
                                          placeholder="Observations from physical exam: coat, eyes, lymph nodes, gait, etc.">{{ old('physical_findings', $__checkup['physical_findings'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Diagnosis & Assessment Card --}}
                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-stethoscope text-red-600 mr-2"></i> Diagnosis & Assessment
                        </h3>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Diagnosis / Clinical Impression <span class="text-red-500">*</span></label>
                        <textarea name="diagnosis" 
                                  class="w-full p-4 border-2 border-gray-300 rounded-lg focus:border-red-500 focus:outline-none"
                                  rows="15" 
                                  placeholder="Primary diagnosis, differential diagnoses, rule-outs..."
                                  required>{{ old('diagnosis', $__checkup['diagnosis'] ?? '') }}</textarea>
                    </div>
                </div>

              

                {{-- Action Buttons --}}
                <div class="flex justify-between items-center pt-4">
                    {{-- NEW Centralized Button (Always visible) --}}
                    <div class="flex items-center gap-2">
                        <button type="button"
                                onclick="openExamForm()"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold shadow-md transition flex items-center gap-2">
                            <i class="fas fa-notes-medical"></i> Examination Form
                        </button>
                        <button type="button"
                                onclick="openChartsModal()"
                                class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-semibold shadow-md transition flex items-center gap-2">
                            <i class="fas fa-tooth"></i> Charts
                        </button>
                        
                    <button type="button" 
                            onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Consultation')"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fas fa-tasks"></i> Service Actions
                    </button>
                    </div>
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Consultation Record
                    </button>
                </div>
            </form>
        </div>

        
  </div>
</div>


<!-- Examination Form Modal (moves #exam-section into modal body on open) -->
<div id="examFormModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="backdropCloseExam(event)">
    <div class="bg-white rounded-xl shadow-2xl w-[1100px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800">Comprehensive Examination Form</h3>
        <button type="button" onclick="closeExamForm()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div id="examModalBody" class="p-4"></div>
    </div>
  </div>
  <script>
    function openExamForm(){
      const modal = document.getElementById('examFormModal');
      const body = document.getElementById('examModalBody');
      const section = document.getElementById('exam-section');
      if (body && section) {
        body.innerHTML = '';
        body.appendChild(section);
      }
      modal.classList.remove('hidden');
    }
    function closeExamForm(){
      const modal = document.getElementById('examFormModal');
      const placeholder = document.getElementById('examSectionPlaceholder');
      const section = document.getElementById('exam-section');
      if (placeholder && section) {
        placeholder.insertAdjacentElement('afterend', section);
      }
      modal.classList.add('hidden');
    }
    function backdropCloseExam(e){
      if (e.target && e.target.id === 'examFormModal') closeExamForm();
    }
  </script>
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
              <div class="space-y-3 max-h-[65vh] overflow-y-auto text-sm">
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

<!-- Charts Modal: dental grade + species-specific chart -->
<div id="chartsModal" class="fixed inset-0 bg-black/60 z-50 hidden">
  <div class="w-full h-full flex items-center justify-center p-4" onclick="backdropCloseCharts(event)">
    <div class="bg-white rounded-xl shadow-2xl w-[1000px] max-w-[95vw] max-h-[95vh] overflow-auto" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <h3 class="font-bold text-lg text-gray-800">Dental & Species Charts</h3>
        <button type="button" onclick="closeChartsModal()" class="px-3 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700 rounded-md"><i class="fas fa-times mr-1"></i>Close</button>
      </div>
      <div class="p-4 space-y-6">
        <div>
          <h4 class="font-semibold text-gray-700 mb-2">Dental Grade</h4>
          <img src="{{ asset('images/dentalGrade.png') }}" alt="Dental Grade Chart" class="w-full max-h-[600px] object-contain border rounded" />
        </div>
        @php($species = strtolower($visit->pet->pet_species ?? ''))
        @if(str_contains($species, 'dog'))
          <div>
            <h4 class="font-semibold text-gray-700 mb-2">Canine Chart</h4>
            <img src="{{ asset('images/Canine.png') }}" alt="Canine Chart" class="w-full max-h-[600px] object-contain border rounded" />
          </div>
        @elseif(str_contains($species, 'cat'))
          <div>
            <h4 class="font-semibold text-gray-700 mb-2">Feline Chart</h4>
            <img src="{{ asset('images/Feline.png') }}" alt="Feline Chart" class="w-full max-h-[600px] object-contain border rounded" />
          </div>
        @endif
      </div>
    </div>
  </div>
  <script>
    function openChartsModal(){
      document.getElementById('chartsModal').classList.remove('hidden');
    }
    function closeChartsModal(){
      document.getElementById('chartsModal').classList.add('hidden');
    }
    function backdropCloseCharts(e){
      if (e.target && e.target.id === 'chartsModal') closeChartsModal();
    }
  </script>
</div>

@include('modals.service_activity_modal', [
    'allPets' => $allPets, 
    'allBranches' => $allBranches, 
    'allProducts' => $allProducts,
])
@endsection