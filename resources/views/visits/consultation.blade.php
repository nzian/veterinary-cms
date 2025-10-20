@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 p-6">
    <div class="max-w-6xl mx-auto">
        
        
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800">🩺 Check-up / Consultation</h1>
                    <p class="text-gray-600 mt-1">Record and manage pet medical visits</p>
                </div>
                <a href="{{ route('medical.index', ['active_tab' => 'visits']) }}" 
                   class="px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                    ← Back
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-blue-500">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pet Name</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->pet_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Owner Name</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->owner->own_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Species</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->species ?? 'Unknown' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Visit Date</label>
                    <div class="bg-blue-50 p-3 rounded-lg font-semibold text-gray-800">
                        {{ optional(\Carbon\Carbon::parse($visit->visit_date))->format('F j, Y') }}
                    </div>
                </div>
            </div>
        </div>
        
        <form action="{{ route('medical.visits.consultation.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            <!-- Status Timeline Section -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-indigo-500 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="text-2xl">⏳</span> Status Timeline
                </h2>
                <div class="flex items-center gap-4">
                    <select name="workflow_status" class="border border-gray-300 rounded px-3 py-2">
                        <option value="arrived" {{ ($visit->workflow_status ?? '') == 'arrived' ? 'selected' : '' }}>Arrived</option>
                        <option value="in-progress" {{ ($visit->workflow_status ?? '') == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ ($visit->workflow_status ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ ($visit->workflow_status ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <span class="text-gray-600">Current Status: <strong>{{ ucfirst($visit->workflow_status ?? 'Arrived') }}</strong></span>
                </div>
            </div>
            <details class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500" open>
                <summary class="text-xl font-bold text-gray-800 mb-4 cursor-pointer list-none flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="text-2xl">📋</span> Medical History (Click to Expand/Collapse)
                    </span>
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <div class="pt-4 border-t mt-4 border-gray-100">
                    @php($previousConsultations = $previousConsultations ?? collect())
                    @if($previousConsultations->count() > 0)
                        <div class="space-y-3">
                            @foreach($previousConsultations as $record)
                                <div class="border-2 border-orange-200 rounded-lg p-4 bg-orange-50">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <span class="inline-block bg-orange-500 text-white px-3 py-1 rounded-full text-xs font-semibold mr-2">
                                                {{ ucfirst($record->visit->visit_type ?? 'Consultation') }}
                                            </span>
                                            <span class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($record->consulted_at)->format('F j, Y') }}</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <label class="font-semibold text-gray-700">Diagnosis:</label>
                                            <p class="text-gray-800">{{ $record->diagnosis ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="font-semibold text-gray-700">Treatment:</label>
                                            <p class="text-gray-800">{{ $record->prescriptions ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <label class="font-semibold text-gray-700">Notes:</label>
                                            <p class="text-gray-800">{{ $record->recommendations ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-4 flex items-center gap-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-yellow-800 font-medium">No previous medical history available</p>
                        </div>
                    @endif
                </div>
            </details>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="text-2xl">🧪</span> Physical Examination
                    </h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Weight (kg)</label>
                                <input type="number" step="0.01" name="weight" value="{{ old('weight', $visit->weight) }}"
                                       class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                       placeholder="Enter weight" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Temperature (°C)</label>
                                <input type="number" step="0.1" name="temperature" value="{{ old('temperature', $visit->temperature) }}"
                                       class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                       placeholder="Enter temperature" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Heart Rate (bpm)</label>
                                <input type="number" name="heart_rate" value="{{ old('heart_rate') }}"
                                       class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                       placeholder="Enter heart rate">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Respiratory Rate (breaths/min)</label>
                                <input type="number" name="respiratory_rate" value="{{ old('respiratory_rate') }}"
                                       class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                       placeholder="Enter respiratory rate">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Physical Findings</label>
                            <textarea name="physical_findings" 
                                      class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-green-500 focus:outline-none"
                                      rows="3"
                                      placeholder="Observations from physical exam...">{{ old('physical_findings') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="text-2xl">🔍</span> Diagnosis & Assessment
                    </h2>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Diagnosis</label>
                    <textarea name="diagnosis" 
                              class="w-full p-4 border-2 border-gray-300 rounded-lg focus:border-red-500 focus:outline-none"
                              rows="12" {{-- Increased rows for better visual balance on the side --}}
                              placeholder="Primary diagnosis, differential diagnoses, clinical impressions..."
                              required>{{ old('diagnosis') }}</textarea>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="text-2xl">💊</span> Treatment & Follow-up
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Prescriptions</label>
                        <textarea name="prescriptions" 
                                  class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"
                                  rows="3"
                                  placeholder="Medication name, dosage, frequency, duration...">{{ old('prescriptions') }}</textarea>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Additional Recommendations</label>
                        <textarea name="recommendations" 
                                  class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-purple-500 focus:outline-none"
                                  rows="3"
                                  placeholder="Diet, exercise, care instructions, follow-up care...">{{ old('recommendations') }}</textarea>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Set Appointment</label>
                        <button type="button" id="open-appoint-modal" class="w-full p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Set Appointment</button>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8">
                {{-- Added a new button for the "Generate/Update Billing" action --}}
                <button type="button" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-semibold shadow-md">
                    Generate/Update Billing
                </button>
                <a href="{{ route('medical.index', ['active_tab' => 'visits']) }}" 
                   class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-semibold">
                    Cancel
                </a>
                <button type="submit" 
                        class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Consultation
                </button>
            </div>

            <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
            <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
        </form>
        
        <!-- Set Appointment Modal -->
        <div id="appoint-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
            <div class="bg-white w-full max-w-lg rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Set Appointment</h3>
                    <button type="button" id="close-appoint-modal" class="text-gray-500 hover:text-gray-700">✕</button>
                </div>
                <form method="POST" action="{{ route('medical.appointments.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
                    <input type="hidden" name="appoint_status" value="scheduled">
                    <input type="hidden" name="appoint_type" value="Follow-up">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Owner Name</label>
                            <input type="text" value="{{ $visit->pet->owner->own_name ?? 'N/A' }}" class="w-full border p-2 rounded bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Owner Contact</label>
                            <input type="text" value="{{ $visit->pet->owner->own_contactnum ?? 'N/A' }}" class="w-full border p-2 rounded bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Pet</label>
                            <input type="text" value="{{ $visit->pet->pet_name ?? 'N/A' }}" class="w-full border p-2 rounded bg-gray-50" readonly>
                        </div>
                        <div></div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                            <input type="date" name="appoint_date" required class="w-full border p-2 rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Time</label>
                            <select name="appoint_time" required class="w-full border p-2 rounded">
                                @php($slots = ['09:00 AM','10:00 AM','11:00 AM','01:00 PM','02:00 PM','03:00 PM','04:00 PM'])
                                @foreach($slots as $slot)
                                    <option value="{{ $slot }}">{{ $slot }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                            <textarea name="appoint_description" rows="3" class="w-full border p-2 rounded" placeholder="Reason / notes for follow-up"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" id="cancel-appoint" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Create Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const modal = document.getElementById('appoint-modal');
    const openBtn = document.getElementById('open-appoint-modal');
    const closeBtn = document.getElementById('close-appoint-modal');
    const cancelBtn = document.getElementById('cancel-appoint');
    function open(){ modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function close(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
    if(openBtn){ openBtn.addEventListener('click', open); }
    if(closeBtn){ closeBtn.addEventListener('click', close); }
    if(cancelBtn){ cancelBtn.addEventListener('click', close); }
});
</script>
@endpush