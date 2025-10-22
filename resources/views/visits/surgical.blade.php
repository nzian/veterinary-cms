@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-rose-50 to-red-50 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800">Surgical Services</h1>
                    <p class="text-gray-600 mt-1">Pre-op, intra-op, and post-op documentation</p>
                </div>
                <a href="{{ route('medical.index', ['active_tab' => 'visits']) }}" 
                   class="px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                    ← Back
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-rose-500">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pet</label>
                    <div class="bg-rose-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->pet_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Owner</label>
                    <div class="bg-rose-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->owner->own_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Visit Date</label>
                    <div class="bg-rose-50 p-3 rounded-lg font-semibold text-gray-800">{{ optional(\Carbon\Carbon::parse($visit->visit_date))->format('F j, Y') }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Surgeon</label>
                    <div class="bg-rose-50 p-3 rounded-lg font-semibold text-gray-800">{{ auth()->user()->user_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-700">Status Timeline</h3>
                <form method="POST" action="{{ route('medical.visits.surgical.save', $visit->visit_id) }}" class="flex items-center gap-2 text-xs">
                    @csrf
                    <select name="workflow_status" class="border px-2 py-1 rounded">
                        @foreach(['Waiting','Pre-op','Surgery Ongoing','Recovery','Completed'] as $s)
                            <option value="{{ $s }}" {{ (($visit->workflow_status ?? 'Waiting') === $s) ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-2 py-1 bg-blue-600 text-white rounded">Update</button>
                </form>
            </div>
            <div class="mt-3 flex items-center gap-2 text-xs">
                @foreach(['Waiting','Pre-op','Surgery Ongoing','Recovery','Completed'] as $i => $label)
                    <span class="px-2 py-1 rounded {{ (($visit->workflow_status ?? 'Waiting') === $label) ? 'bg-green-600 text-white' : 'bg-gray-100' }}">{{ $label }}</span>
                    @if($label !== 'Completed')<span>→</span>@endif
                @endforeach
            </div>
        </div>

        <form action="{{ route('medical.visits.surgical.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Diagnosis / Reason</label>
                        <input type="text" name="diagnosis_reason" class="w-full border p-2 rounded" placeholder="Indication for surgery"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Pre-op Checklist</label>
                        <textarea name="checklist" rows="3" class="w-full border p-2 rounded" placeholder="Fasting, labs, consent..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Surgery Type</label>
                        <input type="text" name="surgery_type" class="w-full border p-2 rounded" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Surgeon / Assistant</label>
                        <input type="text" name="staff" class="w-full border p-2 rounded" placeholder="Lead + assistant" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Anesthesia Used</label>
                        <input type="text" name="anesthesia" class="w-full border p-2 rounded" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Start Time</label>
                            <input type="datetime-local" name="start_time" class="w-full border p-2 rounded"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">End Time</label>
                            <input type="datetime-local" name="end_time" class="w-full border p-2 rounded"/>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Post-op Notes</label>
                        <textarea name="post_op_notes" rows="3" class="w-full border p-2 rounded" placeholder="Recovery, complications..."></textarea>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-1">Medications Given</label>
                        <textarea name="medications_used" rows="2" class="w-full border p-2 rounded" placeholder="Drugs, dosage, route"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Follow-up Schedule</label>
                        <input type="date" name="follow_up" class="w-full border p-2 rounded"/>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Generate Billing</button>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save</button>
            </div>

            <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
            <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
        </form>
    </div>
</div>
@endsection
