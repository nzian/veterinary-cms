{{-- Add Appointment Modal --}}
<div id="addAppointmentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Add Follow-Up Appointment</h2>
            <button class="text-gray-600 hover:text-gray-900" onclick="closeModal('addAppointmentModal')">&times;</button>
        </div>

        <form id="addAppointmentForm">
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="font-medium">Pet</label>
                    <select name="pet_id" class="w-full border rounded p-2" required></select>
                </div>

                <div>
                    <label class="font-medium">Appointment Date</label>
                    <input type="date" name="appoint_date" class="w-full border rounded p-2" required>
                </div>

                <div>
                    <label class="font-medium">Follow-up Type</label>
                    <select name="appoint_type" class="w-full border rounded p-2" required>
                        <option value="General Follow-up">General Follow-up</option>
                        <option value="Vaccination Follow-up">Vaccination Follow-up</option>
                        <option value="Deworming Follow-up">Deworming Follow-up</option>
                        <option value="Post-Surgical Recheck">Post-Surgical Recheck</option>
                    </select>
                </div>

                <div>
                    <label class="font-medium">Purpose / Description</label>
                    <textarea name="appoint_description" class="w-full border rounded p-2" rows="3"></textarea>
                </div>
                
                <!-- Hidden fields required by medical management -->
                <input type="hidden" name="appoint_status" value="scheduled">
                <input type="hidden" name="active_tab" value="appointments">
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('addAppointmentModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Save</button>
            </div>
        </form>
    </div>
</div>


{{-- Edit Appointment Modal --}}
<div id="editAppointmentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Edit Appointment</h2>
            <button class="text-gray-600 hover:text-gray-900" onclick="closeModal('editAppointmentModal')">&times;</button>
        </div>

        <form id="editAppointmentForm">
            <input type="hidden" name="appoint_id">

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="font-medium">Pet</label>
                    <select name="pet_id" class="w-full border rounded p-2"></select>
                </div>

                <div>
                    <label class="font-medium">Appointment Date</label>
                    <input type="date" name="appoint_date" class="w-full border rounded p-2">
                </div>

                <div>
                    <label class="font-medium">Follow-up Type</label>
                    <select name="appoint_type" class="w-full border rounded p-2">
                        <option value="General Follow-up">General Follow-up</option>
                        <option value="Vaccination Follow-up">Vaccination Follow-up</option>
                        <option value="Deworming Follow-up">Deworming Follow-up</option>
                        <option value="Post-Surgical Recheck">Post-Surgical Recheck</option>
                    </select>
                </div>

                <div>
                    <label class="font-medium">Purpose / Description</label>
                    <textarea name="appoint_description" class="w-full border rounded p-2" rows="3"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('editAppointmentModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded">Update</button>
            </div>
        </form>
    </div>
</div>


{{-- Add Prescription Modal --}}
<div id="addPrescriptionModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Add Prescription</h2>
            <button class="text-gray-600 hover:text-gray-900" onclick="closeModal('addPrescriptionModal')">&times;</button>
        </div>

        <form id="addPrescriptionForm">
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="font-medium">Pet</label>
                    <select name="pet_id" class="w-full border rounded p-2" required></select>
                </div>

                <div>
                    <label class="font-medium">Medicine</label>
                    <input type="text" name="medicine" class="w-full border rounded p-2" required>
                </div>

                <div>
                    <label class="font-medium">Dosage</label>
                    <input type="text" name="dosage" class="w-full border rounded p-2" required>
                </div>

                <div>
                    <label class="font-medium">Instructions</label>
                    <textarea name="instructions" class="w-full border rounded p-2" rows="3"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('addPrescriptionModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">Save</button>
            </div>
        </form>
    </div>
</div>


{{-- Add Referral Modal --}}
<div id="addReferralModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white w-full max-w-lg rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Add Referral</h2>
            <button class="text-gray-600 hover:text-gray-900" onclick="closeModal('addReferralModal')">&times;</button>
        </div>

        <form id="addReferralForm">
            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="font-medium">Pet</label>
                    <select name="pet_id" class="w-full border rounded p-2" required></select>
                </div>

                <div>
                    <label class="font-medium">Referred To (Branch)</label>
                    <select name="ref_to" class="w-full border rounded p-2" required>
                        <option value="">Select Branch</option>
                    </select>
                </div>

                <div>
                    <label class="font-medium">Description</label>
                    <textarea name="ref_description" class="w-full border rounded p-2" rows="3"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('addReferralModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded">Submit</button>
            </div>
        </form>
    </div>
</div>
