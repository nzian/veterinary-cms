@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-orange-50 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800">Emergency</h1>
                    <p class="text-gray-600 mt-1">Triage, stabilization, treatment, and observation</p>
                </div>
                <a href="{{ route('medical.index', ['active_tab' => 'visits']) }}" 
                   class="px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                    ← Back
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-red-500">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pet</label>
                    <div class="bg-red-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->pet_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Owner</label>
                    <div class="bg-red-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->owner->own_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Visit Date</label>
                    <div class="bg-red-50 p-3 rounded-lg font-semibold text-gray-800">{{ optional(\Carbon\Carbon::parse($visit->visit_date))->format('F j, Y') }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Assigned Staff</label>
                    <div class="bg-red-50 p-3 rounded-lg font-semibold text-gray-800">{{ auth()->user()->user_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-700">Status Timeline</h3>
                <form method="POST" action="{{ route('medical.visits.emergency.save', $visit->visit_id) }}" class="flex items-center gap-2 text-xs">
                    @csrf
                    <select name="workflow_status" class="border px-2 py-1 rounded">
                        @foreach(['Triage','Stabilization','Treatment','Observation','Completed'] as $s)
                            <option value="{{ $s }}" {{ (($visit->workflow_status ?? 'Triage') === $s) ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-2 py-1 bg-blue-600 text-white rounded">Update</button>
                </form>
            </div>
            <div class="mt-3 flex items-center gap-2 text-xs">
                @foreach(['Triage','Stabilization','Treatment','Observation','Completed'] as $i => $label)
                    <span class="px-2 py-1 rounded {{ (($visit->workflow_status ?? 'Triage') === $label) ? 'bg-green-600 text-white' : 'bg-gray-100' }}">{{ $label }}</span>
                    @if($label !== 'Completed')<span>→</span>@endif
                @endforeach
            </div>
        </div>

        <form action="{{ route('medical.visits.emergency.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Arrival Time</label>
                        <input type="datetime-local" name="arrival_time" class="w-full border p-2 rounded" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Emergency Type</label>
                        <select name="emergency_type" class="w-full border p-2 rounded">
                            <option value="">Select type</option>
                            <option value="Trauma">Trauma</option>
                            <option value="Poisoning">Poisoning</option>
                            <option value="Respiratory distress">Respiratory distress</option>
                            <option value="Seizure">Seizure</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Vitals on Arrival</label>
                        <input type="text" name="vitals" class="w-full border p-2 rounded" placeholder="Temp, HR, RR, BP" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Immediate Intervention</label>
                        <input type="text" name="immediate_intervention" class="w-full border p-2 rounded" placeholder="IV, oxygen, CPR, etc." />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Triage Notes</label>
                        <textarea name="triage_notes" rows="3" class="w-full border p-2 rounded" placeholder="Time of arrival, initial status (ABCs), triage level..."></textarea>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Procedures</label>
                        <textarea name="procedures" rows="3" class="w-full border p-2 rounded" placeholder="IV placement, CPR, intubation, fluid therapy..."></textarea>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Immediate Medications</label>
                        <textarea name="immediate_meds" rows="2" class="w-full border p-2 rounded" placeholder="Epinephrine, pain relief, sedatives..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Assigned Vet / Staff</label>
                        <input type="text" name="assigned_staff" class="w-full border p-2 rounded" value="{{ auth()->user()->user_name ?? '' }}" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Priority Billing</button>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save</button>
            </div>

            <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
            <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
        </form>
    </div>
</div>
@endsection
