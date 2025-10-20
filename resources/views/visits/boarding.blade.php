@extends('AdminBoard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-teal-50 to-cyan-50 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800">Boarding</h1>
                    <p class="text-gray-600 mt-1">Manage hotel-style check-in/out and daily logs</p>
                </div>
                <a href="{{ route('medical.index', ['active_tab' => 'visits']) }}" 
                   class="px-4 py-2 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-medium">
                    ← Back
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-teal-500">
            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pet</label>
                    <div class="bg-teal-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->pet_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Owner</label>
                    <div class="bg-teal-50 p-3 rounded-lg font-semibold text-gray-800">{{ $visit->pet->owner->own_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Visit Date</label>
                    <div class="bg-teal-50 p-3 rounded-lg font-semibold text-gray-800">{{ optional(\Carbon\Carbon::parse($visit->visit_date))->format('F j, Y') }}</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Caretaker</label>
                    <div class="bg-teal-50 p-3 rounded-lg font-semibold text-gray-800">{{ auth()->user()->user_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-700">Status Timeline</h3>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-gray-100">Reserved</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Checked In</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">In Boarding</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Ready for Pick-up</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Checked Out</span>
                </div>
            </div>
        </div>

        <form action="{{ route('medical.visits.boarding.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Check-in</label>
                        <input type="datetime-local" name="checkin" class="w-full border p-2 rounded" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Check-out</label>
                        <input type="datetime-local" name="checkout" class="w-full border p-2 rounded" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Cage / Room</label>
                        <input type="text" name="room" class="w-full border p-2 rounded" />
                    </div>
                    <div class="col-span-3">
                        <label class="block text-sm font-medium mb-1">Feeding Instructions</label>
                        <textarea name="care_instructions" rows="3" class="w-full border p-2 rounded" placeholder="Diet, water, meds times..."></textarea>
                    </div>
                    <div class="col-span-3">
                        <label class="block text-sm font-medium mb-1">Monitoring Notes / Daily Logs</label>
                        <textarea name="monitoring_notes" rows="3" class="w-full border p-2 rounded" placeholder="Daily observations..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Billing Basis</label>
                        <select name="billing_basis" class="w-full border p-2 rounded">
                            <option value="day">Per Day</option>
                            <option value="hour">Per Hour</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Daily Rate</label>
                        <input type="number" step="0.01" name="rate" class="w-full border p-2 rounded" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Total Days</label>
                        <input type="number" step="0.1" name="total_days" class="w-full border p-2 rounded" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">Compute & Bill</button>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">Save</button>
            </div>

            <input type="hidden" name="visit_id" value="{{ $visit->visit_id }}">
            <input type="hidden" name="pet_id" value="{{ $visit->pet_id }}">
        </form>
    </div>
</div>
@endsection
