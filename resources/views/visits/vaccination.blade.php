@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-emerald-50 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800">Vaccination</h1>
                    <p class="text-gray-600 mt-1">Record vaccination details and next schedule</p>
                </div>
                <a href="{{ route('medical.index', ['active_tab' => 'visits']) }}" 
                   class="px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                    ← Back
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-green-500">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pet Name</label>
                    <div class="bg-green-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->pet_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Owner Name</label>
                    <div class="bg-green-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->owner->own_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Visit Date</label>
                    <div class="bg-green-50 p-3 rounded-lg font-semibold text-gray-800">{{ optional(\Carbon\Carbon::parse($visit->visit_date))->format('F j, Y') }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Administered By</label>
                    <div class="bg-green-50 p-3 rounded-lg font-semibold text-gray-800">{{ auth()->user()->user_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-700">Status Timeline</h3>
                <form method="POST" action="{{ route('medical.visits.vaccination.save', $visit->visit_id) }}" class="flex items-center gap-2 text-xs">
                    @csrf
                    <select name="workflow_status" class="border px-2 py-1 rounded">
                        @foreach(['Waiting','Consultation','Vaccination Ongoing','Observation','Completed'] as $s)
                            <option value="{{ $s }}" {{ (($visit->workflow_status ?? 'Waiting') === $s) ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-2 py-1 bg-blue-600 text-white rounded">Update</button>
                </form>
            </div>
            <div class="mt-3 flex items-center gap-2 text-xs">
                @foreach(['Waiting','Consultation','Vaccination Ongoing','Observation','Completed'] as $i => $label)
                    <span class="px-2 py-1 rounded {{ (($visit->workflow_status ?? 'Waiting') === $label) ? 'bg-green-600 text-white' : 'bg-gray-100' }}">{{ $label }}</span>
                    @if($label !== 'Completed')<span>→</span>@endif
                @endforeach
            </div>
        </div>

        @php
            $__details = json_decode($visit->details_json ?? '[]', true) ?: [];
            $__vacc = [];
            if (isset($serviceData) && $serviceData) {
                $__vacc = [
                    'vaccine' => $serviceData->vaccine_name ?? null,
                    'manufacturer' => $serviceData->manufacturer ?? null,
                    'batch_no' => $serviceData->batch_no ?? null,
                    'date_administered' => $serviceData->date_administered ?? null,
                    'next_due_date' => $serviceData->next_due_date ?? null,
                    'administered_by' => $serviceData->administered_by ?? (auth()->user()->user_name ?? null),
                    'remarks' => $serviceData->remarks ?? null,
                ];
            }
        @endphp
        <form action="{{ route('medical.visits.vaccination.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Vaccine Name</label>
                        <select name="vaccine" class="w-full border p-2 rounded" required>
                            <option value="">Select Vaccine</option>
                            @php($vaccOptions = ['Anti Rabies','Kennel Cough','Kennel Cough (one dose)','5-in-1 / DHPP'])
                            @foreach($vaccOptions as $opt)
                                <option value="{{ $opt }}" {{ old('vaccine', $__vacc['vaccine'] ?? ($__details['vaccine'] ?? ''))===$opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Dose</label>
                            <input type="text" name="dose" class="w-full border p-2 rounded" placeholder="e.g., 1 mL" value="{{ old('dose', $__details['dose'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Administered By</label>
                            <input type="text" name="administered_by" value="{{ old('administered_by', $__vacc['administered_by'] ?? (auth()->user()->user_name ?? '')) }}" class="w-full border p-2 rounded"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Manufacturer</label>
                            <input type="text" name="manufacturer" class="w-full border p-2 rounded" value="{{ old('manufacturer', $__vacc['manufacturer'] ?? '') }}" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Batch No.</label>
                            <input type="text" name="batch_no" class="w-full border p-2 rounded" value="{{ old('batch_no', $__vacc['batch_no'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Date Administered</label>
                            <input type="date" name="date_administered" class="w-full border p-2 rounded" value="{{ old('date_administered', $__vacc['date_administered'] ?? '') }}" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Next Due Date</label>
                            <input type="date" name="next_due_date" class="w-full border p-2 rounded" value="{{ old('next_due_date', $__vacc['next_due_date'] ?? '') }}" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Remarks / Reactions</label>
                            <input type="text" name="remarks" class="w-full border p-2 rounded" placeholder="Optional" value="{{ old('remarks', $__vacc['remarks'] ?? '') }}" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center gap-3">
                <button type="button" id="open-appoint-modal-vacc" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Set Appointment</button>
                <button type="button" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Generate/Update Billing</button>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save</button>
            </div>

            <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
            <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
        </form>
        
        <!-- Set Appointment Modal (Vaccination) -->
        <div id="appoint-modal-vacc" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
            <div class="bg-white w-full max-w-lg rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Set Appointment</h3>
                    <button type="button" id="close-appoint-modal-vacc" class="text-gray-500 hover:text-gray-700">✕</button>
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
                        <button type="button" id="cancel-appoint-vacc" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
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
    const modal = document.getElementById('appoint-modal-vacc');
    if(!modal) return;
    const openBtn = document.getElementById('open-appoint-modal-vacc');
    const closeBtn = document.getElementById('close-appoint-modal-vacc');
    const cancelBtn = document.getElementById('cancel-appoint-vacc');
    function open(){ modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function close(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
    if(openBtn){ openBtn.addEventListener('click', open); }
    if(closeBtn){ closeBtn.addEventListener('click', close); }
    if(cancelBtn){ cancelBtn.addEventListener('click', close); }
});
</script>
@endpush
