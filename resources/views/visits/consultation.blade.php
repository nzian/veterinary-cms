@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-4 sm:p-6">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        {{-- Left Column: Pet Overview & History --}}
        <div class="lg:col-span-1 space-y-6">
            <div id="petOverviewCard" class="bg-white rounded-xl shadow-lg p-4 border-t-4 border-purple-500">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-paw mr-2 text-purple-600"></i> Pet Overview
                </h3>
                <div class="space-y-2 text-sm">
                    <p><strong>Name:</strong> {{ $visit->pet->pet_name ?? 'N/A' }}</p>
                    <p><strong>Species:</strong> {{ $visit->pet->pet_species ?? 'N/A' }}</p>
                    <p><strong>Breed:</strong> {{ $visit->pet->pet_breed ?? 'N/A' }}</p>
                    <p><strong>Owner:</strong> {{ $visit->pet->owner->own_name ?? 'N/A' }}</p>
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <p class="text-red-600"><strong>Weight (kg):</strong> {{ $visit->weight ? number_format($visit->weight, 2) . ' kg' : 'N/A' }}</p>
                        <p class="text-blue-600"><strong>Temp (°C):</strong> {{ $visit->temperature ? number_format($visit->temperature, 1) . ' °C' : 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-4 border-t-4 border-orange-500">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-history mr-2 text-orange-600"></i> Recent Medical History
                </h3>
                <div class="space-y-3 max-h-64 overflow-y-auto text-xs">
                    @forelse($petMedicalHistory as $record)
                        <div class="border-l-2 pl-2 {{ $record->diagnosis ? 'border-red-400' : 'border-gray-300' }}">
                            <div class="font-medium">{{ \Carbon\Carbon::parse($record->visit_date)->format('M j, Y') }}</div>
                            <div class="text-gray-700 truncate">{{ $record->diagnosis ?? $record->treatment ?? 'Routine Visit' }}</div>
                            @if($record->medication)
                                <div class="text-xs text-blue-600">Meds: {{ Str::limit($record->medication, 20) }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-500 italic">No recent history found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Main Content Column --}}
        <div class="lg:col-span-3 space-y-6">
            
            {{-- Header and Back Button --}}
            <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border">
                <h2 class="text-3xl font-bold text-gray-800">
                    Consultation Workspace
                </h2>
                <a href="{{ route('medical.index', ['tab' => 'checkup']) }}" 
                   class="px-4 py-2 bg-gray-200 border-2 border-gray-300 rounded-lg hover:bg-gray-300 font-medium shadow-sm transition">
                    ← Back 
                </a>
            </div>
            
            {{-- Status Timeline (Fixed PHP Syntax) --}}
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-indigo-500">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <h3 class="text-lg font-semibold text-gray-700">Consultation Status</h3>
                    <form method="POST" action="{{ route('medical.visits.consultation.save', $visit->visit_id) }}" class="flex items-center gap-2 text-xs">
                        @csrf
                        <select name="workflow_status" class="border border-gray-300 px-3 py-1.5 rounded-lg text-sm">
                            @php($statuses = ['Pending','Consultation Ongoing','Observation','Completed'])
                            @foreach($statuses as $s)
                                <option value="{{ $s }}" {{ (($visit->workflow_status ?? 'Pending') === $s) ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Update Status</button>
                    </form>
                </div>
                <div class="mt-4 flex items-center justify-between gap-1 text-xs overflow-x-auto pt-2">
                    @php($current = $visit->workflow_status ?? 'Pending')
                    @foreach($statuses as $label)
                        <span class="px-2 py-1 rounded-full whitespace-nowrap text-[10px] font-semibold 
                            {{ $current === $label ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-600' }}">
                            {{ $label }}
                        </span>
                        @if($label !== 'Completed')<span class="text-gray-400">→</span>@endif
                    @endforeach
                </div>
            </div>

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

                {{-- Treatment & Follow-up Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-band-aid text-purple-600 mr-2"></i> Treatment & Follow-up
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Prescriptions Summary (Long-term)</label>
                            <textarea name="prescriptions" 
                                      class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"
                                      rows="4"
                                      placeholder="Summary of long-term medications, e.g. Amoxicillin 250mg, 1 tab BID x 7 days">{{ old('prescriptions', $__checkup['prescriptions'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Additional Recommendations</label>
                            <textarea name="recommendations" 
                                      class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"
                                      rows="4"
                                      placeholder="Dietary changes, exercise restriction, follow-up testing dates, etc.">{{ old('recommendations', $__checkup['recommendations'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-between items-center pt-4">
                    {{-- NEW Centralized Button (Always visible) --}}
                    <button type="button" 
                            onclick="openActivityModal('{{ $visit->pet_id }}', '{{ $visit->pet->owner->own_id ?? 'N/A' }}', 'Consultation')"
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold shadow-md transition flex items-center gap-2">
                        <i class="fas fa-tasks"></i> Service Actions
                    </button>
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Consultation Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('modals.service_activity_modal', [
    'allPets' => $allPets, 
    'allBranches' => $allBranches, 
    'allProducts' => $allProducts,
])
@endsection