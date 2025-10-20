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
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-gray-100">Waiting</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Consultation</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Vaccination Ongoing</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Observation</span>
                    <span>→</span>
                    <span class="px-2 py-1 rounded bg-gray-100">Completed</span>
                </div>
            </div>
        </div>

        <form action="{{ route('medical.visits.vaccination.save', $visit->visit_id) }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white rounded-lg shadow p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Vaccine Name</label>
                        <select name="vaccine" class="w-full border p-2 rounded" required>
                            <option value="">Select Vaccine</option>
                            <option value="Anti Rabies">Anti Rabies</option>
                            <option value="Kennel Cough">Kennel Cough</option>
                            <option value="Kennel Cough (one dose)">Kennel Cough (one dose)</option>
                            <option value="5-in-1 / DHPP">5-in-1 / DHPP</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Dose</label>
                            <input type="text" name="dose" class="w-full border p-2 rounded" placeholder="e.g., 1 mL"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Administered By</label>
                            <input type="text" name="administered_by" value="{{ auth()->user()->user_name ?? '' }}" class="w-full border p-2 rounded"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Batch No.</label>
                            <input type="text" name="batch_no" class="w-full border p-2 rounded"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Expiry Date</label>
                            <input type="date" name="expiry_date" class="w-full border p-2 rounded"/>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Next Vaccination Schedule</label>
                            <input type="date" name="next_schedule" class="w-full border p-2 rounded"/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Remarks / Reactions</label>
                            <input type="text" name="remarks" class="w-full border p-2 rounded" placeholder="Optional"/>
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
