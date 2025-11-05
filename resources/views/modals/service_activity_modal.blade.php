{{-- Toast Container --}}
<div id="toastContainer" class="toast-container"></div>

<div id="serviceActivityModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
    {{-- MODAL CONTAINER: Added max-h-[95vh] and overflow-y-auto --}}
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-6 border-b pb-3 sticky top-0 bg-white z-10">
            <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-clipboard-list mr-2 text-purple-600"></i> Service Actions
            </h3>
            <button onclick="closeActivityModal()" class="text-gray-500 hover:text-gray-700 text-xl"><i class="fas fa-times"></i></button>
        </div>

        {{-- Context Bar --}}
        <div class="grid grid-cols-3 gap-6 mb-6">
            <div class="col-span-1 bg-gray-100 p-3 rounded-lg text-sm border-l-4 border-purple-500">
                <label class="block text-xs font-semibold text-gray-500">Pet:</label>
                <div id="activity_pet_name" class="font-medium text-gray-800">-</div>
                <label class="block text-xs font-semibold text-gray-500 mt-2">Owner ID:</label>
                <div id="activity_owner_id" class="font-medium text-gray-800">-</div>
            </div>
            
            {{-- Tab Buttons for Actions --}}
            <div class="col-span-2 flex justify-start items-end">
                <nav class="flex space-x-4">
                    <button type="button" onclick="switchActivityTab('appointment')" data-tab="appointment" class="activity-tab-btn py-2 px-4 border-b-2 font-semibold text-sm active border-purple-600 text-purple-600 hover:border-purple-800">
                        <i class="fas fa-calendar-plus mr-1"></i> Appointment
                    </button>
                    <button type="button" onclick="switchActivityTab('prescription')" data-tab="prescription" class="activity-tab-btn py-2 px-4 border-b-2 font-semibold text-sm border-transparent text-gray-600 hover:text-green-600">
                        <i class="fas fa-prescription mr-1"></i> Prescription
                    </button>
                    <button type="button" onclick="switchActivityTab('referral')" data-tab="referral" class="activity-tab-btn py-2 px-4 border-b-2 font-semibold text-sm border-transparent text-gray-600 hover:text-red-600">
                        <i class="fas fa-share mr-1"></i> Referral
                    </button>
                    <button type="button" onclick="switchActivityTab('initial')" data-tab="initial" class="activity-tab-btn py-2 px-4 border-b-2 font-semibold text-sm border-transparent text-gray-600 hover:text-indigo-600">
                        <i class="fas fa-notes-medical mr-1"></i> Initial Assessment
                    </button>
                </nav>
            </div>
        </div>

        {{-- Scrollable Content Area --}}
        <div class="h-auto"> 
            
            {{-- Appointment Tab Content (Form content remains the same) --}}
            <div id="activity_appointment_content" class="activity-tab-content space-y-4">
                <h4 class="text-lg font-semibold text-blue-600">Set Follow-up Appointment</h4>
                <form id="activityAppointmentForm" action="{{ route('medical.appointments.store') }}" method="POST" class="space-y-4 border border-blue-200 p-4 rounded-lg bg-blue-50">
                    @csrf
                    <input type="hidden" name="pet_id" id="activity_appoint_pet_id">
                    <input type="hidden" name="appoint_status" value="Scheduled">
                    <input type="hidden" name="active_tab" value="visits">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Follow-up Type</label>
                            <select name="appoint_type" id="activity_appoint_type" class="w-full border border-gray-300 p-2 rounded-lg" required>
                                <option value="Follow-up">General Follow-up</option>
                                <option value="Vaccination Follow-up">Vaccination Follow-up</option>
                                <option value="Deworming Follow-up">Deworming Follow-up</option>
                                <option value="Post-Surgical Recheck">Post-Surgical Recheck</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                            <input type="date" name="appoint_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Time</label>
                            <select name="appoint_time" class="w-full border border-gray-300 p-2 rounded-lg" required>
                                @foreach (['09:00:00','10:00:00','11:00:00','13:00:00','14:00:00','15:00:00','16:00:00'] as $time)
                                    <option value="{{ $time }}">{{ \Carbon\Carbon::parse($time)->format('h:i A') }}</option>
                                @endforeach
                            </select>
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

            {{-- Initial Assessment Tab Content --}}
            <div id="activity_initial_content" class="activity-tab-content hidden space-y-4">
                <h4 class="text-lg font-semibold text-indigo-600">Initial Assessment</h4>
                <form id="activityInitialAssessmentForm" action="{{ route('medical.initial_assessments.store') }}" method="POST" onsubmit="return handleInitialAssessmentSubmit(event)" class="space-y-4 border border-indigo-200 p-4 rounded-lg bg-indigo-50">
                    @csrf
                    <input type="hidden" name="pet_id" id="activity_initial_pet_id" value="{{ $visit->pet_id ?? '' }}">
                    <input type="hidden" name="visit_id" id="activity_initial_visit_id" value="{{ $visit->visit_id ?? '' }}">
                    <input type="hidden" name="active_tab" value="visits">

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-gray-300 bg-white">
                            <tbody>
                                <tr class="border-b">
                                    <td class="p-3 align-top w-1/3">
                                        <label class="font-medium">Is your pet sick?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="is_sick" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="is_sick" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top w-1/3">
                                        <label class="font-medium">Has your pet been treated recently?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="been_treated" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="been_treated" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top w-1/3">
                                        <label class="font-medium">Does your pet get table food/human food?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="table_food" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="table_food" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">How many times per day do you feed?</label>
                                        <div class="flex flex-wrap gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Once"> <span>Once</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Twice"> <span>Twice</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="feeding_frequency" value="Thrice"> <span>Thrice</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Is your pet on heartworm preventative?</label>
                                        <div class="flex gap-4 mt-2 flex-wrap">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="No"> <span>No</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="heartworm_preventative" value="No Idea"> <span>No Idea</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Any injury or accident in the past 30 days?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="injury_accident" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="injury_accident" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Allergic To Any Medications/Vaccines?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="allergies" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="allergies" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Had any surgery for the past 30 days?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="surgery_past_30" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="surgery_past_30" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Currently on any medications/vitamins/OTC?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="current_meds" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="current_meds" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Appetite Normal?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="appetite_normal" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="appetite_normal" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Diarrhoea</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="diarrhoea" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="diarrhoea" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Vomiting</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="vomiting" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="vomiting" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Drinking more or less water than usual?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="drinking_unusual" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="drinking_unusual" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Weakness?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="weakness" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="weakness" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Gagging?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="gagging" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="gagging" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Coughing?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="coughing" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="coughing" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Sneezing?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="sneezing" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="sneezing" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Scratching?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scratching" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scratching" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Shaking Head?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="shaking_head" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="shaking_head" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Urinating more or less than usual?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="urinating_unusual" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="urinating_unusual" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Limping? Which Leg?</label>
                                        <div class="flex flex-wrap gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="None"> <span>None</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Front Left"> <span>Front Left</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Front Right"> <span>Front Right</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Back Left"> <span>Back Left</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="limping" value="Back Right"> <span>Back Right</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Scooting?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scooting" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="scooting" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">History of seizures?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="seizures" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="seizures" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Unusually Bad Breath?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="bad_breath" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="bad_breath" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Unusual Discharge?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="discharge" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="discharge" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top">
                                        <label class="font-medium">Did the pet eat this morning?</label>
                                        <div class="flex gap-4 mt-2">
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="ate_this_morning" value="Yes"> <span>Yes</span></label>
                                            <label class="inline-flex items-center gap-2"><input type="radio" name="ate_this_morning" value="No"> <span>No</span></label>
                                        </div>
                                    </td>
                                    <td class="p-3 align-top"></td>
                                </tr>
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

            {{-- Prescription Tab Content (Form content remains the same) --}}
            <div id="activity_prescription_content" class="activity-tab-content hidden space-y-4">
                <h4 class="text-lg font-semibold text-green-600">Add New Prescription</h4>
                <form id="activityPrescriptionForm" onsubmit="return handleActivityPrescriptionSubmit(event)" class="space-y-4 border border-green-200 p-4 rounded-lg bg-green-50">
                    @csrf
                    <input type="hidden" name="pet_id" id="activity_prescription_pet_id">
                    <input type="hidden" name="medications_json" id="activity_medications_json">
                    <input type="hidden" name="active_tab" value="visits">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Prescription Date</label>
                        <input type="date" name="prescription_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                    </div>

                    <div id="activityMedicationContainer" class="space-y-3">
                        {{-- Medication fields added by JS --}}
                    </div>
                    <button type="button" onclick="addActivityMedicationField()" class="bg-indigo-500 text-white px-3 py-1 rounded text-sm hover:bg-indigo-600">
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

            {{-- Referral Tab Content (Form content remains the same) --}}
            <div id="activity_referral_content" class="activity-tab-content hidden space-y-4">
                <h4 class="text-lg font-semibold text-red-600">Create New Referral</h4>
                <form id="activityReferralForm" action="{{ route('medical.referrals.store') }}" method="POST" class="space-y-4 border border-red-200 p-4 rounded-lg bg-red-50">
                    @csrf
                    <input type="hidden" name="appointment_id" id="activity_referral_appoint_id" value="{{ $visit->visit_id ?? '' }}">
                    <input type="hidden" name="active_tab" value="visits">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Referral Date</label>
                            <input type="date" name="ref_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full border border-gray-300 p-2 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Refer To Branch</label>
                            <select name="ref_to" class="w-full border border-gray-300 p-2 rounded-lg" required>
                                <option value="">Select Branch</option>
                                @foreach($allBranches as $branch)
                                    <option value="{{ $branch->branch_id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>
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
            </div>
        </div>
    </div>
</div>

<script>
    // Global state
    let availablePrescriptionProducts = @json($allProducts);
    let activityMedicationCounter = 0;

    function switchActivityTab(tabName) {
        document.querySelectorAll('.activity-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        document.querySelectorAll('.activity-tab-btn').forEach(button => {
            button.classList.remove('active');
        });
        
        document.getElementById(`activity_${tabName}_content`).classList.remove('hidden');
        document.querySelector(`.activity-tab-btn[data-tab="${tabName}"]`).classList.add('active');
    }

    function openActivityModal(petId, ownerId, currentService) {
        const modal = document.getElementById('serviceActivityModal');
        const petName = document.getElementById('activity_pet_name');
        
        // Find pet name from allPets global var or default
        const pet = @json($allPets).find(p => String(p.pet_id) === String(petId));

        // 1. Set Pet/Owner context
        petName.textContent = pet?.pet_name || 'N/A';
        document.getElementById('activity_owner_id').textContent = pet?.owner.own_name;
        document.getElementById('activity_appoint_pet_id').value = petId;
        document.getElementById('activity_prescription_pet_id').value = petId;
        const initPetInput = document.getElementById('activity_initial_pet_id');
        const initVisitInput = document.getElementById('activity_initial_visit_id');
        if (initPetInput) initPetInput.value = petId;
        if (initVisitInput) initVisitInput.value = String({{ $visit->visit_id ?? '""' }});
        
        // 2. Pre-fill default values for Appointment Type
        document.getElementById('activity_appoint_type').value = 
            currentService.includes('Vaccination') ? 'Vaccination Follow-up' : 
            currentService.includes('Deworming') ? 'Deworming Follow-up' : 'Follow-up';

        // 3. Reset Prescription fields
        document.getElementById('activityPrescriptionForm').reset();
        document.getElementById('activityMedicationContainer').innerHTML = '';
        activityMedicationCounter = 0;
        addActivityMedicationField();
        
        // 4. Show default tab and modal
        switchActivityTab('appointment');
        modal.classList.remove('hidden');
    }

    function closeActivityModal() {
        document.getElementById('serviceActivityModal').classList.add('hidden');
    }
    
    // --- Prescription Sub-Functions ---

    function addActivityMedicationField() {
        const container = document.getElementById('activityMedicationContainer');
        const fieldId = ++activityMedicationCounter;
        
        const fieldHtml = `
            <div class="medication-field border border-gray-300 p-3 rounded-lg bg-white" data-field-id="${fieldId}">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-medium text-gray-700">Medication ${fieldId}</h4>
                    ${fieldId > 1 ? `<button type="button" onclick="removeActivityMedicationField(${fieldId})" class="text-red-500 hover:text-red-700 text-xs"><i class="fas fa-trash"></i> Remove</button>` : ''}
                </div>
                <div class="relative mb-3">
                    <label class="block text-xs text-gray-600 mb-1">Product Name / Manual Entry</label>
                    <input type="text" class="product-search w-full border px-2 py-2 rounded-lg text-sm" placeholder="Search product or enter manually" data-field-id="${fieldId}">
                    <div class="product-suggestions absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-32 overflow-y-auto hidden" data-field-id="${fieldId}"></div>
                    <input type="hidden" class="selected-product-id" data-field-id="${fieldId}">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Instructions (Sig.)</label>
                    <textarea class="medication-instructions w-full border px-2 py-2 rounded-lg text-sm" rows="2" data-field-id="${fieldId}" placeholder="e.g., Take 1 capsule twice daily for 7 days" required></textarea>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', fieldHtml);
        setupActivityProductSearch(fieldId);
    }

    function removeActivityMedicationField(fieldId) {
        const field = document.querySelector(`#activityMedicationContainer .medication-field[data-field-id="${fieldId}"]`);
        if (field) {
            field.remove();
        }
    }

    function setupActivityProductSearch(fieldId) {
        const searchInput = document.querySelector(`.product-search[data-field-id="${fieldId}"]`);
        const suggestionsDiv = document.querySelector(`.product-suggestions[data-field-id="${fieldId}"]`);
        const productIdInput = document.querySelector(`.selected-product-id[data-field-id="${fieldId}"]`);

        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            clearTimeout(searchTimeout);

            // Clear selected product ID on manual typing
            productIdInput.value = '';

            if (query.length < 2) {
                suggestionsDiv.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(() => {
                const filtered = availablePrescriptionProducts.filter(p => p.prod_name.toLowerCase().includes(query));
                suggestionsDiv.innerHTML = '';
                
                if (filtered.length > 0) {
                    filtered.forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'product-suggestion-item px-3 py-2 cursor-pointer hover:bg-blue-50 text-sm';
                        item.innerHTML = `<div>${product.prod_name}</div><div class="text-xs text-gray-500">Stock: ${product.prod_stocks} - ₱${parseFloat(product.prod_price || 0).toFixed(2)}</div>`;
                        
                        item.onclick = function() {
                            productIdInput.value = product.prod_id;
                            searchInput.value = product.prod_name;
                            suggestionsDiv.classList.add('hidden');
                            searchInput.focus();
                        };
                        suggestionsDiv.appendChild(item);
                    });
                    suggestionsDiv.classList.remove('hidden');
                } else {
                    suggestionsDiv.innerHTML = '<div class="px-3 py-2 text-gray-500 text-xs">No matching products found.</div>';
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

    function handleActivityPrescriptionSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const medications = [];
        let isValid = true;
        
        document.querySelectorAll('#activityMedicationContainer .medication-field').forEach(field => {
            const fieldId = field.dataset.fieldId;
            const searchInput = document.querySelector(`input.product-search[data-field-id="${fieldId}"]`);
            const productIdInput = document.querySelector(`input.selected-product-id[data-field-id="${fieldId}"]`);
            const instructionsTextarea = document.querySelector(`textarea.medication-instructions[data-field-id="${fieldId}"]`);
            
            const productName = searchInput.value.trim();
            const instructions = instructionsTextarea.value.trim();
            
            if (productName && instructions) {
                medications.push({
                    product_id: productIdInput.value || null,
                    product_name: productName,
                    instructions: instructions
                });
            } else if (productName || instructions) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            alert('Please ensure all medication fields are complete or empty.');
            return;
        }

        if (medications.length === 0) {
            alert('Please add at least one medication.');
            return;
        }
        
        // Finalize data
        document.getElementById('activity_medications_json').value = JSON.stringify(medications);
        form.action = "{{ route('medical.prescriptions.store') }}"; // Set the correct submission route
        
        // Submit the form
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        
        form.submit();
    }

    // Toast notification function
function showToast(type, message) {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type === 'success' ? 'toast-success' : 'toast-error'}`;
    
    // Create toast content with icon
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${icon} mr-2"></i>
            <span>${message}</span>
        </div>
    `;

    container.appendChild(toast);

    // Remove toast after 3 seconds with fade out animation
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.5s ease-out forwards';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 500);
    }, 3000);
}

function handleInitialAssessmentSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    // Show loading state
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
            // Show success message
            showToast('success', data.message || 'Initial assessment saved successfully!');
            
            // Close the modal after a delay
            setTimeout(() => {
                closeActivityModal();
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
</script>