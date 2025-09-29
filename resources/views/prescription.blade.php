{{-- COMPLETE FIXED PRESCRIPTION BLADE --}}
@extends('AdminBoard')

@section('content')
<div class="min-h-screen">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-[#0f7ea0] font-bold text-xl">Prescriptions</h2>
            <button onclick="openAddModal()" class="bg-[#0f7ea0] text-white text-sm px-4 py-2 rounded hover:bg-[#0c6a86]">
                + Add Prescription
            </button>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-500 text-white p-2 rounded mb-4">
            {{ session('error') }}
        </div>
        @endif

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm border text-center">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-2">#</th>
                        <th class="border px-2 py-2">Pet</th>
                        <th class="border px-2 py-2">Date</th>
                        <th class="border px-2 py-2">Medications</th>
                        <th class="border px-2 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prescriptions as $index => $prescription)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-2">{{ $index + 1 }}</td>
                        <td class="border px-2 py-2">{{ $prescription->pet->pet_name }}</td>
                        <td class="border px-2 py-2">{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('F d, Y') }}</td>
                        <td class="border px-2 py-2">
                            @if($prescription->medication)
                                @php
                                    $medications = json_decode($prescription->medication, true) ?? [];
                                @endphp
                                {{ count($medications) }} medication(s)
                            @else
                                No medications
                            @endif
                        </td>
                        <td class="border px-2 py-1 flex justify-center gap-1">
                            <!-- View -->
                            <button onclick="viewPrescription(this)" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-xs"
                                data-id="{{ $prescription->prescription_id }}"
                                data-pet="{{ $prescription->pet->pet_name }}"
                                data-species="{{ $prescription->pet->pet_species }}"
                                data-breed="{{ $prescription->pet->pet_breed }}"
                                data-weight="{{ $prescription->pet->pet_weight }}"
                                data-age="{{ $prescription->pet->pet_age }}"
                                data-temp="{{ $prescription->pet->pet_temperature }}"
                                data-gender="{{ $prescription->pet->pet_gender }}"
                                data-date="{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('F d, Y') }}"
                                data-medication="{{ $prescription->medication }}"
                                data-notes="{{ $prescription->notes }}"
                                data-branch-name="{{ $prescription->branch->branch_name ?? 'Main Branch' }}"
                                data-branch-address="{{ $prescription->branch->branch_address ?? 'Branch Address' }}"
                                data-branch-contact="{{ $prescription->branch->branch_contactNum ?? 'Contact Number' }}">
                                <i class="fas fa-eye"></i>
                            </button>

                            <!-- Direct Print Button -->
                            <button onclick="directPrint(this)" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600 text-xs"
                                data-id="{{ $prescription->prescription_id }}"
                                data-pet="{{ $prescription->pet->pet_name }}"
                                data-species="{{ $prescription->pet->pet_species }}"
                                data-breed="{{ $prescription->pet->pet_breed }}"
                                data-weight="{{ $prescription->pet->pet_weight }}"
                                data-age="{{ $prescription->pet->pet_age }}"
                                data-temp="{{ $prescription->pet->pet_temperature }}"
                                data-gender="{{ $prescription->pet->pet_gender }}"
                                data-date="{{ \Carbon\Carbon::parse($prescription->prescription_date)->format('F d, Y') }}"
                                data-medication="{{ $prescription->medication }}"
                                data-notes="{{ $prescription->notes }}"
                                data-branch-name="{{ $prescription->branch->branch_name ?? 'Main Branch' }}"
                                data-branch-address="{{ $prescription->branch->branch_address ?? 'Branch Address' }}"
                                data-branch-contact="{{ $prescription->branch->branch_contactNum ?? 'Contact Number' }}">
                                <i class="fas fa-print"></i>
                            </button>

                            <!-- Edit -->
                            <button onclick="editPrescription({{ $prescription->prescription_id }})" class="bg-[#0f7ea0] text-white px-2 py-1 rounded hover:bg-[#0c6a86] text-xs">
                                <i class="fas fa-pen"></i>
                            </button>

                            <!-- Delete -->
                            <form action="{{ route('prescriptions.destroy', $prescription->prescription_id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this prescription?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 text-xs">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 py-4">No prescriptions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add/Edit Prescription Modal --}}
<div id="prescriptionModal" class="fixed inset-0 bg-black bg-opacity-30 flex justify-center items-center hidden z-50">
    <div class="bg-white w-full max-w-4xl p-6 rounded shadow-lg max-h-[90vh] overflow-y-auto">
        <h2 class="text-lg font-semibold text-[#0f7ea0] mb-4" id="modalTitle">Add Prescription</h2>
        <form id="prescriptionForm" method="POST">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="prescription_id" id="prescription_id">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm">Pet</label>
                    <select name="pet_id" id="pet_id" class="w-full border px-2 py-1 rounded" required>
                        <option value="">Select Pet</option>
                        @foreach ($pets as $pet)
                            <option value="{{ $pet->pet_id }}">{{ $pet->pet_name }} ({{ $pet->pet_species }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm">Date</label>
                    <input type="date" name="prescription_date" id="prescription_date" class="w-full border px-2 py-1 rounded" required>
                </div>
            </div>

            {{-- Medications Section --}}
            <div class="mb-4">
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-sm font-medium">Medications</label>
                    <button type="button" onclick="addMedicationField()" class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600">
                        <i class="fas fa-plus"></i> Add Medication
                    </button>
                </div>
                
                <div id="medicationContainer" class="space-y-3">
                    <!-- Initial medication field will be added by JavaScript -->
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm">Notes/Recommendations</label>
                <textarea name="notes" id="notes" rows="3" class="w-full border px-2 py-1 rounded" placeholder="Additional notes or recommendations"></textarea>
            </div>

            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400" onclick="closeModal()">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-[#0f7ea0] text-white rounded hover:bg-[#0d6b85]">Save Prescription</button>
            </div>
        </form>
    </div>
</div>

{{-- View Prescription Modal --}}
<div id="viewPrescriptionModal" class="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50 hidden no-print">
    <div class="bg-white w-full max-w-2xl p-0 rounded-lg shadow-lg relative max-h-[100vh] overflow-y-auto">
        <div id="prescriptionContent" class="prescription-container bg-white p-10">
            <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
                <!-- Left side: Logo -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
                </div>
                
                <!-- Right side: Clinic Information -->
                <div class="flex-grow text-center">
                    <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                        PETS 2GO VETERINARY CLINIC
                    </div>
                    <div class="branch-name text-lg font-bold underline text-center mt-1" id="branchName">
                    
                    </div>
                    <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                        <div id="branchAddress"></div>
                        <div id="branchContact"></div>
                    </div>
                </div>
            </div>

            <div class="prescription-body">
                <div class="patient-info mb-6">
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <div class="mb-2"><strong>DATE:</strong> <span id="viewDate"></span></div>
                            <div class="mb-2"><strong>NAME OF PET:</strong> <span id="viewPet"></span></div>
                        </div>
                        <div class="text-center">
                            <div><strong>WEIGHT:</strong> <span id="viewWeight"></span></div>
                            <div><strong>TEMP:</strong> <span id="viewTemp"></span></div>
                        </div>
                        <div class="text-right">
                            <div><strong>AGE:</strong> <span id="viewAge"></span></div>
                            <div><strong>GENDER:</strong> <span id="viewGender"></span></div>
                        </div>
                    </div>
                </div>

                <div class="rx-symbol text-left my-8 text-6xl font-bold text-gray-800">℞</div>

                <div class="medication-section mb-8">
                    <div class="section-title text-base font-bold mb-4">MEDICATION</div>
                    <div id="medicationsList" class="space-y-3"></div>
                </div>

                <div class="recommendations mb-8">
                    <h3 class="text-base font-bold mb-4">RECOMMENDATION/REMINDER:</h3>
                    <div id="viewNotes" class="text-sm"></div>
                </div>

                <div class="footer text-right pt-8 border-t-2 border-black">
                    <div class="doctor-info text-sm">
                        <div class="doctor-name font-bold mb-1">JAN JERICK M. GO DVM</div>
                        <div class="license-info text-gray-600">License No.: 0012045</div>
                        <div class="license-info text-gray-600">Attending Veterinarian</div>
                    </div>
                </div>
            </div>
        </div>
        <button onclick="document.getElementById('viewPrescriptionModal').classList.add('hidden')" 
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-800 text-2xl no-print">&times;</button>
    </div>
</div>

{{-- Hidden Print Container --}}
<div id="printContainer" style="display: none;">
    <div id="printContent" class="prescription-container bg-white p-10">
        <!-- Content will be populated by JavaScript -->
    </div>
</div>

<style>
.prescription-container {
    font-family: Arial, sans-serif;
    max-width: 700px;
    margin: 0 auto;
    border: 1px solid #000;
    background-color: white;
}

.medication-item {
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 12px;
    padding: 8px;
    border-left: 3px solid #dc2626;
    background-color: #fef2f2;
}

.medication-field {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    background-color: #f9fafb;
}

.product-suggestions {
    position: absolute;
    z-index: 1000;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
}

.product-suggestion-item:hover {
    background-color: #f3f4f6;
}

.product-suggestion-item:last-child {
    border-bottom: none;
}

.rx-symbol {
    text-align: center !important;
    margin: 20px 0 !important;
}

/* Print Styles */
@media print {
    @page {
        margin: 0.5in;
        size: A4;
    }
    
    body * {
        visibility: hidden;
    }
    
    #printContainer,
    #printContainer *,
    #printContent,
    #printContent * {
        visibility: visible !important;
    }
    
    #printContainer {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100% !important;
        display: block !important;
        background: white !important;
    }
    
    #printContent {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        border: 2px solid #000 !important;
        padding: 30px !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        page-break-inside: avoid;
    }
    
    .no-print {
        display: none !important;
        visibility: hidden !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .clinic-name {
        color: #a86520 !important;
    }
    
    .medication-item {
        border-left: 3px solid #dc2626 !important;
        background-color: #fef2f2 !important;
    }
}
</style>

<script>
let currentPrescriptionId = null;
let medicationCounter = 0;

// Initialize CSRF token for AJAX requests
function setupCSRF() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.csrfToken = token.getAttribute('content');
    }
    
    // Set default headers for fetch requests
    window.fetch = (function(origFetch) {
        return function(...args) {
            const [url, config = {}] = args;
            if (config.method && config.method.toUpperCase() !== 'GET') {
                config.headers = {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...config.headers
                };
            }
            return origFetch(url, config);
        };
    })(window.fetch);
}

function openAddModal() {
    const form = document.getElementById('prescriptionForm');
    form.reset();
    form.action = "{{ route('prescriptions.store') }}";
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('modalTitle').textContent = 'Add Prescription';
    document.getElementById('prescription_id').value = '';
    
    // Reset medication container
    document.getElementById('medicationContainer').innerHTML = '';
    medicationCounter = 0;
    addMedicationField(); // Add first field
    
    document.getElementById('prescriptionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('prescriptionModal').classList.add('hidden');
}

function addMedicationField() {
    const container = document.getElementById('medicationContainer');
    const fieldId = ++medicationCounter;
    
    const fieldHtml = `
        <div class="medication-field" data-field-id="${fieldId}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-sm font-medium text-gray-700">Medication ${fieldId}</h4>
                ${fieldId > 1 ? `<button type="button" onclick="removeMedicationField(${fieldId})" class="text-red-500 hover:text-red-700 text-sm"><i class="fas fa-trash"></i> Remove</button>` : ''}
            </div>
            
            <div class="grid grid-cols-1 gap-3 mb-3">
                <div class="relative">
                    <label class="block text-xs text-gray-600 mb-1">Search Product or Enter Manually</label>
                    <input type="text" 
                           class="product-search w-full border px-2 py-2 rounded text-sm" 
                           placeholder="Type product name or search from database..."
                           data-field-id="${fieldId}">
                    <div class="product-suggestions hidden" data-field-id="${fieldId}"></div>
                    <input type="hidden" class="selected-product-id" data-field-id="${fieldId}">
                    <input type="hidden" class="selected-product-name" data-field-id="${fieldId}">
                </div>
                
                <div class="bg-gray-50 p-2 rounded text-xs">
                    <div class="selected-product-display" data-field-id="${fieldId}">
                        Manual entry or select from search results above
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs text-gray-600 mb-1">Instructions (Sig.) - Use semicolon (;) to separate multiple instructions</label>
                <textarea class="medication-instructions w-full border px-2 py-2 rounded text-sm" 
                          rows="2" 
                          data-field-id="${fieldId}"
                          placeholder="e.g., Use it everyday; Apply twice daily; Take with food" required></textarea>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', fieldHtml);
    setupProductSearch(fieldId);
}

function removeMedicationField(fieldId) {
    const field = document.querySelector(`[data-field-id="${fieldId}"]`);
    if (field && document.querySelectorAll('.medication-field').length > 1) {
        field.remove();
    }
}

function setupProductSearch(fieldId) {
    const searchInput = document.querySelector(`input[data-field-id="${fieldId}"].product-search`);
    const suggestionsDiv = document.querySelector(`.product-suggestions[data-field-id="${fieldId}"]`);
    const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`);
    const productNameInput = document.querySelector(`.selected-product-name[data-field-id="${fieldId}"]`);
    const displayDiv = document.querySelector(`.selected-product-display[data-field-id="${fieldId}"]`);
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Update the product name for manual entry
        productNameInput.value = query;
        if (query) {
            displayDiv.innerHTML = `<span class="text-blue-700 font-medium">Manual Entry: ${query}</span>`;
            displayDiv.classList.remove('bg-gray-100');
            displayDiv.classList.add('bg-blue-100');
        } else {
            displayDiv.innerHTML = 'Manual entry or select from search results above';
            displayDiv.classList.remove('bg-blue-100');
            displayDiv.classList.add('bg-gray-100');
        }
        
        if (query.length < 2) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        // Debounce the search to avoid too many requests
        searchTimeout = setTimeout(() => {
            // Show loading
            suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-gray-500">Searching...</div>';
            suggestionsDiv.classList.remove('hidden');
            
            // Fetch products from database
            fetch(`{{ route('prescriptions.search-products') }}?q=${encodeURIComponent(query)}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => {
                    console.log('Search response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(products => {
                    console.log('Products received:', products);
                    if (products.length > 0) {
                        suggestionsDiv.innerHTML = products.map(product => `
                            <div class="product-suggestion-item" data-product-id="${product.id}" data-product-name="${product.name}">
                                <div class="font-medium">${product.name}</div>
                                <div class="text-xs text-gray-500">₱${parseFloat(product.price || 0).toFixed(2)} - ${product.type || 'Product'}</div>
                            </div>
                        `).join('');
                        
                        suggestionsDiv.classList.remove('hidden');
                        
                        // Add click handlers
                        suggestionsDiv.querySelectorAll('.product-suggestion-item').forEach(item => {
                            item.addEventListener('click', function() {
                                const productId = this.dataset.productId;
                                const productName = this.dataset.productName;
                                
                                productIdInput.value = productId;
                                productNameInput.value = productName;
                                displayDiv.innerHTML = `<span class="text-green-700 font-medium">Selected: ${productName}</span>`;
                                displayDiv.classList.remove('bg-gray-100', 'bg-blue-100');
                                displayDiv.classList.add('bg-green-100');
                                
                                searchInput.value = productName;
                                suggestionsDiv.classList.add('hidden');
                            });
                        });
                    } else {
                        suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-gray-500">No products found in database. You can still type manually above.</div>';
                        suggestionsDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error searching products:', error);
                    suggestionsDiv.innerHTML = '<div class="product-suggestion-item text-orange-500">Search temporarily unavailable. You can still type manually above.</div>';
                    suggestionsDiv.classList.remove('hidden');
                });
        }, 300); // 300ms delay
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.parentElement.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
}

// Form submission handler
document.getElementById('prescriptionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Form submitted');
    
    // Collect all medication data
    const medications = [];
    document.querySelectorAll('.medication-field').forEach(field => {
        const fieldId = field.dataset.fieldId;
        const productId = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`).value;
        const productName = document.querySelector(`.selected-product-name[data-field-id="${fieldId}"]`).value || 
                           document.querySelector(`input[data-field-id="${fieldId}"].product-search`).value;
        const instructions = document.querySelector(`.medication-instructions[data-field-id="${fieldId}"]`).value;
        
        if (productName && instructions) {
            medications.push({
                product_id: productId || null,
                product_name: productName,
                instructions: instructions
            });
        }
    });
    
    console.log('Medications collected:', medications);
    
    // Validate medications
    if (medications.length === 0) {
        alert('Please add at least one medication with instructions');
        return;
    }
    
    // Validate other required fields
    const petId = document.getElementById('pet_id').value;
    const prescriptionDate = document.getElementById('prescription_date').value;
    
    if (!petId) {
        alert('Please select a pet');
        return;
    }
    
    if (!prescriptionDate) {
        alert('Please select a date');
        return;
    }
    
    // Add medications JSON to form - remove any existing ones first
    const existingHiddenInput = this.querySelector('input[name="medications_json"]');
    if (existingHiddenInput) {
        existingHiddenInput.remove();
    }
    
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'medications_json';
    hiddenInput.value = JSON.stringify(medications);
    this.appendChild(hiddenInput);
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    console.log('Submitting form to:', this.action);
    
    // Submit form normally (not AJAX)
    this.submit();
});

function editPrescription(id) {
    console.log('Editing prescription:', id);
    
    // Fetch prescription data and populate the form
    fetch(`/prescriptions/${id}/edit`, {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken || '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
        .then(response => {
            console.log('Edit response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Edit data received:', data);
            
            if (!data.prescription_id) {
                throw new Error('Invalid prescription data received');
            }
            
            document.getElementById('prescriptionForm').reset();
            document.getElementById('prescription_id').value = id;
            document.getElementById('pet_id').value = data.pet_id;
            document.getElementById('prescription_date').value = data.prescription_date;
            document.getElementById('notes').value = data.notes || '';

            // Clear and populate medications
            document.getElementById('medicationContainer').innerHTML = '';
            medicationCounter = 0;

            if (data.medications && data.medications.length > 0) {
                data.medications.forEach(med => {
                    addMedicationField();
                    const currentFieldId = medicationCounter;
                    
                    // Set product data
                    const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${currentFieldId}"]`);
                    const productNameInput = document.querySelector(`.selected-product-name[data-field-id="${currentFieldId}"]`);
                    const searchInput = document.querySelector(`input[data-field-id="${currentFieldId}"].product-search`);
                    const displayDiv = document.querySelector(`.selected-product-display[data-field-id="${currentFieldId}"]`);
                    const instructionsTextarea = document.querySelector(`.medication-instructions[data-field-id="${currentFieldId}"]`);
                    
                    productIdInput.value = med.product_id || '';
                    productNameInput.value = med.product_name || '';
                    searchInput.value = med.product_name || '';
                    displayDiv.innerHTML = `<span class="text-green-700 font-medium">Selected: ${med.product_name || 'Unknown Product'}</span>`;
                    displayDiv.classList.remove('bg-gray-100');
                    displayDiv.classList.add('bg-green-100');
                    instructionsTextarea.value = med.instructions || '';
                });
            } else {
                addMedicationField(); // Add at least one field
            }

            document.getElementById('prescriptionForm').action = `/prescriptions/${id}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('modalTitle').textContent = 'Edit Prescription';
            document.getElementById('prescriptionModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error fetching prescription data:', error);
            alert('Error loading prescription data: ' + error.message);
        });
}

function populatePrescriptionData(button) {
    let medications = [];
    try {
        medications = JSON.parse(button.dataset.medication || '[]');
    } catch (e) {
        // If it's old format (semicolon separated), convert it
        if (button.dataset.medication) {
            const oldMeds = button.dataset.medication.split(';');
            medications = oldMeds.map(med => ({
                product_name: med.trim(),
                instructions: '[Instructions will be added here]'
            }));
        }
    }
    
    const prescriptionData = {
        id: button.dataset.id,
        pet: button.dataset.pet,
        weight: button.dataset.weight || 'N/A',
        temp: button.dataset.temp || 'N/A',
        age: button.dataset.age || 'N/A',
        gender: button.dataset.gender || 'N/A',
        date: button.dataset.date,
        medications: medications,
        notes: button.dataset.notes || 'No specific recommendations',
        branchName: button.dataset.branchName.toUpperCase(),
        branchAddress: 'Address: ' + button.dataset.branchAddress,
        branchContact: "Contact No: " + button.dataset.branchContact
    };
    
    return prescriptionData;
}

function updatePrescriptionContent(targetId, data) {
    const container = document.getElementById(targetId);
    
    container.innerHTML = `
        <div class="header flex items-center justify-between border-b-2 border-black pb-6 mb-6">
            <div class="flex-shrink-0">
                <img src="{{ asset('images/pets2go.png') }}" alt="Pets2GO Logo" class="w-28 h-28 object-contain">
            </div>
            <div class="flex-grow text-center">
                <div class="clinic-name text-2xl font-bold text-[#a86520] tracking-wide">
                    PETS 2GO VETERINARY CLINIC
                </div>
                <div class="branch-name text-lg font-bold underline text-center mt-1">
                    ${data.branchName}
                </div>
                <div class="clinic-details text-sm text-gray-700 mt-1 text-center leading-tight">
                    <div>${data.branchAddress}</div>
                    <div>${data.branchContact}</div>
                </div>
            </div>
        </div>

        <div class="prescription-body">
            <div class="patient-info mb-6">
                <div class="grid grid-cols-3 gap-2 text-sm">
                    <div>
                        <div class="mb-2"><strong>DATE:</strong> ${data.date}</div>
                        <div class="mb-2"><strong>NAME OF PET:</strong> ${data.pet}</div>
                    </div>
                    <div class="text-center">
                        <div><strong>WEIGHT:</strong> ${data.weight}</div>
                        <div><strong>TEMP:</strong> ${data.temp}</div>
                    </div>
                    <div class="text-right">
                        <div><strong>AGE:</strong> ${data.age}</div>
                        <div><strong>GENDER:</strong> ${data.gender}</div>
                    </div>
                </div>
            </div>

            <div class="rx-symbol text-center my-8 text-6xl font-bold text-gray-800">℞</div>

            <div class="medication-section mb-8">
                <div class="section-title text-base font-bold mb-4">MEDICATION</div>
                <div class="space-y-3">
                    ${data.medications.length > 0 ? data.medications.map((med, index) => `
                        <div class="medication-item">
                            <div class="text-sm font-medium text-red-600 mb-1">${index+1}. ${med.product_name || med.name || 'Unknown medication'}</div>
                            <div class="text-sm text-gray-700 ml-4"><strong>SIG.</strong> ${med.instructions || '[Instructions will be added here]'}</div>
                        </div>
                    `).join('') : '<div class="medication-item text-gray-500">No medications prescribed</div>'}
                </div>
            </div>

            <div class="recommendations mb-8">
                <h3 class="text-base font-bold mb-4">RECOMMENDATION/REMINDER:</h3>
                <div class="text-sm">${data.notes}</div>
            </div>

            <div class="footer text-right pt-8 border-t-2 border-black">
                <div class="doctor-info text-sm">
                    <div class="doctor-name font-bold mb-1">JAN JERICK M. GO DVM</div>
                    <div class="license-info text-gray-600">License No.: 0012045</div>
                    <div class="license-info text-gray-600">Attending Veterinarian</div>
                </div>
            </div>
        </div>
    `;
}

function viewPrescription(button) {
    currentPrescriptionId = button.dataset.id;
    const data = populatePrescriptionData(button);
    
    // Update modal content
    document.getElementById('viewPet').innerText = data.pet;
    document.getElementById('viewWeight').innerText = data.weight;
    document.getElementById('viewTemp').innerText = data.temp;
    document.getElementById('viewAge').innerText = data.age;
    document.getElementById('viewGender').innerText = data.gender;
    document.getElementById('viewDate').innerText = data.date;
    document.getElementById('branchName').innerText = data.branchName;
    document.getElementById('branchAddress').innerText = data.branchAddress;
    document.getElementById('branchContact').innerText = data.branchContact;

    const medsContainer = document.getElementById('medicationsList');
    medsContainer.innerHTML = '';
    
    if (data.medications && data.medications.length > 0) {
        data.medications.forEach((med, index) => {
            const medDiv = document.createElement('div');
            medDiv.classList.add('medication-item');
            medDiv.innerHTML = `
                <div class="text-sm font-medium text-red-600 mb-1">${index+1}. ${med.product_name || med.name || 'Unknown medication'}</div>
                <div class="text-sm text-gray-700 ml-4"><strong>SIG.</strong> ${med.instructions || '[Instructions will be added here]'}</div>
            `;
            medsContainer.appendChild(medDiv);
        });
    } else {
        const medDiv = document.createElement('div');
        medDiv.classList.add('medication-item', 'text-gray-500');
        medDiv.innerHTML = 'No medications prescribed';
        medsContainer.appendChild(medDiv);
    }

    document.getElementById('viewNotes').innerText = data.notes;
    document.getElementById('viewPrescriptionModal').classList.remove('hidden');
}

function directPrint(button) {
    const data = populatePrescriptionData(button);
    
    // Update the hidden print container
    updatePrescriptionContent('printContent', data);
    
    // Show the print container temporarily
    const printContainer = document.getElementById('printContainer');
    printContainer.style.display = 'block';
    
    // Small delay to ensure content is rendered, then print
    setTimeout(() => {
        window.print();
        // Hide the container again after printing
        printContainer.style.display = 'none';
    }, 200);
}

function printFromModal() {
    const modalContent = document.getElementById('prescriptionContent');
    const printContent = document.getElementById('printContent');
    const printContainer = document.getElementById('printContainer');
    
    // Clone the modal content to print content
    printContent.innerHTML = modalContent.innerHTML;
    
    // Show the print container temporarily
    printContainer.style.display = 'block';
    
    // Trigger print
    setTimeout(() => {
        window.print();
        // Hide the container again after printing
        printContainer.style.display = 'none';
    }, 200);
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    setupCSRF();
});
</script>

@endsection