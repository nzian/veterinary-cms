@extends('AdminBoard')

@section('content')

<div class="min-h-screen">
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow flex">

        <!-- Sidebar removed: Activities tab navigation is now handled by main tabs when attending a visit -->

        <main class="flex-1 pl-8">
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <h1 class="text-xl font-bold text-gray-800 border-b pb-3 mb-4">
                {{ $activityTabs[$activeKey]['icon'] }} 
                {{ $activityTabs[$activeKey]['label'] }} 
                for <span class="text-[#ff8c42]">{{ $visit->pet->pet_name ?? 'N/A Pet' }}</span>
            </h1>

            <div class="p-4 bg-gray-50 rounded-lg shadow-inner mb-6">
                <h3 class="font-semibold text-gray-700 mb-2">🧾 Common Visit Elements</h3>
                <div class="grid grid-cols-3 gap-x-6 text-sm">
                    <p><strong>Pet:</strong> {{ $visit->pet->pet_name ?? 'N/A' }} ({{ $visit->pet->pet_species ?? 'N/A' }})</p>
                    <p><strong>Owner:</strong> {{ $visit->pet->owner->own_name ?? 'N/A' }}</p>
                    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($visit->visit_date)->format('M d, Y') }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($visit->visit_status ?? 'Arrived') }}</p>
                    <p><strong>Vet/Staff:</strong> {{ auth()->user()->user_name ?? 'N/A' }}</p>
                </div>
            </div>
            
            <div class="tab-content border p-6 rounded-lg shadow-sm">
                
                @switch($activeKey)
                    
                    {{-- 🩺 1. Check-up / Consultation Tab --}}
                    @case('checkup')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'checkup']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Waiting" {{ ($visit->workflow_status ?? '') == 'Waiting' ? 'selected' : '' }}>Waiting</option>
                                    <option value="Consultation Ongoing" {{ ($visit->workflow_status ?? '') == 'Consultation Ongoing' ? 'selected' : '' }}>Consultation Ongoing</option>
                                    <option value="Completed" {{ ($visit->workflow_status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Check-up Process</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">📋 Pet Information (Auto-filled)</label>
                                    <div class="flex space-x-2">
                                        <input type="number" step="0.01" name="weight" placeholder="Weight (kg)" value="{{ $visit->weight }}" class="border p-2 rounded w-1/2">
                                        <input type="number" step="0.1" name="temperature" placeholder="Temp (°C)" value="{{ $visit->temperature }}" class="border p-2 rounded w-1/2">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🩻 Medical History Section (Notes)</label>
                                    <textarea name="medical_history_notes" rows="2" class="w-full border p-2 rounded" placeholder="Medical history details..."></textarea>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">🧪 Diagnosis & Notes</label>
                                <textarea name="diagnosis" rows="3" class="w-full border p-2 rounded" required placeholder="Primary diagnosis and notes"></textarea>
                            </div>

                            <div class="border p-4 rounded-lg bg-yellow-50 space-y-3">
                                <h4 class="font-medium">💊 Prescriptions / Recommendations</h4>
                                <button type="button" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">Add Medication / Product Used (Dynamic)</button>
                                <textarea name="prescriptions" rows="3" class="w-full border p-2 rounded" placeholder="Prescribed medications, dosage..."></textarea>
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="w-1/3">
                                    <label class="block text-sm font-medium mb-1">📅 Follow-up Appointment</label>
                                    <input type="date" name="followup_appointment" class="border p-2 rounded w-full">
                                </div>
                                <button type="button" class="bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Auto-generate bill for consultation fee</button>
                            </div>
                            <div class="flex gap-4 mt-4">
                                <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded font-semibold hover:bg-blue-600">Add Prescription</button>
                                <button type="button" class="bg-purple-500 text-white px-4 py-2 rounded font-semibold hover:bg-purple-600">Add Referral</button>
                            </div>

                            <div class="text-right border-t pt-4">
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete Appointment</button>
                            </div>
                        </form>
                        @break

                    {{-- 💉 2. Vaccination Tab --}}
                    @case('vaccination')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'vaccination']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Waiting" {{ ($visit->workflow_status ?? '') == 'Waiting' ? 'selected' : '' }}>Waiting</option>
                                    <option value="Consultation" {{ ($visit->workflow_status ?? '') == 'Consultation' ? 'selected' : '' }}>Consultation</option>
                                    <option value="Vaccination Ongoing" {{ ($visit->workflow_status ?? '') == 'Vaccination Ongoing' ? 'selected' : '' }}>Vaccination Ongoing</option>
                                    <option value="Observation" {{ ($visit->workflow_status ?? '') == 'Observation' ? 'selected' : '' }}>Observation</option>
                                    <option value="Completed" {{ ($visit->workflow_status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Vaccine Administration Process</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">🐾 Vaccine Name Dropdown</label>
                                    <select name="vaccine_name" class="w-full border p-2 rounded" required>
                                        <option value="">Select Vaccine</option>
                                        <option value="Anti-rabies">Anti-rabies</option>
                                        <option value="5-in-1">5-in-1 / DHPP</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">💉 Dose, Batch Number, Expiry</label>
                                    <input type="text" name="dose_batch" placeholder="Dose/Batch No." class="border p-2 rounded w-full" required>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">📅 Follow-up Appointment</label>
                                    <input type="date" name="followup_appointment" class="border p-2 rounded w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🩺 Vet who administered</label>
                                    <input type="text" name="administered_by" value="{{ auth()->user()->user_name ?? 'N/A' }}" class="border p-2 rounded w-full bg-gray-100" readonly>
                                </div>
                            </div>
                            <div class="flex gap-4 mt-4">
                                <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded font-semibold hover:bg-blue-600">Add Prescription</button>
                                <button type="button" class="bg-purple-500 text-white px-4 py-2 rounded font-semibold hover:bg-purple-600">Add Referral</button>
                            </div>

                            <div class="text-right border-t pt-4 flex justify-end items-center space-x-4">
                                <button type="button" class="bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Auto-billing for vaccination service</button>
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete</button>
                            </div>
                        </form>
                        @break

                    {{-- 🪱 3. Deworming Tab --}}
                    @case('deworming')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'deworming']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Waiting" {{ ($visit->workflow_status ?? '') == 'Waiting' ? 'selected' : '' }}>Waiting</option>
                                    <option value="Deworming Ongoing" {{ ($visit->workflow_status ?? '') == 'Deworming Ongoing' ? 'selected' : '' }}>Deworming Ongoing</option>
                                    <option value="Observation" {{ ($visit->workflow_status ?? '') == 'Observation' ? 'selected' : '' }}>Observation</option>
                                    <option value="Completed" {{ ($visit->workflow_status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Deworming Process</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">💊 Deworming Product Selection</label>
                                    <input type="text" name="product" placeholder="Product name" class="border p-2 rounded w-full" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🐶 Dosage calculation</label>
                                    <input type="text" name="dosage" placeholder="Dosage (based on {{ $visit->weight ?? 'N/A' }} kg)" class="border p-2 rounded w-full">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">📅 Follow-up Appointment</label>
                                    <input type="date" name="followup_appointment" class="border p-2 rounded w-full">
                                </div>
                                <div>
                                    <button type="button" class="mt-6 bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Auto-billing for deworming service</button>
                                </div>
                            </div>
                            <div class="flex gap-4 mt-4">
                                <button type="button" class="bg-blue-500 text-white px-4 py-2 rounded font-semibold hover:bg-blue-600">Add Prescription</button>
                                <button type="button" class="bg-purple-500 text-white px-4 py-2 rounded font-semibold hover:bg-purple-600">Add Referral</button>
                            </div>

                            <div class="text-right border-t pt-4">
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete</button>
                            </div>
                        </form>
                        @break

                    {{-- 🧼 4. Grooming Tab --}}
                    @case('grooming')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'grooming']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Waiting" {{ ($visit->workflow_status ?? '') == 'Waiting' ? 'selected' : '' }}>Waiting</option>
                                    <option value="In Grooming" {{ ($visit->workflow_status ?? '') == 'In Grooming' ? 'selected' : '' }}>In Grooming</option>
                                    <option value="Bathing" {{ ($visit->workflow_status ?? '') == 'Bathing' ? 'selected' : '' }}>Bathing</option>
                                    <option value="Drying" {{ ($visit->workflow_status ?? '') == 'Drying' ? 'selected' : '' }}>Drying</option>
                                    <option value="Finishing" {{ ($visit->workflow_status ?? '') == 'Finishing' ? 'selected' : '' }}>Finishing</option>
                                    <option value="Completed" {{ ($visit->workflow_status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="Picked Up" {{ ($visit->workflow_status ?? '') == 'Picked Up' ? 'selected' : '' }}>Picked Up</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Grooming Service Process</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">🧽 Type of grooming service</label>
                                    <select name="grooming_type" class="w-full border p-2 rounded" required>
                                        <option value="Basic">Basic Grooming</option>
                                        <option value="Haircut">Full Grooming/Haircut</option>
                                        <option value="Nail">Nail Trim Only</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🧺 Additional services</label>
                                    <input type="text" name="additional_services" placeholder="e.g., Ear Cleaning, Tick Bath" class="border p-2 rounded w-full">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">💬 Special instructions / remarks</label>
                                <textarea name="remarks" rows="3" class="w-full border p-2 rounded" placeholder="Notes on pet behavior or specific requests"></textarea>
                            </div>

                            <div class="text-right border-t pt-4 flex justify-end items-center space-x-4">
                                <button type="button" class="bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Auto-billing for grooming service</button>
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete</button>
                            </div>
                        </form>
                        @break

                    {{-- 🏥 5. Boarding Tab --}}
                    @case('boarding')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'boarding']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Reserved" {{ ($visit->workflow_status ?? '') == 'Reserved' ? 'selected' : '' }}>Reserved</option>
                                    <option value="Checked In" {{ ($visit->workflow_status ?? '') == 'Checked In' ? 'selected' : '' }}>Checked In</option>
                                    <option value="In Boarding" {{ ($visit->workflow_status ?? '') == 'In Boarding' ? 'selected' : '' }}>In Boarding</option>
                                    <option value="Ready for Pick-up" {{ ($visit->workflow_status ?? '') == 'Ready for Pick-up' ? 'selected' : '' }}>Ready for Pick-up</option>
                                    <option value="Checked Out" {{ ($visit->workflow_status ?? '') == 'Checked Out' ? 'selected' : '' }}>Checked Out</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Boarding Management Process</h3>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">🕒 Check-in Time</label>
                                    <input type="datetime-local" name="checkin_time" class="border p-2 rounded w-full" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🕒 Check-out Time (Est.)</label>
                                    <input type="datetime-local" name="checkout_time" class="border p-2 rounded w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🐶 Cage/room assignment</label>
                                    <input type="text" name="room_assignment" placeholder="Room/Cage ID" class="border p-2 rounded w-full">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">🍖 Feeding schedule and care instructions</label>
                                <textarea name="care_instructions" rows="3" class="w-full border p-2 rounded" placeholder="Dietary needs, medication times, exercise schedule..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">🩺 Daily monitoring notes (Add Note button needed)</label>
                                <textarea name="monitoring_notes" rows="2" class="w-full border p-2 rounded" placeholder="Daily vitals, mood, stool checks..."></textarea>
                            </div>

                            <div class="text-right border-t pt-4 flex justify-end items-center space-x-4">
                                <button type="button" class="bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Billing per day / per hour</button>
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete Check-out</button>
                            </div>
                        </form>
                        @break
                        
                    {{-- 🧪 6. Diagnostic / Laboratory Tab --}}
                    @case('diagnostic')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'diagnostic']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Waiting" {{ ($visit->workflow_status ?? '') == 'Waiting' ? 'selected' : '' }}>Waiting</option>
                                    <option value="Sample Collection" {{ ($visit->workflow_status ?? '') == 'Sample Collection' ? 'selected' : '' }}>Sample Collection</option>
                                    <option value="Testing" {{ ($visit->workflow_status ?? '') == 'Testing' ? 'selected' : '' }}>Testing</option>
                                    <option value="Results Encoding" {{ ($visit->workflow_status ?? '') == 'Results Encoding' ? 'selected' : '' }}>Results Encoding</option>
                                    <option value="Completed" {{ ($visit->workflow_status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Diagnostic Work-up Process</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">🧬 Type of test (Dropdown)</label>
                                    <select name="test_type" class="w-full border p-2 rounded" required>
                                        <option value="Blood">Blood Work (CBC/Chem)</option>
                                        <option value="Urine">Urinalysis</option>
                                        <option value="Fecal">Fecalysis</option>
                                        <option value="X-ray">Radiograph (X-ray)</option>
                                        <option value="Ultrasound">Ultrasound</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">📑 Lab results upload/entry</label>
                                    <input type="file" name="lab_file" class="border p-2 rounded w-full">
                                    <p class="text-xs text-gray-500 mt-1">or manually paste results below.</p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-1">🩺 Interpretation / Diagnosis</label>
                                <textarea name="interpretation" rows="4" class="w-full border p-2 rounded" placeholder="Clinical interpretation of test results and final diagnosis"></textarea>
                            </div>

                            <div class="text-right border-t pt-4 flex justify-end items-center space-x-4">
                                <button type="button" class="bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Billing for each test</button>
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete</button>
                            </div>
                        </form>
                        @break
                        
                    {{-- 🐾 7. Surgical Services Tab --}}
                    @case('surgical')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'surgical']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Waiting" {{ ($visit->workflow_status ?? '') == 'Waiting' ? 'selected' : '' }}>Waiting</option>
                                    <option value="Pre-op" {{ ($visit->workflow_status ?? '') == 'Pre-op' ? 'selected' : '' }}>Pre-op</option>
                                    <option value="Surgery Ongoing" {{ ($visit->workflow_status ?? '') == 'Surgery Ongoing' ? 'selected' : '' }}>Surgery Ongoing</option>
                                    <option value="Recovery" {{ ($visit->workflow_status ?? '') == 'Recovery' ? 'selected' : '' }}>Recovery</option>
                                    <option value="Completed" {{ ($visit->workflow_status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Surgical Service Process</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">📝 Pre-surgery checklist/Diagnosis</label>
                                    <textarea name="pre_surgery_notes" rows="3" class="w-full border p-2 rounded" placeholder="Checklist complete, required tests done..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🩺 Diagnosis & type of surgery</label>
                                    <input type="text" name="surgery_type" placeholder="Type of surgery" class="border p-2 rounded w-full" required>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">⏱ Start Time</label>
                                    <input type="datetime-local" name="start_time" class="border p-2 rounded w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">⏱ End Time</label>
                                    <input type="datetime-local" name="end_time" class="border p-2 rounded w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">👩‍⚕️ Surgeon and assisting staff</label>
                                    <input type="text" name="staff" placeholder="Staff names" class="border p-2 rounded w-full">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">💊 Medications used (Anesthesia, Post-op)</label>
                                <textarea name="medications_used" rows="2" class="w-full border p-2 rounded" placeholder="Anesthesia type, dosage, post-op pain meds..."></textarea>
                            </div>

                            <div class="text-right border-t pt-4 flex justify-end items-center space-x-4">
                                <button type="button" class="bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Billing for surgical services</button>
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete</button>
                            </div>
                        </form>
                        @break
                        
                    {{-- 🚨 8. Emergency Tab --}}
                    @case('emergency')
                        <form action="{{ route('activities.save', ['visitId' => $visit->visit_id, 'activityKey' => 'emergency']) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-1">Workflow Status</label>
                                <select name="workflow_status" class="border p-2 rounded w-1/2">
                                    <option value="Triage" {{ ($visit->workflow_status ?? '') == 'Triage' ? 'selected' : '' }}>Triage</option>
                                    <option value="Stabilization" {{ ($visit->workflow_status ?? '') == 'Stabilization' ? 'selected' : '' }}>Stabilization</option>
                                    <option value="Treatment" {{ ($visit->workflow_status ?? '') == 'Treatment' ? 'selected' : '' }}>Treatment</option>
                                    <option value="Observation" {{ ($visit->workflow_status ?? '') == 'Observation' ? 'selected' : '' }}>Observation</option>
                                    <option value="Completed" {{ ($visit->workflow_status ?? '') == 'Completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">Emergency Case Triage & Treatment</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">⏳ Triage notes (Time of arrival, Status)</label>
                                    <textarea name="triage_notes" rows="3" class="w-full border p-2 rounded" placeholder="Time of arrival, initial status (ABCs), triage level..." required></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">🩺 Emergency procedures performed</label>
                                    <textarea name="procedures" rows="3" class="w-full border p-2 rounded" placeholder="IV placement, CPR, intubation, fluid therapy..."></textarea>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">💊 Immediate medications (Drugs, dosage, route)</label>
                                <textarea name="immediate_meds" rows="2" class="w-full border p-2 rounded" placeholder="Epinephrine, pain relief, sedatives..."></textarea>
                            </div>

                            <div class="text-right border-t pt-4 flex justify-end items-center space-x-4">
                                <button type="button" class="bg-orange-500 text-white px-4 py-2 rounded font-semibold hover:bg-orange-600">🧾 Emergency fee + services used</button>
                                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">✅ Save & Complete</button>
                            </div>
                        </form>
                        @break

                    @default
                        <div class="p-10 text-center text-gray-500">
                            <p class="text-lg">Please select an **Activity** from the sidebar to start documenting this visit.</p>
                            <p class="mt-2 text-sm">The initial service type ({{ $visit->service_type ?? 'N/A' }}) has not been mapped yet.</p>
                        </div>
                @endswitch

                <div class="mt-8 pt-4 border-t border-gray-300">
                    <h3 class="font-semibold text-gray-700 mb-3">Common Actions</h3>
                    <div class="space-x-3">
                        <button type="button" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Add Medication / Product Used</button>
                        <button type="button" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Remarks / Notes</button>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>

@endsection