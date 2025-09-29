@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-[#0f7ea0] font-bold text-xl">Appointments</h2>
            <button onclick="openAddModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                + Add Appointment
            </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 px-4 py-2 mb-4 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
    <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

        {{-- Show Entries Dropdown --}}
        <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
            <form method="GET" action="{{ request()->url() }}" class="flex items-center space-x-2">
                <label for="perPage" class="text-sm text-black">Show</label>
                <select name="perPage" id="perPage" onchange="this.form.submit()" class="border border-gray-400 rounded px-2 py-1 text-sm">
                    @foreach ([10, 20, 50, 100, 'all'] as $limit)
                        <option value="{{ $limit }}" {{ request('perPage') == $limit ? 'selected' : '' }}>
                            {{ $limit === 'all' ? 'All' : $limit }}
                        </option>
                    @endforeach
                </select>
                <span>entries</span>
            </form>
        </div>
        <br>

        <!-- Appointments Table -->
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm border text-center">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-2">#</th>
                        <th class="border px-4 py-2">Date</th>
                        <th class="border px-4 py-2">Type</th>
                        <th class="border px-4 py-2">Time</th>
                        <th class="border px-4 py-2">Pet</th>
                        <th class="border px-4 py-2">Services</th>
                        <th class="border px-4 py-2">Description</th>
                        <th class="border px-4 py-2">Owner</th>
                        <th class="border px-4 py-2">Contact</th>
                        <th class="border px-4 py-2">Status</th>
                        <th class="border px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments as $index => $appointment)
                        <tr>
                            <td class="border px-2 py-2">{{ $appointments->firstItem() + $index }}</td>
                            <td class="border px-4 py-2">
    {{ \Carbon\Carbon::parse($appointment->appoint_date)->format('F j, Y') }}
</td>
                            <td class="border px-4 py-2">{{ $appointment->appoint_type }}</td>
                            <td class="border px-4 py-2">
    {{ \Carbon\Carbon::parse($appointment->appoint_time)->format('h:i A') }}
</td>
                            <td class="border px-4 py-2">{{ $appointment->pet?->pet_name ?? 'N/A' }}</td>
                            <td class="border px-2 py-1 text-left">
                                @if($appointment->services->count())
                                    {{ $appointment->services->pluck('serv_name')->join(', ') }}
                                @else
                                    <em>No Service Assigned</em>
                                @endif
                            </td>
                            <td class="border px-4 py-2">{{ $appointment->appoint_description ?? '-' }}</td>
                            <td class="border px-4 py-2">{{ $appointment->pet?->owner?->own_name ?? 'N/A' }}</td>
                            <td class="border px-4 py-2">{{ $appointment->pet?->owner?->own_contactnum}}</td>
                            <td class="border px-4 py-2">{{ ucfirst($appointment->appoint_status) }}</td>
                            <td class="border px-2 py-1">
                                <div class="flex justify-center items-center gap-1">
                                    <button onclick='openEditModal(@json($appointment))'
                                        class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] flex items-center gap-1 text-xs">
                                        <i class="fas fa-pen"></i> 
                                    </button>
                                    <button onclick='openViewModal(@json($appointment))'
                                        class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 flex items-center gap-1 text-xs">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <form action="{{ route('appointments.destroy', $appointment->appoint_id) }}" method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 flex items-center gap-1 text-xs">
                                            <i class="fas fa-trash"></i> 
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-gray-500 py-4">No appointments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-between items-center mt-4 text-sm font-semibold text-black">
            <div>
                Showing {{ $appointments->firstItem() }} to {{ $appointments->lastItem() }} of
                {{ $appointments->total() }} entries
            </div>
            <div class="inline-flex border border-gray-400 rounded overflow-hidden">
                {{ $appointments->links() }}
            </div>
        </div>
    </div>
</div>
<!-- ==================== Add Appointment Modal ==================== -->
<div id="addModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white p-6 rounded shadow-md w-full max-w-3xl">
        <h2 class="text-lg font-bold text-[#0f7ea0] mb-4">Add Appointment</h2>
        <form id="addForm" action="{{ route('appointments.store') }}" method="POST">
            @csrf

            <!-- Row 1: Pet Owner -->
            <div class="mb-3">
                <label class="block text-sm mb-1">Pet Owner</label>
                <select id="owner_id" class="w-full border rounded px-3 py-2 text-sm" required onchange="populateOwnerDetails(this)">
                    <option disabled selected>Select Pet Owner</option>
                    @foreach(\App\Models\Owner::with('pets')->get() as $owner)
                        <option value="{{ $owner->own_id }}" data-contact="{{ $owner->own_contactnum }}" data-pets='@json($owner->pets->map(fn($p) => ["id"=>$p->pet_id,"name"=>$p->pet_name]))'>
                            {{ $owner->own_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Row 2: Contact Number & Pet -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Contact Number</label>
                    <input type="text" name="appoint_contactNum" id="appoint_contactNum" required class="w-full border rounded px-3 py-2 text-sm" readonly />
                </div>
                <div>
                    <label class="block text-sm mb-1">Pet</label>
                    <select name="pet_id" id="pet_id" required class="w-full border rounded px-3 py-2 text-sm">
                        <option disabled selected>Select Pet</option>
                    </select>
                </div>
            </div>

            <!-- Row 3: Appointment Type & Appointment Date -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Type</label>
                    <select name="appoint_type" required class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">Select Type</option>
                        <option value="Walk-in">Walk-in</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Date</label>
                    <input type="date" name="appoint_date" required class="w-full border rounded px-3 py-2 text-sm" />
                </div>
            </div>

            <!-- Row 4: Appointment Time & Status -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Time</label>
                    <select name="appoint_time" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="" disabled selected>Select a time</option>
                        @foreach ([
                            '09:00 AM','10:00 AM','11:00 AM','01:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM'
                        ] as $label)
                            <option value="{{ date('H:i', strtotime($label)) }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Status</label>
                    <select name="appoint_status" required class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">Select status</option>
                        <option value="pending">Pending</option>
                        <option value="arrived">Arrived</option>
                        <option value="completed">Complete</option>
                       
                    </select>
                </div>
            </div>

            <!-- Row 5: Services & Description -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Selected Services</label>
                    <input type="text" id="selectedServicesDisplay" class="w-full border rounded px-3 py-2 text-sm" readonly placeholder="Click 'Select Services' to choose services" />
                    <button type="button" onclick="openServiceSelectionModal('add')" class="mt-2 bg-[#0f7ea0] text-white px-3 py-1 rounded hover:bg-[#0c6a86] text-sm">Select Services</button>
                </div>
                <div>
                    <label class="block text-sm mb-1">Description</label>
                    <textarea name="appoint_description" rows="3" class="w-full border rounded px-3 py-2 text-sm" placeholder="Add description..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeAddModal()" class="bg-gray-300 text-sm px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0d6b85]">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== Edit Appointment Modal ==================== -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-30">
    <div class="bg-white w-full max-w-3xl p-6 rounded shadow">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4">Update  Appointment</h2>
        <form method="POST" id="editForm" class="space-y-4">
            @csrf
            @method('PUT')

            <input type="hidden" id="edit_appoint_id" name="appoint_id">

            <!-- Row 1: Pet Owner -->
            <div class="mb-3">
                <label class="block text-sm mb-1">Pet Owner</label>
                <select id="edit_owner_id" class="w-full border rounded px-3 py-2 text-sm" required onchange="populateOwnerDetailsEdit(this)">
                    <option disabled selected>Select Pet Owner</option>
                    @foreach(\App\Models\Owner::with('pets')->get() as $owner)
                        <option value="{{ $owner->own_id }}" data-contact="{{ $owner->own_contactnum }}" data-pets='@json($owner->pets->map(fn($p) => ["id"=>$p->pet_id,"name"=>$p->pet_name]))'>
                            {{ $owner->own_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Row 2: Contact Number & Pet -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Contact Number</label>
                    <input type="text" id="edit_appoint_contactNum" name="appoint_contactNum" class="w-full border rounded px-3 py-2 text-sm" readonly />
                </div>
                <div>
                    <label class="block text-sm mb-1">Pet</label>
                    <select id="edit_pet_id" name="pet_id" required class="w-full border rounded px-3 py-2 text-sm">
                        <option disabled selected>Select Pet</option>
                    </select>
                </div>
            </div>

            <!-- Row 3: Appointment Type & Appointment Date -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Type</label>
                    <select id="edit_appoint_type" name="appoint_type" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="Walk-in">Walk-in</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Date</label>
                    <input type="date" id="edit_appoint_date" name="appoint_date" class="w-full border rounded px-3 py-2 text-sm" />
                </div>
            </div>

            <!-- Row 4: Appointment Time & Status -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Appointment Time</label>
                    <select id="edit_appoint_time" name="appoint_time" class="w-full border rounded px-3 py-2 text-sm">
                        @foreach ([
                            '09:00 AM','10:00 AM','11:00 AM','01:00 PM','02:00 PM','03:00 PM','04:00 PM','05:00 PM'
                        ] as $label)
                            <option value="{{ date('H:i', strtotime($label)) }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Appointment Status</label>
                    <select id="edit_appoint_status" name="appoint_status" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="pending">Pending</option>
                        <option value="arrived">Arrived</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <!-- Row 5: Services & Description -->
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-sm mb-1">Selected Services</label>
                    <input type="text" id="edit_selectedServicesDisplay" class="w-full border rounded px-3 py-2 text-sm" readonly placeholder="Click 'Select Services' to choose services" />
                    <button type="button" onclick="openServiceSelectionModal('edit')" class="mt-2 bg-[#0f7ea0] text-white px-3 py-1 rounded hover:bg-[#0c6a86] text-sm">Select Services</button>
                </div>
                <div>
                    <label class="block text-sm mb-1">Description</label>
                    <textarea id="edit_appoint_description" name="appoint_description" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-sm px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0d6b85]">Update</button>
            </div>
        </form>
    </div>
</div>


<!-- ==================== Multi-Service Selection Modal ==================== -->
<div id="serviceSelectionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-30">
    <div class="bg-white w-full max-w-lg p-6 rounded shadow">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4">Select Services</h2>
        <div id="serviceOptions" class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto mb-4">
            @foreach(\App\Models\Service::all() as $service)
                <label class="flex items-center gap-2 border p-2 rounded cursor-pointer hover:bg-gray-100">
                    <input type="checkbox" value="{{ $service->serv_id }}" data-name="{{ $service->serv_name }}" class="service-checkbox">
                    {{ $service->serv_name }}
                </label>
            @endforeach
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeServiceSelectionModal()" class="bg-gray-300 text-sm px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
            <button type="button" onclick="saveSelectedServices()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">Save</button>
        </div>
    </div>
</div>

<!-- ==================== JavaScript ==================== -->
<script>
let currentForm = null; // 'add' or 'edit'
let selectedServices = []; // holds currently selected service IDs as strings

// -------------------- Modal open/close --------------------
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('addModal').classList.add('flex');
    // ensure display reflects any existing hidden inputs (none at first)
    const ids = getSelectedFromForm('addForm');
    updateDisplayAndInternal('add', ids);
}
function closeAddModal() {
    document.getElementById('addModal').classList.remove('flex');
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(appointment) {
    // set form action
    document.getElementById('editForm').action = `/appointments/${appointment.appoint_id}`;

    // fill form fields
    document.getElementById('edit_appoint_id').value = appointment.appoint_id ?? '';
    document.getElementById('edit_appoint_date').value = appointment.appoint_date ?? '';
    document.getElementById('edit_appoint_time').value = appointment.appoint_time ?? '';
    document.getElementById('edit_appoint_contactNum').value = appointment.appoint_contactNum ?? '';
    document.getElementById('edit_appoint_status').value = appointment.appoint_status ?? '';
    document.getElementById('edit_appoint_type').value = appointment.appoint_type ?? '';
    document.getElementById('edit_appoint_description').value = appointment.appoint_description ?? '';

    // Owner & pet
    document.getElementById('edit_owner_id').value = appointment.pet?.owner?.own_id ?? '';
    populateOwnerDetailsEdit(document.getElementById('edit_owner_id'), appointment.pet_id ?? null);

    // Services: appointment.services is array (Eloquent)
    const serviceIds = (appointment.services || []).map(s => String(s.serv_id));
    // create hidden inputs in edit form for services[]
    createHiddenServiceInputs('editForm', serviceIds);
    // set display text
    const names = (appointment.services || []).map(s => s.serv_name).join(', ');
    document.getElementById('edit_selectedServicesDisplay').value = names;

    // open modal
    const m = document.getElementById('editModal'); m.classList.remove('hidden'); m.classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('flex');
    document.getElementById('editModal').classList.add('hidden');
}

// -------------------- Service modal logic --------------------
function getSelectedFromForm(formId) {
    // returns array of string ids currently present as hidden inputs inside the form
    return Array.from(document.querySelectorAll(`#${formId} input[name="services[]"]`)).map(i => String(i.value));
}

function createHiddenServiceInputs(formId, ids) {
    console.log('=== createHiddenServiceInputs called ===');
    console.log('Form ID:', formId);
    console.log('Service IDs to create:', ids);
    
    // remove existing hidden service inputs
    const existingInputs = document.querySelectorAll(`#${formId} input[name="services[]"]`);
    console.log('Removing existing inputs:', existingInputs.length);
    existingInputs.forEach(n => n.remove());
    
    // append new hidden inputs to the form
    const form = document.getElementById(formId);
    if (!form) {
        console.error('Form not found:', formId);
        return;
    }
    
    ids.forEach((id, index) => {
        console.log(`Creating input ${index + 1}/${ids.length} with value:`, id);
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'services[]';
        input.value = id;
        form.appendChild(input);
        console.log('Created input element:', input);
    });
    
    // ✅ Verify all inputs were created
    const newInputs = document.querySelectorAll(`#${formId} input[name="services[]"]`);
    console.log('Total hidden inputs after creation:', newInputs.length);
    console.log('Values:', Array.from(newInputs).map(i => i.value));
}

// ✅ Add form submit debugging
document.getElementById('addForm').addEventListener('submit', function(e) {
    console.log('=== Form submission ===');
    const formData = new FormData(this);
    
    // Log all form data
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Specifically check services
    const services = formData.getAll('services[]');
    console.log('Services being submitted:', services);
    
    if (services.length === 0) {
        console.warn('No services found in form data!');
    }
});


function openServiceSelectionModal(formType) {
    currentForm = formType; // 'add' or 'edit'
    const formId = formType === 'add' ? 'addForm' : 'editForm';

    // read currently selected service ids from the form (if any)
    selectedServices = getSelectedFromForm(formId);

    // check checkboxes accordingly
    const checkboxes = document.querySelectorAll('#serviceOptions .service-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectedServices.includes(String(cb.value));
    });

    // show modal
    const modal = document.getElementById('serviceSelectionModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function saveSelectedServices() {
    console.log('=== saveSelectedServices called ===');
    
    // read checked checkboxes
    const checkedBoxes = Array.from(document.querySelectorAll('#serviceOptions .service-checkbox:checked'));
    console.log('Checked boxes:', checkedBoxes);
    
    selectedServices = checkedBoxes.map(cb => String(cb.value));
    console.log('Selected service IDs:', selectedServices);
    
    const names = checkedBoxes.map(cb => cb.dataset.name);
    console.log('Selected service names:', names);

    const formId = currentForm === 'add' ? 'addForm' : 'editForm';
    console.log('Target form:', formId);
    
    // create/update hidden inputs in the correct form
    createHiddenServiceInputs(formId, selectedServices);
    
    // ✅ Verify hidden inputs were created
    const hiddenInputs = document.querySelectorAll(`#${formId} input[name="services[]"]`);
    console.log('Created hidden inputs:', Array.from(hiddenInputs).map(i => i.value));

    // update display field
    if (currentForm === 'add') {
        document.getElementById('selectedServicesDisplay').value = names.join(', ');
    } else {
        document.getElementById('edit_selectedServicesDisplay').value = names.join(', ');
    }

    closeServiceSelectionModal();
}

function closeServiceSelectionModal() {
    const modal = document.getElementById('serviceSelectionModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

// Helper used when opening add modal to reflect any existing hidden inputs
function updateDisplayAndInternal(formType, ids) {
    const names = [];
    ids.forEach(id => {
        const checkbox = document.querySelector(`#serviceOptions .service-checkbox[value="${id}"]`);
        if (checkbox) names.push(checkbox.dataset.name);
    });
    if (formType === 'add') {
        document.getElementById('selectedServicesDisplay').value = names.join(', ');
    } else {
        document.getElementById('edit_selectedServicesDisplay').value = names.join(', ');
    }
}

// -------------------- Owner -> Pet population --------------------
function populateOwnerDetails(select) {
    const petSelect = document.getElementById('pet_id');
    petSelect.innerHTML = '<option disabled selected>Select Pet</option>';
    const pets = JSON.parse(select.selectedOptions[0].dataset.pets || '[]');
    pets.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        petSelect.appendChild(opt);
    });
    document.getElementById('appoint_contactNum').value = select.selectedOptions[0].dataset.contact || '';
}

function populateOwnerDetailsEdit(select, petId = null) {
    const petSelect = document.getElementById('edit_pet_id');
    petSelect.innerHTML = '<option disabled selected>Select Pet</option>';
    const pets = JSON.parse(select.selectedOptions[0].dataset.pets || '[]');
    pets.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        if (String(p.id) === String(petId)) opt.selected = true;
        petSelect.appendChild(opt);
    });
    document.getElementById('edit_appoint_contactNum').value = select.selectedOptions[0].dataset.contact || '';
}

// -------------------- View (placeholder) --------------------
function openViewModal(appointment) {
    // implement a view modal as needed; placeholder for now
    alert('View appointment ID: ' + appointment.appoint_id);
}
</script>
@endsection
