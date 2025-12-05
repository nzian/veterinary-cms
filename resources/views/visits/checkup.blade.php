@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-4 sm:p-6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

                {{-- Error Display --}}
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Consultation Type Selector Card --}}
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-indigo-500">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-stethoscope text-indigo-600 mr-2"></i> Consultation Type
                    </h3>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Select Consultation Service <span class="text-red-500">*</span>
                        </label>
                        <select name="service_id" id="service_id" class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:outline-none" required>
                            <option value="">-- Select a consultation type --</option>
                            @forelse($availableServices ?? [] as $service)
                                <option value="{{ $service->serv_id }}" 
                                    data-price="{{ $service->serv_price }}"
                                    {{ old('service_id', $visit->services->where('serv_type', 'LIKE', '%check%')->first()?->serv_id) == $service->serv_id ? 'selected' : '' }}>
                                    {{ $service->serv_name }} (₱{{ number_format($service->serv_price, 2) }})
                                </option>
                            @empty
                                <option disabled>No consultation services available for this branch</option>
                            @endforelse
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            This service will be billed when the consultation is saved.
                        </p>
                    </div>
                </div>

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
    <input type="number" step="0.01" name="weight" 
            value="{{ old('weight', $visit->weight ?? '') }}"
            class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
            placeholder="Enter weight" required>
</div>

<div>
    <label class="block text-sm font-semibold text-gray-700 mb-1">Temperature (°C) <span class="text-red-500">*</span></label>
    <input type="number" step="0.1" name="temperature" 
            value="{{ old('temperature', $visit->temperature ?? '') }}"
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
                  
                    
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-md transition">
                        <i class="fas fa-save mr-1"></i> Save Consultation Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 1. Initial Assessment Modal --}}
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
                            {{-- Full Initial Assessment Table Content --}}
                            <tr class="border-b"><td class="p-3 align-top w-1/3"><label class="font-medium">Is your pet sick?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="is_sick" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="is_sick" value="No"> <span>No</span></label></div></td><td class="p-3 align-top w-1/3"><label class="font-medium">Has your pet been treated recently?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="been_treated" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="been_treated" value="No"> <span>No</span></label></div></td><td class="p-3 align-top w-1/3"><label class="font-medium">Does your pet get table food/human food?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="table_food" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="table_food" value="No"> <span>No</span></label></div></td></tr>
                            <tr class="border-b"><td class="p-3 align-top"><label class="font-medium">How many times per day do you feed?</label><div class="flex flex-wrap gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Once"> <span>Once</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Twice"> <span>Twice</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Thrice"> <span>Thrice</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Is your pet on heartworm preventative?</label><div class="flex gap-4 mt-2 flex-wrap"><label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="No"> <span>No</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="No Idea"> <span>No Idea</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Any injury or accident in the past 30 days?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="injury_accident" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="injury_accident" value="No"> <span>No</span></label></div></td></tr>
                            <tr class="border-b"><td class="p-3 align-top"><label class="font-medium">Allergic To Any Medications/Vaccines?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="allergies" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="allergies" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Had any surgery for the past 30 days?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="surgery_past_30" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="surgery_past_30" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Currently on any medications/vitamins/OTC?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="current_meds" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="current_meds" value="No"> <span>No</span></label></div></td></tr>
                            <tr class="border-b"><td class="p-3 align-top"><label class="font-medium">Appetite Normal?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="appetite_normal" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="appetite_normal" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Diarrhoea</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="diarrhoea" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="diarrhoea" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Vomiting</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="vomiting" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="vomiting" value="No"> <span>No</span></label></div></td></tr>
                            <tr class="border-b"><td class="p-3 align-top"><label class="font-medium">Drinking more or less water than usual?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="drinking_unusual" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="drinking_unusual" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Weakness?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="weakness" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="weakness" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Gagging?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="gagging" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="gagging" value="No"> <span>No</span></label></div></td></tr>
                            <tr class="border-b"><td class="p-3 align-top"><label class="font-medium">Coughing?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="coughing" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="coughing" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Sneezing?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="sneezing" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="sneezing" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Scratching?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="scratching" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="scratching" value="No"> <span>No</span></label></div></td></tr>
                            <tr class="border-b"><td class="p-3 align-top"><label class="font-medium">Shaking Head?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="shaking_head" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="shaking_head" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Urinating more or less than usual?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="urinating_unusual" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="urinating_unusual" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Limping? Which Leg?</label><div class="flex flex-wrap gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="None"> <span>None</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Front Left"> <span>Front Left</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Front Right"> <span>Front Right</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Back Left"> <span>Back Left</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Back Right"> <span>Back Right</span></label></div></td></tr>
                            <tr class="border-b"><td class="p-3 align-top"><label class="font-medium">Scooting?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="scooting" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="scooting" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">History of seizures?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="seizures" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="seizures" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Unusually Bad Breath?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="bad_breath" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="bad_breath" value="No"> <span>No</span></label></div></td></tr>
                            <tr><td class="p-3 align-top"><label class="font-medium">Unusual Discharge?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="discharge" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="discharge" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"><label class="font-medium">Did the pet eat this morning?</label><div class="flex gap-4 mt-2"><label class="inline-flex items-center gap-2"><input type="radio" name="ate_this_morning" value="Yes"> <span>Yes</span></label><label class="inline-flex items-center gap-2"><input type="radio" name="ate_this_morning" value="No"> <span>No</span></label></div></td><td class="p-3 align-top"></td></tr>
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

{{-- 2. Prescription Modal --}}
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
                <input type="hidden" name="pvisit_id" id="prescription_visit_id" value="{{ $visit->visit_id ?? '' }}">
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

{{-- 3. Appointment Modal --}}
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
                            <option value="General Follow-up">General Follow-up</option>
                            <option value="Vaccination Follow-up">Vaccination Follow-up</option>
                            <option value="Deworming Follow-up">Deworming Follow-up</option>
                            <option value="Post-Surgical Recheck">Post-Surgical Recheck</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                        <input type="date" name="appoint_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
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

{{-- 4. Referral Modal --}}
<div id="referralModal" class="fixed inset-0 bg-black/60 z-50 hidden">
    <div class="w-full h-full flex items-center justify-center p-4" onclick="if(event.target===this){closeReferralModal()}">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[95vh] overflow-y-auto p-6" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6 border-b pb-3 sticky top-0 bg-white z-10">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-share mr-2 text-red-600"></i> Create New Referral
                </h3>
                <button onclick="closeReferralModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <form id="referralForm" action="{{ route('medical.referrals.store') }}" method="POST" class="space-y-4 border border-red-200 p-4 rounded-lg bg-red-50" onsubmit="return validateReferralForm()">
                @csrf
                <input type="hidden" name="visit_id" id="referral_visit_id" value="{{ $visit->visit_id ?? '' }}">
                <input type="hidden" name="pet_id" id="referral_pet_id" value="{{ $visit->pet_id ?? '' }}">
                <input type="hidden" name="active_tab" value="visits">
                <input type="hidden" name="ref_type" id="ref_type" value="">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Referral Date</label>
                        <input type="date" name="ref_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Refer To <span class="text-red-500">*</span></label>
                        <select name="ref_to_select" id="ref_to_select" class="w-full border border-gray-300 p-2 rounded-lg" required onchange="toggleReferralFields()">
                            <option value="">Select Branch or External</option>
                            @foreach($allBranches as $branch)
                                <option value="branch_{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                            @endforeach
                            <option value="external">External Clinic</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="ref_to" id="ref_to_branch">

                <div id="externalField" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">External Clinic Name <span class="text-red-500">*</span></label>
                    <input type="text" name="external_clinic_name" id="external_clinic_name" class="w-full border border-gray-300 p-2 rounded-lg" placeholder="Enter clinic name">
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

            <script>
                function toggleReferralFields() {
                    const refToSelect = document.getElementById('ref_to_select').value;
                    const externalField = document.getElementById('externalField');
                    const refToBranch = document.getElementById('ref_to_branch');
                    const refType = document.getElementById('ref_type');
                    const externalClinicName = document.getElementById('external_clinic_name');

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

                function validateReferralForm() {
                    const refType = document.getElementById('ref_type').value;
                    if (!refType) {
                        alert('Please select a referral destination');
                        return false;
                    }
                    if (refType === 'external') {
                        const clinicName = document.getElementById('external_clinic_name').value;
                        if (!clinicName || clinicName.trim() === '') {
                            alert('Please enter the external clinic name');
                            return false;
                        }
                    }
                    return true;
                }
            </script>
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
    // Global Data
    let availablePrescriptionProducts = @json($allProducts);
    let activityMedicationCounter = 0;
    
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
        document.getElementById('initialAssessmentModal').classList.remove('hidden');
    }
    function closeInitialAssessmentModal() {
        document.getElementById('initialAssessmentModal').classList.add('hidden');
    }

    function openPrescriptionModal(petId) {
        document.getElementById('prescription_pet_id').value = petId;
        document.getElementById('prescriptionForm').reset();
        document.getElementById('medicationContainer').innerHTML = '';
        activityMedicationCounter = 0;
        addMedicationField(); 
        document.getElementById('prescriptionModal').classList.remove('hidden');
    }
    function closePrescriptionModal() {
        document.getElementById('prescriptionModal').classList.add('hidden');
    }

    function openAppointmentModal(petId, defaultType) {
        document.getElementById('appoint_pet_id').value = petId;
        document.getElementById('appointmentForm').reset(); 
        document.getElementById('appoint_type').value = defaultType; 
        document.getElementById('appointmentModal').classList.remove('hidden');
    }
    function closeAppointmentModal() {
        document.getElementById('appointmentModal').classList.add('hidden');
    }
    
    function openReferralModal(visitId, petId) {
        document.getElementById('referral_visit_id').value = visitId;
        document.getElementById('referral_pet_id').value = petId;
        document.getElementById('referralForm').reset();
        document.getElementById('referralModal').classList.remove('hidden');
    }
    function closeReferralModal() {
        document.getElementById('referralModal').classList.add('hidden');
    }

    // --- Prescription/Assessment Submission Logic ---
    // NOTE: This logic needs to be present on every page using these modals.

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
                    <input type="text" class="product-search w-full border px-2 py-2 rounded-lg text-sm" placeholder="Search prescription product or enter manually" data-field-id="${fieldId}">
                    <div class="product-suggestions absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-32 overflow-y-auto hidden" data-field-id="${fieldId}"></div>
                    <input type="hidden" class="selected-product-id" data-field-id="${fieldId}">
                    <input type="hidden" class="selected-product-price" data-field-id="${fieldId}" value="0">
                </div>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Quantity</label>
                        <input type="number" class="medication-quantity w-full border px-2 py-2 rounded-lg text-sm" data-field-id="${fieldId}" value="1" min="1" placeholder="Qty">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Unit Price (₱)</label>
                        <input type="number" step="0.01" class="medication-price w-full border px-2 py-2 rounded-lg text-sm" data-field-id="${fieldId}" value="0" min="0" placeholder="Price per unit">
                        <p class="text-xs text-gray-400 mt-1">Auto-filled from inventory or enter manually</p>
                    </div>
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
        const productPriceInput = document.querySelector(`.medication-field[data-field-id="${fieldId}"] .selected-product-price`);
        const priceInput = document.querySelector(`.medication-field[data-field-id="${fieldId}"] .medication-price`);

        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            clearTimeout(searchTimeout);
            productIdInput.value = '';
            productPriceInput.value = '0';

            if (query.length < 2) {
                suggestionsDiv.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(() => {
                const filtered = availablePrescriptionProducts.filter(p => p.prod_name.toLowerCase().includes(query));
                suggestionsDiv.innerHTML = '';
                
                if (filtered.length > 0) {
                    filtered.forEach(product => {
                        const stockClass = product.prod_stocks > 0 ? 'text-green-600' : 'text-red-500';
                        const stockText = product.prod_stocks > 0 ? `In Stock: ${product.prod_stocks}` : 'Out of Stock';
                        const item = document.createElement('div');
                        item.className = 'product-suggestion-item px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm';
                        item.innerHTML = `<div class="font-medium">${product.prod_name}</div><div class="text-xs ${stockClass}">${stockText} - ₱${parseFloat(product.prod_price || 0).toFixed(2)}</div>`;
                        
                        item.onclick = function() {
                            productIdInput.value = product.prod_id;
                            productPriceInput.value = product.prod_price || 0;
                            searchInput.value = product.prod_name;
                            priceInput.value = parseFloat(product.prod_price || 0).toFixed(2);
                            suggestionsDiv.classList.add('hidden');
                            searchInput.focus();
                        };
                        suggestionsDiv.appendChild(item);
                    });
                    suggestionsDiv.classList.remove('hidden');
                } else {
                    suggestionsDiv.innerHTML = '<div class="px-3 py-2 text-gray-500 text-xs"><i class="fas fa-info-circle mr-1"></i>No prescription products found. You can enter manually and set the price.</div>';
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
            const quantityInput = field.querySelector('.medication-quantity');
            const priceInput = field.querySelector('.medication-price');
            
            const productName = searchInput.value.trim();
            const instructions = instructionsTextarea.value.trim();
            const quantity = parseInt(quantityInput.value) || 1;
            const unitPrice = parseFloat(priceInput.value) || 0;
            
            if (productName && instructions) {
                medications.push({
                    product_id: productIdInput.value || null,
                    product_name: productName,
                    instructions: instructions,
                    quantity: quantity,
                    unit_price: unitPrice,
                    price: unitPrice * quantity
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
                    closeInitialAssessmentModal(); 
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